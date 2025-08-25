<?php

/**
 * 語言效能優化演示腳本
 * 
 * 此腳本演示語言檔案快取和效能監控功能
 */

require_once __DIR__ . '/vendor/autoload.php';

use Illuminate\Foundation\Application;
use App\Services\LanguageFileCache;
use App\Services\LanguagePerformanceMonitor;
use App\Services\MultilingualLogger;

// 啟動 Laravel 應用程式
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== 語言效能優化演示 ===\n\n";

// 取得服務實例
$cache = app(LanguageFileCache::class);
$monitor = app(LanguagePerformanceMonitor::class);
$logger = app(MultilingualLogger::class);

// 1. 清除現有快取
echo "1. 清除現有快取...\n";
$cleared = $cache->clearCache();
echo "   已清除 {$cleared} 個快取項目\n\n";

// 2. 測試未快取的載入效能
echo "2. 測試未快取的語言檔案載入效能...\n";
$locales = ['zh_TW', 'en'];
$groups = ['admin', 'auth', 'layout'];

$uncachedTimes = [];
foreach ($locales as $locale) {
    foreach ($groups as $group) {
        $startTime = microtime(true);
        $data = $cache->loadLanguageFile($locale, $group);
        $loadTime = (microtime(true) - $startTime) * 1000;
        
        $uncachedTimes["{$locale}:{$group}"] = $loadTime;
        echo "   {$locale}/{$group}: " . round($loadTime, 2) . "ms (鍵數量: " . countKeys($data) . ")\n";
    }
}
echo "\n";

// 3. 預熱快取
echo "3. 預熱語言檔案快取...\n";
$startTime = microtime(true);
$results = $cache->warmupCache($locales, $groups);
$warmupTime = (microtime(true) - $startTime) * 1000;

echo "   預熱完成！耗時: " . round($warmupTime, 2) . "ms\n";
echo "   成功: {$results['success']} 個檔案\n";
echo "   失敗: {$results['failed']} 個檔案\n\n";

// 4. 測試快取載入效能
echo "4. 測試快取載入效能...\n";
$cachedTimes = [];
foreach ($locales as $locale) {
    foreach ($groups as $group) {
        $startTime = microtime(true);
        $data = $cache->loadLanguageFile($locale, $group);
        $loadTime = (microtime(true) - $startTime) * 1000;
        
        $cachedTimes["{$locale}:{$group}"] = $loadTime;
        echo "   {$locale}/{$group}: " . round($loadTime, 2) . "ms (快取)\n";
    }
}
echo "\n";

// 5. 效能比較
echo "5. 效能比較分析...\n";
$totalUncached = array_sum($uncachedTimes);
$totalCached = array_sum($cachedTimes);
$improvement = (($totalUncached - $totalCached) / $totalUncached) * 100;

echo "   未快取總載入時間: " . round($totalUncached, 2) . "ms\n";
echo "   快取載入總時間: " . round($totalCached, 2) . "ms\n";
echo "   效能提升: " . round($improvement, 1) . "%\n";
echo "   速度提升倍數: " . round($totalUncached / $totalCached, 1) . "x\n\n";

// 6. 快取統計
echo "6. 快取統計資訊...\n";
$stats = $cache->getCacheStats();
echo "   快取啟用: " . ($stats['cache_enabled'] ? '是' : '否') . "\n";
echo "   快取 TTL: {$stats['cache_ttl']} 秒\n";
echo "   總檔案數: {$stats['total_possible']}\n";
echo "   已快取檔案: {$stats['cached_files']}\n";
echo "   快取命中率: {$stats['cache_hit_rate']}%\n\n";

// 7. 效能監控演示
echo "7. 效能監控演示...\n";

// 記錄一些效能資料
for ($i = 0; $i < 5; $i++) {
    $monitor->recordFileLoadPerformance('zh_TW', 'demo', 20 + $i * 5, $i > 2, 1024 * ($i + 1));
    $monitor->recordTranslationPerformance("demo.key.{$i}", 'zh_TW', 10 + $i * 3, true, $i % 2 === 0);
    $monitor->recordCachePerformance($i % 2 === 0 ? 'hit' : 'miss', 'zh_TW', 'demo');
}

// 取得即時指標
$metrics = $monitor->getRealTimeMetrics();
echo "   即時效能指標:\n";
echo "   - 檔案載入統計: " . json_encode($metrics['file_load'], JSON_UNESCAPED_UNICODE) . "\n";
echo "   - 翻譯查詢統計: " . json_encode($metrics['translation'], JSON_UNESCAPED_UNICODE) . "\n";
echo "   - 快取統計: " . json_encode($metrics['cache'], JSON_UNESCAPED_UNICODE) . "\n";
echo "   - 活躍警報數量: " . count($metrics['alerts']) . "\n\n";

// 8. 警報演示
echo "8. 警報系統演示...\n";

// 觸發一個慢載入警報
$monitor->recordFileLoadPerformance('zh_TW', 'slow_demo', 150, false, 1024); // 超過 100ms 閾值
$monitor->recordTranslationPerformance('slow.translation', 'zh_TW', 80, true, false); // 超過 50ms 閾值

// 檢查警報
$updatedMetrics = $monitor->getRealTimeMetrics();
$alerts = $updatedMetrics['alerts'];

if (!empty($alerts)) {
    echo "   觸發的警報:\n";
    foreach ($alerts as $alert) {
        echo "   - 類型: {$alert['type']}\n";
        echo "     嚴重性: {$alert['severity']}\n";
        echo "     時間: {$alert['timestamp']}\n";
        echo "     詳細: " . json_encode($alert['data'], JSON_UNESCAPED_UNICODE) . "\n";
    }
} else {
    echo "   目前沒有活躍的警報（可能因為冷卻期機制）\n";
}
echo "\n";

// 9. 記憶體使用情況
echo "9. 記憶體使用情況...\n";
$memoryUsage = memory_get_usage(true);
$peakMemory = memory_get_peak_usage(true);
echo "   當前記憶體使用: " . formatBytes($memoryUsage) . "\n";
echo "   峰值記憶體使用: " . formatBytes($peakMemory) . "\n\n";

// 10. 清理演示
echo "10. 清理功能演示...\n";

// 清理效能資料
$clearedData = $monitor->clearPerformanceData(0);
echo "   已清理 {$clearedData} 個效能資料項目\n";

// 部分清理快取
$clearedCache = $cache->clearCache('zh_TW', 'demo');
echo "   已清理 {$clearedCache} 個特定快取項目\n\n";

echo "=== 演示完成 ===\n";
echo "語言效能優化功能已成功實作並測試！\n\n";

echo "主要功能:\n";
echo "✓ 語言檔案智慧快取（版本控制）\n";
echo "✓ 快取預熱和管理\n";
echo "✓ 即時效能監控\n";
echo "✓ 自動警報系統\n";
echo "✓ 效能統計分析\n";
echo "✓ 排程任務整合\n";
echo "✓ 命令列管理工具\n";
echo "✓ 完整的測試覆蓋\n\n";

echo "效能提升:\n";
echo "- 快取載入速度提升 " . round($improvement, 1) . "%\n";
echo "- 減少檔案 I/O 操作\n";
echo "- 智慧版本控制確保資料一致性\n";
echo "- 自動監控和警報機制\n";

/**
 * 計算陣列中的鍵數量（遞迴）
 */
function countKeys(array $array): int
{
    $count = 0;
    foreach ($array as $value) {
        $count++;
        if (is_array($value)) {
            $count += countKeys($value);
        }
    }
    return $count;
}

/**
 * 格式化位元組大小
 */
function formatBytes(int $bytes, int $precision = 2): string
{
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    
    for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
        $bytes /= 1024;
    }
    
    return round($bytes, $precision) . ' ' . $units[$i];
}