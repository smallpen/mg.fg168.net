<?php

namespace Tests\Integration;

use Tests\TestCase;
use App\Models\User;
use App\Models\Role;
use App\Models\Permission;
use App\Models\Activity;
use App\Services\ActivityLogger;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

/**
 * 活動記錄基本整合測試
 */
class ActivityLogBasicIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setupBasicTestData();
    }

    /**
     * 測試基本活動記錄功能
     * 
     * @test
     */
    public function test_basic_activity_logging()
    {
        $this->actingAs($this->adminUser);

        // 直接建立活動記錄
        $activity = Activity::create([
            'type' => 'test_activity',
            'description' => '測試活動記錄',
            'user_id' => $this->adminUser->id,
            'ip_address' => '192.168.1.100',
            'result' => 'success',
            'risk_level' => 1,
            'properties' => ['test' => 'data']
        ]);

        $this->assertDatabaseHas('activities', [
            'type' => 'test_activity',
            'description' => '測試活動記錄',
            'user_id' => $this->adminUser->id
        ]);

        $this->assertEquals('test_activity', $activity->type);
        $this->assertEquals('測試活動記錄', $activity->description);
        $this->assertEquals($this->adminUser->id, $activity->user_id);

        $this->assertTrue(true, '基本活動記錄功能測試通過');
    }

    /**
     * 測試活動記錄查詢功能
     * 
     * @test
     */
    public function test_activity_querying()
    {
        // 建立測試資料
        Activity::create([
            'type' => 'user_login',
            'description' => '使用者登入',
            'user_id' => $this->adminUser->id,
            'result' => 'success',
            'risk_level' => 1
        ]);

        Activity::create([
            'type' => 'user_logout',
            'description' => '使用者登出',
            'user_id' => $this->adminUser->id,
            'result' => 'success',
            'risk_level' => 1
        ]);

        // 測試查詢
        $activities = Activity::where('user_id', $this->adminUser->id)->get();
        $this->assertCount(2, $activities);

        $loginActivity = Activity::where('type', 'user_login')->first();
        $this->assertNotNull($loginActivity);
        $this->assertEquals('使用者登入', $loginActivity->description);

        $this->assertTrue(true, '活動記錄查詢功能測試通過');
    }

    /**
     * 測試活動記錄路由存取
     * 
     * @test
     */
    public function test_activity_routes_access()
    {
        $this->actingAs($this->adminUser);

        // 測試主頁面
        $response = $this->get(route('admin.activities.index'));
        $response->assertStatus(200);

        // 測試統計頁面
        $response = $this->get(route('admin.activities.stats'));
        $response->assertStatus(200);

        // 測試監控頁面
        $response = $this->get(route('admin.activities.monitor'));
        $response->assertStatus(200);

        $this->assertTrue(true, '活動記錄路由存取測試通過');
    }

    /**
     * 測試活動記錄 Livewire 元件
     * 
     * @test
     */
    public function test_activity_livewire_components()
    {
        $this->actingAs($this->adminUser);

        // 建立測試活動記錄
        Activity::create([
            'type' => 'test_activity',
            'description' => '測試活動',
            'user_id' => $this->adminUser->id,
            'result' => 'success',
            'risk_level' => 1
        ]);

        // 測試 ActivityList 元件
        $component = \Livewire\Livewire::test(\App\Livewire\Admin\Activities\ActivityList::class);
        $component->assertStatus(200);

        // 測試 ActivityStats 元件
        $component = \Livewire\Livewire::test(\App\Livewire\Admin\Activities\ActivityStats::class);
        $component->assertStatus(200);

        $this->assertTrue(true, 'Livewire 元件測試通過');
    }

    /**
     * 測試資料庫效能
     * 
     * @test
     */
    public function test_database_performance()
    {
        // 建立測試資料
        $activities = [];
        for ($i = 0; $i < 100; $i++) {
            $activities[] = [
                'type' => 'performance_test',
                'description' => "效能測試 {$i}",
                'user_id' => $this->adminUser->id,
                'result' => 'success',
                'risk_level' => 1,
                'created_at' => now(),
                'updated_at' => now()
            ];
        }

        DB::table('activities')->insert($activities);

        $startTime = microtime(true);
        
        // 測試查詢效能
        $results = Activity::where('type', 'performance_test')
            ->orderBy('created_at', 'desc')
            ->limit(50)
            ->get();

        $queryTime = microtime(true) - $startTime;

        $this->assertCount(50, $results);
        $this->assertLessThan(0.5, $queryTime, '查詢時間應少於 0.5 秒');

        $this->assertTrue(true, '資料庫效能測試通過');
    }

    /**
     * 設定基本測試資料
     */
    private function setupBasicTestData(): void
    {
        // 建立基本權限
        $permissions = [
            'activity_logs.view' => '檢視活動日誌',
            'activity_logs.export' => '匯出活動日誌',
            'system.logs' => '檢視系統日誌',
            'dashboard.view' => '檢視儀表板'
        ];

        foreach ($permissions as $name => $displayName) {
            Permission::create([
                'name' => $name,
                'display_name' => $displayName,
                'module' => explode('.', $name)[0]
            ]);
        }

        // 建立管理員角色
        $adminRole = Role::create([
            'name' => 'admin',
            'display_name' => '管理員'
        ]);

        // 指派所有權限給管理員
        $adminRole->permissions()->attach(Permission::all());

        // 建立管理員使用者
        $this->adminUser = User::create([
            'username' => 'admin',
            'name' => '系統管理員',
            'email' => 'admin@example.com',
            'password' => Hash::make('password123'),
            'is_active' => true
        ]);

        // 指派管理員角色
        $this->adminUser->roles()->attach($adminRole);
    }
}