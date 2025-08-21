<?php

namespace App\Console\Commands;

use App\Livewire\Admin\Settings\SettingPreview;
use App\Services\ConfigurationService;
use Illuminate\Console\Command;

/**
 * 設定預覽功能演示命令
 */
class DemoSettingPreview extends Command
{
    /**
     * 命令名稱
     */
    protected $signature = 'demo:setting-preview';

    /**
     * 命令描述
     */
    protected $description = '演示設定預覽和測試功能';

    /**
     * 執行命令
     */
    public function handle()
    {
        $this->info('=== 設定預覽和測試功能演示 ===');
        $this->newLine();

        // 演示配置服務
        $this->demonstrateConfigurationService();
        
        // 演示預覽元件
        $this->demonstratePreviewComponent();
        
        // 演示連線測試
        $this->demonstrateConnectionTests();
        
        // 演示影響分析
        $this->demonstrateImpactAnalysis();

        $this->newLine();
        $this->info('演示完成！');
    }

    /**
     * 演示配置服務功能
     */
    protected function demonstrateConfigurationService()
    {
        $this->info('1. 配置服務功能演示');
        $this->line('   - 取得設定配置');
        $this->line('   - 驗證設定值');
        $this->line('   - 生成預覽資料');
        
        $configService = app(ConfigurationService::class);
        
        // 取得外觀設定配置
        $config = $configService->getSettingConfig('appearance.primary_color');
        $this->line("   外觀主色配置: " . json_encode($config, JSON_UNESCAPED_UNICODE));
        
        // 驗證顏色值
        $isValid = $configService->validateSettingValue('appearance.primary_color', '#FF0000');
        $this->line("   顏色值 #FF0000 驗證結果: " . ($isValid ? '有效' : '無效'));
        
        $this->newLine();
    }

    /**
     * 演示預覽元件功能
     */
    protected function demonstratePreviewComponent()
    {
        $this->info('2. 預覽元件功能演示');
        $this->line('   - 主題預覽');
        $this->line('   - CSS 變數生成');
        $this->line('   - 預覽模式切換');
        
        $component = new SettingPreview();
        
        // 設定預覽資料
        $component->previewSettings = [
            'appearance.primary_color' => '#FF6B6B',
            'appearance.secondary_color' => '#4ECDC4',
            'appearance.default_theme' => 'dark',
        ];
        
        // 更新主題預覽
        $reflection = new \ReflectionClass($component);
        $method = $reflection->getMethod('updateThemePreview');
        $method->setAccessible(true);
        $method->invoke($component);
        
        $this->line("   主題預覽資料: " . json_encode($component->themePreview, JSON_UNESCAPED_UNICODE));
        
        // 生成 CSS 變數
        $cssVars = $component->previewCssVariables();
        $this->line("   CSS 變數: {$cssVars}");
        
        $this->newLine();
    }

    /**
     * 演示連線測試功能
     */
    protected function demonstrateConnectionTests()
    {
        $this->info('3. 連線測試功能演示');
        
        $configService = app(ConfigurationService::class);
        
        // 測試 SMTP 連線（使用無效配置）
        $this->line('   測試 SMTP 連線...');
        $smtpResult = $configService->testConnection('smtp', [
            'host' => 'invalid.smtp.server',
            'port' => 587,
        ]);
        $this->line("   SMTP 測試結果: " . ($smtpResult ? '成功' : '失敗'));
        
        // 測試 Google OAuth 連線（使用無效配置）
        $this->line('   測試 Google OAuth 連線...');
        $oauthResult = $configService->testConnection('google_oauth', [
            'client_id' => 'invalid_client_id',
            'client_secret' => 'invalid_client_secret',
        ]);
        $this->line("   Google OAuth 測試結果: " . ($oauthResult ? '成功' : '失敗'));
        
        // 測試 AWS S3 連線
        $this->line('   測試 AWS S3 連線...');
        $s3Result = $configService->testConnection('aws_s3', [
            'access_key' => 'invalid_key',
            'secret_key' => 'invalid_secret',
            'region' => 'us-east-1',
            'bucket' => 'test-bucket',
        ]);
        $this->line("   AWS S3 測試結果: " . ($s3Result ? '成功' : '失敗'));
        
        $this->newLine();
    }

    /**
     * 演示影響分析功能
     */
    protected function demonstrateImpactAnalysis()
    {
        $this->info('4. 設定變更影響分析演示');
        
        $component = new SettingPreview();
        
        // 分析維護模式設定的影響
        $component->analyzeImpact('maintenance.maintenance_mode', true);
        
        $impacts = $component->impactAnalysis['maintenance.maintenance_mode'] ?? [];
        $this->line("   維護模式設定影響分析:");
        
        foreach ($impacts as $impact) {
            $severity = $impact['severity'] ?? 'low';
            $severityText = match($severity) {
                'high' => '高',
                'medium' => '中',
                'low' => '低',
                default => '未知'
            };
            
            $this->line("   - {$impact['title']} (影響程度: {$severityText})");
            $this->line("     {$impact['description']}");
        }
        
        // 檢查是否有高影響變更
        $hasHighImpact = $component->hasHighImpactChanges();
        $this->line("   是否包含高影響變更: " . ($hasHighImpact ? '是' : '否'));
        
        $this->newLine();
    }
}