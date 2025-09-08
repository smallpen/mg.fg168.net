<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\AgentService;
use App\Services\PointService;
use App\Services\ActivityLogger;
use App\Models\Agent;
use App\Models\User;
use App\Exceptions\InsufficientPointsException;
use App\Exceptions\InvalidPrefixException;
use App\Exceptions\PrefixAlreadyExistsException;
use App\Exceptions\AgentHasDependenciesException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Mockery;

/**
 * 代理服務單元測試
 * 
 * 測試代理管理的核心業務邏輯，包含建立、更新、刪除等功能
 */
class AgentServiceTest extends TestCase
{
    use RefreshDatabase;

    protected AgentService $agentService;
    protected $pointServiceMock;
    protected $activityLoggerMock;
    protected User $testUser;

    protected function setUp(): void
    {
        parent::setUp();

        // 建立測試使用者
        $this->testUser = User::factory()->create([
            'username' => 'testuser',
            'name' => '測試使用者',
        ]);

        // 模擬登入
        $this->actingAs($this->testUser);

        // 建立 Mock 物件
        $this->pointServiceMock = Mockery::mock(PointService::class);
        $this->activityLoggerMock = Mockery::mock(ActivityLogger::class);

        // 建立服務實例
        $this->agentService = new AgentService(
            $this->pointServiceMock,
            $this->activityLoggerMock
        );
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /**
     * 測試建立第一層代理
     */
    public function test_create_first_level_agent_successfully()
    {
        // 準備測試資料
        $agentData = [
            'name' => '測試代理',
            'username' => 'testagent',
            'email' => 'test@example.com',
            'phone' => '0912345678',
            'prefix' => 'a',
            'initial_points' => 1000.00,
            'is_active' => true,
            'notes' => '測試代理備註',
        ];

        // 設定 Mock 期望
        $this->pointServiceMock
            ->shouldReceive('allocatePointsToAgent')
            ->once()
            ->with(
                Mockery::type(Agent::class),
                1000.00,
                null
            );

        $this->activityLoggerMock
            ->shouldReceive('log')
            ->once()
            ->with(
                'agent_created',
                Mockery::type(Agent::class),
                Mockery::type('array')
            );

        // 執行測試
        $agent = $this->agentService->createAgent($agentData);

        // 驗證結果
        $this->assertInstanceOf(Agent::class, $agent);
        $this->assertEquals('測試代理', $agent->name);
        $this->assertEquals('testagent', $agent->username);
        $this->assertEquals('atestagent', $agent->account);
        $this->assertEquals('a', $agent->prefix);
        $this->assertEquals(1, $agent->level);
        $this->assertNull($agent->parent_id);
        $this->assertEquals($this->testUser->id, $agent->created_by);
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
        // 建立上層代理
        $parentAgent = Agent::factory()->create([
            'name' => '上層代理',
            'username' => 'parent',
            'account' => 'aparent',
            'prefix' => 'a',
            'level' => 1,
            'remaining_points' => 2000.00,
        ]);

        // 準備測試資料
        $agentData = [
            'name' => '下層代理',
            'username' => 'subagent',
            'email' => 'sub@example.com',
            'parent_id' => $parentAgent->id,
            'initial_points' => 500.00,
            'is_active' => true,
        ];

        // 設定 Mock 期望
        $this->pointServiceMock
            ->shouldReceive('allocatePointsToAgent')
            ->once()
            ->with(
                Mockery::type(Agent::class),
                500.00,
                $parentAgent
            );

        $this->activityLoggerMock
            ->shouldReceive('log')
            ->once();

        // 執行測試
        $agent = $this->agentService->createAgent($agentData);

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
        $this->expectException(InvalidPrefixException::class);
        $this->expectExceptionMessage('前置符號必須是 a-z 的單一字母');

        $agentData = [
            'name' => '測試代理',
            'username' => 'testagent',
            'email' => 'test@example.com',
            'prefix' => 'A', // 大寫字母，應該失敗
        ];

        $this->agentService->createAgent($agentData);
    }

    /**
     * 測試前置符號驗證 - 已被使用
     */
    public function test_create_agent_with_existing_prefix()
    {
        // 建立已存在的代理
        Agent::factory()->create([
            'prefix' => 'a',
            'level' => 1,
        ]);

        $this->expectException(PrefixAlreadyExistsException::class);
        $this->expectExceptionMessage("前置符號 'a' 已被使用");

        $agentData = [
            'name' => '測試代理',
            'username' => 'testagent',
            'email' => 'test@example.com',
            'prefix' => 'a', // 重複的前置符號
        ];

        $this->agentService->createAgent($agentData);
    }

    /**
     * 測試更新代理資料
     */
    public function test_update_agent_successfully()
    {
        // 建立測試代理
        $agent = Agent::factory()->create([
            'name' => '原始名稱',
            'username' => 'original',
            'account' => 'aoriginal',
            'email' => 'original@example.com',
            'prefix' => 'a',
            'level' => 1,
        ]);

        // 準備更新資料
        $updateData = [
            'name' => '更新名稱',
            'email' => 'updated@example.com',
            'phone' => '0987654321',
            'notes' => '更新備註',
        ];

        // 設定 Mock 期望
        $this->activityLoggerMock
            ->shouldReceive('log')
            ->once()
            ->with(
                'agent_updated',
                $agent,
                Mockery::type('array')
            );

        // 執行測試
        $updatedAgent = $this->agentService->updateAgent($agent, $updateData);

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

    /**
     * 測試更新代理用戶名
     */
    public function test_update_agent_username_updates_account()
    {
        // 建立測試代理
        $agent = Agent::factory()->create([
            'username' => 'original',
            'account' => 'aoriginal',
            'prefix' => 'a',
            'level' => 1,
        ]);

        // 建立下層代理
        $subAgent = Agent::factory()->create([
            'username' => 'sub',
            'account' => 'asub',
            'level' => 2,
            'parent_id' => $agent->id,
        ]);

        // 設定 Mock 期望
        $this->activityLoggerMock
            ->shouldReceive('log')
            ->once();

        // 執行測試
        $updateData = ['username' => 'newname'];
        $updatedAgent = $this->agentService->updateAgent($agent, $updateData);

        // 驗證結果
        $this->assertEquals('newname', $updatedAgent->username);
        $this->assertEquals('anewname', $updatedAgent->account);

        // 驗證下層代理帳號也被更新
        $subAgent->refresh();
        $this->assertEquals('asub', $subAgent->account); // 下層代理帳號應該保持不變
    }

    /**
     * 測試刪除代理 - 成功
     */
    public function test_delete_agent_successfully()
    {
        // 建立測試代理
        $agent = Agent::factory()->create([
            'remaining_points' => 100.00,
        ]);

        // 建立上層代理
        $parentAgent = Agent::factory()->create();
        $agent->parent_id = $parentAgent->id;
        $agent->save();

        // 設定 Mock 期望
        $this->pointServiceMock
            ->shouldReceive('recoverPointsFromAgent')
            ->once()
            ->with($agent, 100.00, $parentAgent);

        $this->activityLoggerMock
            ->shouldReceive('log')
            ->once()
            ->with(
                'agent_deleted',
                $agent,
                Mockery::type('array')
            );

        // 執行測試
        $result = $this->agentService->deleteAgent($agent);

        // 驗證結果
        $this->assertTrue($result);
        $this->assertSoftDeleted('agents', ['id' => $agent->id]);
    }

    /**
     * 測試刪除代理 - 有下層關聯
     */
    public function test_delete_agent_with_dependencies_fails()
    {
        // 建立測試代理
        $agent = Agent::factory()->create();

        // 建立下層代理
        Agent::factory()->create(['parent_id' => $agent->id]);

        $this->expectException(AgentHasDependenciesException::class);
        $this->expectExceptionMessage("代理 {$agent->name} 有下層代理或玩家，無法刪除");

        $this->agentService->deleteAgent($agent);
    }

    /**
     * 測試取得代理統計資訊
     */
    public function test_get_agent_statistics()
    {
        // 建立測試代理
        $agent = Agent::factory()->create([
            'total_points' => 1000.00,
            'allocated_points' => 600.00,
            'remaining_points' => 400.00,
        ]);

        // 建立下層代理
        Agent::factory()->count(2)->create([
            'parent_id' => $agent->id,
            'is_active' => true,
        ]);

        Agent::factory()->create([
            'parent_id' => $agent->id,
            'is_active' => false,
        ]);

        // 執行測試
        $statistics = $this->agentService->getAgentStatistics($agent);

        // 驗證結果
        $this->assertEquals(3, $statistics['total_children']);
        $this->assertEquals(2, $statistics['active_children']);
        $this->assertEquals(1000.00, $statistics['total_points']);
        $this->assertEquals(600.00, $statistics['allocated_points']);
        $this->assertEquals(400.00, $statistics['remaining_points']);
        $this->assertEquals(60.0, $statistics['points_utilization_rate']);
    }

    /**
     * 測試檢查代理是否可以建立下層代理
     */
    public function test_can_create_sub_agent()
    {
        // 建立測試代理
        $agent = Agent::factory()->create([
            'is_active' => true,
            'remaining_points' => 500.00,
        ]);

        // 測試可以建立
        $this->assertTrue($this->agentService->canCreateSubAgent($agent, 300.00));

        // 測試點數不足
        $this->assertFalse($this->agentService->canCreateSubAgent($agent, 600.00));

        // 測試代理未啟用
        $agent->is_active = false;
        $agent->save();
        $this->assertFalse($this->agentService->canCreateSubAgent($agent, 300.00));
    }

    /**
     * 測試取得可用前置符號
     */
    public function test_get_available_prefixes()
    {
        // 建立一些已使用的前置符號
        Agent::factory()->create(['prefix' => 'a', 'level' => 1]);
        Agent::factory()->create(['prefix' => 'b', 'level' => 1]);
        Agent::factory()->create(['prefix' => 'z', 'level' => 1]);

        // 執行測試
        $availablePrefixes = $this->agentService->getAvailablePrefixes();

        // 驗證結果
        $this->assertIsArray($availablePrefixes);
        $this->assertNotContains('a', $availablePrefixes);
        $this->assertNotContains('b', $availablePrefixes);
        $this->assertNotContains('z', $availablePrefixes);
        $this->assertContains('c', $availablePrefixes);
        $this->assertContains('d', $availablePrefixes);
    }

    /**
     * 測試資料庫交易回滾
     */
    public function test_create_agent_transaction_rollback_on_exception()
    {
        // 設定 Mock 拋出例外
        $this->pointServiceMock
            ->shouldReceive('allocatePointsToAgent')
            ->once()
            ->andThrow(new InsufficientPointsException('測試例外'));

        $agentData = [
            'name' => '測試代理',
            'username' => 'testagent',
            'email' => 'test@example.com',
            'prefix' => 'a',
            'initial_points' => 1000.00,
        ];

        // 執行測試並期望例外
        $this->expectException(InsufficientPointsException::class);

        try {
            $this->agentService->createAgent($agentData);
        } catch (InsufficientPointsException $e) {
            // 驗證資料庫沒有建立記錄
            $this->assertDatabaseMissing('agents', [
                'username' => 'testagent',
            ]);
            
            throw $e;
        }
    }

    /**
     * 測試更新前置符號
     */
    public function test_update_agent_prefix_updates_descendant_accounts()
    {
        // 建立第一層代理
        $rootAgent = Agent::factory()->create([
            'username' => 'root',
            'account' => 'aroot',
            'prefix' => 'a',
            'level' => 1,
        ]);

        // 建立第二層代理
        $subAgent = Agent::factory()->create([
            'username' => 'sub',
            'account' => 'asub',
            'level' => 2,
            'parent_id' => $rootAgent->id,
        ]);

        // 設定 Mock 期望
        $this->activityLoggerMock
            ->shouldReceive('log')
            ->once();

        // 執行測試 - 更新前置符號
        $updateData = ['prefix' => 'b'];
        $this->agentService->updateAgent($rootAgent, $updateData);

        // 驗證結果
        $rootAgent->refresh();
        $subAgent->refresh();

        $this->assertEquals('b', $rootAgent->prefix);
        $this->assertEquals('broot', $rootAgent->account);
        $this->assertEquals('bsub', $subAgent->account);
    }
}