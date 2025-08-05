<?php

namespace App\Http\Controllers;

use App\Services\MonitoringService;
use App\Services\BackupService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * 健康檢查控制器
 * 
 * 提供系統健康狀態和監控資訊的 API 端點
 */
class HealthController extends Controller
{
    protected MonitoringService $monitoringService;
    protected BackupService $backupService;

    public function __construct(MonitoringService $monitoringService, BackupService $backupService)
    {
        $this->monitoringService = $monitoringService;
        $this->backupService = $backupService;
    }

    /**
     * 基本健康檢查端點
     * 
     * @return JsonResponse
     */
    public function basic(): JsonResponse
    {
        return response()->json([
            'status' => 'healthy',
            'timestamp' => now()->toISOString(),
            'service' => 'Laravel Admin System',
            'version' => app()->version(),
        ]);
    }

    /**
     * 詳細健康檢查端點
     * 
     * @return JsonResponse
     */
    public function detailed(): JsonResponse
    {
        try {
            $health = $this->monitoringService->checkSystemHealth();
            
            $httpStatus = match ($health['overall_status']) {
                'healthy' => 200,
                'warning' => 200, // 警告狀態仍返回 200，但在回應中標示
                'critical' => 503, // 服務不可用
                default => 200,
            };

            return response()->json($health, $httpStatus);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => '健康檢查失敗',
                'error' => $e->getMessage(),
                'timestamp' => now()->toISOString(),
            ], 500);
        }
    }

    /**
     * 效能指標端點
     * 
     * @return JsonResponse
     */
    public function metrics(): JsonResponse
    {
        try {
            $metrics = $this->monitoringService->collectPerformanceMetrics();
            
            return response()->json([
                'status' => 'success',
                'data' => $metrics,
                'timestamp' => now()->toISOString(),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => '效能指標收集失敗',
                'error' => $e->getMessage(),
                'timestamp' => now()->toISOString(),
            ], 500);
        }
    }

    /**
     * 資料庫健康檢查端點
     * 
     * @return JsonResponse
     */
    public function database(): JsonResponse
    {
        try {
            $health = $this->monitoringService->checkSystemHealth();
            $dbHealth = $health['components']['database'] ?? null;

            if (!$dbHealth) {
                return response()->json([
                    'status' => 'error',
                    'message' => '無法取得資料庫健康狀態',
                    'timestamp' => now()->toISOString(),
                ], 500);
            }

            $httpStatus = match ($dbHealth['status']) {
                'healthy' => 200,
                'warning' => 200,
                'critical' => 503,
                default => 500,
            };

            return response()->json([
                'status' => $dbHealth['status'],
                'data' => $dbHealth,
                'timestamp' => now()->toISOString(),
            ], $httpStatus);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => '資料庫健康檢查失敗',
                'error' => $e->getMessage(),
                'timestamp' => now()->toISOString(),
            ], 500);
        }
    }

    /**
     * Redis 健康檢查端點
     * 
     * @return JsonResponse
     */
    public function redis(): JsonResponse
    {
        try {
            $health = $this->monitoringService->checkSystemHealth();
            $redisHealth = $health['components']['redis'] ?? null;

            if (!$redisHealth) {
                return response()->json([
                    'status' => 'error',
                    'message' => '無法取得 Redis 健康狀態',
                    'timestamp' => now()->toISOString(),
                ], 500);
            }

            $httpStatus = match ($redisHealth['status']) {
                'healthy' => 200,
                'warning' => 200,
                'critical' => 503,
                default => 500,
            };

            return response()->json([
                'status' => $redisHealth['status'],
                'data' => $redisHealth,
                'timestamp' => now()->toISOString(),
            ], $httpStatus);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Redis 健康檢查失敗',
                'error' => $e->getMessage(),
                'timestamp' => now()->toISOString(),
            ], 500);
        }
    }

    /**
     * 備份狀態端點
     * 
     * @return JsonResponse
     */
    public function backups(): JsonResponse
    {
        try {
            $backups = $this->backupService->listAvailableBackups();
            
            // 計算備份統計
            $stats = [
                'database_backups_count' => count($backups['database']),
                'files_backups_count' => count($backups['files']),
                'latest_database_backup' => $backups['database'][0]['created_at'] ?? null,
                'latest_files_backup' => $backups['files'][0]['created_at'] ?? null,
                'total_backup_size_mb' => 0,
            ];

            // 計算總備份大小
            foreach ($backups['database'] as $backup) {
                $stats['total_backup_size_mb'] += $backup['size_mb'];
            }
            foreach ($backups['files'] as $backup) {
                $stats['total_backup_size_mb'] += $backup['size_mb'];
            }
            $stats['total_backup_size_mb'] = round($stats['total_backup_size_mb'], 2);

            return response()->json([
                'status' => 'success',
                'data' => [
                    'statistics' => $stats,
                    'backups' => $backups,
                ],
                'timestamp' => now()->toISOString(),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => '備份狀態檢查失敗',
                'error' => $e->getMessage(),
                'timestamp' => now()->toISOString(),
            ], 500);
        }
    }

    /**
     * 系統資訊端點
     * 
     * @return JsonResponse
     */
    public function info(): JsonResponse
    {
        try {
            return response()->json([
                'status' => 'success',
                'data' => [
                    'application' => [
                        'name' => config('app.name'),
                        'environment' => config('app.env'),
                        'debug' => config('app.debug'),
                        'url' => config('app.url'),
                        'timezone' => config('app.timezone'),
                        'locale' => config('app.locale'),
                    ],
                    'laravel' => [
                        'version' => app()->version(),
                        'php_version' => PHP_VERSION,
                    ],
                    'server' => [
                        'os' => PHP_OS,
                        'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
                        'document_root' => $_SERVER['DOCUMENT_ROOT'] ?? 'Unknown',
                    ],
                    'database' => [
                        'default_connection' => config('database.default'),
                        'connections' => array_keys(config('database.connections')),
                    ],
                    'cache' => [
                        'default_store' => config('cache.default'),
                        'stores' => array_keys(config('cache.stores')),
                    ],
                ],
                'timestamp' => now()->toISOString(),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => '系統資訊取得失敗',
                'error' => $e->getMessage(),
                'timestamp' => now()->toISOString(),
            ], 500);
        }
    }

    /**
     * 執行完整系統檢查
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function fullCheck(Request $request): JsonResponse
    {
        try {
            $includeMetrics = $request->boolean('include_metrics', true);
            $includeBackups = $request->boolean('include_backups', false);

            $result = [
                'overall_status' => 'healthy',
                'timestamp' => now()->toISOString(),
                'checks' => [],
            ];

            // 基本健康檢查
            $health = $this->monitoringService->checkSystemHealth();
            $result['checks']['health'] = $health;
            $result['overall_status'] = $health['overall_status'];

            // 效能指標（可選）
            if ($includeMetrics) {
                $metrics = $this->monitoringService->collectPerformanceMetrics();
                $result['checks']['metrics'] = $metrics;
            }

            // 備份狀態（可選）
            if ($includeBackups) {
                $backups = $this->backupService->listAvailableBackups();
                $result['checks']['backups'] = $backups;
            }

            $httpStatus = match ($result['overall_status']) {
                'healthy' => 200,
                'warning' => 200,
                'critical' => 503,
                default => 200,
            };

            return response()->json($result, $httpStatus);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => '完整系統檢查失敗',
                'error' => $e->getMessage(),
                'timestamp' => now()->toISOString(),
            ], 500);
        }
    }
}