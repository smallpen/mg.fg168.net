<?php

namespace App\Services;

use App\Models\Agent;
use App\Models\Player;
use App\Models\PointTransaction;
use App\Notifications\ChannelIntegrityAlert;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\DB;

class ChannelMonitoringService
{
    private ChannelIntegrityService $integrityService;
    private array $thresholds;
    private array $alertChannels;

    public function __construct(ChannelIntegrityService $integrityService)
    {
        $this->integrityService = $integrityService;
        $this->thresholds = config('channel.monitoring.thresholds', [
            'points_discrepancy' => 100.0,
            'negative_points_count' => 1,
            'orphaned_records_count' => 5,
            'failed_transactions_rate' => 0.05,
            'response_time_ms' => 1000
        ]);
        $this->alertChannels = config('channel.monitoring.alert_channels', ['log', 'database']);
    }

    /**
     * 執行系統監控檢查
     */
    public function performMonitoringCheck(): array
    {
        $results = [
            'timestamp' => now()->toISOString(),
            'status' => 'healthy',
            'checks' => [],
            'alerts' => [],
            'metrics' => []
        ];

        try {
            // 執行各項監控檢查
            $results['checks']['points_consistency'] = $this->checkPointsConsistency();
            $results['checks']['negative_balances'] = $this->checkNegativeBalances();
            $results['checks']['orphaned_records'] = $this->checkOrphanedRecords();
            $results['checks']['transaction_integrity'] = $this->checkTransactionIntegrity();
            $results['checks']['system_performance'] = $this->checkSystemPerformance();
            $results['checks']['data_growth'] = $this->checkDataGrowth();

            // 收集指標
            $results['metrics'] = $this->collectMetrics();

            // 評估整體狀態
            $results['status'] = $this->evaluateOverallStatus($results['checks']);

            // 生成警報
            $results['alerts'] = $this->generateAlerts($results['checks']);

            // 發送警報通知
            if (!empty($results['alerts'])) {
                $this->sendAlerts($results['alerts']);
            }

            // 更新監控快取
            $this->updateMonitoringCache($results);

        } catch (\Exception $e) {
            $results['status'] = 'error';
            $results['error'] = $e->getMessage();
            Log::error('Monitoring check failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }

        return $results;
    }

    /**
     * 檢查點數一致性
     */
    private function checkPointsConsistency(): array
    {
        $audit = $this->integrityService->auditPointsConsistency();
        
        return [
            'status' => $audit['total_discrepancy'] <= $this->thresholds['points_discrepancy'] ? 'healthy' : 'warning',
            'agents_checked' => $audit['total_agents_checked'],
            'agents_with_issues' => $audit['agents_with_issues'],
            'total_discrepancy' => $audit['total_discrepancy'],
            'threshold' => $this->thresholds['points_discrepancy']
        ];
    }

    /**
     * 檢查負餘額
     */
    private function checkNegativeBalances(): array
    {
        $negativeAgents = Agent::where('remaining_points', '<', 0)->count();
        $negativePlayers = Player::where('points', '<', 0)->count();
        $totalNegative = $negativeAgents + $negativePlayers;

        return [
            'status' => $totalNegative <= $this->thresholds['negative_points_count'] ? 'healthy' : 'critical',
            'negative_agents' => $negativeAgents,
            'negative_players' => $negativePlayers,
            'total_negative' => $totalNegative,
            'threshold' => $this->thresholds['negative_points_count']
        ];
    }

    /**
     * 檢查孤立記錄
     */
    private function checkOrphanedRecords(): array
    {
        $orphanedPlayers = Player::whereDoesntHave('agent')->count();
        $orphanedTransactions = PointTransaction::where(function ($query) {
            $query->whereNotNull('agent_id')->whereDoesntHave('agent');
        })->orWhere(function ($query) {
            $query->whereNotNull('player_id')->whereDoesntHave('player');
        })->count();
        
        $totalOrphaned = $orphanedPlayers + $orphanedTransactions;

        return [
            'status' => $totalOrphaned <= $this->thresholds['orphaned_records_count'] ? 'healthy' : 'warning',
            'orphaned_players' => $orphanedPlayers,
            'orphaned_transactions' => $orphanedTransactions,
            'total_orphaned' => $totalOrphaned,
            'threshold' => $this->thresholds['orphaned_records_count']
        ];
    }

    /**
     * 檢查交易完整性
     */
    private function checkTransactionIntegrity(): array
    {
        // 檢查最近24小時的交易
        $recentTransactions = PointTransaction::where('created_at', '>=', now()->subDay())->count();
        
        // 檢查交易餘額錯誤
        $transactionErrors = 0;
        $sampleTransactions = PointTransaction::with(['agent', 'player'])
            ->where('created_at', '>=', now()->subHour())
            ->limit(100)
            ->get();

        foreach ($sampleTransactions as $transaction) {
            if ($transaction->agent) {
                // 簡化的餘額檢查
                $expectedBalance = $transaction->balance_before + $transaction->amount;
                if (abs($transaction->balance_after - $expectedBalance) > 0.01) {
                    $transactionErrors++;
                }
            }
        }

        $errorRate = $sampleTransactions->count() > 0 ? $transactionErrors / $sampleTransactions->count() : 0;

        return [
            'status' => $errorRate <= $this->thresholds['failed_transactions_rate'] ? 'healthy' : 'warning',
            'recent_transactions' => $recentTransactions,
            'sample_checked' => $sampleTransactions->count(),
            'errors_found' => $transactionErrors,
            'error_rate' => round($errorRate, 4),
            'threshold' => $this->thresholds['failed_transactions_rate']
        ];
    }

    /**
     * 檢查系統效能
     */
    private function checkSystemPerformance(): array
    {
        $start = microtime(true);
        
        // 執行一些代表性查詢
        Agent::count();
        Player::with('agent')->limit(10)->get();
        PointTransaction::where('created_at', '>=', now()->subDay())->count();
        
        $responseTime = (microtime(true) - $start) * 1000;

        return [
            'status' => $responseTime <= $this->thresholds['response_time_ms'] ? 'healthy' : 'warning',
            'response_time_ms' => round($responseTime, 2),
            'memory_usage_mb' => round(memory_get_usage(true) / 1024 / 1024, 2),
            'peak_memory_mb' => round(memory_get_peak_usage(true) / 1024 / 1024, 2),
            'threshold_ms' => $this->thresholds['response_time_ms']
        ];
    }

    /**
     * 檢查資料增長
     */
    private function checkDataGrowth(): array
    {
        $currentCounts = [
            'agents' => Agent::count(),
            'players' => Player::count(),
            'transactions' => PointTransaction::count()
        ];

        $previousCounts = Cache::get('channel.monitoring.previous_counts', $currentCounts);
        
        $growth = [
            'agents' => $currentCounts['agents'] - $previousCounts['agents'],
            'players' => $currentCounts['players'] - $previousCounts['players'],
            'transactions' => $currentCounts['transactions'] - $previousCounts['transactions']
        ];

        // 更新快取
        Cache::put('channel.monitoring.previous_counts', $currentCounts, now()->addDay());

        return [
            'status' => 'healthy',
            'current_counts' => $currentCounts,
            'growth_24h' => $growth,
            'growth_rate' => [
                'agents' => $previousCounts['agents'] > 0 ? round(($growth['agents'] / $previousCounts['agents']) * 100, 2) : 0,
                'players' => $previousCounts['players'] > 0 ? round(($growth['players'] / $previousCounts['players']) * 100, 2) : 0,
                'transactions' => $previousCounts['transactions'] > 0 ? round(($growth['transactions'] / $previousCounts['transactions']) * 100, 2) : 0
            ]
        ];
    }

    /**
     * 收集系統指標
     */
    private function collectMetrics(): array
    {
        return [
            'total_points_in_system' => Agent::sum('total_points'),
            'total_allocated_points' => Agent::sum('allocated_points'),
            'total_remaining_points' => Agent::sum('remaining_points'),
            'total_player_points' => Player::sum('points'),
            'active_agents_count' => Agent::where('is_active', true)->count(),
            'active_players_count' => Player::where('is_active', true)->count(),
            'transactions_today' => PointTransaction::whereDate('created_at', today())->count(),
            'transactions_this_hour' => PointTransaction::where('created_at', '>=', now()->subHour())->count(),
            'average_agent_points' => Agent::avg('total_points'),
            'average_player_points' => Player::avg('points'),
            'max_agent_level' => Agent::max('level'),
            'unique_prefixes' => Agent::where('level', 1)->distinct('prefix')->count('prefix')
        ];
    }

    /**
     * 評估整體狀態
     */
    private function evaluateOverallStatus(array $checks): string
    {
        $statuses = collect($checks)->pluck('status');
        
        if ($statuses->contains('critical')) {
            return 'critical';
        }
        
        if ($statuses->contains('warning')) {
            return 'warning';
        }
        
        if ($statuses->contains('error')) {
            return 'error';
        }
        
        return 'healthy';
    }

    /**
     * 生成警報
     */
    private function generateAlerts(array $checks): array
    {
        $alerts = [];

        foreach ($checks as $checkName => $checkResult) {
            if (in_array($checkResult['status'], ['warning', 'critical', 'error'])) {
                $alerts[] = [
                    'type' => $checkName,
                    'severity' => $checkResult['status'],
                    'message' => $this->generateAlertMessage($checkName, $checkResult),
                    'timestamp' => now()->toISOString(),
                    'data' => $checkResult
                ];
            }
        }

        return $alerts;
    }

    /**
     * 生成警報訊息
     */
    private function generateAlertMessage(string $checkName, array $checkResult): string
    {
        return match($checkName) {
            'points_consistency' => "點數一致性檢查發現 {$checkResult['agents_with_issues']} 個代理存在問題，總差異: {$checkResult['total_discrepancy']}",
            'negative_balances' => "發現 {$checkResult['total_negative']} 個負餘額記錄（代理: {$checkResult['negative_agents']}, 玩家: {$checkResult['negative_players']}）",
            'orphaned_records' => "發現 {$checkResult['total_orphaned']} 個孤立記錄（玩家: {$checkResult['orphaned_players']}, 交易: {$checkResult['orphaned_transactions']}）",
            'transaction_integrity' => "交易完整性檢查發現 {$checkResult['error_rate']}% 的錯誤率",
            'system_performance' => "系統響應時間 {$checkResult['response_time_ms']}ms 超過閾值 {$checkResult['threshold_ms']}ms",
            default => "檢查 {$checkName} 發現問題"
        };
    }

    /**
     * 發送警報通知
     */
    private function sendAlerts(array $alerts): void
    {
        foreach ($alerts as $alert) {
            try {
                // 記錄到日誌
                if (in_array('log', $this->alertChannels)) {
                    Log::warning('Channel monitoring alert', $alert);
                }

                // 儲存到資料庫
                if (in_array('database', $this->alertChannels)) {
                    DB::table('channel_monitoring_alerts')->insert([
                        'type' => $alert['type'],
                        'severity' => $alert['severity'],
                        'message' => $alert['message'],
                        'data' => json_encode($alert['data']),
                        'created_at' => now(),
                        'updated_at' => now()
                    ]);
                }

                // 發送通知（如果配置了）
                if (in_array('notification', $this->alertChannels)) {
                    $this->sendNotificationAlert($alert);
                }

            } catch (\Exception $e) {
                Log::error('Failed to send alert', [
                    'alert' => $alert,
                    'error' => $e->getMessage()
                ]);
            }
        }
    }

    /**
     * 發送通知警報
     */
    private function sendNotificationAlert(array $alert): void
    {
        // 獲取需要通知的使用者（系統管理員）
        $adminUsers = \App\Models\User::whereHas('roles', function ($query) {
            $query->where('name', 'admin');
        })->get();

        if ($adminUsers->isNotEmpty()) {
            Notification::send($adminUsers, new ChannelIntegrityAlert($alert));
        }
    }

    /**
     * 更新監控快取
     */
    private function updateMonitoringCache(array $results): void
    {
        Cache::put('channel.monitoring.last_check', $results, now()->addHours(24));
        Cache::put('channel.monitoring.status', $results['status'], now()->addHours(24));
        Cache::put('channel.monitoring.metrics', $results['metrics'], now()->addHours(1));
    }

    /**
     * 獲取監控儀表板資料
     */
    public function getDashboardData(): array
    {
        $lastCheck = Cache::get('channel.monitoring.last_check');
        $currentMetrics = Cache::get('channel.monitoring.metrics');

        return [
            'status' => Cache::get('channel.monitoring.status', 'unknown'),
            'last_check' => $lastCheck ? $lastCheck['timestamp'] : null,
            'metrics' => $currentMetrics ?? [],
            'recent_alerts' => $this->getRecentAlerts(),
            'system_health' => $this->integrityService->checkSystemHealth()
        ];
    }

    /**
     * 獲取最近的警報
     */
    private function getRecentAlerts(int $limit = 10): array
    {
        try {
            return DB::table('channel_monitoring_alerts')
                ->orderBy('created_at', 'desc')
                ->limit($limit)
                ->get()
                ->map(function ($alert) {
                    $alert->data = json_decode($alert->data, true);
                    return $alert;
                })
                ->toArray();
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * 清理舊的監控資料
     */
    public function cleanupOldData(int $daysToKeep = 30): int
    {
        $cutoffDate = now()->subDays($daysToKeep);
        
        try {
            return DB::table('channel_monitoring_alerts')
                ->where('created_at', '<', $cutoffDate)
                ->delete();
        } catch (\Exception $e) {
            Log::error('Failed to cleanup old monitoring data', [
                'error' => $e->getMessage()
            ]);
            return 0;
        }
    }

    /**
     * 設定監控閾值
     */
    public function setThreshold(string $metric, float $value): void
    {
        $this->thresholds[$metric] = $value;
        
        // 可以選擇將閾值儲存到配置檔案或資料庫
        Cache::put("channel.monitoring.threshold.{$metric}", $value, now()->addDays(30));
    }

    /**
     * 獲取監控閾值
     */
    public function getThresholds(): array
    {
        return $this->thresholds;
    }

    /**
     * 執行自動修復
     */
    public function performAutoRepair(): array
    {
        $results = [
            'repairs_attempted' => 0,
            'repairs_successful' => 0,
            'repairs_failed' => 0,
            'details' => []
        ];

        try {
            // 修復點數一致性問題
            $pointsRepair = $this->integrityService->recalculateAllPoints();
            $results['repairs_attempted']++;
            
            if (empty($pointsRepair['errors'])) {
                $results['repairs_successful']++;
                $results['details'][] = "成功修復 {$pointsRepair['agents_updated']} 個代理的點數問題";
            } else {
                $results['repairs_failed']++;
                $results['details'][] = "點數修復失敗: " . implode(', ', $pointsRepair['errors']);
            }

        } catch (\Exception $e) {
            $results['repairs_failed']++;
            $results['details'][] = "自動修復過程發生錯誤: " . $e->getMessage();
        }

        return $results;
    }
}