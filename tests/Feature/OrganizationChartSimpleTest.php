<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrganizationChartSimpleTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function organization_chart_route_exists()
    {
        // 執行基本 seeders
        $this->artisan('db:seed', ['--class' => 'PermissionSeeder']);
        $this->artisan('db:seed', ['--class' => 'RoleSeeder']);
        $this->artisan('db:seed', ['--class' => 'UserSeeder']);
        
        // 登入管理員
        $user = User::where('username', 'admin')->first();
        $this->actingAs($user);

        // 測試路由是否存在
        $response = $this->get('/admin/channels/organization');
        
        // 檢查是否不是 404 錯誤
        $this->assertNotEquals(404, $response->getStatusCode());
    }

    /** @test */
    public function organization_chart_requires_authentication()
    {
        // 未登入時訪問
        $response = $this->get('/admin/channels/organization');
        
        // 應該重定向到登入頁面
        $response->assertRedirect();
    }
}