<?php

namespace Tests\Integration\PermissionManagement;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * 權限管理 Playwright MCP 整合測試
 * 
 * 使用實際的 Playwright MCP 工具進行端到端測試
 */
class PermissionManagementPlaywrightTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected Role $adminRole;
    protected string $baseUrl;

    protected function setUp(): void
    {
        parent::setUp();
        $this->baseUrl = config('app.url', 'http://localhost');
        $this->setupTestData();
    }

    protected function setupTestData(): void
    {
        // 建立管理員角色和使用者
        $this->adminRole = Role::create([
            'name' => 'admin',
            'display_name' => '管理員',
            'description' => '系統管理員',
        ]);

        $this->adminUser = User::create([
            'username' => 'admin',
            'name' => '管理員',
            'email' => 'admin@test.com',
            'password' => bcrypt('password123'),
            'is_active' => true,
        ]);

        $this->adminUser->roles()->attach($this->adminRole);

        // 建立權限管理相關權限
        $permissions = [
            ['name' => 'permissions.view', 'display_name' => '檢視權限', 'module' => 'permissions', 'type' => 'view'],
            ['name' => 'permissions.create', 'display_name' => '建立權限', 'module' => 'permissions', 'type' => 'create'],
            ['name' => 'permissions.edit', 'display_name' => '編輯權限', 'module' => 'permissions', 'type' => 'edit'],
            ['name' => 'permissions.delete', 'display_name' => '刪除權限', 'module' => 'permissions', 'type' => 'delete'],
            ['name' => 'admin.access', 'display_name' => '管理員存取', 'module' => 'admin', 'type' => 'view'],
        ];

        foreach ($permissions as $permissionData) {
            $permission = Permission::create($permissionData);
            $this->adminRole->permissions()->attach($permission);
        }

        // 建立一些測試權限
        $testPermissions = [
            ['name' => 'users.view', 'display_name' => '檢視使用者', 'module' => 'users', 'type' => 'view'],
            ['name' => 'users.create', 'display_name' => '建立使用者', 'module' => 'users', 'type' => 'create'],
            ['name' => 'reports.view', 'display_name' => '檢視報表', 'module' => 'reports', 'type' => 'view'],
        ];

        foreach ($testPermissions as $permissionData) {
            Permission::create($permissionData);
        }
    }

    /** @test */
    public function test_complete_permission_workflow_with_playwright()
    {
        // 使用 Playwright MCP 進行完整的權限管理工作流程測試
        
        // 1. 啟動瀏覽器並導航到登入頁面
        $this->startBrowserSession();
        
        // 2. 執行登入流程
        $this->performLogin();
        
        // 3. 導航到權限管理頁面
        $this->navigateToPermissions();
        
        // 4. 測試權限列表功能
        $this->testPermissionList();
        
        // 5. 測試搜尋功能
        $this->testSearchFunctionality();
        
        // 6. 測試建立權限
        $this->testCreatePermission();
        
        // 7. 測試編輯權限
        $this->testEditPermission();
        
        // 8. 測試刪除權限
        $this->testDeletePermission();
        
        // 9. 關閉瀏覽器
        $this->closeBrowserSession();
    }

    protected function startBrowserSession(): void
    {
        // 使用 Playwright MCP 啟動瀏覽器
        mcp_playwright_playwright_navigate([
            'url' => $this->baseUrl,
            'headless' => true,
            'width' => 1280,
            'height' => 720
        ]);
        
        // 截圖記錄初始狀態
        mcp_playwright_playwright_screenshot([
            'name' => 'browser-started',
            'savePng' => true
        ]);
    }

    protected function performLogin(): void
    {
        // 導航到登入頁面
        mcp_playwright_playwright_navigate([
            'url' => $this->baseUrl . '/admin/login'
        ]);

        // 等待頁面載入
        sleep(2);
        
        // 截圖登入頁面
        mcp_playwright_playwright_screenshot([
            'name' => 'login-page',
            'savePng' => true
        ]);

        // 填寫登入表單
        mcp_playwright_playwright_fill([
            'selector' => 'input[name="username"]',
            'value' => 'admin'
        ]);

        mcp_playwright_playwright_fill([
            'selector' => 'input[name="password"]',
            'value' => 'password123'
        ]);

        // 提交登入表單
        mcp_playwright_playwright_click([
            'selector' => 'button[type="submit"]'
        ]);

        // 等待登入完成
        sleep(3);
        
        // 截圖登入後狀態
        mcp_playwright_playwright_screenshot([
            'name' => 'after-login',
            'savePng' => true
        ]);

        // 驗證登入成功
        $pageText = mcp_playwright_playwright_get_visible_text();
        $this->assertStringContains('管理後台', $pageText);
    }

    protected function navigateToPermissions(): void
    {
        // 導航到權限管理頁面
        mcp_playwright_playwright_navigate([
            'url' => $this->baseUrl . '/admin/permissions'
        ]);

        // 等待頁面載入
        sleep(2);
        
        // 截圖權限管理頁面
        mcp_playwright_playwright_screenshot([
            'name' => 'permissions-page',
            'savePng' => true
        ]);

        // 驗證頁面載入成功
        $pageText = mcp_playwright_playwright_get_visible_text();
        $this->assertStringContains('權限管理', $pageText);
    }

    protected function testPermissionList(): void
    {
        // 獲取頁面 HTML 內容
        $html = mcp_playwright_playwright_get_visible_html([
            'removeScripts' => true,
            'maxLength' => 20000
        ]);

        // 驗證權限列表表格存在
        $this->assertStringContains('permissions-table', $html);
        $this->assertStringContains('權限名稱', $html);
        $this->assertStringContains('顯示名稱', $html);
        $this->assertStringContains('模組', $html);
        $this->assertStringContains('類型', $html);

        // 驗證測試權限顯示
        $this->assertStringContains('users.view', $html);
        $this->assertStringContains('檢視使用者', $html);
        
        // 截圖權限列表
        mcp_playwright_playwright_screenshot([
            'name' => 'permission-list',
            'savePng' => true
        ]);
    }

    protected function testSearchFunctionality(): void
    {
        // 測試搜尋功能
        mcp_playwright_playwright_fill([
            'selector' => 'input[name="search"]',
            'value' => 'users'
        ]);

        // 等待搜尋結果
        sleep(2);
        
        // 截圖搜尋結果
        mcp_playwright_playwright_screenshot([
            'name' => 'search-results',
            'savePng' => true
        ]);

        // 驗證搜尋結果
        $html = mcp_playwright_playwright_get_visible_html([
            'selector' => '.permissions-table',
            'removeScripts' => true
        ]);
        
        $this->assertStringContains('users.view', $html);
        $this->assertStringNotContains('reports.view', $html);

        // 清除搜尋
        mcp_playwright_playwright_fill([
            'selector' => 'input[name="search"]',
            'value' => ''
        ]);
        
        sleep(1);
    }

    protected function testCreatePermission(): void
    {
        // 點擊建立權限按鈕
        mcp_playwright_playwright_click([
            'selector' => 'a[href*="/admin/permissions/create"], .create-permission-btn'
        ]);

        // 等待表單載入
        sleep(2);
        
        // 截圖建立表單
        mcp_playwright_playwright_screenshot([
            'name' => 'create-permission-form',
            'savePng' => true
        ]);

        // 填寫權限表單
        mcp_playwright_playwright_fill([
            'selector' => 'input[name="name"]',
            'value' => 'playwright.test'
        ]);

        mcp_playwright_playwright_fill([
            'selector' => 'input[name="display_name"]',
            'value' => 'Playwright 測試權限'
        ]);

        mcp_playwright_playwright_fill([
            'selector' => 'textarea[name="description"]',
            'value' => '這是 Playwright 自動化測試建立的權限'
        ]);

        // 選擇模組
        mcp_playwright_playwright_select([
            'selector' => 'select[name="module"]',
            'value' => 'testing'
        ]);

        // 選擇類型
        mcp_playwright_playwright_select([
            'selector' => 'select[name="type"]',
            'value' => 'view'
        ]);

        // 提交表單
        mcp_playwright_playwright_click([
            'selector' => 'button[type="submit"]'
        ]);

        // 等待重導向
        sleep(3);
        
        // 截圖建立後狀態
        mcp_playwright_playwright_screenshot([
            'name' => 'after-create-permission',
            'savePng' => true
        ]);

        // 驗證權限已建立
        $pageText = mcp_playwright_playwright_get_visible_text();
        $this->assertStringContains('playwright.test', $pageText);
        $this->assertStringContains('Playwright 測試權限', $pageText);
        
        // 同時驗證資料庫
        $this->assertDatabaseHas('permissions', [
            'name' => 'playwright.test',
            'display_name' => 'Playwright 測試權限'
        ]);
    }

    protected function testEditPermission(): void
    {
        // 尋找並點擊編輯按鈕
        mcp_playwright_playwright_click([
            'selector' => '.edit-permission-btn:first-child, [data-action="edit"]:first-child'
        ]);

        // 等待編輯表單載入
        sleep(2);
        
        // 截圖編輯表單
        mcp_playwright_playwright_screenshot([
            'name' => 'edit-permission-form',
            'savePng' => true
        ]);

        // 修改顯示名稱
        mcp_playwright_playwright_fill([
            'selector' => 'input[name="display_name"]',
            'value' => 'Playwright 測試權限（已編輯）'
        ]);

        // 提交表單
        mcp_playwright_playwright_click([
            'selector' => 'button[type="submit"]'
        ]);

        // 等待更新完成
        sleep(3);
        
        // 截圖編輯後狀態
        mcp_playwright_playwright_screenshot([
            'name' => 'after-edit-permission',
            'savePng' => true
        ]);

        // 驗證權限已更新
        $pageText = mcp_playwright_playwright_get_visible_text();
        $this->assertStringContains('Playwright 測試權限（已編輯）', $pageText);
    }

    protected function testDeletePermission(): void
    {
        // 點擊刪除按鈕
        mcp_playwright_playwright_click([
            'selector' => '.delete-permission-btn:last-child, [data-action="delete"]:last-child'
        ]);

        // 等待確認對話框出現
        sleep(1);
        
        // 截圖確認對話框
        mcp_playwright_playwright_screenshot([
            'name' => 'delete-confirmation',
            'savePng' => true
        ]);

        // 確認刪除
        mcp_playwright_playwright_click([
            'selector' => '.confirm-delete-btn, [data-confirm="yes"]'
        ]);

        // 等待刪除完成
        sleep(3);
        
        // 截圖刪除後狀態
        mcp_playwright_playwright_screenshot([
            'name' => 'after-delete-permission',
            'savePng' => true
        ]);

        // 驗證刪除成功訊息
        $pageText = mcp_playwright_playwright_get_visible_text();
        $this->assertStringContains('成功', $pageText);
    }

    /** @test */
    public function test_responsive_design_with_playwright()
    {
        $this->startBrowserSession();
        $this->performLogin();
        $this->navigateToPermissions();

        // 測試桌面版本 (1280x720) - 已經是預設尺寸
        mcp_playwright_playwright_screenshot([
            'name' => 'desktop-responsive',
            'savePng' => true
        ]);

        // 測試平板版本 (768x1024)
        mcp_playwright_playwright_evaluate([
            'script' => 'window.resizeTo(768, 1024);'
        ]);
        sleep(1);
        mcp_playwright_playwright_screenshot([
            'name' => 'tablet-responsive',
            'savePng' => true
        ]);

        // 測試手機版本 (375x667)
        mcp_playwright_playwright_evaluate([
            'script' => 'window.resizeTo(375, 667);'
        ]);
        sleep(1);
        mcp_playwright_playwright_screenshot([
            'name' => 'mobile-responsive',
            'savePng' => true
        ]);

        // 驗證響應式佈局
        $html = mcp_playwright_playwright_get_visible_html([
            'removeScripts' => true
        ]);
        
        // 在手機版本中應該有不同的佈局
        $this->assertStringContains('mobile', $html);

        $this->closeBrowserSession();
    }

    /** @test */
    public function test_accessibility_features_with_playwright()
    {
        $this->startBrowserSession();
        $this->performLogin();
        $this->navigateToPermissions();

        // 測試鍵盤導航
        mcp_playwright_playwright_press_key(['key' => 'Tab']);
        sleep(0.5);
        mcp_playwright_playwright_press_key(['key' => 'Tab']);
        sleep(0.5);
        
        mcp_playwright_playwright_screenshot([
            'name' => 'keyboard-navigation',
            'savePng' => true
        ]);

        // 測試 Enter 鍵操作
        mcp_playwright_playwright_press_key(['key' => 'Enter']);
        sleep(1);
        
        mcp_playwright_playwright_screenshot([
            'name' => 'keyboard-enter',
            'savePng' => true
        ]);

        // 檢查 ARIA 標籤和無障礙屬性
        $html = mcp_playwright_playwright_get_visible_html([
            'removeScripts' => true
        ]);
        
        $this->assertStringContains('aria-label', $html);
        $this->assertStringContains('role=', $html);

        // 測試焦點管理
        mcp_playwright_playwright_evaluate([
            'script' => 'document.querySelector("input[name=\'search\']").focus();'
        ]);
        
        mcp_playwright_playwright_screenshot([
            'name' => 'focus-management',
            'savePng' => true
        ]);

        $this->closeBrowserSession();
    }

    /** @test */
    public function test_error_handling_with_playwright()
    {
        $this->startBrowserSession();
        $this->performLogin();
        $this->navigateToPermissions();

        // 測試建立重複權限的錯誤處理
        mcp_playwright_playwright_click([
            'selector' => 'a[href*="/admin/permissions/create"]'
        ]);

        sleep(2);

        // 填寫已存在的權限名稱
        mcp_playwright_playwright_fill([
            'selector' => 'input[name="name"]',
            'value' => 'permissions.view'
        ]);

        mcp_playwright_playwright_fill([
            'selector' => 'input[name="display_name"]',
            'value' => '重複權限測試'
        ]);

        // 提交表單
        mcp_playwright_playwright_click([
            'selector' => 'button[type="submit"]'
        ]);

        // 等待錯誤訊息顯示
        sleep(2);
        
        mcp_playwright_playwright_screenshot([
            'name' => 'duplicate-permission-error',
            'savePng' => true
        ]);

        // 驗證錯誤訊息
        $pageText = mcp_playwright_playwright_get_visible_text();
        $this->assertStringContains('已存在', $pageText);

        $this->closeBrowserSession();
    }

    /** @test */
    public function test_real_time_search_with_playwright()
    {
        $this->startBrowserSession();
        $this->performLogin();
        $this->navigateToPermissions();

        // 測試即時搜尋
        $searchTerms = ['u', 'us', 'use', 'user'];
        
        foreach ($searchTerms as $index => $term) {
            mcp_playwright_playwright_fill([
                'selector' => 'input[name="search"]',
                'value' => $term
            ]);
            
            sleep(0.5);
            
            mcp_playwright_playwright_screenshot([
                'name' => "realtime-search-{$index}",
                'savePng' => true
            ]);
        }

        // 驗證最終搜尋結果
        $html = mcp_playwright_playwright_get_visible_html([
            'selector' => '.permissions-table',
            'removeScripts' => true
        ]);
        
        $this->assertStringContains('users.view', $html);

        $this->closeBrowserSession();
    }

    /** @test */
    public function test_permission_dependency_ui_with_playwright()
    {
        $this->startBrowserSession();
        $this->performLogin();
        $this->navigateToPermissions();

        // 點擊依賴關係管理按鈕
        mcp_playwright_playwright_click([
            'selector' => '.dependency-btn:first-child, [data-action="dependencies"]:first-child'
        ]);

        sleep(2);
        
        mcp_playwright_playwright_screenshot([
            'name' => 'dependency-management',
            'savePng' => true
        ]);

        // 測試新增依賴關係
        mcp_playwright_playwright_select([
            'selector' => 'select[name="dependency_permission"]',
            'value' => 'users.view'
        ]);

        mcp_playwright_playwright_click([
            'selector' => '.add-dependency-btn'
        ]);

        sleep(1);
        
        mcp_playwright_playwright_screenshot([
            'name' => 'after-add-dependency',
            'savePng' => true
        ]);

        // 驗證依賴關係已新增
        $html = mcp_playwright_playwright_get_visible_html([
            'selector' => '.dependencies-list',
            'removeScripts' => true
        ]);
        
        $this->assertStringContains('users.view', $html);

        $this->closeBrowserSession();
    }

    protected function closeBrowserSession(): void
    {
        // 關閉瀏覽器
        mcp_playwright_playwright_close();
    }

    /** @test */
    public function test_database_verification_with_mysql_mcp()
    {
        // 使用 MySQL MCP 驗證資料庫狀態
        
        // 檢查權限表結構
        $tableStructure = mcp_mysql_describe_table([
            'table' => 'permissions',
            'database' => 'laravel_admin'
        ]);
        
        $this->assertNotEmpty($tableStructure);

        // 檢查測試權限是否存在
        $permissions = mcp_mysql_execute_query([
            'query' => 'SELECT name, display_name, module, type FROM permissions WHERE module IN ("users", "reports", "permissions") ORDER BY name',
            'database' => 'laravel_admin'
        ]);

        $this->assertNotEmpty($permissions);
        
        // 驗證特定權限存在
        $permissionNames = array_column($permissions, 'name');
        $this->assertContains('permissions.view', $permissionNames);
        $this->assertContains('users.view', $permissionNames);

        // 檢查權限依賴關係表
        $dependencies = mcp_mysql_execute_query([
            'query' => 'SELECT COUNT(*) as count FROM permission_dependencies',
            'database' => 'laravel_admin'
        ]);

        $this->assertIsArray($dependencies);

        // 檢查使用者角色關聯
        $userRoles = mcp_mysql_execute_query([
            'query' => 'SELECT u.username, r.name as role_name FROM users u JOIN user_roles ur ON u.id = ur.user_id JOIN roles r ON ur.role_id = r.id WHERE u.username = "admin"',
            'database' => 'laravel_admin'
        ]);

        $this->assertNotEmpty($userRoles);
        $this->assertEquals('admin', $userRoles[0]['username']);
        $this->assertEquals('admin', $userRoles[0]['role_name']);
    }

    protected function tearDown(): void
    {
        // 確保瀏覽器已關閉
        try {
            mcp_playwright_playwright_close();
        } catch (\Exception $e) {
            // 忽略關閉錯誤
        }

        parent::tearDown();
    }
}