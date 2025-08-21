<?php

namespace Tests\Unit\Livewire\Admin\Permissions;

use App\Livewire\Admin\Permissions\PermissionImportExport;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Services\PermissionImportExportService;
use App\Services\AuditLogService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * PermissionImportExport 元件單元測試
 */
class PermissionImportExportTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected Role $adminRole;
    protected array $testPermissions;

    protected function setUp(): void
    {
        parent::setUp();
        
        // 停用權限安全觀察者以避免測試中的安全檢查
        \App\Models\Permission::unsetEventDispatcher();
        
        // 建立管理員角色和使用者
        $this->adminRole = Role::create([
            'name' => 'admin',
            'display_name' => '管理員',
            'is_system_role' => true,
            'is_active' => true,
        ]);

        $this->adminUser = User::factory()->create(['is_active' => true]);
        $this->adminUser->roles()->attach($this->adminRole);
        
        // 建立權限管理相關權限
        $permissions = ['permissions.view', 'permissions.export', 'permissions.import'];
        foreach ($permissions as $permission) {
            Permission::create([
                'name' => $permission,
                'display_name' => $permission,
                'module' => 'permissions',
                'type' => 'view'
            ]);
        }
        
        $this->adminRole->permissions()->attach(
            Permission::whereIn('name', $permissions)->pluck('id')
        );

        // 建立測試權限
        $this->createTestPermissions();

        // 設定假的檔案系統
        Storage::fake('local');
    }

    private function createTestPermissions(): void
    {
        $this->testPermissions = [
            'users.view' => Permission::create([
                'name' => 'users.view',
                'display_name' => '檢視使用者',
                'description' => '檢視使用者列表',
                'module' => 'users',
                'type' => 'view',
            ]),
            'users.edit' => Permission::create([
                'name' => 'users.edit',
                'display_name' => '編輯使用者',
                'description' => '編輯使用者資料',
                'module' => 'users',
                'type' => 'edit',
            ]),
            'roles.view' => Permission::create([
                'name' => 'roles.view',
                'display_name' => '檢視角色',
                'description' => '檢視角色列表',
                'module' => 'roles',
                'type' => 'view',
            ]),
        ];
    }

    /** @test */
    public function 元件可以正確初始化()
    {
        $this->actingAs($this->adminUser);

        $component = Livewire::test(PermissionImportExport::class);

        $component->assertSet('exportFilters', [
                     'modules' => [],
                     'types' => [],
                     'usage_status' => 'all',
                     'permission_ids' => [],
                 ])
                 ->assertSet('exportInProgress', false)
                 ->assertSet('importFile', null)
                 ->assertSet('importOptions', [
                     'conflict_resolution' => 'skip',
                     'validate_dependencies' => true,
                     'create_missing_dependencies' => false,
                     'dry_run' => false,
                 ])
                 ->assertSet('importInProgress', false)
                 ->assertSet('importResults', [])
                 ->assertSet('importPreview', [])
                 ->assertSet('showImportPreview', false)
                 ->assertSet('showImportResults', false)
                 ->assertSet('conflicts', [])
                 ->assertSet('showConflictResolution', false)
                 ->assertSet('conflictResolutions', []);
    }

    /** @test */
    public function 沒有權限的使用者無法存取元件()
    {
        $user = User::factory()->create(['is_active' => true]);
        $this->actingAs($user);

        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        
        Livewire::test(PermissionImportExport::class);
    }

    /** @test */
    public function 可以設定匯出篩選條件()
    {
        $this->actingAs($this->adminUser);

        $component = Livewire::test(PermissionImportExport::class);

        // 設定模組篩選
        $component->set('exportFilters.modules', ['users', 'roles'])
                 ->assertSet('exportFilters.modules', ['users', 'roles']);

        // 設定類型篩選
        $component->set('exportFilters.types', ['view', 'edit'])
                 ->assertSet('exportFilters.types', ['view', 'edit']);

        // 設定使用狀態篩選
        $component->set('exportFilters.usage_status', 'used')
                 ->assertSet('exportFilters.usage_status', 'used');

        // 設定特定權限 ID
        $permissionIds = [1, 2, 3];
        $component->set('exportFilters.permission_ids', $permissionIds)
                 ->assertSet('exportFilters.permission_ids', $permissionIds);
    }

    /** @test */
    public function 可以執行權限匯出()
    {
        $this->actingAs($this->adminUser);

        $component = Livewire::test(PermissionImportExport::class);

        $component->call('exportPermissions');

        // 檢查匯出狀態
        $component->assertSet('exportInProgress', true);

        // 檢查是否發送了匯出事件
        $component->assertDispatched('export-started');
    }

    /** @test */
    public function 沒有匯出權限的使用者無法匯出()
    {
        // 建立只有檢視權限的使用者
        $user = User::factory()->create(['is_active' => true]);
        $role = Role::create([
            'name' => 'viewer',
            'display_name' => '檢視者',
            'is_active' => true,
        ]);
        
        $viewPermission = Permission::where('name', 'permissions.view')->first();
        $role->permissions()->attach($viewPermission);
        $user->roles()->attach($role);

        $this->actingAs($user);

        $component = Livewire::test(PermissionImportExport::class);

        $component->call('exportPermissions')
                 ->assertDispatched('show-toast', [
                     'type' => 'error',
                     'message' => '您沒有匯出權限的權限'
                 ]);
    }

    /** @test */
    public function 可以上傳匯入檔案()
    {
        $this->actingAs($this->adminUser);

        // 建立測試 JSON 檔案
        $testData = [
            'permissions' => [
                [
                    'name' => 'test.import',
                    'display_name' => '測試匯入權限',
                    'description' => '這是一個測試匯入的權限',
                    'module' => 'test',
                    'type' => 'view',
                ]
            ]
        ];

        $file = UploadedFile::fake()->createWithContent(
            'permissions.json',
            json_encode($testData)
        );

        $component = Livewire::test(PermissionImportExport::class);

        $component->set('importFile', $file)
                 ->call('previewImport');

        // 檢查是否顯示預覽
        $component->assertSet('showImportPreview', true);

        $importPreview = $component->get('importPreview');
        $this->assertNotEmpty($importPreview);
    }

    /** @test */
    public function 無效的匯入檔案顯示錯誤()
    {
        $this->actingAs($this->adminUser);

        // 建立無效的 JSON 檔案
        $file = UploadedFile::fake()->createWithContent(
            'invalid.json',
            'invalid json content'
        );

        $component = Livewire::test(PermissionImportExport::class);

        $component->set('importFile', $file)
                 ->call('previewImport')
                 ->assertDispatched('show-toast', [
                     'type' => 'error'
                 ]);
    }

    /** @test */
    public function 可以設定匯入選項()
    {
        $this->actingAs($this->adminUser);

        $component = Livewire::test(PermissionImportExport::class);

        // 設定衝突解決方式
        $component->set('importOptions.conflict_resolution', 'overwrite')
                 ->assertSet('importOptions.conflict_resolution', 'overwrite');

        // 設定依賴驗證
        $component->set('importOptions.validate_dependencies', false)
                 ->assertSet('importOptions.validate_dependencies', false);

        // 設定建立缺失依賴
        $component->set('importOptions.create_missing_dependencies', true)
                 ->assertSet('importOptions.create_missing_dependencies', true);

        // 設定試運行模式
        $component->set('importOptions.dry_run', true)
                 ->assertSet('importOptions.dry_run', true);
    }

    /** @test */
    public function 可以執行權限匯入()
    {
        $this->actingAs($this->adminUser);

        // 建立測試 JSON 檔案
        $testData = [
            'permissions' => [
                [
                    'name' => 'test.import',
                    'display_name' => '測試匯入權限',
                    'description' => '這是一個測試匯入的權限',
                    'module' => 'test',
                    'type' => 'view',
                ]
            ]
        ];

        $file = UploadedFile::fake()->createWithContent(
            'permissions.json',
            json_encode($testData)
        );

        $component = Livewire::test(PermissionImportExport::class);

        $component->set('importFile', $file)
                 ->call('previewImport')
                 ->call('executeImport');

        // 檢查匯入狀態
        $component->assertSet('importInProgress', true);

        // 檢查是否顯示結果
        $component->assertSet('showImportResults', true);

        $importResults = $component->get('importResults');
        $this->assertNotEmpty($importResults);
    }

    /** @test */
    public function 沒有匯入權限的使用者無法匯入()
    {
        // 建立只有檢視權限的使用者
        $user = User::factory()->create(['is_active' => true]);
        $role = Role::create([
            'name' => 'viewer',
            'display_name' => '檢視者',
            'is_active' => true,
        ]);
        
        $viewPermission = Permission::where('name', 'permissions.view')->first();
        $role->permissions()->attach($viewPermission);
        $user->roles()->attach($role);

        $this->actingAs($user);

        $component = Livewire::test(PermissionImportExport::class);

        $component->call('executeImport')
                 ->assertDispatched('show-toast', [
                     'type' => 'error',
                     'message' => '您沒有匯入權限的權限'
                 ]);
    }

    /** @test */
    public function 匯入衝突時顯示解決選項()
    {
        $this->actingAs($this->adminUser);

        // 建立與現有權限衝突的測試資料
        $testData = [
            'permissions' => [
                [
                    'name' => 'users.view', // 與現有權限衝突
                    'display_name' => '檢視使用者（更新版）',
                    'description' => '更新的描述',
                    'module' => 'users',
                    'type' => 'view',
                ]
            ]
        ];

        $file = UploadedFile::fake()->createWithContent(
            'permissions.json',
            json_encode($testData)
        );

        $component = Livewire::test(PermissionImportExport::class);

        $component->set('importFile', $file)
                 ->call('previewImport');

        // 檢查是否檢測到衝突
        $conflicts = $component->get('conflicts');
        $this->assertNotEmpty($conflicts);

        // 檢查是否顯示衝突解決介面
        $component->assertSet('showConflictResolution', true);
    }

    /** @test */
    public function 可以解決匯入衝突()
    {
        $this->actingAs($this->adminUser);

        $component = Livewire::test(PermissionImportExport::class);

        // 模擬衝突情況
        $conflicts = [
            'users.view' => [
                'existing' => $this->testPermissions['users.view']->toArray(),
                'new' => [
                    'name' => 'users.view',
                    'display_name' => '檢視使用者（更新版）',
                    'description' => '更新的描述',
                    'module' => 'users',
                    'type' => 'view',
                ]
            ]
        ];

        $component->set('conflicts', $conflicts)
                 ->set('showConflictResolution', true);

        // 設定衝突解決方案
        $component->set('conflictResolutions.users.view', 'overwrite')
                 ->call('resolveConflicts');

        // 檢查衝突是否被解決
        $component->assertSet('showConflictResolution', false);
    }

    /** @test */
    public function 可以取消匯入操作()
    {
        $this->actingAs($this->adminUser);

        $component = Livewire::test(PermissionImportExport::class);

        // 設定一些匯入狀態
        $component->set('showImportPreview', true)
                 ->set('importPreview', ['some' => 'data'])
                 ->call('cancelImport')
                 ->assertSet('showImportPreview', false)
                 ->assertSet('importPreview', [])
                 ->assertSet('importFile', null);

        $component->assertDispatched('import-cancelled');
    }

    /** @test */
    public function 可以重置匯出篩選()
    {
        $this->actingAs($this->adminUser);

        $component = Livewire::test(PermissionImportExport::class);

        // 設定一些篩選條件
        $component->set('exportFilters.modules', ['users', 'roles'])
                 ->set('exportFilters.types', ['view', 'edit'])
                 ->set('exportFilters.usage_status', 'used')
                 ->set('exportFilters.permission_ids', [1, 2, 3]);

        // 重置篩選
        $component->call('resetExportFilters')
                 ->assertSet('exportFilters', [
                     'modules' => [],
                     'types' => [],
                     'usage_status' => 'all',
                     'permission_ids' => [],
                 ]);
    }

    /** @test */
    public function 可以重置匯入選項()
    {
        $this->actingAs($this->adminUser);

        $component = Livewire::test(PermissionImportExport::class);

        // 修改一些匯入選項
        $component->set('importOptions.conflict_resolution', 'overwrite')
                 ->set('importOptions.validate_dependencies', false)
                 ->set('importOptions.create_missing_dependencies', true)
                 ->set('importOptions.dry_run', true);

        // 重置選項
        $component->call('resetImportOptions')
                 ->assertSet('importOptions', [
                     'conflict_resolution' => 'skip',
                     'validate_dependencies' => true,
                     'create_missing_dependencies' => false,
                     'dry_run' => false,
                 ]);
    }

    /** @test */
    public function 匯入檔案驗證規則正常運作()
    {
        $this->actingAs($this->adminUser);

        $component = Livewire::test(PermissionImportExport::class);

        // 測試沒有檔案的情況
        $component->call('previewImport')
                 ->assertHasErrors(['importFile' => 'required']);

        // 測試非 JSON 檔案
        $txtFile = UploadedFile::fake()->create('test.txt', 100);
        $component->set('importFile', $txtFile)
                 ->call('previewImport')
                 ->assertHasErrors(['importFile']);

        // 測試檔案大小限制
        $largeFile = UploadedFile::fake()->create('large.json', 10240); // 10MB
        $component->set('importFile', $largeFile)
                 ->call('previewImport')
                 ->assertHasErrors(['importFile']);
    }

    /** @test */
    public function 試運行模式不會實際匯入資料()
    {
        $this->actingAs($this->adminUser);

        // 建立測試 JSON 檔案
        $testData = [
            'permissions' => [
                [
                    'name' => 'test.dry_run',
                    'display_name' => '試運行測試權限',
                    'description' => '這是一個試運行測試權限',
                    'module' => 'test',
                    'type' => 'view',
                ]
            ]
        ];

        $file = UploadedFile::fake()->createWithContent(
            'permissions.json',
            json_encode($testData)
        );

        $component = Livewire::test(PermissionImportExport::class);

        $component->set('importFile', $file)
                 ->set('importOptions.dry_run', true)
                 ->call('previewImport')
                 ->call('executeImport');

        // 檢查權限沒有被實際建立
        $this->assertDatabaseMissing('permissions', [
            'name' => 'test.dry_run'
        ]);

        // 但應該有匯入結果顯示
        $importResults = $component->get('importResults');
        $this->assertNotEmpty($importResults);
    }

    /** @test */
    public function 匯入完成後發送通知事件()
    {
        $this->actingAs($this->adminUser);

        // 建立測試 JSON 檔案
        $testData = [
            'permissions' => [
                [
                    'name' => 'test.import_complete',
                    'display_name' => '匯入完成測試權限',
                    'description' => '測試匯入完成通知',
                    'module' => 'test',
                    'type' => 'view',
                ]
            ]
        ];

        $file = UploadedFile::fake()->createWithContent(
            'permissions.json',
            json_encode($testData)
        );

        $component = Livewire::test(PermissionImportExport::class);

        $component->set('importFile', $file)
                 ->call('previewImport')
                 ->call('executeImport');

        // 檢查是否發送了匯入完成事件
        $component->assertDispatched('import-completed');
        $component->assertDispatched('permissions-imported');
    }

    /** @test */
    public function 匯出完成後發送通知事件()
    {
        $this->actingAs($this->adminUser);

        $component = Livewire::test(PermissionImportExport::class);

        $component->call('exportPermissions');

        // 檢查是否發送了匯出完成事件
        $component->assertDispatched('export-completed');
    }

    /** @test */
    public function 可以下載匯出檔案()
    {
        $this->actingAs($this->adminUser);

        $component = Livewire::test(PermissionImportExport::class);

        $response = $component->call('downloadExport');

        // 檢查回應是否為下載回應
        $this->assertInstanceOf(\Symfony\Component\HttpFoundation\StreamedResponse::class, $response);
    }

    /** @test */
    public function 匯入預覽顯示正確資訊()
    {
        $this->actingAs($this->adminUser);

        // 建立測試 JSON 檔案
        $testData = [
            'permissions' => [
                [
                    'name' => 'test.preview',
                    'display_name' => '預覽測試權限',
                    'description' => '這是一個預覽測試權限',
                    'module' => 'test',
                    'type' => 'view',
                ]
            ]
        ];

        $file = UploadedFile::fake()->createWithContent(
            'permissions.json',
            json_encode($testData)
        );

        $component = Livewire::test(PermissionImportExport::class);

        $component->set('importFile', $file)
                 ->call('previewImport');

        $importPreview = $component->get('importPreview');
        
        $this->assertArrayHasKey('total_permissions', $importPreview);
        $this->assertArrayHasKey('new_permissions', $importPreview);
        $this->assertArrayHasKey('existing_permissions', $importPreview);
        $this->assertArrayHasKey('conflicts', $importPreview);
        
        $this->assertEquals(1, $importPreview['total_permissions']);
        $this->assertEquals(1, $importPreview['new_permissions']);
        $this->assertEquals(0, $importPreview['existing_permissions']);
    }

    /** @test */
    public function 事件監聽器正常運作()
    {
        $this->actingAs($this->adminUser);

        $component = Livewire::test(PermissionImportExport::class);

        // 測試開啟匯入對話框事件
        $component->dispatch('open-import-modal')
                 ->assertSet('showImportPreview', false)
                 ->assertSet('showImportResults', false);

        // 測試開啟匯出對話框事件
        $component->dispatch('open-export-modal')
                 ->assertSet('exportInProgress', false);
    }
}