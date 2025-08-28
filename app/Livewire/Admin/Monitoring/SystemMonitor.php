<?php

namespace App\Livewire\Admin\Monitoring;

use App\Services\MonitoringService;
use App\Services\BackupService;
use Livewire\Component;
use Livewire\Attributes\On;

/**
 * 系統監控 Livewire 元件
 * 
 * 提供即時的系統健康狀態和效能指標顯示
 */
class SystemMonitor extends Component
{
    public array $healthStatus = [];
    public array $performanceMetrics = [];
    public array $backupStatus = [];
    public bool $autoRefresh = true;
    public int $refreshInterval = 30; // 秒
    public string $lastUpdated = '';

    protected MonitoringService $monitoringService;
    protected BackupService $backupService;

    public function boot(MonitoringService $monitoringService, BackupService $backupService)
    {
        $this->monitoringService = $monitoringService;
        $this->backupService = $backupService;
    }

    public function mount()
    {
        $this->refreshData();
    }

    /**
     * 刷新監控資料
     */
    public function refreshData()
    {
        try {
            // 獲取系統健康狀態
            $this->healthStatus = $this->monitoringService->checkSystemHealth();
            
            // 獲取效能指標
            $this->performanceMetrics = $this->monitoringService->collectPerformanceMetrics();
            
            // 獲取備份狀態
            $backups = $this->backupService->listAvailableBackups();
            $this->backupStatus = [
                'database_count' => count($backups['database']),
                'files_count' => count($backups['files']),
                'latest_database' => $backups['database'][0]['created_at'] ?? null,
                'latest_files' => $backups['files'][0]['created_at'] ?? null,
            ];
            
            $this->lastUpdated = now()->format('Y-m-d H:i:s');
            
            // 檢查警報
            $this->monitoringService->checkAlerts($this->performanceMetrics, $this->healthStatus);
            
            // 清除之前的錯誤訊息
            session()->forget('system_monitor_error');
            
            // 強制重新渲染元件
            $this->dispatch('$refresh');
            
            // 發送資料更新事件
            $this->dispatch('system-monitor-data-updated');
            
        } catch (\Exception $e) {
            // 記錄錯誤
            \Log::error('系統監控資料更新失敗', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            // 設定預設值
            $this->healthStatus = [
                'overall_status' => 'unknown',
                'components' => []
            ];
            $this->performanceMetrics = [
                'memory' => ['current_mb' => 0, 'peak_mb' => 0],
                'disk' => ['usage_percent' => 0, 'free_gb' => 0, 'total_gb' => 0]
            ];
            $this->backupStatus = [
                'database_count' => 0,
                'files_count' => 0,
                'latest_database' => null,
                'latest_files' => null
            ];
            
            session()->flash('system_monitor_error', '監控資料更新失敗: ' . $e->getMessage());
        }
    }

    /**
     * 切換自動刷新
     */
    public function toggleAutoRefresh()
    {
        $this->autoRefresh = !$this->autoRefresh;
        
        // 強制重新渲染元件以確保前端同步
        $this->dispatch('$refresh');
        
        // 發送前端刷新事件
        $this->dispatch('system-monitor-auto-refresh-changed', autoRefresh: $this->autoRefresh);
    }

    /**
     * 設定刷新間隔
     */
    public function setRefreshInterval(int $interval)
    {
        $this->refreshInterval = max(10, min(300, $interval)); // 限制在 10-300 秒之間
        
        // 強制重新渲染元件以確保前端同步
        $this->dispatch('$refresh');
        
        // 發送前端刷新事件
        $this->dispatch('system-monitor-interval-changed', interval: $this->refreshInterval);
    }

    /**
     * 當自動刷新狀態變更時觸發
     */
    public function updatedAutoRefresh($value)
    {
        // 強制重新渲染元件以確保前端同步
        $this->dispatch('$refresh');
        
        // 發送前端刷新事件
        $this->dispatch('system-monitor-auto-refresh-changed', autoRefresh: $value);
    }

    /**
     * 當刷新間隔變更時觸發
     */
    public function updatedRefreshInterval($value)
    {
        $this->refreshInterval = max(10, min(300, (int)$value)); // 限制在 10-300 秒之間
        
        // 強制重新渲染元件以確保前端同步
        $this->dispatch('$refresh');
        
        // 發送前端刷新事件
        $this->dispatch('system-monitor-interval-changed', interval: $this->refreshInterval);
    }

    /**
     * 執行系統備份
     */
    public function performBackup(string $type = 'full')
    {
        try {
            switch ($type) {
                case 'database':
                    $result = $this->backupService->backupDatabase();
                    break;
                case 'files':
                    $result = $this->backupService->backupFiles();
                    break;
                case 'full':
                default:
                    $result = $this->backupService->performFullBackup();
                    break;
            }

            if ($result['success'] ?? false) {
                session()->flash('success', "備份完成: {$type}");
                $this->refreshData(); // 刷新備份狀態
            } else {
                session()->flash('error', '備份失敗: ' . ($result['error'] ?? '未知錯誤'));
            }

        } catch (\Exception $e) {
            session()->flash('error', '備份執行失敗: ' . $e->getMessage());
        }
    }

    /**
     * 獲取狀態顏色類別
     */
    public function getStatusColor(string $status): string
    {
        return match ($status) {
            'healthy' => 'text-green-600 bg-green-100',
            'warning' => 'text-yellow-600 bg-yellow-100',
            'critical' => 'text-red-600 bg-red-100',
            default => 'text-gray-600 bg-gray-100',
        };
    }

    /**
     * 獲取狀態圖示
     */
    public function getStatusIcon(string $status): string
    {
        return match ($status) {
            'healthy' => 'check-circle',
            'warning' => 'exclamation-triangle',
            'critical' => 'x-circle',
            default => 'question-mark-circle',
        };
    }

    /**
     * 格式化位元組大小
     */
    public function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        
        $bytes /= pow(1024, $pow);
        
        return round($bytes, 2) . ' ' . $units[$pow];
    }

    /**
     * 監聽自動刷新事件
     */
    #[On('auto-refresh')]
    public function handleAutoRefresh()
    {
        if ($this->autoRefresh) {
            $this->refreshData();
        }
    }

    public function render()
    {
        return view('livewire.admin.monitoring.system-monitor');
    }
}