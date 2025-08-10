<?php

namespace App\Livewire\Admin\Dashboard;

use Livewire\Component;

/**
 * 快速操作元件
 * 
 * 顯示常用功能連結
 */
class QuickActions extends Component
{
    /**
     * 取得快速操作項目
     * 
     * @return array
     */
    public function getQuickActionsProperty(): array
    {
        $actions = [];

        // 基本的快速操作
        if (auth()->user()->hasPermission('users.create')) {
            $actions[] = [
                'title' => '建立使用者',
                'description' => '新增系統使用者帳號',
                'icon' => 'user-plus',
                'color' => 'primary',
                'route' => 'admin.users.create',
            ];
        }

        if (auth()->user()->hasPermission('users.view')) {
            $actions[] = [
                'title' => '管理使用者',
                'description' => '檢視和管理系統使用者',
                'icon' => 'users',
                'color' => 'info',
                'route' => 'admin.users.index',
            ];
        }

        if (auth()->user()->hasPermission('roles.create')) {
            $actions[] = [
                'title' => '建立角色',
                'description' => '新增系統角色',
                'icon' => 'shield-plus',
                'color' => 'success',
                'route' => 'admin.roles.create',
            ];
        }

        if (auth()->user()->hasPermission('roles.view')) {
            $actions[] = [
                'title' => '管理角色',
                'description' => '檢視和管理系統角色',
                'icon' => 'shield-check',
                'color' => 'warning',
                'route' => 'admin.roles.index',
            ];
        }

        return array_slice($actions, 0, 6); // 最多顯示 6 個
    }

    /**
     * 處理快速操作點擊
     * 
     * @param string $route
     * @return void
     */
    public function handleAction(string $route)
    {
        // 記錄活動
        if (auth()->check()) {
            // 找到對應的操作標題
            $action = collect($this->quickActions)->firstWhere('route', $route);
            $actionTitle = $action['title'] ?? $route;

            // 這裡可以加入活動記錄邏輯
            // app(\App\Services\ActivityService::class)->logQuickAction(
            //     auth()->user(),
            //     $route,
            //     $actionTitle
            // );
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
        // 觸發重新渲染
        $this->dispatch('actions-refreshed', [
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