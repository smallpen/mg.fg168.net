<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\LanguageFileValidator;
use App\Services\MultilingualLogger;

/**
 * 多語系驗證命令
 * 
 * 驗證語言檔案的完整性和一致性
 */
class MultilingualValidateCommand extends Command
{
    /**
     * 命令名稱和簽名
     */
    protected $signature = 'multilingual:validate 
                            {--fix : 嘗試自動修復發現的問題}
                            {--report= : 生成詳細報告到指定檔案}
                            {--locale= : 只驗證指定語言}';

    /**
     * 命令描述
     */
    protected $description = '驗證語言檔案的完整性和一致性';

    /**
     * 語言檔案驗證器
     */
    private LanguageFileValidator $validator;

    /**
     * 多語系日誌記錄器
     */
    private MultilingualLogger $logger;

    /**
     * 建構函數
     */
    public function __construct(LanguageFileValidator $validator, MultilingualLogger $logger)
    {
        parent::__construct();
        $this->validator = $validator;
        $this->logger = $logger;
    }

    /**
     * 執行命令
     */
    public function handle(): int
    {
        $this->info('🔍 開始驗證多語系檔案...');
        $this->line('');

        $locale = $this->option('locale');
        $shouldFix = $this->option('fix');
        $reportPath = $this->option('report');

        try {
            // 執行驗證
            $result = $this->validator->validateCompleteness();
            
            // 顯示驗證結果
            $this->displayValidationResults($result);
            
            // 自動修復（如果啟用）
            if ($shouldFix && $this->hasValidationIssues($result)) {
                $this->attemptAutoFix($result);
            }
            
            // 生成報告（如果指定）
            if ($reportPath) {
                $this->generateReport($result, $reportPath);
            }
            
            // 記錄驗證結果
            $this->logValidationResults($result);
            
            return $this->hasValidationIssues($result) ? Command::FAILURE : Command::SUCCESS;
            
        } catch (\Exception $e) {
            $this->error('❌ 驗證過程中發生錯誤: ' . $e->getMessage());
            $this->logger->logLanguageFileLoadError('validation', $locale ?? 'all', $e->getMessage());
            return Command::FAILURE;
        }
    }

    /**
     * 顯示驗證結果
     */
    private function displayValidationResults(array $result): void
    {
        if (!$this->hasValidationIssues($result)) {
            $this->info('✅ 所有語言檔案驗證通過！');
            return;
        }

        $this->error('❌ 發現語言檔案問題：');
        $this->line('');

        // 顯示缺少的翻譯鍵
        if (!empty($result['missing_keys'])) {
            $this->warn('🔑 缺少的翻譯鍵：');
            foreach ($result['missing_keys'] as $file => $locales) {
                $this->comment("  檔案: {$file}");
                foreach ($locales as $locale => $keys) {
                    $this->line("    語言: {$locale}");
                    foreach ($keys as $key) {
                        $this->line("      - {$key}");
                    }
                }
            }
            $this->line('');
        }

        // 顯示多餘的翻譯鍵
        if (!empty($result['extra_keys'])) {
            $this->warn('➕ 多餘的翻譯鍵：');
            foreach ($result['extra_keys'] as $file => $locales) {
                $this->comment("  檔案: {$file}");
                foreach ($locales as $locale => $keys) {
                    $this->line("    語言: {$locale}");
                    foreach ($keys as $key) {
                        $this->line("      - {$key}");
                    }
                }
            }
            $this->line('');
        }

        // 顯示檔案問題
        if (!empty($result['missing_files'])) {
            $this->warn('📁 檔案問題：');
            foreach ($result['missing_files'] as $fileInfo) {
                if (is_array($fileInfo)) {
                    $this->line("  - {$fileInfo['file']}: {$fileInfo['error']}");
                } else {
                    $this->line("  - {$fileInfo}");
                }
            }
            $this->line('');
        }

        // 顯示硬編碼文字
        if (!empty($result['hardcoded_texts'])) {
            $this->warn('💬 發現硬編碼文字：');
            foreach ($result['hardcoded_texts'] as $text) {
                $this->line("  - {$text}");
            }
            $this->line('');
        }

        // 顯示統計資訊
        $this->displayStatistics($result);
    }

    /**
     * 檢查是否有驗證問題
     */
    private function hasValidationIssues(array $result): bool
    {
        return !empty($result['missing_keys']) || 
               !empty($result['extra_keys']) || 
               !empty($result['missing_files']) || 
               !empty($result['hardcoded_texts']);
    }

    /**
     * 顯示統計資訊
     */
    private function displayStatistics(array $result): void
    {
        $missingKeysCount = array_sum(array_map('count', $result['missing_keys'] ?? []));
        $extraKeysCount = array_sum(array_map('count', $result['extra_keys'] ?? []));
        $missingFilesCount = count($result['missing_files'] ?? []);
        $hardcodedTextsCount = count($result['hardcoded_texts'] ?? []);
        
        $this->info('📊 驗證統計：');
        $this->table(
            ['項目', '數量'],
            [
                ['檢查的語言', count(config('multilingual.supported_locales', ['zh_TW', 'en']))],
                ['缺少的鍵', $missingKeysCount],
                ['多餘的鍵', $extraKeysCount],
                ['缺少的檔案', $missingFilesCount],
                ['硬編碼文字', $hardcodedTextsCount],
            ]
        );
        $this->line('');
    }

    /**
     * 嘗試自動修復
     */
    private function attemptAutoFix(array $result): void
    {
        $this->info('🔧 嘗試自動修復問題...');
        
        $fixedCount = 0;
        
        if (!empty($result['missing_keys'])) {
            foreach ($result['missing_keys'] as $locale => $keys) {
                foreach ($keys as $key) {
                    if ($this->fixMissingKey($key, $locale)) {
                        $fixedCount++;
                    }
                }
            }
        }
        
        if ($fixedCount > 0) {
            $this->info("✅ 已修復 {$fixedCount} 個問題");
        } else {
            $this->comment('⚠️  無法自動修復問題，需要手動處理');
        }
    }

    /**
     * 修復缺少的翻譯鍵
     */
    private function fixMissingKey(string $key, string $locale): bool
    {
        // 這裡實作自動修復邏輯
        // 例如：從其他語言複製翻譯、使用預設值等
        
        try {
            // 簡單的修復策略：使用鍵值作為預設翻譯
            $keyParts = explode('.', $key);
            $file = $keyParts[0];
            $keyPath = implode('.', array_slice($keyParts, 1));
            
            $filePath = lang_path("{$locale}/{$file}.php");
            
            if (file_exists($filePath)) {
                $translations = include $filePath;
                
                // 使用點記法設定值
                data_set($translations, $keyPath, $keyPath);
                
                // 寫回檔案
                $content = "<?php\n\nreturn " . var_export($translations, true) . ";\n";
                file_put_contents($filePath, $content);
                
                return true;
            }
            
        } catch (\Exception $e) {
            $this->logger->logLanguageFileLoadError($file ?? 'unknown', $locale, $e->getMessage());
        }
        
        return false;
    }

    /**
     * 生成詳細報告
     */
    private function generateReport(array $result, string $reportPath): void
    {
        $this->info("📄 生成詳細報告到 {$reportPath}...");
        
        $report = [
            'validation_date' => now()->toISOString(),
            'is_valid' => !$this->hasValidationIssues($result),
            'summary' => $result['summary'] ?? [],
            'missing_keys' => $result['missing_keys'] ?? [],
            'extra_keys' => $result['extra_keys'] ?? [],
            'missing_files' => $result['missing_files'] ?? [],
            'hardcoded_texts' => $result['hardcoded_texts'] ?? [],
            'recommendations' => $this->generateRecommendations($result),
        ];
        
        try {
            file_put_contents($reportPath, json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            $this->info('✅ 報告已生成');
        } catch (\Exception $e) {
            $this->error('❌ 報告生成失敗: ' . $e->getMessage());
        }
    }

    /**
     * 生成建議
     */
    private function generateRecommendations(array $result): array
    {
        $recommendations = [];
        
        if (!empty($result['missing_keys'])) {
            $recommendations[] = '建議定期檢查並補充缺少的翻譯鍵';
            $recommendations[] = '考慮使用翻譯管理工具來維護多語言內容';
        }
        
        if (!empty($result['extra_keys'])) {
            $recommendations[] = '清理不再使用的翻譯鍵以減少檔案大小';
        }
        
        if (!empty($result['missing_files'])) {
            $recommendations[] = '建立缺少的語言檔案';
            $recommendations[] = '確保所有必要的語言檔案都存在';
        }
        
        if (!empty($result['hardcoded_texts'])) {
            $recommendations[] = '將硬編碼文字移至語言檔案';
            $recommendations[] = '使用翻譯函數替換硬編碼文字';
        }
        
        return $recommendations;
    }

    /**
     * 記錄驗證結果
     */
    private function logValidationResults(array $result): void
    {
        $hasIssues = $this->hasValidationIssues($result);
        
        $context = [
            'is_valid' => !$hasIssues,
            'statistics' => $result['summary'] ?? [],
            'command_options' => [
                'locale' => $this->option('locale'),
                'fix' => $this->option('fix'),
                'report' => $this->option('report'),
            ],
        ];
        
        if (!$hasIssues) {
            $this->logger->logLanguagePreferenceUpdate('validation_passed', 'command', $context);
        } else {
            if (!empty($result['missing_keys'])) {
                foreach ($result['missing_keys'] as $file => $locales) {
                    foreach ($locales as $locale => $keys) {
                        foreach ($keys as $key) {
                            $this->logger->logMissingTranslationKey($key, $locale, [
                                'source' => 'validation_command',
                                'file' => $file,
                                'total_missing' => count($keys),
                            ]);
                        }
                    }
                }
            }
        }
    }
}