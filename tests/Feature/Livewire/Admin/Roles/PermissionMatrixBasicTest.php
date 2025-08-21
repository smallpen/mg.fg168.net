<?php

namespace Tests\Feature\Livewire\Admin\Roles;

use App\Livewire\Admin\Roles\PermissionMatrix;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * PermissionMatrix 基本功能測試
 */
class PermissionMatrixBasicTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // 建立測試角色和權限
        $this->adminRole = Role::factory()->create(['name' => 'admin']);
        $this->editorRole = Role::factory()->create(['name' => 'editor']);
        
        // 建立權限管理相關權限
        $this->viewPermission = Permission::factory()->create([
            'name' => 'roles.view',
            'display_name' => '檢視角色',
            'module' => 'roles'
        ]);
        
        $this->editPermission = Permission::factory()->create([
            'name' => 'roles.edit',
            'display_name' => '編輯角色',
            'module' => 'roles'
        ]);
        
        // 建立管理員使用者並給予權限
        $this->admin = User::factory()->create();
        $this->admin->roles()->attach($this->adminRole);
        $this->adminRole->permissions()->attach([$this->viewPermission->id, $this->editPermission->id]);
    }

    /**
     * 測試元件能正確渲染
     */
    public function test_component_renders_correctly()
    {
        $this->actingAs($this->admin);

        Livewire::test(PermissionMatrix::class)
            ->assertStatus(200)
            ->assertSee('權限矩陣');
    }

    /**
     * 測試權限檢查
     */
    public function test_unauthorized_access()
    {
        $unauthorizedUser = User::factory()->create();
        $this->actingAs($unauthorizedUser);

        Livewire::test(PermissionMatrix::class)
            ->assertStatus(403);
    }

    /**
     * 測試基本屬性
     */
    public function test_basic_properties()
    {
        $this->actingAs($this->admin);

        Livewire::test(PermissionMatrix::class)
            ->assertSet('search', '')
            ->assertSet('moduleFilter', '')
            ->assertSet('viewMode', 'matrix')
            ->assertSet('showDescriptions', false);
    }
}