<?php

namespace Tests\Unit;

use App\Http\Middleware\CheckAdminPermission;
use App\Models\User;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Mockery;
use Tests\TestCase;

/**
 * 管理員權限檢查中介軟體單元測試
 */
class CheckAdminPermissionMiddlewareTest extends TestCase
{
    protected CheckAdminPermission $middleware;

    protected function setUp(): void
    {
        parent::setUp();
        $this->middleware = new CheckAdminPermission();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /**
     * 測試未登入使用者被重新導向
     */
    public function test_unauthenticated_user_redirected(): void
    {
        // 模擬未登入狀態
        Auth::shouldReceive('check')->once()->andReturn(false);

        $request = Request::create('/admin/test', 'GET');
        
        $response = $this->middleware->handle($request, function () {
            return new Response('Should not reach here');
        });

        $this->assertEquals(302, $response->getStatusCode());
    }

    /**
     * 測試已登入但非管理員使用者被拒絕
     */
    public function test_non_admin_user_denied(): void
    {
        // 建立模擬使用者
        $user = Mockery::mock(User::class);
        $user->shouldReceive('getAttribute')->with('is_active')->andReturn(true);
        $user->shouldReceive('isAdmin')->once()->andReturn(false);

        // 模擬已登入狀態
        Auth::shouldReceive('check')->once()->andReturn(true);
        Auth::shouldReceive('user')->once()->andReturn($user);

        $request = Request::create('/admin/test', 'GET');
        
        $response = $this->middleware->handle($request, function () {
            return new Response('Should not reach here');
        });

        $this->assertEquals(302, $response->getStatusCode());
    }

    /**
     * 測試管理員使用者可以通過
     */
    public function test_admin_user_passes(): void
    {
        // 建立模擬使用者
        $user = Mockery::mock(User::class);
        $user->shouldReceive('getAttribute')->with('is_active')->andReturn(true);
        $user->shouldReceive('isAdmin')->once()->andReturn(true);

        // 模擬已登入狀態
        Auth::shouldReceive('check')->once()->andReturn(true);
        Auth::shouldReceive('user')->once()->andReturn($user);

        $request = Request::create('/admin/test', 'GET');
        
        $response = $this->middleware->handle($request, function () {
            return new Response('Success');
        });

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('Success', $response->getContent());
    }

    /**
     * 測試停用使用者被登出
     */
    public function test_inactive_user_logged_out(): void
    {
        // 建立模擬使用者
        $user = Mockery::mock(User::class);
        $user->shouldReceive('getAttribute')->with('is_active')->andReturn(false);
        $user->shouldReceive('getAttribute')->with('id')->andReturn(1);

        // 模擬已登入狀態
        Auth::shouldReceive('check')->once()->andReturn(true);
        Auth::shouldReceive('user')->once()->andReturn($user);
        Auth::shouldReceive('logout')->once();

        $request = Request::create('/admin/test', 'GET');
        
        $response = $this->middleware->handle($request, function () {
            return new Response('Should not reach here');
        });

        $this->assertEquals(302, $response->getStatusCode());
    }

    /**
     * 測試權限檢查
     */
    public function test_permission_check(): void
    {
        // 建立模擬使用者
        $user = Mockery::mock(User::class);
        $user->shouldReceive('getAttribute')->with('is_active')->andReturn(true);
        $user->shouldReceive('hasPermission')->with('user.manage')->once()->andReturn(false);
        $user->shouldReceive('getAttribute')->with('id')->andReturn(1);
        $user->shouldReceive('getAllPermissions->pluck')->andReturn(collect([]));

        // 模擬已登入狀態
        Auth::shouldReceive('check')->once()->andReturn(true);
        Auth::shouldReceive('user')->once()->andReturn($user);

        $request = Request::create('/admin/test', 'GET');
        
        $response = $this->middleware->handle($request, function () {
            return new Response('Should not reach here');
        }, 'user.manage');

        $this->assertEquals(302, $response->getStatusCode());
    }

    /**
     * 測試 AJAX 請求的錯誤回應
     */
    public function test_ajax_request_json_response(): void
    {
        // 模擬未登入狀態
        Auth::shouldReceive('check')->once()->andReturn(false);

        $request = Request::create('/admin/test', 'GET');
        $request->headers->set('Accept', 'application/json');
        
        $response = $this->middleware->handle($request, function () {
            return new Response('Should not reach here');
        });

        $this->assertEquals(401, $response->getStatusCode());
        $this->assertJson($response->getContent());
    }
}