<?php

namespace Tests\Feature\Livewire\Admin\Layout;

use App\Livewire\Admin\Layout\Sidebar;
use App\Models\User;
use App\Models\Role;
use App\Models\Permission;
use App\Services\NavigationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * 側邊導航選單元件測試
 */
class SidebarTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Role $adminRole;
    protected NavigationService $navigationService;

    protected function setUp(): void
    {
        parent::setUp();
        
        // 建立測試使用者和角色
        $this->adminRole = Role::create([
            'name' => 'admin',
            'display_name' => '管理員',
            'description' => '系統管理員',
        ]);
        
        $this->user = User::factory()->create([
            'name' => '測試使用者',
            'email' => 'test@example.com',
        ]);
        
        $this->user->roles()->attach($this->adminRole);
        
        // 建立測試權限
        $permissions = [
            ['name' => 'admin.dashboard.view', 'module' => 'dashboard'],
            ['name' => 'admin.users.view', 'module' => 'users'],
            ['name' => 'admin.users.create', 'module' => 'users'],
            ['name' => 'admin.roles.view', 'module' => 'roles'],
            ['name' => 'admin.roles.manage', 'module' => 'roles'],
        ];
        
        foreach ($permissions as $permissionData) {
            $permission = Permission::create([
                'name' => $permissionData['name'],
                'display_name' => $permissionData['name'],
                'description' => $permissionData['name'],
                'module' => $permissionData['module'],
            ]);
            
            $this->adminRole->permissions()->attach($permission);
        }
        
        $this->navigationService = app(NavigationService::class);
    }

    /** @test */
    public function 可以渲染側邊欄元件()
    {
        $this->actingAs($this->user);
        
        Livewire::test(Sidebar::class)
            ->assertStatus(200)
            ->assertSee('儀表板');
    }

    /** @test */
    public function 可以切換側邊欄收合狀態()
    {
        $this->actingAs($this->user);
        
        Livewire::test(Sidebar::class)
            ->assertSet('collapsed', false)
            ->call('toggleCollapse')
            ->assertSet('collapsed', true)
            ->assertDispatched('sidebar-toggled', collapsed: true);
    }

    /** @test */
    public function 可以展開和收合選單項目()
    {
        $this->actingAs($this->user);
        
        Livewire::test(Sidebar::class)
            ->call('toggleMenu', 'users')
            ->assertSet('expandedMenus', ['users'])
            ->call('toggleMenu', 'users')
            ->assertSet('expandedMenus', []);
    }

    /** @test */
    public function 可以搜尋選單項目()
    {
        $this->actingAs($this->user);
        
        $component = Livewire::test(Sidebar::class)
            ->set('menuSearch', '使用者')
            ->assertSet('showSearch', true);
            
        // 檢查搜尋結果不為空
        $searchResults = $component->get('searchResults');
        $this->assertNotEmpty($searchResults);
        
        // 檢查搜尋結果包含相關項目
        $foundUserRelated = false;
        foreach ($searchResults as $result) {
            if (str_contains($result['title'], '使用者')) {
                $foundUserRelated = true;
                break;
            }
        }
        
        $this->assertTrue($foundUserRelated);
    }

    /** @test */
    public function 可以清除搜尋()
    {
        $this->actingAs($this->user);
        
        Livewire::test(Sidebar::class)
            ->set('menuSearch', '使用者')
            ->call('clearMenuSearch')
            ->assertSet('menuSearch', '')
            ->assertSet('showSearch', false)
            ->assertSet('searchResults', []);
    }

    /** @test */
    public function 只顯示有權限的選單項目()
    {
        // 建立沒有權限的使用者
        $limitedUser = User::factory()->create();
        $limitedRole = Role::create([
            'name' => 'limited',
            'display_name' => '受限使用者',
            'description' => '受限使用者',
        ]);
        
        // 只給儀表板權限（使用已存在的權限）
        $dashboardPermission = Permission::where('name', 'admin.dashboard.view')->first();
        
        $limitedRole->permissions()->attach($dashboardPermission);
        $limitedUser->roles()->attach($limitedRole);
        
        $this->actingAs($limitedUser);
        
        $component = Livewire::test(Sidebar::class);
        
        // 檢查選單項目是否根據權限過濾
        $menuItems = $component->get('menuItems');
        
        // 應該只有儀表板項目
        $this->assertCount(1, $menuItems);
        $this->assertEquals('dashboard', $menuItems[0]['key']);
    }

    /** @test */
    public function 可以正確識別活躍路由()
    {
        $this->actingAs($this->user);
        
        $component = Livewire::test(Sidebar::class);
        
        // 測試路由識別邏輯
        $this->assertFalse($component->instance()->isActiveRoute('admin.users.index'));
        $this->assertFalse($component->instance()->isActiveRoute('admin.roles.index'));
    }

    /** @test */
    public function 搜尋功能可以找到子選單項目()
    {
        $this->actingAs($this->user);
        
        Livewire::test(Sidebar::class)
            ->set('menuSearch', '建立使用者')
            ->assertSet('showSearch', true);
            
        // 檢查搜尋結果包含子選單項目
        $component = Livewire::test(Sidebar::class)
            ->set('menuSearch', '建立');
            
        $searchResults = $component->get('searchResults');
        $this->assertNotEmpty($searchResults);
        
        // 檢查是否包含父選單資訊
        $foundCreateUser = false;
        foreach ($searchResults as $result) {
            if (str_contains($result['title'], '建立') && isset($result['parent'])) {
                $foundCreateUser = true;
                break;
            }
        }
        
        $this->assertTrue($foundCreateUser);
    }

    /** @test */
    public function 收合模式下不顯示搜尋框()
    {
        $this->actingAs($this->user);
        
        Livewire::test(Sidebar::class)
            ->set('collapsed', true)
            ->assertDontSee('搜尋選單...');
    }

    /** @test */
    public function 可以從session恢復側邊欄狀態()
    {
        $this->actingAs($this->user);
        
        // 設定 session 狀態
        session(['sidebar.collapsed' => true, 'sidebar.expanded' => ['users', 'roles']]);
        
        $component = Livewire::test(Sidebar::class);
        
        $this->assertTrue($component->get('collapsed'));
        $this->assertEquals(['users', 'roles'], $component->get('expandedMenus'));
    }

    /** @test */
    public function 選單狀態會儲存到session()
    {
        $this->actingAs($this->user);
        
        Livewire::test(Sidebar::class)
            ->call('toggleCollapse')
            ->call('toggleMenu', 'users');
            
        $this->assertTrue(session('sidebar.collapsed'));
        $this->assertContains('users', session('sidebar.expanded'));
    }

    /** @test */
    public function 可以正確處理選單權限檢查()
    {
        $this->actingAs($this->user);
        
        $component = Livewire::test(Sidebar::class);
        $menuItems = $component->get('menuItems');
        
        // 檢查所有返回的選單項目都是使用者有權限存取的
        foreach ($menuItems as $item) {
            if (isset($item['permission'])) {
                $this->assertTrue($this->user->hasPermission($item['permission']));
            }
            
            // 檢查子選單
            if (isset($item['children'])) {
                foreach ($item['children'] as $child) {
                    if (isset($child['permission'])) {
                        $this->assertTrue($this->user->hasPermission($child['permission']));
                    }
                }
            }
        }
    }
}