<?php

namespace Tests\Feature;

use App\Models\Activity;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * 活動統計整合測試
 */
class ActivityStatsIntegrationTest extends TestCase
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
    public function 活動統計頁面可以正常載入()
    {
        $this->actingAs($this->adminUser);

        // 建立一些測試活動記錄
        Activity::create([
            'type' => 'login',
            'description' => '管理員登入',
            'module' => 'auth',
            'user_id' => $this->adminUser->id,
            'ip_address' => '192.168.1.1',
            'user_agent' => 'Test Browser',
            'result' => 'success',
            'risk_level' => 1,
            'signature' => hash('sha256', 'test_signature'),
        ]);

        Activity::create([
            'type' => 'create_user',
            'description' => '建立新使用者',
            'module' => 'users',
            'user_id' => $this->adminUser->id,
            'ip_address' => '192.168.1.1',
            'user_agent' => 'Test Browser',
            'result' => 'success',
            'risk_level' => 2,
            'signature' => hash('sha256', 'test_signature_2'),
        ]);

        $response = $this->get(route('admin.activities.stats'));

        $response->assertStatus(200)
                 ->assertSee('活動統計分析')
                 ->assertSee('總活動數')
                 ->assertSee('活躍使用者')
                 ->assertSee('安全事件')
                 ->assertSee('成功率');
    }

    /** @test */
    public function 統計資料會正確計算()
    {
        $this->actingAs($this->adminUser);

        // 建立多筆測試活動記錄
        for ($i = 0; $i < 5; $i++) {
            Activity::create([
                'type' => 'login',
                'description' => "登入測試 {$i}",
                'module' => 'auth',
                'user_id' => $this->adminUser->id,
                'ip_address' => '192.168.1.1',
                'user_agent' => 'Test Browser',
                'result' => 'success',
                'risk_level' => 1,
                'signature' => hash('sha256', "test_signature_{$i}"),
                'created_at' => now()->subDays($i),
            ]);
        }

        // 建立一筆失敗記錄
        Activity::create([
            'type' => 'login',
            'description' => '登入失敗',
            'module' => 'auth',
            'user_id' => $this->adminUser->id,
            'ip_address' => '192.168.1.1',
            'user_agent' => 'Test Browser',
            'result' => 'failed',
            'risk_level' => 5,
            'signature' => hash('sha256', 'test_signature_failed'),
        ]);

        $response = $this->get(route('admin.activities.stats'));

        $response->assertStatus(200);
        
        // 檢查頁面內容包含統計資料
        $content = $response->getContent();
        $this->assertStringContainsString('活動統計分析', $content);
    }

    /** @test */
    public function 沒有活動記錄時頁面仍能正常顯示()
    {
        $this->actingAs($this->adminUser);

        // 清除所有活動記錄
        Activity::truncate();

        $response = $this->get(route('admin.activities.stats'));

        $response->assertStatus(200)
                 ->assertSee('活動統計分析')
                 ->assertSee('總活動數');
    }

    /** @test */
    public function 不同時間範圍的統計資料會正確顯示()
    {
        $this->actingAs($this->adminUser);

        // 建立不同時間的活動記錄
        Activity::create([
            'type' => 'login',
            'description' => '今天的活動',
            'module' => 'auth',
            'user_id' => $this->adminUser->id,
            'ip_address' => '192.168.1.1',
            'user_agent' => 'Test Browser',
            'result' => 'success',
            'risk_level' => 1,
            'signature' => hash('sha256', 'today'),
            'created_at' => now(),
        ]);

        Activity::create([
            'type' => 'login',
            'description' => '一週前的活動',
            'module' => 'auth',
            'user_id' => $this->adminUser->id,
            'ip_address' => '192.168.1.1',
            'user_agent' => 'Test Browser',
            'result' => 'success',
            'risk_level' => 1,
            'signature' => hash('sha256', 'week_ago'),
            'created_at' => now()->subWeek(),
        ]);

        Activity::create([
            'type' => 'login',
            'description' => '一個月前的活動',
            'module' => 'auth',
            'user_id' => $this->adminUser->id,
            'ip_address' => '192.168.1.1',
            'user_agent' => 'Test Browser',
            'result' => 'success',
            'risk_level' => 1,
            'signature' => hash('sha256', 'month_ago'),
            'created_at' => now()->subMonth(),
        ]);

        $response = $this->get(route('admin.activities.stats'));

        $response->assertStatus(200);
    }
}