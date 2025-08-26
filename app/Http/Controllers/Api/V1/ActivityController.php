<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\ActivityIndexRequest;
use App\Http\Resources\ActivityResource;
use App\Http\Resources\ActivityCollection;
use App\Repositories\Contracts\ActivityRepositoryInterface;
use App\Services\ActivityLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * 活動記錄 API 控制器
 * 
 * 提供活動記錄的 RESTful API 介面，支援查詢、篩選、搜尋和匯出功能
 */
class ActivityController extends Controller
{
    public function __construct(
        private ActivityRepositoryInterface $activityRepository,
        private ActivityLogger $activityLogger
    ) {
        // 所有 API 操作都需要 activity_logs.view 權限
        $this->middleware('auth:sanctum');
        $this->middleware('can:activity_logs.view');
    }

    /**
     * 取得活動記錄列表
     * 
     * @param ActivityIndexRequest $request
     * @return ActivityCollection
     */
    public function index(ActivityIndexRequest $request): ActivityCollection
    {
        // 記錄 API 存取
        $this->activityLogger->logApiAccess('activities.index', [
            'filters' => $request->validated(),
            'user_agent' => $request->userAgent(),
            'ip' => $request->ip()
        ]);

        $filters = $request->validated();
        $perPage = min($request->get('per_page', 50), 100); // 限制最大每頁數量

        $activities = $this->activityRepository->getPaginatedActivities($filters, $perPage);

        return new ActivityCollection($activities);
    }

    /**
     * 取得特定活動記錄詳情
     * 
     * @param int $id
     * @return ActivityResource
     */
    public function show(int $id): ActivityResource
    {
        $activity = $this->activityRepository->getActivityById($id);

        if (!$activity) {
            abort(404, '找不到指定的活動記錄');
        }

        // 記錄 API 存取
        $this->activityLogger->logApiAccess('activities.show', [
            'activity_id' => $id,
            'ip' => request()->ip()
        ]);

        return new ActivityResource($activity);
    }

    /**
     * 搜尋活動記錄
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function search(Request $request): JsonResponse
    {
        $request->validate([
            'query' => 'required|string|min:2|max:255',
            'filters' => 'array',
            'limit' => 'integer|min:1|max:100'
        ]);

        $query = $request->get('query');
        $filters = $request->get('filters', []);
        $limit = $request->get('limit', 50);

        $activities = $this->activityRepository->searchActivities($query, $filters)
            ->take($limit);

        // 記錄搜尋操作
        $this->activityLogger->logApiAccess('activities.search', [
            'query' => $query,
            'filters' => $filters,
            'results_count' => $activities->count()
        ]);

        return response()->json([
            'data' => ActivityResource::collection($activities),
            'meta' => [
                'query' => $query,
                'total' => $activities->count(),
                'limit' => $limit
            ]
        ]);
    }

    /**
     * 取得活動統計資料
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function stats(Request $request): JsonResponse
    {
        $request->validate([
            'time_range' => 'string|in:1d,7d,30d,90d'
        ]);

        $timeRange = $request->get('time_range', '7d');
        $stats = $this->activityRepository->getActivityStats($timeRange);

        // 記錄統計查詢
        $this->activityLogger->logApiAccess('activities.stats', [
            'time_range' => $timeRange
        ]);

        return response()->json([
            'data' => $stats,
            'meta' => [
                'time_range' => $timeRange,
                'generated_at' => now()->toISOString()
            ]
        ]);
    }

    /**
     * 匯出活動記錄
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function export(Request $request): JsonResponse
    {
        $this->authorize('activity_logs.export');

        $request->validate([
            'format' => 'required|string|in:csv,json,pdf',
            'filters' => 'array',
            'date_from' => 'date',
            'date_to' => 'date|after_or_equal:date_from'
        ]);

        $format = $request->get('format');
        $filters = $request->get('filters', []);

        try {
            $exportPath = $this->activityRepository->exportActivities($filters, $format);

            // 記錄匯出操作
            $this->activityLogger->logApiAccess('activities.export', [
                'format' => $format,
                'filters' => $filters,
                'export_path' => $exportPath
            ]);

            return response()->json([
                'message' => '匯出完成',
                'download_url' => url("api/v1/activities/download/{$exportPath}"),
                'format' => $format,
                'expires_at' => now()->addHours(24)->toISOString()
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => '匯出失敗',
                'message' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * 下載匯出檔案
     * 
     * @param string $filename
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function download(string $filename)
    {
        $this->authorize('activity_logs.export');

        $filePath = storage_path("app/exports/{$filename}");

        if (!file_exists($filePath)) {
            abort(404, '檔案不存在或已過期');
        }

        // 記錄下載操作
        $this->activityLogger->logApiAccess('activities.download', [
            'filename' => $filename
        ]);

        return response()->download($filePath)->deleteFileAfterSend();
    }

    /**
     * 取得相關活動記錄
     * 
     * @param int $id
     * @return JsonResponse
     */
    public function related(int $id): JsonResponse
    {
        $activity = $this->activityRepository->getActivityById($id);

        if (!$activity) {
            abort(404, '找不到指定的活動記錄');
        }

        $relatedActivities = $this->activityRepository->getRelatedActivities($activity);

        return response()->json([
            'data' => ActivityResource::collection($relatedActivities),
            'meta' => [
                'activity_id' => $id,
                'total' => $relatedActivities->count()
            ]
        ]);
    }

    /**
     * 批量操作處理
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function bulkAction(Request $request): JsonResponse
    {
        $this->authorize('activity_logs.delete');

        $request->validate([
            'action' => 'required|string|in:delete,archive,export',
            'activity_ids' => 'required|array|min:1|max:1000', // API 限制最多 1000 筆
            'activity_ids.*' => 'integer|exists:activities,id'
        ]);

        $action = $request->get('action');
        $activityIds = $request->get('activity_ids');

        try {
            switch ($action) {
                case 'delete':
                    $count = $this->activityRepository->bulkDelete($activityIds);
                    $message = "成功刪除 {$count} 筆活動記錄";
                    break;
                    
                case 'archive':
                    $count = $this->activityRepository->bulkArchive($activityIds);
                    $message = "成功歸檔 {$count} 筆活動記錄";
                    break;
                    
                case 'export':
                    $exportPath = $this->activityRepository->bulkExport($activityIds);
                    $message = "成功匯出選定的活動記錄";
                    break;
                    
                default:
                    return response()->json([
                        'error' => 'Invalid Action',
                        'message' => '不支援的操作'
                    ], Response::HTTP_BAD_REQUEST);
            }

            // 記錄批量操作
            $this->activityLogger->logApiAccess('activities.bulk_action', [
                'action' => $action,
                'activity_ids' => $activityIds,
                'count' => count($activityIds),
                'result' => $message
            ]);

            $response = [
                'success' => true,
                'message' => $message,
                'data' => [
                    'action' => $action,
                    'processed_count' => count($activityIds),
                    'timestamp' => now()->toISOString()
                ]
            ];
            
            if (isset($exportPath)) {
                $response['data']['download_url'] = url("api/v1/activities/download/{$exportPath}");
                $response['data']['expires_at'] = now()->addHours(24)->toISOString();
            }

            return response()->json($response);

        } catch (\Exception $e) {
            // 記錄操作失敗
            $this->activityLogger->logApiAccess('activities.bulk_action_failed', [
                'action' => $action,
                'activity_ids' => $activityIds,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'error' => 'Bulk Action Failed',
                'message' => '批量操作失敗: ' . $e->getMessage(),
                'code' => 'BULK_ACTION_ERROR'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}