<?php

namespace Tests\Unit\Livewire\Admin\Permissions;

use App\Livewire\Admin\Permissions\PermissionList;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Repositories\PermissionRepository;
use App\Services\AuditLogService;
use App\Services\InputValidationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * PermissionList 元件單元測試
 */
class PermissionListTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected Role $adminRole;
    protected array $testPermissions;

    protected function setUp(): void
    {
        parent::setUp();
        
        // 建立管理員角色和使用者
        $this->adminRole = Role::create([
            'name' => 'admin',
            'display_name' => '管理員',
            'is_system_role' => true,
            'is_active' => true,
        ]);

        $this->adminUser = User::factory()->create(['is_active' => true]);
        $this->adminUser->roles()->attach($this->adminRole);
        
        // 建立權限管理相關權限
        $permissions = [
            'permissions.view', 'permissions.create', 'permissions.edit', 
            'permissions.delete', 'permissions.export', 'permissions.import'
        ];
        
        foreach ($permissions as $permission) {
            Permission::create([
                'name' => $permission,
                'display_name' => $permission,
                'module' => 'permissions',
                'type' => 'view'
            ]);
        }
        
        $this->adminRole->permissions()->attach(
            Permission::whereIn('name', $permissions)->pluck('id')
        );

        // 建立測試權限
        $this->createTestPermissions();
    }

    private function createTestPermissions(): void
    {
        $this->testPermissions = [
            [
                'name' => 'users.view',
                'display_name' => '檢視使用者',
                'description' => '檢視使用者列表',
                'module' => 'users',
                'type' => 'view',
            ],
            [
                'name' => 'users.create',
                'display_name' => '建立使用者',
                'description' => '建立新使用者',
                'module' => 'users',
                'type' => 'create',
            ],
            [
                'name' => 'roles.view',
                'display_name' => '檢視角色',
                'description' => '檢視角色列表',
                'module' => 'roles',
                'type' => 'view',
            ],
            [
                'name' => 'roles.edit',
                'display_name' => '編輯角色',
                'description' => '編輯角色資料',
                'module' => 'roles',
                'type' => 'edit',
            ],
        ];

        foreach ($this->testPermissions as $permissionData) {
            Permission::create($permissionData);
        }
    }

    /** @test */
    public function 元件可以正確初始化()
    {
        $this->actingAs($this->adminUser);

        $component = Livewire::test(PermissionList::class);

        $component->assertSet('search', '')
                 ->assertSet('moduleFilter', 'all')
                 ->assertSet('typeFilter', 'all')
                 ->assertSet('usageFilter', 'all')
                 ->assertSet('viewMode', 'list')
                 ->assertSet('sortField', 'module')
                 ->assertSet('sortDirection', 'asc')
                 ->assertSet('perPage', 25)
                 ->assertSet('selectedPermissions', [])
                 ->assertSet('selectAll', false);
    }

    /** @test */
    public function 沒有權限的使用者無法存取元件()
    {
        $user = User::factory()->create(['is_active' => true]);
        $this->actingAs($user);

        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        
        Livewire::test(PermissionList::class);
    }

    /** @test */
    public function 可以正確顯示權限列表()
    {
        $this->actingAs($this->adminUser);

        $component = Livewire::test(PermissionList::class);

        $component->assertStatus(200);
        
        // 檢查是否顯示測試權限
        foreach ($this->testPermissions as $permission) {
            $component->assertSee($permission['name'])
                     ->assertSee($permission['display_name']);
        }
    }

    /** @test */
    public function 搜尋功能正常運作()
    {
        $this->actingAs($this->adminUser);

        $component = Livewire::test(PermissionList::class);

        // 搜尋 "users"
        $component->set('search', 'users')
                 ->assertSee('users.view')
                 ->assertSee('users.create')
                 ->assertDontSee('roles.view')
                 ->assertDontSee('roles.edit');

        // 搜尋 "檢視"
        $component->set('search', '檢視')
                 ->assertSee('users.view')
                 ->assertSee('roles.view')
                 ->assertDontSee('users.create')
                 ->assertDontSee('roles.edit');
    }

    /** @test */
    public function 模組篩選功能正常運作()
    {
        $this->actingAs($this->adminUser);

        $component = Livewire::test(PermissionList::class);

        // 篩選 users 模組
        $component->set('moduleFilter', 'users')
                 ->assertSee('users.view')
                 ->assertSee('users.create')
                 ->assertDontSee('roles.view')
                 ->assertDontSee('roles.edit');

        // 篩選 roles 模組
        $component->set('moduleFilter', 'roles')
                 ->assertSee('roles.view')
                 ->assertSee('roles.edit')
                 ->assertDontSee('users.view')
                 ->assertDontSee('users.create');
    }

    /** @test */
    public function 類型篩選功能正常運作()
    {
        $this->actingAs($this->adminUser);

        $component = Livewire::test(PermissionList::class);

        // 篩選 view 類型
        $component->set('typeFilter', 'view')
                 ->assertSee('users.view')
                 ->assertSee('roles.view')
                 ->assertDontSee('users.create')
                 ->assertDontSee('roles.edit');

        // 篩選 create 類型
        $component->set('typeFilter', 'create')
                 ->assertSee('users.create')
                 ->assertDontSee('users.view')
                 ->assertDontSee('roles.view')
                 ->assertDontSee('roles.edit');
    }

    /** @test */
    public function 檢視模式切換功能正常()
    {
        $this->actingAs($this->adminUser);

        $component = Livewire::test(PermissionList::class);

        // 切換到分組檢視
        $component->set('viewMode', 'grouped')
                 ->assertSet('viewMode', 'grouped');

        // 切換到樹狀檢視
        $component->set('viewMode', 'tree')
                 ->assertSet('viewMode', 'tree');

        // 切換回列表檢視
        $component->set('viewMode', 'list')
                 ->assertSet('viewMode', 'list');
    }

    /** @test */
    public function 排序功能正常運作()
    {
        $this->actingAs($this->adminUser);

        $component = Livewire::test(PermissionList::class);

        // 按名稱排序
        $component->call('sortBy', 'name')
                 ->assertSet('sortField', 'name')
                 ->assertSet('sortDirection', 'asc');

        // 再次點擊應該改變排序方向
        $component->call('sortBy', 'name')
                 ->assertSet('sortDirection', 'desc');

        // 點擊不同欄位應該重置為升序
        $component->call('sortBy', 'display_name')
                 ->assertSet('sortField', 'display_name')
                 ->assertSet('sortDirection', 'asc');
    }

    /** @test */
    public function 權限選擇功能正常運作()
    {
        $this->actingAs($this->adminUser);

        $permission = Permission::where('name', 'users.view')->first();
        
        $component = Livewire::test(PermissionList::class);

        // 選擇權限
        $component->call('togglePermissionSelection', $permission->id)
                 ->assertSet('selectedPermissions', [$permission->id]);

        // 取消選擇權限
        $component->call('togglePermissionSelection', $permission->id)
                 ->assertSet('selectedPermissions', []);
    }

    /** @test */
    public function 全選功能正常運作()
    {
        $this->actingAs($this->adminUser);

        $component = Livewire::test(PermissionList::class);

        // 啟用全選
        $component->set('selectAll', true)
                 ->call('toggleSelectAll');

        $selectedPermissions = $component->get('selectedPermissions');
        $this->assertNotEmpty($selectedPermissions);

        // 取消全選
        $component->set('selectAll', false)
                 ->call('toggleSelectAll')
                 ->assertSet('selectedPermissions', []);
    }

    /** @test */
    public function 分組展開收合功能正常()
    {
        $this->actingAs($this->adminUser);

        $component = Livewire::test(PermissionList::class);

        // 展開 users 分組
        $component->call('toggleGroup', 'users')
                 ->assertSet('expandedGroups', ['users']);

        // 收合 users 分組
        $component->call('toggleGroup', 'users')
                 ->assertSet('expandedGroups', []);

        // 展開多個分組
        $component->call('toggleGroup', 'users')
                 ->call('toggleGroup', 'roles');
        
        $expandedGroups = $component->get('expandedGroups');
        $this->assertContains('users', $expandedGroups);
        $this->assertContains('roles', $expandedGroups);
    }

    /** @test */
    public function 建立權限功能檢查權限()
    {
        $this->actingAs($this->adminUser);

        $component = Livewire::test(PermissionList::class);

        $component->call('createPermission')
                 ->assertDispatched('open-permission-form', [
                     'mode' => 'create'
                 ]);
    }

    /** @test */
    public function 沒有建立權限時顯示錯誤()
    {
        // 建立沒有建立權限的使用者
        $user = User::factory()->create(['is_active' => true]);
        $role = Role::create([
            'name' => 'viewer',
            'display_name' => '檢視者',
            'is_active' => true,
        ]);
        
        // 只給檢視權限
        $viewPermission = Permission::where('name', 'permissions.view')->first();
        $role->permissions()->attach($viewPermission);
        $user->roles()->attach($role);

        $this->actingAs($user);

        $component = Livewire::test(PermissionList::class);

        $component->call('createPermission')
                 ->assertDispatched('show-toast', [
                     'type' => 'error',
                     'message' => __('admin.permissions.no_permission_create')
                 ]);
    }

    /** @test */
    public function 編輯權限功能正常運作()
    {
        $this->actingAs($this->adminUser);

        $permission = Permission::where('name', 'users.view')->first();
        
        $component = Livewire::test(PermissionList::class);

        $component->call('editPermission', $permission->id)
                 ->assertDispatched('open-permission-form', [
                     'mode' => 'edit',
                     'permissionId' => $permission->id
                 ]);
    }

    /** @test */
    public function 編輯不存在的權限顯示錯誤()
    {
        $this->actingAs($this->adminUser);

        $component = Livewire::test(PermissionList::class);

        $component->call('editPermission', 99999)
                 ->assertDispatched('show-toast', [
                     'type' => 'error',
                     'message' => __('admin.permissions.permission_not_found')
                 ]);
    }

    /** @test */
    public function 檢視依賴關係功能正常運作()
    {
        $this->actingAs($this->adminUser);

        $permission = Permission::where('name', 'users.view')->first();
        
        $component = Livewire::test(PermissionList::class);

        $component->call('viewDependencies', $permission->id)
                 ->assertDispatched('select-permission-for-dependencies', [
                     'permissionId' => $permission->id
                 ]);
    }

    /** @test */
    public function 刪除權限功能檢查權限()
    {
        $this->actingAs($this->adminUser);

        $permission = Permission::where('name', 'users.view')->first();
        
        $component = Livewire::test(PermissionList::class);

        $component->call('deletePermission', $permission->id)
                 ->assertDispatched('confirm-permission-delete', [
                     'permissionId' => $permission->id
                 ]);
    }

    /** @test */
    public function 重置篩選功能正常運作()
    {
        $this->actingAs($this->adminUser);

        $component = Livewire::test(PermissionList::class);

        // 設定一些篩選條件
        $component->set('search', 'test')
                 ->set('moduleFilter', 'users')
                 ->set('typeFilter', 'view')
                 ->set('usageFilter', 'used')
                 ->set('sortField', 'name')
                 ->set('sortDirection', 'desc')
                 ->set('selectedPermissions', [1, 2, 3])
                 ->set('selectAll', true)
                 ->set('expandedGroups', ['users', 'roles']);

        // 重置篩選
        $component->call('resetFilters')
                 ->assertSet('search', '')
                 ->assertSet('moduleFilter', 'all')
                 ->assertSet('typeFilter', 'all')
                 ->assertSet('usageFilter', 'all')
                 ->assertSet('sortField', 'module')
                 ->assertSet('sortDirection', 'asc')
                 ->assertSet('selectedPermissions', [])
                 ->assertSet('selectAll', false)
                 ->assertSet('expandedGroups', []);
    }

    /** @test */
    public function 匯出權限功能檢查權限()
    {
        $this->actingAs($this->adminUser);

        $component = Livewire::test(PermissionList::class);

        $component->call('exportPermissions')
                 ->assertDispatched('export-permissions-started');
    }

    /** @test */
    public function 沒有匯出權限時顯示錯誤()
    {
        // 建立沒有匯出權限的使用者
        $user = User::factory()->create(['is_active' => true]);
        $role = Role::create([
            'name' => 'viewer',
            'display_name' => '檢視者',
            'is_active' => true,
        ]);
        
        $viewPermission = Permission::where('name', 'permissions.view')->first();
        $role->permissions()->attach($viewPermission);
        $user->roles()->attach($role);

        $this->actingAs($user);

        $component = Livewire::test(PermissionList::class);

        $component->call('exportPermissions')
                 ->assertDispatched('show-toast', [
                     'type' => 'error',
                     'message' => '您沒有匯出權限資料的權限'
                 ]);
    }

    /** @test */
    public function 匯入權限功能檢查權限()
    {
        $this->actingAs($this->adminUser);

        $component = Livewire::test(PermissionList::class);

        $component->call('importPermissions')
                 ->assertDispatched('open-import-modal');
    }

    /** @test */
    public function 計算屬性返回正確的資料()
    {
        $this->actingAs($this->adminUser);

        $component = Livewire::test(PermissionList::class);

        // 測試 modules 計算屬性
        $modules = $component->get('modules');
        $this->assertInstanceOf(\Illuminate\Support\Collection::class, $modules);

        // 測試 types 計算屬性
        $types = $component->get('types');
        $this->assertInstanceOf(\Illuminate\Support\Collection::class, $types);

        // 測試 stats 計算屬性
        $stats = $component->get('stats');
        $this->assertIsArray($stats);
        $this->assertArrayHasKey('total', $stats);
        $this->assertArrayHasKey('used', $stats);
        $this->assertArrayHasKey('unused', $stats);
    }

    /** @test */
    public function 搜尋條件更新時重置分頁()
    {
        $this->actingAs($this->adminUser);

        $component = Livewire::test(PermissionList::class);

        // 設定到第二頁
        $component->set('page', 2);

        // 更新搜尋條件應該重置到第一頁
        $component->set('search', 'test')
                 ->assertSet('page', 1);
    }

    /** @test */
    public function 篩選條件更新時重置分頁()
    {
        $this->actingAs($this->adminUser);

        $component = Livewire::test(PermissionList::class);

        // 設定到第二頁
        $component->set('page', 2);

        // 更新模組篩選應該重置到第一頁
        $component->set('moduleFilter', 'users')
                 ->assertSet('page', 1);

        // 重新設定到第二頁
        $component->set('page', 2);

        // 更新類型篩選應該重置到第一頁
        $component->set('typeFilter', 'view')
                 ->assertSet('page', 1);

        // 重新設定到第二頁
        $component->set('page', 2);

        // 更新使用狀態篩選應該重置到第一頁
        $component->set('usageFilter', 'used')
                 ->assertSet('page', 1);
    }

    /** @test */
    public function 權限匯入完成後重新載入資料()
    {
        $this->actingAs($this->adminUser);

        $component = Livewire::test(PermissionList::class);

        $component->dispatch('permissions-imported')
                 ->assertDispatched('show-toast', [
                     'type' => 'success',
                     'message' => '權限資料已更新，列表已重新載入'
                 ]);
    }

    /** @test */
    public function 使用情況分析功能正常運作()
    {
        $this->actingAs($this->adminUser);

        $component = Livewire::test(PermissionList::class);

        $component->call('openUsageAnalysis')
                 ->assertDispatched('open-usage-analysis');
    }

    /** @test */
    public function 未使用權限標記功能檢查權限()
    {
        $this->actingAs($this->adminUser);

        // 需要管理權限
        $managePermission = Permission::create([
            'name' => 'permissions.manage',
            'display_name' => '管理權限',
            'module' => 'permissions',
            'type' => 'manage'
        ]);
        $this->adminRole->permissions()->attach($managePermission);

        $component = Livewire::test(PermissionList::class);

        $component->call('openUnusedPermissionMarker')
                 ->assertDispatched('open-unused-permission-marker');
    }

    /** @test */
    public function 快取機制正常運作()
    {
        $this->actingAs($this->adminUser);

        // 清除所有快取
        Cache::flush();

        $component = Livewire::test(PermissionList::class);

        // 第一次載入應該查詢資料庫
        $permissions = $component->get('permissions');
        $this->assertNotNull($permissions);

        // 檢查快取是否被設定
        $cacheKeys = [
            'permission_modules_list',
            'permission_types_list',
            'permission_stats'
        ];

        foreach ($cacheKeys as $key) {
            $this->assertTrue(Cache::has($key));
        }
    }

    /** @test */
    public function 惡意搜尋輸入被正確處理()
    {
        $this->actingAs($this->adminUser);

        $component = Livewire::test(PermissionList::class);

        // 模擬惡意輸入
        $maliciousInput = '<script>alert("xss")</script>';
        
        $component->set('search', $maliciousInput);

        // 搜尋應該被清空或清理
        $search = $component->get('search');
        $this->assertNotEquals($maliciousInput, $search);
    }

    /** @test */
    public function 無效的權限ID被正確處理()
    {
        $this->actingAs($this->adminUser);

        $component = Livewire::test(PermissionList::class);

        // 測試無效的權限 ID
        $component->call('editPermission', 'invalid_id')
                 ->assertDispatched('show-toast', [
                     'type' => 'error',
                     'message' => '無效的權限 ID'
                 ]);

        $component->call('viewDependencies', 'invalid_id')
                 ->assertDispatched('show-toast', [
                     'type' => 'error',
                     'message' => '無效的權限 ID'
                 ]);

        $component->call('deletePermission', 'invalid_id')
                 ->assertDispatched('show-toast', [
                     'type' => 'error',
                     'message' => '無效的權限 ID'
                 ]);
    }

    protected function tearDown(): void
    {
        Cache::flush();
        parent::tearDown();
    }
}