<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\PermissionImportExportService;
use App\Services\AuditLogService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

/**
 * 權限匯入匯出控制器
 * 
 * 處理權限資料的匯入匯出 HTTP 請求
 */
class PermissionImportExportController extends Controller
{
    protected PermissionImportExportService $importExportService;
    protected AuditLogService $auditService;

    public function __construct(
        PermissionImportExportService $importExportService,
        AuditLogService $auditService
    ) {
        $this->importExportService = $importExportService;
        $this->auditService = $auditService;
    }

    /**
     * 匯出權限資料
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function export(Request $request): JsonResponse
    {
        // 檢查權限
        Gate::authorize('permissions.export');

        try {
            // 驗證請求參數
            $validated = $request->validate([
                'modules' => 'nullable|array',
                'modules.*' => 'string|max:50',
                'types' => 'nullable|array',
                'types.*' => 'string|max:20',
                'usage_status' => 'nullable|string|in:all,used,unused',
                'permission_ids' => 'nullable|array',
                'permission_ids.*' => 'integer|exists:permissions,id',
                'format' => 'nullable|string|in:json,csv',
            ]);

            // 準備篩選條件
            $filters = [
                'modules' => $validated['modules'] ?? [],
                'types' => $validated['types'] ?? [],
                'usage_status' => $validated['usage_status'] ?? 'all',
                'permission_ids' => $validated['permission_ids'] ?? [],
            ];

            // 執行匯出
            $exportData = $this->importExportService->exportPermissions($filters);

            // 生成檔案名稱
            $timestamp = now()->format('Y-m-d_H-i-s');
            $filename = "permissions_export_{$timestamp}.json";

            // 記錄匯出操作
            $this->auditService->logDataAccess('permissions', 'export', [
                'total_exported' => $exportData['metadata']['total_permissions'],
                'filters' => $filters,
                'filename' => $filename,
            ]);

            return response()->json($exportData)
                          ->header('Content-Disposition', "attachment; filename=\"{$filename}\"")
                          ->header('Content-Type', 'application/json');

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => '參數驗證失敗',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => '匯出失敗：' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * 匯入權限資料
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function import(Request $request): JsonResponse
    {
        // 檢查權限
        Gate::authorize('permissions.import');

        try {
            // 驗證請求
            $validated = $request->validate([
                'file' => 'required|file|mimes:json|max:10240', // 最大 10MB
                'conflict_resolution' => 'nullable|string|in:skip,update,merge',
                'validate_dependencies' => 'nullable|boolean',
                'create_missing_dependencies' => 'nullable|boolean',
                'dry_run' => 'nullable|boolean',
            ]);

            // 讀取並解析檔案
            $file = $request->file('file');
            $content = file_get_contents($file->getRealPath());
            $importData = json_decode($content, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                return response()->json([
                    'success' => false,
                    'message' => 'JSON 檔案格式錯誤：' . json_last_error_msg(),
                ], 422);
            }

            // 準備匯入選項
            $options = [
                'conflict_resolution' => $validated['conflict_resolution'] ?? 'skip',
                'validate_dependencies' => $validated['validate_dependencies'] ?? true,
                'create_missing_dependencies' => $validated['create_missing_dependencies'] ?? false,
                'dry_run' => $validated['dry_run'] ?? false,
            ];

            // 執行匯入
            $results = $this->importExportService->importPermissions($importData, $options);

            // 生成報告
            $report = $this->importExportService->generateImportReport($results);

            // 記錄匯入操作
            $this->auditService->logDataAccess('permissions', 'import', [
                'filename' => $file->getClientOriginalName(),
                'total_processed' => $report['summary']['total_processed'],
                'created' => $report['summary']['created'],
                'updated' => $report['summary']['updated'],
                'skipped' => $report['summary']['skipped'],
                'errors' => $report['summary']['errors'],
                'dry_run' => $options['dry_run'],
            ]);

            return response()->json([
                'success' => $results['success'],
                'message' => $results['success'] ? '匯入完成' : '匯入完成但有錯誤',
                'results' => $results,
                'report' => $report,
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => '參數驗證失敗',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => '匯入失敗：' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * 預覽匯入資料
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function preview(Request $request): JsonResponse
    {
        // 檢查權限
        Gate::authorize('permissions.import');

        try {
            // 驗證請求
            $validated = $request->validate([
                'file' => 'required|file|mimes:json|max:10240',
                'conflict_resolution' => 'nullable|string|in:skip,update,merge',
                'validate_dependencies' => 'nullable|boolean',
            ]);

            // 讀取並解析檔案
            $file = $request->file('file');
            $content = file_get_contents($file->getRealPath());
            $importData = json_decode($content, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                return response()->json([
                    'success' => false,
                    'message' => 'JSON 檔案格式錯誤：' . json_last_error_msg(),
                ], 422);
            }

            // 準備預覽選項（強制試運行）
            $options = [
                'conflict_resolution' => $validated['conflict_resolution'] ?? 'skip',
                'validate_dependencies' => $validated['validate_dependencies'] ?? true,
                'create_missing_dependencies' => false,
                'dry_run' => true,
            ];

            // 執行預覽
            $results = $this->importExportService->importPermissions($importData, $options);

            return response()->json([
                'success' => true,
                'message' => '預覽生成成功',
                'preview' => [
                    'metadata' => $importData['metadata'] ?? [],
                    'summary' => [
                        'total_permissions' => count($importData['permissions'] ?? []),
                        'will_create' => $results['created'],
                        'will_update' => $results['updated'],
                        'will_skip' => $results['skipped'],
                        'has_errors' => !empty($results['errors']),
                        'has_conflicts' => !empty($results['conflicts']),
                    ],
                    'conflicts' => $results['conflicts'],
                    'errors' => $results['errors'],
                    'warnings' => $results['warnings'],
                ],
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => '參數驗證失敗',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => '預覽失敗：' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * 取得匯入匯出統計
     * 
     * @return JsonResponse
     */
    public function stats(): JsonResponse
    {
        try {
            // 檢查權限
            Gate::authorize('permissions.view');

            // 取得統計資料
            $stats = [
                'total_permissions' => \App\Models\Permission::count(),
                'available_modules' => \App\Models\Permission::distinct()->pluck('module')->toArray(),
                'available_types' => \App\Models\Permission::distinct()->pluck('type')->toArray(),
                'recent_exports' => $this->getRecentExports(),
                'recent_imports' => $this->getRecentImports(),
            ];

            return response()->json([
                'success' => true,
                'stats' => $stats,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => '取得統計資料失敗：' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * 取得最近的匯出記錄
     * 
     * @return array
     */
    protected function getRecentExports(): array
    {
        // 這裡可以從審計日誌取得最近的匯出記錄
        // 目前返回空陣列，實際實作時可以查詢 activity_log 表
        return [];
    }

    /**
     * 取得最近的匯入記錄
     * 
     * @return array
     */
    protected function getRecentImports(): array
    {
        // 這裡可以從審計日誌取得最近的匯入記錄
        // 目前返回空陣列，實際實作時可以查詢 activity_log 表
        return [];
    }
}