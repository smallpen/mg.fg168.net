<?php

namespace Tests\Feature\Admin;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * 使用者管理功能測試
 * 
 * 測試使用者的建立、編輯、刪除和權限管理功能
 */
class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // 建立基本角色
        $this->adminRole = Role::factory()->create(['name' => 'admin']);
        $this->userRole = Role::factory()->create(['name' => 'user']);
        
        // 建立管理員使用者
        $this->admin = User::factory()->create();
        $this->admin->roles()->attach($this->adminRole);
    }

    /**
     * 測試管理員可以檢視使用者列表
     */
    public function test_admin_can_view_user_list()
    {
        $this->actingAs($this->admin);

        // 建立一些測試使用者
        $users = User::factory()->count(3)->create();

        $response = $this->get('/admin/users');
        $response->assertStatus(200);
        $response->assertSee('使用者管理');
        
        // 檢查使用者是否顯示在列表中
        foreach ($users as $user) {
            $response->assertSee($user->username);
        }
    }

    /**
     * 測試管理員可以建立新使用者
     */
    public function test_admin_can_create_new_user()
    {
        $this->actingAs($this->admin);

        // 訪問建立使用者頁面
        $response = $this->get('/admin/users/create');
        $response->assertStatus(200);
        $response->assertSee('建立使用者');

        // 提交建立使用者表單
        $userData = [
            'username' => 'newuser',
            'name' => 'New User',
            'email' => 'newuser@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'roles' => [$this->userRole->id],
            'is_active' => true
        ];

        $response = $this->post('/admin/users', $userData);
        $response->assertRedirect('/admin/users');

        // 檢查使用者是否被建立
        $this->assertDatabaseHas('users', [
            'username' => 'newuser',
            'name' => 'New User',
            'email' => 'newuser@example.com'
        ]);

        // 檢查角色是否被指派
        $newUser = User::where('username', 'newuser')->first();
        $this->assertTrue($newUser->hasRole('user'));
    }

    /**
     * 測試建立使用者時的驗證規則
     */
    public function test_user_creation_validation()
    {
        $this->actingAs($this->admin);

        // 測試必填欄位
        $response = $this->post('/admin/users', []);
        $response->assertSessionHasErrors(['username', 'name', 'password']);

        // 測試使用者名稱唯一性
        $existingUser = User::factory()->create(['username' => 'existing']);
        
        $response = $this->post('/admin/users', [
            'username' => 'existing',
            'name' => 'Test User',
            'password' => 'password123',
            'password_confirmation' => 'password123'
        ]);
        $response->assertSessionHasErrors(['username']);

        // 測試電子郵件格式
        $response = $this->post('/admin/users', [
            'username' => 'testuser',
            'name' => 'Test User',
            'email' => 'invalid-email',
            'password' => 'password123',
            'password_confirmation' => 'password123'
        ]);
        $response->assertSessionHasErrors(['email']);

        // 測試密碼確認
        $response = $this->post('/admin/users', [
            'username' => 'testuser',
            'name' => 'Test User',
            'password' => 'password123',
            'password_confirmation' => 'different'
        ]);
        $response->assertSessionHasErrors(['password']);
    }

    /**
     * 測試管理員可以編輯使用者
     */
    public function test_admin_can_edit_user()
    {
        $this->actingAs($this->admin);

        $user = User::factory()->create([
            'username' => 'testuser',
            'name' => 'Test User',
            'email' => 'test@example.com'
        ]);

        // 訪問編輯頁面
        $response = $this->get("/admin/users/{$user->id}/edit");
        $response->assertStatus(200);
        $response->assertSee('編輯使用者');
        $response->assertSee($user->username);

        // 提交編輯表單
        $updatedData = [
            'username' => 'testuser',
            'name' => 'Updated User',
            'email' => 'updated@example.com',
            'roles' => [$this->adminRole->id],
            'is_active' => true
        ];

        $response = $this->put("/admin/users/{$user->id}", $updatedData);
        $response->assertRedirect('/admin/users');

        // 檢查使用者資料是否被更新
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Updated User',
            'email' => 'updated@example.com'
        ]);

        // 檢查角色是否被更新
        $user->refresh();
        $this->assertTrue($user->hasRole('admin'));
    }

    /**
     * 測試管理員可以刪除使用者
     */
    public function test_admin_can_delete_user()
    {
        $this->actingAs($this->admin);

        $user = User::factory()->create();

        $response = $this->delete("/admin/users/{$user->id}");
        $response->assertRedirect('/admin/users');

        // 檢查使用者是否被刪除
        $this->assertDatabaseMissing('users', ['id' => $user->id]);
    }

    /**
     * 測試管理員無法刪除自己的帳號
     */
    public function test_admin_cannot_delete_own_account()
    {
        $this->actingAs($this->admin);

        $response = $this->delete("/admin/users/{$this->admin->id}");
        $response->assertSessionHasErrors();

        // 檢查管理員帳號仍然存在
        $this->assertDatabaseHas('users', ['id' => $this->admin->id]);
    }

    /**
     * 測試使用者狀態切換
     */
    public function test_admin_can_toggle_user_status()
    {
        $this->actingAs($this->admin);

        $user = User::factory()->create(['is_active' => true]);

        // 停用使用者
        $response = $this->patch("/admin/users/{$user->id}/toggle-status");
        $response->assertRedirect();

        $user->refresh();
        $this->assertFalse($user->is_active);

        // 啟用使用者
        $response = $this->patch("/admin/users/{$user->id}/toggle-status");
        $response->assertRedirect();

        $user->refresh();
        $this->assertTrue($user->is_active);
    }

    /**
     * 測試使用者搜尋功能
     */
    public function test_user_search_functionality()
    {
        $this->actingAs($this->admin);

        $user1 = User::factory()->create(['name' => 'John Doe', 'username' => 'johndoe']);
        $user2 = User::factory()->create(['name' => 'Jane Smith', 'username' => 'janesmith']);

        // 按姓名搜尋
        $response = $this->get('/admin/users?search=John');
        $response->assertStatus(200);
        $response->assertSee('John Doe');
        $response->assertDontSee('Jane Smith');

        // 按使用者名稱搜尋
        $response = $this->get('/admin/users?search=janesmith');
        $response->assertStatus(200);
        $response->assertSee('Jane Smith');
        $response->assertDontSee('John Doe');
    }

    /**
     * 測試角色篩選功能
     */
    public function test_user_role_filtering()
    {
        $this->actingAs($this->admin);

        $adminUser = User::factory()->create();
        $adminUser->roles()->attach($this->adminRole);

        $regularUser = User::factory()->create();
        $regularUser->roles()->attach($this->userRole);

        // 篩選管理員
        $response = $this->get('/admin/users?role=admin');
        $response->assertStatus(200);
        $response->assertSee($adminUser->username);
        $response->assertDontSee($regularUser->username);

        // 篩選一般使用者
        $response = $this->get('/admin/users?role=user');
        $response->assertStatus(200);
        $response->assertSee($regularUser->username);
        $response->assertDontSee($adminUser->username);
    }

    /**
     * 測試使用者詳情檢視
     */
    public function test_admin_can_view_user_details()
    {
        $this->actingAs($this->admin);

        $user = User::factory()->create();
        $user->roles()->attach($this->userRole);

        $response = $this->get("/admin/users/{$user->id}");
        $response->assertStatus(200);
        $response->assertSee($user->username);
        $response->assertSee($user->name);
        $response->assertSee($user->email);
        $response->assertSee($this->userRole->display_name);
    }

    /**
     * 測試批量操作功能
     */
    public function test_bulk_user_operations()
    {
        $this->actingAs($this->admin);

        $users = User::factory()->count(3)->create(['is_active' => true]);
        $userIds = $users->pluck('id')->toArray();

        // 批量停用
        $response = $this->post('/admin/users/bulk-action', [
            'action' => 'deactivate',
            'user_ids' => $userIds
        ]);

        $response->assertRedirect('/admin/users');

        // 檢查所有使用者都被停用
        foreach ($users as $user) {
            $user->refresh();
            $this->assertFalse($user->is_active);
        }
    }

    /**
     * 測試使用者匯出功能
     */
    public function test_user_export_functionality()
    {
        $this->actingAs($this->admin);

        User::factory()->count(5)->create();

        $response = $this->get('/admin/users/export');
        
        // 檢查是否返回 CSV 檔案
        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
    }

    /**
     * 測試使用者匯入功能
     */
    public function test_user_import_functionality()
    {
        $this->actingAs($this->admin);

        // 建立測試 CSV 檔案
        $csvContent = "username,name,email,password\n";
        $csvContent .= "import1,Import User 1,import1@example.com,password123\n";
        $csvContent .= "import2,Import User 2,import2@example.com,password123\n";

        $tempFile = tmpfile();
        fwrite($tempFile, $csvContent);
        $tempPath = stream_get_meta_data($tempFile)['uri'];

        $response = $this->post('/admin/users/import', [
            'file' => new \Illuminate\Http\UploadedFile($tempPath, 'users.csv', 'text/csv', null, true)
        ]);

        $response->assertRedirect('/admin/users');

        // 檢查使用者是否被匯入
        $this->assertDatabaseHas('users', ['username' => 'import1']);
        $this->assertDatabaseHas('users', ['username' => 'import2']);

        fclose($tempFile);
    }

    /**
     * 測試權限控制 - 無權限使用者
     */
    public function test_unauthorized_user_cannot_access_user_management()
    {
        $unauthorizedUser = User::factory()->create();
        $this->actingAs($unauthorizedUser);

        $response = $this->get('/admin/users');
        $response->assertStatus(403);

        $response = $this->get('/admin/users/create');
        $response->assertStatus(403);
    }

    /**
     * 測試使用者活動記錄
     */
    public function test_user_activity_logging()
    {
        $this->actingAs($this->admin);

        $user = User::factory()->create();

        // 編輯使用者
        $this->put("/admin/users/{$user->id}", [
            'username' => $user->username,
            'name' => 'Updated Name',
            'email' => $user->email,
            'is_active' => true
        ]);

        // 檢查活動記錄是否被建立
        $this->assertDatabaseHas('activities', [
            'subject_type' => User::class,
            'subject_id' => $user->id,
            'description' => 'updated'
        ]);
    }
}