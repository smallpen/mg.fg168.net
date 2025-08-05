<?php

namespace Tests\Unit;

use App\Models\Role;
use App\Models\Permission;
use App\Models\User;
use App\Repositories\RoleRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * 角色資料存取層測試
 */
class RoleRepositoryTest extends TestCase
{
    use RefreshDatabase;

    protected RoleRepository $roleRepository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->roleRepository = new RoleRepository();
    }

    /**
     * 測試建立角色
     */
    public function test_create_role(): void
    {
        $roleData = [
            'name' => 'test_role',
            'display_name' => '測試角色',
            'description' => '這是一個測試角色'
        ];

        $role = $this->roleRepository->create($roleData);

        $this->assertInstanceOf(Role::class, $role);
        $this->assertEquals('test_role', $role->name);
        $this->assertEquals('測試角色', $role->display_name);
    }

    /**
     * 測試根據名稱尋找角色
     */
    public function test_find_by_name(): void
    {
        $role = Role::create([
            'name' => 'admin',
            'display_name' => '管理員',
            'description' => '管理員角色'
        ]);

        $foundRole = $this->roleRepository->findByName('admin');

        $this->assertNotNull($foundRole);
        $this->assertEquals($role->id, $foundRole->id);
    }

    /**
     * 測試檢查角色名稱是否存在
     */
    public function test_name_exists(): void
    {
        Role::create([
            'name' => 'existing_role',
            'display_name' => '已存在角色',
            'description' => '已存在的角色'
        ]);

        $this->assertTrue($this->roleRepository->nameExists('existing_role'));
        $this->assertFalse($this->roleRepository->nameExists('non_existing_role'));
    }
}