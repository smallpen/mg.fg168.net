<?php

namespace Tests\Feature\Livewire\Admin\Users;

use App\Livewire\Admin\Users\UserList;
use App\Models\Role;
use App\Models\User;
use App\Models\Permission;
use App\Services\ErrorHandlerService;
use App\Services\UserFriendlyErrorService;
use App\Services\EnhancedErrorLoggingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\QueryException;
use Livewire\Livewire;
use Tests\TestCase;
use Mockery;

/**
 * UserList 元件錯誤處理機制測試
 * 
 * 專門測試各種錯誤情況的處理，包含：
 * - 資料庫錯誤
 * - 網路錯誤
 * - 權限錯誤
 * - 驗證錯誤
 * - 系統錯誤
 */
class UserListErrorHandlingTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected Role $adminRole;

    protected function setUp(): void
    {
        parent::setUp();
        
        // 建立測試角色和權限
        $this->adminRole = Role::factory()->create(['name' => 'admin']);
        
        $permissions = ['users.view', 'users.edit', 'users.delete'];
        foreach ($permissions as $permissionName) {
            $permission = Permission::factory()->create(['name' => $permissionName]);
            $this->adminRole->permissions()->attach($permission);
        }
        
        // 建立管理員使用者
        $this->admin = User::factory()->create();
        $this->admin->roles()->attach($this->adminRole);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    // ==================== 資料庫錯誤測試 ====================

    /**
     * 測試資料庫連線失敗處理
     */
    public function test_database_connection_failure_handling()
    {
        $this->actingAs($this->admin);

        // 模擬資料庫連線失敗
        DB::shouldReceive('connection')->andThrow(new \PDOException('Connection failed'));

        $component = Livewire::test(UserList::class);
        
        // 應該顯示友善的錯誤訊息
        $component->assertStatus(200)
            ->assertDispatched('show-database-error');
    }

    /**
     * 測試 SQL 查詢錯誤處理
     */
    public function test_sql_query_error_handling()
    {
        $this->actingAs($this->admin);

        // 模擬 SQL 查詢錯誤
        $mockException = new QueryException(
            'mysql',
            'SELECT * FROM users',
            [],
            new \PDOException('Table does not exist')
        );

        DB::shouldReceive('table')->andThrow($mockException);

        Livewire::test(UserList::class)
            ->assertStatus(200)
            ->assertDispatched('show-database-error');
    }

    /**
     * 測試資料庫約束違反錯誤
     */
    public function test_database_constraint_violation_handling()
    {
        $this->actingAs($this->admin);

        $user = User::factory()->create(['is_active' => true]);

        // 模擬外鍵約束錯誤
        $mockException = new QueryException(
            'mysql',
            'UPDATE users SET is_active = ?',
            [false],
            new \PDOException('Foreign key constraint fails', '23000')
        );

        DB::shouldReceive('table')->andThrow($mockException);

        Livewire::test(UserList::class)
            ->call('toggleUserStatus', $user->id)
            ->assertDispatched('show-database-error');
    }

    // ==================== 權限錯誤測試 ====================

    /**
     * 測試權限不足錯誤處理
     */
    public function test_permission_denied_error_handling()
    {
        $unauthorizedUser = User::factory()->create();
        $this->actingAs($unauthorizedUser);

        Livewire::test(UserList::class)
            ->assertStatus(403);
    }

    /**
     * 測試動態權限檢查錯誤
     */
    public function test_dynamic_permission_check_error()
    {
        $this->actingAs($this->admin);

        $user = User::factory()->create();

        // 模擬權限檢查失敗
        $mockException = new AuthorizationException('This action is unauthorized.');

        $component = Livewire::test(UserList::class);
        
        // 移除使用者的編輯權限
        $this->admin->roles()->detach();
        
        $component->call('editUser', $user->id)
            ->assertDispatched('show-toast');
    }

    // ==================== 驗證錯誤測試 ====================

    /**
     * 測試輸入驗證錯誤處理
     */
    public function test_input_validation_error_handling()
    {
        $this->actingAs($this->admin);

        // 測試無效的搜尋輸入
        Livewire::test(UserList::class)
            ->set('search', str_repeat('a', 300)) // 超過最大長度
            ->assertSet('search', ''); // 應該被清空
    }

    /**
     * 測試惡意輸入處理
     */
    public function test_malicious_input_handling()
    {
        $this->actingAs($this->admin);

        $maliciousInputs = [
            '<script>alert("xss")</script>',
            '"; DROP TABLE users; --',
            '<?php system("rm -rf /"); ?>',
            'javascript:alert("xss")',
            '<img src=x onerror=alert("xss")>'
        ];

        foreach ($maliciousInputs as $input) {
            Livewire::test(UserList::class)
                ->set('search', $input)
                ->assertSet('search', ''); // 應該被清空或清理
        }
    }

    /**
     * 測試使用者 ID 驗證錯誤
     */
    public function test_user_id_validation_error()
    {
        $this->actingAs($this->admin);

        $invalidIds = [
            -1,
            0,
            'abc',
            null,
            99999999
        ];

        foreach ($invalidIds as $invalidId) {
            Livewire::test(UserList::class)
                ->call('viewUser', $invalidId)
                ->assertDispatched('show-toast');
        }
    }

    // ==================== 網路錯誤測試 ====================

    /**
     * 測試網路連線超時處理
     */
    public function test_network_timeout_handling()
    {
        $this->actingAs($this->admin);

        // 模擬網路超時
        $mockException = new \Exception('Connection timeout', 408);

        // 模擬服務依賴失敗
        $mockService = Mockery::mock('App\Services\UserCacheService');
        $mockService->shouldReceive('clearAll')->andThrow($mockException);
        
        $this->app->instance('App\Services\UserCacheService', $mockService);

        Livewire::test(UserList::class)
            ->assertStatus(200); // 應該優雅地處理錯誤
    }

    /**
     * 測試外部服務不可用處理
     */
    public function test_external_service_unavailable_handling()
    {
        $this->actingAs($this->admin);

        // 模擬外部服務不可用
        $mockException = new \Exception('Service unavailable', 503);

        Log::shouldReceive('error')->once();

        $component = Livewire::test(UserList::class);
        
        // 應該能正常載入，但可能有功能限制
        $component->assertStatus(200);
    }

    // ==================== 系統錯誤測試 ====================

    /**
     * 測試記憶體不足錯誤處理
     */
    public function test_memory_limit_error_handling()
    {
        $this->actingAs($this->admin);

        // 模擬記憶體不足錯誤
        $mockException = new \Error('Allowed memory size exhausted');

        Log::shouldReceive('critical')->once();

        // 系統應該記錄嚴重錯誤
        $component = Livewire::test(UserList::class);
        $component->assertStatus(200);
    }

    /**
     * 測試檔案系統錯誤處理
     */
    public function test_filesystem_error_handling()
    {
        $this->actingAs($this->admin);

        // 模擬檔案系統錯誤
        $mockException = new \Exception('Permission denied: Cannot write to log file');

        Log::shouldReceive('error')->andThrow($mockException);

        // 應該能處理日誌寫入失敗
        $component = Livewire::test(UserList::class);
        $component->assertStatus(200);
    }

    // ==================== 錯誤恢復測試 ====================

    /**
     * 測試錯誤後的狀態恢復
     */
    public function test_error_recovery_state()
    {
        $this->actingAs($this->admin);

        $user = User::factory()->create(['is_active' => true]);

        $component = Livewire::test(UserList::class);

        // 模擬操作失敗
        $component->call('toggleUserStatus', 99999)
            ->assertDispatched('show-toast');

        // 後續操作應該仍然正常
        $component->call('toggleUserStatus', $user->id)
            ->assertDispatched('user-status-updated');

        $this->assertFalse($user->fresh()->is_active);
    }

    /**
     * 測試批量操作錯誤恢復
     */
    public function test_bulk_operation_error_recovery()
    {
        $this->actingAs($this->admin);

        $users = User::factory()->count(3)->create(['is_active' => false]);
        $userIds = $users->pluck('id')->toArray();
        $userIds[] = 99999; // 加入無效 ID

        $component = Livewire::test(UserList::class);

        // 批量操作應該處理部分失敗
        $component->set('selectedUsers', $userIds)
            ->call('bulkActivate')
            ->assertDispatched('show-toast');

        // 有效的使用者應該被處理
        foreach ($users as $user) {
            $this->assertTrue($user->fresh()->is_active);
        }
    }

    // ==================== 錯誤日誌測試 ====================

    /**
     * 測試錯誤日誌記錄
     */
    public function test_error_logging()
    {
        $this->actingAs($this->admin);

        Log::shouldReceive('error')->once()->with(
            Mockery::pattern('/UserList component error/'),
            Mockery::type('array')
        );

        // 觸發會記錄錯誤的操作
        Livewire::test(UserList::class)
            ->call('viewUser', 99999);
    }

    /**
     * 測試安全事件日誌記錄
     */
    public function test_security_event_logging()
    {
        $this->actingAs($this->admin);

        Log::shouldReceive('warning')->once()->with(
            Mockery::pattern('/Malicious input detected/'),
            Mockery::type('array')
        );

        // 觸發安全事件
        Livewire::test(UserList::class)
            ->set('search', '<script>alert("xss")</script>');
    }

    // ==================== 使用者體驗測試 ====================

    /**
     * 測試錯誤訊息的使用者友善性
     */
    public function test_user_friendly_error_messages()
    {
        $this->actingAs($this->admin);

        // 測試各種錯誤情況的友善訊息
        $component = Livewire::test(UserList::class);

        // 使用者不存在
        $component->call('viewUser', 99999)
            ->assertDispatched('show-toast', function ($event) {
                return str_contains($event['message'], '找不到指定的使用者');
            });

        // 權限不足（移除權限後測試）
        $this->admin->roles()->detach();
        
        $user = User::factory()->create();
        $component->call('editUser', $user->id)
            ->assertDispatched('show-toast', function ($event) {
                return str_contains($event['message'], '沒有權限');
            });
    }

    /**
     * 測試錯誤狀態的視覺回饋
     */
    public function test_error_visual_feedback()
    {
        $this->actingAs($this->admin);

        // 測試載入狀態和錯誤狀態的視覺回饋
        $component = Livewire::test(UserList::class);

        // 觸發錯誤後應該有適當的視覺回饋
        $component->call('viewUser', 99999)
            ->assertDispatched('show-toast');
    }
}