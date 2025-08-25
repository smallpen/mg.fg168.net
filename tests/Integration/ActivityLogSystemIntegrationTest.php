<?php

namespace Tests\Integration;

use Tests\TestCase;
use App\Models\User;
use App\Models\Activity;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * 活動記錄系統整合測試
 * 使用已存在的測試資料
 */
class ActivityLogSystemIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;

    protected function setUp(): void
    {
        parent::setUp();
        
        // 執行資料庫遷移和種子
        $this->artisan('migrate:fresh --seed');
        
        // 使用已建立的管理員使用者
        $this->adminUser = User::where('username', 'admin')->first();
    }

    /**
     * 測試活動記錄基本功能
     * 
     * @test
     */
    public function test_activity_logging_basic_functionality()
    {
        $this->actingAs($this->adminUser);

        // 直接建立活動記錄
        $activity = Activity::create([
            'type' => 'system_test',
            'description' => '系統整合測試',
            'user_id' => $this->adminUser->id,
            'ip_address' => '127.0.0.1',
            'result' => 'success',
            'risk_level' => 1,
            'properties' => ['test' => 'integration']
        ]);

        // 驗證記錄已建立
        $this->assertDatabaseHas('activities', [
            'type' => 'system_test',
            'description' => '系統整合測試',
            'user_id' => $this->adminUser->id
        ]);

        // 驗證記錄屬性
        $this->assertEquals('system_test', $activity->type);
        $this->assertEquals('系統整合測試', $activity->description);
        $this->assertEquals($this->adminUser->id, $activity->user_id);
        $this->assertEquals('success', $activity->result);
        $this->assertEquals(1, $activity->risk_level);

        $this->assertTrue(true, '活動記錄基本功能測試通過');
    }

    /**
     * 測試活動記錄查詢功能
     * 
     * @test
     */
    public function test_activity_querying_functionality()
    {
        $this->actingAs($this->adminUser);

        // 建立多筆測試記錄
        $activities = [
            [
                'type' => 'user_login',
                'description' => '使用者登入',
                'user_id' => $this->adminUser->id,
                'result' => 'success',
                'risk_level' => 1
            ],
            [
                'type' => 'user_logout',
                'description' => '使用者登出',
                'user_id' => $this->adminUser->id,
                'result' => 'success',
                'risk_level' => 1
            ],
            [
                'type' => 'security_event',
                'description' => '安全事件',
                'user_id' => $this->adminUser->id,
                'result' => 'warning',
                'risk_level' => 5
            ]
        ];

        foreach ($activities as $activityData) {
            Activity::create($activityData);
        }

        // 測試基本查詢
        $userActivities = Activity::where('user_id', $this->adminUser->id)->get();
        $this->assertCount(3, $userActivities);

        // 測試按類型查詢
        $loginActivities = Activity::where('type', 'user_login')->get();
        $this->assertCount(1, $loginActivities);
        $this->assertEquals('使用者登入', $loginActivities->first()->description);

        // 測試按風險等級查詢
        $highRiskActivities = Activity::where('risk_level', '>=', 5)->get();
        $this->assertCount(1, $highRiskActivities);
        $this->assertEquals('security_event', $highRiskActivities->first()->type);

        // 測試按結果查詢
        $successActivities = Activity::where('result', 'success')->get();
        $this->assertGreaterThanOrEqual(2, $successActivities->count());

        $this->assertTrue(true, '活動記錄查詢功能測試通過');
    }

    /**
     * 測試活動記錄路由存取
     * 
     * @test
     */
    public function test_activity_routes_accessibility()
    {
        $this->actingAs($this->adminUser);

        // 測試主頁面路由
        $response = $this->get(route('admin.activities.index'));
        $response->assertStatus(200);
        $response->assertViewIs('admin.activities.index');

        // 測試統計頁面路由
        $response = $this->get(route('admin.activities.stats'));
        $response->assertStatus(200);
        $response->assertViewIs('admin.activities.stats');

        // 測試監控頁面路由
        $response = $this->get(route('admin.activities.monitor'));
        $response->assertStatus(200);
        $response->assertViewIs('admin.activities.monitor');

        // 測試匯出頁面路由
        $response = $this->get(route('admin.activities.export'));
        $response->assertStatus(200);
        $response->assertViewIs('admin.activities.export');

        $this->assertTrue(true, '活動記錄路由存取測試通過');
    }

    /**
     * 測試活動記錄權限控制
     * 
     * @test
     */
    public function test_activity_permission_control()
    {
        // 測試管理員存取
        $this->actingAs($this->adminUser);
        
        $response = $this->get(route('admin.activities.index'));
        $response->assertStatus(200);

        // 測試未登入使用者
        auth()->logout();
        
        $response = $this->get(route('admin.activities.index'));
        $response->assertRedirect(route('admin.login'));

        // 測試一般使用者（如果存在）
        $regularUser = User::where('username', 'testuser')->first();
        if ($regularUser) {
            $this->actingAs($regularUser);
            
            $response = $this->get(route('admin.activities.index'));
            // 一般使用者應該被拒絕存取或重新導向
            $this->assertTrue(
                $response->status() === 403 || $response->isRedirect(),
                '一般使用者應該無法存取活動記錄頁面'
            );
        }

        $this->assertTrue(true, '活動記錄權限控制測試通過');
    }

    /**
     * 測試活動記錄效能
     * 
     * @test
     */
    public function test_activity_performance()
    {
        $this->actingAs($this->adminUser);

        // 建立大量測試資料
        $startTime = microtime(true);
        
        $activities = [];
        for ($i = 0; $i < 100; $i++) {
            $activities[] = [
                'type' => 'performance_test',
                'description' => "效能測試記錄 {$i}",
                'user_id' => $this->adminUser->id,
                'result' => 'success',
                'risk_level' => 1,
                'created_at' => now(),
                'updated_at' => now()
            ];
        }

        Activity::insert($activities);
        
        $insertTime = microtime(true) - $startTime;
        $this->assertLessThan(2.0, $insertTime, '插入 100 筆記錄應該在 2 秒內完成');

        // 測試查詢效能
        $startTime = microtime(true);
        
        $results = Activity::where('type', 'performance_test')
            ->orderBy('created_at', 'desc')
            ->limit(50)
            ->get();

        $queryTime = microtime(true) - $startTime;
        
        $this->assertCount(50, $results);
        $this->assertLessThan(0.5, $queryTime, '查詢 50 筆記錄應該在 0.5 秒內完成');

        // 測試分頁查詢效能
        $startTime = microtime(true);
        
        $paginatedResults = Activity::where('type', 'performance_test')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        $paginationTime = microtime(true) - $startTime;
        
        $this->assertEquals(20, $paginatedResults->count());
        $this->assertLessThan(0.5, $paginationTime, '分頁查詢應該在 0.5 秒內完成');

        $this->assertTrue(true, '活動記錄效能測試通過');
    }

    /**
     * 測試活動記錄資料完整性
     * 
     * @test
     */
    public function test_activity_data_integrity()
    {
        $this->actingAs($this->adminUser);

        // 測試必填欄位
        $activity = Activity::create([
            'type' => 'integrity_test',
            'description' => '資料完整性測試',
            'result' => 'success',
            'risk_level' => 1
        ]);

        $this->assertNotNull($activity->id);
        $this->assertNotNull($activity->created_at);
        $this->assertNotNull($activity->updated_at);

        // 測試 JSON 欄位
        $activityWithProperties = Activity::create([
            'type' => 'json_test',
            'description' => 'JSON 欄位測試',
            'result' => 'success',
            'risk_level' => 1,
            'properties' => [
                'key1' => 'value1',
                'key2' => ['nested' => 'value'],
                'key3' => 123
            ]
        ]);

        $retrieved = Activity::find($activityWithProperties->id);
        $this->assertIsArray($retrieved->properties);
        $this->assertEquals('value1', $retrieved->properties['key1']);
        $this->assertEquals('value', $retrieved->properties['key2']['nested']);
        $this->assertEquals(123, $retrieved->properties['key3']);

        // 測試關聯關係
        $activityWithUser = Activity::create([
            'type' => 'relation_test',
            'description' => '關聯關係測試',
            'user_id' => $this->adminUser->id,
            'result' => 'success',
            'risk_level' => 1
        ]);

        $retrievedWithUser = Activity::with('user')->find($activityWithUser->id);
        $this->assertNotNull($retrievedWithUser->user);
        $this->assertEquals($this->adminUser->id, $retrievedWithUser->user->id);
        $this->assertEquals($this->adminUser->name, $retrievedWithUser->user->name);

        $this->assertTrue(true, '活動記錄資料完整性測試通過');
    }

    /**
     * 測試活動記錄搜尋功能
     * 
     * @test
     */
    public function test_activity_search_functionality()
    {
        $this->actingAs($this->adminUser);

        // 建立測試資料
        $testActivities = [
            [
                'type' => 'search_test_1',
                'description' => '這是第一個搜尋測試記錄',
                'user_id' => $this->adminUser->id,
                'result' => 'success',
                'risk_level' => 1
            ],
            [
                'type' => 'search_test_2',
                'description' => '這是第二個搜尋測試記錄',
                'user_id' => $this->adminUser->id,
                'result' => 'success',
                'risk_level' => 2
            ],
            [
                'type' => 'other_test',
                'description' => '這是其他類型的記錄',
                'user_id' => $this->adminUser->id,
                'result' => 'failed',
                'risk_level' => 3
            ]
        ];

        foreach ($testActivities as $activityData) {
            Activity::create($activityData);
        }

        // 測試按描述搜尋
        $searchResults = Activity::where('description', 'like', '%搜尋測試%')->get();
        $this->assertCount(2, $searchResults);

        // 測試按類型搜尋
        $typeResults = Activity::where('type', 'like', '%search_test%')->get();
        $this->assertCount(2, $typeResults);

        // 測試按結果搜尋
        $successResults = Activity::where('result', 'success')->get();
        $this->assertGreaterThanOrEqual(2, $successResults->count());

        // 測試複合搜尋
        $complexResults = Activity::where('description', 'like', '%搜尋測試%')
            ->where('result', 'success')
            ->get();
        $this->assertCount(2, $complexResults);

        $this->assertTrue(true, '活動記錄搜尋功能測試通過');
    }
}