<?php

namespace Tests\Browser;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

/**
 * 設定匯入匯出瀏覽器測試
 */
class SettingImportExportTest extends DuskTestCase
{
    use DatabaseMigrations;

    protected User $adminUser;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->adminUser = User::factory()->create([
            'username' => 'admin',
            'email' => 'admin@example.com',
            'is_active' => true,
        ]);
        
        // 建立測試設定
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
    }

    /**
     * 測試匯出功能
     */
    public function test_export_functionality()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->adminUser)
                    ->visit('/admin/settings')
                    ->waitFor('[wire\\:click="exportSettings"]')
                    ->click('[wire\\:click="exportSettings"]')
                    ->waitFor('.fixed.inset-0') // 等待對話框出現
                    ->assertSee('匯出設定')
                    ->assertSee('匯出選項')
                    ->assertSee('匯出統計');
        });
    }

    /**
     * 測試匯入功能
     */
    public function test_import_functionality()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->adminUser)
                    ->visit('/admin/settings')
                    ->waitFor('[wire\\:click="openImportDialog"]')
                    ->click('[wire\\:click="openImportDialog"]')
                    ->waitFor('.fixed.inset-0') // 等待對話框出現
                    ->assertSee('匯入設定')
                    ->assertSee('選擇設定檔案')
                    ->assertSee('匯入說明');
        });
    }

    /**
     * 測試設定列表顯示
     */
    public function test_settings_list_display()
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->adminUser)
                    ->visit('/admin/settings')
                    ->waitForText('系統設定')
                    ->assertSee('app.name')
                    ->assertSee('app.timezone')
                    ->assertSee('Test Application')
                    ->assertSee('Asia/Taipei');
        });
    }
}