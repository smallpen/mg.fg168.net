<?php

namespace App\Livewire\Admin\Activities;

use Livewire\Component;
use App\Repositories\Contracts\ActivityRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

/**
 * 活動統計分析元件
 * 
 * 提供活動記錄的統計分析功能，包括趨勢圖表、分佈圖、使用者排行榜和安全事件統計
 */
class ActivityStats extends Component
{
    /**
     * 時間範圍選項
     */
    public string $timeRange = '7d';

    /**
     * 圖表類型
     */
    public string $chartType = 'timeline';

    /**
     * 選中的統計指標
     */
    public array $selectedMetrics = ['total', 'users', 'security'];

    /**
     * 是否顯示詳細統計
     */
    public bool $showDetailedStats = false;

    /**
     * 自動重新整理間隔（秒）
     */
    public int $refreshInterval = 30;

    /**
     * 是否啟用自動重新整理
     */
    public bool $autoRefresh = false;

    /**
     * 活動記錄資料存取層
     */
    protected ?ActivityRepositoryInterface $activityRepository = null;

    /**
     * 元件初始化
     */
    public function mount(): void
    {
        $this->authorize('activity_logs.view');
        $this->activityRepository = app(ActivityRepositoryInterface::class);
    }

    /**
     * 取得活動記錄資料存取層實例
     */
    protected function getActivityRepository(): ActivityRepositoryInterface
    {
        if ($this->activityRepository === null) {
            $this->activityRepository = app(ActivityRepositoryInterface::class);
        }
        
        return $this->activityRepository;
    }

    /**
     * 取得時間線資料
     */
    public function getTimelineDataProperty(): array
    {
        $cacheKey = "activity_stats_timeline_{$this->timeRange}";
        
        return Cache::remember($cacheKey, 300, function () {
            $stats = $this->getActivityRepository()->getActivityStats($this->timeRange);
            
            // 準備時間線圖表資料
            $timelineData = [];
            $dailyTrends = $stats['daily_trends'] ?? [];
            
            foreach ($dailyTrends as $date => $count) {
                $timelineData[] = [
                    'date' => $date,
                    'count' => $count,
                    'formatted_date' => Carbon::parse($date)->format('m/d'),
                ];
            }
            
            return $timelineData;
        });
    }

    /**
     * 取得分佈資料
     */
    public function getDistributionDataProperty(): array
    {
        $cacheKey = "activity_stats_distribution_{$this->timeRange}";
        
        return Cache::remember($cacheKey, 300, function () {
            $stats = $this->getActivityRepository()->getActivityStats($this->timeRange);
            
            // 活動類型分佈
            $typeDistribution = [];
            $activityByType = $stats['activity_by_type'] ?? [];
            $total = array_sum($activityByType);
            
            foreach ($activityByType as $type => $count) {
                $percentage = $total > 0 ? round(($count / $total) * 100, 1) : 0;
                $typeDistribution[] = [
                    'type' => $this->getTypeDisplayName($type),
                    'count' => $count,
                    'percentage' => $percentage,
                    'color' => $this->getTypeColor($type),
                ];
            }
            
            // 模組分佈
            $moduleDistribution = [];
            $activityByModule = $stats['activity_by_module'] ?? [];
            
            foreach ($activityByModule as $module => $count) {
                $percentage = $total > 0 ? round(($count / $total) * 100, 1) : 0;
                $moduleDistribution[] = [
                    'module' => $this->getModuleDisplayName($module),
                    'count' => $count,
                    'percentage' => $percentage,
                    'color' => $this->getModuleColor($module),
                ];
            }
            
            return [
                'type_distribution' => $typeDistribution,
                'module_distribution' => $moduleDistribution,
                'hourly_distribution' => $this->formatHourlyDistribution($stats['hourly_distribution'] ?? []),
            ];
        });
    }

    /**
     * 取得最活躍使用者
     */
    public function getTopUsersProperty(): Collection
    {
        $cacheKey = "activity_stats_top_users_{$this->timeRange}";
        
        return Cache::remember($cacheKey, 300, function () {
            return $this->getActivityRepository()->getTopUsers($this->timeRange, 10);
        });
    }

    /**
     * 取得安全事件統計
     */
    public function getSecurityEventsProperty(): Collection
    {
        $cacheKey = "activity_stats_security_events_{$this->timeRange}";
        
        return Cache::remember($cacheKey, 300, function () {
            return $this->getActivityRepository()->getSecurityEvents($this->timeRange);
        });
    }

    /**
     * 取得綜合統計資料
     */
    public function getOverallStatsProperty(): array
    {
        $cacheKey = "activity_stats_overall_{$this->timeRange}";
        
        return Cache::remember($cacheKey, 300, function () {
            $stats = $this->getActivityRepository()->getActivityStats($this->timeRange);
            
            return [
                'total_activities' => $stats['total_activities'] ?? 0,
                'unique_users' => $stats['unique_users'] ?? 0,
                'security_events' => $stats['security_events'] ?? 0,
                'high_risk_activities' => $stats['high_risk_activities'] ?? 0,
                'success_rate' => $stats['success_rate'] ?? 0,
                'average_daily' => $this->calculateAverageDailyActivities($stats),
                'peak_hour' => $this->findPeakHour($stats['hourly_distribution'] ?? []),
                'most_active_day' => $this->findMostActiveDay($stats['daily_trends'] ?? []),
            ];
        });
    }

    /**
     * 更新時間範圍
     */
    public function updateTimeRange(string $range): void
    {
        $this->timeRange = $range;
        $this->clearStatsCache();
        $this->dispatch('stats-updated');
    }

    /**
     * 更新圖表類型
     */
    public function updateChartType(string $type): void
    {
        $this->chartType = $type;
    }

    /**
     * 切換統計指標
     */
    public function toggleMetric(string $metric): void
    {
        if (in_array($metric, $this->selectedMetrics)) {
            $this->selectedMetrics = array_diff($this->selectedMetrics, [$metric]);
        } else {
            $this->selectedMetrics[] = $metric;
        }
    }

    /**
     * 匯出統計報告
     */
    public function exportStats(): void
    {
        $this->authorize('activity_logs.export');
        
        try {
            $stats = $this->overallStats;
            $timelineData = $this->timelineData;
            $distributionData = $this->distributionData;
            $topUsers = $this->topUsers;
            $securityEvents = $this->securityEvents;
            
            $exportData = [
                'export_info' => [
                    'generated_at' => now()->toDateTimeString(),
                    'time_range' => $this->timeRange,
                    'chart_type' => $this->chartType,
                    'selected_metrics' => $this->selectedMetrics,
                ],
                'overall_stats' => $stats,
                'timeline_data' => $timelineData,
                'distribution_data' => $distributionData,
                'top_users' => $topUsers->toArray(),
                'security_events' => $securityEvents->take(20)->toArray(),
            ];
            
            $filename = 'activity_stats_' . $this->timeRange . '_' . now()->format('Y-m-d_H-i-s') . '.json';
            $path = storage_path('app/exports/' . $filename);
            
            if (!is_dir(dirname($path))) {
                mkdir(dirname($path), 0755, true);
            }
            
            file_put_contents($path, json_encode($exportData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            
            $this->dispatch('download-file', [
                'url' => route('admin.activities.download-export', ['filename' => $filename]),
                'filename' => $filename
            ]);
            
            session()->flash('success', '統計報告已匯出完成');
            
        } catch (\Exception $e) {
            session()->flash('error', '匯出統計報告時發生錯誤：' . $e->getMessage());
        }
    }

    /**
     * 重新整理統計資料
     */
    public function refreshStats(): void
    {
        $this->clearStatsCache();
        $this->dispatch('stats-refreshed');
        session()->flash('success', '統計資料已重新整理');
    }

    /**
     * 切換自動重新整理
     */
    public function toggleAutoRefresh(): void
    {
        $this->autoRefresh = !$this->autoRefresh;
        
        if ($this->autoRefresh) {
            $this->dispatch('start-auto-refresh', ['interval' => $this->refreshInterval]);
            session()->flash('success', '已啟用自動重新整理');
        } else {
            $this->dispatch('stop-auto-refresh');
            session()->flash('success', '已停用自動重新整理');
        }
    }

    /**
     * 切換詳細統計顯示
     */
    public function toggleDetailedStats(): void
    {
        $this->showDetailedStats = !$this->showDetailedStats;
    }

    /**
     * 匯出特定圖表資料
     */
    public function exportChartData(string $chartType): void
    {
        $this->authorize('activity_logs.export');
        
        try {
            $data = match ($chartType) {
                'timeline' => $this->timelineData,
                'distribution' => $this->distributionData,
                'top_users' => $this->topUsers->toArray(),
                'security_events' => $this->securityEvents->toArray(),
                default => [],
            };
            
            $filename = "chart_data_{$chartType}_{$this->timeRange}_" . now()->format('Y-m-d_H-i-s') . '.json';
            $path = storage_path('app/exports/' . $filename);
            
            if (!is_dir(dirname($path))) {
                mkdir(dirname($path), 0755, true);
            }
            
            file_put_contents($path, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            
            $this->dispatch('download-file', [
                'url' => route('admin.activities.download-export', ['filename' => $filename]),
                'filename' => $filename
            ]);
            
            session()->flash('success', '圖表資料已匯出完成');
            
        } catch (\Exception $e) {
            session()->flash('error', '匯出圖表資料時發生錯誤：' . $e->getMessage());
        }
    }

    /**
     * 設定自訂時間範圍
     */
    public function setCustomTimeRange(string $startDate, string $endDate): void
    {
        $start = Carbon::parse($startDate);
        $end = Carbon::parse($endDate);
        $days = $start->diffInDays($end) + 1;
        
        $this->timeRange = $days . 'd';
        $this->clearStatsCache();
        $this->dispatch('stats-updated');
        
        session()->flash('success', "已設定自訂時間範圍：{$startDate} 至 {$endDate}");
    }

    /**
     * 比較不同時間範圍的資料
     */
    public function compareTimeRanges(array $ranges): array
    {
        $comparison = [];
        
        foreach ($ranges as $range) {
            $stats = $this->getActivityRepository()->getActivityStats($range);
            $comparison[$range] = [
                'total_activities' => $stats['total_activities'] ?? 0,
                'unique_users' => $stats['unique_users'] ?? 0,
                'security_events' => $stats['security_events'] ?? 0,
                'success_rate' => $stats['success_rate'] ?? 0,
            ];
        }
        
        return $comparison;
    }

    /**
     * 取得趨勢分析
     */
    public function getTrendAnalysisProperty(): array
    {
        $cacheKey = "activity_stats_trend_analysis_{$this->timeRange}";
        
        return Cache::remember($cacheKey, 300, function () {
            $timelineData = $this->timelineData;
            
            if (count($timelineData) < 2) {
                return [
                    'trend' => 'insufficient_data',
                    'change_percentage' => 0,
                    'prediction' => null,
                ];
            }
            
            $values = array_column($timelineData, 'count');
            $firstHalf = array_slice($values, 0, ceil(count($values) / 2));
            $secondHalf = array_slice($values, floor(count($values) / 2));
            
            $firstAvg = array_sum($firstHalf) / count($firstHalf);
            $secondAvg = array_sum($secondHalf) / count($secondHalf);
            
            $changePercentage = $firstAvg > 0 ? (($secondAvg - $firstAvg) / $firstAvg) * 100 : 0;
            
            $trend = 'stable';
            if ($changePercentage > 10) {
                $trend = 'increasing';
            } elseif ($changePercentage < -10) {
                $trend = 'decreasing';
            }
            
            return [
                'trend' => $trend,
                'change_percentage' => round($changePercentage, 1),
                'first_period_avg' => round($firstAvg, 1),
                'second_period_avg' => round($secondAvg, 1),
                'prediction' => $this->predictNextPeriod($values),
            ];
        });
    }

    /**
     * 預測下一期間的活動數量
     */
    protected function predictNextPeriod(array $values): ?float
    {
        if (count($values) < 3) {
            return null;
        }
        
        // 簡單的線性回歸預測
        $n = count($values);
        $sumX = array_sum(range(1, $n));
        $sumY = array_sum($values);
        $sumXY = 0;
        $sumX2 = 0;
        
        for ($i = 0; $i < $n; $i++) {
            $x = $i + 1;
            $y = $values[$i];
            $sumXY += $x * $y;
            $sumX2 += $x * $x;
        }
        
        $slope = ($n * $sumXY - $sumX * $sumY) / ($n * $sumX2 - $sumX * $sumX);
        $intercept = ($sumY - $slope * $sumX) / $n;
        
        return round($slope * ($n + 1) + $intercept, 1);
    }

    /**
     * 取得時間範圍選項
     */
    public function getTimeRangeOptionsProperty(): array
    {
        return [
            '1d' => '今天',
            '7d' => '最近 7 天',
            '30d' => '最近 30 天',
            '90d' => '最近 90 天',
        ];
    }

    /**
     * 取得圖表類型選項
     */
    public function getChartTypeOptionsProperty(): array
    {
        return [
            'timeline' => '時間線圖',
            'distribution' => '分佈圖',
            'heatmap' => '熱力圖',
            'comparison' => '對比圖',
        ];
    }

    /**
     * 取得統計指標選項
     */
    public function getMetricOptionsProperty(): array
    {
        return [
            'total' => '總活動數',
            'users' => '活躍使用者',
            'security' => '安全事件',
            'success_rate' => '成功率',
            'risk_level' => '風險等級',
        ];
    }

    /**
     * 清除統計快取
     */
    protected function clearStatsCache(): void
    {
        $cacheKeys = [
            "activity_stats_timeline_{$this->timeRange}",
            "activity_stats_distribution_{$this->timeRange}",
            "activity_stats_top_users_{$this->timeRange}",
            "activity_stats_security_events_{$this->timeRange}",
            "activity_stats_overall_{$this->timeRange}",
        ];
        
        foreach ($cacheKeys as $key) {
            Cache::forget($key);
        }
    }

    /**
     * 計算平均每日活動數
     */
    protected function calculateAverageDailyActivities(array $stats): float
    {
        $dailyTrends = $stats['daily_trends'] ?? [];
        $days = count($dailyTrends);
        
        if ($days === 0) {
            return 0;
        }
        
        $total = array_sum($dailyTrends);
        return round($total / $days, 1);
    }

    /**
     * 找出活動高峰時段
     */
    protected function findPeakHour(array $hourlyDistribution): int
    {
        if (empty($hourlyDistribution)) {
            return 0;
        }
        
        return array_keys($hourlyDistribution, max($hourlyDistribution))[0];
    }

    /**
     * 找出最活躍的日期
     */
    protected function findMostActiveDay(array $dailyTrends): ?string
    {
        if (empty($dailyTrends)) {
            return null;
        }
        
        $maxDate = array_keys($dailyTrends, max($dailyTrends))[0];
        return Carbon::parse($maxDate)->format('Y-m-d');
    }

    /**
     * 格式化每小時分佈資料
     */
    protected function formatHourlyDistribution(array $hourlyData): array
    {
        $formatted = [];
        
        for ($hour = 0; $hour < 24; $hour++) {
            $count = $hourlyData[$hour] ?? 0;
            $formatted[] = [
                'hour' => $hour,
                'count' => $count,
                'formatted_hour' => sprintf('%02d:00', $hour),
                'intensity' => $this->calculateIntensity($count, $hourlyData),
            ];
        }
        
        return $formatted;
    }

    /**
     * 計算活動強度（用於熱力圖）
     */
    protected function calculateIntensity(int $count, array $allData): float
    {
        if (empty($allData)) {
            return 0;
        }
        
        $max = max($allData);
        return $max > 0 ? ($count / $max) : 0;
    }

    /**
     * 取得活動類型顯示名稱
     */
    protected function getTypeDisplayName(string $type): string
    {
        return match ($type) {
            'login' => '登入',
            'logout' => '登出',
            'create_user' => '建立使用者',
            'update_user' => '更新使用者',
            'delete_user' => '刪除使用者',
            'create_role' => '建立角色',
            'update_role' => '更新角色',
            'delete_role' => '刪除角色',
            'assign_role' => '指派角色',
            'remove_role' => '移除角色',
            'update_permissions' => '更新權限',
            'view_dashboard' => '檢視儀表板',
            'export_data' => '匯出資料',
            'quick_action' => '快速操作',
            default => $type,
        };
    }

    /**
     * 取得活動類型顏色
     */
    protected function getTypeColor(string $type): string
    {
        return match ($type) {
            'login', 'create_user', 'create_role' => '#10b981', // green
            'logout' => '#3b82f6', // blue
            'update_user', 'update_role', 'update_permissions' => '#f59e0b', // amber
            'delete_user', 'delete_role' => '#ef4444', // red
            'assign_role', 'remove_role' => '#8b5cf6', // violet
            'view_dashboard', 'export_data' => '#06b6d4', // cyan
            'quick_action' => '#ec4899', // pink
            default => '#6b7280', // gray
        };
    }

    /**
     * 取得模組顯示名稱
     */
    protected function getModuleDisplayName(string $module): string
    {
        return match ($module) {
            'auth' => '認證',
            'users' => '使用者管理',
            'roles' => '角色管理',
            'permissions' => '權限管理',
            'dashboard' => '儀表板',
            'system' => '系統管理',
            default => $module,
        };
    }

    /**
     * 取得模組顏色
     */
    protected function getModuleColor(string $module): string
    {
        return match ($module) {
            'auth' => '#10b981', // green
            'users' => '#3b82f6', // blue
            'roles' => '#8b5cf6', // violet
            'permissions' => '#f59e0b', // amber
            'dashboard' => '#06b6d4', // cyan
            'system' => '#ef4444', // red
            default => '#6b7280', // gray
        };
    }

    /**
     * 渲染元件
     */
    public function render()
    {
        return view('livewire.admin.activities.activity-stats');
    }
}