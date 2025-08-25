<?php

namespace Tests\Traits;

use App\Services\PermissionSecurityService;
use Mockery;

/**
 * 測試中停用權限安全檢查的 Trait
 */
trait DisablesPermissionSecurity
{
    /**
     * 停用權限安全檢查
     */
    protected function disablePermissionSecurity(): void
    {
        // 模擬 PermissionSecurityService，讓所有權限檢查都通過
        $mockSecurityService = Mockery::mock(PermissionSecurityService::class);
        $mockSecurityService->shouldReceive('checkMultiLevelPermission')
            ->andReturn(true);
        $mockSecurityService->shouldReceive('logSecurityEvent')
            ->andReturn(true);
        
        $this->app->instance(PermissionSecurityService::class, $mockSecurityService);
    }

    /**
     * 在測試設定中自動停用權限安全檢查
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->disablePermissionSecurity();
    }
}