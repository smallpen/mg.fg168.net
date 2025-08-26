<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Repositories\Contracts\ActivityRepositoryInterface;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * 管理後台活動記錄控制器
 * 
 * 提供活動記錄的管理介面，包含列表、詳情、統計、監控等功能
 */
class ActivityController extends Controller
{
    public function __construct(
        private ActivityRepositoryInterface $activityRepository,
        private ActivityLogger $activityLogger
    ) {
        // 所有活動記錄操作都需要相應權限
        $this->middleware('can:activity_logs.view')->except(['export', 'download']);
        $this->middleware('can:activity_logs.export')->only(['export', 'download']);
    }

    /**
     * 活動記錄主頁面
     * 
     * @return View
     */
    public function index(): View
    {
        // 記錄管理員存取活動記錄頁面
        $this->activityLogger->logUserAction('view_activity_logs', null, [
            'page' => 'index',
            'ip' => request()->ip()
        ]);

        return view('admin.activities.index');
    }

    /**
     * 活動記錄詳情頁面
     * 
     * @param int $id
     * @return View
     */
    public function show(int $id): View
    {
        $activity = $this->activityRepository->getActivityById($id);

        if (!$activity) {
            abort(404, '找不到指定的活動記錄');
        }

        // 記錄查看活動詳情
        $this->activityLogger->logUserAction('view_activity_detail', $activity, [
            'activity_id' => $id
        ]);

        return view('admin.activities.show', compact('activity'));
    }

    /**
     * 安全事件監控頁面
     * 
     * @return View
     */
    public function security(): View
    {
        $this->authorize('system.logs');

        // 記錄存取安全監控頁面
        $this->activityLogger->logSecurityEvent('security_monitor_access', '管理員存取安全事件監控頁面', [
            'user_id' => auth()->id(),
            'ip' => request()->ip()
        ]);

        return view('admin.activities.security');
    }

    /**
     * 活動統計頁面
     * 
     * @return View
     */
    public function stats(): View
    {
        $this->authorize('system.logs');

        // 記錄存取統計頁面
        $this->activityLogger->logUserAction('view_activity_stats', null, [
            'page' => 'stats'
        ]);

        return view('admin.activities.stats');
    }

    /**
     * 即時監控頁面
     * 
     * @return View
     */
    public function monitor(): View
    {
        $this->authorize('system.logs');

        // 記錄存取即時監控頁面
        $this->activityLogger->logUserAction('view_activity_monitor', null, [
            'page' => 'monitor'
        ]);

        return view('admin.activities.monitor');
    }

    /**
     * 活動記錄匯出頁面
     * 
     * @return View
     */
    public function export(): View
    {
        // 記錄存取匯出頁面
        $this->activityLogger->logUserAction('view_activity_export', null, [
            'page' => 'export'
        ]);

        return view('admin.activities.export');
    }

    /**
     * 自訂報告頁面
     * 
     * @return View
     */
    public function customReport(): View
    {
        // 記錄存取自訂報告頁面
        $this->activityLogger->logUserAction('view_custom_report', null, [
            'page' => 'custom-report'
        ]);

        return view('admin.activities.custom-report');
    }

    /**
     * 下載匯出檔案
     * 
     * @param string $filename
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function downloadExport(string $filename)
    {
        $path = storage_path('app/exports/activities/' . $filename);
        
        if (!file_exists($path)) {
            abort(404, '檔案不存在或已過期');
        }

        // 記錄檔案下載
        $this->activityLogger->logUserAction('download_activity_export', null, [
            'filename' => $filename,
            'file_size' => filesize($path)
        ]);

        return response()->download($path)->deleteFileAfterSend();
    }

    /**
     * 活動記錄搜尋 API（供前端 AJAX 使用）
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function search(Request $request)
    {
        $request->validate([
            'query' => 'required|string|min:1|max:255',
            'filters' => 'array',
            'page' => 'integer|min:1',
            'per_page' => 'integer|min:1|max:100'
        ]);

        $query = $request->get('query');
        $filters = $request->get('filters', []);
        $perPage = $request->get('per_page', 50);

        $activities = $this->activityRepository->searchActivities($query, $filters);
        $paginatedActivities = $this->activityRepository->getPaginatedActivities(
            array_merge($filters, ['search' => $query]), 
            $perPage
        );

        // 記錄搜尋操作
        $this->activityLogger->logUserAction('search_activities', null, [
            'query' => $query,
            'filters' => $filters,
            'results_count' => $paginatedActivities->total()
        ]);

        return response()->json([
            'success' => true,
            'data' => $paginatedActivities->items(),
            'pagination' => [
                'current_page' => $paginatedActivities->currentPage(),
                'last_page' => $paginatedActivities->lastPage(),
                'per_page' => $paginatedActivities->perPage(),
                'total' => $paginatedActivities->total(),
            ],
            'meta' => [
                'query' => $query,
                'filters' => $filters
            ]
        ]);
    }

    /**
     * 批量操作處理
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function bulkAction(Request $request)
    {
        $this->authorize('activity_logs.delete');

        $request->validate([
            'action' => 'required|string|in:delete,archive,export',
            'activity_ids' => 'required|array|min:1',
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
                    return response()->json(['error' => '不支援的操作'], 400);
            }

            // 記錄批量操作
            $this->activityLogger->logUserAction('bulk_activity_action', null, [
                'action' => $action,
                'activity_ids' => $activityIds,
                'count' => count($activityIds),
                'result' => $message
            ]);

            $response = ['success' => true, 'message' => $message];
            
            if (isset($exportPath)) {
                $response['download_url'] = route('admin.activities.download-export', $exportPath);
            }

            return response()->json($response);

        } catch (\Exception $e) {
            // 記錄操作失敗
            $this->activityLogger->logUserAction('bulk_activity_action_failed', null, [
                'action' => $action,
                'activity_ids' => $activityIds,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'error' => '批量操作失敗',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}