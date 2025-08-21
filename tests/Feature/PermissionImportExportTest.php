<?php

namespace Tests\Feature;

use App\Models\Permission;
use App\Models\User;
use App\Models\Role;
use App\Services\PermissionImportExportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * 權限匯入匯出功能測試
 */
class PermissionImportExportTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected User $adminUser;
    protected Role $adminRole;
    protected PermissionImportExportService $importExportService;

    protected function setUp(): void
    {
        parent::setUp();

        // 建立測試用的管理員使用者和角色
        $this->adminRole = Role::create([
            'name' => 'admin',
            'display_name' => '管理員',
            'description' => '系統管理員角色',
        ]);

        $this->adminUser = User::create([
            'username' => 'admin',
            'name' => '測試管理員',
            'email' => 'admin@test.com',
            'password' => bcrypt('password'),
            'is_active' => true,
        ]);

        $this->adminUser->roles()->attach($this->adminRole);

        // 建立必要的權限
        $permissions = [
            'permissions.view',
            'permissions.create',
            'permissions.edit',
            'permissions.delete',
            'permissions.export',
            'permissions.import',
        ];

        foreach ($permissions as $permissionName) {
            $permission = Permission::create([
                'name' => $permissionName,
                'display_name' => ucfirst(str_replace('.', ' ', $permissionName)),
                'description' => "Permission to {$permissionName}",
                'module' => 'permissions',
                'type' => explode('.', $permissionName)[1],
            ]);

            $this->adminRole->permissions()->attach($permission);
        }

        $this->importExportService = app(PermissionImportExportService::class);
    }

    /** @test */
    public function it_can_export_permissions()
    {
        // 建立一些測試權限
        $testPermissions = [
            [
                'name' => 'users.view',
                'display_name' => '檢視使用者',
                'description' => '檢視使用者列表',
                'module' => 'users',
                'type' => 'view',
            ],
            [
                'name' => 'users.create',
                'display_name' => '建立使用者',
                'description' => '建立新使用者',
                'module' => 'users',
                'type' => 'create',
            ],
        ];

        foreach ($testPermissions as $permissionData) {
            Permission::create($permissionData);
        }

        // 執行匯出
        $exportData = $this->importExportService->exportPermissions();

        // 驗證匯出資料結構
        $this->assertArrayHasKey('metadata', $exportData);
        $this->assertArrayHasKey('permissions', $exportData);
        $this->assertArrayHasKey('version', $exportData['metadata']);
        $this->assertArrayHasKey('exported_at', $exportData['metadata']);
        $this->assertArrayHasKey('total_permissions', $exportData['metadata']);

        // 驗證權限資料
        $this->assertGreaterThan(0, count($exportData['permissions']));
        
        $firstPermission = $exportData['permissions'][0];
        $this->assertArrayHasKey('name', $firstPermission);
        $this->assertArrayHasKey('display_name', $firstPermission);
        $this->assertArrayHasKey('module', $firstPermission);
        $this->assertArrayHasKey('type', $firstPermission);
        $this->assertArrayHasKey('dependencies', $firstPermission);
    }

    /** @test */
    public function it_can_export_permissions_with_filters()
    {
        // 建立不同模組的權限
        Permission::create([
            'name' => 'users.view',
            'display_name' => '檢視使用者',
            'module' => 'users',
            'type' => 'view',
        ]);

        Permission::create([
            'name' => 'roles.view',
            'display_name' => '檢視角色',
            'module' => 'roles',
            'type' => 'view',
        ]);

        // 只匯出 users 模組的權限
        $filters = ['modules' => ['users']];
        $exportData = $this->importExportService->exportPermissions($filters);

        // 驗證只包含 users 模組的權限
        $userPermissions = array_filter($exportData['permissions'], function ($permission) {
            return $permission['module'] === 'users';
        });

        $this->assertGreaterThan(0, count($userPermissions));
        
        // 確保沒有其他模組的權限
        $nonUserPermissions = array_filter($exportData['permissions'], function ($permission) {
            return $permission['module'] !== 'users' && $permission['module'] !== 'permissions';
        });

        $this->assertEquals(0, count($nonUserPermissions));
    }

    /** @test */
    public function it_can_import_permissions()
    {
        $importData = [
            'metadata' => [
                'version' => '1.0',
                'exported_at' => now()->toISOString(),
                'total_permissions' => 2,
            ],
            'permissions' => [
                [
                    'name' => 'test.view',
                    'display_name' => '測試檢視',
                    'description' => '測試權限描述',
                    'module' => 'test',
                    'type' => 'view',
                    'dependencies' => [],
                ],
                [
                    'name' => 'test.create',
                    'display_name' => '測試建立',
                    'description' => '測試建立權限',
                    'module' => 'test',
                    'type' => 'create',
                    'dependencies' => ['test.view'],
                ],
            ],
        ];

        $results = $this->importExportService->importPermissions($importData);

        // 驗證匯入結果
        $this->assertTrue($results['success']);
        $this->assertEquals(2, $results['created']);
        $this->assertEquals(0, $results['updated']);
        $this->assertEquals(0, $results['skipped']);
        $this->assertEmpty($results['errors']);

        // 驗證權限已建立
        $this->assertDatabaseHas('permissions', [
            'name' => 'test.view',
            'display_name' => '測試檢視',
            'module' => 'test',
            'type' => 'view',
        ]);

        $this->assertDatabaseHas('permissions', [
            'name' => 'test.create',
            'display_name' => '測試建立',
            'module' => 'test',
            'type' => 'create',
        ]);

        // 驗證依賴關係
        $createPermission = Permission::where('name', 'test.create')->first();
        $viewPermission = Permission::where('name', 'test.view')->first();
        
        $this->assertTrue($createPermission->dependencies->contains($viewPermission));
    }

    /** @test */
    public function it_handles_import_conflicts_correctly()
    {
        // 先建立一個權限
        $existingPermission = Permission::create([
            'name' => 'test.view',
            'display_name' => '原始檢視',
            'description' => '原始描述',
            'module' => 'test',
            'type' => 'view',
        ]);

        $importData = [
            'metadata' => [
                'version' => '1.0',
                'total_permissions' => 1,
            ],
            'permissions' => [
                [
                    'name' => 'test.view',
                    'display_name' => '更新檢視',
                    'description' => '更新描述',
                    'module' => 'test',
                    'type' => 'view',
                    'dependencies' => [],
                ],
            ],
        ];

        // 測試跳過策略
        $results = $this->importExportService->importPermissions($importData, [
            'conflict_resolution' => 'skip'
        ]);

        $this->assertEquals(0, $results['created']);
        $this->assertEquals(0, $results['updated']);
        $this->assertEquals(1, $results['skipped']);

        // 驗證原始資料未變更
        $existingPermission->refresh();
        $this->assertEquals('原始檢視', $existingPermission->display_name);

        // 測試更新策略
        $results = $this->importExportService->importPermissions($importData, [
            'conflict_resolution' => 'update'
        ]);

        $this->assertEquals(0, $results['created']);
        $this->assertEquals(1, $results['updated']);
        $this->assertEquals(0, $results['skipped']);

        // 驗證資料已更新
        $existingPermission->refresh();
        $this->assertEquals('更新檢視', $existingPermission->display_name);
        $this->assertEquals('更新描述', $existingPermission->description);
    }

    /** @test */
    public function it_validates_import_data_format()
    {
        $invalidData = [
            'metadata' => [
                'version' => '1.0',
            ],
            'permissions' => [
                [
                    'name' => '', // 空名稱
                    'display_name' => '測試',
                    'module' => 'test',
                    'type' => 'view',
                ],
                [
                    'name' => 'invalid-name!', // 無效格式
                    'display_name' => '測試2',
                    'module' => 'test',
                    'type' => 'view',
                ],
            ],
        ];

        try {
            $results = $this->importExportService->importPermissions($invalidData);
            $this->fail('Expected ValidationException to be thrown');
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->assertArrayHasKey('permissions.0.name', $e->errors());
        }
    }

    /** @test */
    public function it_handles_circular_dependencies()
    {
        $importData = [
            'metadata' => [
                'version' => '1.0',
                'total_permissions' => 2,
            ],
            'permissions' => [
                [
                    'name' => 'test.a',
                    'display_name' => '測試A',
                    'module' => 'test',
                    'type' => 'view',
                    'dependencies' => ['test.b'],
                ],
                [
                    'name' => 'test.b',
                    'display_name' => '測試B',
                    'module' => 'test',
                    'type' => 'view',
                    'dependencies' => ['test.a'],
                ],
            ],
        ];

        $results = $this->importExportService->importPermissions($importData);

        // 應該建立權限但依賴關係設定會失敗
        $this->assertEquals(2, $results['created']);
        
        // 檢查是否有警告或錯誤（循環依賴應該被檢測到）
        $hasCircularDependencyIssue = !empty($results['warnings']) || !empty($results['errors']);
        $this->assertTrue($hasCircularDependencyIssue, 'Expected warnings or errors for circular dependencies');
    }

    /** @test */
    public function it_can_perform_dry_run_import()
    {
        $importData = [
            'metadata' => [
                'version' => '1.0',
                'total_permissions' => 1,
            ],
            'permissions' => [
                [
                    'name' => 'test.view',
                    'display_name' => '測試檢視',
                    'module' => 'test',
                    'type' => 'view',
                    'dependencies' => [],
                ],
            ],
        ];

        $results = $this->importExportService->importPermissions($importData, [
            'dry_run' => true
        ]);

        // 驗證試運行結果
        $this->assertTrue($results['success']);
        $this->assertEquals(1, $results['created']);
        $this->assertArrayHasKey('dry_run', $results);
        $this->assertTrue($results['dry_run']);

        // 驗證實際上沒有建立權限
        $this->assertDatabaseMissing('permissions', [
            'name' => 'test.view',
        ]);
    }

    /** @test */
    public function authenticated_user_can_export_permissions_via_http()
    {
        $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);
        $this->actingAs($this->adminUser);

        $response = $this->postJson('/admin/permissions/import-export/export', [
            'modules' => ['permissions'],
            'format' => 'json',
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'metadata' => [
                'version',
                'exported_at',
                'total_permissions',
            ],
            'permissions' => [
                '*' => [
                    'name',
                    'display_name',
                    'module',
                    'type',
                    'dependencies',
                ]
            ]
        ]);
    }

    /** @test */
    public function authenticated_user_can_import_permissions_via_http()
    {
        $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);
        $this->actingAs($this->adminUser);

        $importData = [
            'metadata' => [
                'version' => '1.0',
                'total_permissions' => 1,
            ],
            'permissions' => [
                [
                    'name' => 'test.view',
                    'display_name' => '測試檢視',
                    'module' => 'test',
                    'type' => 'view',
                    'dependencies' => [],
                ],
            ],
        ];

        // 建立臨時 JSON 檔案
        Storage::fake('local');
        $file = UploadedFile::fake()->createWithContent(
            'permissions.json',
            json_encode($importData)
        );

        $response = $this->postJson('/admin/permissions/import-export/import', [
            'file' => $file,
            'conflict_resolution' => 'skip',
            'validate_dependencies' => true,
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'message',
            'results' => [
                'success',
                'created',
                'updated',
                'skipped',
                'errors',
            ],
            'report',
        ]);

        // 驗證權限已建立
        $this->assertDatabaseHas('permissions', [
            'name' => 'test.view',
            'display_name' => '測試檢視',
        ]);
    }

    /** @test */
    public function unauthorized_user_cannot_access_import_export()
    {
        $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);
        
        $user = User::create([
            'username' => 'user',
            'name' => '一般使用者',
            'email' => 'user@test.com',
            'password' => bcrypt('password'),
            'is_active' => true,
        ]);

        $this->actingAs($user);

        $response = $this->postJson('/admin/permissions/import-export/export');
        $response->assertStatus(403);

        $response = $this->postJson('/admin/permissions/import-export/import');
        $response->assertStatus(403);
    }
}