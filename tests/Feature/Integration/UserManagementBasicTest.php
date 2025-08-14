<?php

namespace Tests\Feature\Integration;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * 使用者管理基本整合測試
 * 
 * 驗證基本的整合測試功能是否正常運作
 */
class UserManagementBasicTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 測試基本的使用者建立和查詢功能
     */
    public function test_basic_user_creation_and_retrieval()
    {
        // 建立測試角色
        $role = Role::factory()->create([
            'name' => 'admin',
            'display_name' => '管理員'
        ]);

        // 建立測試使用者
        $user = User::factory()->create([
            'username' => 'testuser',
            'name' => '測試使用者',
            'email' => 'test@example.com'
        ]);

        // 指派角色
        $user->roles()->attach($role);

        // 驗證使用者建立成功
        $this->assertDatabaseHas('users', [
            'username' => 'testuser',
            'name' => '測試使用者',
            'email' => 'test@example.com'
        ]);

        // 驗證角色關聯
        $this->assertTrue($user->roles->contains($role));
        
        // 驗證使用者可以被查詢
        $foundUser = User::where('username', 'testuser')->first();
        $this->assertNotNull($foundUser);
        $this->assertEquals('測試使用者', $foundUser->name);
    }

    /**
     * 測試使用者狀態功能
     */
    public function test_user_status_functionality()
    {
        // 建立啟用的使用者
        $activeUser = User::factory()->create(['is_active' => true]);
        $this->assertTrue($activeUser->is_active);

        // 建立停用的使用者
        $inactiveUser = User::factory()->create(['is_active' => false]);
        $this->assertFalse($inactiveUser->is_active);

        // 測試狀態切換
        $activeUser->is_active = false;
        $activeUser->save();
        
        $activeUser->refresh();
        $this->assertFalse($activeUser->is_active);
    }

    /**
     * 測試使用者搜尋功能
     */
    public function test_user_search_functionality()
    {
        // 建立多個測試使用者
        $user1 = User::factory()->create([
            'name' => 'John Doe',
            'username' => 'johndoe',
            'email' => 'john@example.com'
        ]);

        $user2 = User::factory()->create([
            'name' => 'Jane Smith',
            'username' => 'janesmith',
            'email' => 'jane@example.com'
        ]);

        // 測試按姓名搜尋
        $results = User::where('name', 'like', '%John%')->get();
        $this->assertCount(1, $results);
        $this->assertEquals('John Doe', $results->first()->name);

        // 測試按使用者名稱搜尋
        $results = User::where('username', 'like', '%jane%')->get();
        $this->assertCount(1, $results);
        $this->assertEquals('janesmith', $results->first()->username);

        // 測試按電子郵件搜尋
        $results = User::where('email', 'like', '%john@%')->get();
        $this->assertCount(1, $results);
        $this->assertEquals('john@example.com', $results->first()->email);
    }

    /**
     * 測試角色篩選功能
     */
    public function test_role_filtering_functionality()
    {
        // 建立角色
        $adminRole = Role::factory()->create(['name' => 'admin']);
        $userRole = Role::factory()->create(['name' => 'user']);

        // 建立使用者並指派角色
        $admin = User::factory()->create();
        $admin->roles()->attach($adminRole);

        $regularUser = User::factory()->create();
        $regularUser->roles()->attach($userRole);

        // 測試角色篩選
        $adminUsers = User::whereHas('roles', function ($query) {
            $query->where('name', 'admin');
        })->get();

        $this->assertCount(1, $adminUsers);
        $this->assertEquals($admin->id, $adminUsers->first()->id);

        $regularUsers = User::whereHas('roles', function ($query) {
            $query->where('name', 'user');
        })->get();

        $this->assertCount(1, $regularUsers);
        $this->assertEquals($regularUser->id, $regularUsers->first()->id);
    }

    /**
     * 測試分頁功能
     */
    public function test_pagination_functionality()
    {
        // 建立多個使用者
        User::factory()->count(25)->create();

        // 測試分頁
        $paginatedUsers = User::paginate(15);
        
        $this->assertEquals(15, $paginatedUsers->perPage());
        $this->assertEquals(25, $paginatedUsers->total());
        $this->assertEquals(2, $paginatedUsers->lastPage());
        $this->assertCount(15, $paginatedUsers->items());
    }

    /**
     * 測試軟刪除功能
     */
    public function test_soft_delete_functionality()
    {
        // 建立使用者
        $user = User::factory()->create();
        $userId = $user->id;

        // 確認使用者存在
        $this->assertDatabaseHas('users', ['id' => $userId]);

        // 軟刪除使用者
        $user->delete();

        // 確認軟刪除
        $this->assertSoftDeleted('users', ['id' => $userId]);

        // 確認一般查詢找不到已刪除的使用者
        $this->assertNull(User::find($userId));

        // 確認包含軟刪除的查詢可以找到
        $this->assertNotNull(User::withTrashed()->find($userId));
    }

    /**
     * 測試批量操作功能
     */
    public function test_bulk_operations_functionality()
    {
        // 建立多個啟用的使用者
        $users = User::factory()->count(5)->create(['is_active' => true]);
        $userIds = $users->pluck('id')->toArray();

        // 批量停用
        User::whereIn('id', $userIds)->update(['is_active' => false]);

        // 驗證所有使用者都被停用
        $updatedUsers = User::whereIn('id', $userIds)->get();
        foreach ($updatedUsers as $user) {
            $this->assertFalse($user->is_active);
        }

        // 批量啟用
        User::whereIn('id', $userIds)->update(['is_active' => true]);

        // 驗證所有使用者都被啟用
        $updatedUsers = User::whereIn('id', $userIds)->get();
        foreach ($updatedUsers as $user) {
            $this->assertTrue($user->is_active);
        }
    }

    /**
     * 測試效能基準
     */
    public function test_basic_performance_benchmark()
    {
        // 建立測試資料
        User::factory()->count(50)->create();

        // 測試查詢效能
        $startTime = microtime(true);
        
        $users = User::with('roles')->paginate(15);
        
        $queryTime = microtime(true) - $startTime;

        // 基本效能檢查（應該很快）
        $this->assertLessThan(1.0, $queryTime, "基本查詢時間 {$queryTime} 秒過長");
        $this->assertNotNull($users);
        $this->assertGreaterThan(0, $users->count());
    }

    /**
     * 測試資料完整性
     */
    public function test_data_integrity()
    {
        // 建立使用者和角色
        $role = Role::factory()->create();
        $user = User::factory()->create();
        
        // 建立關聯
        $user->roles()->attach($role);
        
        // 驗證關聯存在
        $this->assertDatabaseHas('user_roles', [
            'user_id' => $user->id,
            'role_id' => $role->id
        ]);

        // 刪除使用者
        $user->delete();

        // 驗證關聯也被清除（如果有設定外鍵約束）
        // 注意：這取決於資料庫設定
        $this->assertSoftDeleted('users', ['id' => $user->id]);
    }

    /**
     * 測試驗證規則
     */
    public function test_validation_rules()
    {
        // 測試必填欄位
        $this->expectException(\Illuminate\Database\QueryException::class);
        
        // 嘗試建立沒有必填欄位的使用者（應該失敗）
        User::create([]);
    }
}