<?php

namespace Tests\Unit;

use App\Models\User;
use App\Models\Role;
use App\Models\Permission;
use App\Services\PermissionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * 權限服務測試
 */
class PermissionServiceTest extends TestCase
{
    use RefreshDatabase;

    protected PermissionService $permissionService;
    protected User $user;
    protected Role $adminRole;
    protected Role $userRole;
    protected Permission $userManagePermission;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->permissionService = new PermissionService();
        
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

        // 建立權限
        $this->userManagePermission = Permission::create([
            'name' => 'user.manage',
            'display_name' => '使用者管理',
            'description' => '管理使用者帳號',
            'module' => 'user'
        ]);

        // 指派權限給角色
        $this->adminRole->permissions()->attach($this->userManagePermission);

        // 建立使用者
        $this->user = User::factory()->create();
    }

    /**
     * 測試權限檢查
     */
    public function test_has_permission(): void
    {
        // 使用者沒有角色時應該沒有權限
        $this->assertFalse($this->permissionService->hasPermission($this->user, 'user.manage'));

        // 指派角色後應該有權限
        $this->user->assignRole($this->adminRole);
        $this->assertTrue($this->permissionService->hasPermission($this->user, 'user.manage'));
    }
}