<?php

namespace App\Console\Commands;

use App\Services\MonitoringService;
use Illuminate\Console\Command;

/**
 * 系統監控指令
 */
class MonitorSystem extends Command
{
    /**
     * 指令名稱和簽名
     *
     * @var string
     */
    protected $signature = 'monitor:system 
                            {--check=all : 檢查類型 (all, health, performance, alerts)}
                            {--format=table : 輸出格式 (table, json)}';

    /**
     * 指令描述
     *
     * @var string
     */
    protected $description = '執行系統監控檢查';

    /**
     * 執行指令
     */
    public function handle(MonitoringService $monitoringService): int
    {
        $check = $this->option('check');
        $format = $this->option('format');

        try {
            switch ($check) {
                case 'health':
                    $result = $monitoringService->checkSystemHealth();
                    $this->displayHealthResult($result, $format);
                    break;

                case 'performance':
                    $result = $monitoringService->collectPerformanceMetrics();
                    $this->displayPerformanceResult($result, $format);
                    break;

                case 'alerts':
                    $health = $monitoringService->checkSystemHealth();
                    $metrics = $monitoringService->collectPerformanceMetrics();
                    $monitoringService->checkAlerts($metrics, $health);
                    $this->info('警報檢查完成');
                    break;

                case 'all':
                default:
                    $health = $monitoringService->checkSystemHealth();
                    $metrics = $monitoringService->collectPerformanceMetrics();
                    
                    $this->displayHealthResult($health, $format);
                    $this->line('');
                    $this->displayPerformanceResult($metrics, $format);
                    
                    $monitoringService->checkAlerts($metrics, $health);
                    break;
            }

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error('監控檢查失敗: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }

    /**
     * 顯示健康檢查結果
     */
    protected function displayHealthResult(array $health, string $format): void
    {
        if ($format === 'json') {
            $this->line(json_encode($health, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            return;
        }

        $this->info('=== 系統健康狀態 ===');
        
        // 整體狀態
        $statusColor = match ($health['overall_status']) {
            'healthy' => 'info',
            'warning' => 'comment',
            'critical' => 'error',
            default => 'line',
        };
        
        $this->{$statusColor}("整體狀態: {$health['overall_status']}");
        $this->line("檢查時間: {$health['timestamp']}");
        $this->line('');

        // 組件狀態表格
        $headers = ['組件', '狀態', '回應時間', '訊息'];
        $rows = [];

        foreach ($health['components'] as $component => $status) {
            $responseTime = isset($status['response_time_ms']) 
                ? $status['response_time_ms'] . 'ms' 
                : 'N/A';
            
            $rows[] = [
                $component,
                $status['status'],
                $responseTime,
                $status['message'] ?? '',
            ];
        }

        $this->table($headers, $rows);
    }

    /**
     * 顯示效能指標結果
     */
    protected function displayPerformanceResult(array $metrics, string $format): void
    {
        if ($format === 'json') {
            $this->line(json_encode($metrics, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            return;
        }

        $this->info('=== 效能指標 ===');

        // 記憶體使用量
        $this->line('記憶體使用量:');
        $this->line("  目前: {$metrics['memory']['current_mb']} MB");
        $this->line("  峰值: {$metrics['memory']['peak_mb']} MB");
        $this->line('');

        // CPU 負載（如果可用）
        if (isset($metrics['cpu_load'])) {
            $this->line('CPU 負載:');
            $this->line("  1分鐘: {$metrics['cpu_load']['1min']}");
            $this->line("  5分鐘: {$metrics['cpu_load']['5min']}");
            $this->line("  15分鐘: {$metrics['cpu_load']['15min']}");
            $this->line('');
        }

        // 磁碟使用量
        $this->line('磁碟使用量:');
        $this->line("  使用率: {$metrics['disk']['usage_percent']}%");
        $this->line("  可用空間: {$metrics['disk']['free_gb']} GB");
        $this->line("  總空間: {$metrics['disk']['total_gb']} GB");
        $this->line('');

        // 資料庫狀態
        $this->line('資料庫:');
        $this->line("  狀態: {$metrics['database']['status']}");
        if (isset($metrics['database']['response_time_ms'])) {
            $this->line("  回應時間: {$metrics['database']['response_time_ms']} ms");
        }
        if (isset($metrics['database']['error'])) {
            $this->error("  錯誤: {$metrics['database']['error']}");
        }
        $this->line('');

        // Redis 狀態
        $this->line('Redis:');
        $this->line("  狀態: {$metrics['redis']['status']}");
        if (isset($metrics['redis']['response_time_ms'])) {
            $this->line("  回應時間: {$metrics['redis']['response_time_ms']} ms");
        }
        if (isset($metrics['redis']['error'])) {
            $this->error("  錯誤: {$metrics['redis']['error']}");
        }
    }
}