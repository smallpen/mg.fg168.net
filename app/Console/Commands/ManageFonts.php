<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\FontService;

class ManageFonts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fonts:manage 
                            {action : Action to perform (status|install|test)}
                            {--format=table : Output format (table|json)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Manage PDF fonts for Chinese character support';

    protected FontService $fontService;

    public function __construct(FontService $fontService)
    {
        parent::__construct();
        $this->fontService = $fontService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $action = $this->argument('action');
        $format = $this->option('format');

        switch ($action) {
            case 'status':
                $this->showFontStatus($format);
                break;
            case 'install':
                $this->installFonts();
                break;
            case 'test':
                $this->testFontSupport();
                break;
            default:
                $this->error("Unknown action: {$action}");
                $this->info("Available actions: status, install, test");
                return 1;
        }

        return 0;
    }

    /**
     * 顯示字體狀態
     */
    protected function showFontStatus(string $format): void
    {
        $this->info('🔍 檢查字體狀態...');
        
        $report = $this->fontService->getFontStatusReport();

        if ($format === 'json') {
            $this->line(json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            return;
        }

        // 顯示基本資訊
        $this->info("\n📁 字體目錄資訊:");
        $this->table(
            ['項目', '狀態'],
            [
                ['字體目錄', $report['font_directory']],
                ['目錄存在', $report['directory_exists'] ? '✅ 是' : '❌ 否'],
                ['目錄可寫', $report['directory_writable'] ? '✅ 是' : '❌ 否'],
                ['中文支援', $report['chinese_support'] ? '✅ 支援' : '❌ 不支援'],
            ]
        );

        // 顯示系統字體
        $this->info("\n🖥️ 系統字體:");
        if (empty($report['system_fonts'])) {
            $this->warn('未找到可用的系統字體');
        } else {
            foreach ($report['system_fonts'] as $font) {
                $this->line("  ✅ {$font}");
            }
        }

        // 顯示已安裝字體
        $this->info("\n📦 已安裝字體:");
        foreach ($report['installed_fonts'] as $key => $fontInfo) {
            $status = $fontInfo['installed'] ? '✅ 已安裝' : '❌ 未安裝';
            $this->line("  {$fontInfo['name']}: {$status}");
        }

        // 顯示建議
        $this->info("\n💡 建議:");
        if (!$report['chinese_support']) {
            $this->warn('  • 執行 "php artisan fonts:manage install" 安裝中文字體支援');
            $this->warn('  • 或使用 HTML 格式匯出以獲得更好的中文支援');
        } else {
            $this->info('  • 字體配置正常，PDF 匯出應該能正確顯示中文');
        }
    }

    /**
     * 安裝字體
     */
    protected function installFonts(): void
    {
        $this->info('🔧 安裝中文字體支援...');

        if ($this->fontService->hasChineseFontSupport()) {
            $this->info('✅ 中文字體支援已存在');
            return;
        }

        $this->info('📥 嘗試安裝系統字體...');
        
        if ($this->fontService->installSystemChineseFonts()) {
            $this->info('✅ 字體安裝成功');
            
            // 重新檢查狀態
            if ($this->fontService->hasChineseFontSupport()) {
                $this->info('✅ 中文字體支援已啟用');
            } else {
                $this->warn('⚠️ 字體已安裝但中文支援仍未完全啟用');
                $this->info('💡 建議使用 HTML 格式匯出以獲得最佳中文支援');
            }
        } else {
            $this->error('❌ 字體安裝失敗');
            $this->info('💡 建議:');
            $this->info('  • 檢查系統是否有可用的中文字體');
            $this->info('  • 使用 HTML 格式匯出作為替代方案');
        }
    }

    /**
     * 測試字體支援
     */
    protected function testFontSupport(): void
    {
        $this->info('🧪 測試字體支援...');

        // 測試基本字體配置
        $config = $this->fontService->getRecommendedPdfFontConfig();
        
        $this->info("\n📋 字體配置:");
        $this->table(
            ['設定', '值'],
            [
                ['字體系列', $config['font_family']],
                ['中文支援', $config['supports_chinese'] ? '✅ 支援' : '❌ 不支援'],
                ['備註', $config['note'] ?? '無'],
            ]
        );

        // 測試字體檔案
        $this->info("\n📁 字體檔案測試:");
        $fontDir = storage_path('fonts');
        
        if (!is_dir($fontDir)) {
            $this->error("❌ 字體目錄不存在: {$fontDir}");
            return;
        }

        $fontFiles = glob($fontDir . '/*.{ttf,otf,ttc}', GLOB_BRACE);
        
        if (empty($fontFiles)) {
            $this->warn('⚠️ 未找到字體檔案');
        } else {
            foreach ($fontFiles as $fontFile) {
                $fileName = basename($fontFile);
                $fileSize = number_format(filesize($fontFile) / 1024, 1);
                $this->info("  ✅ {$fileName} ({$fileSize} KB)");
            }
        }

        // 測試建議
        $this->info("\n💡 測試結果:");
        if ($config['supports_chinese']) {
            $this->info('  ✅ PDF 匯出應該能正確顯示中文字符');
        } else {
            $this->warn('  ⚠️ PDF 匯出中文字符可能顯示為方框');
            $this->info('  💡 建議使用 HTML 格式匯出以獲得完美的中文支援');
        }
    }
}
