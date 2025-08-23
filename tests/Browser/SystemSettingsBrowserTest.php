<?php

namespace Tests\Browser;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

/**
 * 系統設定瀏覽器自動化測試
 * 
 * 使用 Laravel Dusk 進行端到端測試，模擬真實使用者操作
 */
class SystemSettingsBrowserTest extends DuskTestCase
{
    use DatabaseMigrations;

    protected User $adminUser;
    protected User $editorUser;
    protected User $regularUser;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->createTestUsers();
        $this->createTestSettings();
    }

    /**
     * 建立測試使用者
     */
    protected function createTestUsers(): void
    {
        $this->adminUser = User::factory()->create([
            'username' => 'admin',
            'name' => '系統管理員',
            'email' => 'admin@test.com',
            'is_active' => true,
        ]);

        $this->editorUser = User::factory()->create([
            'username' => 'editor',
            'name' => '設定編輯者',
            'email' => 'editor@test.com',
            'is_active' => true,
        ]);

        $this->regularUser = User::factory()->create([
            'username' => 'regular',
            'name' => '一般使用者',
            'email' => 'regular@test.com',
            'is_active' => true,
        ]);
    }

    /**
     * 建立測試設定
     */
    protected function createTestSettings(): void
    {
        Setting::create([
            'key' => 'app.name',
            'value' => 'Test Application',
            'category' => 'basic',
            'type' => 'text',
            'description' => '應用程式名稱',
            'default_value' => 'Laravel Admin',
            'is_system' => false,
        ]);

        Setting::create([
            'key' => 'app.timezone',
            'value' => 'Asia/Taipei',
            'category' => 'basic',
            'type' => 'select',
            'description' => '系統時區',
            'default_value' => 'UTC',
            'is_system' => true,
        ]);

        Setting::create([
            'key' => 'security.password_min_length',
            'value' => 8,
            'category' => 'security',
            'type' => 'number',
            'description' => '密碼最小長度',
            'default_value' => 8,
            'is_system' => false,
        ]);

        Setting::create([
            'key' => 'appearance.primary_color',
            'value' => '#3B82F6',
            'category' => 'appearance',
            'type' => 'color',
            'description' => '主要顏色',
            'default_value' => '#3B82F6',
            'is_system' => false,
        ]);
    }

    /**
     * 測試設定頁面基本顯示
     * 
     * @test
     */
    public function test_settings_page_basic_display(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->adminUser)
                    ->visit('/admin/settings')
                    ->waitForText('系統設定')
                    ->assertSee('基本設定')
                    ->assertSee('安全設定')
                    ->assertSee('外觀設定')
                    ->assertSee('app.name')
                    ->assertSee('Test Application')
                    ->screenshot('settings-page-basic-display');
        });
    }

    /**
     * 測試設定搜尋功能
     * 
     * @test
     */
    public function test_settings_search_functionality(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->adminUser)
                    ->visit('/admin/settings')
                    ->waitForText('系統設定')
                    ->type('input[placeholder*="搜尋"]', 'app')
                    ->waitFor('.setting-item')
                    ->assertSee('app.name')
                    ->assertSee('app.timezone')
                    ->assertDontSee('security.password_min_length')
                    ->screenshot('settings-search-functionality');
        });
    }

    /**
     * 測試設定分類篩選
     * 
     * @test
     */
    public function test_settings_category_filter(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->adminUser)
                    ->visit('/admin/settings')
                    ->waitForText('系統設定')
                    ->click('[data-category="basic"]')
                    ->waitFor('.setting-item')
                    ->assertSee('app.name')
                    ->assertSee('app.timezone')
                    ->assertDontSee('security.password_min_length')
                    ->click('[data-category="security"]')
                    ->waitFor('.setting-item')
                    ->assertSee('security.password_min_length')
                    ->assertDontSee('app.name')
                    ->screenshot('settings-category-filter');
        });
    }

    /**
     * 測試設定編輯功能
     * 
     * @test
     */
    public function test_settings_edit_functionality(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->adminUser)
                    ->visit('/admin/settings')
                    ->waitForText('系統設定')
                    ->click('[data-setting-key="app.name"] .edit-button')
                    ->waitFor('.setting-edit-modal')
                    ->assertSee('編輯設定')
                    ->clear('input[name="value"]')
                    ->type('input[name="value"]', 'Updated Application Name')
                    ->click('.save-button')
                    ->waitUntilMissing('.setting-edit-modal')
                    ->assertSee('Updated Application Name')
                    ->screenshot('settings-edit-functionality');
        });
    }

    /**
     * 測試設定驗證功能
     * 
     * @test
     */
    public function test_settings_validation_functionality(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->adminUser)
                    ->visit('/admin/settings')
                    ->waitForText('系統設定')
                    ->click('[data-setting-key="security.password_min_length"] .edit-button')
                    ->waitFor('.setting-edit-modal')
                    ->clear('input[name="value"]')
                    ->type('input[name="value"]', '3') // 無效值（小於最小值 6）
                    ->click('.save-button')
                    ->waitFor('.error-message')
                    ->assertSee('密碼長度必須至少為 6')
                    ->clear('input[name="value"]')
                    ->type('input[name="value"]', '10') // 有效值
                    ->click('.save-button')
                    ->waitUntilMissing('.setting-edit-modal')
                    ->assertSee('10')
                    ->screenshot('settings-validation-functionality');
        });
    }

    /**
     * 測試設定重設功能
     * 
     * @test
     */
    public function test_settings_reset_functionality(): void
    {
        $this->browse(function (Browser $browser) {
            // 先修改設定
            $browser->loginAs($this->adminUser)
                    ->visit('/admin/settings')
                    ->waitForText('系統設定')
                    ->click('[data-setting-key="app.name"] .edit-button')
                    ->waitFor('.setting-edit-modal')
                    ->clear('input[name="value"]')
                    ->type('input[name="value"]', 'Modified Name')
                    ->click('.save-button')
                    ->waitUntilMissing('.setting-edit-modal')
                    ->assertSee('Modified Name');

            // 然後重設設定
            $browser->click('[data-setting-key="app.name"] .reset-button')
                    ->waitFor('.confirm-dialog')
                    ->assertSee('確定要重設此設定嗎？')
                    ->click('.confirm-button')
                    ->waitUntilMissing('.confirm-dialog')
                    ->assertSee('Laravel Admin') // 預設值
                    ->screenshot('settings-reset-functionality');
        });
    }

    /**
     * 測試設定備份功能
     * 
     * @test
     */
    public function test_settings_backup_functionality(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->adminUser)
                    ->visit('/admin/settings')
                    ->waitForText('系統設定')
                    ->click('.backup-button')
                    ->waitFor('.backup-modal')
                    ->assertSee('建立設定備份')
                    ->type('input[name="backup_name"]', '瀏覽器測試備份')
                    ->type('textarea[name="backup_description"]', '自動化測試建立的備份')
                    ->click('.create-backup-button')
                    ->waitFor('.success-message')
                    ->assertSee('備份建立成功')
                    ->screenshot('settings-backup-functionality');
        });
    }

    /**
     * 測試設定還原功能
     * 
     * @test
     */
    public function test_settings_restore_functionality(): void
    {
        // 先建立備份
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->adminUser)
                    ->visit('/admin/settings')
                    ->waitForText('系統設定')
                    ->click('.backup-button')
                    ->waitFor('.backup-modal')
                    ->type('input[name="backup_name"]', '還原測試備份')
                    ->click('.create-backup-button')
                    ->waitFor('.success-message');

            // 修改設定
            $browser->click('[data-setting-key="app.name"] .edit-button')
                    ->waitFor('.setting-edit-modal')
                    ->clear('input[name="value"]')
                    ->type('input[name="value"]', 'Before Restore')
                    ->click('.save-button')
                    ->waitUntilMissing('.setting-edit-modal')
                    ->assertSee('Before Restore');

            // 還原備份
            $browser->click('.backup-list-button')
                    ->waitFor('.backup-list-modal')
                    ->click('.restore-backup-button:first-child')
                    ->waitFor('.confirm-dialog')
                    ->assertSee('確定要還原此備份嗎？')
                    ->click('.confirm-button')
                    ->waitFor('.success-message')
                    ->assertSee('備份還原成功')
                    ->assertSee('Test Application') // 原始值
                    ->screenshot('settings-restore-functionality');
        });
    }

    /**
     * 測試設定匯出功能
     * 
     * @test
     */
    public function test_settings_export_functionality(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->adminUser)
                    ->visit('/admin/settings')
                    ->waitForText('系統設定')
                    ->click('.export-button')
                    ->waitFor('.export-modal')
                    ->assertSee('匯出設定')
                    ->assertSee('匯出選項')
                    ->check('input[name="categories[]"][value="basic"]')
                    ->check('input[name="categories[]"][value="security"]')
                    ->click('.export-download-button')
                    ->waitFor('.success-message')
                    ->assertSee('匯出完成')
                    ->screenshot('settings-export-functionality');
        });
    }

    /**
     * 測試設定匯入功能
     * 
     * @test
     */
    public function test_settings_import_functionality(): void
    {
        $this->browse(function (Browser $browser) {
            // 建立測試匯入檔案
            $importData = [
                [
                    'key' => 'test.import.setting',
                    'value' => 'Imported Value',
                    'category' => 'basic',
                    'type' => 'text',
                    'description' => '匯入的測試設定',
                    'default_value' => 'Default',
                    'is_system' => false,
                    'is_encrypted' => false,
                ]
            ];

            $tempFile = tempnam(sys_get_temp_dir(), 'settings_import_') . '.json';
            file_put_contents($tempFile, json_encode($importData));

            $browser->loginAs($this->adminUser)
                    ->visit('/admin/settings')
                    ->waitForText('系統設定')
                    ->click('.import-button')
                    ->waitFor('.import-modal')
                    ->assertSee('匯入設定')
                    ->attach('input[type="file"]', $tempFile)
                    ->waitFor('.import-preview')
                    ->assertSee('test.import.setting')
                    ->assertSee('Imported Value')
                    ->click('.import-execute-button')
                    ->waitFor('.success-message')
                    ->assertSee('匯入完成')
                    ->assertSee('test.import.setting')
                    ->screenshot('settings-import-functionality');

            // 清理臨時檔案
            unlink($tempFile);
        });
    }

    /**
     * 測試設定預覽功能
     * 
     * @test
     */
    public function test_settings_preview_functionality(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->adminUser)
                    ->visit('/admin/settings')
                    ->waitForText('系統設定')
                    ->click('[data-setting-key="appearance.primary_color"] .edit-button')
                    ->waitFor('.setting-edit-modal')
                    ->clear('input[name="value"]')
                    ->type('input[name="value"]', '#FF0000')
                    ->click('.preview-button')
                    ->waitFor('.preview-panel')
                    ->assertSee('預覽效果')
                    // 檢查預覽樣式是否套用
                    ->assertPresent('.preview-element[style*="color: #FF0000"]')
                    ->click('.apply-preview-button')
                    ->waitFor('.success-message')
                    ->assertSee('設定已套用')
                    ->screenshot('settings-preview-functionality');
        });
    }

    /**
     * 測試設定變更歷史
     * 
     * @test
     */
    public function test_settings_change_history(): void
    {
        $this->browse(function (Browser $browser) {
            // 先進行一些變更
            $browser->loginAs($this->adminUser)
                    ->visit('/admin/settings')
                    ->waitForText('系統設定')
                    ->click('[data-setting-key="app.name"] .edit-button')
                    ->waitFor('.setting-edit-modal')
                    ->clear('input[name="value"]')
                    ->type('input[name="value"]', 'History Test 1')
                    ->click('.save-button')
                    ->waitUntilMissing('.setting-edit-modal');

            // 再次變更
            $browser->click('[data-setting-key="app.name"] .edit-button')
                    ->waitFor('.setting-edit-modal')
                    ->clear('input[name="value"]')
                    ->type('input[name="value"]', 'History Test 2')
                    ->click('.save-button')
                    ->waitUntilMissing('.setting-edit-modal');

            // 檢視變更歷史
            $browser->click('[data-setting-key="app.name"] .history-button')
                    ->waitFor('.history-modal')
                    ->assertSee('設定變更歷史')
                    ->assertSee('History Test 2')
                    ->assertSee('History Test 1')
                    ->assertSee('Test Application') // 原始值
                    ->assertSee($this->adminUser->name)
                    ->screenshot('settings-change-history');
        });
    }

    /**
     * 測試不同使用者權限存取
     * 
     * @test
     */
    public function test_different_user_permission_access(): void
    {
        // 測試管理員存取
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->adminUser)
                    ->visit('/admin/settings')
                    ->waitForText('系統設定')
                    ->assertPresent('.edit-button')
                    ->assertPresent('.reset-button')
                    ->assertPresent('.backup-button')
                    ->assertPresent('.export-button')
                    ->assertPresent('.import-button')
                    ->screenshot('admin-user-access');
        });

        // 測試編輯者存取
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->editorUser)
                    ->visit('/admin/settings')
                    ->waitForText('系統設定')
                    ->assertPresent('.edit-button')
                    ->assertMissing('.backup-button') // 假設編輯者沒有備份權限
                    ->assertMissing('.import-button') // 假設編輯者沒有匯入權限
                    ->screenshot('editor-user-access');
        });

        // 測試一般使用者存取（應該被重導向或顯示權限不足）
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->regularUser)
                    ->visit('/admin/settings')
                    ->waitFor('body')
                    // 根據實際實作，可能會重導向到登入頁面或顯示權限不足訊息
                    ->assertPathIsNot('/admin/settings')
                    ->screenshot('regular-user-access-denied');
        });
    }

    /**
     * 測試響應式設計
     * 
     * @test
     */
    public function test_responsive_design(): void
    {
        $this->browse(function (Browser $browser) {
            // 測試桌面版本
            $browser->loginAs($this->adminUser)
                    ->resize(1200, 800)
                    ->visit('/admin/settings')
                    ->waitForText('系統設定')
                    ->assertPresent('.desktop-layout')
                    ->screenshot('desktop-responsive');

            // 測試平板版本
            $browser->resize(768, 1024)
                    ->refresh()
                    ->waitForText('系統設定')
                    ->assertPresent('.tablet-layout')
                    ->screenshot('tablet-responsive');

            // 測試手機版本
            $browser->resize(375, 667)
                    ->refresh()
                    ->waitForText('系統設定')
                    ->assertPresent('.mobile-layout')
                    ->assertPresent('.mobile-menu-button')
                    ->screenshot('mobile-responsive');
        });
    }

    /**
     * 測試鍵盤導航
     * 
     * @test
     */
    public function test_keyboard_navigation(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->adminUser)
                    ->visit('/admin/settings')
                    ->waitForText('系統設定')
                    ->keys('body', '{tab}') // Tab 到第一個可聚焦元素
                    ->keys('body', '{enter}') // 按 Enter
                    ->waitFor('.setting-edit-modal')
                    ->keys('body', '{escape}') // 按 Escape 關閉對話框
                    ->waitUntilMissing('.setting-edit-modal')
                    ->screenshot('keyboard-navigation');
        });
    }

    /**
     * 測試無障礙功能
     * 
     * @test
     */
    public function test_accessibility_features(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->adminUser)
                    ->visit('/admin/settings')
                    ->waitForText('系統設定')
                    // 檢查 ARIA 標籤
                    ->assertAttribute('[role="main"]', 'role', 'main')
                    ->assertAttribute('[aria-label]', 'aria-label')
                    // 檢查標題結構
                    ->assertPresent('h1')
                    ->assertPresent('h2')
                    // 檢查表單標籤
                    ->assertPresent('label[for]')
                    ->screenshot('accessibility-features');
        });
    }

    /**
     * 測試錯誤處理
     * 
     * @test
     */
    public function test_error_handling(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->adminUser)
                    ->visit('/admin/settings')
                    ->waitForText('系統設定')
                    // 模擬網路錯誤或伺服器錯誤
                    ->script('
                        // 攔截 AJAX 請求並返回錯誤
                        window.fetch = function() {
                            return Promise.reject(new Error("Network Error"));
                        };
                    ')
                    ->click('[data-setting-key="app.name"] .edit-button')
                    ->waitFor('.setting-edit-modal')
                    ->clear('input[name="value"]')
                    ->type('input[name="value"]', 'Error Test')
                    ->click('.save-button')
                    ->waitFor('.error-message')
                    ->assertSee('發生錯誤')
                    ->screenshot('error-handling');
        });
    }
}