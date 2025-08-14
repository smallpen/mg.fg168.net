<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\PermissionService;
use App\Models\User;
use App\Models\Role;
use App\Models\Permission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;

class PermissionServiceTest extends TestCase
{
    use RefreshDatabase;

    protected PermissionService $permissionService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->permissionService = app(PermissionService::class);
    }

    /** @test */
    public function it_can_check_user_permissions()
    {
        // 建立測試資料
        $user = User::factory()->create(['is_active' => true]);
        $role = Role::factory()->create(['name' => 'admin']);
        $permission = Permission::factory()->create(['name' => 'users.view']);
        
        $role->permissions()->attach($permission);
        $user->roles()->attach($role);

        Auth::login($user);

        // 測試權限檢查
        $this->assertTrue($this->permissionService->hasPermission('users.view'));
        $this->assertFalse($this->permissionService->hasPermission('users.delete'));
    }

    /** @test */
    public function it_denies_permission_for_inactive_users()
    {
        // 建立停用的使用者
        $user = User::factory()->create(['is_active' => false]);
        $role = Role::factory()->create(['name' => 'admin']);
        $permission = Permission::factory()->create(['name' => 'users.view']);
        
        $role->permissions()->attach($permission);
        $user->roles()->attach($role);

        Auth::login($user);

        // 停用使用者應該沒有任何權限
        $this->assertFalse($this->permissionService->hasPermission('users.view'));
    }

    /** @test */
    public function it_can_check_action_permissions_on_users()
    {
        $currentUser = User::factory()->create(['is_active' => true]);
        $targetUser = User::factory()->create(['is_active' => true]);
        
        $role = Role::factory()->create(['name' => 'admin']);
        $permission = Permission::factory()->create(['name' => 'users.edit']);
        
        $role->permissions()->attach($permission);
        $currentUser->roles()->attach($role);

        Auth::login($currentUser);

        // 應該可以編輯其他使用者
        $this->assertTrue($this->permissionService->canPerformActionOnUser('users.edit', $targetUser));
        
        // 不應該可以刪除自己
        $this->assertFalse($this->permissionService->canPerformActionOnUser('users.delete', $currentUser));
    }

    /** @test */
    public function it_prevents_non_super_admin_from_modifying_super_admin()
    {
        $adminUser = User::factory()->create(['is_active' => true]);
        $superAdminUser = User::factory()->create(['is_active' => true]);
        
        $adminRole = Role::factory()->create(['name' => 'admin']);
        $superAdminRole = Role::factory()->create(['name' => 'super_admin']);
        $permission = Permission::factory()->create(['name' => 'users.edit']);
        
        $adminRole->permissions()->attach($permission);
        $adminUser->roles()->attach($adminRole);
        $superAdminUser->roles()->attach($superAdminRole);

        Auth::login($adminUser);

        // 一般管理員不應該能修改超級管理員
        $this->assertFalse($this->permissionService->canPerformActionOnUser('users.edit', $superAdminUser));
    }
}