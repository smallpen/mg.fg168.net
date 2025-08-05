<?php

namespace Tests\Feature;

use App\Services\LoggingService;
use App\Services\MonitoringService;
use App\Services\BackupService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * 監控系統功能測試
 */
class MonitoringSystemTest extends TestCase
{
    use RefreshDatabase;

    protected LoggingService $loggingService;
    protected MonitoringService $monitoringService;
    protected BackupService $backupService;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->loggingService = app(LoggingService::class);
        $this->monitoringService = app(MonitoringService::class);
        $this->backupService = app(BackupService::class);
    }

    /**
     * 測試日誌服務基本功能
     */
    public function test_logging_service_basic_functionality(): void
    {
        // 測試管理員活動日誌
        $this->loggingService->logAdminActivity('test_action', 'test_resource', ['key' => 'value']);
        
        // 測試安全事件日誌
        $this->loggingService->logSecurityEvent('test_event', 'Test description', [], 'medium');
        
        // 測試效能指標日誌
        $this->loggingService->logPerformanceMetric('test_metric', 100.5, 'ms');
        
        // 測試健康狀態日誌
        $this->loggingService->logHealthStatus('test_component', 'healthy');
        
        // 測試備份操作日誌
        $this->loggingService->logBackupOperation('test_backup', 'completed');
        
        $this->assertTrue(true); // 如果沒有例外，測試通過
    }

    /**
     * 測試監控服務效能指標收集
     */
    public function test_monitoring_service_collects_performance_metrics(): void
    {
        $metrics = $this->monitoringService->collectPerformanceMetrics();
        
        // 驗證必要的指標存在
        $this->assertArrayHasKey('memory', $metrics);
        $this->assertArrayHasKey('disk', $metrics);
        $this->assertArrayHasKey('database', $metrics);
        $this->assertArrayHasKey('redis', $metrics);
        
        // 驗證記憶體指標結構
        $this->assertArrayHasKey('current', $metrics['memory']);
        $this->assertArrayHasKey('peak', $metrics['memory']);
        $this->assertArrayHasKey('current_mb', $metrics['memory']);
        $this->assertArrayHasKey('peak_mb', $metrics['memory']);
        
        // 驗證磁碟指標結構
        $this->assertArrayHasKey('usage_percent', $metrics['disk']);
        $this->assertArrayHasKey('free_gb', $metrics['disk']);
        $this->assertArrayHasKey('total_gb', $metrics['disk']);
    }

    /**
     * 測試監控服務健康檢查
     */
    public function test_monitoring_service_checks_system_health(): void
    {
        $health = $this->monitoringService->checkSystemHealth();
        
        // 驗證健康檢查結構
        $this->assertArrayHasKey('overall_status', $health);
        $this->assertArrayHasKey('components', $health);
        $this->assertArrayHasKey('timestamp', $health);
        
        // 驗證組件檢查
        $this->assertArrayHasKey('database', $health['components']);
        $this->assertArrayHasKey('redis', $health['components']);
        $this->assertArrayHasKey('filesystem', $health['components']);
        $this->assertArrayHasKey('application', $health['components']);
        
        // 驗證每個組件都有狀態
        foreach ($health['components'] as $component => $status) {
            $this->assertArrayHasKey('status', $status);
            $this->assertContains($status['status'], ['healthy', 'warning', 'critical']);
        }
    }

    /**
     * 測試健康檢查 API 端點
     */
    public function test_health_check_api_endpoints(): void
    {
        // 測試基本健康檢查
        $response = $this->get('/api/health');
        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'status',
                     'timestamp',
                     'service',
                     'version'
                 ]);

        // 測試詳細健康檢查
        $response = $this->get('/api/health/detailed');
        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'overall_status',
                     'components',
                     'timestamp'
                 ]);

        // 測試效能指標端點
        $response = $this->get('/api/health/metrics');
        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'status',
                     'data',
                     'timestamp'
                 ]);

        // 測試資料庫健康檢查
        $response = $this->get('/api/health/database');
        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'status',
                     'data',
                     'timestamp'
                 ]);

        // 測試系統資訊端點
        $response = $this->get('/api/health/info');
        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'status',
                     'data' => [
                         'application',
                         'laravel',
                         'server',
                         'database',
                         'cache'
                     ],
                     'timestamp'
                 ]);
    }

    /**
     * 測試備份服務列出可用備份
     */
    public function test_backup_service_lists_available_backups(): void
    {
        $backups = $this->backupService->listAvailableBackups();
        
        // 驗證備份列表結構
        $this->assertArrayHasKey('database', $backups);
        $this->assertArrayHasKey('files', $backups);
        $this->assertIsArray($backups['database']);
        $this->assertIsArray($backups['files']);
    }

    /**
     * 測試備份狀態 API 端點
     */
    public function test_backup_status_api_endpoint(): void
    {
        $response = $this->get('/api/health/backups');
        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'status',
                     'data' => [
                         'statistics',
                         'backups'
                     ],
                     'timestamp'
                 ]);
    }

    /**
     * 測試完整系統檢查 API 端點
     */
    public function test_full_system_check_api_endpoint(): void
    {
        // 測試基本完整檢查
        $response = $this->get('/api/health/full');
        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'overall_status',
                     'timestamp',
                     'checks' => [
                         'health',
                         'metrics'
                     ]
                 ]);

        // 測試包含備份的完整檢查
        $response = $this->get('/api/health/full?include_backups=true');
        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'overall_status',
                     'timestamp',
                     'checks' => [
                         'health',
                         'metrics',
                         'backups'
                     ]
                 ]);

        // 測試不包含指標的完整檢查
        $response = $this->get('/api/health/full?include_metrics=false');
        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'overall_status',
                     'timestamp',
                     'checks' => [
                         'health'
                     ]
                 ]);
    }

    /**
     * 測試日誌記錄功能
     */
    public function test_logging_functionality(): void
    {
        // 測試不同類型的日誌記錄（不使用 Mock，直接測試功能）
        try {
            $this->loggingService->logAdminActivity('test', 'resource');
            $this->loggingService->logSecurityEvent('test', 'description', [], 'medium');
            $this->loggingService->logPerformanceMetric('test', 100);
            $this->loggingService->logHealthStatus('test', 'healthy');
            $this->loggingService->logBackupOperation('test', 'completed');
            
            // 如果沒有例外拋出，測試通過
            $this->assertTrue(true);
        } catch (\Exception $e) {
            $this->fail('日誌記錄功能測試失敗: ' . $e->getMessage());
        }
    }

    /**
     * 測試警報檢查功能
     */
    public function test_alert_checking(): void
    {
        // 模擬高記憶體使用量
        $metrics = [
            'memory' => ['current_mb' => 600], // 超過 512MB 閾值
            'disk' => ['usage_percent' => 90], // 超過 85% 閾值
            'database' => ['response_time_ms' => 1500], // 超過 1000ms 閾值
        ];

        $health = [
            'overall_status' => 'critical',
            'components' => [
                'database' => ['status' => 'critical'],
            ]
        ];

        // 執行警報檢查（不應該拋出例外）
        $this->monitoringService->checkAlerts($metrics, $health);
        
        $this->assertTrue(true); // 如果沒有例外，測試通過
    }
}