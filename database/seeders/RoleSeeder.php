<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Role;
use App\Models\Permission;

/**
 * 角色種子檔案
 * 
 * 建立系統基本角色並指派權限
 */
class RoleSeeder extends Seeder
{
    /**
     * 執行角色種子
     */
    public function run(): void
    {
        // 定義系統基本角色
        $roles = [
            [
                'name' => 'admin',
                'display_name' => '系統管理員',
                'description' => '擁有系統完整管理權限的管理員角色',
                'permissions' => 'all', // 擁有所有權限
                'is_system' => true,
                'priority' => 1
            ],
            [
                'name' => 'manager',
                'display_name' => '部門主管',
                'description' => '部門主管角色，擁有部分管理權限',
                'permissions' => [
                    // 儀表板權限
                    'dashboard.view',
                    'dashboard.stats',
                    
                    // 使用者檢視權限
                    'users.view',
                    'users.create',
                    'users.edit',
                    
                    // 角色檢視權限
                    'roles.view',
                    
                    // 權限檢視權限
                    'permissions.view',
                    
                    // 個人資料權限
                    'profile.view',
                    'profile.edit',
                    
                    // 活動日誌權限
                    'activity_logs.view',
                    'activity_logs.export',
                    
                    // 通知權限
                    'notifications.view',
                    'notifications.create',
                    'notifications.edit',
                    'notifications.send',
                    
                    // 設定檢視權限
                    'settings.view',
                ],
                'is_system' => true,
                'priority' => 2
            ],
            [
                'name' => 'user',
                'display_name' => '一般使用者',
                'description' => '系統一般使用者，擁有基本操作權限',
                'permissions' => [
                    // 儀表板基本檢視
                    'dashboard.view',
                    
                    // 個人資料權限
                    'profile.view',
                    'profile.edit',
                    
                    // 通知檢視權限
                    'notifications.view',
                ],
                'is_system' => true,
                'priority' => 3
            ],
        ];

        // 建立角色並指派權限
        foreach ($roles as $roleData) {
            // 建立角色
            $role = Role::firstOrCreate(
                ['name' => $roleData['name']],
                [
                    'display_name' => $roleData['display_name'],
                    'description' => $roleData['description'],
                ]
            );

            // 指派權限
            if ($roleData['permissions'] === 'all') {
                // 管理員擁有所有權限
                $allPermissions = Permission::all();
                $role->permissions()->sync($allPermissions->pluck('id')->toArray());
                $this->command->info("✓ 已為角色 '{$role->display_name}' 指派所有權限 ({$allPermissions->count()} 個)");
            } elseif (is_array($roleData['permissions'])) {
                // 指派特定權限
                $permissions = Permission::whereIn('name', $roleData['permissions'])->get();
                $role->permissions()->sync($permissions->pluck('id')->toArray());
                $this->command->info("✓ 已為角色 '{$role->display_name}' 指派 {$permissions->count()} 個權限");
                
                // 顯示未找到的權限
                $notFound = array_diff($roleData['permissions'], $permissions->pluck('name')->toArray());
                if (!empty($notFound)) {
                    $this->command->warn("  警告: 以下權限未找到: " . implode(', ', $notFound));
                }
            }
        }

        $this->command->info('');
        $this->command->info("✓ 已成功建立 " . count($roles) . " 個系統角色");
    }
}