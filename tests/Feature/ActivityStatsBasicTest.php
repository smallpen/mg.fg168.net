<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * 活動統計基本功能測試
 */
class ActivityStatsBasicTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;

    protected function setUp(): void
    {
        parent::setUp();
        
        // 建立測試資料
        $this->seed();
        
        // 建立管理員使用者
        $this->adminUser = User::where('username', 'admin')->first();
        $this->assertNotNull($this->adminUser, '管理員使用者不存在，請確認已執行 Seeder');
    }

    /** @test */
    public function 管理員可以存取活動統計頁面()
    {
        $this->actingAs($this->adminUser);

        $response = $this->get(route('admin.activities.stats'));

        $response->assertStatus(200)
                 ->assertSee('活動統計分析');
    }

    /** @test */
    public function 沒有權限的使用者無法存取活動統計頁面()
    {
        // 建立沒有權限的使用者
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->get(route('admin.activities.stats'));

        $response->assertStatus(403);
    }

    /** @test */
    public function 未登入使用者會被重導向到登入頁面()
    {
        $response = $this->get(route('admin.activities.stats'));

        $response->assertRedirect(route('admin.login'));
    }
}