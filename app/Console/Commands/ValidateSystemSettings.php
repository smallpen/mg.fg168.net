<?php

namespace App\Console\Commands;

use App\Services\SystemSettingsValidator;
use Illuminate\Console\Command;

class ValidateSystemSettings extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'settings:validate 
                            {--fix : 嘗試自動修復可修復的問題}
                            {--summary : 顯示配置摘要}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '驗證系統設定配置的完整性和正確性';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('正在驗證系統設定配置...');
        $this->newLine();

        $validator = new SystemSettingsValidator();
        $result = $validator->validateConfiguration();

        if ($result['valid']) {
            $this->info('✅ 系統設定配置驗證通過！');
            
            if ($this->option('summary')) {
                $this->displayConfigurationSummary();
            }
            
            return Command::SUCCESS;
        }

        // 顯示錯誤
        if (!empty($result['errors'])) {
            $this->error('❌ 發現以下錯誤：');
            foreach ($result['errors'] as $error) {
                $this->line("  • {$error}");
            }
            $this->newLine();
        }

        // 顯示警告
        if (!empty($result['warnings'])) {
            $this->warn('⚠️  發現以下警告：');
            foreach ($result['warnings'] as $warning) {
                $this->line("  • {$warning}");
            }
            $this->newLine();
        }

        // 嘗試自動修復
        if ($this->option('fix')) {
            $this->info('正在嘗試自動修復...');
            $this->attemptAutoFix($result);
        }

        $this->error('系統設定配置驗證失敗，請修復上述問題後重新執行。');
        return Command::FAILURE;
    }

    /**
     * 顯示配置摘要
     *
     * @return void
     */
    protected function displayConfigurationSummary(): void
    {
        $categories = config('system-settings.categories', []);
        $settings = config('system-settings.settings', []);
        $dependencies = config('system-settings.dependencies', []);
        $testableSettings = config('system-settings.testable_settings', []);

        $this->info('📊 配置摘要：');
        $this->table(
            ['項目', '數量'],
            [
                ['設定分類', count($categories)],
                ['設定項目', count($settings)],
                ['依賴關係', count($dependencies)],
                ['可測試群組', count($testableSettings)],
            ]
        );

        $this->newLine();
        $this->info('📋 分類詳情：');
        
        $categoryData = [];
        foreach ($categories as $key => $category) {
            $settingsCount = collect($settings)->where('category', $key)->count();
            $categoryData[] = [
                $category['name'],
                $key,
                $settingsCount,
                $category['order'] ?? 'N/A'
            ];
        }

        $this->table(
            ['分類名稱', '分類鍵值', '設定數量', '排序'],
            $categoryData
        );

        $this->newLine();
        $this->info('🔗 依賴關係：');
        
        if (empty($dependencies)) {
            $this->line('  無全域依賴關係');
        } else {
            foreach ($dependencies as $setting => $deps) {
                $this->line("  • {$setting} 依賴於：" . implode(', ', $deps));
            }
        }

        $this->newLine();
        $this->info('🧪 可測試設定：');
        
        if (empty($testableSettings)) {
            $this->line('  無可測試設定群組');
        } else {
            foreach ($testableSettings as $group => $config) {
                $settingsCount = count($config['settings'] ?? []);
                $this->line("  • {$group}: {$settingsCount} 個設定");
            }
        }
    }

    /**
     * 嘗試自動修復問題
     *
     * @param array $result
     * @return void
     */
    protected function attemptAutoFix(array $result): void
    {
        $fixed = 0;

        // 這裡可以實作一些自動修復邏輯
        // 例如：自動添加缺少的排序、修復格式問題等

        if ($fixed > 0) {
            $this->info("✅ 已自動修復 {$fixed} 個問題");
        } else {
            $this->warn('⚠️  沒有可以自動修復的問題');
        }
    }
}
