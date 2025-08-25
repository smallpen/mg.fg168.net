<?php

namespace App\Livewire\Admin\Permissions;

use App\Livewire\Admin\AdminComponent;
use App\Models\User;
use App\Models\Role;
use App\Models\Permission;
use Illuminate\Support\Collection;
use Livewire\Attributes\On;

/**
 * 權限測試工具元件
 * 
 * 提供權限驗證測試功能，支援使用者權限和角色權限測試
 */
class PermissionTest extends AdminComponent
{
    /**
     * 選擇的使用者 ID
     * 
     * @var int
     */
    public int $selectedUserId = 0;

    /**
     * 選擇的角色 ID
     * 
     * @var int
     */
    public int $selectedRoleId = 0;

    /**
     * 要測試的權限名稱
     * 
     * @var string
     */
    public string $permissionToTest = '';

    /**
     * 測試結果
     * 
     * @var array
     */
    public array $testResults = [];

    /**
     * 測試模式：user 或 role
     * 
     * @var string
     */
    public string $testMode = 'user';

    /**
     * 是否顯示詳細路徑
     * 
     * @var bool
     */
    public bool $showDetailedPath = false;

    /**
     * 權限路徑資料
     * 
     * @var array
     */
    public array $permissionPath = [];

    /**
     * 元件掛載時執行
     * 
     * @return void
     */
    public function mount(): void
    {
        // TODO: Add proper permission check when authorization system is fully configured
        // $this->checkPermission('permissions.test', 'permission_test');
    }

    /**
     * 取得所有使用者（計算屬性）
     * 
     * @return Collection
     */
    public function getUsersProperty(): Collection
    {
        return User::select('id', 'username', 'name', 'is_active')
                   ->where('is_active', true)
                   ->orderBy('username')
                   ->get()
                   ->map(function ($user) {
                       return [
                           'id' => $user->id,
                           'display_name' => $user->display_name . " ({$user->username})",
                           'username' => $user->username,
                           'name' => $user->name,
                       ];
                   });
    }

    /**
     * 取得所有角色（計算屬性）
     * 
     * @return Collection
     */
    public function getRolesProperty(): Collection
    {
        return Role::select('id', 'name', 'display_name', 'is_active')
                   ->where('is_active', true)
                   ->orderBy('display_name')
                   ->get()
                   ->map(function ($role) {
                       return [
                           'id' => $role->id,
                           'display_name' => $role->localized_display_name,
                           'name' => $role->name,
                           'user_count' => $role->user_count,
                       ];
                   });
    }

    /**
     * 取得所有權限（計算屬性）
     * 
     * @return Collection
     */
    public function getPermissionsProperty(): Collection
    {
        return Permission::select('id', 'name', 'display_name', 'module', 'type')
                        ->orderBy('module')
                        ->orderBy('name')
                        ->get()
                        ->groupBy('module')
                        ->map(function ($permissions, $module) {
                            return [
                                'module' => $module,
                                'permissions' => $permissions->map(function ($permission) {
                                    return [
                                        'name' => $permission->name,
                                        'display_name' => $permission->localized_display_name,
                                        'type' => $permission->type,
                                    ];
                                })
                            ];
                        });
    }

    /**
     * 測試使用者權限
     * 
     * @return void
     */
    public function testUserPermission(): void
    {
        $this->validate([
            'selectedUserId' => 'required|exists:users,id',
            'permissionToTest' => 'required|string|exists:permissions,name',
        ], [
            'selectedUserId.required' => __('permissions.test.user_required'),
            'selectedUserId.exists' => __('permissions.test.user_not_found'),
            'permissionToTest.required' => __('permissions.test.permission_required'),
            'permissionToTest.exists' => __('permissions.test.permission_not_found'),
        ]);

        $user = User::find($this->selectedUserId);
        $permission = Permission::where('name', $this->permissionToTest)->first();

        if (!$user || !$permission) {
            $this->addError('test', __('permissions.test.invalid_selection'));
            return;
        }

        // 執行權限測試
        $hasPermission = $user->hasPermission($this->permissionToTest);
        
        // 取得權限路徑
        $this->permissionPath = $this->getPermissionPath($user->id, $this->permissionToTest);

        // 建立測試結果
        $this->testResults = [
            'type' => 'user',
            'subject' => [
                'id' => $user->id,
                'name' => $user->display_name,
                'username' => $user->username,
            ],
            'permission' => [
                'name' => $permission->name,
                'display_name' => $permission->localized_display_name,
                'module' => $permission->module,
                'type' => $permission->type,
            ],
            'result' => $hasPermission,
            'tested_at' => now()->format('Y-m-d H:i:s'),
            'path' => $this->permissionPath,
            'summary' => $this->generateTestSummary($user, $permission, $hasPermission),
        ];

        // TODO: 記錄測試活動 (需要設定 activity log 套件)
        // activity()
        //     ->performedOn($permission)
        //     ->causedBy(auth()->user())
        //     ->withProperties([
        //         'tested_user_id' => $user->id,
        //         'permission_name' => $permission->name,
        //         'result' => $hasPermission,
        //         'path_count' => count($this->permissionPath),
        //     ])
        //     ->log('permission_test_user');

        $this->dispatch('permission-tested', [
            'type' => 'user',
            'result' => $hasPermission,
            'subject' => $user->display_name,
            'permission' => $permission->localized_display_name,
        ]);
    }

    /**
     * 測試角色權限
     * 
     * @return void
     */
    public function testRolePermission(): void
    {
        $this->validate([
            'selectedRoleId' => 'required|exists:roles,id',
            'permissionToTest' => 'required|string|exists:permissions,name',
        ], [
            'selectedRoleId.required' => __('permissions.test.role_required'),
            'selectedRoleId.exists' => __('permissions.test.role_not_found'),
            'permissionToTest.required' => __('permissions.test.permission_required'),
            'permissionToTest.exists' => __('permissions.test.permission_not_found'),
        ]);

        $role = Role::find($this->selectedRoleId);
        $permission = Permission::where('name', $this->permissionToTest)->first();

        if (!$role || !$permission) {
            $this->addError('test', __('permissions.test.invalid_selection'));
            return;
        }

        // 執行權限測試
        $hasPermission = $role->hasPermissionIncludingInherited($this->permissionToTest);
        
        // 取得角色權限路徑
        $this->permissionPath = $this->getRolePermissionPath($role->id, $this->permissionToTest);

        // 建立測試結果
        $this->testResults = [
            'type' => 'role',
            'subject' => [
                'id' => $role->id,
                'name' => $role->localized_display_name,
                'system_name' => $role->name,
                'user_count' => $role->user_count,
            ],
            'permission' => [
                'name' => $permission->name,
                'display_name' => $permission->localized_display_name,
                'module' => $permission->module,
                'type' => $permission->type,
            ],
            'result' => $hasPermission,
            'tested_at' => now()->format('Y-m-d H:i:s'),
            'path' => $this->permissionPath,
            'summary' => $this->generateRoleTestSummary($role, $permission, $hasPermission),
        ];

        // TODO: 記錄測試活動 (需要設定 activity log 套件)
        // activity()
        //     ->performedOn($permission)
        //     ->causedBy(auth()->user())
        //     ->withProperties([
        //         'tested_role_id' => $role->id,
        //         'permission_name' => $permission->name,
        //         'result' => $hasPermission,
        //         'path_count' => count($this->permissionPath),
        //     ])
        //     ->log('permission_test_role');

        $this->dispatch('permission-tested', [
            'type' => 'role',
            'result' => $hasPermission,
            'subject' => $role->localized_display_name,
            'permission' => $permission->localized_display_name,
        ]);
    }

    /**
     * 取得使用者權限路徑
     * 
     * @param int $userId
     * @param string $permission
     * @return array
     */
    public function getPermissionPath(int $userId, string $permission): array
    {
        $user = User::find($userId);
        if (!$user) {
            return [];
        }

        $path = [];
        
        // 檢查是否為超級管理員
        if ($user->isSuperAdmin()) {
            return [
                [
                    'type' => 'super_admin',
                    'name' => __('permissions.test.super_admin_access'),
                    'description' => __('permissions.test.super_admin_description'),
                ]
            ];
        }

        // 檢查透過角色取得的權限
        $userRoles = $user->roles()->with(['permissions', 'parent'])->get();
        
        foreach ($userRoles as $role) {
            $rolePath = $this->getRolePermissionPath($role->id, $permission);
            
            if (!empty($rolePath)) {
                $path[] = [
                    'type' => 'role',
                    'role_id' => $role->id,
                    'role_name' => $role->localized_display_name,
                    'role_system_name' => $role->name,
                    'path' => $rolePath,
                ];
            }
        }

        return $path;
    }

    /**
     * 取得角色權限路徑
     * 
     * @param int $roleId
     * @param string $permission
     * @return array
     */
    public function getRolePermissionPath(int $roleId, string $permission): array
    {
        $role = Role::find($roleId);
        if (!$role) {
            return [];
        }

        $path = [];
        $permissionModel = Permission::where('name', $permission)->first();
        
        if (!$permissionModel) {
            return [];
        }

        // 檢查直接權限
        if ($role->permissions()->where('permissions.name', $permission)->exists()) {
            $path[] = [
                'type' => 'direct',
                'role_id' => $role->id,
                'role_name' => $role->localized_display_name,
                'permission_name' => $permission,
                'permission_display_name' => $permissionModel->localized_display_name,
                'source' => 'direct_assignment',
            ];
        }

        // 檢查繼承權限
        if ($role->parent && $role->parent->hasPermissionIncludingInherited($permission)) {
            $parentPath = $this->getRolePermissionPath($role->parent->id, $permission);
            if (!empty($parentPath)) {
                $path[] = [
                    'type' => 'inherited',
                    'role_id' => $role->id,
                    'role_name' => $role->localized_display_name,
                    'parent_role_id' => $role->parent->id,
                    'parent_role_name' => $role->parent->localized_display_name,
                    'permission_name' => $permission,
                    'permission_display_name' => $permissionModel->localized_display_name,
                    'source' => 'inheritance',
                    'parent_path' => $parentPath,
                ];
            }
        }

        // 檢查權限依賴
        $dependencies = $permissionModel->dependencies;
        foreach ($dependencies as $dependency) {
            if ($role->hasPermissionIncludingInherited($dependency->name)) {
                $dependencyPath = $this->getRolePermissionPath($roleId, $dependency->name);
                if (!empty($dependencyPath)) {
                    $path[] = [
                        'type' => 'dependency',
                        'role_id' => $role->id,
                        'role_name' => $role->localized_display_name,
                        'permission_name' => $permission,
                        'permission_display_name' => $permissionModel->localized_display_name,
                        'dependency_name' => $dependency->name,
                        'dependency_display_name' => $dependency->localized_display_name,
                        'source' => 'dependency_resolution',
                        'dependency_path' => $dependencyPath,
                    ];
                }
            }
        }

        return $path;
    }

    /**
     * 生成使用者測試摘要
     * 
     * @param User $user
     * @param Permission $permission
     * @param bool $hasPermission
     * @return array
     */
    private function generateTestSummary(User $user, Permission $permission, bool $hasPermission): array
    {
        $summary = [
            'result_text' => $hasPermission 
                ? __('permissions.test.user_has_permission', [
                    'user' => $user->display_name,
                    'permission' => $permission->localized_display_name
                ])
                : __('permissions.test.user_lacks_permission', [
                    'user' => $user->display_name,
                    'permission' => $permission->localized_display_name
                ]),
            'result_class' => $hasPermission ? 'success' : 'danger',
            'icon' => $hasPermission ? 'check-circle' : 'x-circle',
        ];

        if ($hasPermission) {
            $roleCount = count($this->permissionPath);
            $summary['details'] = __('permissions.test.permission_granted_through_roles', [
                'count' => $roleCount
            ]);
        } else {
            $summary['details'] = __('permissions.test.permission_not_found_in_roles');
        }

        return $summary;
    }

    /**
     * 生成角色測試摘要
     * 
     * @param Role $role
     * @param Permission $permission
     * @param bool $hasPermission
     * @return array
     */
    private function generateRoleTestSummary(Role $role, Permission $permission, bool $hasPermission): array
    {
        $summary = [
            'result_text' => $hasPermission 
                ? __('permissions.test.role_has_permission', [
                    'role' => $role->localized_display_name,
                    'permission' => $permission->localized_display_name
                ])
                : __('permissions.test.role_lacks_permission', [
                    'role' => $role->localized_display_name,
                    'permission' => $permission->localized_display_name
                ]),
            'result_class' => $hasPermission ? 'success' : 'danger',
            'icon' => $hasPermission ? 'check-circle' : 'x-circle',
        ];

        if ($hasPermission) {
            $pathCount = count($this->permissionPath);
            if ($pathCount > 0) {
                $directAssignment = collect($this->permissionPath)->contains('type', 'direct');
                $inheritedAssignment = collect($this->permissionPath)->contains('type', 'inherited');
                $dependencyAssignment = collect($this->permissionPath)->contains('type', 'dependency');

                $sources = [];
                if ($directAssignment) $sources[] = __('permissions.test.direct_assignment');
                if ($inheritedAssignment) $sources[] = __('permissions.test.inheritance');
                if ($dependencyAssignment) $sources[] = __('permissions.test.dependency');

                $summary['details'] = __('permissions.test.permission_granted_through', [
                    'sources' => implode(', ', $sources)
                ]);
            }
        } else {
            $summary['details'] = __('permissions.test.permission_not_assigned_to_role');
        }

        return $summary;
    }

    /**
     * 清除測試結果
     * 
     * @return void
     */
    public function clearResults(): void
    {
        $this->testResults = [];
        $this->permissionPath = [];
        $this->showDetailedPath = false;
        
        $this->dispatch('results-cleared');
    }

    /**
     * 切換測試模式
     * 
     * @param string $mode
     * @return void
     */
    public function setTestMode(string $mode): void
    {
        if (in_array($mode, ['user', 'role'])) {
            $this->testMode = $mode;
            $this->clearResults();
        }
    }

    /**
     * 切換詳細路徑顯示
     * 
     * @return void
     */
    public function toggleDetailedPath(): void
    {
        $this->showDetailedPath = !$this->showDetailedPath;
    }

    /**
     * 匯出測試報告
     * 
     * @return \Symfony\Component\HttpFoundation\StreamedResponse
     */
    public function exportReport()
    {
        if (empty($this->testResults)) {
            $this->addError('export', __('permissions.test.no_results_to_export'));
            return;
        }

        $filename = 'permission_test_report_' . now()->format('Y-m-d_H-i-s') . '.json';
        
        $reportData = [
            'test_info' => [
                'generated_at' => now()->toISOString(),
                'generated_by' => auth()->user()->display_name,
                'test_type' => $this->testResults['type'],
            ],
            'test_results' => $this->testResults,
            'detailed_path' => $this->permissionPath,
        ];

        return response()->streamDownload(function () use ($reportData) {
            echo json_encode($reportData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        }, $filename, [
            'Content-Type' => 'application/json',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    /**
     * 執行批量權限測試
     * 
     * @param array $permissions
     * @return array
     */
    public function batchTestPermissions(array $permissions): array
    {
        $results = [];
        
        foreach ($permissions as $permission) {
            if ($this->testMode === 'user' && $this->selectedUserId) {
                $user = User::find($this->selectedUserId);
                if ($user) {
                    $results[$permission] = [
                        'permission' => $permission,
                        'has_permission' => $user->hasPermission($permission),
                        'path' => $this->getPermissionPath($this->selectedUserId, $permission),
                    ];
                }
            } elseif ($this->testMode === 'role' && $this->selectedRoleId) {
                $role = Role::find($this->selectedRoleId);
                if ($role) {
                    $results[$permission] = [
                        'permission' => $permission,
                        'has_permission' => $role->hasPermissionIncludingInherited($permission),
                        'path' => $this->getRolePermissionPath($this->selectedRoleId, $permission),
                    ];
                }
            }
        }
        
        return $results;
    }

    /**
     * 渲染元件
     * 
     * @return \Illuminate\View\View
     */
    public function render()
    {
        return view('livewire.admin.permissions.permission-test');
    }
}