<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrganizationChartBasicTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // 執行 seeders 來建立基本資料
        $this->artisan('db:seed', ['--class' => 'PermissionSeeder']);
        $this->artisan('db:seed', ['--class' => 'RoleSeeder']);
        $this->artisan('db:seed', ['--class' => 'UserSeeder']);
        $this->artisan('db:seed', ['--class' => 'ChannelManagementTestSeeder']);
    }

    /** @test */
    public function it_can_display_organization_chart_page()
    {
        // 建立測試使用者並登入
        $user = User::where('username', 'admin')->first();
        $this->actingAs($user);

        // 訪問組織架構圖表頁面
        $response = $this->get(route('admin.channels.organization.index'));

        $response->assertStatus(200);
        $response->assertViewIs('admin.channels.organization');
        $response->assertSee('組織架構圖表');
    }

    /** @test */
    public function it_requires_authentication_to_access_organization_chart()
    {
        // 未登入時訪問組織架構圖表頁面
        $response = $this->get(route('admin.channels.organization.index'));

        // 應該重定向到登入頁面
        $response->assertRedirect(route('admin.login'));
    }

    /** @test */
    public function it_requires_permission_to_access_organization_chart()
    {
        // 建立沒有權限的使用者
        $user = User::where('username', 'no_role')->first();
        $this->actingAs($user);

        // 訪問組織架構圖表頁面
        $response = $this->get(route('admin.channels.organization.index'));

        // 應該返回 403 錯誤
        $response->assertStatus(403);
    }
}