<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Role;
use App\Models\Permission;
use App\Services\ChannelAccessControlService;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * 通路管理權限基礎測試
 */
class ChannelPermissionBasicTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function channel_permissions_are_created()
    {
        // 執行權限 Seeder
        $this->artisan('db:seed', ['--class' => 'PermissionSeeder']);

        // 檢查通路管理權限是否存在
        $channelPermissions = Permission::where('module', 'channels')->get();
        
        $this->assertGreaterThan(0, $channelPermissions->count());
        
        // 檢查特定權限
        $this->assertTrue(Permission::where('name', 'channels.agents.view')->exists());
        $this->assertTrue(Permission::where('name', 'channels.players.view')->exists());
        $this->assertTrue(Permission::where('name', 'channels.points.view')->exists());
    }

    /** @test */
    public function channel_access_control_service_can_be_instantiated()
    {
        $service = app(ChannelAccessControlService::class);
        
        $this->assertInstanceOf(ChannelAccessControlService::class, $service);
    }

    /** @test */
    public function admin_user_has_all_permissions()
    {
        // 執行 Seeder
        $this->artisan('db:seed', ['--class' => 'PermissionSeeder']);
        $this->artisan('db:seed', ['--class' => 'RoleSeeder']);
        $this->artisan('db:seed', ['--class' => 'UserSeeder']);

        $admin = User::where('username', 'admin')->first();
        
        if ($admin) {
            $this->assertTrue($admin->hasPermission('channels.agents.view'));
            $this->assertTrue($admin->hasPermission('channels.players.view'));
            $this->assertTrue($admin->hasPermission('channels.points.view'));
        } else {
            $this->markTestSkipped('Admin user not found in seeded data');
        }
    }

    /** @test */
    public function middleware_can_be_instantiated()
    {
        $middleware = app(\App\Http\Middleware\ChannelPermissionMiddleware::class);
        
        $this->assertInstanceOf(\App\Http\Middleware\ChannelPermissionMiddleware::class, $middleware);
    }

    /** @test */
    public function policies_can_be_instantiated()
    {
        $agentPolicy = app(\App\Policies\AgentPolicy::class);
        $playerPolicy = app(\App\Policies\PlayerPolicy::class);
        
        $this->assertInstanceOf(\App\Policies\AgentPolicy::class, $agentPolicy);
        $this->assertInstanceOf(\App\Policies\PlayerPolicy::class, $playerPolicy);
    }
}