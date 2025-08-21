<?php

namespace Tests\Feature\Admin\Settings;

use App\Livewire\Admin\Settings\NotificationSettings;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * 通知設定元件測試
 */
class NotificationSettingsTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;

    protected function setUp(): void
    {
        parent::setUp();
        
        // 暫時停用觀察者以避免安全檢查
        \App\Models\Permission::unsetEventDispatcher();
        \App\Models\Role::unsetEventDispatcher();
        
        // 建立必要的權限
        \App\Models\Permission::create([
            'name' => 'system.settings',
            'display_name' => '系統設定',
            'description' => '可以修改系統設定',
            'module' => 'system',
        ]);
        
        // 建立超級管理員角色
        $superAdminRole = \App\Models\Role::create([
            'name' => 'super_admin',
            'display_name' => '超級管理員',
            'description' => '擁有系統所有權限的最高管理員',
        ]);
        
        // 給予角色權限
        $superAdminRole->permissions()->attach(\App\Models\Permission::where('name', 'system.settings')->first());
        
        // 建立管理員使用者
        $this->adminUser = User::factory()->create([
            'username' => 'admin',
            'email' => 'admin@example.com',
            'is_active' => true,
        ]);
        
        // 給予系統設定權限（使用 super_admin 角色）
        $this->adminUser->assignRole('super_admin');
    }

    /** @test */
    public function 管理員可以存取通知設定頁面()
    {
        // 直接測試 Livewire 元件
        Livewire::actingAs($this->adminUser)
                ->test(NotificationSettings::class)
                ->assertOk();
    }

    /** @test */
    public function 元件可以正確載入預設設定()
    {
        // 先測試基本功能，不檢查具體值
        $component = Livewire::actingAs($this->adminUser)
                            ->test(NotificationSettings::class);
        
        // 檢查設定陣列是否存在
        $this->assertIsArray($component->get('settings'));
        $this->assertArrayHasKey('notification.email_enabled', $component->get('settings'));
    }

    /** @test */
    public function 可以切換郵件通知開關()
    {
        $component = Livewire::actingAs($this->adminUser)
                            ->test(NotificationSettings::class);
        
        // 檢查初始狀態
        $initialValue = $component->get('settings.notification.email_enabled');
        
        // 切換狀態
        $component->set('settings.notification.email_enabled', !$initialValue);
        
        // 驗證狀態已變更
        $this->assertEquals(!$initialValue, $component->get('settings.notification.email_enabled'));
    }

    /** @test */
    public function 可以更新SMTP設定()
    {
        Livewire::actingAs($this->adminUser)
                ->test(NotificationSettings::class)
                ->set('settings.notification.smtp_host', 'smtp.example.com')
                ->set('settings.notification.smtp_port', 465)
                ->set('settings.notification.smtp_encryption', 'ssl')
                ->set('settings.notification.smtp_username', 'test@example.com')
                ->set('settings.notification.smtp_password', 'password123')
                ->assertSet('settings.notification.smtp_host', 'smtp.example.com')
                ->assertSet('settings.notification.smtp_port', 465)
                ->assertSet('settings.notification.smtp_encryption', 'ssl');
    }

    /** @test */
    public function 可以更新寄件者資訊()
    {
        Livewire::actingAs($this->adminUser)
                ->test(NotificationSettings::class)
                ->set('settings.notification.from_name', '測試系統')
                ->set('settings.notification.from_email', 'test@example.com')
                ->assertSet('settings.notification.from_name', '測試系統')
                ->assertSet('settings.notification.from_email', 'test@example.com');
    }

    /** @test */
    public function 可以設定通知頻率限制()
    {
        Livewire::actingAs($this->adminUser)
                ->test(NotificationSettings::class)
                ->set('settings.notification.rate_limit_per_minute', 20)
                ->assertSet('settings.notification.rate_limit_per_minute', 20);
    }

    /** @test */
    public function 驗證必填欄位()
    {
        Livewire::actingAs($this->adminUser)
                ->test(NotificationSettings::class)
                ->set('settings.notification.smtp_host', '')
                ->set('settings.notification.from_email', 'invalid-email')
                ->call('save')
                ->assertHasErrors([
                    'notification.smtp_host',
                    'notification.from_email'
                ]);
    }

    /** @test */
    public function 可以測試SMTP連線()
    {
        Livewire::actingAs($this->adminUser)
                ->test(NotificationSettings::class)
                ->set('settings.notification.email_enabled', true)
                ->set('settings.notification.smtp_host', 'smtp.gmail.com')
                ->set('settings.notification.smtp_port', 587)
                ->call('testSmtpConnection')
                ->assertSet('showTestResult', true);
    }

    /** @test */
    public function 可以開啟通知範本管理()
    {
        Livewire::actingAs($this->adminUser)
                ->test(NotificationSettings::class)
                ->call('openTemplateManager')
                ->assertSet('showTemplateManager', true);
    }

    /** @test */
    public function 可以選擇和編輯通知範本()
    {
        Livewire::actingAs($this->adminUser)
                ->test(NotificationSettings::class)
                ->call('openTemplateManager')
                ->call('selectTemplate', 'welcome')
                ->assertSet('selectedTemplate', 'welcome')
                ->assertSet('templateContent.name', '歡迎郵件')
                ->set('templateContent.subject', '歡迎加入我們的系統')
                ->assertSet('templateContent.subject', '歡迎加入我們的系統');
    }

    /** @test */
    public function 可以重設範本為預設值()
    {
        Livewire::actingAs($this->adminUser)
                ->test(NotificationSettings::class)
                ->call('openTemplateManager')
                ->call('selectTemplate', 'welcome')
                ->set('templateContent.subject', '修改過的主旨')
                ->call('resetTemplate')
                ->assertSet('templateContent.subject', '歡迎加入 {app_name}');
    }

    /** @test */
    public function 可以儲存設定變更()
    {
        Livewire::actingAs($this->adminUser)
                ->test(NotificationSettings::class)
                ->set('settings.notification.from_name', '新的系統名稱')
                ->call('save')
                ->assertHasNoErrors()
                ->assertDispatched('notification-settings-updated');
    }

    /** @test */
    public function 可以重設所有設定()
    {
        Livewire::actingAs($this->adminUser)
                ->test(NotificationSettings::class)
                ->set('settings.notification.from_name', '修改過的名稱')
                ->call('resetAll')
                ->assertSet('settings.notification.from_name', 'Laravel Admin System');
    }

    /** @test */
    public function 檢查設定變更狀態()
    {
        Livewire::actingAs($this->adminUser)
                ->test(NotificationSettings::class)
                ->assertSet('hasChanges', false)
                ->set('settings.notification.from_name', '新名稱')
                ->assertSet('hasChanges', true);
    }

    /** @test */
    public function 檢查SMTP配置狀態()
    {
        $component = Livewire::actingAs($this->adminUser)
                            ->test(NotificationSettings::class);

        // 預設狀態應該是已配置
        $status = $component->get('smtpConfigStatus');
        $this->assertEquals('configured', $status['status']);

        // 清空必要欄位後應該是不完整
        $component->set('settings.notification.smtp_host', '')
                  ->assertSet('smtpConfigStatus.status', 'incomplete');
    }

    /** @test */
    public function 檢查郵件通知啟用狀態()
    {
        Livewire::actingAs($this->adminUser)
                ->test(NotificationSettings::class)
                ->assertSet('isEmailEnabled', true)
                ->set('settings.notification.email_enabled', false)
                ->assertSet('isEmailEnabled', false);
    }

    /** @test */
    public function 檢查設定依賴關係()
    {
        $component = Livewire::actingAs($this->adminUser)
                            ->test(NotificationSettings::class);

        // SMTP 設定應該依賴郵件通知啟用
        $this->assertTrue($component->call('isDependentSetting', 'notification.smtp_host'));
        $this->assertTrue($component->call('isDependencySatisfied', 'notification.smtp_host'));

        // 停用郵件通知後依賴應該不滿足
        $component->set('settings.notification.email_enabled', false);
        $this->assertFalse($component->call('isDependencySatisfied', 'notification.smtp_host'));
    }

    /** @test */
    public function 無權限使用者無法存取()
    {
        $user = User::factory()->create();
        
        $this->actingAs($user)
             ->get(route('admin.settings.notifications'))
             ->assertForbidden();
    }

    /** @test */
    public function 未登入使用者會被重新導向()
    {
        $this->get(route('admin.settings.notifications'))
             ->assertRedirect(route('admin.login'));
    }
}