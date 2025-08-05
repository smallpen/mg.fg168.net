<?php

namespace Tests\Unit;

use App\Models\User;
use App\Models\Role;
use App\Models\Permission;
use App\Services\UserService;
use App\Services\PermissionService;
use App\Services\DashboardService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

/**
 * 使用者服務測試
 */
class UserServiceTest extends TestCase
{
    use RefreshDatabase;

    protected UserService $userService;
    protected PermissionService $permissionService;
    protected DashboardService $dashboardService;
    protected User $testUser;
    protected User $adminUser;
    protected Role $adminRole;
    protected Role $userRole;

    protected function setUp(): void
    {
        parent::setUp();
        
        // 建立服務實例
        $this->permissionService = app(PermissionService::class);
        $this->dashboardService = app(DashboardService::class);
        $this->userService = new UserService($this->permissionService, $this->dashboardService);
        
        // 建立測試資料
        $this->createTestData();
    }

    /**
     * 建立測試資料
     */
    private function createTestData(): void
    {
        // 建立角色
        $this->adminRole = Role::create([
            'name' => 'admin',
            'display_name' => '管理員',
            'description' => '一般管理員'
        ]);

        $this->userRole = Role::create([
            'name' => 'user',
            'display_name' => '一般使用者',
            'description' => '一般使用者'
        ]);

        Role::create([
            'name' => 'super_admin',
            'display_name' => '超級管理員',
            'description' => '超級管理員'
        ]);

        // 建立權限
        $permission = Permission::create([
            'name' => 'user.manage',
            'display_name' => '使用者管理',
            'description' => '管理使用者帳號',
            'module' => 'user'
        ]);

        // 指派權限給角色
        $this->adminRole->permissions()->attach($permission);

        // 建立測試使用者
        $this->testUser = User::factory()->create([
            'username' => 'testuser',
            'name' => '測試使用者',
            'email' => 'test@example.com',
            'is_active' => true
        ]);

        // 建立管理員使用者
        $this->adminUser = User::factory()->create([
            'username' => 'admin',
            'name' => '管理員',
            'email' => 'admin@example.com',
            'is_active' => true
        ]);
        $this->adminUser->assignRole($this->adminRole);

        // 設定當前認證使用者
        $this->actingAs($this->adminUser);
    }

    /**
     * 測試建立使用者成功
     */
    public function test_create_user_successfully(): void
    {
        $userData = [
            'username' => 'newuser',
            'name' => '新使用者',
            'email' => 'newuser@example.com',
            'password' => 'Password123',
            'theme_preference' => 'dark',
            'locale' => 'en',
            'is_active' => true,
            'roles' => ['user']
        ];

        $user = $this->userService->createUser($userData);

        $this->assertInstanceOf(User::class, $user);
        $this->assertEquals('newuser', $user->username);
        $this->assertEquals('新使用者', $user->name);
        $this->assertEquals('newuser@example.com', $user->email);
        $this->assertEquals('dark', $user->theme_preference);
        $this->assertEquals('en', $user->locale);
        $this->assertTrue($user->is_active);
        $this->assertTrue($user->hasRole('user'));
        $this->assertTrue(Hash::check('Password123', $user->password));
    }

    /**
     * 測試建立使用者時驗證失敗
     */
    public function test_create_user_validation_fails(): void
    {
        $this->expectException(ValidationException::class);

        $userData = [
            'username' => 'ab', // 太短
            'name' => '',       // 必填
            'email' => 'invalid-email', // 無效格式
            'password' => '123', // 太短
        ];

        $this->userService->createUser($userData);
    }

    /**
     * 測試建立使用者時使用者名稱重複
     */
    public function test_create_user_duplicate_username(): void
    {
        $this->expectException(ValidationException::class);

        $userData = [
            'username' => 'testuser', // 已存在
            'name' => '新使用者',
            'password' => 'Password123',
        ];

        $this->userService->createUser($userData);
    }

    /**
     * 測試更新使用者成功
     */
    public function test_update_user_successfully(): void
    {
        $updateData = [
            'name' => '更新後的名稱',
            'email' => 'updated@example.com',
            'theme_preference' => 'dark',
            'locale' => 'en',
            'roles' => ['admin']
        ];

        $updatedUser = $this->userService->updateUser($this->testUser, $updateData);

        $this->assertEquals('更新後的名稱', $updatedUser->name);
        $this->assertEquals('updated@example.com', $updatedUser->email);
        $this->assertEquals('dark', $updatedUser->theme_preference);
        $this->assertEquals('en', $updatedUser->locale);
        $this->assertTrue($updatedUser->hasRole('admin'));
    }

    /**
     * 測試更新使用者密碼
     */
    public function test_update_user_password(): void
    {
        $updateData = [
            'password' => 'NewPassword123'
        ];

        $updatedUser = $this->userService->updateUser($this->testUser, $updateData);

        $this->assertTrue(Hash::check('NewPassword123', $updatedUser->password));
    }

    /**
     * 測試軟刪除使用者
     */
    public function test_delete_user_soft_delete(): void
    {
        $result = $this->userService->deleteUser($this->testUser, false);

        $this->assertTrue($result);
        $this->testUser->refresh();
        $this->assertFalse($this->testUser->is_active);
    }

    /**
     * 測試無法刪除超級管理員
     */
    public function test_cannot_delete_super_admin(): void
    {
        $superAdmin = User::factory()->create();
        $superAdmin->assignRole('super_admin');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot delete super admin user');

        $this->userService->deleteUser($superAdmin);
    }

    /**
     * 測試無法刪除當前使用者
     */
    public function test_cannot_delete_current_user(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot delete current user');

        $this->userService->deleteUser($this->adminUser);
    }

    /**
     * 測試啟用使用者
     */
    public function test_activate_user(): void
    {
        $this->testUser->update(['is_active' => false]);

        $result = $this->userService->activateUser($this->testUser);

        $this->assertTrue($result);
        $this->testUser->refresh();
        $this->assertTrue($this->testUser->is_active);
    }

    /**
     * 測試停用使用者
     */
    public function test_deactivate_user(): void
    {
        $result = $this->userService->deactivateUser($this->testUser);

        $this->assertTrue($result);
        $this->testUser->refresh();
        $this->assertFalse($this->testUser->is_active);
    }

    /**
     * 測試無法停用超級管理員
     */
    public function test_cannot_deactivate_super_admin(): void
    {
        $superAdmin = User::factory()->create();
        $superAdmin->assignRole('super_admin');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot deactivate super admin user');

        $this->userService->deactivateUser($superAdmin);
    }

    /**
     * 測試無法停用當前使用者
     */
    public function test_cannot_deactivate_current_user(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot deactivate current user');

        $this->userService->deactivateUser($this->adminUser);
    }

    /**
     * 測試為使用者指派角色
     */
    public function test_assign_roles_to_user(): void
    {
        $result = $this->userService->assignRolesToUser($this->testUser, ['admin', 'user']);

        $this->assertTrue($result);
        $this->assertTrue($this->testUser->hasRole('admin'));
        $this->assertTrue($this->testUser->hasRole('user'));
    }

    /**
     * 測試同步使用者角色
     */
    public function test_sync_user_roles(): void
    {
        // 先指派一些角色
        $this->testUser->assignRole($this->adminRole);
        $this->testUser->assignRole($this->userRole);

        // 同步為只有 user 角色
        $result = $this->userService->syncUserRoles($this->testUser, ['user']);

        $this->assertTrue($result);
        $this->assertFalse($this->testUser->hasRole('admin'));
        $this->assertTrue($this->testUser->hasRole('user'));
    }

    /**
     * 測試移除使用者角色
     */
    public function test_remove_role_from_user(): void
    {
        $this->testUser->assignRole($this->adminRole);
        $this->assertTrue($this->testUser->hasRole('admin'));

        $result = $this->userService->removeRoleFromUser($this->testUser, 'admin');

        $this->assertTrue($result);
        $this->assertFalse($this->testUser->hasRole('admin'));
    }

    /**
     * 測試檢查使用者權限
     */
    public function test_user_has_permission(): void
    {
        $this->testUser->assignRole($this->adminRole);

        $hasPermission = $this->userService->userHasPermission($this->testUser, 'user.manage');

        $this->assertTrue($hasPermission);
    }

    /**
     * 測試檢查使用者模組存取權限
     */
    public function test_user_can_access_module(): void
    {
        $this->testUser->assignRole($this->adminRole);

        $canAccess = $this->userService->userCanAccessModule($this->testUser, 'user');

        $this->assertTrue($canAccess);
    }

    /**
     * 測試取得使用者權限
     */
    public function test_get_user_permissions(): void
    {
        $this->testUser->assignRole($this->adminRole);

        $permissions = $this->userService->getUserPermissions($this->testUser);

        $this->assertCount(1, $permissions);
        $this->assertEquals('user.manage', $permissions->first()->name);
    }

    /**
     * 測試重設使用者密碼
     */
    public function test_reset_user_password(): void
    {
        $newPassword = 'NewPassword123';

        $result = $this->userService->resetUserPassword($this->testUser, $newPassword);

        $this->assertTrue($result);
        $this->testUser->refresh();
        $this->assertTrue(Hash::check($newPassword, $this->testUser->password));
    }

    /**
     * 測試重設密碼時密碼強度不足
     */
    public function test_reset_password_weak_password(): void
    {
        $this->expectException(ValidationException::class);

        $this->userService->resetUserPassword($this->testUser, '123456'); // 沒有大小寫字母
    }

    /**
     * 測試更新使用者偏好設定
     */
    public function test_update_user_preferences(): void
    {
        $preferences = [
            'theme_preference' => 'dark',
            'locale' => 'en'
        ];

        $result = $this->userService->updateUserPreferences($this->testUser, $preferences);

        $this->assertTrue($result);
        $this->testUser->refresh();
        $this->assertEquals('dark', $this->testUser->theme_preference);
        $this->assertEquals('en', $this->testUser->locale);
    }

    /**
     * 測試驗證使用者建立規則
     */
    public function test_validate_user_creation_rules(): void
    {
        $data = [
            'username' => 'ab', // 太短
            'roles' => ['nonexistent_role'] // 不存在的角色
        ];

        $errors = $this->userService->validateUserCreationRules($data);

        $this->assertArrayHasKey('username', $errors);
        $this->assertArrayHasKey('roles', $errors);
        $this->assertStringContainsString('至少需要 3 個字元', $errors['username'][0]);
        $this->assertStringContainsString('不存在', $errors['roles'][0]);
    }

    /**
     * 測試取得使用者刪除依賴檢查
     */
    public function test_get_user_deletion_dependencies(): void
    {
        // 測試超級管理員
        $superAdmin = User::factory()->create();
        $superAdmin->assignRole('super_admin');

        $dependencies = $this->userService->getUserDeletionDependencies($superAdmin);

        $this->assertArrayHasKey('super_admin', $dependencies);
        $this->assertEquals('無法刪除超級管理員帳號', $dependencies['super_admin']);

        // 測試當前使用者
        $dependencies = $this->userService->getUserDeletionDependencies($this->adminUser);

        $this->assertArrayHasKey('current_user', $dependencies);
        $this->assertEquals('無法刪除當前登入的使用者', $dependencies['current_user']);
    }

    /**
     * 測試密碼驗證規則
     */
    public function test_password_validation(): void
    {
        // 測試有效密碼
        $validData = [
            'username' => 'validuser',
            'name' => '有效使用者',
            'password' => 'ValidPassword123'
        ];

        $user = $this->userService->createUser($validData);
        $this->assertInstanceOf(User::class, $user);

        // 測試無效密碼（沒有大寫字母）
        $this->expectException(ValidationException::class);

        $invalidData = [
            'username' => 'invaliduser',
            'name' => '無效使用者',
            'password' => 'invalidpassword123'
        ];

        $this->userService->createUser($invalidData);
    }

    /**
     * 測試使用者名稱格式驗證
     */
    public function test_username_format_validation(): void
    {
        // 測試包含特殊字元的使用者名稱
        $this->expectException(ValidationException::class);

        $invalidData = [
            'username' => 'user@name', // 包含 @
            'name' => '測試使用者',
            'password' => 'Password123'
        ];

        $this->userService->createUser($invalidData);
    }

    /**
     * 測試建立使用者時的日誌記錄
     */
    public function test_create_user_logging(): void
    {
        Log::shouldReceive('info')
            ->once()
            ->with('User created successfully', \Mockery::type('array'));

        $userData = [
            'username' => 'logtest',
            'name' => '日誌測試',
            'password' => 'Password123'
        ];

        $this->userService->createUser($userData);
    }

    /**
     * 測試更新不存在的使用者
     */
    public function test_update_nonexistent_user(): void
    {
        $nonexistentUser = new User();
        $nonexistentUser->id = 99999;

        $this->expectException(\Exception::class);

        $this->userService->updateUser($nonexistentUser, ['name' => '測試']);
    }
}