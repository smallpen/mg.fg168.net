<?php

namespace Tests\Unit;

use App\Models\User;
use App\Models\Role;
use App\Models\Permission;
use App\Repositories\UserRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;
use Carbon\Carbon;

/**
 * 使用者資料存取層測試
 */
class UserRepositoryTest extends TestCase
{
    use RefreshDatabase;

    protected UserRepository $userRepository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->userRepository = new UserRepository();
    }

    /**
     * 測試建立使用者
     */
    public function test_create_user(): void
    {
        $userData = [
            'username' => 'testuser',
            'name' => '測試使用者',
            'email' => 'test@example.com',
            'password' => 'password123',
            'is_active' => true
        ];

        $user = $this->userRepository->create($userData);

        $this->assertInstanceOf(User::class, $user);
        $this->assertEquals('testuser', $user->username);
        $this->assertEquals('測試使用者', $user->name);
        $this->assertEquals('test@example.com', $user->email);
        $this->assertTrue($user->is_active);
        $this->assertTrue(Hash::check('password123', $user->password));
    }

    /**
     * 測試根據使用者名稱尋找使用者
     */
    public function test_find_by_username(): void
    {
        $user = User::create([
            'username' => 'admin',
            'name' => '管理員',
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
            'is_active' => true
        ]);

        $foundUser = $this->userRepository->findByUsername('admin');

        $this->assertNotNull($foundUser);
        $this->assertEquals($user->id, $foundUser->id);
        $this->assertEquals('admin', $foundUser->username);
    }

    /**
     * 測試根據電子郵件尋找使用者
     */
    public function test_find_by_email(): void
    {
        $user = User::create([
            'username' => 'testuser',
            'name' => '測試使用者',
            'email' => 'test@example.com',
            'password' => Hash::make('password'),
            'is_active' => true
        ]);

        $foundUser = $this->userRepository->findByEmail('test@example.com');

        $this->assertNotNull($foundUser);
        $this->assertEquals($user->id, $foundUser->id);
        $this->assertEquals('test@example.com', $foundUser->email);
    }

    /**
     * 測試檢查使用者名稱是否存在
     */
    public function test_username_exists(): void
    {
        User::create([
            'username' => 'existing_user',
            'name' => '已存在使用者',
            'email' => 'existing@example.com',
            'password' => Hash::make('password'),
            'is_active' => true
        ]);

        $this->assertTrue($this->userRepository->usernameExists('existing_user'));
        $this->assertFalse($this->userRepository->usernameExists('non_existing_user'));
    }

    /**
     * 測試檢查電子郵件是否存在
     */
    public function test_email_exists(): void
    {
        User::create([
            'username' => 'testuser',
            'name' => '測試使用者',
            'email' => 'existing@example.com',
            'password' => Hash::make('password'),
            'is_active' => true
        ]);

        $this->assertTrue($this->userRepository->emailExists('existing@example.com'));
        $this->assertFalse($this->userRepository->emailExists('non_existing@example.com'));
    }

    /**
     * 測試更新使用者
     */
    public function test_update_user(): void
    {
        $user = User::create([
            'username' => 'testuser',
            'name' => '測試使用者',
            'email' => 'test@example.com',
            'password' => Hash::make('oldpassword'),
            'is_active' => true
        ]);

        $updateData = [
            'name' => '更新後的使用者',
            'email' => 'updated@example.com',
            'password' => 'newpassword123'
        ];

        $result = $this->userRepository->update($user, $updateData);

        $this->assertTrue($result);
        
        $user->refresh();
        $this->assertEquals('更新後的使用者', $user->name);
        $this->assertEquals('updated@example.com', $user->email);
        $this->assertTrue(Hash::check('newpassword123', $user->password));
    }

    /**
     * 測試更新使用者時不覆蓋空密碼
     */
    public function test_update_user_without_password(): void
    {
        $originalPassword = Hash::make('originalpassword');
        $user = User::create([
            'username' => 'testuser',
            'name' => '測試使用者',
            'email' => 'test@example.com',
            'password' => $originalPassword,
            'is_active' => true
        ]);

        $updateData = [
            'name' => '更新後的使用者',
            'password' => '' // 空密碼應該被忽略
        ];

        $result = $this->userRepository->update($user, $updateData);

        $this->assertTrue($result);
        
        $user->refresh();
        $this->assertEquals('更新後的使用者', $user->name);
        $this->assertEquals($originalPassword, $user->password); // 密碼應該保持不變
    }

    /**
     * 測試停用使用者
     */
    public function test_deactivate_user(): void
    {
        $user = User::create([
            'username' => 'testuser',
            'name' => '測試使用者',
            'email' => 'test@example.com',
            'password' => Hash::make('password'),
            'is_active' => true
        ]);

        $result = $this->userRepository->deactivate($user);

        $this->assertTrue($result);
        
        $user->refresh();
        $this->assertFalse($user->is_active);
    }

    /**
     * 測試啟用使用者
     */
    public function test_activate_user(): void
    {
        $user = User::create([
            'username' => 'testuser',
            'name' => '測試使用者',
            'email' => 'test@example.com',
            'password' => Hash::make('password'),
            'is_active' => false
        ]);

        $result = $this->userRepository->activate($user);

        $this->assertTrue($result);
        
        $user->refresh();
        $this->assertTrue($user->is_active);
    }

    /**
     * 測試搜尋使用者
     */
    public function test_search_users(): void
    {
        User::create([
            'username' => 'john_doe',
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => Hash::make('password'),
            'is_active' => true
        ]);

        User::create([
            'username' => 'jane_smith',
            'name' => 'Jane Smith',
            'email' => 'jane@example.com',
            'password' => Hash::make('password'),
            'is_active' => true
        ]);

        // 搜尋使用者名稱
        $results = $this->userRepository->search('john');
        $this->assertCount(1, $results);
        $this->assertEquals('john_doe', $results->first()->username);

        // 搜尋姓名
        $results = $this->userRepository->search('Jane');
        $this->assertCount(1, $results);
        $this->assertEquals('jane_smith', $results->first()->username);

        // 搜尋電子郵件
        $results = $this->userRepository->search('john@example.com');
        $this->assertCount(1, $results);
        $this->assertEquals('john_doe', $results->first()->username);
    }

    /**
     * 測試根據角色取得使用者
     */
    public function test_get_users_by_role(): void
    {
        // 建立角色
        $adminRole = Role::create([
            'name' => 'admin',
            'display_name' => '管理員',
            'description' => '管理員角色'
        ]);

        $userRole = Role::create([
            'name' => 'user',
            'display_name' => '一般使用者',
            'description' => '一般使用者角色'
        ]);

        // 建立使用者
        $admin = User::create([
            'username' => 'admin',
            'name' => '管理員',
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
            'is_active' => true
        ]);

        $user = User::create([
            'username' => 'user',
            'name' => '一般使用者',
            'email' => 'user@example.com',
            'password' => Hash::make('password'),
            'is_active' => true
        ]);

        // 指派角色
        $admin->roles()->attach($adminRole->id);
        $user->roles()->attach($userRole->id);

        // 測試根據角色取得使用者
        $adminUsers = $this->userRepository->getByRole('admin');
        $this->assertCount(1, $adminUsers);
        $this->assertEquals('admin', $adminUsers->first()->username);

        $regularUsers = $this->userRepository->getByRole('user');
        $this->assertCount(1, $regularUsers);
        $this->assertEquals('user', $regularUsers->first()->username);
    }

    /**
     * 測試取得啟用的使用者
     */
    public function test_get_active_users(): void
    {
        User::create([
            'username' => 'active_user',
            'name' => '啟用使用者',
            'email' => 'active@example.com',
            'password' => Hash::make('password'),
            'is_active' => true
        ]);

        User::create([
            'username' => 'inactive_user',
            'name' => '停用使用者',
            'email' => 'inactive@example.com',
            'password' => Hash::make('password'),
            'is_active' => false
        ]);

        $activeUsers = $this->userRepository->getActiveUsers();
        
        $this->assertCount(1, $activeUsers);
        $this->assertEquals('active_user', $activeUsers->first()->username);
        $this->assertTrue($activeUsers->first()->is_active);
    }

    /**
     * 測試取得停用的使用者
     */
    public function test_get_inactive_users(): void
    {
        User::create([
            'username' => 'active_user',
            'name' => '啟用使用者',
            'email' => 'active@example.com',
            'password' => Hash::make('password'),
            'is_active' => true
        ]);

        User::create([
            'username' => 'inactive_user',
            'name' => '停用使用者',
            'email' => 'inactive@example.com',
            'password' => Hash::make('password'),
            'is_active' => false
        ]);

        $inactiveUsers = $this->userRepository->getInactiveUsers();
        
        $this->assertCount(1, $inactiveUsers);
        $this->assertEquals('inactive_user', $inactiveUsers->first()->username);
        $this->assertFalse($inactiveUsers->first()->is_active);
    }

    /**
     * 測試取得最近註冊的使用者
     */
    public function test_get_recent_users(): void
    {
        // 建立舊使用者（超過 30 天）
        $oldUser = User::create([
            'username' => 'old_user',
            'name' => '舊使用者',
            'email' => 'old@example.com',
            'password' => Hash::make('password'),
            'is_active' => true,
            'created_at' => Carbon::now()->subDays(35)
        ]);

        // 建立新使用者（最近 30 天內）
        $newUser = User::create([
            'username' => 'new_user',
            'name' => '新使用者',
            'email' => 'new@example.com',
            'password' => Hash::make('password'),
            'is_active' => true,
            'created_at' => Carbon::now()->subDays(5)
        ]);

        $recentUsers = $this->userRepository->getRecentUsers(10, 30);
        
        $this->assertCount(1, $recentUsers);
        $this->assertEquals('new_user', $recentUsers->first()->username);
    }

    /**
     * 測試取得使用者統計資訊
     */
    public function test_get_stats(): void
    {
        // 建立角色
        $adminRole = Role::create([
            'name' => 'admin',
            'display_name' => '管理員',
            'description' => '管理員角色'
        ]);

        // 建立使用者
        $activeUser = User::create([
            'username' => 'active_user',
            'name' => '啟用使用者',
            'email' => 'active@example.com',
            'password' => Hash::make('password'),
            'is_active' => true
        ]);

        $inactiveUser = User::create([
            'username' => 'inactive_user',
            'name' => '停用使用者',
            'email' => 'inactive@example.com',
            'password' => Hash::make('password'),
            'is_active' => false
        ]);

        $userWithoutRole = User::create([
            'username' => 'no_role_user',
            'name' => '無角色使用者',
            'email' => 'norole@example.com',
            'password' => Hash::make('password'),
            'is_active' => true
        ]);

        // 指派角色
        $activeUser->roles()->attach($adminRole->id);

        $stats = $this->userRepository->getStats();

        $this->assertEquals(3, $stats['total_users']);
        $this->assertEquals(2, $stats['active_users']);
        $this->assertEquals(1, $stats['inactive_users']);
        $this->assertEquals(2, $stats['users_without_roles']); // inactiveUser 和 userWithoutRole
        $this->assertArrayHasKey('users_by_role', $stats);
        $this->assertArrayHasKey('activity_rate', $stats);
    }

    /**
     * 測試批量更新使用者狀態
     */
    public function test_bulk_update_status(): void
    {
        $user1 = User::create([
            'username' => 'user1',
            'name' => '使用者1',
            'email' => 'user1@example.com',
            'password' => Hash::make('password'),
            'is_active' => true
        ]);

        $user2 = User::create([
            'username' => 'user2',
            'name' => '使用者2',
            'email' => 'user2@example.com',
            'password' => Hash::make('password'),
            'is_active' => true
        ]);

        $updatedCount = $this->userRepository->bulkUpdateStatus(
            [$user1->id, $user2->id], 
            false
        );

        $this->assertEquals(2, $updatedCount);
        
        $user1->refresh();
        $user2->refresh();
        
        $this->assertFalse($user1->is_active);
        $this->assertFalse($user2->is_active);
    }

    /**
     * 測試重設使用者密碼
     */
    public function test_reset_password(): void
    {
        $user = User::create([
            'username' => 'testuser',
            'name' => '測試使用者',
            'email' => 'test@example.com',
            'password' => Hash::make('oldpassword'),
            'is_active' => true
        ]);

        $result = $this->userRepository->resetPassword($user, 'newpassword123');

        $this->assertTrue($result);
        
        $user->refresh();
        $this->assertTrue(Hash::check('newpassword123', $user->password));
        $this->assertFalse(Hash::check('oldpassword', $user->password));
    }

    /**
     * 測試更新使用者偏好設定
     */
    public function test_update_preferences(): void
    {
        $user = User::create([
            'username' => 'testuser',
            'name' => '測試使用者',
            'email' => 'test@example.com',
            'password' => Hash::make('password'),
            'is_active' => true,
            'theme_preference' => 'light',
            'locale' => 'zh_TW'
        ]);

        $preferences = [
            'theme_preference' => 'dark',
            'locale' => 'en',
            'invalid_field' => 'should_be_ignored' // 這個欄位應該被忽略
        ];

        $result = $this->userRepository->updatePreferences($user, $preferences);

        $this->assertTrue($result);
        
        $user->refresh();
        $this->assertEquals('dark', $user->theme_preference);
        $this->assertEquals('en', $user->locale);
        $this->assertNull($user->invalid_field ?? null);
    }

    /**
     * 測試分頁功能
     */
    public function test_paginate_users(): void
    {
        // 建立多個使用者
        for ($i = 1; $i <= 25; $i++) {
            User::create([
                'username' => "user{$i}",
                'name' => "使用者{$i}",
                'email' => "user{$i}@example.com",
                'password' => Hash::make('password'),
                'is_active' => true
            ]);
        }

        $paginatedUsers = $this->userRepository->paginate(10);

        $this->assertEquals(25, $paginatedUsers->total());
        $this->assertEquals(10, $paginatedUsers->perPage());
        $this->assertEquals(3, $paginatedUsers->lastPage());
        $this->assertCount(10, $paginatedUsers->items());
    }

    /**
     * 測試分頁搜尋功能
     */
    public function test_paginate_with_search_filter(): void
    {
        User::create([
            'username' => 'john_doe',
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => Hash::make('password'),
            'is_active' => true
        ]);

        User::create([
            'username' => 'jane_smith',
            'name' => 'Jane Smith',
            'email' => 'jane@example.com',
            'password' => Hash::make('password'),
            'is_active' => true
        ]);

        $filters = ['search' => 'john'];
        $paginatedUsers = $this->userRepository->paginate(10, $filters);

        $this->assertEquals(1, $paginatedUsers->total());
        $this->assertEquals('john_doe', $paginatedUsers->items()[0]->username);
    }

    /**
     * 測試刪除使用者
     */
    public function test_delete_user(): void
    {
        $role = Role::create([
            'name' => 'test_role',
            'display_name' => '測試角色',
            'description' => '測試角色'
        ]);

        $user = User::create([
            'username' => 'testuser',
            'name' => '測試使用者',
            'email' => 'test@example.com',
            'password' => Hash::make('password'),
            'is_active' => true
        ]);

        // 指派角色
        $user->roles()->attach($role->id);

        $result = $this->userRepository->delete($user);

        $this->assertTrue($result);
        $this->assertDatabaseMissing('users', ['id' => $user->id]);
        $this->assertDatabaseMissing('user_roles', ['user_id' => $user->id]);
    }
}