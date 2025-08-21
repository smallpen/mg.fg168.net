<?php

namespace Tests\Integration\RoleManagement;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\RoleManagementTestHelpers;

/**
 * 角色管理整合測試基礎類別
 */
abstract class RoleManagementTestCase extends TestCase
{
    use RefreshDatabase, RoleManagementTestHelpers;

    /**
     * 測試標籤
     */
    protected array $groups = ['role-management', 'integration'];

    /**
     * 設定測試環境
     */
    protected function setUp(): void
    {
        parent::setUp();
        
        // 設定測試環境配置
        config([
            'cache.default' => 'array',
            'queue.default' => 'sync',
            'mail.default' => 'array',
        ]);
        
        // 清除快取
        \Cache::flush();
        
        // 設定測試資料庫
        $this->artisan('migrate:fresh');
        
        // 執行基礎資料填充
        $this->seedBasicData();
    }

    /**
     * 清理測試環境
     */
    protected function tearDown(): void
    {
        // 清理快取
        \Cache::flush();
        
        // 清理測試資料
        $this->cleanupTestData();
        
        parent::tearDown();
    }

    /**
     * 填充基礎測試資料
     */
    protected function seedBasicData(): void
    {
        // 建立基本權限模組
        $modules = ['users', 'roles', 'permissions', 'system'];
        $actions = ['view', 'create', 'edit', 'delete'];
        
        foreach ($modules as $module) {
            foreach ($actions as $action) {
                \App\Models\Permission::factory()->create([
                    'name' => "{$module}.{$action}",
                    'display_name' => ucfirst($action) . ' ' . ucfirst($module),
                    'module' => $module,
                    'description' => "Permission to {$action} {$module}"
                ]);
            }
        }
    }

    /**
     * 斷言測試群組
     */
    protected function assertTestGroup(string $expectedGroup): void
    {
        $this->assertContains($expectedGroup, $this->groups,
            "測試應該屬於群組: {$expectedGroup}");
    }

    /**
     * 跳過效能測試（在 CI 環境中）
     */
    protected function skipPerformanceTestsInCI(): void
    {
        if (env('CI', false)) {
            $this->markTestSkipped('效能測試在 CI 環境中跳過');
        }
    }

    /**
     * 跳過瀏覽器測試（如果沒有安裝 Chrome）
     */
    protected function skipBrowserTestsIfNoChromeDriver(): void
    {
        if (!file_exists(base_path('vendor/laravel/dusk/bin/chromedriver-linux'))) {
            $this->markTestSkipped('瀏覽器測試需要 Chrome Driver');
        }
    }

    /**
     * 模擬慢速網路環境
     */
    protected function simulateSlowNetwork(): void
    {
        // 在測試中添加延遲來模擬慢速網路
        usleep(100000); // 100ms 延遲
    }

    /**
     * 模擬高負載環境
     */
    protected function simulateHighLoad(): void
    {
        // 建立大量測試資料來模擬高負載
        $this->createLargeDataSet();
    }

    /**
     * 驗證 Livewire 元件回應
     */
    protected function assertLivewireResponse($component, array $expectedData = []): void
    {
        $component->assertStatus(200);
        
        foreach ($expectedData as $key => $value) {
            $component->assertSet($key, $value);
        }
    }

    /**
     * 驗證 JSON API 回應結構
     */
    protected function assertJsonApiStructure($response, array $structure = []): void
    {
        $defaultStructure = [
            'data',
            'meta' => [
                'current_page',
                'total',
                'per_page'
            ]
        ];
        
        $structure = array_merge($defaultStructure, $structure);
        $response->assertJsonStructure($structure);
    }

    /**
     * 建立測試用的 HTTP 標頭
     */
    protected function getTestHeaders(): array
    {
        return [
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
            'X-Requested-With' => 'XMLHttpRequest',
        ];
    }

    /**
     * 驗證審計日誌記錄
     */
    protected function assertAuditLogExists(string $action, array $data = []): void
    {
        $this->assertDatabaseHas('audit_logs', array_merge([
            'action' => $action,
            'user_id' => auth()->id(),
        ], $data));
    }

    /**
     * 驗證快取鍵存在
     */
    protected function assertCacheKeyExists(string $key): void
    {
        $this->assertTrue(\Cache::has($key), "快取鍵 '{$key}' 不存在");
    }

    /**
     * 驗證快取鍵不存在
     */
    protected function assertCacheKeyNotExists(string $key): void
    {
        $this->assertFalse(\Cache::has($key), "快取鍵 '{$key}' 不應該存在");
    }

    /**
     * 清除特定快取
     */
    protected function clearRoleCache(int $roleId = null): void
    {
        if ($roleId) {
            \Cache::forget("role_permissions_{$roleId}");
            \Cache::forget("role_all_permissions_{$roleId}");
            \Cache::forget("role_hierarchy_{$roleId}");
        } else {
            \Cache::flush();
        }
    }

    /**
     * 驗證事件已觸發
     */
    protected function assertEventDispatched(string $eventClass, array $data = []): void
    {
        \Event::assertDispatched($eventClass, function ($event) use ($data) {
            foreach ($data as $key => $value) {
                if (!isset($event->$key) || $event->$key !== $value) {
                    return false;
                }
            }
            return true;
        });
    }

    /**
     * 模擬資料庫錯誤
     */
    protected function simulateDatabaseError(): void
    {
        // 暫時關閉資料庫連線來模擬錯誤
        \DB::disconnect();
    }

    /**
     * 恢復資料庫連線
     */
    protected function restoreDatabaseConnection(): void
    {
        \DB::reconnect();
    }

    /**
     * 驗證 SQL 查詢數量
     */
    protected function assertQueryCount(int $expectedCount, callable $callback): void
    {
        $queryCount = 0;
        
        \DB::listen(function ($query) use (&$queryCount) {
            $queryCount++;
        });
        
        $callback();
        
        $this->assertEquals($expectedCount, $queryCount,
            "預期執行 {$expectedCount} 個查詢，實際執行了 {$queryCount} 個");
    }

    /**
     * 建立測試用的表單資料
     */
    protected function getValidRoleFormData(): array
    {
        return [
            'name' => 'test_role_' . uniqid(),
            'display_name' => '測試角色 ' . uniqid(),
            'description' => '這是一個測試角色的描述',
            'is_active' => true,
        ];
    }

    /**
     * 建立無效的表單資料
     */
    protected function getInvalidRoleFormData(): array
    {
        return [
            'name' => '', // 必填欄位為空
            'display_name' => str_repeat('a', 256), // 超過最大長度
            'description' => str_repeat('b', 1000), // 超過最大長度
        ];
    }

    /**
     * 驗證表單驗證錯誤
     */
    protected function assertValidationErrors($response, array $expectedErrors): void
    {
        $response->assertStatus(422);
        
        foreach ($expectedErrors as $field) {
            $response->assertJsonValidationErrors($field);
        }
    }
}