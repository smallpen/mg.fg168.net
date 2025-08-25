<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Models\Role;
use App\Models\Permission;
use Tests\MultilingualTestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Session;

/**
 * 管理後台頁面多語系測試
 * 
 * 測試管理後台各個頁面的多語系功能，包括：
 * - 儀表板頁面的語言切換功能
 * - 使用者管理頁面的翻譯完整性
 * - 角色管理頁面的多語系功能
 * - 權限管理頁面的語言顯示
 */
class AdminPagesMultilingualTest extends MultilingualTestCase
{
    use RefreshDatabase;

    /**
     * 測試用的管理員使用者
     */
    protected User $adminUser;

    /**
     * 測試用的角色
     */
    protected Role $adminRole;

    /**
     * 設定測試環境
     */
    protected function setUp(): void
    {
        parent::setUp();
        
        // 建立測試角色和權限
        $this->setupTestRolesAndPermissions();
        
        // 建立管理員使用者
        $this->adminUser = User::factory()->create([
            'username' => 'admin',
            'name' => 'Test Admin',
            'email' => 'admin@example.com',
            'is_active' => true,
        ]);
        
        // 指派管理員角色
        $this->adminUser->roles()->attach($this->adminRole);
    }

    /**
     * 建立測試用的角色和權限
     */
    protected function setupTestRolesAndPermissions(): void
    {
        // 建立權限
        $permissions = [
            'dashboard.view' => '檢視儀表板',
            'users.view' => '檢視使用者',
            'users.create' => '建立使用者',
            'users.edit' => '編輯使用者',
            'users.delete' => '刪除使用者',
            'roles.view' => '檢視角色',
            'roles.create' => '建立角色',
            'roles.edit' => '編輯角色',
            'roles.delete' => '刪除角色',
            'permissions.view' => '檢視權限',
            'permissions.create' => '建立權限',
            'permissions.edit' => '編輯權限',
            'permissions.delete' => '刪除權限',
        ];

        foreach ($permissions as $name => $displayName) {
            Permission::create([
                'name' => $name,
                'display_name' => $displayName,
                'module' => explode('.', $name)[0],
            ]);
        }

        // 建立管理員角色
        $this->adminRole = Role::create([
            'name' => 'admin',
            'display_name' => '管理員',
            'description' => '系統管理員角色',
        ]);

        // 指派所有權限給管理員角色
        $this->adminRole->permissions()->attach(Permission::all());
    }

    /**
     * 測試儀表板頁面的語言切換功能
     */
    public function test_dashboard_language_switching(): void
    {
        $this->actingAs($this->adminUser);

        // 測試中文儀表板
        $this->setTestLocale('zh_TW');
        $response = $this->get('/admin/dashboard');
        
        $response->assertStatus(200);
        $response->assertSee('儀表板');
        $response->assertSee('總使用者數');
        $response->assertSee('總角色數');
        $response->assertSee('總權限數');
        $response->assertSee('最近活動');
        $response->assertSee('快速操作');

        // 測試英文儀表板
        $this->setTestLocale('en');
        $response = $this->get('/admin/dashboard');
        
        $response->assertStatus(200);
        $response->assertSee('Dashboard');
        $response->assertSee('Total Users');
        $response->assertSee('Total Roles');
        $response->assertSee('Total Permissions');
        $response->assertSee('Recent Activity');
        $response->assertSee('Quick Actions');

        // 驗證語言切換後頁面內容確實不同
        $this->assertTranslationDiffersAcrossLocales('admin.dashboard.title');
        $this->assertTranslationDiffersAcrossLocales('admin.dashboard.stats.total_users');
    }

    /**
     * 測試儀表板統計資訊的多語系顯示
     */
    public function test_dashboard_statistics_multilingual_display(): void
    {
        $this->actingAs($this->adminUser);

        // 建立一些測試資料
        User::factory()->count(5)->create();
        Role::factory()->count(3)->create();

        $this->runInAllLocales(function () {
            $response = $this->get('/admin/dashboard');
            $response->assertStatus(200);

            // 驗證統計資訊翻譯
            $currentLocale = $this->getCurrentLocale();
            
            if ($currentLocale === 'zh_TW') {
                $response->assertSee('總使用者數');
                $response->assertSee('總角色數');
                $response->assertSee('總權限數');
            } else {
                $response->assertSee('Total Users');
                $response->assertSee('Total Roles');
                $response->assertSee('Total Permissions');
            }
        });
    }

    /**
     * 測試使用者管理頁面的翻譯完整性
     */
    public function test_user_management_page_translation_completeness(): void
    {
        $this->actingAs($this->adminUser);

        // 建立一些測試使用者
        $testUsers = User::factory()->count(3)->create();

        $this->runInAllLocales(function () use ($testUsers) {
            $response = $this->get('/admin/users');
            $response->assertStatus(200);

            $currentLocale = $this->getCurrentLocale();
            
            if ($currentLocale === 'zh_TW') {
                // 驗證中文翻譯
                $response->assertSee('使用者管理');
                $response->assertSee('使用者名稱');
                $response->assertSee('姓名');
                $response->assertSee('電子郵件');
                $response->assertSee('角色');
                $response->assertSee('狀態');
                $response->assertSee('操作');
                $response->assertSee('建立使用者');
                $response->assertSee('搜尋使用者');
                $response->assertSee('篩選');
            } else {
                // 驗證英文翻譯
                $response->assertSee('User Management');
                $response->assertSee('Username');
                $response->assertSee('Name');
                $response->assertSee('Email');
                $response->assertSee('Roles');
                $response->assertSee('Status');
                $response->assertSee('Actions');
                $response->assertSee('Create User');
                $response->assertSee('Search Users');
                $response->assertSee('Filter');
            }

            // 驗證使用者資料顯示
            foreach ($testUsers as $user) {
                $response->assertSee($user->username);
                $response->assertSee($user->name);
            }
        });
    }

    /**
     * 測試使用者建立頁面的多語系功能
     */
    public function test_user_create_page_multilingual_functionality(): void
    {
        $this->actingAs($this->adminUser);

        $this->runInAllLocales(function () {
            $response = $this->get('/admin/users/create');
            $response->assertStatus(200);

            $currentLocale = $this->getCurrentLocale();
            
            if ($currentLocale === 'zh_TW') {
                $response->assertSee('建立使用者');
                $response->assertSee('基本資訊');
                $response->assertSee('使用者名稱');
                $response->assertSee('姓名');
                $response->assertSee('電子郵件');
                $response->assertSee('密碼');
                $response->assertSee('確認密碼');
                $response->assertSee('角色分配');
                $response->assertSee('儲存');
                $response->assertSee('取消');
            } else {
                $response->assertSee('Create User');
                $response->assertSee('Username');
                $response->assertSee('Name');
                $response->assertSee('Email');
                $response->assertSee('Password');
                $response->assertSee('Save');
                $response->assertSee('Cancel');
            }
        });
    }

    /**
     * 測試使用者編輯頁面的多語系功能
     */
    public function test_user_edit_page_multilingual_functionality(): void
    {
        $this->actingAs($this->adminUser);
        
        $testUser = User::factory()->create([
            'username' => 'testuser',
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        $this->runInAllLocales(function () use ($testUser) {
            $response = $this->get("/admin/users/{$testUser->id}/edit");
            $response->assertStatus(200);

            $currentLocale = $this->getCurrentLocale();
            
            if ($currentLocale === 'zh_TW') {
                $response->assertSee('編輯使用者');
                $response->assertSee('基本資訊');
                $response->assertSee('密碼設定');
                $response->assertSee('留空則不修改密碼');
                $response->assertSee('角色分配');
                $response->assertSee('更新');
            } else {
                $response->assertSee('Edit User');
                $response->assertSee('Update');
            }

            // 驗證使用者資料顯示
            $response->assertSee($testUser->username);
            $response->assertSee($testUser->name);
            $response->assertSee($testUser->email);
        });
    }

    /**
     * 測試角色管理頁面的多語系功能
     */
    public function test_role_management_page_multilingual_functionality(): void
    {
        $this->actingAs($this->adminUser);

        // 建立一些測試角色
        $testRoles = Role::factory()->count(3)->create();

        $this->runInAllLocales(function () use ($testRoles) {
            $response = $this->get('/admin/roles');
            $response->assertStatus(200);

            $currentLocale = $this->getCurrentLocale();
            
            if ($currentLocale === 'zh_TW') {
                $response->assertSee('角色管理');
                $response->assertSee('角色名稱');
                $response->assertSee('顯示名稱');
                $response->assertSee('描述');
                $response->assertSee('權限數');
                $response->assertSee('使用者數');
                $response->assertSee('建立角色');
                $response->assertSee('搜尋角色');
                $response->assertSee('操作');
            } else {
                $response->assertSee('Role Management');
                $response->assertSee('Role Name');
                $response->assertSee('Display Name');
                $response->assertSee('Description');
                $response->assertSee('Permissions Count');
                $response->assertSee('Users Count');
                $response->assertSee('Create Role');
                $response->assertSee('Search Roles');
                $response->assertSee('Actions');
            }

            // 驗證角色資料顯示
            foreach ($testRoles as $role) {
                $response->assertSee($role->name);
                $response->assertSee($role->display_name);
            }
        });
    }

    /**
     * 測試角色建立頁面的多語系功能
     */
    public function test_role_create_page_multilingual_functionality(): void
    {
        $this->actingAs($this->adminUser);

        $this->runInAllLocales(function () {
            $response = $this->get('/admin/roles/create');
            $response->assertStatus(200);

            $currentLocale = $this->getCurrentLocale();
            
            if ($currentLocale === 'zh_TW') {
                $response->assertSee('建立角色');
                $response->assertSee('基本資訊');
                $response->assertSee('角色名稱');
                $response->assertSee('顯示名稱');
                $response->assertSee('描述');
                $response->assertSee('權限設定');
                $response->assertSee('儲存');
                $response->assertSee('取消');
            } else {
                $response->assertSee('Create Role');
                $response->assertSee('Role Name');
                $response->assertSee('Display Name');
                $response->assertSee('Description');
                $response->assertSee('Save');
                $response->assertSee('Cancel');
            }
        });
    }

    /**
     * 測試權限管理頁面的語言顯示
     */
    public function test_permission_management_page_language_display(): void
    {
        $this->actingAs($this->adminUser);

        $this->runInAllLocales(function () {
            $response = $this->get('/admin/permissions');
            $response->assertStatus(200);

            $currentLocale = $this->getCurrentLocale();
            
            if ($currentLocale === 'zh_TW') {
                $response->assertSee('權限管理');
                $response->assertSee('權限名稱');
                $response->assertSee('顯示名稱');
                $response->assertSee('描述');
                $response->assertSee('模組');
                $response->assertSee('搜尋權限');
                $response->assertSee('模組篩選');
                $response->assertSee('全部模組');
            } else {
                $response->assertSee('Permission Management');
                $response->assertSee('Permission Name');
                $response->assertSee('Display Name');
                $response->assertSee('Description');
                $response->assertSee('Module');
                $response->assertSee('Search Permissions');
                $response->assertSee('Filter by Module');
                $response->assertSee('All Modules');
            }
        });
    }

    /**
     * 測試權限矩陣頁面的多語系功能
     */
    public function test_permission_matrix_page_multilingual_functionality(): void
    {
        $this->actingAs($this->adminUser);

        $this->runInAllLocales(function () {
            $response = $this->get('/admin/permissions/matrix');
            $response->assertStatus(200);

            $currentLocale = $this->getCurrentLocale();
            
            if ($currentLocale === 'zh_TW') {
                $response->assertSee('權限矩陣');
                $response->assertSee('管理角色和權限的對應關係');
            } else {
                $response->assertSee('Permission Matrix');
                $response->assertSee('Manage role and permission relationships');
            }
        });
    }

    /**
     * 測試導航選單的多語系翻譯
     */
    public function test_navigation_menu_multilingual_translation(): void
    {
        $this->actingAs($this->adminUser);

        $this->runInAllLocales(function () {
            $response = $this->get('/admin/dashboard');
            $response->assertStatus(200);

            $currentLocale = $this->getCurrentLocale();
            
            if ($currentLocale === 'zh_TW') {
                $response->assertSee('儀表板');
                $response->assertSee('使用者管理');
                $response->assertSee('角色管理');
                $response->assertSee('權限管理');
                $response->assertSee('活動記錄');
                $response->assertSee('系統設定');
            } else {
                $response->assertSee('Dashboard');
                $response->assertSee('User Management');
                $response->assertSee('Role Management');
                $response->assertSee('Permission Management');
                $response->assertSee('Activity Logs');
                $response->assertSee('System Settings');
            }
        });
    }

    /**
     * 測試麵包屑導航的多語系功能
     */
    public function test_breadcrumb_navigation_multilingual_functionality(): void
    {
        $this->actingAs($this->adminUser);

        // 測試使用者管理頁面的麵包屑
        $this->runInAllLocales(function () {
            $response = $this->get('/admin/users');
            $response->assertStatus(200);

            $currentLocale = $this->getCurrentLocale();
            
            if ($currentLocale === 'zh_TW') {
                $response->assertSee('使用者管理');
            } else {
                $response->assertSee('User Management');
            }
        });

        // 測試使用者建立頁面的麵包屑
        $this->runInAllLocales(function () {
            $response = $this->get('/admin/users/create');
            $response->assertStatus(200);

            $currentLocale = $this->getCurrentLocale();
            
            if ($currentLocale === 'zh_TW') {
                $response->assertSee('建立使用者');
            } else {
                $response->assertSee('Create User');
            }
        });
    }

    /**
     * 測試操作按鈕的多語系翻譯
     */
    public function test_action_buttons_multilingual_translation(): void
    {
        $this->actingAs($this->adminUser);

        $testUser = User::factory()->create();

        $this->runInAllLocales(function () use ($testUser) {
            $response = $this->get('/admin/users');
            $response->assertStatus(200);

            $currentLocale = $this->getCurrentLocale();
            
            if ($currentLocale === 'zh_TW') {
                $response->assertSee('檢視');
                $response->assertSee('編輯');
                $response->assertSee('刪除');
                $response->assertSee('建立使用者');
            } else {
                $response->assertSee('View');
                $response->assertSee('Edit');
                $response->assertSee('Delete');
                $response->assertSee('Create User');
            }
        });
    }

    /**
     * 測試表格標題的多語系翻譯
     */
    public function test_table_headers_multilingual_translation(): void
    {
        $this->actingAs($this->adminUser);

        // 測試使用者列表表格標題
        $this->runInAllLocales(function () {
            $response = $this->get('/admin/users');
            $response->assertStatus(200);

            $currentLocale = $this->getCurrentLocale();
            
            if ($currentLocale === 'zh_TW') {
                $response->assertSee('使用者名稱');
                $response->assertSee('姓名');
                $response->assertSee('電子郵件');
                $response->assertSee('角色');
                $response->assertSee('狀態');
                $response->assertSee('建立時間');
                $response->assertSee('操作');
            } else {
                $response->assertSee('Username');
                $response->assertSee('Name');
                $response->assertSee('Email');
                $response->assertSee('Roles');
                $response->assertSee('Status');
                $response->assertSee('Created At');
                $response->assertSee('Actions');
            }
        });

        // 測試角色列表表格標題
        $this->runInAllLocales(function () {
            $response = $this->get('/admin/roles');
            $response->assertStatus(200);

            $currentLocale = $this->getCurrentLocale();
            
            if ($currentLocale === 'zh_TW') {
                $response->assertSee('角色名稱');
                $response->assertSee('顯示名稱');
                $response->assertSee('描述');
                $response->assertSee('權限數');
                $response->assertSee('使用者數');
            } else {
                $response->assertSee('Role Name');
                $response->assertSee('Display Name');
                $response->assertSee('Description');
                $response->assertSee('Permissions Count');
                $response->assertSee('Users Count');
            }
        });
    }

    /**
     * 測試搜尋和篩選功能的多語系翻譯
     */
    public function test_search_and_filter_multilingual_translation(): void
    {
        $this->actingAs($this->adminUser);

        $this->runInAllLocales(function () {
            $response = $this->get('/admin/users');
            $response->assertStatus(200);

            $currentLocale = $this->getCurrentLocale();
            
            if ($currentLocale === 'zh_TW') {
                $response->assertSee('搜尋使用者');
                $response->assertSee('角色篩選');
                $response->assertSee('狀態篩選');
                $response->assertSee('全部角色');
                $response->assertSee('全部狀態');
                $response->assertSee('清除篩選');
            } else {
                $response->assertSee('Search Users');
                $response->assertSee('Filter by Role');
                $response->assertSee('Filter by Status');
                $response->assertSee('All Roles');
                $response->assertSee('All Status');
                $response->assertSee('Clear Filters');
            }
        });
    }

    /**
     * 測試狀態標籤的多語系翻譯
     */
    public function test_status_labels_multilingual_translation(): void
    {
        $this->actingAs($this->adminUser);

        // 建立啟用和停用的使用者
        $activeUser = User::factory()->create(['is_active' => true]);
        $inactiveUser = User::factory()->create(['is_active' => false]);

        $this->runInAllLocales(function () {
            $response = $this->get('/admin/users');
            $response->assertStatus(200);

            $currentLocale = $this->getCurrentLocale();
            
            if ($currentLocale === 'zh_TW') {
                $response->assertSee('啟用');
                $response->assertSee('停用');
            } else {
                $response->assertSee('Active');
                $response->assertSee('Inactive');
            }
        });
    }

    /**
     * 測試分頁控制項的多語系翻譯
     */
    public function test_pagination_controls_multilingual_translation(): void
    {
        $this->actingAs($this->adminUser);

        // 建立足夠的使用者以觸發分頁
        User::factory()->count(20)->create();

        $this->runInAllLocales(function () {
            $response = $this->get('/admin/users');
            $response->assertStatus(200);

            $currentLocale = $this->getCurrentLocale();
            
            if ($currentLocale === 'zh_TW') {
                $response->assertSee('上一頁');
                $response->assertSee('下一頁');
                $response->assertSee('每頁顯示');
            } else {
                $response->assertSee('Previous');
                $response->assertSee('Next');
                $response->assertSee('Per page');
            }
        });
    }

    /**
     * 測試語言切換後的頁面重新載入
     */
    public function test_page_reload_after_language_switch(): void
    {
        $this->actingAs($this->adminUser);

        // 初始為中文
        $this->setTestLocale('zh_TW');
        $response = $this->get('/admin/users');
        $response->assertStatus(200);
        $response->assertSee('使用者管理');

        // 切換到英文
        $this->setTestLocale('en');
        $response = $this->get('/admin/users');
        $response->assertStatus(200);
        $response->assertSee('User Management');
        $response->assertDontSee('使用者管理');

        // 切換回中文
        $this->setTestLocale('zh_TW');
        $response = $this->get('/admin/users');
        $response->assertStatus(200);
        $response->assertSee('使用者管理');
        $response->assertDontSee('User Management');
    }

    /**
     * 測試語言偏好在管理後台的持久化
     */
    public function test_language_preference_persistence_in_admin(): void
    {
        // 設定使用者語言偏好為英文
        $this->adminUser->update(['locale' => 'en']);
        $this->actingAs($this->adminUser);

        // 訪問管理頁面應該使用使用者偏好語言
        $response = $this->get('/admin/dashboard');
        $response->assertStatus(200);
        $response->assertSee('Dashboard');
        $this->assertEquals('en', App::getLocale());

        // 切換語言偏好為中文
        $this->adminUser->update(['locale' => 'zh_TW']);
        
        // 重新訪問應該使用新的語言偏好
        $response = $this->get('/admin/dashboard');
        $response->assertStatus(200);
        $response->assertSee('儀表板');
        $this->assertEquals('zh_TW', App::getLocale());
    }

    /**
     * 測試管理後台頁面的語言檔案完整性
     */
    public function test_admin_pages_language_file_completeness(): void
    {
        // 檢查管理介面相關的語言檔案完整性
        $this->assertLanguageFileCompleteness('admin');
        
        // 檢查特定翻譯鍵在所有語言中都存在
        $requiredTranslationKeys = [
            'admin.dashboard.title',
            'admin.users.title',
            'admin.roles.title',
            'admin.permissions.title',
            'admin.navigation.dashboard',
            'admin.navigation.users',
            'admin.navigation.roles',
            'admin.navigation.permissions',
            'admin.actions.create',
            'admin.actions.edit',
            'admin.actions.delete',
            'admin.actions.view',
            'admin.common.search',
            'admin.common.filter',
            'admin.common.loading',
        ];

        foreach ($requiredTranslationKeys as $key) {
            $this->assertTranslationExistsInAllLocales($key);
        }
    }

    /**
     * 測試管理後台錯誤頁面的多語系功能
     */
    public function test_admin_error_pages_multilingual_functionality(): void
    {
        $this->actingAs($this->adminUser);

        // 測試 404 錯誤頁面
        $this->runInAllLocales(function () {
            $response = $this->get('/admin/nonexistent-page');
            $response->assertStatus(404);
            
            // 驗證錯誤頁面使用正確的語言
            $this->assertEquals($this->getCurrentLocale(), App::getLocale());
        });
    }

    /**
     * 測試管理後台表單驗證訊息的多語系功能
     */
    public function test_admin_form_validation_messages_multilingual(): void
    {
        $this->actingAs($this->adminUser);

        $this->runInAllLocales(function () {
            // 提交空的使用者建立表單以觸發驗證錯誤
            $response = $this->post('/admin/users', []);
            
            // 驗證回應包含驗證錯誤
            $response->assertSessionHasErrors();
            
            // 驗證語言設定正確
            $this->assertEquals($this->getCurrentLocale(), App::getLocale());
        });
    }

    /**
     * 測試管理後台成功訊息的多語系功能
     */
    public function test_admin_success_messages_multilingual(): void
    {
        $this->actingAs($this->adminUser);

        $this->runInAllLocales(function () {
            // 建立使用者以觸發成功訊息
            $userData = [
                'username' => 'testuser_' . $this->getCurrentLocale(),
                'name' => 'Test User',
                'email' => 'test_' . $this->getCurrentLocale() . '@example.com',
                'password' => 'password123',
                'password_confirmation' => 'password123',
                'is_active' => true,
            ];

            $response = $this->post('/admin/users', $userData);
            
            // 驗證重定向成功
            $response->assertRedirect('/admin/users');
            
            // 驗證語言設定正確
            $this->assertEquals($this->getCurrentLocale(), App::getLocale());
        });
    }
}