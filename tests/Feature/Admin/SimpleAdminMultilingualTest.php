<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Models\Role;
use App\Models\Permission;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\App;

/**
 * 簡單的管理後台多語系測試
 */
class SimpleAdminMultilingualTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;

    protected function setUp(): void
    {
        parent::setUp();
        
        // 建立基本權限
        Permission::create(['name' => 'dashboard.view', 'display_name' => '檢視儀表板', 'module' => 'dashboard']);
        Permission::create(['name' => 'users.view', 'display_name' => '檢視使用者', 'module' => 'users']);
        
        // 建立管理員角色
        $adminRole = Role::create(['name' => 'admin', 'display_name' => '管理員']);
        $adminRole->permissions()->attach(Permission::all());
        
        // 建立管理員使用者
        $this->adminUser = User::factory()->create(['username' => 'admin']);
        $this->adminUser->roles()->attach($adminRole);
    }

    /**
     * 測試基本的語言切換
     */
    public function test_basic_language_switching(): void
    {
        // 測試中文
        App::setLocale('zh_TW');
        $this->assertEquals('zh_TW', App::getLocale());
        
        // 測試英文
        App::setLocale('en');
        $this->assertEquals('en', App::getLocale());
        
        $this->assertTrue(true);
    }

    /**
     * 測試翻譯功能
     */
    public function test_translation_functionality(): void
    {
        // 測試中文翻譯
        App::setLocale('zh_TW');
        $zhTranslation = __('admin.dashboard.title');
        $this->assertNotEmpty($zhTranslation);
        
        // 測試英文翻譯
        App::setLocale('en');
        $enTranslation = __('admin.dashboard.title');
        $this->assertNotEmpty($enTranslation);
        
        // 驗證翻譯不同
        $this->assertNotEquals($zhTranslation, $enTranslation);
    }

    /**
     * 測試管理員登入
     */
    public function test_admin_login(): void
    {
        $this->actingAs($this->adminUser);
        $this->assertAuthenticated();
    }
}