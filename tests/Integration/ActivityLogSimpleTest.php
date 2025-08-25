<?php

namespace Tests\Integration;

use Tests\TestCase;
use App\Models\User;
use App\Models\Activity;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * 活動記錄簡單整合測試
 */
class ActivityLogSimpleTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 測試活動記錄基本建立功能
     * 
     * @test
     */
    public function test_can_create_activity_record()
    {
        // 執行資料庫遷移和種子
        $this->artisan('migrate:fresh --seed');
        
        // 取得管理員使用者
        $adminUser = User::where('username', 'admin')->first();
        $this->assertNotNull($adminUser, '管理員使用者應該存在');

        // 建立活動記錄
        $activity = Activity::create([
            'type' => 'test_activity',
            'description' => '測試活動記錄',
            'user_id' => $adminUser->id,
            'ip_address' => '127.0.0.1',
            'result' => 'success',
            'risk_level' => 1
        ]);

        // 驗證記錄已建立
        $this->assertNotNull($activity->id);
        $this->assertEquals('test_activity', $activity->type);
        $this->assertEquals('測試活動記錄', $activity->description);
        $this->assertEquals($adminUser->id, $activity->user_id);

        // 驗證資料庫中的記錄
        $this->assertDatabaseHas('activities', [
            'type' => 'test_activity',
            'description' => '測試活動記錄',
            'user_id' => $adminUser->id
        ]);
    }

    /**
     * 測試活動記錄查詢功能
     * 
     * @test
     */
    public function test_can_query_activity_records()
    {
        // 執行資料庫遷移和種子
        $this->artisan('migrate:fresh --seed');
        
        // 取得管理員使用者
        $adminUser = User::where('username', 'admin')->first();

        // 建立測試記錄
        Activity::create([
            'type' => 'user_login',
            'description' => '使用者登入',
            'user_id' => $adminUser->id,
            'result' => 'success',
            'risk_level' => 1
        ]);

        Activity::create([
            'type' => 'user_logout',
            'description' => '使用者登出',
            'user_id' => $adminUser->id,
            'result' => 'success',
            'risk_level' => 1
        ]);

        // 測試查詢
        $activities = Activity::where('user_id', $adminUser->id)->get();
        $this->assertGreaterThanOrEqual(2, $activities->count());

        $loginActivity = Activity::where('type', 'user_login')->first();
        $this->assertNotNull($loginActivity);
        $this->assertEquals('使用者登入', $loginActivity->description);
    }

    /**
     * 測試活動記錄路由存取
     * 
     * @test
     */
    public function test_can_access_activity_routes()
    {
        // 執行資料庫遷移和種子
        $this->artisan('migrate:fresh --seed');
        
        // 取得管理員使用者並登入
        $adminUser = User::where('username', 'admin')->first();
        $this->actingAs($adminUser);

        // 測試主頁面路由
        $response = $this->get(route('admin.activities.index'));
        $response->assertStatus(200);

        // 測試統計頁面路由
        $response = $this->get(route('admin.activities.stats'));
        $response->assertStatus(200);

        // 測試監控頁面路由
        $response = $this->get(route('admin.activities.monitor'));
        $response->assertStatus(200);
    }
}