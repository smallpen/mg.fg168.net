<?php

namespace Tests\Feature\Middleware;

use App\Models\User;
use App\Models\Role;
use App\Models\Permission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * 管理員權限檢查中介軟體測試
 */
class CheckAdminPermissionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // 建立測試路由
        Route::middleware(['web', 'admin'])->group(function () {
            Route::get('/admin/test', function () {
                return response()->json(['message' => 'success']);
            })->name('admin.test');
        });

        // 建立測試用的角色和權限
        $this->createTestRolesAndPermissions();
    }

    /**
     * 建立測試用的角色和權限
     */
    private function createTestRolesAndPermissions(): void
    {
        // 建立角色
        Role::create([
            'name' => 'super_admin',
            'display_name' => '超級管理員',
            'description' => '擁有所有權限的超級管理員'
        ]);

        Role::create([
            'name' => 'admin',
            'display_name' => '管理員',
            'description' => '一般管理員'
        ]);

        Role::create([
            'name' => 'user',
            'display_name' => '一般使用者',
            'description' => '一般使用者'
        ]);
    }

    /**
     * 測試管理員使用者可以存取
     */
    public function test_admin_user_can_access(): void
    {
        $user = User::factory()->create();
        $adminRole = Role::where('name', 'admin')->first();
        $user->assignRole($adminRole);

        $this->actingAs($user);

        $response = $this->get('/admin/test');

        $response->assertStatus(200);
        $response->assertJson(['message' => 'success']);
    }
}