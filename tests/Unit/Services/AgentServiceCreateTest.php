<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\AgentService;
use App\Models\Agent;
use App\Models\User;
use App\Exceptions\InvalidPrefixException;
use App\Exceptions\PrefixAlreadyExistsException;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * 代理服務建立功能測試
 */
class AgentServiceCreateTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 測試建立第一層代理（不分配點數）
     */
    public function test_create_first_level_agent_without_points()
    {
        // 建立測試使用者
        $user = User::factory()->create();
        $this->actingAs($user);

        // 準備測試資料
        $agentData = [
            'name' => '測試代理',
            'username' => 'testagent',
            'email' => 'test@example.com',
            'phone' => '0912345678',
            'prefix' => 'a',
            'is_active' => true,
            'notes' => '測試代理備註',
        ];

        // 建立服務實例
        $agentService = app(AgentService::class);

        // 執行測試
        $agent = $agentService->createAgent($agentData);

        // 驗證結果
        $this->assertInstanceOf(Agent::class, $agent);
        $this->assertEquals('測試代理', $agent->name);
        $this->assertEquals('testagent', $agent->username);
        $this->assertEquals('atestagent', $agent->account);
        $this->assertEquals('a', $agent->prefix);
        $this->assertEquals(1, $agent->level);
        $this->assertNull($agent->parent_id);
        $this->assertEquals($user->id, $agent->created_by);
        $this->assertTrue($agent->is_active);

        // 驗證資料庫記錄
        $this->assertDatabaseHas('agents', [
            'name' => '測試代理',
            'username' => 'testagent',
            'account' => 'atestagent',
            'prefix' => 'a',
            'level' => 1,
            'parent_id' => null,
        ]);
    }

    /**
     * 測試建立下層代理
     */
    public function test_create_sub_agent_successfully()
    {
        // 建立測試使用者
        $user = User::factory()->create();
        $this->actingAs($user);

        // 建立上層代理
        $parentAgent = Agent::create([
            'name' => '上層代理',
            'username' => 'parent',
            'account' => 'aparent',
            'email' => 'parent@example.com',
            'prefix' => 'a',
            'level' => 1,
            'total_points' => 2000,
            'allocated_points' => 0,
            'remaining_points' => 2000,
            'is_active' => true,
            'created_by' => $user->id,
        ]);

        // 準備測試資料
        $agentData = [
            'name' => '下層代理',
            'username' => 'subagent',
            'email' => 'sub@example.com',
            'parent_id' => $parentAgent->id,
            'is_active' => true,
        ];

        // 建立服務實例
        $agentService = app(AgentService::class);

        // 執行測試
        $agent = $agentService->createAgent($agentData);

        // 驗證結果
        $this->assertEquals('下層代理', $agent->name);
        $this->assertEquals('subagent', $agent->username);
        $this->assertEquals('asubagent', $agent->account);
        $this->assertNull($agent->prefix);
        $this->assertEquals(2, $agent->level);
        $this->assertEquals($parentAgent->id, $agent->parent_id);
    }

    /**
     * 測試前置符號驗證 - 格式錯誤
     */
    public function test_create_agent_with_invalid_prefix_format()
    {
        // 建立測試使用者
        $user = User::factory()->create();
        $this->actingAs($user);

        $this->expectException(InvalidPrefixException::class);
        $this->expectExceptionMessage('前置符號必須是 a-z 的單一字母');

        $agentData = [
            'name' => '測試代理',
            'username' => 'testagent',
            'email' => 'test@example.com',
            'prefix' => 'A', // 大寫字母，應該失敗
        ];

        $agentService = app(AgentService::class);
        $agentService->createAgent($agentData);
    }

    /**
     * 測試前置符號驗證 - 已被使用
     */
    public function test_create_agent_with_existing_prefix()
    {
        // 建立測試使用者
        $user = User::factory()->create();
        $this->actingAs($user);

        // 建立已存在的代理
        Agent::create([
            'name' => '現有代理',
            'username' => 'existing',
            'account' => 'aexisting',
            'email' => 'existing@example.com',
            'prefix' => 'a',
            'level' => 1,
            'total_points' => 1000,
            'allocated_points' => 0,
            'remaining_points' => 1000,
            'is_active' => true,
            'created_by' => $user->id,
        ]);

        $this->expectException(PrefixAlreadyExistsException::class);
        $this->expectExceptionMessage("前置符號 'a' 已被使用");

        $agentData = [
            'name' => '測試代理',
            'username' => 'testagent',
            'email' => 'test@example.com',
            'prefix' => 'a', // 重複的前置符號
        ];

        $agentService = app(AgentService::class);
        $agentService->createAgent($agentData);
    }

    /**
     * 測試更新代理資料
     */
    public function test_update_agent_successfully()
    {
        // 建立測試使用者
        $user = User::factory()->create();
        $this->actingAs($user);

        // 建立測試代理
        $agent = Agent::create([
            'name' => '原始名稱',
            'username' => 'original',
            'account' => 'aoriginal',
            'email' => 'original@example.com',
            'prefix' => 'a',
            'level' => 1,
            'total_points' => 1000,
            'allocated_points' => 0,
            'remaining_points' => 1000,
            'is_active' => true,
            'created_by' => $user->id,
        ]);

        // 準備更新資料
        $updateData = [
            'name' => '更新名稱',
            'email' => 'updated@example.com',
            'phone' => '0987654321',
            'notes' => '更新備註',
        ];

        // 建立服務實例
        $agentService = app(AgentService::class);

        // 執行測試
        $updatedAgent = $agentService->updateAgent($agent, $updateData);

        // 驗證結果
        $this->assertEquals('更新名稱', $updatedAgent->name);
        $this->assertEquals('updated@example.com', $updatedAgent->email);
        $this->assertEquals('0987654321', $updatedAgent->phone);
        $this->assertEquals('更新備註', $updatedAgent->notes);

        // 驗證資料庫記錄
        $this->assertDatabaseHas('agents', [
            'id' => $agent->id,
            'name' => '更新名稱',
            'email' => 'updated@example.com',
        ]);
    }
}