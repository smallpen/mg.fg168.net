<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\LanguageFileValidator;

class ValidateLanguageFiles extends Command
{
    /**
     * 命令名稱和簽名
     */
    protected $signature = 'lang:validate 
                            {--report : Generate detailed report}
                            {--hardcoded : Check for hardcoded text only}
                            {--missing : Check for missing keys only}
                            {--export= : Export report to file}';

    /**
     * 命令描述
     */
    protected $description = 'Validate language files for completeness and consistency';

    /**
     * 語言檔案驗證器
     */
    private LanguageFileValidator $validator;

    /**
     * 建立新的命令實例
     */
    public function __construct(LanguageFileValidator $validator)
    {
        parent::__construct();
        $this->validator = $validator;
    }

    /**
     * 執行命令
     */
    public function handle(): int
    {
        $this->info('🔍 開始驗證語言檔案...');
        $this->newLine();

        try {
            if ($this->option('hardcoded')) {
                return $this->checkHardcodedText();
            }

            if ($this->option('missing')) {
                return $this->checkMissingKeys();
            }

            if ($this->option('report')) {
                return $this->generateDetailedReport();
            }

            return $this->runBasicValidation();

        } catch (\Exception $e) {
            $this->error('❌ 驗證過程中發生錯誤: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }

    /**
     * 執行基本驗證
     */
    private function runBasicValidation(): int
    {
        $result = $this->validator->validateCompleteness();
        $summary = $result['summary'];

        $this->displaySummary($summary);
        $this->displayIssues($result);

        $hasIssues = $summary['missing_files_count'] > 0 ||
                    $summary['files_with_missing_keys'] > 0 ||
                    $summary['total_hardcoded_instances'] > 0;

        if ($hasIssues) {
            $this->warn('⚠️  發現一些問題需要處理');
            $this->info('💡 使用 --report 選項獲取詳細報告');
            return Command::FAILURE;
        }

        $this->info('✅ 語言檔案驗證通過！');
        return Command::SUCCESS;
    }

    /**
     * 檢查硬編碼文字
     */
    private function checkHardcodedText(): int
    {
        $this->info('🔍 檢查硬編碼文字...');
        
        $hardcodedTexts = $this->validator->detectHardcodedText();

        if (empty($hardcodedTexts)) {
            $this->info('✅ 未發現硬編碼文字');
            return Command::SUCCESS;
        }

        $this->warn('⚠️  發現硬編碼文字:');
        $this->newLine();

        foreach ($hardcodedTexts as $filename => $matches) {
            $this->line("📄 <comment>{$filename}</comment>");
            
            foreach ($matches as $match) {
                $this->line("   第 {$match['line']} 行: " . implode(', ', $match['chinese_text']));
                $this->line("   內容: <fg=yellow>" . trim($match['content']) . "</>");
            }
            $this->newLine();
        }

        $totalInstances = array_sum(array_map('count', $hardcodedTexts));
        $this->warn("總計發現 {$totalInstances} 個硬編碼文字實例");

        return Command::FAILURE;
    }

    /**
     * 檢查缺少的翻譯鍵
     */
    private function checkMissingKeys(): int
    {
        $this->info('🔍 檢查缺少的翻譯鍵...');
        
        $missingKeys = $this->validator->findMissingKeys();

        if (empty($missingKeys)) {
            $this->info('✅ 所有語言檔案的翻譯鍵都完整');
            return Command::SUCCESS;
        }

        $this->warn('⚠️  發現缺少的翻譯鍵:');
        $this->newLine();

        foreach ($missingKeys as $filename => $locales) {
            $this->line("📄 <comment>{$filename}</comment>");
            
            foreach ($locales as $locale => $keys) {
                $this->line("   {$locale} 缺少 " . count($keys) . " 個鍵:");
                foreach ($keys as $key) {
                    $this->line("     - {$key}");
                }
            }
            $this->newLine();
        }

        return Command::FAILURE;
    }

    /**
     * 生成詳細報告
     */
    private function generateDetailedReport(): int
    {
        $this->info('📊 生成詳細報告...');
        
        $report = $this->validator->generateReport();
        
        $this->displayDetailedReport($report);

        if ($exportPath = $this->option('export')) {
            $this->exportReport($report, $exportPath);
        }

        return Command::SUCCESS;
    }

    /**
     * 顯示摘要
     */
    private function displaySummary(array $summary): void
    {
        $this->info('📊 驗證摘要:');
        $this->table(
            ['項目', '數量'],
            [
                ['檢查的檔案總數', $summary['total_files_checked']],
                ['缺少的檔案', $summary['missing_files_count']],
                ['有缺少鍵值的檔案', $summary['files_with_missing_keys']],
                ['有額外鍵值的檔案', $summary['files_with_extra_keys']],
                ['有硬編碼文字的檔案', $summary['hardcoded_files_count']],
                ['硬編碼文字總數', $summary['total_hardcoded_instances']],
            ]
        );
    }

    /**
     * 顯示問題
     */
    private function displayIssues(array $result): void
    {
        if (!empty($result['missing_files'])) {
            $this->warn('❌ 缺少的檔案:');
            foreach ($result['missing_files'] as $file) {
                $this->line("   - {$file['file']}: {$file['error']}");
            }
            $this->newLine();
        }

        if (!empty($result['missing_keys'])) {
            $this->warn('⚠️  缺少翻譯鍵的檔案數: ' . count($result['missing_keys']));
            $this->info('💡 使用 --missing 選項查看詳細資訊');
            $this->newLine();
        }

        if (!empty($result['hardcoded_texts'])) {
            $totalHardcoded = array_sum(array_map('count', $result['hardcoded_texts']));
            $this->warn('⚠️  發現硬編碼文字: ' . $totalHardcoded . ' 個實例');
            $this->info('💡 使用 --hardcoded 選項查看詳細資訊');
            $this->newLine();
        }
    }

    /**
     * 顯示詳細報告
     */
    private function displayDetailedReport(array $report): void
    {
        $this->info('📋 詳細報告 (' . $report['timestamp'] . ')');
        $this->newLine();

        $this->info('🌐 支援的語言: ' . implode(', ', $report['supported_locales']));
        $this->newLine();

        $this->displaySummary($report['completeness']['summary']);

        if (!empty($report['missing_keys'])) {
            $this->warn('📝 缺少的翻譯鍵:');
            foreach ($report['missing_keys'] as $filename => $locales) {
                $this->line("  📄 {$filename}");
                foreach ($locales as $locale => $keys) {
                    $this->line("    {$locale}: " . implode(', ', array_slice($keys, 0, 5)) . 
                               (count($keys) > 5 ? ' ...' : ''));
                }
            }
            $this->newLine();
        }

        if (!empty($report['hardcoded_texts'])) {
            $this->warn('🔤 硬編碼文字 (前5個檔案):');
            $count = 0;
            foreach ($report['hardcoded_texts'] as $filename => $matches) {
                if ($count >= 5) break;
                $this->line("  📄 {$filename}: " . count($matches) . " 個實例");
                $count++;
            }
            $this->newLine();
        }

        $this->info('💡 建議:');
        foreach ($report['recommendations'] as $recommendation) {
            $this->line("  • {$recommendation}");
        }
    }

    /**
     * 匯出報告
     */
    private function exportReport(array $report, string $path): void
    {
        $jsonReport = json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        
        if (file_put_contents($path, $jsonReport)) {
            $this->info("📁 報告已匯出至: {$path}");
        } else {
            $this->error("❌ 無法匯出報告至: {$path}");
        }
    }
}