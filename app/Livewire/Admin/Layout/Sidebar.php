<?php

namespace App\Livewire\Admin\Layout;

use Livewire\Component;

/**
 * 側邊導航選單元件
 * 
 * 負責顯示管理後台的主要導航選單，包括：
 * - 動態選單項目生成
 * - 權限控制的選單顯示
 * - 當前頁面的選單高亮
 */
class Sidebar extends Component
{
    /**
     * 選單項目配置
     */
    protected array $menuItems = [
        [
            'name' => '儀表板',
            'route' => 'admin.dashboard',
            'icon' => 'dashboard',
            'permission' => null, // 所有已登入使用者都可存取
        ],
        [
            'name' => '使用者管理',
            'route' => 'admin.users.index',
            'icon' => 'users',
            'permission' => 'users.view',
            'children' => [
                [
                    'name' => '使用者列表',
                    'route' => 'admin.users.index',
                    'permission' => 'users.view',
                ],
                [
                    'name' => '新增使用者',
                    'route' => 'admin.users.create',
                    'permission' => 'users.create',
                ],
            ],
        ],
        [
            'name' => '角色管理',
            'route' => 'admin.roles.index',
            'icon' => 'shield',
            'permission' => 'roles.view',
            'children' => [
                [
                    'name' => '角色列表',
                    'route' => 'admin.roles.index',
                    'permission' => 'roles.view',
                ],
                [
                    'name' => '新增角色',
                    'route' => 'admin.roles.create',
                    'permission' => 'roles.create',
                ],
            ],
        ],
        [
            'name' => '權限管理',
            'route' => 'admin.permissions.index',
            'icon' => 'lock',
            'permission' => 'permissions.view',
        ],
        [
            'name' => '系統設定',
            'route' => 'admin.settings.index',
            'icon' => 'settings',
            'permission' => 'settings.manage',
        ],
    ];
    
    /**
     * 取得過濾後的選單項目（根據權限）
     */
    public function getFilteredMenuItems()
    {
        return collect($this->menuItems)->filter(function ($item) {
            return $this->hasPermission($item['permission']);
        })->map(function ($item) {
            // 如果有子選單，也需要過濾
            if (isset($item['children'])) {
                $item['children'] = collect($item['children'])->filter(function ($child) {
                    return $this->hasPermission($child['permission']);
                })->toArray();
            }
            return $item;
        })->toArray();
    }
    
    /**
     * 檢查使用者是否有指定權限
     */
    private function hasPermission(?string $permission): bool
    {
        if ($permission === null) {
            return true; // 無權限要求，所有人都可存取
        }
        
        return auth()->user()->hasPermission($permission);
    }
    
    /**
     * 檢查路由是否為當前活躍路由
     */
    public function isActiveRoute(string $route): bool
    {
        return request()->routeIs($route) || request()->routeIs($route . '.*');
    }
    
    /**
     * 取得圖示 SVG
     */
    public function getIcon(string $iconName): string
    {
        $icons = [
            'dashboard' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2H5a2 2 0 00-2-2z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5a2 2 0 012-2h4a2 2 0 012 2v6H8V5z"></path>',
            
            'users' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>',
            
            'shield' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>',
            
            'lock' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>',
            
            'settings' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>',
        ];
        
        return $icons[$iconName] ?? $icons['dashboard'];
    }
    
    /**
     * 渲染元件
     */
    public function render()
    {
        return view('livewire.admin.layout.sidebar', [
            'menuItems' => $this->getFilteredMenuItems(),
        ]);
    }
}