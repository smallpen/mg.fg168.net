<?php

namespace Tests\Integration\PermissionManagement;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * 權限管理瀏覽器自動化測試
 * 
 * 使用 Playwright MCP 進行端到端測試，涵蓋：
 * - 完整的使用者操作流程
 * - 響應式設計測試
 * - 即時互動功能測試
 * - 無障礙功能測試
 */
class PermissionManagementBrowserTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected Role $adminRole;

    protected function setUp(): void
    {
        parent::setUp();
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
            ['name' => 'permissions.manage', 'display_name' => '管理權限', 'module' => 'permissions', 'type' => 'manage'],
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
            ['name' => 'users.edit', 'display_name' => '編輯使用者', 'module' => 'users', 'type' => 'edit'],
            ['name' => 'users.delete', 'display_name' => '刪除使用者', 'module' => 'users', 'type' => 'delete'],
            ['name' => 'reports.view', 'display_name' => '檢視報表', 'module' => 'reports', 'type' => 'view'],
            ['name' => 'reports.export', 'display_name' => '匯出報表', 'module' => 'reports', 'type' => 'export'],
        ];

        foreach ($testPermissions as $permissionData) {
            Permission::create($permissionData);
        }
    }

    /** @test */
    public function test_complete_permission_management_workflow_browser()
    {
        // 啟動瀏覽器並導航到登入頁面
        $this->startBrowser();
        $this->loginAsAdmin();

        // 1. 導航到權限管理頁面
        $this->navigateToPermissionsPage();

        // 2. 測試權限列表顯示
        $this->verifyPermissionListDisplay();

        // 3. 測試搜尋功能
        $this->testPermissionSearch();

        // 4. 測試篩選功能
        $this->testPermissionFilters();

        // 5. 測試建立權限
        $this->testCreatePermission();

        // 6. 測試編輯權限
        $this->testEditPermission();

        // 7. 測試依賴關係管理
        $this->testDependencyManagement();

        // 8. 測試權限刪除
        $this->testDeletePermission();

        // 9. 測試匯入匯出功能
        $this->testImportExportFunctionality();

        // 10. 測試權限測試工具
        $this->testPermissionTestTool();

        $this->closeBrowser();
    }

    protected function startBrowser(): void
    {
        // 使用 Playwright MCP 啟動瀏覽器
        $this->playwright()->navigate([
            'url' => config('app.url'),
            'headless' => false, // 設為 true 以在 CI 環境中運行
            'width' => 1280,
            'height' => 720
        ]);
    }

    protected function loginAsAdmin(): void
    {
        // 導航到登入頁面
        $this->playwright()->navigate(['url' => config('app.url') . '/admin/login']);

        // 填寫登入表單
        $this->playwright()->fill([
            'selector' => 'input[name="username"]',
            'value' => 'admin'
        ]);

        $this->playwright()->fill([
            'selector' => 'input[name="password"]',
            'value' => 'password123'
        ]);

        // 提交登入表單
        $this->playwright()->click(['selector' => 'button[type="submit"]']);

        // 等待登入完成並截圖
        $this->playwright()->screenshot(['name' => 'after-login']);

        // 驗證登入成功
        $pageText = $this->playwright()->getVisibleText();
        $this->assertStringContains('管理後台', $pageText);
    }

    protected function navigateToPermissionsPage(): void
    {
        // 點擊權限管理選單
        $this->playwright()->click(['selector' => 'a[href*="/admin/permissions"]']);

        // 等待頁面載入並截圖
        $this->playwright()->screenshot(['name' => 'permissions-page']);

        // 驗證頁面載入
        $pageText = $this->playwright()->getVisibleText();
        $this->assertStringContains('權限管理', $pageText);
    }

    protected function verifyPermissionListDisplay(): void
    {
        // 檢查權限列表表格是否顯示
        $html = $this->playwright()->getVisibleHtml(['selector' => '.permissions-table']);
        $this->assertStringContains('權限名稱', $html);
        $this->assertStringContains('顯示名稱', $html);
        $this->assertStringContains('模組', $html);
        $this->assertStringContains('類型', $html);

        // 檢查是否顯示權限資料
        $this->assertStringContains('permissions.view', $html);
        $this->assertStringContains('users.view', $html);
    }

    protected function testPermissionSearch(): void
    {
        // 測試搜尋功能
        $this->playwright()->fill([
            'selector' => 'input[name="search"]',
            'value' => 'users'
        ]);

        // 等待搜尋結果
        sleep(1);
        $this->playwright()->screenshot(['name' => 'search-results']);

        // 驗證搜尋結果
        $html = $this->playwright()->getVisibleHtml(['selector' => '.permissions-table']);
        $this->assertStringContains('users.view', $html);
        $this->assertStringNotContains('permissions.view', $html);

        // 清除搜尋
        $this->playwright()->fill([
            'selector' => 'input[name="search"]',
            'value' => ''
        ]);
    }

    protected function testPermissionFilters(): void
    {
        // 測試模組篩選
        $this->playwright()->select([
            'selector' => 'select[name="module_filter"]',
            'value' => 'users'
        ]);

        sleep(1);
        $this->playwright()->screenshot(['name' => 'module-filter']);

        $html = $this->playwright()->getVisibleHtml(['selector' => '.permissions-table']);
        $this->assertStringContains('users.view', $html);
        $this->assertStringNotContains('permissions.view', $html);

        // 測試類型篩選
        $this->playwright()->select([
            'selector' => 'select[name="type_filter"]',
            'value' => 'view'
        ]);

        sleep(1);
        $this->playwright()->screenshot(['name' => 'type-filter']);

        // 重置篩選
        $this->playwright()->select([
            'selector' => 'select[name="module_filter"]',
            'value' => 'all'
        ]);

        $this->playwright()->select([
            'selector' => 'select[name="type_filter"]',
            'value' => 'all'
        ]);
    }

    protected function testCreatePermission(): void
    {
        // 點擊建立權限按鈕
        $this->playwright()->click(['selector' => 'a[href*="/admin/permissions/create"]']);

        // 等待表單載入
        $this->playwright()->screenshot(['name' => 'create-permission-form']);

        // 填寫權限表單
        $this->playwright()->fill([
            'selector' => 'input[name="name"]',
            'value' => 'browser.test'
        ]);

        $this->playwright()->fill([
            'selector' => 'input[name="display_name"]',
            'value' => '瀏覽器測試權限'
        ]);

        $this->playwright()->fill([
            'selector' => 'textarea[name="description"]',
            'value' => '這是瀏覽器自動化測試建立的權限'
        ]);

        $this->playwright()->select([
            'selector' => 'select[name="module"]',
            'value' => 'browser_test'
        ]);

        $this->playwright()->select([
            'selector' => 'select[name="type"]',
            'value' => 'view'
        ]);

        // 提交表單
        $this->playwright()->click(['selector' => 'button[type="submit"]']);

        // 等待重導向並截圖
        sleep(2);
        $this->playwright()->screenshot(['name' => 'after-create-permission']);

        // 驗證權限已建立
        $pageText = $this->playwright()->getVisibleText();
        $this->assertStringContains('browser.test', $pageText);
        $this->assertStringContains('瀏覽器測試權限', $pageText);
    }

    protected function testEditPermission(): void
    {
        // 點擊編輯按鈕（假設是第一個編輯按鈕）
        $this->playwright()->click(['selector' => '.edit-permission-btn:first-child']);

        // 等待編輯表單載入
        $this->playwright()->screenshot(['name' => 'edit-permission-form']);

        // 修改顯示名稱
        $this->playwright()->fill([
            'selector' => 'input[name="display_name"]',
            'value' => '瀏覽器測試權限（已編輯）'
        ]);

        // 提交表單
        $this->playwright()->click(['selector' => 'button[type="submit"]']);

        // 等待更新完成
        sleep(2);
        $this->playwright()->screenshot(['name' => 'after-edit-permission']);

        // 驗證權限已更新
        $pageText = $this->playwright()->getVisibleText();
        $this->assertStringContains('瀏覽器測試權限（已編輯）', $pageText);
    }

    protected function testDependencyManagement(): void
    {
        // 點擊依賴關係管理按鈕
        $this->playwright()->click(['selector' => '.dependency-btn:first-child']);

        // 等待依賴關係頁面載入
        $this->playwright()->screenshot(['name' => 'dependency-management']);

        // 測試新增依賴關係
        $this->playwright()->select([
            'selector' => 'select[name="dependency_permission"]',
            'value' => 'users.view'
        ]);

        $this->playwright()->click(['selector' => '.add-dependency-btn']);

        // 等待依賴關係更新
        sleep(1);
        $this->playwright()->screenshot(['name' => 'after-add-dependency']);

        // 驗證依賴關係已新增
        $html = $this->playwright()->getVisibleHtml(['selector' => '.dependencies-list']);
        $this->assertStringContains('users.view', $html);
    }

    protected function testDeletePermission(): void
    {
        // 點擊刪除按鈕
        $this->playwright()->click(['selector' => '.delete-permission-btn:last-child']);

        // 等待確認對話框出現
        sleep(1);
        $this->playwright()->screenshot(['name' => 'delete-confirmation']);

        // 確認刪除
        $this->playwright()->click(['selector' => '.confirm-delete-btn']);

        // 等待刪除完成
        sleep(2);
        $this->playwright()->screenshot(['name' => 'after-delete-permission']);

        // 驗證權限已刪除（這裡需要根據實際的刪除邏輯調整）
        $pageText = $this->playwright()->getVisibleText();
        $this->assertStringContains('權限已成功刪除', $pageText);
    }

    protected function testImportExportFunctionality(): void
    {
        // 測試匯出功能
        $this->playwright()->click(['selector' => '.export-permissions-btn']);

        // 等待匯出對話框
        sleep(1);
        $this->playwright()->screenshot(['name' => 'export-dialog']);

        // 選擇匯出選項
        $this->playwright()->select([
            'selector' => 'select[name="export_format"]',
            'value' => 'json'
        ]);

        $this->playwright()->click(['selector' => '.confirm-export-btn']);

        // 等待匯出完成
        sleep(2);
        $this->playwright()->screenshot(['name' => 'after-export']);

        // 測試匯入功能
        $this->playwright()->click(['selector' => '.import-permissions-btn']);

        // 等待匯入對話框
        sleep(1);
        $this->playwright()->screenshot(['name' => 'import-dialog']);

        // 這裡可以測試檔案上傳功能（需要準備測試檔案）
        // $this->playwright()->uploadFile([
        //     'selector' => 'input[type="file"]',
        //     'filePath' => '/path/to/test-permissions.json'
        // ]);
    }

    protected function testPermissionTestTool(): void
    {
        // 導航到權限測試頁面
        $this->playwright()->click(['selector' => 'a[href*="/admin/permissions/test"]']);

        // 等待頁面載入
        $this->playwright()->screenshot(['name' => 'permission-test-tool']);

        // 選擇測試使用者
        $this->playwright()->select([
            'selector' => 'select[name="user_id"]',
            'value' => $this->adminUser->id
        ]);

        // 選擇測試權限
        $this->playwright()->select([
            'selector' => 'select[name="permission"]',
            'value' => 'permissions.view'
        ]);

        // 執行測試
        $this->playwright()->click(['selector' => '.test-permission-btn']);

        // 等待測試結果
        sleep(2);
        $this->playwright()->screenshot(['name' => 'permission-test-results']);

        // 驗證測試結果
        $html = $this->playwright()->getVisibleHtml(['selector' => '.test-results']);
        $this->assertStringContains('權限檢查結果', $html);
        $this->assertStringContains('通過', $html);
    }

    /** @test */
    public function test_responsive_design()
    {
        $this->startBrowser();
        $this->loginAsAdmin();
        $this->navigateToPermissionsPage();

        // 測試桌面版本 (1280x720)
        $this->playwright()->screenshot(['name' => 'desktop-view']);
        $html = $this->playwright()->getVisibleHtml();
        $this->assertStringContains('permissions-table', $html);

        // 測試平板版本 (768x1024)
        $this->playwright()->evaluate([
            'script' => 'window.resizeTo(768, 1024);'
        ]);
        sleep(1);
        $this->playwright()->screenshot(['name' => 'tablet-view']);

        // 測試手機版本 (375x667)
        $this->playwright()->evaluate([
            'script' => 'window.resizeTo(375, 667);'
        ]);
        sleep(1);
        $this->playwright()->screenshot(['name' => 'mobile-view']);

        // 在手機版本中測試選單功能
        $this->playwright()->click(['selector' => '.mobile-menu-toggle']);
        sleep(1);
        $this->playwright()->screenshot(['name' => 'mobile-menu-open']);

        $this->closeBrowser();
    }

    /** @test */
    public function test_accessibility_features()
    {
        $this->startBrowser();
        $this->loginAsAdmin();
        $this->navigateToPermissionsPage();

        // 測試鍵盤導航
        $this->playwright()->pressKey(['key' => 'Tab']);
        sleep(0.5);
        $this->playwright()->pressKey(['key' => 'Tab']);
        sleep(0.5);
        $this->playwright()->pressKey(['key' => 'Enter']);

        $this->playwright()->screenshot(['name' => 'keyboard-navigation']);

        // 測試 ARIA 標籤
        $html = $this->playwright()->getVisibleHtml();
        $this->assertStringContains('aria-label', $html);
        $this->assertStringContains('role=', $html);

        // 測試焦點管理
        $this->playwright()->evaluate([
            'script' => 'document.querySelector("input[name=\'search\']").focus();'
        ]);
        $this->playwright()->screenshot(['name' => 'focus-management']);

        $this->closeBrowser();
    }

    /** @test */
    public function test_real_time_updates()
    {
        $this->startBrowser();
        $this->loginAsAdmin();
        $this->navigateToPermissionsPage();

        // 測試即時搜尋
        $this->playwright()->fill([
            'selector' => 'input[name="search"]',
            'value' => 'u'
        ]);
        sleep(0.5);
        $this->playwright()->screenshot(['name' => 'realtime-search-1']);

        $this->playwright()->fill([
            'selector' => 'input[name="search"]',
            'value' => 'us'
        ]);
        sleep(0.5);
        $this->playwright()->screenshot(['name' => 'realtime-search-2']);

        $this->playwright()->fill([
            'selector' => 'input[name="search"]',
            'value' => 'user'
        ]);
        sleep(0.5);
        $this->playwright()->screenshot(['name' => 'realtime-search-3']);

        // 驗證搜尋結果即時更新
        $html = $this->playwright()->getVisibleHtml(['selector' => '.permissions-table']);
        $this->assertStringContains('users.view', $html);

        $this->closeBrowser();
    }

    /** @test */
    public function test_error_handling()
    {
        $this->startBrowser();
        $this->loginAsAdmin();
        $this->navigateToPermissionsPage();

        // 測試建立重複權限的錯誤處理
        $this->playwright()->click(['selector' => 'a[href*="/admin/permissions/create"]']);

        // 填寫已存在的權限名稱
        $this->playwright()->fill([
            'selector' => 'input[name="name"]',
            'value' => 'permissions.view'
        ]);

        $this->playwright()->fill([
            'selector' => 'input[name="display_name"]',
            'value' => '重複權限測試'
        ]);

        $this->playwright()->select([
            'selector' => 'select[name="module"]',
            'value' => 'test'
        ]);

        $this->playwright()->select([
            'selector' => 'select[name="type"]',
            'value' => 'view'
        ]);

        // 提交表單
        $this->playwright()->click(['selector' => 'button[type="submit"]']);

        // 等待錯誤訊息顯示
        sleep(2);
        $this->playwright()->screenshot(['name' => 'duplicate-permission-error']);

        // 驗證錯誤訊息
        $pageText = $this->playwright()->getVisibleText();
        $this->assertStringContains('權限名稱已存在', $pageText);

        $this->closeBrowser();
    }

    protected function playwright()
    {
        // 這裡應該返回 Playwright MCP 的實例
        // 實際實作中需要根據 MCP 的具體 API 調整
        return app('playwright');
    }

    protected function closeBrowser(): void
    {
        $this->playwright()->close();
    }

    protected function tearDown(): void
    {
        // 確保瀏覽器已關閉
        try {
            $this->closeBrowser();
        } catch (\Exception $e) {
            // 忽略關閉錯誤
        }

        parent::tearDown();
    }
}