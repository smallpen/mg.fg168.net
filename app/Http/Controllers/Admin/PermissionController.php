<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use App\Services\AuditLogService;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Support\Facades\Log;

/**
 * 權限管理控制器
 * 
 * 負責處理權限管理相關的 HTTP 請求，包括：
 * - 權限列表顯示
 * - 權限建立表單
 * - 權限編輯表單
 * - 權限矩陣管理
 * - 權限依賴關係管理
 */
class PermissionController extends Controller
{
    protected AuditLogService $auditService;

    public function __construct(AuditLogService $auditService)
    {
        $this->auditService = $auditService;
    }

    /**
     * 顯示權限列表頁面
     * 
     * @param Request $request
     * @return View
     */
    public function index(Request $request): View
    {
        // 檢查檢視權限權限
        $this->authorize('permissions.view');
        
        // 記錄存取日誌
        $this->auditService->logDataAccess('permissions', 'view_list', [
            'user_id' => auth()->id(),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);
        
        // 準備麵包屑導航
        $breadcrumbs = [
            ['name' => __('admin.navigation.dashboard'), 'url' => route('admin.dashboard')],
            ['name' => __('admin.permissions.title', ['default' => '權限管理']), 'url' => null],
        ];
        
        return view('admin.permissions.index', compact('breadcrumbs'));
    }
    
    /**
     * 顯示權限建立表單
     * 
     * @param Request $request
     * @return View
     */
    public function create(Request $request): View
    {
        // 檢查建立權限權限
        $this->authorize('permissions.create');
        
        // 記錄存取日誌
        $this->auditService->logDataAccess('permissions', 'view_create_form', [
            'user_id' => auth()->id(),
            'ip' => $request->ip(),
        ]);
        
        // 準備麵包屑導航
        $breadcrumbs = [
            ['name' => __('admin.navigation.dashboard'), 'url' => route('admin.dashboard')],
            ['name' => __('admin.permissions.title', ['default' => '權限管理']), 'url' => route('admin.permissions.index')],
            ['name' => __('admin.permissions.create', ['default' => '建立權限']), 'url' => null],
        ];
        
        return view('admin.permissions.create', compact('breadcrumbs'));
    }
    
    /**
     * 顯示權限編輯表單
     * 
     * @param Request $request
     * @param Permission $permission
     * @return View
     */
    public function edit(Request $request, Permission $permission): View
    {
        // 檢查編輯權限權限
        $this->authorize('permissions.edit');
        
        // 檢查是否為系統權限
        if ($permission->is_system_permission) {
            Log::warning('嘗試編輯系統權限', [
                'permission_id' => $permission->id,
                'permission_name' => $permission->name,
                'user_id' => auth()->id(),
            ]);
        }
        
        // 記錄存取日誌
        $this->auditService->logDataAccess('permissions', 'view_edit_form', [
            'permission_id' => $permission->id,
            'permission_name' => $permission->name,
            'user_id' => auth()->id(),
            'ip' => $request->ip(),
        ]);
        
        // 準備麵包屑導航
        $breadcrumbs = [
            ['name' => __('admin.navigation.dashboard'), 'url' => route('admin.dashboard')],
            ['name' => __('admin.permissions.title', ['default' => '權限管理']), 'url' => route('admin.permissions.index')],
            ['name' => __('admin.permissions.edit', ['default' => '編輯權限']) . ': ' . $permission->display_name, 'url' => null],
        ];
        
        return view('admin.permissions.edit', compact('permission', 'breadcrumbs'));
    }
    
    /**
     * 顯示權限矩陣頁面
     * 
     * @param Request $request
     * @return View
     */
    public function matrix(Request $request): View
    {
        // 檢查編輯角色權限
        $this->authorize('roles.edit');
        
        // 記錄存取日誌
        $this->auditService->logDataAccess('permissions', 'view_matrix', [
            'user_id' => auth()->id(),
            'ip' => $request->ip(),
        ]);
        
        // 準備麵包屑導航
        $breadcrumbs = [
            ['name' => __('admin.navigation.dashboard'), 'url' => route('admin.dashboard')],
            ['name' => __('admin.permissions.title', ['default' => '權限管理']), 'url' => route('admin.permissions.index')],
            ['name' => __('admin.permissions.matrix', ['default' => '權限矩陣']), 'url' => null],
        ];
        
        return view('admin.permissions.matrix', compact('breadcrumbs'));
    }
    
    /**
     * 顯示權限依賴關係圖表頁面
     * 
     * @param Request $request
     * @return View
     */
    public function dependencies(Request $request): View
    {
        // 檢查檢視權限權限
        $this->authorize('permissions.view');
        
        // 取得選中的權限 ID（如果有的話）
        $selectedPermissionId = $request->get('permission_id');
        $selectedPermission = null;
        
        if ($selectedPermissionId) {
            $selectedPermission = Permission::find($selectedPermissionId);
            if (!$selectedPermission) {
                Log::warning('嘗試檢視不存在的權限依賴關係', [
                    'permission_id' => $selectedPermissionId,
                    'user_id' => auth()->id(),
                ]);
            }
        }
        
        // 記錄存取日誌
        $this->auditService->logDataAccess('permissions', 'view_dependencies', [
            'selected_permission_id' => $selectedPermissionId,
            'user_id' => auth()->id(),
            'ip' => $request->ip(),
        ]);
        
        // 準備麵包屑導航
        $breadcrumbs = [
            ['name' => __('admin.navigation.dashboard'), 'url' => route('admin.dashboard')],
            ['name' => __('admin.permissions.title', ['default' => '權限管理']), 'url' => route('admin.permissions.index')],
            ['name' => '權限依賴關係圖表', 'url' => null],
        ];
        
        return view('admin.permissions.dependencies', compact('selectedPermissionId', 'selectedPermission', 'breadcrumbs'));
    }
    
    /**
     * 顯示權限詳情頁面
     * 
     * @param Request $request
     * @param Permission $permission
     * @return View
     */
    public function show(Request $request, Permission $permission): View
    {
        // 檢查檢視權限權限
        $this->authorize('permissions.view');
        
        // 記錄存取日誌
        $this->auditService->logDataAccess('permissions', 'view_detail', [
            'permission_id' => $permission->id,
            'permission_name' => $permission->name,
            'user_id' => auth()->id(),
            'ip' => $request->ip(),
        ]);
        
        // 準備麵包屑導航
        $breadcrumbs = [
            ['name' => __('admin.navigation.dashboard'), 'url' => route('admin.dashboard')],
            ['name' => __('admin.permissions.title', ['default' => '權限管理']), 'url' => route('admin.permissions.index')],
            ['name' => $permission->display_name, 'url' => null],
        ];
        
        return view('admin.permissions.show', compact('permission', 'breadcrumbs'));
    }
}