<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\AgentService;
use App\Models\Agent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * 代理服務簡單測試
 */
class AgentServiceSimpleTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 測試取得可用前置符號
     */
    public function test_get_available_prefixes_basic()
    {
        // 建立測試使用者
        $user = User::factory()->create();
        $this->actingAs($user);

        // 建立一些已使用的前置符號
        Agent::create([
            'name' => '測試代理A',
            'username' => 'agenta',
            'account' => 'aagenta',
            'email' => 'a@test.com',
            'prefix' => 'a',
            'level' => 1,
            'total_points' => 1000,
            'allocated_points' => 0,
            'remaining_points' => 1000,
            'is_active' => true,
            'created_by' => $user->id,
        ]);

        Agent::create([
            'name' => '測試代理B',
            'username' => 'agentb',
            'account' => 'bagentb',
            'email' => 'b@test.com',
            'prefix' => 'b',
            'level' => 1,
            'total_points' => 1000,
            'allocated_points' => 0,
            'remaining_points' => 1000,
            'is_active' => true,
            'created_by' => $user->id,
        ]);

        // 建立服務實例（使用實際的依賴）
        $agentService = app(AgentService::class);

        // 執行測試
        $availablePrefixes = $agentService->getAvailablePrefixes();

        // 驗證結果
        $this->assertIsArray($availablePrefixes);
        $this->assertNotContains('a', $availablePrefixes);
        $this->assertNotContains('b', $availablePrefixes);
        $this->assertContains('c', $availablePrefixes);
        $this->assertContains('d', $availablePrefixes);
    }

    /**
     * 測試檢查代理是否可以建立下層代理
     */
    public function test_can_create_sub_agent_basic()
    {
        // 建立測試使用者
        $user = User::factory()->create();
        $this->actingAs($user);

        // 建立測試代理
        $agent = Agent::create([
            'name' => '測試代理',
            'username' => 'testagent',
            'account' => 'atestagent',
            'email' => 'test@example.com',
            'prefix' => 'a',
            'level' => 1,
            'total_points' => 1000,
            'allocated_points' => 0,
            'remaining_points' => 500,
            'is_active' => true,
            'created_by' => $user->id,
        ]);

        // 建立服務實例
        $agentService = app(AgentService::class);

        // 測試可以建立
        $this->assertTrue($agentService->canCreateSubAgent($agent, 300.00));

        // 測試點數不足
        $this->assertFalse($agentService->canCreateSubAgent($agent, 600.00));

        // 測試代理未啟用
        $agent->is_active = false;
        $agent->save();
        $this->assertFalse($agentService->canCreateSubAgent($agent, 300.00));
    }

    /**
     * 測試取得代理統計資訊
     */
    public function test_get_agent_statistics_basic()
    {
        // 建立測試使用者
        $user = User::factory()->create();
        $this->actingAs($user);

        // 建立測試代理
        $agent = Agent::create([
            'name' => '測試代理',
            'username' => 'testagent',
            'account' => 'atestagent',
            'email' => 'test@example.com',
            'prefix' => 'a',
            'level' => 1,
            'total_points' => 1000,
            'allocated_points' => 600,
            'remaining_points' => 400,
            'is_active' => true,
            'created_by' => $user->id,
        ]);

        // 建立下層代理
        Agent::create([
            'name' => '下層代理1',
            'username' => 'sub1',
            'account' => 'asub1',
            'email' => 'sub1@example.com',
            'level' => 2,
            'parent_id' => $agent->id,
            'total_points' => 300,
            'allocated_points' => 0,
            'remaining_points' => 300,
            'is_active' => true,
            'created_by' => $user->id,
        ]);

        Agent::create([
            'name' => '下層代理2',
            'username' => 'sub2',
            'account' => 'asub2',
            'email' => 'sub2@example.com',
            'level' => 2,
            'parent_id' => $agent->id,
            'total_points' => 200,
            'allocated_points' => 0,
            'remaining_points' => 200,
            'is_active' => false,
            'created_by' => $user->id,
        ]);

        // 建立服務實例
        $agentService = app(AgentService::class);

        // 執行測試
        $statistics = $agentService->getAgentStatistics($agent);

        // 驗證結果
        $this->assertEquals(2, $statistics['total_children']);
        $this->assertEquals(1, $statistics['active_children']);
        $this->assertEquals(1000.00, $statistics['total_points']);
        $this->assertEquals(600.00, $statistics['allocated_points']);
        $this->assertEquals(400.00, $statistics['remaining_points']);
        $this->assertEquals(60.0, $statistics['points_utilization_rate']);
    }
}