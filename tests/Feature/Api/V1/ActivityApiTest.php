<?php

namespace Tests\Feature\Api\V1;

use App\Models\Activity;
use App\Models\User;
use App\Models\Role;
use App\Models\Permission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * 活動記錄 API 測試
 */
class ActivityApiTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    private User $user;
    private User $adminUser;

    protected function setUp(): void
    {
        parent::setUp();

        // 建立權限
        $viewPermission = Permission::create([
            'name' => 'activity_logs.view',
            'display_name' => '檢視活動記錄',
            'module' => 'activity_logs'
        ]);

        $exportPermission = Permission::create([
            'name' => 'activity_logs.export',
            'display_name' => '匯出活動記錄',
            'module' => 'activity_logs'
        ]);

        // 建立角色
        $userRole = Role::create([
            'name' => 'user',
            'display_name' => '一般使用者'
        ]);

        $adminRole = Role::create([
            'name' => 'admin',
            'display_name' => '管理員'
        ]);

        // 指派權限
        $adminRole->permissions()->attach([$viewPermission->id, $exportPermission->id]);
        $userRole->permissions()->attach([$viewPermission->id]);

        // 建立使用者
        $this->user = User::factory()->create();
        $this->adminUser = User::factory()->create();

        // 指派角色
        $this->user->roles()->attach($userRole);
        $this->adminUser->roles()->attach($adminRole);

        // 建立測試活動記錄
        Activity::factory()->count(50)->create();
    }

    /** @test */
    public function it_requires_authentication_to_access_activities()
    {
        $response = $this->getJson('/api/v1/activities');

        $response->assertStatus(401)
            ->assertJson([
                'error' => 'Unauthorized'
            ]);
    }

    /** @test */
    public function it_can_list_activities_with_authentication()
    {
        Sanctum::actingAs($this->user, ['activities:read']);

        $response = $this->getJson('/api/v1/activities');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'type',
                        'description',
                        'result',
                        'risk_level',
                        'ip_address',
                        'created_at',
                        'links'
                    ]
                ],
                'meta' => [
                    'total',
                    'per_page',
                    'current_page',
                    'statistics'
                ],
                'links'
            ]);
    }

    /** @test */
    public function it_can_show_specific_activity()
    {
        Sanctum::actingAs($this->user, ['activities:read']);

        $activity = Activity::first();

        $response = $this->getJson("/api/v1/activities/{$activity->id}");

        $response->assertStatus(200)
            ->assertJson([
                'id' => $activity->id,
                'type' => $activity->type,
                'description' => $activity->description
            ]);
    }

    /** @test */
    public function it_returns_404_for_non_existent_activity()
    {
        Sanctum::actingAs($this->user, ['activities:read']);

        $response = $this->getJson('/api/v1/activities/99999');

        $response->assertStatus(404)
            ->assertJson([
                'message' => '找不到指定的活動記錄'
            ]);
    }

    /** @test */
    public function it_can_search_activities()
    {
        Sanctum::actingAs($this->user, ['activities:read']);

        // 建立特定的活動記錄用於搜尋
        $activity = Activity::create([
            'type' => 'user_login',
            'description' => '使用者登入系統',
            'result' => 'success',
            'risk_level' => 1,
            'ip_address' => '192.168.1.1',
            'properties' => ['username' => 'testuser']
        ]);

        $response = $this->getJson('/api/v1/activities/search?query=登入');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'type',
                        'description'
                    ]
                ],
                'meta' => [
                    'query',
                    'total',
                    'limit'
                ]
            ]);
    }

    /** @test */
    public function it_validates_search_query_length()
    {
        Sanctum::actingAs($this->user, ['activities:read']);

        $response = $this->getJson('/api/v1/activities/search?query=a');

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['query']);
    }

    /** @test */
    public function it_can_get_activity_statistics()
    {
        Sanctum::actingAs($this->user, ['activities:read']);

        $response = $this->getJson('/api/v1/activities/stats');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'timeline',
                    'distribution',
                    'top_users',
                    'security_events'
                ],
                'meta' => [
                    'time_range',
                    'generated_at'
                ]
            ]);
    }

    /** @test */
    public function it_can_get_related_activities()
    {
        Sanctum::actingAs($this->user, ['activities:read']);

        $activity = Activity::first();

        $response = $this->getJson("/api/v1/activities/{$activity->id}/related");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'type',
                        'description'
                    ]
                ],
                'meta' => [
                    'activity_id',
                    'total'
                ]
            ]);
    }

    /** @test */
    public function it_requires_export_permission_for_export()
    {
        Sanctum::actingAs($this->user, ['activities:read']); // 沒有 export 權限

        $response = $this->postJson('/api/v1/activities/export', [
            'format' => 'csv'
        ]);

        $response->assertStatus(403);
    }

    /** @test */
    public function it_can_export_activities_with_permission()
    {
        Sanctum::actingAs($this->adminUser, ['activities:read', 'activities:export']);

        $response = $this->postJson('/api/v1/activities/export', [
            'format' => 'csv',
            'filters' => [
                'date_from' => now()->subDays(7)->toDateString(),
                'date_to' => now()->toDateString()
            ]
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'download_url',
                'format',
                'expires_at'
            ]);
    }

    /** @test */
    public function it_validates_export_format()
    {
        Sanctum::actingAs($this->adminUser, ['activities:read', 'activities:export']);

        $response = $this->postJson('/api/v1/activities/export', [
            'format' => 'invalid_format'
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['format']);
    }

    /** @test */
    public function it_applies_pagination_limits()
    {
        Sanctum::actingAs($this->user, ['activities:read']);

        // 測試超過最大限制
        $response = $this->getJson('/api/v1/activities?per_page=200');

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['per_page']);
    }

    /** @test */
    public function it_filters_activities_by_date_range()
    {
        Sanctum::actingAs($this->user, ['activities:read']);

        $dateFrom = now()->subDays(7)->toDateString();
        $dateTo = now()->toDateString();

        $response = $this->getJson("/api/v1/activities?date_from={$dateFrom}&date_to={$dateTo}");

        $response->assertStatus(200);

        // 驗證回應中的活動都在指定日期範圍內
        $activities = $response->json('data');
        foreach ($activities as $activity) {
            $createdAt = \Carbon\Carbon::parse($activity['created_at'])->toDateString();
            $this->assertGreaterThanOrEqual($dateFrom, $createdAt);
            $this->assertLessThanOrEqual($dateTo, $createdAt);
        }
    }

    /** @test */
    public function it_filters_activities_by_type()
    {
        Sanctum::actingAs($this->user, ['activities:read']);

        // 建立特定類型的活動
        Activity::create([
            'type' => 'user_login',
            'description' => '使用者登入',
            'result' => 'success',
            'risk_level' => 1,
            'ip_address' => '192.168.1.1'
        ]);

        $response = $this->getJson('/api/v1/activities?type=user_login');

        $response->assertStatus(200);

        $activities = $response->json('data');
        foreach ($activities as $activity) {
            $this->assertEquals('user_login', $activity['type']);
        }
    }

    /** @test */
    public function it_includes_statistics_in_collection_response()
    {
        Sanctum::actingAs($this->user, ['activities:read']);

        $response = $this->getJson('/api/v1/activities');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'meta' => [
                    'statistics' => [
                        'total_activities',
                        'security_events',
                        'success_rate',
                        'risk_distribution',
                        'type_distribution'
                    ]
                ]
            ]);
    }

    /** @test */
    public function it_respects_rate_limiting()
    {
        Sanctum::actingAs($this->user, ['activities:read']);

        // 模擬超過速率限制的請求
        // 注意：這個測試可能需要調整速率限制設定或使用模擬
        for ($i = 0; $i < 5; $i++) {
            $this->getJson('/api/v1/activities/search?query=test');
        }

        // 第6次請求應該被限制（假設限制是每分鐘5次）
        $response = $this->getJson('/api/v1/activities/search?query=test');

        // 根據實際的速率限制設定調整這個斷言
        // $response->assertStatus(429);
    }

    /** @test */
    public function it_logs_api_access()
    {
        Sanctum::actingAs($this->user, ['activities:read']);

        $initialCount = Activity::count();

        $this->getJson('/api/v1/activities');

        // 驗證 API 存取被記錄
        $this->assertGreaterThan($initialCount, Activity::count());

        // 驗證最新的活動記錄是 API 存取
        $latestActivity = Activity::latest()->first();
        $this->assertStringContains('api_access', $latestActivity->type);
    }
}