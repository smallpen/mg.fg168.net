<?php

namespace Tests\Unit\Livewire\Admin\Settings;

use App\Livewire\Admin\Settings\SettingForm;
use App\Models\Setting;
use App\Models\User;
use App\Models\Role;
use App\Repositories\SettingsRepositoryInterface;
use App\Services\ConfigurationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;
use Mockery;

/**
 * SettingForm 元件單元測試
 */
class SettingFormTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected Role $adminRole;
    protected Setting $testSetting;

    protected function setUp(): void
    {
        parent::setUp();
        
        // 建立管理員角色和使用者
        $this->adminRole = Role::create([
            "name" => "admin",
            "display_name" => "系統管理員",
            "description" => "系統管理員角色",
            "is_system" => true,
        ]);

        $this->adminUser = User::factory()->create();
        $this->adminUser->assignRole("admin");

        // 建立測試設定
        $this->testSetting = Setting::create([
            "key" => "test.setting",
            "value" => "test_value",
            "category" => "basic",
            "type" => "text",
            "description" => "測試設定",
            "default_value" => "default_value",
            "is_encrypted" => false,
            "is_system" => false,
            "is_public" => true,
            "sort_order" => 1,
        ]);
    }

    /** @test */
    public function 元件可以正確初始化()
    {
        $component = Livewire::actingAs($this->adminUser)
            ->test(SettingForm::class);

        $component->assertSet("settingKey", "")
                  ->assertSet("value", null)
                  ->assertSet("originalValue", null)
                  ->assertSet("showForm", false)
                  ->assertSet("showPreview", false)
                  ->assertSet("saving", false)
                  ->assertSet("testingConnection", false);
    }

    /** @test */
    public function 可以開啟設定表單()
    {
        $component = Livewire::actingAs($this->adminUser)
            ->test(SettingForm::class);

        $component->call("openForm", $this->testSetting->key);

        $component->assertSet("settingKey", $this->testSetting->key)
                  ->assertSet("showForm", true);
    }

    /** @test */
    public function 可以取消編輯()
    {
        $component = Livewire::actingAs($this->adminUser)
            ->test(SettingForm::class);

        $component->call("openForm", $this->testSetting->key)
                  ->set("value", "changed_value")
                  ->call("cancel");

        $component->assertSet("showForm", false);
    }
}
