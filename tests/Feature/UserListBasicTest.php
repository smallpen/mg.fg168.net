<?php

namespace Tests\Feature;

use App\Livewire\Admin\Users\UserList;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * UserList 基本功能測試
 * 
 * 簡化的測試，專注於核心功能
 */
class UserListBasicTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // 建立測試角色
        $this->adminRole = Role::factory()->create(['name' => 'admin']);
        
        // 建立管理員使用者
        $this->admin = User::factory()->create();
        $this->admin->roles()->attach($this->adminRole);
        
        // 建立必要的權限
        $permissions = ['users.view', 'users.edit', 'users.delete'];
        foreach ($permissions as $permissionName) {
            $permission = \App\Models\Permission::factory()->create(['name' => $permissionName]);
            $this->adminRole->permissions()->attach($permission);
        }
    }

    /**
     * 測試元件基本渲染
     */
    public function test_component_renders_without_errors()
    {
        $this->actingAs($this->admin);

        $component = Livewire::test(UserList::class);
        
        $component->assertStatus(200)
                  ->assertSee('使用者管理');
    }

    /**
     * 測試搜尋功能
     */
    public function test_search_functionality()
    {
        $this->actingAs($this->admin);

        // 建立測試使用者
        $user = User::factory()->create(['name' => 'Test User']);

        $component = Livewire::test(UserList::class);
        
        $component->set('search', 'Test')
                  ->assertSee('Test User');
    }

    /**
     * 測試狀態篩選
     */
    public function test_status_filter()
    {
        $this->actingAs($this->admin);

        // 建立測試使用者
        $activeUser = User::factory()->create(['is_active' => true, 'name' => 'Active User']);
        $inactiveUser = User::factory()->create(['is_active' => false, 'name' => 'Inactive User']);

        $component = Livewire::test(UserList::class);
        
        // 測試啟用使用者篩選
        $component->set('statusFilter', 'active')
                  ->assertSee('Active User')
                  ->assertDontSee('Inactive User');
    }

    /**
     * 測試使用者狀態切換
     */
    public function test_toggle_user_status()
    {
        $this->actingAs($this->admin);

        $user = User::factory()->create(['is_active' => true]);

        $component = Livewire::test(UserList::class);
        
        $component->call('toggleUserStatus', $user->id)
                  ->assertDispatched('user-status-updated');
        
        // 檢查使用者狀態是否已更改
        $user->refresh();
        $this->assertFalse($user->is_active);
    }
}