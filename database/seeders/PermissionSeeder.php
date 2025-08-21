<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Permission;
use App\Observers\PermissionSecurityObserver;

/**
 * 權限種子檔案
 * 
 * 建立系統基本權限
 */
class PermissionSeeder extends Seeder
{
    /**
     * 執行權限種子
     */
    public function run(): void
    {
        // 在種子期間暫時停用安全觀察者
        Permission::unsetEventDispatcher();
        
        // 或者可以使用 withoutEvents 方法
        Permission::withoutEvents(function () {
            $this->seedPermissions();
        });
    }
    
    /**
     * 建立權限資料
     */
    private function seedPermissions(): void
    {
        // 定義系統基本權限
        $permissions = [
            // 使用者管理權限
            [
                'name' => 'users.view',
                'display_name' => '檢視使用者',
                'description' => '可以檢視使用者列表和詳細資訊',
                'module' => 'users'
            ],
            [
                'name' => 'users.create',
                'display_name' => '建立使用者',
                'description' => '可以建立新的使用者帳號',
                'module' => 'users'
            ],
            [
                'name' => 'users.edit',
                'display_name' => '編輯使用者',
                'description' => '可以編輯使用者資訊和設定',
                'module' => 'users'
            ],
            [
                'name' => 'users.delete',
                'display_name' => '刪除使用者',
                'description' => '可以刪除使用者帳號',
                'module' => 'users'
            ],
            [
                'name' => 'users.assign_roles',
                'display_name' => '指派使用者角色',
                'description' => '可以為使用者指派或移除角色',
                'module' => 'users'
            ],

            // 角色管理權限
            [
                'name' => 'roles.view',
                'display_name' => '檢視角色',
                'description' => '可以檢視角色列表和詳細資訊',
                'module' => 'roles'
            ],
            [
                'name' => 'roles.create',
                'display_name' => '建立角色',
                'description' => '可以建立新的角色',
                'module' => 'roles'
            ],
            [
                'name' => 'roles.edit',
                'display_name' => '編輯角色',
                'description' => '可以編輯角色資訊和權限設定',
                'module' => 'roles'
            ],
            [
                'name' => 'roles.delete',
                'display_name' => '刪除角色',
                'description' => '可以刪除角色',
                'module' => 'roles'
            ],
            [
                'name' => 'roles.manage_permissions',
                'display_name' => '管理角色權限',
                'description' => '可以為角色指派或移除權限',
                'module' => 'roles'
            ],

            // 權限管理權限
            [
                'name' => 'permissions.view',
                'display_name' => '檢視權限',
                'description' => '可以檢視權限列表和詳細資訊',
                'module' => 'permissions'
            ],
            [
                'name' => 'permissions.create',
                'display_name' => '建立權限',
                'description' => '可以建立新的權限',
                'module' => 'permissions'
            ],
            [
                'name' => 'permissions.edit',
                'display_name' => '編輯權限',
                'description' => '可以編輯權限資訊',
                'module' => 'permissions'
            ],
            [
                'name' => 'permissions.delete',
                'display_name' => '刪除權限',
                'description' => '可以刪除權限',
                'module' => 'permissions'
            ],

            // 儀表板權限
            [
                'name' => 'dashboard.view',
                'display_name' => '檢視儀表板',
                'description' => '可以存取管理後台儀表板',
                'module' => 'dashboard'
            ],
            [
                'name' => 'dashboard.stats',
                'display_name' => '檢視統計資訊',
                'description' => '可以檢視系統統計資訊',
                'module' => 'dashboard'
            ],

            // 系統管理權限
            [
                'name' => 'system.settings',
                'display_name' => '系統設定',
                'description' => '可以修改系統設定',
                'module' => 'system'
            ],
            [
                'name' => 'system.logs',
                'display_name' => '檢視系統日誌',
                'description' => '可以檢視系統日誌和錯誤記錄',
                'module' => 'system'
            ],
            [
                'name' => 'system.maintenance',
                'display_name' => '系統維護',
                'description' => '可以執行系統維護操作',
                'module' => 'system'
            ],

            // 個人資料權限
            [
                'name' => 'profile.view',
                'display_name' => '檢視個人資料',
                'description' => '可以檢視自己的個人資料',
                'module' => 'profile'
            ],
            [
                'name' => 'profile.edit',
                'display_name' => '編輯個人資料',
                'description' => '可以編輯自己的個人資料',
                'module' => 'profile'
            ],
        ];

        // 建立權限記錄
        foreach ($permissions as $permission) {
            Permission::firstOrCreate(
                ['name' => $permission['name']],
                $permission
            );
        }

        $this->command->info('已成功建立 ' . count($permissions) . ' 個權限');
    }
}