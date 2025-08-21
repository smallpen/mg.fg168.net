<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

/**
 * 角色管理控制器
 * 
 * 負責處理角色管理相關的 HTTP 請求，包括：
 * - 角色列表顯示
 * - 角色建立表單
 * - 角色編輯表單
 * - 角色統計資訊
 * - 權限矩陣管理
 */
class RoleController extends Controller
{
    /**
     * 顯示角色列表頁面
     * 
     * @return View
     */
    public function index(): View
    {
        // 檢查檢視角色權限
        $this->authorize('roles.view');
        
        return view('admin.roles.index');
    }
    
    /**
     * 顯示角色建立表單
     * 
     * @return View
     */
    public function create(): View
    {
        // 檢查建立角色權限
        $this->authorize('roles.create');
        
        return view('admin.roles.create');
    }
    
    /**
     * 顯示角色編輯表單
     * 
     * @param Role $role
     * @return View
     */
    public function edit(Role $role): View
    {
        // 檢查編輯角色權限
        $this->authorize('roles.edit');
        
        // 檢查是否為系統角色
        if ($role->is_system_role && !auth()->user()->hasRole('super_admin')) {
            abort(403, '無法編輯系統預設角色');
        }
        
        return view('admin.roles.edit', compact('role'));
    }
    
    /**
     * 顯示角色詳情頁面
     * 
     * @param Role $role
     * @return View
     */
    public function show(Role $role): View
    {
        // 檢查檢視角色權限
        $this->authorize('roles.view');
        
        // 載入相關資料
        $role->load(['permissions', 'users']);
        
        return view('admin.roles.show', compact('role'));
    }
    
    /**
     * 顯示角色統計頁面
     * 
     * @return View
     */
    public function statistics(): View
    {
        // 檢查檢視角色權限
        $this->authorize('roles.view');
        
        return view('admin.roles.statistics');
    }
    
    /**
     * 顯示特定角色的統計資訊
     * 
     * @param Role $role
     * @return View
     */
    public function roleStatistics(Role $role): View
    {
        // 檢查檢視角色權限
        $this->authorize('roles.view');
        
        return view('admin.roles.role-statistics', compact('role'));
    }
    
    /**
     * 顯示權限矩陣頁面
     * 
     * @param Role|null $role
     * @return View
     */
    public function permissionMatrix(Role $role = null): View
    {
        // 檢查編輯角色權限
        $this->authorize('roles.edit');
        
        return view('admin.roles.permission-matrix', compact('role'));
    }
    
    /**
     * 複製角色
     * 
     * @param Role $role
     * @return RedirectResponse
     */
    public function duplicate(Role $role): RedirectResponse
    {
        // 檢查建立角色權限
        $this->authorize('roles.create');
        
        // 檢查是否為系統角色
        if ($role->is_system_role) {
            return redirect()->back()->with('error', '無法複製系統預設角色');
        }
        
        try {
            // 建立角色副本
            $newRole = $role->replicate();
            $newRole->name = $role->name . '_copy_' . time();
            $newRole->display_name = $role->display_name . ' (副本)';
            $newRole->is_system_role = false;
            $newRole->save();
            
            // 複製權限
            $newRole->permissions()->sync($role->permissions->pluck('id'));
            
            return redirect()->route('admin.roles.edit', $newRole)
                           ->with('success', '角色複製成功');
                           
        } catch (\Exception $e) {
            return redirect()->back()->with('error', '角色複製失敗：' . $e->getMessage());
        }
    }
    
    /**
     * 匯出角色配置
     * 
     * @param Role $role
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function export(Role $role)
    {
        // 檢查檢視角色權限
        $this->authorize('roles.view');
        
        $roleData = [
            'name' => $role->name,
            'display_name' => $role->display_name,
            'description' => $role->description,
            'permissions' => $role->permissions->pluck('name')->toArray(),
            'created_at' => $role->created_at->toISOString(),
            'updated_at' => $role->updated_at->toISOString(),
        ];
        
        $filename = 'role_' . $role->name . '_' . date('Y-m-d_H-i-s') . '.json';
        
        return response()->json($roleData)
                         ->header('Content-Disposition', 'attachment; filename="' . $filename . '"');
    }
    
    /**
     * 批量操作處理
     * 
     * @param Request $request
     * @return RedirectResponse
     */
    public function bulkAction(Request $request): RedirectResponse
    {
        $request->validate([
            'action' => 'required|in:delete,activate,deactivate,export',
            'role_ids' => 'required|array|min:1',
            'role_ids.*' => 'exists:roles,id'
        ]);
        
        $action = $request->input('action');
        $roleIds = $request->input('role_ids');
        
        // 檢查權限
        switch ($action) {
            case 'delete':
                $this->authorize('roles.delete');
                break;
            case 'activate':
            case 'deactivate':
                $this->authorize('roles.edit');
                break;
            case 'export':
                $this->authorize('roles.view');
                break;
        }
        
        try {
            $roles = Role::whereIn('id', $roleIds)->get();
            
            // 檢查是否包含系統角色
            $systemRoles = $roles->where('is_system_role', true);
            if ($systemRoles->isNotEmpty() && in_array($action, ['delete', 'deactivate'])) {
                return redirect()->back()->with('error', '無法對系統預設角色執行此操作');
            }
            
            switch ($action) {
                case 'delete':
                    // 檢查角色是否有使用者關聯
                    $rolesWithUsers = $roles->filter(function ($role) {
                        return $role->users()->count() > 0;
                    });
                    
                    if ($rolesWithUsers->isNotEmpty()) {
                        $roleNames = $rolesWithUsers->pluck('display_name')->join(', ');
                        return redirect()->back()->with('error', "以下角色仍有使用者關聯，無法刪除：{$roleNames}");
                    }
                    
                    foreach ($roles as $role) {
                        $role->delete();
                    }
                    
                    return redirect()->back()->with('success', "成功刪除 {$roles->count()} 個角色");
                    
                case 'activate':
                    Role::whereIn('id', $roleIds)->update(['is_active' => true]);
                    return redirect()->back()->with('success', "成功啟用 {$roles->count()} 個角色");
                    
                case 'deactivate':
                    Role::whereIn('id', $roleIds)->update(['is_active' => false]);
                    return redirect()->back()->with('success', "成功停用 {$roles->count()} 個角色");
                    
                case 'export':
                    // 匯出多個角色的配置
                    $exportData = $roles->map(function ($role) {
                        return [
                            'name' => $role->name,
                            'display_name' => $role->display_name,
                            'description' => $role->description,
                            'permissions' => $role->permissions->pluck('name')->toArray(),
                        ];
                    });
                    
                    $filename = 'roles_export_' . date('Y-m-d_H-i-s') . '.json';
                    
                    return response()->json($exportData->toArray())
                                   ->header('Content-Disposition', 'attachment; filename="' . $filename . '"');
            }
            
        } catch (\Exception $e) {
            return redirect()->back()->with('error', '批量操作失敗：' . $e->getMessage());
        }
        
        return redirect()->back();
    }
}