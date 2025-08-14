<?php

namespace Tests\Unit;

use App\Repositories\Contracts\UserRepositoryInterface;
use App\Repositories\UserRepository;
use Tests\TestCase;

/**
 * 資料存取層服務提供者測試
 */
class RepositoryServiceProviderTest extends TestCase
{
    /**
     * 測試使用者資料存取層介面綁定
     */
    public function test_user_repository_interface_binding(): void
    {
        $repository = $this->app->make(UserRepositoryInterface::class);
        
        $this->assertInstanceOf(UserRepository::class, $repository);
        $this->assertInstanceOf(UserRepositoryInterface::class, $repository);
    }

    /**
     * 測試依賴注入解析
     */
    public function test_dependency_injection_resolution(): void
    {
        // 測試透過容器解析介面
        $repository1 = app(UserRepositoryInterface::class);
        $repository2 = app(UserRepositoryInterface::class);
        
        // 應該是不同的實例（非單例）
        $this->assertInstanceOf(UserRepositoryInterface::class, $repository1);
        $this->assertInstanceOf(UserRepositoryInterface::class, $repository2);
        $this->assertNotSame($repository1, $repository2);
    }
}