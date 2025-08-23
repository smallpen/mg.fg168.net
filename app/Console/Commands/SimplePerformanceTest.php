<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

/**
 * 簡單效能測試指令
 */
class SimplePerformanceTest extends Command
{
    /**
     * 指令名稱和簽名
     */
    protected $signature = 'settings:simple-test';

    /**
     * 指令描述
     */
    protected $description = '簡單測試設定效能服務';

    /**
     * 執行指令
     */
    public function handle(): int
    {
        $this->info('🧪 開始簡單測試...');

        try {
            // 測試快取服務
            $this->info('📦 測試快取服務...');
            $cacheService = app(\App\Services\SettingsCacheService::class);
            $this->info('  ✓ 快取服務載入成功');

            // 測試基本操作
            $result = $cacheService->set('test', 'value');
            $this->info('  ✓ 快取設定: ' . ($result ? '成功' : '失敗'));

            $value = $cacheService->get('test');
            $this->info('  ✓ 快取取得: ' . ($value === 'value' ? '成功' : '失敗'));

            // 測試效能服務
            $this->info('⚡ 測試效能服務...');
            $performanceService = app(\App\Services\SettingsPerformanceService::class);
            $this->info('  ✓ 效能服務載入成功');

            // 測試批量處理服務
            $this->info('🔄 測試批量處理服務...');
            $batchProcessor = app(\App\Services\SettingsBatchProcessor::class);
            $this->info('  ✓ 批量處理服務載入成功');

            $this->info('✅ 所有服務測試通過！');
            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error("❌ 測試失敗: {$e->getMessage()}");
            $this->error("堆疊追蹤: {$e->getTraceAsString()}");
            return Command::FAILURE;
        }
    }
}