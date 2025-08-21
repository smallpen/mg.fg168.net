<?php

namespace Tests\Unit;

use App\Repositories\Contracts\UserRepositoryInterface;
use App\Repositories\Contracts\RoleRepositoryInterface;
use App\Repositories\Contracts\PermissionRepositoryInterface;
use App\Repositories\UserRepository;
use App\Repositories\RoleRepository;
use App\Repositories\PermissionRepository;
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

    /**
     * 測試角色資料存取層介面綁定
     */
    public function test_role_repository_interface_binding(): void
    {
        $repository = $this->app->make(RoleRepositoryInterface::class);
        
        $this->assertInstanceOf(RoleRepository::class, $repository);
        $this->assertInstanceOf(RoleRepositoryInterface::class, $repository);
    }

    /**
     * 測試權限資料存取層介面綁定
     */
    public function test_permission_repository_interface_binding(): void
    {
        $repository = $this->app->make(PermissionRepositoryInterface::class);
        
        $this->assertInstanceOf(PermissionRepository::class, $repository);
        $this->assertInstanceOf(PermissionRepositoryInterface::class, $repository);
    }

    /**
     * 測試所有資料存取層介面綁定
     */
    public function test_all_repository_interfaces_binding(): void
    {
        // 測試使用者資料存取層
        $userRepo = $this->app->make(UserRepositoryInterface::class);
        $this->assertInstanceOf(UserRepository::class, $userRepo);

        // 測試角色資料存取層
        $roleRepo = $this->app->make(RoleRepositoryInterface::class);
        $this->assertInstanceOf(RoleRepository::class, $roleRepo);

        // 測試權限資料存取層
        $permissionRepo = $this->app->make(PermissionRepositoryInterface::class);
        $this->assertInstanceOf(PermissionRepository::class, $permissionRepo);
    }
}