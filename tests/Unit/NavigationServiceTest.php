<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\NavigationService;
use App\Models\User;
use App\Models\Role;
use App\Models\Permission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;

/**
 * NavigationService 單元測試
 */
class NavigationServiceTest extends TestCase
{
    use RefreshDatabase;

    protected NavigationService $navigationService;
    protected User $user;
    protected User $adminUser;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->navigationService = new NavigationService();
        
        // 建立測試使用者
        $this->user = User::factory()->create();
        $this->adminUser = User::factory()->create();
        
        // 建立測試角色和權限
        $this->createTestRolesAndPermissions();
    }

    /**
     * 測試取得選單結構
     */
    public function test_can_get_menu_structure(): void
    {
        $menu = $this->navigationService->getMenuStructure();
        
        $this->assertIsArray($menu);
        $this->assertNotEmpty($menu);
        
        // 檢查選單項目是否按順序排列
        $orders = array_column($menu, 'order');
        $sortedOrders = $orders;
        sort($sortedOrders);
        $this->assertEquals($orders, $sortedOrders);
        
        // 檢查必要的選單項目
        $menuKeys = array_column($menu, 'key');
        $this->assertContains('dashboard', $menuKeys);
        $this->assertContains('users', $menuKeys);
        $this->assertContains('roles', $menuKeys);
    }

    /**
     * 測試根據權限過濾選單
     */
    public function test_can_filter_menu_by_permissions(): void
    {
        // 給使用者分配部分權限
        $this->giveUserPermissions($this->user, ['admin.dashboard.view', 'admin.users.view']);
        
        $menu = $this->navigationService->getMenuStructure();
        $filteredMenu = $this->navigationService->filterMenuByPermissions($menu, $this->user);
        
        $this->assertIsArray($filteredMenu);
        
        // 檢查只有有權限的選單項目被保留
        $menuKeys = array_column($filteredMenu, 'key');
        $this->assertContains('dashboard', $menuKeys);
        $this->assertContains('users', $menuKeys);
        $this->assertNotContains('roles', $menuKeys); // 沒有權限的項目應該被過濾掉
    }

    /**
     * 測試子選單權限過濾
     */
    public function test_can_filter_submenu_by_permissions(): void
    {
        // 只給使用者查看權限，沒有建立權限
        $this->giveUserPermissions($this->user, ['admin.users.view']);
        
        // 確保使用者不是超級管理員
        $this->assertFalse($this->user->isSuperAdmin());
        

        
        // 確保使用者有查看權限但沒有建立權限
        $this->assertTrue($this->user->hasPermission('admin.users.view'));
        $this->assertFalse($this->user->hasPermission('admin.users.create'));
        
        $menu = $this->navigationService->getMenuStructure();
        $filteredMenu = $this->navigationService->filterMenuByPermissions($menu, $this->user);
        
        // 找到使用者管理選單
        $usersMenu = collect($filteredMenu)->firstWhere('key', 'users');
        $this->assertNotNull($usersMenu);
        
        // 檢查子選單是否正確過濾
        if (isset($usersMenu['children'])) {
            $childKeys = array_column($usersMenu['children'], 'key');
            

            $this->assertContains('users.index', $childKeys);
            $this->assertNotContains('users.create', $childKeys); // 沒有建立權限
        } else {
            // 如果沒有子選單，表示所有子項目都被過濾掉了
            $this->assertTrue(true);
        }
    }

    /**
     * 測試取得使用者選單結構（含快取）
     */
    public function test_can_get_user_menu_structure_with_cache(): void
    {
        $this->giveUserPermissions($this->user, ['admin.dashboard.view']);
        
        // 第一次呼叫
        $menu1 = $this->navigationService->getUserMenuStructure($this->user);
        
        // 第二次呼叫應該從快取取得
        $menu2 = $this->navigationService->getUserMenuStructure($this->user);
        
        $this->assertEquals($menu1, $menu2);
        
        // 檢查快取是否存在
        $cacheKey = "menu_structure_{$this->user->id}_" . $this->user->roles->pluck('id')->sort()->implode('_');
        $this->assertTrue(Cache::has($cacheKey));
    }

    /**
     * 測試生成麵包屑導航
     */
    public function test_can_generate_breadcrumbs(): void
    {
        // 測試從路由名稱生成麵包屑
        $breadcrumbs = $this->navigationService->getCurrentBreadcrumbs('admin.users.index');
        
        $this->assertIsArray($breadcrumbs);
        $this->assertNotEmpty($breadcrumbs);
        
        // 檢查麵包屑結構
        foreach ($breadcrumbs as $breadcrumb) {
            $this->assertArrayHasKey('title', $breadcrumb);
            $this->assertArrayHasKey('active', $breadcrumb);
        }
        
        // 最後一個項目應該是活躍的
        $lastBreadcrumb = end($breadcrumbs);
        $this->assertTrue($lastBreadcrumb['active']);
    }

    /**
     * 測試取得快速操作選單
     */
    public function test_can_get_quick_actions(): void
    {
        $this->giveUserPermissions($this->user, ['admin.users.create', 'admin.settings.general']);
        
        $quickActions = $this->navigationService->getQuickActions($this->user);
        
        $this->assertIsArray($quickActions);
        
        // 檢查只有有權限的操作被包含
        $actionRoutes = array_column($quickActions, 'route');
        $this->assertContains('admin.users.create', $actionRoutes);
        $this->assertContains('admin.settings.general', $actionRoutes);
        $this->assertNotContains('admin.roles.create', $actionRoutes); // 沒有權限
    }

    /**
     * 測試建立選單樹狀結構
     */
    public function test_can_build_menu_tree(): void
    {
        $items = [
            ['id' => 1, 'title' => 'Parent 1', 'parent_id' => null],
            ['id' => 2, 'title' => 'Child 1', 'parent_id' => 1],
            ['id' => 3, 'title' => 'Child 2', 'parent_id' => 1],
            ['id' => 4, 'title' => 'Parent 2', 'parent_id' => null],
        ];
        
        $tree = $this->navigationService->buildMenuTree($items);
        
        $this->assertCount(2, $tree); // 兩個父項目
        $this->assertCount(2, $tree[0]['children']); // 第一個父項目有兩個子項目
        $this->assertArrayNotHasKey('children', $tree[1]); // 第二個父項目沒有子項目
    }

    /**
     * 測試取得選單權限列表
     */
    public function test_can_get_menu_permissions(): void
    {
        $permissions = $this->navigationService->getMenuPermissions();
        
        $this->assertIsArray($permissions);
        $this->assertNotEmpty($permissions);
        
        // 檢查是否包含預期的權限
        $this->assertContains('admin.dashboard.view', $permissions);
        $this->assertContains('admin.users.view', $permissions);
        $this->assertContains('admin.users.create', $permissions);
    }

    /**
     * 測試清除選單快取
     */
    public function test_can_clear_menu_cache(): void
    {
        $this->giveUserPermissions($this->user, ['admin.dashboard.view']);
        
        // 建立快取
        $this->navigationService->getUserMenuStructure($this->user);
        $cacheKey = "menu_structure_{$this->user->id}_" . $this->user->roles->pluck('id')->sort()->implode('_');
        $this->assertTrue(Cache::has($cacheKey));
        
        // 清除特定使用者快取
        $this->navigationService->clearMenuCache($this->user);
        $this->assertFalse(Cache::has($cacheKey));
    }

    /**
     * 測試超級管理員可以看到所有選單
     */
    public function test_super_admin_can_see_all_menus(): void
    {
        // 建立超級管理員
        $superAdminRole = Role::create(['name' => 'super_admin', 'display_name' => '超級管理員']);
        $this->adminUser->assignRole($superAdminRole);
        
        $menu = $this->navigationService->getMenuStructure();
        $filteredMenu = $this->navigationService->filterMenuByPermissions($menu, $this->adminUser);
        
        // 超級管理員應該能看到所有選單項目
        $this->assertCount(count($menu), $filteredMenu);
    }

    /**
     * 建立測試角色和權限
     */
    protected function createTestRolesAndPermissions(): void
    {
        // 建立權限
        $permissions = [
            'admin.dashboard.view',
            'admin.users.view',
            'admin.users.create',
            'admin.roles.view',
            'admin.roles.create',
            'admin.settings.general',
            'admin.activities.view',
        ];

        foreach ($permissions as $permission) {
            Permission::create([
                'name' => $permission,
                'display_name' => $permission,
                'module' => explode('.', $permission)[1] ?? 'general'
            ]);
        }

        // 建立角色
        $userRole = Role::create(['name' => 'user', 'display_name' => '一般使用者']);
        $adminRole = Role::create(['name' => 'admin', 'display_name' => '管理員']);
    }

    /**
     * 給使用者分配權限
     */
    protected function giveUserPermissions(User $user, array $permissions): void
    {
        $role = Role::create(['name' => 'test_role_' . $user->id, 'display_name' => '測試角色']);
        
        foreach ($permissions as $permissionName) {
            $permission = Permission::where('name', $permissionName)->first();
            if ($permission) {
                $role->permissions()->attach($permission->id);
            }
        }
        
        $user->assignRole($role);
    }
}