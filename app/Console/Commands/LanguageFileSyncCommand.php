<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\LanguageFileValidator;
use App\Services\MultilingualLogger;
use Illuminate\Support\Facades\File;

/**
 * 語言檔案同步命令
 * 
 * 同步不同語言檔案之間的翻譯鍵，確保所有語言版本保持一致
 */
class LanguageFileSyncCommand extends Command
{
    /**
     * 命令名稱和簽名
     */
    protected $signature = 'lang:sync 
                            {--source=zh_TW : 來源語言（作為基準）}
                            {--target= : 目標語言（留空則同步所有語言）}
                            {--dry-run : 只顯示將要執行的操作，不實際修改檔案}
                            {--backup : 同步前先備份目標檔案}
                            {--force : 強制覆蓋現有翻譯}';

    /**
     * 命令描述
     */
    protected $description = '同步語言檔案之間的翻譯鍵，確保所有語言版本保持一致';

    /**
     * 語言檔案驗證器
     */
    private LanguageFileValidator $validator;

    /**
     * 多語系日誌記錄器
     */
    private MultilingualLogger $logger;

    /**
     * 支援的語言列表
     */
    private array $supportedLocales;

    /**
     * 建構函數
     */
    public function __construct(LanguageFileValidator $validator, MultilingualLogger $logger)
    {
        parent::__construct();
        $this->validator = $validator;
        $this->logger = $logger;
        $this->supportedLocales = config('app.supported_locales', ['zh_TW', 'en']);
    }

    /**
     * 執行命令
     */
    public function handle(): int
    {
        $sourceLocale = $this->option('source');
        $targetLocale = $this->option('target');
        $isDryRun = $this->option('dry-run');
        $shouldBackup = $this->option('backup');
        $forceOverwrite = $this->option('force');

        $this->info('🔄 開始語言檔案同步...');
        $this->line('');

        // 驗證來源語言
        if (!in_array($sourceLocale, $this->supportedLocales)) {
            $this->error("❌ 不支援的來源語言: {$sourceLocale}");
            return Command::FAILURE;
        }

        // 確定目標語言列表
        $targetLocales = $targetLocale 
            ? [$targetLocale] 
            : array_filter($this->supportedLocales, fn($locale) => $locale !== $sourceLocale);

        // 驗證目標語言
        foreach ($targetLocales as $locale) {
            if (!in_array($locale, $this->supportedLocales)) {
                $this->error("❌ 不支援的目標語言: {$locale}");
                return Command::FAILURE;
            }
        }

        $this->info("📋 同步設定:");
        $this->line("   來源語言: {$sourceLocale}");
        $this->line("   目標語言: " . implode(', ', $targetLocales));
        $this->line("   模式: " . ($isDryRun ? '預覽模式' : '實際執行'));
        $this->line("   備份: " . ($shouldBackup ? '是' : '否'));
        $this->line("   強制覆蓋: " . ($forceOverwrite ? '是' : '否'));
        $this->line('');

        try {
            $syncResults = [];
            
            foreach ($targetLocales as $target) {
                $result = $this->syncLanguageFiles($sourceLocale, $target, $isDryRun, $shouldBackup, $forceOverwrite);
                $syncResults[$target] = $result;
            }

            $this->displaySyncResults($syncResults);
            $this->logSyncResults($sourceLocale, $syncResults);

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error('❌ 同步過程中發生錯誤: ' . $e->getMessage());
            $this->logger->logLanguageFileLoadError('sync', $sourceLocale, $e->getMessage());
            return Command::FAILURE;
        }
    }

    /**
     * 同步語言檔案
     */
    private function syncLanguageFiles(string $sourceLocale, string $targetLocale, bool $isDryRun, bool $shouldBackup, bool $forceOverwrite): array
    {
        $this->info("🔄 同步 {$sourceLocale} → {$targetLocale}");

        $sourceDir = lang_path($sourceLocale);
        $targetDir = lang_path($targetLocale);

        if (!File::isDirectory($sourceDir)) {
            throw new \Exception("來源語言目錄不存在: {$sourceDir}");
        }

        // 確保目標目錄存在
        if (!File::isDirectory($targetDir)) {
            if (!$isDryRun) {
                File::makeDirectory($targetDir, 0755, true);
                $this->line("   ✅ 建立目標目錄: {$targetDir}");
            } else {
                $this->line("   📁 將建立目標目錄: {$targetDir}");
            }
        }

        $sourceFiles = File::files($sourceDir);
        $syncResult = [
            'files_processed' => 0,
            'keys_added' => 0,
            'keys_updated' => 0,
            'keys_skipped' => 0,
            'files_created' => 0,
            'files_backed_up' => 0,
            'errors' => []
        ];

        foreach ($sourceFiles as $sourceFile) {
            $filename = $sourceFile->getFilename();
            $targetFile = $targetDir . '/' . $filename;

            try {
                $result = $this->syncSingleFile($sourceFile->getPathname(), $targetFile, $isDryRun, $shouldBackup, $forceOverwrite);
                
                $syncResult['files_processed']++;
                $syncResult['keys_added'] += $result['keys_added'];
                $syncResult['keys_updated'] += $result['keys_updated'];
                $syncResult['keys_skipped'] += $result['keys_skipped'];
                
                if ($result['file_created']) {
                    $syncResult['files_created']++;
                }
                
                if ($result['file_backed_up']) {
                    $syncResult['files_backed_up']++;
                }

                $this->line("   📄 {$filename}: +{$result['keys_added']} ~{$result['keys_updated']} -{$result['keys_skipped']}");

            } catch (\Exception $e) {
                $syncResult['errors'][] = "檔案 {$filename}: " . $e->getMessage();
                $this->error("   ❌ {$filename}: " . $e->getMessage());
            }
        }

        return $syncResult;
    }

    /**
     * 同步單個檔案
     */
    private function syncSingleFile(string $sourceFilePath, string $targetFilePath, bool $isDryRun, bool $shouldBackup, bool $forceOverwrite): array
    {
        $result = [
            'keys_added' => 0,
            'keys_updated' => 0,
            'keys_skipped' => 0,
            'file_created' => false,
            'file_backed_up' => false
        ];

        // 載入來源檔案
        $sourceContent = include $sourceFilePath;
        $sourceKeys = $this->flattenArray($sourceContent);

        // 載入目標檔案（如果存在）
        $targetContent = [];
        $targetKeys = [];
        
        if (File::exists($targetFilePath)) {
            $targetContent = include $targetFilePath;
            $targetKeys = $this->flattenArray($targetContent);
            
            // 備份現有檔案
            if ($shouldBackup && !$isDryRun) {
                $backupPath = $targetFilePath . '.backup.' . now()->format('Y-m-d_H-i-s');
                File::copy($targetFilePath, $backupPath);
                $result['file_backed_up'] = true;
            }
        } else {
            $result['file_created'] = true;
        }

        // 合併翻譯鍵
        $mergedContent = $targetContent;
        
        foreach ($sourceKeys as $key => $value) {
            if (!array_key_exists($key, $targetKeys)) {
                // 新增缺少的鍵
                $this->setNestedArrayValue($mergedContent, $key, $this->getPlaceholderTranslation($key, $value));
                $result['keys_added']++;
            } elseif ($forceOverwrite) {
                // 強制覆蓋現有鍵
                $this->setNestedArrayValue($mergedContent, $key, $this->getPlaceholderTranslation($key, $value));
                $result['keys_updated']++;
            } else {
                // 跳過現有鍵
                $result['keys_skipped']++;
            }
        }

        // 寫入檔案
        if (!$isDryRun && ($result['keys_added'] > 0 || $result['keys_updated'] > 0 || $result['file_created'])) {
            $this->writeLanguageFile($targetFilePath, $mergedContent);
        }

        return $result;
    }

    /**
     * 取得佔位符翻譯
     */
    private function getPlaceholderTranslation(string $key, string $sourceValue): string
    {
        // 對於新的翻譯鍵，使用鍵名作為佔位符，並加上 [需要翻譯] 標記
        return "[需要翻譯] {$key}";
    }

    /**
     * 設定巢狀陣列值
     */
    private function setNestedArrayValue(array &$array, string $key, $value): void
    {
        $keys = explode('.', $key);
        $current = &$array;
        
        foreach ($keys as $k) {
            if (!isset($current[$k]) || !is_array($current[$k])) {
                $current[$k] = [];
            }
            $current = &$current[$k];
        }
        
        $current = $value;
    }

    /**
     * 將多維陣列扁平化
     */
    private function flattenArray(array $array, string $prefix = ''): array
    {
        $result = [];
        
        foreach ($array as $key => $value) {
            $newKey = $prefix ? $prefix . '.' . $key : $key;
            
            if (is_array($value)) {
                $result = array_merge($result, $this->flattenArray($value, $newKey));
            } else {
                $result[$newKey] = $value;
            }
        }
        
        return $result;
    }

    /**
     * 寫入語言檔案
     */
    private function writeLanguageFile(string $filePath, array $content): void
    {
        $phpContent = "<?php\n\nreturn " . $this->arrayToPhpString($content, 0) . ";\n";
        File::put($filePath, $phpContent);
    }

    /**
     * 將陣列轉換為格式化的 PHP 字串
     */
    private function arrayToPhpString(array $array, int $indent = 0): string
    {
        $indentStr = str_repeat('    ', $indent);
        $nextIndentStr = str_repeat('    ', $indent + 1);
        
        $lines = ["["];
        
        foreach ($array as $key => $value) {
            $keyStr = is_string($key) ? "'{$key}'" : $key;
            
            if (is_array($value)) {
                $valueStr = $this->arrayToPhpString($value, $indent + 1);
                $lines[] = "{$nextIndentStr}{$keyStr} => {$valueStr},";
            } else {
                $valueStr = is_string($value) ? "'" . addslashes($value) . "'" : var_export($value, true);
                $lines[] = "{$nextIndentStr}{$keyStr} => {$valueStr},";
            }
        }
        
        $lines[] = "{$indentStr}]";
        
        return implode("\n", $lines);
    }

    /**
     * 顯示同步結果
     */
    private function displaySyncResults(array $syncResults): void
    {
        $this->line('');
        $this->info('📊 同步結果摘要:');
        
        $totalProcessed = 0;
        $totalAdded = 0;
        $totalUpdated = 0;
        $totalSkipped = 0;
        $totalCreated = 0;
        $totalBackedUp = 0;
        $totalErrors = 0;

        foreach ($syncResults as $locale => $result) {
            $totalProcessed += $result['files_processed'];
            $totalAdded += $result['keys_added'];
            $totalUpdated += $result['keys_updated'];
            $totalSkipped += $result['keys_skipped'];
            $totalCreated += $result['files_created'];
            $totalBackedUp += $result['files_backed_up'];
            $totalErrors += count($result['errors']);
        }

        $this->table(
            ['項目', '數量'],
            [
                ['處理的檔案', $totalProcessed],
                ['新增的翻譯鍵', $totalAdded],
                ['更新的翻譯鍵', $totalUpdated],
                ['跳過的翻譯鍵', $totalSkipped],
                ['建立的檔案', $totalCreated],
                ['備份的檔案', $totalBackedUp],
                ['錯誤數量', $totalErrors],
            ]
        );

        // 顯示錯誤詳情
        foreach ($syncResults as $locale => $result) {
            if (!empty($result['errors'])) {
                $this->warn("❌ {$locale} 的錯誤:");
                foreach ($result['errors'] as $error) {
                    $this->line("   - {$error}");
                }
            }
        }

        if ($totalAdded > 0 || $totalUpdated > 0) {
            $this->line('');
            $this->comment('💡 提示: 請檢查標記為 [需要翻譯] 的項目並提供正確的翻譯');
        }
    }

    /**
     * 記錄同步結果
     */
    private function logSyncResults(string $sourceLocale, array $syncResults): void
    {
        foreach ($syncResults as $targetLocale => $result) {
            $context = [
                'source_locale' => $sourceLocale,
                'target_locale' => $targetLocale,
                'files_processed' => $result['files_processed'],
                'keys_added' => $result['keys_added'],
                'keys_updated' => $result['keys_updated'],
                'keys_skipped' => $result['keys_skipped'],
                'files_created' => $result['files_created'],
                'files_backed_up' => $result['files_backed_up'],
                'errors_count' => count($result['errors']),
                'command_options' => [
                    'dry_run' => $this->option('dry-run'),
                    'backup' => $this->option('backup'),
                    'force' => $this->option('force'),
                ],
            ];

            if (empty($result['errors'])) {
                $this->logger->logLanguagePreferenceUpdate('sync_completed', 'command', $context);
            } else {
                $this->logger->logLanguageFileLoadError('sync', $targetLocale, implode('; ', $result['errors']), $context);
            }
        }
    }
}