<?php

namespace Tests\Unit;

use App\Models\Permission;
use App\Models\PermissionTemplate;
use App\Models\User;
use App\Services\PermissionTemplateService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * 權限模板功能測試
 */
class PermissionTemplateTest extends TestCase
{
    use RefreshDatabase;

    protected PermissionTemplateService $templateService;
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->templateService = new PermissionTemplateService();
        
        // 建立測試使用者
        $this->user = User::factory()->create();
        $this->actingAs($this->user);
    }

    /**
     * 測試建立系統模板
     */
    public function test_can_create_system_templates(): void
    {
        PermissionTemplate::createSystemTemplates();

        $this->assertDatabaseHas('permission_templates', [
            'name' => 'crud_basic',
            'is_system_template' => true,
        ]);

        $this->assertDatabaseHas('permission_templates', [
            'name' => 'user_management',
            'is_system_template' => true,
        ]);

        // 檢查模板數量
        $this->assertEquals(4, PermissionTemplate::where('is_system_template', true)->count());
    }

    /**
     * 測試應用模板建立權限
     */
    public function test_can_apply_template_to_create_permissions(): void
    {
        // 建立測試模板
        $template = PermissionTemplate::create([
            'name' => 'test_template',
            'display_name' => '測試模板',
            'description' => '測試用模板',
            'module' => 'test',
            'permissions' => [
                ['action' => 'view', 'display_name' => '檢視', 'type' => 'view'],
                ['action' => 'create', 'display_name' => '建立', 'type' => 'create'],
            ],
            'is_system_template' => false,
            'created_by' => $this->user->id,
        ]);

        // 應用模板
        $results = $this->templateService->applyTemplate($template, 'products');

        // 驗證結果
        $this->assertCount(2, $results['created']);
        $this->assertCount(0, $results['skipped']);
        $this->assertCount(0, $results['errors']);

        // 驗證權限已建立
        $this->assertDatabaseHas('permissions', [
            'name' => 'products.view',
            'display_name' => '檢視',
            'module' => 'products',
            'type' => 'view',
        ]);

        $this->assertDatabaseHas('permissions', [
            'name' => 'products.create',
            'display_name' => '建立',
            'module' => 'products',
            'type' => 'create',
        ]);
    }

    /**
     * 測試應用模板時跳過已存在的權限
     */
    public function test_skips_existing_permissions_when_applying_template(): void
    {
        // 先建立一個權限
        Permission::create([
            'name' => 'products.view',
            'display_name' => '檢視產品',
            'module' => 'products',
            'type' => 'view',
        ]);

        // 建立測試模板
        $template = PermissionTemplate::create([
            'name' => 'test_template',
            'display_name' => '測試模板',
            'module' => 'test',
            'permissions' => [
                ['action' => 'view', 'display_name' => '檢視', 'type' => 'view'],
                ['action' => 'create', 'display_name' => '建立', 'type' => 'create'],
            ],
            'created_by' => $this->user->id,
        ]);

        // 應用模板
        $results = $this->templateService->applyTemplate($template, 'products');

        // 驗證結果
        $this->assertCount(1, $results['created']); // 只建立 create 權限
        $this->assertCount(1, $results['skipped']); // 跳過 view 權限
        $this->assertEquals('products.view', $results['skipped'][0]['name']);
    }

    /**
     * 測試從現有權限建立模板
     */
    public function test_can_create_template_from_existing_permissions(): void
    {
        // 建立測試權限
        $permissions = collect([
            Permission::create([
                'name' => 'orders.view',
                'display_name' => '檢視訂單',
                'description' => '檢視訂單列表',
                'module' => 'orders',
                'type' => 'view',
            ]),
            Permission::create([
                'name' => 'orders.create',
                'display_name' => '建立訂單',
                'description' => '建立新訂單',
                'module' => 'orders',
                'type' => 'create',
            ]),
        ]);

        // 從權限建立模板
        $template = $this->templateService->createTemplateFromPermissions($permissions, [
            'name' => 'order_management',
            'display_name' => '訂單管理',
            'description' => '訂單管理相關權限',
            'module' => 'orders',
            'created_by' => $this->user->id,
        ]);

        // 驗證模板
        $this->assertDatabaseHas('permission_templates', [
            'name' => 'order_management',
            'display_name' => '訂單管理',
            'module' => 'orders',
            'created_by' => $this->user->id,
        ]);

        // 驗證權限配置
        $this->assertCount(2, $template->permissions);
        $this->assertEquals('view', $template->permissions[0]['action']);
        $this->assertEquals('create', $template->permissions[1]['action']);
    }

    /**
     * 測試取得模板預覽
     */
    public function test_can_get_template_preview(): void
    {
        // 建立測試模板
        $template = PermissionTemplate::create([
            'name' => 'test_template',
            'display_name' => '測試模板',
            'module' => 'test',
            'permissions' => [
                ['action' => 'view', 'display_name' => '檢視', 'type' => 'view'],
                ['action' => 'create', 'display_name' => '建立', 'type' => 'create'],
            ],
            'created_by' => $this->user->id,
        ]);

        // 建立一個已存在的權限
        Permission::create([
            'name' => 'products.view',
            'display_name' => '檢視產品',
            'module' => 'products',
            'type' => 'view',
        ]);

        // 取得預覽
        $preview = $this->templateService->getTemplatePreview($template, 'products');

        // 驗證預覽結果
        $this->assertCount(2, $preview);
        
        // 第一個權限已存在
        $this->assertEquals('products.view', $preview[0]['name']);
        $this->assertTrue($preview[0]['exists']);
        $this->assertFalse($preview[0]['will_create']);
        
        // 第二個權限不存在
        $this->assertEquals('products.create', $preview[1]['name']);
        $this->assertFalse($preview[1]['exists']);
        $this->assertTrue($preview[1]['will_create']);
    }

    /**
     * 測試匯出模板
     */
    public function test_can_export_template(): void
    {
        // 建立測試模板
        $template = PermissionTemplate::create([
            'name' => 'test_template',
            'display_name' => '測試模板',
            'description' => '測試用模板',
            'module' => 'test',
            'permissions' => [
                ['action' => 'view', 'display_name' => '檢視', 'type' => 'view'],
            ],
            'created_by' => $this->user->id,
        ]);

        // 匯出模板
        $exportData = $this->templateService->exportTemplate($template);

        // 驗證匯出資料
        $this->assertEquals('test_template', $exportData['name']);
        $this->assertEquals('測試模板', $exportData['display_name']);
        $this->assertEquals('測試用模板', $exportData['description']);
        $this->assertEquals('test', $exportData['module']);
        $this->assertCount(1, $exportData['permissions']);
        $this->assertEquals('1.0', $exportData['version']);
        $this->assertArrayHasKey('exported_at', $exportData);
    }

    /**
     * 測試匯入模板
     */
    public function test_can_import_template(): void
    {
        $templateData = [
            'name' => 'imported_template',
            'display_name' => '匯入的模板',
            'description' => '從檔案匯入的模板',
            'module' => 'imported',
            'permissions' => [
                ['action' => 'view', 'display_name' => '檢視', 'type' => 'view'],
                ['action' => 'edit', 'display_name' => '編輯', 'type' => 'edit'],
            ],
            'created_by' => $this->user->id,
        ];

        // 匯入模板
        $template = $this->templateService->importTemplate($templateData);

        // 驗證模板
        $this->assertDatabaseHas('permission_templates', [
            'name' => 'imported_template',
            'display_name' => '匯入的模板',
            'module' => 'imported',
            'is_system_template' => false,
            'created_by' => $this->user->id,
        ]);

        $this->assertCount(2, $template->permissions);
    }

    /**
     * 測試模板驗證
     */
    public function test_validates_template_data(): void
    {
        $invalidData = [
            'name' => '', // 空名稱
            'display_name' => '',
            'module' => '',
            'permissions' => [], // 空權限陣列
        ];

        $errors = $this->templateService->validateTemplateData($invalidData);

        $this->assertArrayHasKey('name', $errors);
        $this->assertArrayHasKey('display_name', $errors);
        $this->assertArrayHasKey('module', $errors);
        $this->assertArrayHasKey('permissions', $errors);
    }
}
