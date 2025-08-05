<?php

namespace App\Http\Livewire\Admin\Dashboard;

use App\Http\Livewire\Admin\AdminComponent;
use App\Services\PermissionService;

/**
 * 快速操作元件
 * 
 * 顯示常用功能連結，根據使用者權限動態調整顯示內容
 */
class QuickActions extends AdminComponent
{
    /**
     * 權限服務
     * 
     * @var PermissionService
     */
    protected $permissionService;

    /**
     * 元件掛載時執行
     * 
     * @return void
     */
    public function mount()
    {
        $this->permissionService = app(PermissionService::class);
    }

    /**
     * 取得快速操作項目
     * 
     * @return array
     */
    public function getQuickActionsProperty(): array
    {
        $user = auth()->user();
        $actions = [];

        // 使用者管理相關操作
        if ($this->permissionService->canAccessModule($user, 'users')) {
            if ($this->permissionService->hasPermission($user, 'users.create')) {
                $actions[] = [
                    'title' => '建立使用者',
                    'description' => '新增系統使用者帳號',
                    'icon' => 'user-plus',
                    'color' => 'primary',
                    'route' => 'admin.users.create',
                    'permission' => 'users.create',
                    'category' => 'users'
                ];
            }

            if ($this->permissionService->hasPermission($user, 'users.view')) {
                $actions[] = [
                    'title' => '管理使用者',
                    'description' => '檢視和管理系統使用者',
                    'icon' => 'users',
                    'color' => 'info',
                    'route' => 'admin.users.index',
                    'permission' => 'users.view',
                    'category' => 'users'
                ];
            }
        }

        // 角色權限管理相關操作
        if ($this->permissionService->canAccessModule($user, 'roles')) {
            if ($this->permissionService->hasPermission($user, 'roles.create')) {
                $actions[] = [
                    'title' => '建立角色',
                    'description' => '新增系統角色',
                    'icon' => 'shield-plus',
                    'color' => 'success',
                    'route' => 'admin.roles.create',
                    'permission' => 'roles.create',
                    'category' => 'roles'
                ];
            }

            if ($this->permissionService->hasPermission($user, 'roles.view')) {
                $actions[] = [
                    'title' => '管理角色',
                    'description' => '檢視和管理系統角色',
                    'icon' => 'shield-check',
                    'color' => 'warning',
                    'route' => 'admin.roles.index',
                    'permission' => 'roles.view',
                    'category' => 'roles'
                ];
            }

            if ($this->permissionService->hasPermission($user, 'permissions.manage')) {
                $actions[] = [
                    'title' => '權限管理',
                    'description' => '管理角色權限設定',
                    'icon' => 'key',
                    'color' => 'purple',
                    'route' => 'admin.permissions.index',
                    'permission' => 'permissions.manage',
                    'category' => 'permissions'
                ];
            }
        }

        // 系統管理相關操作
        if ($this->permissionService->hasPermission($user, 'system.settings')) {
            $actions[] = [
                'title' => '系統設定',
                'description' => '管理系統配置和設定',
                'icon' => 'cog',
                'color' => 'gray',
                'route' => 'admin.settings.index',
                'permission' => 'system.settings',
                'category' => 'system'
            ];
        }

        if ($this->permissionService->hasPermission($user, 'system.logs')) {
            $actions[] = [
                'title' => '活動記錄',
                'description' => '檢視系統活動記錄',
                'icon' => 'clipboard-list',
                'color' => 'indigo',
                'route' => 'admin.activities.index',
                'permission' => 'system.logs',
                'category' => 'system'
            ];
        }

        // 資料管理相關操作
        if ($this->permissionService->hasPermission($user, 'data.export')) {
            $actions[] = [
                'title' => '匯出資料',
                'description' => '匯出系統資料',
                'icon' => 'download',
                'color' => 'green',
                'route' => 'admin.export.index',
                'permission' => 'data.export',
                'category' => 'data'
            ];
        }

        if ($this->permissionService->hasPermission($user, 'data.backup')) {
            $actions[] = [
                'title' => '資料備份',
                'description' => '建立系統資料備份',
                'icon' => 'database',
                'color' => 'blue',
                'route' => 'admin.backup.index',
                'permission' => 'data.backup',
                'category' => 'data'
            ];
        }

        // 根據權限過濾並限制顯示數量
        return collect($actions)
            ->filter(function ($action) use ($user) {
                return $this->permissionService->hasPermission($user, $action['permission']);
            })
            ->take(8) // 最多顯示 8 個快速操作
            ->values()
            ->toArray();
    }

    /**
     * 取得按類別分組的快速操作
     * 
     * @return array
     */
    public function getGroupedActionsProperty(): array
    {
        return collect($this->quickActions)
            ->groupBy('category')
            ->map(function ($actions, $category) {
                return [
                    'category' => $category,
                    'title' => $this->getCategoryTitle($category),
                    'actions' => $actions->toArray()
                ];
            })
            ->values()
            ->toArray();
    }

    /**
     * 取得類別標題
     * 
     * @param string $category
     * @return string
     */
    protected function getCategoryTitle(string $category): string
    {
        return match ($category) {
            'users' => '使用者管理',
            'roles' => '角色管理',
            'permissions' => '權限管理',
            'system' => '系統管理',
            'data' => '資料管理',
            default => '其他'
        };
    }

    /**
     * 處理快速操作點擊
     * 
     * @param string $route
     * @return void
     */
    public function handleAction(string $route)
    {
        // 找到對應的操作標題
        $action = collect($this->quickActions)->firstWhere('route', $route);
        $actionTitle = $action['title'] ?? $route;

        // 記錄活動
        if (auth()->check()) {
            app(\App\Services\ActivityService::class)->logQuickAction(
                auth()->user(),
                $route,
                $actionTitle
            );
        }

        // 重新導向到指定路由
        return redirect()->route($route);
    }

    /**
     * 重新整理快速操作
     * 
     * @return void
     */
    public function refresh()
    {
        // 清除權限快取
        $this->permissionService->clearUserPermissionCache(auth()->user());
        
        $this->dispatchBrowserEvent('actions-refreshed', [
            'message' => '快速操作已更新'
        ]);
    }

    /**
     * 渲染元件
     * 
     * @return \Illuminate\View\View
     */
    public function render()
    {
        return view('livewire.admin.dashboard.quick-actions');
    }
}