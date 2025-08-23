<?php

namespace Tests\Feature\Livewire\Admin\Settings;

use App\Livewire\Admin\Settings\CategorySettingsForm;
use App\Models\Role;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * 分類設定表單元件測試
 */
class CategorySettingsFormTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected Setting $testSetting;

    protected function setUp(): void
    {
        parent::setUp();

        // 建立管理員角色和使用者
        $adminRole = Role::create([
            'name' => 'admin',
            'display_name' => '系統管理員',
            'description' => '系統管理員角色',
        ]);

        $this->adminUser = User::factory()->create([
            'username' => 'admin',
            'name' => '系統管理員',
            'email' => 'admin@example.com',
            'is_active' => true,
        ]);

        $this->adminUser->roles()->attach($adminRole);

        // 建立測試設定
        $this->testSetting = Setting::create([
            'key' => 'app.name',
            'value' => 'Test Application',
            'category' => 'basic',
            'type' => 'text',
            'description' => '應用程式名稱',
            'is_system' => false,
        ]);
    }

    /** @test */
    public function 可以載入分類設定表單()
    {
        $component = Livewire::actingAs($this->adminUser)
            ->test(CategorySettingsForm::class, ['category' => 'basic']);

        $component->assertStatus(200)
            ->assertSee('基本設定')
            ->assertSee('應用程式名稱');
    }

    /** @test */
    public function 可以載入設定值()
    {
        $component = Livewire::actingAs($this->adminUser)
            ->test(CategorySettingsForm::class, ['category' => 'basic']);

        $component->assertSet('values.app.name', 'Test Application');
    }

    /** @test */
    public function 可以更新設定值()
    {
        $component = Livewire::actingAs($this->adminUser)
            ->test(CategorySettingsForm::class, ['category' => 'basic']);

        $component->set('values.app.name', 'Updated Application')
            ->call('save');

        $this->assertDatabaseHas('settings', [
            'key' => 'app.name',
            'value' => json_encode('Updated Application'),
        ]);
    }

    /** @test */
    public function 可以重設設定為預設值()
    {
        // 更新設定值
        $this->testSetting->update(['value' => 'Changed Value']);

        $component = Livewire::actingAs($this->adminUser)
            ->test(CategorySettingsForm::class, ['category' => 'basic']);

        $component->call('resetSetting', 'app.name');

        // 檢查設定是否重設為預設值
        $this->testSetting->refresh();
        $this->assertEquals('Laravel Admin System', $this->testSetting->value);
    }

    /** @test */
    public function 可以檢測表單變更()
    {
        $component = Livewire::actingAs($this->adminUser)
            ->test(CategorySettingsForm::class, ['category' => 'basic']);

        // 初始狀態沒有變更
        $this->assertFalse($component->get('hasChanges'));

        // 修改值後有變更
        $component->set('values.app.name', 'Changed Value');
        $this->assertTrue($component->get('hasChanges'));
    }

    /** @test */
    public function 可以驗證必填欄位()
    {
        // 建立必填設定
        Setting::create([
            'key' => 'required.field',
            'value' => 'test',
            'category' => 'basic',
            'type' => 'text',
            'description' => '必填欄位',
            'validation_rules' => ['required'],
            'is_system' => false,
        ]);

        $component = Livewire::actingAs($this->adminUser)
            ->test(CategorySettingsForm::class, ['category' => 'basic']);

        // 設定空值
        $component->set('values.required.field', '')
            ->call('save');

        // 應該有驗證錯誤
        $this->assertNotEmpty($component->get('validationErrors'));
    }

    /** @test */
    public function 無權限使用者無法存取()
    {
        $regularUser = User::factory()->create([
            'username' => 'user',
            'name' => '一般使用者',
            'email' => 'user@example.com',
            'is_active' => true,
        ]);

        $component = Livewire::actingAs($regularUser)
            ->test(CategorySettingsForm::class, ['category' => 'basic']);

        $component->assertForbidden();
    }

    /** @test */
    public function 可以處理空分類()
    {
        $component = Livewire::actingAs($this->adminUser)
            ->test(CategorySettingsForm::class, ['category' => 'nonexistent']);

        $component->assertStatus(200)
            ->assertSee('此分類暫無設定項目');
    }

    /** @test */
    public function 可以重設整個表單()
    {
        $component = Livewire::actingAs($this->adminUser)
            ->test(CategorySettingsForm::class, ['category' => 'basic']);

        // 修改值
        $component->set('values.app.name', 'Changed Value');
        $this->assertTrue($component->get('hasChanges'));

        // 重設表單
        $component->call('resetForm');
        $this->assertFalse($component->get('hasChanges'));
        $component->assertSet('values.app.name', 'Test Application');
    }
}