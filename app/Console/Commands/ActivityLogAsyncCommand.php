<?php

namespace App\Console\Commands;

use App\Services\AsyncActivityManager;
use App\Jobs\ActivityPerformanceMonitorJob;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

/**
 * 非同步活動記錄系統管理命令
 */
class ActivityLogAsyncCommand extends Command
{
    /**
     * 命令名稱和簽名
     *
     * @var string
     */
    protected $signature = 'activity-log:async 
                            {action : 動作 (start|stop|restart|status|maintenance|monitor)}
                            {--force : 強制執行}
                            {--time-range=1h : 監控時間範圍}
                            {--monitor-type=all : 監控類型}';

    /**
     * 命令描述
     *
     * @var string
     */
    protected $description = '管理非同步活動記錄系統';

    /**
     * 非同步活動管理器
     */
    protected AsyncActivityManager $manager;

    /**
     * 建構函式
     */
    public function __construct(AsyncActivityManager $manager)
    {
        parent::__construct();
        $this->manager = $manager;
    }

    /**
     * 執行命令
     *
     * @return int
     */
    public function handle(): int
    {
        $action = $this->argument('action');

        try {
            switch ($action) {
                case 'start':
                    return $this->handleStart();
                case 'stop':
                    return $this->handleStop();
                case 'restart':
                    return $this->handleRestart();
                case 'status':
                    return $this->handleStatus();
                case 'maintenance':
                    return $this->handleMaintenance();
                case 'monitor':
                    return $this->handleMonitor();
                default:
                    $this->error("未知的動作: {$action}");
                    $this->showHelp();
                    return 1;
            }
        } catch (\Exception $e) {
            $this->error("執行失敗: {$e->getMessage()}");
            return 1;
        }
    }

    /**
     * 處理啟動命令
     *
     * @return int
     */
    protected function handleStart(): int
    {
        $this->info('正在啟動非同步活動記錄系統...');

        $result = $this->manager->start();

        if ($result['status'] === 'success') {
            $this->info('✅ 系統啟動成功');
            $this->displayStartupResults($result);
            return 0;
        } else {
            $this->error('❌ 系統啟動失敗');
            $this->error("錯誤: {$result['error']}");
            return 1;
        }
    }

    /**
     * 處理停止命令
     *
     * @return int
     */
    protected function handleStop(): int
    {
        if (!$this->option('force')) {
            if (!$this->confirm('確定要停止非同步活動記錄系統嗎？')) {
                $this->info('操作已取消');
                return 0;
            }
        }

        $this->info('正在停止非同步活動記錄系統...');

        $result = $this->manager->stop();

        if ($result['status'] === 'success') {
            $this->info('✅ 系統停止成功');
            $this->displayShutdownResults($result);
            return 0;
        } else {
            $this->error('❌ 系統停止失敗');
            $this->error("錯誤: {$result['error']}");
            return 1;
        }
    }

    /**
     * 處理重新啟動命令
     *
     * @return int
     */
    protected function handleRestart(): int
    {
        $this->info('正在重新啟動非同步活動記錄系統...');

        $result = $this->manager->restart();

        if ($result['restart_successful']) {
            $this->info('✅ 系統重新啟動成功');
            return 0;
        } else {
            $this->error('❌ 系統重新啟動失敗');
            $this->displayRestartResults($result);
            return 1;
        }
    }

    /**
     * 處理狀態查詢命令
     *
     * @return int
     */
    protected function handleStatus(): int
    {
        $this->info('正在檢查系統狀態...');

        $status = $this->manager->getSystemStatus();

        $this->displaySystemStatus($status);

        return 0;
    }

    /**
     * 處理維護命令
     *
     * @return int
     */
    protected function handleMaintenance(): int
    {
        if (!$this->option('force')) {
            if (!$this->confirm('確定要執行系統維護嗎？')) {
                $this->info('操作已取消');
                return 0;
            }
        }

        $this->info('正在執行系統維護...');

        $result = $this->manager->performMaintenance();

        if ($result['status'] === 'success') {
            $this->info('✅ 系統維護完成');
            $this->displayMaintenanceResults($result);
            return 0;
        } else {
            $this->error('❌ 系統維護失敗');
            $this->error("錯誤: {$result['error']}");
            return 1;
        }
    }

    /**
     * 處理監控命令
     *
     * @return int
     */
    protected function handleMonitor(): int
    {
        $timeRange = $this->option('time-range');
        $monitorType = $this->option('monitor-type');

        $this->info("正在執行效能監控 (範圍: {$timeRange}, 類型: {$monitorType})...");

        // 分派監控工作
        dispatch(new ActivityPerformanceMonitorJob($timeRange, $monitorType));

        $this->info('✅ 監控工作已分派');
        $this->info('請查看日誌檔案以取得詳細的監控結果');

        return 0;
    }

    /**
     * 顯示啟動結果
     *
     * @param array $result
     * @return void
     */
    protected function displayStartupResults(array $result): void
    {
        $this->table(['項目', '狀態'], [
            ['系統檢查', $result['system_check']['ready'] ? '✅ 通過' : '❌ 失敗'],
            ['效能監控', $result['performance_monitoring'] === 'initialized' ? '✅ 已初始化' : '❌ 失敗'],
            ['排程任務', $result['scheduled_tasks'] === 'configured' ? '✅ 已配置' : '❌ 失敗'],
            ['初始清理', isset($result['cleanup']) ? '✅ 完成' : '❌ 失敗'],
            ['啟動時間', round($result['startup_time_ms'] ?? 0, 2) . ' ms'],
        ]);

        if (!$result['system_check']['ready']) {
            $this->warn('系統檢查發現問題:');
            foreach ($result['system_check']['issues'] as $issue) {
                $this->line("  • {$issue}");
            }
        }
    }

    /**
     * 顯示停止結果
     *
     * @param array $result
     * @return void
     */
    protected function displayShutdownResults(array $result): void
    {
        $this->table(['項目', '結果'], [
            ['處理剩餘活動', ($result['flushed_activities'] ?? 0) . ' 筆'],
            ['最終報告', $result['final_report'] === 'generated' ? '✅ 已生成' : '❌ 失敗'],
            ['快取清理', $result['cache_cleanup'] === 'completed' ? '✅ 完成' : '❌ 失敗'],
            ['停止時間', round($result['shutdown_time_ms'] ?? 0, 2) . ' ms'],
        ]);
    }

    /**
     * 顯示重新啟動結果
     *
     * @param array $result
     * @return void
     */
    protected function displayRestartResults(array $result): void
    {
        $this->info('停止結果:');
        $this->line('狀態: ' . ($result['stop_result']['status'] === 'success' ? '✅ 成功' : '❌ 失敗'));

        $this->info('啟動結果:');
        $this->line('狀態: ' . ($result['start_result']['status'] === 'success' ? '✅ 成功' : '❌ 失敗'));

        if (!$result['restart_successful']) {
            if ($result['stop_result']['status'] !== 'success') {
                $this->error('停止失敗: ' . ($result['stop_result']['error'] ?? '未知錯誤'));
            }
            if ($result['start_result']['status'] !== 'success') {
                $this->error('啟動失敗: ' . ($result['start_result']['error'] ?? '未知錯誤'));
            }
        }
    }

    /**
     * 顯示系統狀態
     *
     * @param array $status
     * @return void
     */
    protected function displaySystemStatus(array $status): void
    {
        // 基本狀態
        $this->info('=== 系統狀態 ===');
        $this->table(['項目', '狀態'], [
            ['運行狀態', $status['is_running'] ? '🟢 運行中' : '🔴 已停止'],
            ['最後啟動', $status['last_startup'] ?? 'N/A'],
            ['運行時間', round($status['uptime_hours'] ?? 0, 2) . ' 小時'],
        ]);

        // 佇列統計
        $this->info('=== 佇列統計 ===');
        $queueData = [];
        foreach ($status['queue_stats']['queues'] as $queue => $size) {
            $queueData[] = [$queue, $size];
        }
        $this->table(['佇列名稱', '大小'], $queueData);

        // 效能指標
        $this->info('=== 效能指標 ===');
        $performance = $status['performance_metrics'];
        $this->table(['指標', '數值'], [
            ['平均分派時間', round($performance['average_dispatch_time'] ?? 0, 2) . ' ms'],
            ['平均處理時間', round($performance['average_processing_time'] ?? 0, 2) . ' ms'],
            ['總分派數', $performance['total_dispatched'] ?? 0],
            ['總處理數', $performance['total_processed'] ?? 0],
            ['成功率', round($performance['success_rate'] ?? 0, 2) . '%'],
        ]);

        // 健康狀態
        $this->info('=== 健康狀態 ===');
        $health = $status['health_status'];
        $healthColor = match ($health['status']) {
            'healthy' => '🟢',
            'warning' => '🟡',
            'critical' => '🔴',
            default => '⚪',
        };
        
        $this->line("整體狀態: {$healthColor} {$health['status']}");
        
        if (!empty($health['issues'])) {
            $this->warn('發現問題:');
            foreach ($health['issues'] as $issue) {
                $this->line("  • {$issue}");
            }
        }
        
        if (!empty($health['recommendations'])) {
            $this->info('建議:');
            foreach ($health['recommendations'] as $recommendation) {
                $this->line("  • {$recommendation}");
            }
        }
    }

    /**
     * 顯示維護結果
     *
     * @param array $result
     * @return void
     */
    protected function displayMaintenanceResults(array $result): void
    {
        $this->table(['維護項目', '結果'], [
            ['清理效能資料', ($result['cleaned_performance_data'] ?? 0) . ' 筆'],
            ['佇列優化', '已完成'],
            ['健康檢查', '已完成'],
            ['維護報告', $result['maintenance_report'] ?? 'N/A'],
            ['維護時間', round($result['maintenance_time_ms'] ?? 0, 2) . ' ms'],
        ]);

        if (isset($result['queue_optimization'])) {
            $this->info('=== 佇列優化結果 ===');
            foreach ($result['queue_optimization'] as $queue => $info) {
                $priority = match ($info['priority']) {
                    'high' => '🔴',
                    'medium' => '🟡',
                    'low' => '🟢',
                    default => '⚪',
                };
                
                $this->line("{$priority} {$queue}: {$info['size']} 個工作");
                if (isset($info['recommendation'])) {
                    $this->line("   建議: {$info['recommendation']}");
                }
            }
        }
    }

    /**
     * 顯示幫助資訊
     *
     * @return void
     */
    protected function showHelp(): void
    {
        $this->info('可用的動作:');
        $this->line('  start       - 啟動非同步活動記錄系統');
        $this->line('  stop        - 停止非同步活動記錄系統');
        $this->line('  restart     - 重新啟動系統');
        $this->line('  status      - 檢查系統狀態');
        $this->line('  maintenance - 執行系統維護');
        $this->line('  monitor     - 執行效能監控');
        $this->line('');
        $this->line('選項:');
        $this->line('  --force           - 強制執行，不詢問確認');
        $this->line('  --time-range=1h   - 監控時間範圍 (1h, 6h, 24h)');
        $this->line('  --monitor-type=all - 監控類型 (queue, database, performance, health, all)');
    }
}