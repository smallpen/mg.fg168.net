<?php

namespace Tests\Feature\Livewire\Admin\Settings;

use App\Livewire\Admin\Settings\SettingForm;
use App\Models\Role;
use App\Models\Setting;
use App\Models\User;
use App\Repositories\SettingsRepositoryInterface;
use App\Services\ConfigurationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * 設定表單元件測試
 */
class SettingFormTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected Setting $testSetting;

    protected function setUp(): void
    {
        parent::setUp();

        // 建立管理員角色
        $adminRole = Role::create([
            'name' => 'admin',
            'display_name' => '系統管理員',
            'description' => '系統管理員角色',
            'is_system' => true,
        ]);

        // 建立管理員使用者
        $this->adminUser = User::factory()->create();
        $this->adminUser->assignRole('admin');

        // 建立測試設定
        $this->testSetting = Setting::create([
            'key' => 'test.setting',
            'value' => 'test_value',
            'category' => 'basic',
            'type' => 'text',
            'description' => '測試設定',
            'default_value' => 'default_value',
            'is_encrypted' => false,
            'is_system' => false,
            'is_public' => true,
            'sort_order' => 1,
        ]);
    }

    /** @test */
    public function 可以開啟設定表單()
    {
        $component = Livewire::actingAs($this->adminUser)
            ->test(SettingForm::class);

        $component->call('openForm', $this->testSetting->key);

        $component->assertSet('settingKey', $this->testSetting->key)
                  ->assertSet('showForm', true)
                  ->assertSet('value', $this->testSetting->value)
                  ->assertSet('originalValue', $this->testSetting->value);
    }

    /** @test */
    public function 可以更新文字設定()
    {
        $newValue = 'updated_value';

        $component = Livewire::actingAs($this->adminUser)
            ->test(SettingForm::class);

        $component->call('openForm', $this->testSetting->key)
                  ->set('value', $newValue)
                  ->call('save');

        $component->assertHasNoErrors()
                  ->assertDispatched('setting-updated');

        $this->testSetting->refresh();
        $this->assertEquals($newValue, $this->testSetting->value);
    }

    /** @test */
    public function 可以驗證設定值()
    {
        // 建立需要驗證的設定
        $numberSetting = Setting::create([
            'key' => 'test.number',
            'value' => 10,
            'category' => 'basic',
            'type' => 'number',
            'description' => '數字設定',
            'default_value' => 5,
            'options' => ['min' => 1, 'max' => 100],
            'is_encrypted' => false,
            'is_system' => false,
            'is_public' => true,
            'sort_order' => 1,
        ]);

        $component = Livewire::actingAs($this->adminUser)
            ->test(SettingForm::class);

        // 測試有效值
        $component->call('openForm', $numberSetting->key)
                  ->set('value', 50)
                  ->call('validateValue');

        $component->assertSet('validationErrors', []);

        // 測試無效值
        $component->set('value', 150)
                  ->call('validateValue');

        $component->assertSet('validationErrors', function ($errors) {
            return !empty($errors['value']);
        });
    }

    /** @test */
    public function 可以重設設定為預設值()
    {
        // 先更新設定值
        $this->testSetting->updateValue('changed_value');

        $component = Livewire::actingAs($this->adminUser)
            ->test(SettingForm::class);

        $component->call('openForm', $this->testSetting->key)
                  ->call('resetToDefault');

        $component->assertDispatched('setting-updated');

        $this->testSetting->refresh();
        $this->assertEquals($this->testSetting->default_value, $this->testSetting->value);
    }

    /** @test */
    public function 可以取消編輯()
    {
        $component = Livewire::actingAs($this->adminUser)
            ->test(SettingForm::class);

        $component->call('openForm', $this->testSetting->key)
                  ->set('value', 'changed_value')
                  ->call('cancel');

        $component->assertSet('value', $this->testSetting->value)
                  ->assertSet('showForm', false);
    }

    /** @test */
    public function 可以處理布林設定()
    {
        $booleanSetting = Setting::create([
            'key' => 'test.boolean',
            'value' => false,
            'category' => 'basic',
            'type' => 'boolean',
            'description' => '布林設定',
            'default_value' => true,
            'is_encrypted' => false,
            'is_system' => false,
            'is_public' => true,
            'sort_order' => 1,
        ]);

        $component = Livewire::actingAs($this->adminUser)
            ->test(SettingForm::class);

        $component->call('openForm', $booleanSetting->key)
                  ->set('value', true)
                  ->call('save');

        $component->assertHasNoErrors();

        $booleanSetting->refresh();
        $this->assertTrue($booleanSetting->value);
    }

    /** @test */
    public function 可以處理選擇設定()
    {
        $selectSetting = Setting::create([
            'key' => 'test.select',
            'value' => 'option1',
            'category' => 'basic',
            'type' => 'select',
            'description' => '選擇設定',
            'default_value' => 'option1',
            'options' => [
                'values' => [
                    'option1' => '選項 1',
                    'option2' => '選項 2',
                    'option3' => '選項 3',
                ]
            ],
            'is_encrypted' => false,
            'is_system' => false,
            'is_public' => true,
            'sort_order' => 1,
        ]);

        $component = Livewire::actingAs($this->adminUser)
            ->test(SettingForm::class);

        $component->call('openForm', $selectSetting->key)
                  ->set('value', 'option2')
                  ->call('save');

        $component->assertHasNoErrors();

        $selectSetting->refresh();
        $this->assertEquals('option2', $selectSetting->value);
    }

    /** @test */
    public function 可以處理檔案上傳()
    {
        Storage::fake('public');

        $fileSetting = Setting::create([
            'key' => 'test.file',
            'value' => '',
            'category' => 'appearance',
            'type' => 'image',
            'description' => '圖片設定',
            'default_value' => '',
            'is_encrypted' => false,
            'is_system' => false,
            'is_public' => true,
            'sort_order' => 1,
        ]);

        $file = UploadedFile::fake()->image('test.jpg');

        $component = Livewire::actingAs($this->adminUser)
            ->test(SettingForm::class);

        $component->call('openForm', $fileSetting->key)
                  ->set('uploadedFile', $file)
                  ->call('save');

        $component->assertHasNoErrors();

        $fileSetting->refresh();
        $this->assertStringContains('test.jpg', $fileSetting->value);
        Storage::disk('public')->assertExists('settings/' . $file->hashName());
    }

    /** @test */
    public function 可以檢查設定依賴關係()
    {
        // 建立依賴設定
        $dependentSetting = Setting::create([
            'key' => 'test.dependent',
            'value' => false,
            'category' => 'basic',
            'type' => 'boolean',
            'description' => '依賴設定',
            'default_value' => false,
            'is_encrypted' => false,
            'is_system' => false,
            'is_public' => true,
            'sort_order' => 1,
        ]);

        // 建立有依賴關係的設定
        $mainSetting = Setting::create([
            'key' => 'test.main',
            'value' => 'test',
            'category' => 'basic',
            'type' => 'text',
            'description' => '主要設定',
            'default_value' => 'test',
            'options' => [
                'dependencies' => [
                    'test.dependent' => true
                ]
            ],
            'is_encrypted' => false,
            'is_system' => false,
            'is_public' => true,
            'sort_order' => 2,
        ]);

        // 模擬配置服務返回依賴關係
        $configService = $this->mock(ConfigurationService::class);
        $configService->shouldReceive('getSettingConfig')
                     ->with($mainSetting->key)
                     ->andReturn([
                         'depends_on' => [
                             'test.dependent' => true
                         ]
                     ]);

        $component = Livewire::actingAs($this->adminUser)
            ->test(SettingForm::class);

        $component->call('openForm', $mainSetting->key);

        // 檢查是否有依賴警告
        $component->assertSet('dependencyWarnings', function ($warnings) {
            return !empty($warnings);
        });
    }

    /** @test */
    public function 可以即時驗證設定值()
    {
        $component = Livewire::actingAs($this->adminUser)
            ->test(SettingForm::class);

        $component->call('openForm', $this->testSetting->key)
                  ->set('value', '') // 設為空值觸發驗證
                  ->assertMethodWiredToForm('updatedValue');
    }

    /** @test */
    public function 可以關閉表單()
    {
        $component = Livewire::actingAs($this->adminUser)
            ->test(SettingForm::class);

        $component->call('openForm', $this->testSetting->key)
                  ->call('closeForm');

        $component->assertSet('showForm', false)
                  ->assertSet('settingKey', '')
                  ->assertSet('value', null);
    }

    /** @test */
    public function 可以檢測設定變更()
    {
        $component = Livewire::actingAs($this->adminUser)
            ->test(SettingForm::class);

        $component->call('openForm', $this->testSetting->key);

        // 初始狀態沒有變更
        $this->assertFalse($component->get('hasChanges'));

        // 修改值後有變更
        $component->set('value', 'new_value');
        $this->assertTrue($component->get('hasChanges'));

        // 恢復原值後沒有變更
        $component->set('value', $this->testSetting->value);
        $this->assertFalse($component->get('hasChanges'));
    }

    /** @test */
    public function 可以檢查是否可以重設()
    {
        $component = Livewire::actingAs($this->adminUser)
            ->test(SettingForm::class);

        // 設定值等於預設值時不能重設
        $this->testSetting->updateValue($this->testSetting->default_value);
        $component->call('openForm', $this->testSetting->key);
        $this->assertFalse($component->get('canReset'));

        // 設定值不等於預設值時可以重設
        $this->testSetting->updateValue('different_value');
        $component->call('openForm', $this->testSetting->key);
        $this->assertTrue($component->get('canReset'));
    }

    /** @test */
    public function 無權限使用者無法存取()
    {
        $regularUser = User::factory()->create();

        $component = Livewire::actingAs($regularUser)
            ->test(SettingForm::class);

        $component->call('openForm', $this->testSetting->key)
                  ->assertForbidden();
    }
}