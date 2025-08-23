<?php

namespace Tests\Unit\Repositories;

use Tests\TestCase;
use App\Models\Activity;
use App\Models\User;
use App\Repositories\ActivityRepository;
use App\Repositories\Contracts\ActivityRepositoryInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

/**
 * 活動記錄資料存取層測試
 */
class ActivityRepositoryTest extends TestCase
{
    use RefreshDatabase;

    protected ActivityRepositoryInterface $repository;
    protected User $testUser;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->repository = app(ActivityRepositoryInterface::class);
        $this->testUser = User::factory()->create();
        
        // 清除快取
        Cache::flush();
    }

    /** @test */
    public function it_can_get_paginated_activities()
    {
        // 建立測試資料
        Activity::factory()->count(25)->create();

        $result = $this->repository->getPaginatedActivities([], 10);

        $this->assertEquals(10, $result->perPage());
        $this->assertEquals(25, $result->total());
        $this->assertEquals(3, $result->lastPage());
    }

    /** @test */
    public function it_can_filter_activities_by_user()
    {
        $otherUser = User::factory()->create();
        
        Activity::factory()->count(5)->create(['user_id' => $this->testUser->id]);
        Activity::factory()->count(3)->create(['user_id' => $otherUser->id]);

        $result = $this->repository->getPaginatedActivities([
            'user_id' => $this->testUser->id
        ], 10);

        $this->assertEquals(5, $result->total());
        
        foreach ($result->items() as $activity) {
            $this->assertEquals($this->testUser->id, $activity->user_id);
        }
    }

    /** @test */
    public function it_can_filter_activities_by_type()
    {
        Activity::factory()->count(3)->create(['type' => 'login']);
        Activity::factory()->count(2)->create(['type' => 'logout']);
        Activity::factory()->count(1)->create(['type' => 'create_user']);

        $result = $this->repository->getPaginatedActivities([
            'type' => 'login'
        ], 10);

        $this->assertEquals(3, $result->total());
        
        foreach ($result->items() as $activity) {
            $this->assertEquals('login', $activity->type);
        }
    }

    /** @test */
    public function it_can_filter_activities_by_date_range()
    {
        $startDate = Carbon::now()->subDays(10);
        $endDate = Carbon::now()->subDays(5);

        Activity::factory()->create(['created_at' => $startDate->copy()->addDays(2)]);
        Activity::factory()->create(['created_at' => $startDate->copy()->addDays(3)]);
        Activity::factory()->create(['created_at' => Carbon::now()->subDays(2)]); // 超出範圍

        $result = $this->repository->getPaginatedActivities([
            'date_from' => $startDate->format('Y-m-d'),
            'date_to' => $endDate->format('Y-m-d')
        ], 10);

        $this->assertEquals(2, $result->total());
    }

    /** @test */
    public function it_can_search_activities()
    {
        Activity::factory()->create(['description' => '使用者登入系統']);
        Activity::factory()->create(['description' => '建立新使用者']);
        Activity::factory()->create(['description' => '更新權限設定']);

        $result = $this->repository->searchActivities('使用者');

        $this->assertEquals(2, $result->count());
    }

    /** @test */
    public function it_can_get_activity_by_id()
    {
        $activity = Activity::factory()->create();

        $result = $this->repository->getActivityById($activity->id);

        $this->assertNotNull($result);
        $this->assertEquals($activity->id, $result->id);
        $this->assertEquals($activity->type, $result->type);
    }

    /** @test */
    public function it_returns_null_for_non_existent_activity()
    {
        $result = $this->repository->getActivityById(999);

        $this->assertNull($result);
    }

    /** @test */
    public function it_can_get_related_activities()
    {
        $activity = Activity::factory()->create([
            'user_id' => $this->testUser->id,
            'ip_address' => '192.168.1.1',
            'created_at' => Carbon::now()
        ]);

        // 建立相關活動
        Activity::factory()->create([
            'user_id' => $this->testUser->id,
            'created_at' => Carbon::now()->subHours(2)
        ]);
        
        Activity::factory()->create([
            'ip_address' => '192.168.1.1',
            'created_at' => Carbon::now()->subHours(1)
        ]);

        // 建立不相關的活動
        Activity::factory()->create([
            'user_id' => User::factory()->create()->id,
            'ip_address' => '10.0.0.1',
            'created_at' => Carbon::now()->subDays(2)
        ]);

        $result = $this->repository->getRelatedActivities($activity);

        $this->assertEquals(2, $result->count());
    }

    /** @test */
    public function it_can_get_recent_activities()
    {
        Activity::factory()->count(15)->create();

        $result = $this->repository->getRecentActivities(10);

        $this->assertEquals(10, $result->count());
        
        // 檢查是否按時間倒序排列
        $timestamps = $result->pluck('created_at')->toArray();
        $sortedTimestamps = collect($timestamps)->sortDesc()->values()->toArray();
        $this->assertEquals($sortedTimestamps, $timestamps);
    }

    /** @test */
    public function it_can_get_activities_by_user()
    {
        Activity::factory()->count(5)->create(['user_id' => $this->testUser->id]);
        Activity::factory()->count(3)->create(['user_id' => User::factory()->create()->id]);

        $result = $this->repository->getActivitiesByUser($this->testUser->id);

        $this->assertEquals(5, $result->count());
        
        foreach ($result as $activity) {
            $this->assertEquals($this->testUser->id, $activity->user_id);
        }
    }

    /** @test */
    public function it_can_get_activities_by_type()
    {
        Activity::factory()->count(4)->create(['type' => 'login']);
        Activity::factory()->count(2)->create(['type' => 'logout']);

        $result = $this->repository->getActivitiesByType('login');

        $this->assertEquals(4, $result->count());
        
        foreach ($result as $activity) {
            $this->assertEquals('login', $activity->type);
        }
    }

    /** @test */
    public function it_can_get_activities_by_module()
    {
        Activity::factory()->count(3)->create(['module' => 'users']);
        Activity::factory()->count(2)->create(['module' => 'roles']);

        $result = $this->repository->getActivitiesByModule('users');

        $this->assertEquals(3, $result->count());
        
        foreach ($result as $activity) {
            $this->assertEquals('users', $activity->module);
        }
    }

    /** @test */
    public function it_can_get_activity_stats()
    {
        // 建立不同類型的活動
        Activity::factory()->count(5)->create([
            'type' => 'login',
            'result' => 'success',
            'created_at' => Carbon::now()->subDays(2)
        ]);
        
        Activity::factory()->count(2)->create([
            'type' => 'login_failed',
            'result' => 'failed',
            'created_at' => Carbon::now()->subDays(1)
        ]);

        $stats = $this->repository->getActivityStats('7d');

        $this->assertIsArray($stats);
        $this->assertArrayHasKey('total_activities', $stats);
        $this->assertArrayHasKey('unique_users', $stats);
        $this->assertArrayHasKey('security_events', $stats);
        $this->assertArrayHasKey('success_rate', $stats);
        $this->assertEquals(7, $stats['total_activities']);
    }

    /** @test */
    public function it_can_get_security_events()
    {
        Activity::factory()->count(3)->create(['type' => 'login_failed']);
        Activity::factory()->count(2)->create(['type' => 'permission_escalation']);
        Activity::factory()->count(1)->create(['type' => 'login']); // 非安全事件

        $result = $this->repository->getSecurityEvents('7d');

        $this->assertEquals(5, $result->count());
        
        foreach ($result as $activity) {
            $this->assertTrue(in_array($activity->type, [
                'login_failed', 'permission_escalation', 'sensitive_data_access',
                'system_config_change', 'suspicious_ip_access', 'bulk_operation'
            ]));
        }
    }

    /** @test */
    public function it_can_get_top_users()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        Activity::factory()->count(5)->create(['user_id' => $user1->id]);
        Activity::factory()->count(3)->create(['user_id' => $user2->id]);
        Activity::factory()->count(1)->create(['user_id' => $this->testUser->id]);

        $result = $this->repository->getTopUsers('7d', 5);

        $this->assertEquals(3, $result->count());
        
        // 檢查是否按活動數量排序
        $this->assertEquals($user1->id, $result->first()->user_id);
        $this->assertEquals(5, $result->first()->activity_count);
    }

    /** @test */
    public function it_can_get_today_stats()
    {
        Activity::factory()->count(3)->create(['created_at' => Carbon::today()]);
        Activity::factory()->count(2)->create(['created_at' => Carbon::yesterday()]);

        $stats = $this->repository->getTodayStats();

        $this->assertIsArray($stats);
        $this->assertArrayHasKey('total_activities', $stats);
        $this->assertEquals(3, $stats['total_activities']);
    }

    /** @test */
    public function it_can_cleanup_old_activities()
    {
        Activity::factory()->count(5)->create(['created_at' => Carbon::now()->subDays(100)]);
        Activity::factory()->count(3)->create(['created_at' => Carbon::now()->subDays(10)]);

        $deletedCount = $this->repository->cleanupOldActivities(30);

        $this->assertEquals(5, $deletedCount);
        $this->assertEquals(3, Activity::count());
    }

    /** @test */
    public function it_can_create_batch_activities()
    {
        $activities = [
            [
                'type' => 'login',
                'description' => '使用者登入',
                'user_id' => $this->testUser->id,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'type' => 'logout',
                'description' => '使用者登出',
                'user_id' => $this->testUser->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        ];

        $result = $this->repository->createBatch($activities);

        $this->assertTrue($result);
        $this->assertEquals(2, Activity::count());
    }

    /** @test */
    public function it_can_detect_suspicious_patterns()
    {
        // 建立多次登入失敗記錄
        Activity::factory()->count(6)->create([
            'type' => 'login_failed',
            'user_id' => $this->testUser->id,
            'created_at' => Carbon::now()->subHours(2)
        ]);

        $patterns = $this->repository->detectSuspiciousPatterns($this->testUser->id, '1d');

        $this->assertIsArray($patterns);
        $this->assertNotEmpty($patterns);
        
        $failedLoginPattern = collect($patterns)->firstWhere('type', 'excessive_failed_logins');
        $this->assertNotNull($failedLoginPattern);
        $this->assertEquals('high', $failedLoginPattern['severity']);
    }

    /** @test */
    public function it_caches_recent_activities()
    {
        Activity::factory()->count(5)->create();

        // 第一次呼叫
        $result1 = $this->repository->getRecentActivities(5);
        
        // 新增更多活動
        Activity::factory()->count(3)->create();
        
        // 第二次呼叫應該回傳快取結果
        $result2 = $this->repository->getRecentActivities(5);
        
        $this->assertEquals($result1->count(), $result2->count());
        $this->assertEquals($result1->pluck('id'), $result2->pluck('id'));
    }

    /** @test */
    public function it_can_verify_integrity()
    {
        // 建立有簽章的活動
        Activity::factory()->count(3)->create(['signature' => 'valid_signature']);
        
        // 建立沒有簽章的活動
        Activity::factory()->count(2)->create(['signature' => null]);

        $result = $this->repository->verifyIntegrity();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('total_records', $result);
        $this->assertArrayHasKey('records_with_signature', $result);
        $this->assertEquals(5, $result['total_records']);
        $this->assertEquals(3, $result['records_with_signature']);
    }
}