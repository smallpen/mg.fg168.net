<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\User;
use App\Models\Role;
use App\Models\Permission;
use App\Models\Agent;
use App\Http\Middleware\ChannelPermissionMiddleware;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

/**
 * 通路管理權限中介軟體單元測試
 */
class ChannelPermissionMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    protected ChannelPermissionMiddleware $middleware;
    protected User $admin;
    protected User $agent;
    protected User $regularUser;
    protected Agent $testAgent;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->middleware = new ChannelPermissionMiddleware();
        $this->seedTestData();
    }

    private function seedTestData(): void
    {
        // 建立權限
        $permission = Permission::create([
            'name' => 'channels.agents.view',
            'display_name' => '檢視代理',
            'description' => '可以檢視代理列表',
            'module' => 'channels',
            'type' => 'view',
        ]);

        $selfManagePermission = Permission::create([
            'name' => 'channels.agents.self_manage',
            'display_name' => '代理自主管理',
            'description' => '代理可以管理自己的下層',
            'module' => 'channels',
            'type' => 'self_manage',
        ]);

        // 建立角色
        $adminRole = Role::create([
            'name' => 'admin',
            'display_name' => '管理員',
            'description' => '系統管理員',
        ]);

        $agentRole = Role::create([
            'name' => 'agent',
            'display_name' => '代理',
            'description' => '代理角色',
        ]);

        $adminRole->permissions()->attach([$permission->id, $selfManagePermission->id]);
        $agentRole->permissions()->attach([$permission->id, $selfManagePermission->id]);

        // 建立使用者
        $this->admin = User::factory()->create([
            'username' => 'admin',
            'email' => 'admin@test.com',
            'is_active' => true,
        ]);
        $this->admin->roles()->attach($adminRole);

        $this->agent = User::factory()->create([
            'username' => 'agent1',
            'email' => 'agent1@test.com',
            'is_active' => true,
        ]);
        $this->agent->roles()->attach($agentRole);

        $this->regularUser = User::factory()->create([
            'username' => 'user1',
            'email' => 'user1@test.com',
            'is_active' => true,
        ]);

        // 建立代理
        $this->testAgent = Agent::create([
            'name' => '測試代理',
            'username' => 'agent1',
            'account' => 'aagent1',
            'email' => 'agent1@test.com',
            'prefix' => 'a',
            'level' => 1,
            'total_points' => 1000,
            'remaining_points' => 1000,
            'is_active' => true,
            'created_by' => $this->admin->id,
        ]);
    }

    /** @test */
    public function it_redirects_unauthenticated_users()
    {
        $request = Request::create('/admin/channels/agents', 'GET');
        
        $response = $this->middleware->handle($request, function () {
            return new Response('OK');
        }, 'channels.agents.view');

        $this->assertEquals(302, $response->getStatusCode());
        $this->assertStringContains('login', $response->getTargetUrl());
    }

    /** @test */
    public function it_blocks_inactive_users()
    {
        $inactiveUser = User::factory()->create(['is_active' => false]);
        Auth::login($inactiveUser);

        $request = Request::create('/admin/channels/agents', 'GET');
        
        $response = $this->middleware->handle($request, function () {
            return new Response('OK');
        }, 'channels.agents.view');

        $this->assertEquals(302, $response->getStatusCode());
        $this->assertFalse(Auth::check()); // 使用者應該被登出
    }

    /** @test */
    public function it_blocks_users_without_permission()
    {
        Auth::login($this->regularUser);

        $request = Request::create('/admin/channels/agents', 'GET');
        
        $response = $this->middleware->handle($request, function () {
            return new Response('OK');
        }, 'channels.agents.view');

        $this->assertEquals(403, $response->getStatusCode());
    }

    /** @test */
    public function it_allows_users_with_permission()
    {
        Auth::login($this->admin);

        $request = Request::create('/admin/channels/agents', 'GET');
        
        $response = $this->middleware->handle($request, function () {
            return new Response('OK');
        }, 'channels.agents.view');

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('OK', $response->getContent());
    }

    /** @test */
    public function it_allows_access_to_all_scope_for_admin()
    {
        Auth::login($this->admin);

        $request = Request::create('/admin/channels/agents', 'GET');
        
        $response = $this->middleware->handle($request, function () {
            return new Response('OK');
        }, 'channels.agents.view', 'all');

        $this->assertEquals(200, $response->getStatusCode());
    }

    /** @test */
    public function it_blocks_all_scope_for_non_admin()
    {
        Auth::login($this->agent);

        $request = Request::create('/admin/channels/agents', 'GET');
        
        $response = $this->middleware->handle($request, function () {
            return new Response('OK');
        }, 'channels.agents.view', 'all');

        $this->assertEquals(403, $response->getStatusCode());
    }

    /** @test */
    public function it_allows_subordinate_scope_for_agent()
    {
        Auth::login($this->agent);

        $request = Request::create('/admin/channels/agents', 'GET');
        
        $response = $this->middleware->handle($request, function () {
            return new Response('OK');
        }, 'channels.agents.view', 'subordinate');

        $this->assertEquals(200, $response->getStatusCode());
    }

    /** @test */
    public function it_allows_own_scope_for_agent()
    {
        Auth::login($this->agent);

        $request = Request::create('/admin/channels/agents/' . $this->testAgent->id, 'GET');
        $request->setRouteResolver(function () {
            $route = new \Illuminate\Routing\Route(['GET'], '/admin/channels/agents/{agent}', []);
            $route->bind($request = Request::create('/admin/channels/agents/' . $this->testAgent->id));
            $route->setParameter('agent', $this->testAgent->id);
            return $route;
        });
        
        $response = $this->middleware->handle($request, function () {
            return new Response('OK');
        }, 'channels.agents.view', 'own');

        $this->assertEquals(200, $response->getStatusCode());
    }

    /** @test */
    public function it_returns_json_error_for_ajax_requests()
    {
        $request = Request::create('/admin/channels/agents', 'GET');
        $request->headers->set('Accept', 'application/json');
        
        $response = $this->middleware->handle($request, function () {
            return new Response('OK');
        }, 'channels.agents.view');

        $this->assertEquals(401, $response->getStatusCode());
        $this->assertJson($response->getContent());
        
        $data = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('message', $data);
        $this->assertArrayHasKey('redirect', $data);
    }

    /** @test */
    public function it_returns_json_error_for_permission_denied()
    {
        Auth::login($this->regularUser);

        $request = Request::create('/admin/channels/agents', 'GET');
        $request->headers->set('Accept', 'application/json');
        
        $response = $this->middleware->handle($request, function () {
            return new Response('OK');
        }, 'channels.agents.view');

        $this->assertEquals(403, $response->getStatusCode());
        $this->assertJson($response->getContent());
        
        $data = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('message', $data);
        $this->assertArrayHasKey('error', $data);
        $this->assertEquals('insufficient_permission', $data['error']);
    }

    /** @test */
    public function it_handles_livewire_requests_correctly()
    {
        Auth::login($this->regularUser);

        $request = Request::create('/admin/channels/agents', 'GET');
        $request->headers->set('X-Livewire', 'true');
        
        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        $this->expectExceptionCode(403);
        
        $this->middleware->handle($request, function () {
            return new Response('OK');
        }, 'channels.agents.view');
    }

    /** @test */
    public function it_allows_access_without_specific_permission()
    {
        Auth::login($this->admin);

        $request = Request::create('/admin/channels/agents', 'GET');
        
        $response = $this->middleware->handle($request, function () {
            return new Response('OK');
        });

        $this->assertEquals(200, $response->getStatusCode());
    }

    /** @test */
    public function it_logs_access_attempts()
    {
        // 這個測試需要檢查日誌記錄
        // 由於日誌記錄是透過 Log facade，我們可以使用 Log::fake() 來測試
        
        \Illuminate\Support\Facades\Log::fake();

        Auth::login($this->admin);

        $request = Request::create('/admin/channels/agents', 'GET');
        
        $this->middleware->handle($request, function () {
            return new Response('OK');
        }, 'channels.agents.view');

        \Illuminate\Support\Facades\Log::assertLogged('info', function ($message, $context) {
            return $message === 'Channel management access granted' &&
                   isset($context['user_id']) &&
                   $context['user_id'] === $this->admin->id;
        });
    }
}