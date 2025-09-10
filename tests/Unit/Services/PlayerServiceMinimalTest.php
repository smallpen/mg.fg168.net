<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\PlayerService;
use App\Services\PointService;
use App\Services\ActivityLogger;
use Mockery;

/**
 * 玩家服務最小測試
 * 
 * 只測試基本實例化，不涉及資料庫操作
 */
class PlayerServiceMinimalTest extends TestCase
{
    /**
     * 測試 PlayerService 實例化
     * 
     * @test
     */
    public function test_player_service_can_be_instantiated()
    {
        $mockPointService = Mockery::mock(PointService::class);
        $mockActivityLogger = Mockery::mock(ActivityLogger::class);

        $playerService = new PlayerService($mockPointService, $mockActivityLogger);

        $this->assertInstanceOf(PlayerService::class, $playerService);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}