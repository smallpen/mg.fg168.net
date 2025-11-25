<?php

namespace App\Livewire\Admin\Channels;

use App\Services\ChannelMonitoringService;
use App\Services\ChannelIntegrityService;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\Attributes\On;

class MonitoringDashboard extends Component
{
    public array $dashboardData = [];
    public array $systemHealth = [];
    public array $recentAlerts = [];
    public bool $isLoading = true;
    public string $selectedTab = 'overview';
    public int $refreshInterval = 30; // 秒

    protected ChannelMonitoringService $monitoringService;
    protected ChannelIntegrityService $integrityService;

    public function boot(
        ChannelMonitoringService $monitoringService,
        ChannelIntegrityService $integrityService
    ): void {
        $this->monitoringService = $monitoringService;
        $this->integrityService = $integrityService;
    }

    public function mount(): void
    {
        $this->authorize('channels.monitoring.view');
        $this->loadDashboardData();
    }

    public function render()
    {
        return view('livewire.admin.channels.monitoring-dashboard', [
            'dashboardData' => $this->dashboardData,
            'systemHealth' => $this->systemHealth,
            'recentAlerts' => $this->recentAlerts,
        ]);
    }

    /**
     * 載入儀表板資料
     */
    public function loadDashboardData(): void
    {
        try {
            $this->isLoading = true;
            
            $this->dashboardData = $this->monitoringService->getDashboardData();
            $this->systemHealth = $this->integrityService->checkSystemHealth();
            $this->recentAlerts = $this->getRecentAlerts();
            
        } catch (\Exception $e) {
            $this->dispatch('show-toast', [
                'type' => 'error',
                'message' => '載入監控資料失敗: ' . $e->getMessage()
            ]);
        } finally {
            $this->isLoading = false;
        }
    }

    /**
     * 手動刷新資料
     */
    public function refreshData(): void
    {
        $this->loadDashboardData();
        $this->dispatch('show-toast', [
            'type' => 'success',
            'message' => '監控資料已更新'
        ]);
    }

    /**
     * 執行完整性檢查
     */
    public function runIntegrityCheck(): void
    {
        try {
            $this->authorize('channels.integrity.check');
            
            $this->dispatch('show-toast', [
                'type' => 'info',
                'message' => '正在執行完整性檢查...'
            ]);

            // 在背景執行檢查
            \Illuminate\Support\Facades\Artisan::queue('channel:integrity-check');
            
            $this->dispatch('show-toast', [
                'type' => 'success',
                'message' => '完整性檢查已開始執行'
            ]);

        } catch (\Exception $e) {
            $this->dispatch('show-toast', [
                'type' => 'error',
                'message' => '執行完整性檢查失敗: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * 執行監控檢查
     */
    public function runMonitoringCheck(): void
    {
        try {
            $this->authorize('channels.monitoring.check');
            
            $results = $this->monitoringService->performMonitoringCheck();
            
            $this->loadDashboardData();
            
            $statusMessage = match($results['status']) {
                'healthy' => '系統狀態良好',
                'warning' => '發現 ' . count($results['alerts']) . ' 個警告',
                'critical' => '發現嚴重問題',
                'error' => '檢查過程發生錯誤',
                default => '檢查完成'
            };

            $this->dispatch('show-toast', [
                'type' => $results['status'] === 'healthy' ? 'success' : 'warning',
                'message' => $statusMessage
            ]);

        } catch (\Exception $e) {
            $this->dispatch('show-toast', [
                'type' => 'error',
                'message' => '執行監控檢查失敗: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * 執行自動修復
     */
    public function runAutoRepair(): void
    {
        try {
            $this->authorize('channels.auto.repair');
            
            if (!$this->confirm('確定要執行自動修復嗎？此操作可能會修改資料。')) {
                return;
            }

            $results = $this->monitoringService->performAutoRepair();
            
            $this->loadDashboardData();
            
            $message = "修復完成: 嘗試 {$results['repairs_attempted']} 項，成功 {$results['repairs_successful']} 項";
            
            $this->dispatch('show-toast', [
                'type' => $results['repairs_successful'] > 0 ? 'success' : 'info',
                'message' => $message
            ]);

        } catch (\Exception $e) {
            $this->dispatch('show-toast', [
                'type' => 'error',
                'message' => '執行自動修復失敗: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * 建立備份
     */
    public function createBackup(): void
    {
        try {
            $this->authorize('channels.backup.create');
            
            $this->dispatch('show-toast', [
                'type' => 'info',
                'message' => '正在建立備份...'
            ]);

            $backupPath = $this->integrityService->createBackup();
            
            $this->dispatch('show-toast', [
                'type' => 'success',
                'message' => '備份建立成功: ' . basename($backupPath)
            ]);

        } catch (\Exception $e) {
            $this->dispatch('show-toast', [
                'type' => 'error',
                'message' => '建立備份失敗: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * 解決警報
     */
    public function resolveAlert(int $alertId, string $notes = ''): void
    {
        try {
            $this->authorize('channels.alerts.resolve');
            
            DB::table('channel_monitoring_alerts')
                ->where('id', $alertId)
                ->update([
                    'resolved' => true,
                    'resolved_at' => now(),
                    'resolved_by' => auth()->id(),
                    'resolution_notes' => $notes,
                    'updated_at' => now()
                ]);

            $this->loadDashboardData();
            
            $this->dispatch('show-toast', [
                'type' => 'success',
                'message' => '警報已標記為已解決'
            ]);

        } catch (\Exception $e) {
            $this->dispatch('show-toast', [
                'type' => 'error',
                'message' => '解決警報失敗: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * 切換分頁
     */
    public function switchTab(string $tab): void
    {
        $this->selectedTab = $tab;
        
        if ($tab === 'alerts') {
            $this->recentAlerts = $this->getRecentAlerts(50);
        }
    }

    /**
     * 獲取最近警報
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
     * 獲取狀態圖示
     */
    public function getStatusIcon(string $status): string
    {
        return match($status) {
            'healthy' => '✅',
            'warning' => '⚠️',
            'critical' => '🔴',
            'error' => '❌',
            default => '❓'
        };
    }

    /**
     * 獲取狀態顏色類別
     */
    public function getStatusColorClass(string $status): string
    {
        return match($status) {
            'healthy' => 'text-green-600 bg-green-100',
            'warning' => 'text-yellow-600 bg-yellow-100',
            'critical' => 'text-red-600 bg-red-100',
            'error' => 'text-red-600 bg-red-100',
            default => 'text-gray-600 bg-gray-100'
        };
    }

    /**
     * 格式化數值
     */
    public function formatNumber($value): string
    {
        if (is_numeric($value)) {
            return number_format($value, 2);
        }
        return (string) $value;
    }

    /**
     * 格式化時間
     */
    public function formatTime(?string $timestamp): string
    {
        if (!$timestamp) {
            return '未知';
        }
        
        try {
            return \Carbon\Carbon::parse($timestamp)->diffForHumans();
        } catch (\Exception $e) {
            return $timestamp;
        }
    }

    /**
     * 確認對話框
     */
    private function confirm(string $message): bool
    {
        // 在實際應用中，這應該通過前端 JavaScript 實現
        // 這裡簡化處理
        return true;
    }

    /**
     * 監聽自動刷新事件
     */
    #[On('auto-refresh')]
    public function autoRefresh(): void
    {
        $this->loadDashboardData();
    }

    /**
     * 獲取指標標籤
     */
    public function getMetricLabel(string $key): string
    {
        return match($key) {
            'total_points_in_system' => '系統總點數',
            'total_allocated_points' => '已分配點數',
            'total_remaining_points' => '剩餘點數',
            'total_player_points' => '玩家總點數',
            'active_agents_count' => '活躍代理數',
            'active_players_count' => '活躍玩家數',
            'transactions_today' => '今日交易數',
            'transactions_this_hour' => '本小時交易數',
            'average_agent_points' => '平均代理點數',
            'average_player_points' => '平均玩家點數',
            'max_agent_level' => '最大代理層級',
            'unique_prefixes' => '唯一前置符號數',
            default => $key
        };
    }

    /**
     * 獲取健康檢查分類標籤
     */
    public function getHealthCategoryLabel(string $category): string
    {
        return match($category) {
            'database_connection' => '資料庫連接',
            'table_integrity' => '資料表完整性',
            'data_consistency' => '資料一致性',
            'performance_metrics' => '效能指標',
            default => $category
        };
    }
}
