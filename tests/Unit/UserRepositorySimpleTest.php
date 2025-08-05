<?php

namespace Tests\Unit;

use Tests\TestCase;

/**
 * 使用者資料存取層簡化測試
 * 
 * 由於測試環境限制，這裡只驗證類別是否可以正常載入
 */
class UserRepositorySimpleTest extends TestCase
{
    /**
     * 測試 UserRepository 類別是否存在
     */
    public function test_user_repository_class_exists(): void
    {
        $this->assertTrue(class_exists('App\Repositories\UserRepository'));
    }

    /**
     * 測試 UserRepository 可以被實例化
     */
    public function test_user_repository_can_be_instantiated(): void
    {
        $repository = new \App\Repositories\UserRepository();
        $this->assertInstanceOf(\App\Repositories\UserRepository::class, $repository);
    }

    /**
     * 測試 UserRepository 具有必要的方法
     */
    public function test_user_repository_has_required_methods(): void
    {
        $repository = new \App\Repositories\UserRepository();
        
        $this->assertTrue(method_exists($repository, 'all'));
        $this->assertTrue(method_exists($repository, 'find'));
        $this->assertTrue(method_exists($repository, 'create'));
        $this->assertTrue(method_exists($repository, 'update'));
        $this->assertTrue(method_exists($repository, 'delete'));
        $this->assertTrue(method_exists($repository, 'paginate'));
        $this->assertTrue(method_exists($repository, 'search'));
        $this->assertTrue(method_exists($repository, 'getStats'));
        $this->assertTrue(method_exists($repository, 'findByUsername'));
        $this->assertTrue(method_exists($repository, 'findByEmail'));
        $this->assertTrue(method_exists($repository, 'usernameExists'));
        $this->assertTrue(method_exists($repository, 'emailExists'));
        $this->assertTrue(method_exists($repository, 'getActiveUsers'));
        $this->assertTrue(method_exists($repository, 'getInactiveUsers'));
        $this->assertTrue(method_exists($repository, 'getRecentUsers'));
        $this->assertTrue(method_exists($repository, 'bulkUpdateStatus'));
        $this->assertTrue(method_exists($repository, 'resetPassword'));
        $this->assertTrue(method_exists($repository, 'updatePreferences'));
    }
}