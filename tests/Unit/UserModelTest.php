<?php

namespace Tests\Unit;

use App\Models\User;
use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * 使用者模型單元測試
 * 
 * 測試 User 模型的計算屬性、軟刪除功能和狀態切換方法
 */
class UserModelTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Role $adminRole;
    protected Role $userRole;

    protected function setUp(): void
    {
        parent::setUp();
        
        // 建立測試角色
        $this->adminRole = Role::create([
            'name' => 'admin',
            'display_name' => '管理員',
            'description' => '系統管理員'
        ]);

        $this->userRole = Role::create([
            'name' => 'user',
            'display_name' => '一般使用者',
            'description' => '一般使用者'
        ]);

        // 建立測試使用者
        $this->user = User::factory()->create([
            'username' => 'testuser',
            'name' => '測試使用者',
            'email' => 'test@example.com',
            'avatar' => null,
            'is_active' => true
        ]);
    }

    /**
     * 測試取得主要角色屬性
     */
    public function test_get_primary_role_attribute(): void
    {
        // 測試沒有角色的情況
        $this->assertNull($this->user->primary_role);

        // 指派單一角色
        $this->user->assignRole($this->adminRole);
        $this->user->refresh();
        
        $this->assertEquals('管理員', $this->user->primary_role);

        // 指派多個角色（應該回傳按名稱排序的第一個）
        $this->user->assignRole($this->userRole);
        $this->user->refresh();
        
        // admin 在字母順序上排在 user 前面
        $this->assertEquals('管理員', $this->user->primary_role);
    }

    /**
     * 測試取得頭像 URL 屬性
     */
    public function test_get_avatar_url_attribute(): void
    {
        // 測試沒有頭像的情況（應該使用 Gravatar）
        $expectedHash = md5(strtolower(trim($this->user->email)));
        $expectedUrl = "https://www.gravatar.com/avatar/{$expectedHash}?d=identicon&s=40";
        
        $this->assertEquals($expectedUrl, $this->user->avatar_url);

        // 測試有頭像的情況
        $this->user->update(['avatar' => 'test-avatar.jpg']);
        $this->user->refresh();
        
        $expectedUrl = asset('storage/avatars/test-avatar.jpg');
        $this->assertEquals($expectedUrl, $this->user->avatar_url);
    }

    /**
     * 測試取得格式化建立時間屬性
     */
    public function test_get_formatted_created_at_attribute(): void
    {
        $expectedFormat = $this->user->created_at->format('Y-m-d H:i');
        $this->assertEquals($expectedFormat, $this->user->formatted_created_at);
    }

    /**
     * 測試檢查是否可以被刪除
     */
    public function test_can_be_deleted(): void
    {
        // 一般使用者可以被刪除
        $this->assertTrue($this->user->canBeDeleted());

        // 超級管理員不能被刪除
        $superAdminRole = Role::create([
            'name' => 'super_admin',
            'display_name' => '超級管理員',
            'description' => '超級管理員'
        ]);
        
        $this->user->assignRole($superAdminRole);
        $this->assertFalse($this->user->canBeDeleted());

        // 重置角色
        $this->user->roles()->detach();
        $this->assertTrue($this->user->canBeDeleted());

        // 當前登入使用者不能被刪除
        $currentUser = User::factory()->create([
            'username' => 'currentuser',
            'name' => '當前使用者',
            'email' => 'current@example.com'
        ]);
        
        $this->actingAs($currentUser);
        
        // 確認認證狀態
        $this->assertTrue(auth()->check());
        $this->assertEquals($currentUser->username, auth()->id());
        
        $this->assertFalse($currentUser->canBeDeleted());
        $this->assertTrue($this->user->canBeDeleted()); // 其他使用者仍可被刪除
    }

    /**
     * 測試軟刪除功能
     */
    public function test_soft_delete(): void
    {
        // 指派角色給使用者
        $this->user->assignRole($this->adminRole);
        $this->assertTrue($this->user->hasRole('admin'));

        // 執行軟刪除
        $result = $this->user->softDelete();
        
        $this->assertTrue($result);
        
        // 重新載入使用者資料
        $this->user->refresh();
        
        // 檢查使用者已被停用
        $this->assertFalse($this->user->is_active);
        
        // 檢查角色關聯已被移除
        $this->assertFalse($this->user->hasRole('admin'));
        
        // 檢查使用者已被軟刪除
        $this->assertNotNull($this->user->deleted_at);
    }

    /**
     * 測試無法軟刪除超級管理員
     */
    public function test_cannot_soft_delete_super_admin(): void
    {
        $superAdminRole = Role::create([
            'name' => 'super_admin',
            'display_name' => '超級管理員',
            'description' => '超級管理員'
        ]);
        
        $this->user->assignRole($superAdminRole);
        
        $result = $this->user->softDelete();
        
        $this->assertFalse($result);
        $this->assertTrue($this->user->is_active);
        $this->assertNull($this->user->deleted_at);
    }

    /**
     * 測試無法軟刪除當前登入使用者
     */
    public function test_cannot_soft_delete_current_user(): void
    {
        $currentUser = User::factory()->create([
            'username' => 'currentuser',
            'name' => '當前使用者',
            'email' => 'current@example.com'
        ]);
        
        $this->actingAs($currentUser);
        
        $result = $currentUser->softDelete();
        
        $this->assertFalse($result);
        $this->assertTrue($currentUser->is_active);
        $this->assertNull($currentUser->deleted_at);
    }

    /**
     * 測試恢復軟刪除的使用者
     */
    public function test_restore_user(): void
    {
        // 先軟刪除使用者
        $this->user->delete();
        $this->assertNotNull($this->user->deleted_at);

        // 恢復使用者
        $result = $this->user->restoreUser();
        
        $this->assertTrue($result);
        
        // 重新載入使用者資料
        $this->user->refresh();
        
        // 檢查使用者已被恢復
        $this->assertNull($this->user->deleted_at);
    }

    /**
     * 測試切換使用者狀態
     */
    public function test_toggle_status(): void
    {
        // 初始狀態為啟用
        $this->assertTrue($this->user->is_active);

        // 切換為停用
        $result = $this->user->toggleStatus();
        
        $this->assertTrue($result);
        $this->user->refresh();
        $this->assertFalse($this->user->is_active);

        // 再次切換為啟用
        $result = $this->user->toggleStatus();
        
        $this->assertTrue($result);
        $this->user->refresh();
        $this->assertTrue($this->user->is_active);
    }

    /**
     * 測試顯示名稱屬性
     */
    public function test_get_display_name_attribute(): void
    {
        // 有姓名時使用姓名
        $this->assertEquals('測試使用者', $this->user->display_name);

        // 姓名為空字串時使用使用者名稱
        $this->user->update(['name' => '']);
        $this->user->refresh();
        
        $this->assertEquals('testuser', $this->user->display_name);
    }

    /**
     * 測試檢查是否為超級管理員
     */
    public function test_is_super_admin(): void
    {
        // 一般使用者不是超級管理員
        $this->assertFalse($this->user->isSuperAdmin());

        // 指派超級管理員角色
        $superAdminRole = Role::create([
            'name' => 'super_admin',
            'display_name' => '超級管理員',
            'description' => '超級管理員'
        ]);
        
        $this->user->assignRole($superAdminRole);
        $this->assertTrue($this->user->isSuperAdmin());
    }

    /**
     * 測試檢查是否為管理員
     */
    public function test_is_admin(): void
    {
        // 一般使用者不是管理員
        $this->assertFalse($this->user->isAdmin());

        // 指派管理員角色
        $this->user->assignRole($this->adminRole);
        $this->assertTrue($this->user->isAdmin());

        // 移除管理員角色，指派超級管理員角色
        $this->user->removeRole($this->adminRole);
        
        $superAdminRole = Role::create([
            'name' => 'super_admin',
            'display_name' => '超級管理員',
            'description' => '超級管理員'
        ]);
        
        $this->user->assignRole($superAdminRole);
        $this->assertTrue($this->user->isAdmin()); // 超級管理員也是管理員
    }

    /**
     * 測試角色指派和移除
     */
    public function test_role_assignment_and_removal(): void
    {
        // 初始沒有角色
        $this->assertFalse($this->user->hasRole('admin'));

        // 指派角色
        $this->user->assignRole($this->adminRole);
        $this->assertTrue($this->user->hasRole('admin'));

        // 重複指派同一角色不會產生重複記錄
        $this->user->assignRole($this->adminRole);
        $this->assertEquals(1, $this->user->roles()->count());

        // 移除角色
        $this->user->removeRole($this->adminRole);
        $this->assertFalse($this->user->hasRole('admin'));
    }

    /**
     * 測試檢查是否擁有任一指定角色
     */
    public function test_has_any_role(): void
    {
        // 沒有任何角色
        $this->assertFalse($this->user->hasAnyRole(['admin', 'user']));

        // 指派其中一個角色
        $this->user->assignRole($this->adminRole);
        $this->assertTrue($this->user->hasAnyRole(['admin', 'user']));
        $this->assertTrue($this->user->hasAnyRole(['admin']));
        $this->assertFalse($this->user->hasAnyRole(['user']));

        // 指派多個角色
        $this->user->assignRole($this->userRole);
        $this->assertTrue($this->user->hasAnyRole(['admin', 'user']));
        $this->assertTrue($this->user->hasAnyRole(['user']));
    }

    /**
     * 測試使用字串指派和移除角色
     */
    public function test_role_assignment_with_string(): void
    {
        // 使用字串指派角色
        $this->user->assignRole('admin');
        $this->assertTrue($this->user->hasRole('admin'));

        // 使用字串移除角色
        $this->user->removeRole('admin');
        $this->assertFalse($this->user->hasRole('admin'));
    }

    /**
     * 測試認證相關方法
     */
    public function test_authentication_methods(): void
    {
        // 測試使用者名稱欄位
        $this->assertEquals('username', $this->user->username());
        
        // 測試認證識別名稱
        $this->assertEquals('username', $this->user->getAuthIdentifierName());
    }
}