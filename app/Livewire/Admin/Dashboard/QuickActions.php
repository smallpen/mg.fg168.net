<?php

namespace App\Livewire\Admin\Dashboard;

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
                    'permission' => 'users.create'
                ];
            }

            if ($this->permissionService->hasPermission($user, 'users.index')) {
                $actions[] = [
                    'title' => '管理使用者',
                    'description' => '檢視和管理所有使用者',
                    'icon' => 'users',
                    'color' => 'info',
                    'route' => 'admin.users.index',
                    'permission' => 'users.index'
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
                    'permission' => 'roles.create'
                ];
            }

            if ($this->permissionService->hasPermission($user, 'roles.index')) {
                $actions[] = [
                    'title' => '管理角色',
                    'description' => '檢視和管理系統角色',
                    'icon' => 'shield-check',
                    'color' => 'warning',
                    'route' => 'admin.roles.index',
                    'permission' => 'roles.index'
                ];
            }
        }

        // 權限管理相關操作
        if ($this->permissionService->canAccessModule($user, 'permissions')) {
            if ($this->permissionService->hasPermission($user, 'permissions.index')) {
                $actions[] = [
                    'title' => '權限矩陣',
                    'description' => '管理角色權限設定',
                    'icon' => 'lock-closed',
                    'color' => 'purple',
                    'route' => 'admin.permissions.matrix',
                    'permission' => 'permissions.index'
                ];
            }
        }

        // 系統管理相關操作
        if ($this->permissionService->canAccessModule($user, 'system')) {
            if ($this->permissionService->hasPermission($user, 'system.settings')) {
                $actions[] = [
                    'title' => '系統設定',
                    'description' => '配置系統參數',
                    'icon' => 'cog',
                    'color' => 'gray',
                    'route' => 'admin.settings.index',
                    'permission' => 'system.settings'
                ];
            }

            if ($this->permissionService->hasPermission($user, 'system.logs')) {
                $actions[] = [
                    'title' => '活動記錄',
                    'description' => '檢視系統活動日誌',
                    'icon' => 'document-text',
                    'color' => 'indigo',
                    'route' => 'admin.activities.index',
                    'permission' => 'system.logs'
                ];
            }

            if ($this->permissionService->hasPermission($user, 'system.backup')) {
                $actions[] = [
                    'title' => '資料備份',
                    'description' => '執行系統資料備份',
                    'icon' => 'cloud-download',
                    'color' => 'green',
                    'route' => 'admin.backup.index',
                    'permission' => 'system.backup'
                ];
            }
        }

        // 報表相關操作
        if ($this->permissionService->canAccessModule($user, 'reports')) {
            if ($this->permissionService->hasPermission($user, 'reports.users')) {
                $actions[] = [
                    'title' => '使用者報表',
                    'description' => '產生使用者統計報表',
                    'icon' => 'chart-bar',
                    'color' => 'blue',
                    'route' => 'admin.reports.users',
                    'permission' => 'reports.users'
                ];
            }

            if ($this->permissionService->hasPermission($user, 'reports.activities')) {
                $actions[] = [
                    'title' => '活動報表',
                    'description' => '產生活動統計報表',
                    'icon' => 'chart-pie',
                    'color' => 'pink',
                    'route' => 'admin.reports.activities',
                    'permission' => 'reports.activities'
                ];
            }
        }

        // 限制顯示數量，避免介面過於擁擠
        return array_slice($actions, 0, 8);
    }

    /**
     * 取得常用工具
     * 
     * @return array
     */
    public function getUtilityActionsProperty(): array
    {
        $user = auth()->user();
        $utilities = [];

        // 快取管理
        if ($this->permissionService->hasPermission($user, 'system.cache')) {
            $utilities[] = [
                'title' => '清除快取',
                'description' => '清除系統快取',
                'icon' => 'refresh',
                'color' => 'orange',
                'action' => 'clearCache',
                'confirm' => true,
                'permission' => 'system.cache'
            ];
        }

        // 匯出資料
        if ($this->permissionService->hasPermission($user, 'system.export')) {
            $utilities[] = [
                'title' => '匯出使用者',
                'description' => '匯出使用者資料',
                'icon' => 'download',
                'color' => 'teal',
                'action' => 'exportUsers',
                'permission' => 'system.export'
            ];
        }

        // 系統健康檢查
        if ($this->permissionService->hasPermission($user, 'system.health')) {
            $utilities[] = [
                'title' => '健康檢查',
                'description' => '檢查系統狀態',
                'icon' => 'heart',
                'color' => 'red',
                'action' => 'healthCheck',
                'permission' => 'system.health'
            ];
        }

        return $utilities;
    }

    /**
     * 清除快取
     * 
     * @return void
     */
    public function clearCache()
    {
        try {
            \Artisan::call('cache:clear');
            \Artisan::call('config:clear');
            \Artisan::call('route:clear');
            \Artisan::call('view:clear');

            $this->dispatchBrowserEvent('show-message', [
                'type' => 'success',
                'message' => '快取已成功清除'
            ]);

            // 記錄活動
            app(\App\Services\ActivityService::class)->log(
                'clear_cache',
                '清除系統快取',
                [
                    'module' => 'system',
                    'user_id' => auth()->id(),
                ]
            );

        } catch (\Exception $e) {
            $this->dispatchBrowserEvent('show-message', [
                'type' => 'error',
                'message' => '清除快取失敗：' . $e->getMessage()
            ]);
        }
    }

    /**
     * 匯出使用者資料
     * 
     * @return void
     */
    public function exportUsers()
    {
        try {
            // 這裡可以整合匯出功能
            $this->dispatchBrowserEvent('show-message', [
                'type' => 'info',
                'message' => '使用者資料匯出功能開發中'
            ]);

            // 記錄活動
            app(\App\Services\ActivityService::class)->log(
                'export_users',
                '匯出使用者資料',
                [
                    'module' => 'users',
                    'user_id' => auth()->id(),
                ]
            );

        } catch (\Exception $e) {
            $this->dispatchBrowserEvent('show-message', [
                'type' => 'error',
                'message' => '匯出失敗：' . $e->getMessage()
            ]);
        }
    }

    /**
     * 系統健康檢查
     * 
     * @return void
     */
    public function healthCheck()
    {
        try {
            $dashboardService = app(\App\Services\DashboardService::class);
            $health = $dashboardService->getSystemHealth();

            $status = $health['overall'] === 'healthy' ? 'success' : 'warning';
            $message = $health['overall'] === 'healthy' 
                ? '系統狀態良好' 
                : '系統狀態需要注意';

            $this->dispatchBrowserEvent('show-message', [
                'type' => $status,
                'message' => $message
            ]);

            // 記錄活動
            app(\App\Services\ActivityService::class)->log(
                'health_check',
                '執行系統健康檢查',
                [
                    'module' => 'system',
                    'user_id' => auth()->id(),
                    'properties' => $health,
                ]
            );

        } catch (\Exception $e) {
            $this->dispatchBrowserEvent('show-message', [
                'type' => 'error',
                'message' => '健康檢查失敗：' . $e->getMessage()
            ]);
        }
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
