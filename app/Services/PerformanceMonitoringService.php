<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;

/**
 * 效能監控服務
 * 
 * 負責收集、分析和報告系統效能指標
 */
class PerformanceMonitoringService
{
    /**
     * 效能指標快取鍵前綴
     */
    protected string $cachePrefix = 'performance_metrics';

    /**
     * 效能閾值配置
     */
    protected array $thresholds = [
        'lcp' => 2500, // Largest Contentful Paint (ms)
        'fid' => 100,  // First Input Delay (ms)
        'cls' => 0.1,  // Cumulative Layout Shift
        'ttfb' => 600, // Time to First Byte (ms)
        'fcp' => 1800, // First Contentful Paint (ms)
    ];

    /**
     * 記錄效能指標
     */
    public function recordMetric(string $metric, float $value, array $context = []): void
    {
        $data = [
            'metric' => $metric,
            'value' => $value,
            'timestamp' => now()->timestamp,
            'context' => $context,
            'user_id' => auth()->id(),
            'session_id' => session()->getId(),
            'url' => request()->url(),
            'user_agent' => request()->userAgent(),
        ];

        // 儲存到快取中進行批次處理
        $cacheKey = "{$this->cachePrefix}:batch:" . date('Y-m-d-H');
        $metrics = Cache::get($cacheKey, []);
        $metrics[] = $data;
        
        Cache::put($cacheKey, $metrics, 3600); // 1小時

        // 檢查是否超過閾值
        $this->checkThreshold($metric, $value, $context);
    }

    /**
     * 取得效能統計
     */
    public function getPerformanceStats(string $period = '24h'): array
    {
        $cacheKey = "{$this->cachePrefix}:stats:{$period}";
        
        return Cache::remember($cacheKey, 300, function () use ($period) {
            $metrics = $this->getMetricsForPeriod($period);
            
            return [
                'total_measurements' => count($metrics),
                'average_lcp' => $this->calculateAverage($metrics, 'lcp'),
                'average_fid' => $this->calculateAverage($metrics, 'fid'),
                'average_cls' => $this->calculateAverage($metrics, 'cls'),
                'average_ttfb' => $this->calculateAverage($metrics, 'ttfb'),
                'average_fcp' => $this->calculateAverage($metrics, 'fcp'),
                'performance_score' => $this->calculatePerformanceScore($metrics),
                'threshold_violations' => $this->getThresholdViolations($metrics),
                'top_slow_pages' => $this->getTopSlowPages($metrics),
                'browser_breakdown' => $this->getBrowserBreakdown($metrics),
                'device_breakdown' => $this->getDeviceBreakdown($metrics),
            ];
        });
    }

    /**
     * 取得即時效能指標
     */
    public function getRealTimeMetrics(): array
    {
        $cacheKey = "{$this->cachePrefix}:realtime";
        
        return Cache::get($cacheKey, [
            'active_users' => 0,
            'current_load' => 0,
            'cache_hit_rate' => 0,
            'average_response_time' => 0,
            'error_rate' => 0,
        ]);
    }

    /**
     * 更新即時指標
     */
    public function updateRealTimeMetrics(array $metrics): void
    {
        $cacheKey = "{$this->cachePrefix}:realtime";
        $current = Cache::get($cacheKey, []);
        
        $updated = array_merge($current, $metrics, [
            'last_updated' => now()->timestamp
        ]);
        
        Cache::put($cacheKey, $updated, 60); // 1分鐘
    }

    /**
     * 取得效能建議
     */
    public function getPerformanceRecommendations(): array
    {
        $stats = $this->getPerformanceStats();
        $recommendations = [];

        // LCP 建議
        if ($stats['average_lcp'] > $this->thresholds['lcp']) {
            $recommendations[] = [
                'type' => 'lcp',
                'severity' => 'high',
                'title' => '最大內容繪製時間過長',
                'description' => 'LCP 超過建議值，建議優化圖片載入和關鍵資源',
                'actions' => [
                    '啟用圖片延遲載入',
                    '優化關鍵 CSS',
                    '使用 CDN 加速資源載入',
                    '壓縮圖片檔案'
                ]
            ];
        }

        // FID 建議
        if ($stats['average_fid'] > $this->thresholds['fid']) {
            $recommendations[] = [
                'type' => 'fid',
                'severity' => 'medium',
                'title' => '首次輸入延遲過長',
                'description' => 'FID 超過建議值，建議優化 JavaScript 執行',
                'actions' => [
                    '分割 JavaScript 程式碼',
                    '延遲載入非關鍵腳本',
                    '使用 Web Workers 處理重計算',
                    '優化事件處理器'
                ]
            ];
        }

        // CLS 建議
        if ($stats['average_cls'] > $this->thresholds['cls']) {
            $recommendations[] = [
                'type' => 'cls',
                'severity' => 'medium',
                'title' => '累積版面位移過大',
                'description' => 'CLS 超過建議值，建議穩定版面配置',
                'actions' => [
                    '為圖片設定明確尺寸',
                    '預留廣告空間',
                    '避免在現有內容上方插入內容',
                    '使用骨架屏佔位'
                ]
            ];
        }

        return $recommendations;
    }

    /**
     * 產生效能報告
     */
    public function generatePerformanceReport(string $period = '7d'): array
    {
        $stats = $this->getPerformanceStats($period);
        $recommendations = $this->getPerformanceRecommendations();
        
        return [
            'period' => $period,
            'generated_at' => now()->toISOString(),
            'summary' => [
                'performance_score' => $stats['performance_score'],
                'total_measurements' => $stats['total_measurements'],
                'threshold_violations' => count($stats['threshold_violations']),
                'recommendations_count' => count($recommendations),
            ],
            'core_web_vitals' => [
                'lcp' => [
                    'value' => $stats['average_lcp'],
                    'threshold' => $this->thresholds['lcp'],
                    'status' => $stats['average_lcp'] <= $this->thresholds['lcp'] ? 'good' : 'needs_improvement'
                ],
                'fid' => [
                    'value' => $stats['average_fid'],
                    'threshold' => $this->thresholds['fid'],
                    'status' => $stats['average_fid'] <= $this->thresholds['fid'] ? 'good' : 'needs_improvement'
                ],
                'cls' => [
                    'value' => $stats['average_cls'],
                    'threshold' => $this->thresholds['cls'],
                    'status' => $stats['average_cls'] <= $this->thresholds['cls'] ? 'good' : 'needs_improvement'
                ],
            ],
            'detailed_stats' => $stats,
            'recommendations' => $recommendations,
            'trends' => $this->getPerformanceTrends($period),
        ];
    }

    /**
     * 清除效能資料
     */
    public function clearPerformanceData(string $period = null): void
    {
        if ($period) {
            $cacheKey = "{$this->cachePrefix}:stats:{$period}";
            Cache::forget($cacheKey);
        } else {
            // 清除所有效能相關快取
            $keys = Cache::getRedis()->keys("{$this->cachePrefix}:*");
            foreach ($keys as $key) {
                Cache::forget(str_replace(config('cache.prefix') . ':', '', $key));
            }
        }
    }

    /**
     * 檢查閾值
     */
    protected function checkThreshold(string $metric, float $value, array $context): void
    {
        if (!isset($this->thresholds[$metric])) {
            return;
        }

        $threshold = $this->thresholds[$metric];
        
        if ($value > $threshold) {
            Log::warning("效能指標 {$metric} 超過閾值", [
                'metric' => $metric,
                'value' => $value,
                'threshold' => $threshold,
                'context' => $context,
                'url' => request()->url(),
                'user_id' => auth()->id(),
            ]);

            // 記錄閾值違規
            $this->recordThresholdViolation($metric, $value, $threshold, $context);
        }
    }

    /**
     * 記錄閾值違規
     */
    protected function recordThresholdViolation(string $metric, float $value, float $threshold, array $context): void
    {
        $cacheKey = "{$this->cachePrefix}:violations:" . date('Y-m-d');
        $violations = Cache::get($cacheKey, []);
        
        $violations[] = [
            'metric' => $metric,
            'value' => $value,
            'threshold' => $threshold,
            'context' => $context,
            'timestamp' => now()->timestamp,
            'url' => request()->url(),
            'user_id' => auth()->id(),
        ];
        
        Cache::put($cacheKey, $violations, 86400); // 24小時
    }

    /**
     * 取得指定期間的指標資料
     */
    protected function getMetricsForPeriod(string $period): array
    {
        // 這裡應該從實際的資料儲存中取得資料
        // 目前使用快取作為示例
        $hours = $this->periodToHours($period);
        $metrics = [];
        
        for ($i = 0; $i < $hours; $i++) {
            $cacheKey = "{$this->cachePrefix}:batch:" . date('Y-m-d-H', strtotime("-{$i} hours"));
            $hourlyMetrics = Cache::get($cacheKey, []);
            $metrics = array_merge($metrics, $hourlyMetrics);
        }
        
        return $metrics;
    }

    /**
     * 計算平均值
     */
    protected function calculateAverage(array $metrics, string $metricType): float
    {
        $values = collect($metrics)
            ->where('metric', $metricType)
            ->pluck('value')
            ->filter();
            
        return $values->isEmpty() ? 0 : $values->average();
    }

    /**
     * 計算效能分數
     */
    protected function calculatePerformanceScore(array $metrics): int
    {
        $lcp = $this->calculateAverage($metrics, 'lcp');
        $fid = $this->calculateAverage($metrics, 'fid');
        $cls = $this->calculateAverage($metrics, 'cls');
        
        $lcpScore = $lcp <= 2500 ? 100 : max(0, 100 - (($lcp - 2500) / 25));
        $fidScore = $fid <= 100 ? 100 : max(0, 100 - (($fid - 100) / 3));
        $clsScore = $cls <= 0.1 ? 100 : max(0, 100 - (($cls - 0.1) * 1000));
        
        return (int) round(($lcpScore + $fidScore + $clsScore) / 3);
    }

    /**
     * 取得閾值違規
     */
    protected function getThresholdViolations(array $metrics): array
    {
        return collect($metrics)
            ->filter(function ($metric) {
                $threshold = $this->thresholds[$metric['metric']] ?? null;
                return $threshold && $metric['value'] > $threshold;
            })
            ->values()
            ->toArray();
    }

    /**
     * 取得最慢的頁面
     */
    protected function getTopSlowPages(array $metrics): array
    {
        return collect($metrics)
            ->groupBy('url')
            ->map(function ($pageMetrics, $url) {
                return [
                    'url' => $url,
                    'average_lcp' => collect($pageMetrics)->where('metric', 'lcp')->avg('value'),
                    'measurements' => count($pageMetrics),
                ];
            })
            ->sortByDesc('average_lcp')
            ->take(10)
            ->values()
            ->toArray();
    }

    /**
     * 取得瀏覽器分佈
     */
    protected function getBrowserBreakdown(array $metrics): array
    {
        return collect($metrics)
            ->groupBy(function ($metric) {
                return $this->extractBrowser($metric['user_agent'] ?? '');
            })
            ->map(function ($browserMetrics, $browser) {
                return [
                    'browser' => $browser,
                    'count' => count($browserMetrics),
                    'average_lcp' => collect($browserMetrics)->where('metric', 'lcp')->avg('value'),
                ];
            })
            ->values()
            ->toArray();
    }

    /**
     * 取得裝置分佈
     */
    protected function getDeviceBreakdown(array $metrics): array
    {
        return collect($metrics)
            ->groupBy(function ($metric) {
                return $this->extractDevice($metric['user_agent'] ?? '');
            })
            ->map(function ($deviceMetrics, $device) {
                return [
                    'device' => $device,
                    'count' => count($deviceMetrics),
                    'average_lcp' => collect($deviceMetrics)->where('metric', 'lcp')->avg('value'),
                ];
            })
            ->values()
            ->toArray();
    }

    /**
     * 取得效能趨勢
     */
    protected function getPerformanceTrends(string $period): array
    {
        // 簡化的趨勢計算
        return [
            'lcp_trend' => 'improving', // improving, declining, stable
            'fid_trend' => 'stable',
            'cls_trend' => 'improving',
            'overall_trend' => 'improving',
        ];
    }

    /**
     * 期間轉換為小時數
     */
    protected function periodToHours(string $period): int
    {
        return match ($period) {
            '1h' => 1,
            '6h' => 6,
            '24h' => 24,
            '7d' => 168,
            '30d' => 720,
            default => 24,
        };
    }

    /**
     * 從 User Agent 提取瀏覽器資訊
     */
    protected function extractBrowser(string $userAgent): string
    {
        if (str_contains($userAgent, 'Chrome')) return 'Chrome';
        if (str_contains($userAgent, 'Firefox')) return 'Firefox';
        if (str_contains($userAgent, 'Safari')) return 'Safari';
        if (str_contains($userAgent, 'Edge')) return 'Edge';
        return 'Other';
    }

    /**
     * 從 User Agent 提取裝置資訊
     */
    protected function extractDevice(string $userAgent): string
    {
        if (str_contains($userAgent, 'Mobile')) return 'Mobile';
        if (str_contains($userAgent, 'Tablet')) return 'Tablet';
        return 'Desktop';
    }
}