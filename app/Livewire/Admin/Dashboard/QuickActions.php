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
     * 渲染元件
     * 
     * @return \Illuminate\View\View
     */
    public function render()
    {
        return view('livewire.admin.dashboard.quick-actions');
    }
}