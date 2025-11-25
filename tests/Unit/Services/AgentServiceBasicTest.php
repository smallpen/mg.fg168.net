<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\AgentService;
use App\Services\PointService;
use App\Services\ActivityLogger;
use App\Models\Agent;
use App\Models\User;
use App\Exceptions\InvalidPrefixException;
use App\Exceptions\PrefixAlreadyExistsException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;

/**
 * 代理服務基本測試
 * 
 * 測試代理管理的核心功能
 */
class AgentServiceBasicTest extends TestCase
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
}