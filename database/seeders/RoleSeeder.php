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
                'name' => 'super_admin',
                'display_name' => '超級管理員',
                'description' => '擁有系統所有權限的最高管理員',
                'permissions' => 'all' // 特殊標記，表示擁有所有權限
            ],
            [
                'name' => 'admin',
                'display_name' => '管理員',
                'description' => '擁有所有管理權限的管理員',
                'permissions' => 'all' // 修改為擁有所有權限，避免權限不足問題
            ],
            [
                'name' => 'user',
                'display_name' => '一般使用者',
                'description' => '系統的一般使用者，擁有基本權限',
                'permissions' => [
                    // 儀表板基本檢視
                    'dashboard.view',
                    
                    // 個人資料權限
                    'profile.view',
                    'profile.edit',
                ]
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
                // 超級管理員擁有所有權限
                $allPermissions = Permission::all();
                $role->permissions()->sync($allPermissions->pluck('id')->toArray());
                $this->command->info("已為角色 '{$role->display_name}' 指派所有權限");
            } elseif (is_array($roleData['permissions'])) {
                // 指派特定權限
                $permissions = Permission::whereIn('name', $roleData['permissions'])->get();
                $role->permissions()->sync($permissions->pluck('id')->toArray());
                $this->command->info("已為角色 '{$role->display_name}' 指派 " . count($permissions) . " 個權限");
            }
        }

        $this->command->info('已成功建立 ' . count($roles) . ' 個角色');
    }
}