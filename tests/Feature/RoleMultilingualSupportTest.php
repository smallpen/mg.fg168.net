<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\App;
use App\Helpers\RoleLocalizationHelper;
use App\Models\User;
use App\Models\Role;
use App\Models\Permission;

/**
 * 角色多語言支援測試
 */
class RoleMultilingualSupportTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected Role $adminRole;

    protected function setUp(): void
    {
        parent::setUp();

        // 建立測試使用者和角色
        $this->adminRole = Role::create([
            'name' => 'admin',
            'display_name' => 'Administrator',
            'description' => 'System administrator'
        ]);

        $this->adminUser = User::factory()->create([
            'username' => 'admin',
            'name' => 'Admin User',
            'email' => 'admin@example.com'
        ]);

        $this->adminUser->roles()->attach($this->adminRole);

        // 建立測試權限
        Permission::create([
            'name' => 'roles.view',
            'display_name' => 'View Roles',
            'description' => 'Can view role list',
            'module' => 'roles'
        ]);

        Permission::create([
            'name' => 'users.create',
            'display_name' => 'Create Users',
            'description' => 'Can create new users',
            'module' => 'users'
        ]);
    }

    /** @test */
    public function it_can_get_localized_permission_names_in_english()
    {
        App::setLocale('en');

        $displayName = RoleLocalizationHelper::getPermissionDisplayName('roles.view');
        $this->assertEquals('View Roles', $displayName);

        $displayName = RoleLocalizationHelper::getPermissionDisplayName('users.create');
        $this->assertEquals('Create Users', $displayName);
    }

    /** @test */
    public function it_can_get_localized_permission_names_in_chinese()
    {
        App::setLocale('zh_TW');

        $displayName = RoleLocalizationHelper::getPermissionDisplayName('roles.view');
        $this->assertEquals('檢視角色', $displayName);

        $displayName = RoleLocalizationHelper::getPermissionDisplayName('users.create');
        $this->assertEquals('建立使用者', $displayName);
    }

    /** @test */
    public function it_can_get_localized_role_names_in_english()
    {
        App::setLocale('en');

        $displayName = RoleLocalizationHelper::getRoleDisplayName('admin');
        $this->assertEquals('Administrator', $displayName);

        $displayName = RoleLocalizationHelper::getRoleDisplayName('super_admin');
        $this->assertEquals('Super Administrator', $displayName);
    }

    /** @test */
    public function it_can_get_localized_role_names_in_chinese()
    {
        App::setLocale('zh_TW');

        $displayName = RoleLocalizationHelper::getRoleDisplayName('admin');
        $this->assertEquals('管理員', $displayName);

        $displayName = RoleLocalizationHelper::getRoleDisplayName('super_admin');
        $this->assertEquals('超級管理員', $displayName);
    }

    /** @test */
    public function it_can_get_localized_module_names_in_english()
    {
        App::setLocale('en');

        $displayName = RoleLocalizationHelper::getModuleDisplayName('roles');
        $this->assertEquals('Role Management', $displayName);

        $displayName = RoleLocalizationHelper::getModuleDisplayName('users');
        $this->assertEquals('User Management', $displayName);
    }

    /** @test */
    public function it_can_get_localized_module_names_in_chinese()
    {
        App::setLocale('zh_TW');

        $displayName = RoleLocalizationHelper::getModuleDisplayName('roles');
        $this->assertEquals('角色管理', $displayName);

        $displayName = RoleLocalizationHelper::getModuleDisplayName('users');
        $this->assertEquals('使用者管理', $displayName);
    }

    /** @test */
    public function it_can_get_localized_error_messages_in_english()
    {
        App::setLocale('en');

        $errorMessage = RoleLocalizationHelper::getErrorMessage('crud.role_not_found');
        $this->assertEquals('The specified role was not found', $errorMessage);

        $errorMessage = RoleLocalizationHelper::getErrorMessage('validation.name_required');
        $this->assertEquals('Role name is required', $errorMessage);
    }

    /** @test */
    public function it_can_get_localized_error_messages_in_chinese()
    {
        App::setLocale('zh_TW');

        $errorMessage = RoleLocalizationHelper::getErrorMessage('crud.role_not_found');
        $this->assertEquals('找不到指定的角色', $errorMessage);

        $errorMessage = RoleLocalizationHelper::getErrorMessage('validation.name_required');
        $this->assertEquals('角色名稱為必填', $errorMessage);
    }

    /** @test */
    public function it_can_get_localized_success_messages_in_english()
    {
        App::setLocale('en');

        $successMessage = RoleLocalizationHelper::getSuccessMessage('created', ['name' => 'Test Role']);
        $this->assertEquals('Role "Test Role" has been successfully created', $successMessage);
    }

    /** @test */
    public function it_can_get_localized_success_messages_in_chinese()
    {
        App::setLocale('zh_TW');

        $successMessage = RoleLocalizationHelper::getSuccessMessage('created', ['name' => '測試角色']);
        $this->assertEquals('角色 "測試角色" 已成功建立', $successMessage);
    }

    /** @test */
    public function it_falls_back_to_formatted_names_when_translation_missing()
    {
        App::setLocale('en');

        // 測試不存在的權限名稱
        $displayName = RoleLocalizationHelper::getPermissionDisplayName('nonexistent.permission');
        $this->assertEquals('Nonexistent Permission', $displayName);

        // 測試不存在的角色名稱
        $displayName = RoleLocalizationHelper::getRoleDisplayName('nonexistent_role');
        $this->assertEquals('Nonexistent Role', $displayName);
    }

    /** @test */
    public function it_can_detect_chinese_locale()
    {
        App::setLocale('zh_TW');
        $this->assertTrue(RoleLocalizationHelper::isChineseLocale());

        App::setLocale('en');
        $this->assertFalse(RoleLocalizationHelper::isChineseLocale());
    }

    /** @test */
    public function it_can_format_dates_according_to_locale()
    {
        $date = now();

        App::setLocale('zh_TW');
        $formattedDate = RoleLocalizationHelper::formatDate($date);
        $this->assertStringContainsString('年', $formattedDate);
        $this->assertStringContainsString('月', $formattedDate);
        $this->assertStringContainsString('日', $formattedDate);

        App::setLocale('en');
        $formattedDate = RoleLocalizationHelper::formatDate($date);
        $this->assertStringNotContainsString('年', $formattedDate);
    }

    /** @test */
    public function api_returns_role_translations_in_english()
    {
        App::setLocale('en');

        $response = $this->get('/api/role-translations');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'permission_names',
            'permission_descriptions',
            'role_names',
            'role_descriptions',
            'modules',
            'messages',
            'errors'
        ]);

        $data = $response->json();
        $this->assertEquals('View Roles', $data['permission_names']['roles.view']);
        $this->assertEquals('Administrator', $data['role_names']['admin']);
    }

    /** @test */
    public function api_returns_role_translations_in_chinese()
    {
        App::setLocale('zh_TW');

        $response = $this->get('/api/role-translations');

        $response->assertStatus(200);
        $data = $response->json();
        $this->assertEquals('檢視角色', $data['permission_names']['roles.view']);
        $this->assertEquals('管理員', $data['role_names']['admin']);
    }

    /** @test */
    public function api_returns_specific_translation_type()
    {
        App::setLocale('en');

        $response = $this->get('/api/role-translations/permissions');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'names',
            'descriptions'
        ]);

        $data = $response->json();
        $this->assertEquals('View Roles', $data['names']['roles.view']);
    }

    /** @test */
    public function api_returns_404_for_invalid_translation_type()
    {
        $response = $this->get('/api/role-translations/invalid-type');

        $response->assertStatus(404);
        $response->assertJson(['error' => 'Translation type not found']);
    }

    /** @test */
    public function role_localization_middleware_sets_locale_from_url_parameter()
    {
        $this->actingAs($this->adminUser);

        // 測試中文語言參數
        $response = $this->get('/admin/roles?locale=zh_TW');
        $this->assertEquals('zh_TW', App::getLocale());

        // 測試英文語言參數
        $response = $this->get('/admin/roles?locale=en');
        $this->assertEquals('en', App::getLocale());
    }

    /** @test */
    public function role_localization_middleware_ignores_invalid_locale()
    {
        $this->actingAs($this->adminUser);
        $originalLocale = App::getLocale();

        $response = $this->get('/admin/roles?locale=invalid');
        $this->assertEquals($originalLocale, App::getLocale());
    }
}