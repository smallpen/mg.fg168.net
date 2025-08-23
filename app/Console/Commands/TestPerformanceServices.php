<?php

namespace App\Console\Commands;

use App\Services\SettingsCacheService;
use App\Services\SettingsPerformanceService;
use App\Services\SettingsBatchProcessor;
use Illuminate\Console\Command;

/**
 * 測試效能服務指令
 */
class TestPerformanceServices extends Command
{
    /**
     * 指令名稱和簽名
     */
    protected $signature = 'settings:test-performance';

    /**
     * 指令描述
     */
    protected $description = '測試設定效能服務是否正常運作';

    /**
     * 執行指令
     */
    public function handle(): int
    {
        $this->info('🧪 開始測試設定效能服務...');
        $this->newLine();

        try {
            // 測試快取服務
            $this->testCacheService();
            
            // 測試效能服務
            $this->testPerformanceService();
            
            // 測試批量處理服務
            $this->testBatchProcessor();
            
            $this->newLine();
            $this->info('✅ 所有效能服務測試通過！');
            
            return Command::SUCCESS;
            
        } catch (\Exception $e) {
            $this->error("❌ 測試失敗: {$e->getMessage()}");
            return Command::FAILURE;
        }
    }

    /**
     * 測試快取服務
     */
    protected function testCacheService(): void
    {
        $this->info('📦 測試快取服務...');
        
        $cacheService = app(SettingsCacheService::class);
        
        // 測試基本快取操作
        $testKey = 'test_cache_key';
        $testValue = 'test_cache_value';
        
        // 測試設定快取
        $result = $cacheService->set($testKey, $testValue);
        if ($result) {
            $this->info('  ✓ 設定快取成功');
        } else {
            throw new \Exception('設定快取失敗');
        }
        
        // 測試取得快取
        $value = $cacheService->get($testKey);
        if ($value === $testValue) {
            $this->info('  ✓ 取得快取成功');
        } else {
            throw new \Exception('取得快取失敗');
        }
        
        // 測試刪除快取
        $result = $cacheService->forget($testKey);
        if ($result) {
            $this->info('  ✓ 刪除快取成功');
        } else {
            throw new \Exception('刪除快取失敗');
        }
        
        // 測試批量操作
        $batchItems = [
            'batch_key1' => 'batch_value1',
            'batch_key2' => 'batch_value2',
            'batch_key3' => 'batch_value3',
        ];
        
        // 測試批量設定快取
        $result = $cacheService->setMany($batchItems);
        if ($result) {
            $this->info('  ✓ 批量設定快取成功');
        } else {
            throw new \Exception('批量設定快取失敗');
        }
        
        // 測試批量取得快取
        $results = $cacheService->getMany(array_keys($batchItems));
        if (count($results) === count($batchItems)) {
            $this->info('  ✓ 批量取得快取成功');
        } else {
            throw new \Exception('批量取得快取失敗');
        }
        
        // 測試統計功能
        $stats = $cacheService->getStats();
        if (is_array($stats) && isset($stats['hits'], $stats['misses'])) {
            $this->info('  ✓ 取得快取統計成功');
        } else {
            throw new \Exception('取得快取統計失敗');
        }
        
        $this->info('  ✓ 快取服務測試完成');
    }

    /**
     * 測試效能服務
     */
    protected function testPerformanceService(): void
    {
        $this->info('⚡ 測試效能服務...');
        
        $performanceService = app(SettingsPerformanceService::class);
        
        // 測試批量大小設定
        $performanceService->setBatchSize(200);
        if ($performanceService->getDefaultBatchSize() === 200) {
            $this->info('  ✓ 設定批量大小成功');
        } else {
            throw new \Exception('設定批量大小失敗');
        }
        
        // 測試延遲載入閾值設定
        $performanceService->setLazyLoadThreshold(75);
        $this->info('  ✓ 設定延遲載入閾值成功');
        
        // 測試效能統計
        $stats = $performanceService->getPerformanceStats(1);
        if (is_array($stats)) {
            $this->info('  ✓ 取得效能統計成功');
        } else {
            throw new \Exception('取得效能統計失敗');
        }
        
        // 測試清理效能指標
        $cleanedCount = $performanceService->cleanupPerformanceMetrics(365);
        if (is_int($cleanedCount)) {
            $this->info('  ✓ 清理效能指標成功');
        } else {
            throw new \Exception('清理效能指標失敗');
        }
        
        $this->info('  ✓ 效能服務測試完成');
    }

    /**
     * 測試批量處理服務
     */
    protected function testBatchProcessor(): void
    {
        $this->info('🔄 測試批量處理服務...');
        
        $batchProcessor = app(SettingsBatchProcessor::class);
        
        // 測試批量大小設定
        $batchProcessor->setDefaultBatchSize(150);
        if ($batchProcessor->getDefaultBatchSize() === 150) {
            $this->info('  ✓ 設定預設批量大小成功');
        } else {
            throw new \Exception('設定預設批量大小失敗');
        }
        
        // 測試邊界值處理
        $batchProcessor->setDefaultBatchSize(0);
        $result1 = $batchProcessor->getDefaultBatchSize() === 1;
        
        $batchProcessor->setDefaultBatchSize(2000);
        $result2 = $batchProcessor->getDefaultBatchSize() === 1000;
        
        if ($result1 && $result2) {
            $this->info('  ✓ 批量大小邊界值處理正確');
        } else {
            throw new \Exception('批量大小邊界值處理失敗');
        }
        
        $this->info('  ✓ 批量處理服務測試完成');
    }
}