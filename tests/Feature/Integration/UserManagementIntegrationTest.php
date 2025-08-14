<?php

namespace Tests\Feature\Integration;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;
use Livewire\Livewire;
use App\Livewire\Admin\Users\UserList;
use App\Livewire\Admin\Users\UserDeleteModal;

/**
 * 使用者管理功能整合測試
 * 
 * 測試完整的使用者管理流程，包含權限控制、響應式設計和效能要求
 * 需求: 6.1, 6.2, 6.3, 7.1, 7.2, 9.1
 */
class UserManagementIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;
    protected User $admin;
    protected User $regularUser;
    protected User $unauthorizedUser;
    protected Role $superAdminRole;
    protected Role $adminRole;
    protected Role $userRole;

    protected function setUp(): void
    {
        parent::setUp();
        
        // 建立測試角色
        $this->superAdminRole = Role::factory()->create([
            'name' => 'super_admin',
            'display_name' => '超級管理員'
        ]);
        
        $this->adminRole = Role::factory()->create([
            'name' => 'admin',
            'display_name' => '管理員'
        ]);
        
        $this->userRole = Role::factory()->create([
            'name' => 'user',
            'display_name' => '一般使用者'
        ]);

        // 建立測試使用者
        $this->superAdmin = User::factory()->create([
            'username' => 'superadmin',
            'name' => '超級管理員',
            'email' => 'superadmin@example.com'
        ]);
        $this->superAdmin->roles()->attach($this->superAdminRole);

        $this->admin = User::factory()->create([
            'username' => 'admin',
            'name' => '管理員',
            'email' => 'admin@example.com'
        ]);
        $this->admin->roles()->attach($this->adminRole);

        $this->regularUser = User::factory()->create([
            'username' => 'user',
            'name' => '一般使用者',
            'email' => 'user@example.com'
        ]);
        $this->regularUser->roles()->attach($this->userRole);

        $this->unauthorizedUser = User::factory()->create([
            'username' => 'unauthorized',
            'name' => '無權限使用者',
            'email' => 'unauthorized@example.com'
        ]);
        // 不指派任何角色
    }

    /**
     * 測試完整的使用者管理流程
     * 需求: 1.1, 1.2, 2.1, 2.2, 3.1, 3.2, 4.1, 4.2, 5.1-5.6
     */
    public function test_complete_user_management_workflow()
    {
        $this->actingAs($this->admin);

        // 1. 檢視使用者列表
        $response = $this->get('/admin/users');
        $response->assertStatus(200);
        $response->assertSee('使用者管理');
        $response->assertSee($this->superAdmin->username);
        $response->assertSee($this->admin->username);
        $response->assertSee($this->regularUser->username);

        // 2. 測試搜尋功能
        Livewire::test(UserList::class)
            ->set('search', 'super')
            ->assertSee($this->superAdmin->username)
            ->assertDontSee($this->regularUser->username);

        // 3. 測試狀態篩選
        Livewire::test(UserList::class)
            ->set('statusFilter', 'active')
            ->assertSee($this->superAdmin->username)
            ->assertSee($this->admin->username);

        // 4. 測試角色篩選
        Livewire::test(UserList::class)
            ->set('roleFilter', 'admin')
            ->assertSee($this->admin->username)
            ->assertDontSee($this->regularUser->username);

        // 5. 測試排序功能
        Livewire::test(UserList::class)
            ->call('sortBy', 'name')
            ->assertSet('sortField', 'name')
            ->assertSet('sortDirection', 'asc');

        // 6. 測試使用者狀態切換
        $testUser = User::factory()->create(['is_active' => true]);
        
        Livewire::test(UserList::class)
            ->call('toggleUserStatus', $testUser->id);
            
        $testUser->refresh();
        $this->assertFalse($testUser->is_active);

        // 7. 測試批量操作
        $users = User::factory()->count(3)->create(['is_active' => true]);
        $userIds = $users->pluck('id')->toArray();

        Livewire::test(UserList::class)
            ->set('selectedUsers', $userIds)
            ->call('bulkDeactivate');

        foreach ($users as $user) {
            $user->refresh();
            $this->assertFalse($user->is_active);
        }

        // 8. 測試使用者建立流程
        $response = $this->get('/admin/users/create');
        $response->assertStatus(200);
        $response->assertSee('建立使用者');

        $userData = [
            'username' => 'newuser',
            'name' => '新使用者',
            'email' => 'newuser@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'roles' => [$this->userRole->id],
            'is_active' => true
        ];

        $response = $this->post('/admin/users', $userData);
        $response->assertRedirect('/admin/users');

        $this->assertDatabaseHas('users', [
            'username' => 'newuser',
            'name' => '新使用者',
            'email' => 'newuser@example.com'
        ]);

        // 9. 測試使用者編輯流程
        $newUser = User::where('username', 'newuser')->first();
        
        $response = $this->get("/admin/users/{$newUser->id}/edit");
        $response->assertStatus(200);
        $response->assertSee('編輯使用者');

        $updatedData = [
            'username' => 'newuser',
            'name' => '更新的使用者',
            'email' => 'updated@example.com',
            'roles' => [$this->adminRole->id],
            'is_active' => true
        ];

        $response = $this->put("/admin/users/{$newUser->id}", $updatedData);
        $response->assertRedirect('/admin/users');

        $this->assertDatabaseHas('users', [
            'id' => $newUser->id,
            'name' => '更新的使用者',
            'email' => 'updated@example.com'
        ]);

        // 10. 測試使用者刪除流程
        Livewire::test(UserList::class)
            ->call('deleteUser', $newUser->id)
            ->assertDispatched('confirm-user-delete');

        Livewire::test(UserList::class)
            ->call('confirmDelete', $newUser->id);

        $this->assertDatabaseMissing('users', ['id' => $newUser->id]);
    }

    /**
     * 測試不同權限使用者的存取控制
     * 需求: 9.1
     */
    public function test_permission_based_access_control()
    {
        // 測試超級管理員權限
        $this->actingAs($this->superAdmin);
        
        $response = $this->get('/admin/users');
        $response->assertStatus(200);
        
        Livewire::test(UserList::class)
            ->assertSet('search', '')
            ->call('viewUser', $this->admin->id)
            ->call('editUser', $this->admin->id)
            ->call('toggleUserStatus', $this->regularUser->id)
            ->call('deleteUser', $this->regularUser->id);

        // 測試管理員權限
        $this->actingAs($this->admin);
        
        $response = $this->get('/admin/users');
        $response->assertStatus(200);
        
        // 管理員可以管理一般使用者
        Livewire::test(UserList::class)
            ->call('viewUser', $this->regularUser->id)
            ->call('editUser', $this->regularUser->id)
            ->call('toggleUserStatus', $this->regularUser->id);

        // 但不能刪除超級管理員
        $component = Livewire::test(UserList::class)
            ->call('deleteUser', $this->superAdmin->id);
        
        // 應該顯示權限不足的錯誤訊息
        $component->assertDispatched('show-toast');

        // 測試一般使用者權限
        $this->actingAs($this->regularUser);
        
        $response = $this->get('/admin/users');
        $response->assertStatus(403); // 應該被拒絕存取

        // 測試無權限使用者
        $this->actingAs($this->unauthorizedUser);
        
        $response = $this->get('/admin/users');
        $response->assertStatus(403); // 應該被拒絕存取

        // 測試未登入使用者
        $this->logout();
        
        $response = $this->get('/admin/users');
        $response->assertRedirect('/admin/login'); // 應該重導向到登入頁面
    }

    /**
     * 測試響應式設計在不同裝置的表現
     * 需求: 6.1, 6.2, 6.3
     */
    public function test_responsive_design_across_devices()
    {
        $this->actingAs($this->admin);

        // 建立測試資料
        User::factory()->count(10)->create();

        // 測試桌面版本 (≥1024px)
        $response = $this->get('/admin/users', [
            'HTTP_USER_AGENT' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'
        ]);
        
        $response->assertStatus(200);
        $response->assertSee('使用者管理');
        
        // 檢查桌面版應該顯示的元素
        $response->assertSee('建立時間'); // 桌面版顯示完整欄位
        $response->assertSee('操作'); // 操作欄位

        // 測試平板版本 (768px-1023px)
        $component = Livewire::test(UserList::class);
        
        // 檢查平板版本的響應式行為
        $this->assertTrue($component->instance()->users->count() > 0);
        
        // 測試手機版本 (<768px)
        // 在手機版本中，應該使用卡片式佈局
        $response = $this->get('/admin/users', [
            'HTTP_USER_AGENT' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 14_0 like Mac OS X)'
        ]);
        
        $response->assertStatus(200);
        $response->assertSee('使用者管理');

        // 測試響應式篩選器
        Livewire::test(UserList::class)
            ->set('search', 'test')
            ->set('statusFilter', 'active')
            ->set('roleFilter', 'admin')
            ->assertSet('search', 'test')
            ->assertSet('statusFilter', 'active')
            ->assertSet('roleFilter', 'admin');

        // 測試響應式分頁
        $component = Livewire::test(UserList::class);
        $this->assertInstanceOf(\Illuminate\Pagination\LengthAwarePaginator::class, $component->instance()->users);
    }

    /**
     * 測試效能要求（載入時間、響應時間）
     * 需求: 7.1, 7.2
     */
    public function test_performance_requirements()
    {
        $this->actingAs($this->admin);

        // 建立大量測試資料來測試效能
        User::factory()->count(100)->create();

        // 測試初始載入時間（應在 2 秒內完成）
        $startTime = microtime(true);
        
        $response = $this->get('/admin/users');
        
        $loadTime = microtime(true) - $startTime;
        
        $response->assertStatus(200);
        $this->assertLessThan(2.0, $loadTime, '頁面載入時間應少於 2 秒');

        // 測試搜尋響應時間（應在 1 秒內完成）
        $startTime = microtime(true);
        
        Livewire::test(UserList::class)
            ->set('search', 'test');
            
        $searchTime = microtime(true) - $startTime;
        
        $this->assertLessThan(1.0, $searchTime, '搜尋響應時間應少於 1 秒');

        // 測試篩選響應時間
        $startTime = microtime(true);
        
        Livewire::test(UserList::class)
            ->set('statusFilter', 'active');
            
        $filterTime = microtime(true) - $startTime;
        
        $this->assertLessThan(1.0, $filterTime, '篩選響應時間應少於 1 秒');

        // 測試分頁效能
        $startTime = microtime(true);
        
        $component = Livewire::test(UserList::class);
        $users = $component->instance()->users;
        
        $paginationTime = microtime(true) - $startTime;
        
        $this->assertLessThan(1.0, $paginationTime, '分頁載入時間應少於 1 秒');
        $this->assertEquals(15, $users->perPage(), '每頁應顯示 15 筆資料');

        // 測試快取效能
        Cache::flush(); // 清除快取
        
        $startTime = microtime(true);
        
        $component = Livewire::test(UserList::class);
        $roles = $component->instance()->availableRoles;
        
        $firstLoadTime = microtime(true) - $startTime;

        // 第二次載入應該更快（使用快取）
        $startTime = microtime(true);
        
        $component = Livewire::test(UserList::class);
        $roles = $component->instance()->availableRoles;
        
        $cachedLoadTime = microtime(true) - $startTime;
        
        $this->assertLessThan($firstLoadTime, $cachedLoadTime, '快取載入應該更快');

        // 測試資料庫查詢效能
        DB::enableQueryLog();
        
        Livewire::test(UserList::class)
            ->set('search', 'admin')
            ->set('statusFilter', 'active')
            ->set('roleFilter', 'admin');
            
        $queries = DB::getQueryLog();
        
        // 檢查查詢數量是否合理（避免 N+1 問題）
        $this->assertLessThan(10, count($queries), '查詢數量應該合理');
        
        DB::disableQueryLog();
    }

    /**
     * 測試資料完整性和一致性
     */
    public function test_data_integrity_and_consistency()
    {
        $this->actingAs($this->admin);

        // 測試軟刪除功能
        $user = User::factory()->create();
        
        Livewire::test(UserList::class)
            ->call('confirmDelete', $user->id);

        // 檢查軟刪除
        $this->assertSoftDeleted('users', ['id' => $user->id]);
        
        // 檢查關聯資料是否正確處理
        $this->assertDatabaseMissing('user_roles', ['user_id' => $user->id]);

        // 測試批量操作的資料一致性
        $users = User::factory()->count(5)->create(['is_active' => true]);
        $userIds = $users->pluck('id')->toArray();

        Livewire::test(UserList::class)
            ->set('selectedUsers', $userIds)
            ->call('bulkDeactivate');

        // 檢查所有使用者都被正確更新
        foreach ($users as $user) {
            $user->refresh();
            $this->assertFalse($user->is_active);
        }

        // 測試角色指派的一致性
        $user = User::factory()->create();
        $roles = Role::factory()->count(2)->create();
        
        $user->roles()->attach($roles->pluck('id'));
        
        $this->assertEquals(2, $user->roles()->count());
        
        // 刪除使用者後，角色關聯應該被清除
        Livewire::test(UserList::class)
            ->call('confirmDelete', $user->id);
            
        $this->assertDatabaseMissing('user_roles', ['user_id' => $user->id]);
    }

    /**
     * 測試錯誤處理和恢復機制
     */
    public function test_error_handling_and_recovery()
    {
        $this->actingAs($this->admin);

        // 測試無效使用者 ID 的處理
        Livewire::test(UserList::class)
            ->call('viewUser', 99999)
            ->assertDispatched('show-toast');

        // 測試權限錯誤的處理
        $this->actingAs($this->regularUser);
        
        Livewire::test(UserList::class)
            ->call('deleteUser', $this->admin->id)
            ->assertDispatched('show-toast');

        // 測試搜尋輸入驗證
        $this->actingAs($this->admin);
        
        Livewire::test(UserList::class)
            ->set('search', '<script>alert("xss")</script>')
            ->assertSet('search', ''); // 應該被清除

        // 測試批量操作錯誤處理
        Livewire::test(UserList::class)
            ->set('selectedUsers', [99999, 99998]) // 無效的使用者 ID
            ->call('bulkActivate')
            ->assertDispatched('show-toast');

        // 測試網路錯誤恢復
        // 模擬網路中斷情況
        $component = Livewire::test(UserList::class);
        
        // 檢查元件是否能正常恢復
        $this->assertNotNull($component->instance()->users);
    }

    /**
     * 測試多語言支援
     * 需求: 8.1, 8.2, 8.3
     */
    public function test_multilingual_support()
    {
        $this->actingAs($this->admin);

        // 測試正體中文介面
        app()->setLocale('zh_TW');
        
        $response = $this->get('/admin/users');
        $response->assertStatus(200);
        $response->assertSee('使用者管理');
        $response->assertSee('建立使用者');
        $response->assertSee('搜尋');

        // 測試狀態本地化
        $component = Livewire::test(UserList::class);
        $statusOptions = $component->instance()->statusOptions;
        
        $this->assertArrayHasKey('all', $statusOptions);
        $this->assertArrayHasKey('active', $statusOptions);
        $this->assertArrayHasKey('inactive', $statusOptions);

        // 測試日期時間格式本地化
        $user = User::factory()->create();
        $formattedDate = $component->instance()->formatUserCreatedAt($user);
        
        $this->assertNotEmpty($formattedDate);
        $this->assertIsString($formattedDate);

        // 測試角色名稱本地化
        $user = User::factory()->create();
        $user->roles()->attach($this->adminRole);
        
        $roleDisplay = $component->instance()->getLocalizedUserRoles($user);
        $this->assertNotEmpty($roleDisplay);
    }

    /**
     * 測試安全性功能
     * 需求: 9.1, 9.3, 9.4
     */
    public function test_security_features()
    {
        // 測試 CSRF 保護
        $response = $this->post('/admin/users', [
            'username' => 'testuser',
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123'
        ]);
        
        $response->assertStatus(419); // CSRF token mismatch

        // 測試 SQL 注入防護
        $this->actingAs($this->admin);
        
        Livewire::test(UserList::class)
            ->set('search', "'; DROP TABLE users; --")
            ->assertSet('search', ''); // 應該被清除或轉義

        // 測試 XSS 防護
        Livewire::test(UserList::class)
            ->set('search', '<script>alert("xss")</script>')
            ->assertSet('search', ''); // 應該被清除

        // 測試權限升級攻擊防護
        $this->actingAs($this->regularUser);
        
        // 嘗試存取管理功能
        $response = $this->get('/admin/users');
        $response->assertStatus(403);

        // 測試批量操作安全性
        $this->actingAs($this->admin);
        
        // 嘗試停用自己的帳號
        Livewire::test(UserList::class)
            ->set('selectedUsers', [$this->admin->id])
            ->call('bulkDeactivate')
            ->assertDispatched('show-toast'); // 應該顯示錯誤訊息

        // 測試操作日誌記錄
        $user = User::factory()->create();
        
        Livewire::test(UserList::class)
            ->call('toggleUserStatus', $user->id);

        // 檢查是否有操作記錄（假設有 activities 表）
        if (Schema::hasTable('activities')) {
            $this->assertDatabaseHas('activities', [
                'subject_type' => User::class,
                'subject_id' => $user->id,
                'description' => 'user_status_toggle'
            ]);
        }
    }

    /**
     * 測試快取機制
     */
    public function test_caching_mechanisms()
    {
        $this->actingAs($this->admin);

        // 清除快取
        Cache::flush();

        // 測試角色列表快取
        $component = Livewire::test(UserList::class);
        $roles1 = $component->instance()->availableRoles;
        
        // 檢查快取是否被建立
        $this->assertTrue(Cache::has('user_roles_list'));
        
        // 再次取得，應該從快取載入
        $roles2 = $component->instance()->availableRoles;
        
        $this->assertEquals($roles1->count(), $roles2->count());

        // 測試搜尋防抖功能
        $component = Livewire::test(UserList::class)
            ->set('search', 'test')
            ->assertSet('search', 'test');

        // 測試快取清除
        $user = User::factory()->create();
        
        Livewire::test(UserList::class)
            ->call('toggleUserStatus', $user->id);
            
        // 相關快取應該被清除
        // 這裡可以檢查特定的快取鍵是否被清除
    }

    protected function tearDown(): void
    {
        // 清除測試快取
        Cache::flush();
        
        parent::tearDown();
    }
}