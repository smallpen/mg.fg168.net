<?php

namespace Tests\Feature;

use App\Models\Activity;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

/**
 * 活動記錄中介軟體測試
 */
class ActivityLoggingMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    protected User $testUser;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->testUser = User::factory()->create([
            'username' => 'testuser',
            'name' => '測試使用者',
            'email' => 'test@example.com',
        ]);
    }

    /** @test */
    public function it_logs_web_requests(): void
    {
        // 登入使用者
        $this->actingAs($this->testUser);

        // 記錄請求前的活動數量
        $initialCount = Activity::count();

        // 發送 GET 請求
        $response = $this->get('/admin/dashboard');

        // 驗證回應
        $response->assertStatus(200);

        // 驗證活動記錄增加
        $this->assertGreaterThan($initialCount, Activity::count());

        // 檢查是否有頁面檢視活動記錄
        $this->assertDatabaseHas('activities', [
            'type' => 'page_view',
            'user_id' => $this->testUser->id,
            'module' => 'admin',
        ]);
    }

    /** @test */
    public function it_logs_post_requests(): void
    {
        // 登入使用者
        $this->actingAs($this->testUser);

        // 記錄請求前的活動數量
        $initialCount = Activity::count();

        // 發送 POST 請求（假設有一個測試路由）
        $response = $this->post('/admin/test', [
            'test_field' => 'test_value',
        ]);

        // 驗證活動記錄增加
        $this->assertGreaterThan($initialCount, Activity::count());
    }

    /** @test */
    public function it_excludes_certain_routes(): void
    {
        // 記錄請求前的活動數量
        $initialCount = Activity::count();

        // 發送到排除路由的請求
        $response = $this->get('/api/health');

        // 驗證活動記錄沒有增加
        $this->assertEquals($initialCount, Activity::count());
    }

    /** @test */
    public function it_logs_error_responses(): void
    {
        // 登入使用者
        $this->actingAs($this->testUser);

        // 記錄請求前的活動數量
        $initialCount = Activity::count();

        // 發送到不存在的路由
        $response = $this->get('/admin/nonexistent');

        // 驗證回應是 404
        $response->assertStatus(404);

        // 驗證活動記錄增加
        $this->assertGreaterThan($initialCount, Activity::count());

        // 檢查是否有錯誤活動記錄
        $this->assertDatabaseHas('activities', [
            'type' => 'http_error',
            'user_id' => $this->testUser->id,
            'result' => 'client_error',
        ]);
    }
}