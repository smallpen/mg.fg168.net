<?php

namespace App\Examples;

use App\Livewire\Admin\Layout\Sidebar;
use App\Models\User;
use App\Models\Role;
use App\Models\Permission;
use App\Services\NavigationService;

/**
 * Sidebar 元件使用範例
 * 
 * 展示如何使用 Sidebar 元件的各種功能
 */
class SidebarExample
{
    /**
     * 基本使用範例
     */
    public function basicUsage()
    {
        // 在 Blade 模板中使用
        echo '<!-- 基本使用 -->';
        echo '<livewire:admin.layout.sidebar />';
        
        // 或使用 @livewire 指令
        echo '<!-- 使用 @livewire 指令 -->';
        echo '@livewire("admin.layout.sidebar")';
    }
    
    /**
     * 權限設定範例
     */
    public function permissionSetup()
    {
        // 建立權限
        $permissions = [
            ['name' => 'admin.dashboard.view', 'module' => 'dashboard', 'display_name' => '儀表板檢視'],
            ['name' => 'admin.users.view', 'module' => 'users', 'display_name' => '使用者檢視'],
            ['name' => 'admin.users.create', 'module' => 'users', 'display_name' => '建立使用者'],
            ['name' => 'admin.roles.view', 'module' => 'roles', 'display_name' => '角色檢視'],
            ['name' => 'admin.roles.manage', 'module' => 'roles', 'display_name' => '角色管理'],
        ];
        
        foreach ($permissions as $permissionData) {
            Permission::firstOrCreate(
                ['name' => $permissionData['name']],
                [
                    'display_name' => $permissionData['display_name'],
                    'description' => $permissionData['display_name'],
                    'module' => $permissionData['module'],
                ]
            );
        }
        
        // 建立角色並分配權限
        $adminRole = Role::firstOrCreate(
            ['name' => 'admin'],
            [
                'display_name' => '管理員',
                'description' => '系統管理員',
            ]
        );
        
        // 分配所有權限給管理員角色
        $allPermissions = Permission::whereIn('name', array_column($permissions, 'name'))->get();
        $adminRole->permissions()->sync($allPermissions->pluck('id'));
        
        // 建立使用者並分配角色
        $user = User::factory()->create([
            'name' => '管理員',
            'email' => 'admin@example.com',
        ]);
        
        $user->roles()->attach($adminRole);
        
        return $user;
    }
    
    /**
     * 選單結構自訂範例
     */
    public function customMenuStructure()
    {
        $navigationService = app(NavigationService::class);
        
        // 取得使用者選單結構
        $user = auth()->user();
        $menuStructure = $navigationService->getUserMenuStructure($user);
        
        // 顯示選單結構
        foreach ($menuStructure as $item) {
            echo "選單項目: {$item['title']}\n";
            echo "圖示: {$item['icon']}\n";
            echo "權限: {$item['permission']}\n";
            
            if (isset($item['children'])) {
                echo "子選單:\n";
                foreach ($item['children'] as $child) {
                    echo "  - {$child['title']}\n";
                }
            }
            
            echo "\n";
        }
        
        return $menuStructure;
    }
    
    /**
     * 搜尋功能範例
     */
    public function searchExample()
    {
        // 模擬搜尋操作
        $sidebar = new Sidebar();
        $sidebar->mount();
        
        // 設定搜尋關鍵字
        $sidebar->menuSearch = '使用者';
        $sidebar->updatedMenuSearch();
        
        // 取得搜尋結果
        $searchResults = $sidebar->searchResults;
        
        echo "搜尋 '使用者' 的結果:\n";
        foreach ($searchResults as $result) {
            echo "- {$result['title']}";
            if (isset($result['parent'])) {
                echo " (屬於: {$result['parent']})";
            }
            echo "\n";
        }
        
        return $searchResults;
    }
    
    /**
     * 收合功能範例
     */
    public function collapseExample()
    {
        $sidebar = new Sidebar();
        $sidebar->mount();
        
        // 切換收合狀態
        echo "初始狀態: " . ($sidebar->collapsed ? '收合' : '展開') . "\n";
        
        $sidebar->toggleCollapse();
        echo "切換後: " . ($sidebar->collapsed ? '收合' : '展開') . "\n";
        
        // 展開/收合選單項目
        $sidebar->toggleMenu('users');
        echo "展開的選單: " . implode(', ', $sidebar->expandedMenus) . "\n";
        
        return $sidebar;
    }
    
    /**
     * Session 狀態管理範例
     */
    public function sessionStateExample()
    {
        // 設定 session 狀態
        session([
            'sidebar.collapsed' => true,
            'sidebar.expanded' => ['users', 'roles']
        ]);
        
        // 建立 Sidebar 實例，會自動從 session 恢復狀態
        $sidebar = new Sidebar();
        $sidebar->mount();
        
        echo "從 session 恢復的狀態:\n";
        echo "收合狀態: " . ($sidebar->collapsed ? '是' : '否') . "\n";
        echo "展開的選單: " . implode(', ', $sidebar->expandedMenus) . "\n";
        
        return $sidebar;
    }
    
    /**
     * 事件處理範例
     */
    public function eventHandlingExample()
    {
        // 在 JavaScript 中監聽側邊欄事件
        $jsCode = "
        // 監聽側邊欄切換事件
        document.addEventListener('livewire:init', () => {
            Livewire.on('sidebar-toggled', (event) => {
                console.log('側邊欄狀態變更:', event.collapsed ? '收合' : '展開');
                
                // 調整主內容區域的邊距
                const mainContent = document.querySelector('.main-content');
                if (mainContent) {
                    mainContent.style.marginLeft = event.collapsed ? '64px' : '256px';
                }
            });
        });
        ";
        
        return $jsCode;
    }
    
    /**
     * CSS 自訂範例
     */
    public function customStylingExample()
    {
        $css = "
        /* 自訂側邊欄樣式 */
        .sidebar-custom {
            /* 自訂背景色 */
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        
        .sidebar-custom .menu-item {
            /* 自訂選單項目樣式 */
            border-radius: 8px;
            margin-bottom: 4px;
            transition: all 0.3s ease;
        }
        
        .sidebar-custom .menu-item:hover {
            transform: translateX(4px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }
        
        .sidebar-custom .menu-item.active {
            background: rgba(255, 255, 255, 0.2);
            border-left: 4px solid #fff;
        }
        
        /* 收合動畫 */
        .sidebar-transition {
            transition: width 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        /* 搜尋框樣式 */
        .search-input {
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            color: white;
        }
        
        .search-input::placeholder {
            color: rgba(255, 255, 255, 0.7);
        }
        ";
        
        return $css;
    }
    
    /**
     * 完整整合範例
     */
    public function fullIntegrationExample()
    {
        // 1. 設定權限和使用者
        $user = $this->permissionSetup();
        
        // 2. 登入使用者
        auth()->login($user);
        
        // 3. 取得選單結構
        $menuStructure = $this->customMenuStructure();
        
        // 4. 測試搜尋功能
        $searchResults = $this->searchExample();
        
        // 5. 測試收合功能
        $sidebar = $this->collapseExample();
        
        // 6. 測試 session 狀態
        $sessionSidebar = $this->sessionStateExample();
        
        return [
            'user' => $user,
            'menuStructure' => $menuStructure,
            'searchResults' => $searchResults,
            'sidebar' => $sidebar,
            'sessionSidebar' => $sessionSidebar,
        ];
    }
}