<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\Role;
use App\Models\Permission;

/**
 * 開發環境專用種子檔案
 * 
 * 建立豐富的測試資料，方便開發和測試使用者管理功能
 */
class DevelopmentSeeder extends Seeder
{
    /**
     * 執行開發環境種子
     */
    public function run(): void
    {
        $this->command->info('開始建立開發測試資料...');
        
        // 確保基本角色和權限存在
        $this->ensureBasicRolesAndPermissions();
        
        // 建立測試使用者
        $this->createTestUsers();
        
        $this->command->info('');
        $this->command->info('=== 開發測試資料建立完成 ===');
        $this->displayAccountInfo();
    }

    /**
     * 確保基本角色和權限存在
     */
    private function ensureBasicRolesAndPermissions(): void
    {
        // 如果沒有基本資料，先執行基本 seeder
        if (Role::count() === 0 || Permission::count() === 0) {
            $this->command->info('檢測到缺少基本資料，正在建立...');
            $this->call([
                PermissionSeeder::class,
                RoleSeeder::class,
            ]);
        }
    }

    /**
     * 建立測試使用者
     */
    private function createTestUsers(): void
    {
        // 基本管理員帳號
        $basicUsers = [
            [
                'username' => 'superadmin',
                'name' => '超級管理員',
                'email' => 'superadmin@example.com',
                'password' => Hash::make('password123'),
                'theme_preference' => 'light',
                'locale' => 'zh_TW',
                'is_active' => true,
                'roles' => ['super_admin']
            ],
            [
                'username' => 'admin',
                'name' => '系統管理員',
                'email' => 'admin@example.com',
                'password' => Hash::make('password123'),
                'theme_preference' => 'light',
                'locale' => 'zh_TW',
                'is_active' => true,
                'roles' => ['admin']
            ],
        ];

        // 測試使用者資料
        $testUsers = [
            // 不同狀態的使用者
            [
                'username' => 'active_user',
                'name' => '啟用使用者',
                'email' => 'active@example.com',
                'password' => Hash::make('password123'),
                'theme_preference' => 'light',
                'locale' => 'zh_TW',
                'is_active' => true,
                'roles' => ['user']
            ],
            [
                'username' => 'inactive_user',
                'name' => '停用使用者',
                'email' => 'inactive@example.com',
                'password' => Hash::make('password123'),
                'theme_preference' => 'dark',
                'locale' => 'en',
                'is_active' => false,
                'roles' => ['user']
            ],
            
            // 不同角色的使用者
            [
                'username' => 'manager',
                'name' => '部門經理',
                'email' => 'manager@example.com',
                'password' => Hash::make('password123'),
                'theme_preference' => 'light',
                'locale' => 'zh_TW',
                'is_active' => true,
                'roles' => ['admin']
            ],
            [
                'username' => 'editor',
                'name' => '內容編輯',
                'email' => 'editor@example.com',
                'password' => Hash::make('password123'),
                'theme_preference' => 'dark',
                'locale' => 'zh_TW',
                'is_active' => true,
                'roles' => ['user']
            ],
            
            // 多角色使用者
            [
                'username' => 'multi_role',
                'name' => '多重角色使用者',
                'email' => 'multi@example.com',
                'password' => Hash::make('password123'),
                'theme_preference' => 'light',
                'locale' => 'zh_TW',
                'is_active' => true,
                'roles' => ['admin', 'user']
            ],
            
            // 無角色使用者
            [
                'username' => 'no_role',
                'name' => '無角色使用者',
                'email' => 'norole@example.com',
                'password' => Hash::make('password123'),
                'theme_preference' => 'light',
                'locale' => 'en',
                'is_active' => true,
                'roles' => []
            ],
            
            // 測試搜尋功能的使用者
            [
                'username' => 'john_doe',
                'name' => 'John Doe',
                'email' => 'john.doe@example.com',
                'password' => Hash::make('password123'),
                'theme_preference' => 'light',
                'locale' => 'en',
                'is_active' => true,
                'roles' => ['user']
            ],
            [
                'username' => 'jane_smith',
                'name' => 'Jane Smith',
                'email' => 'jane.smith@example.com',
                'password' => Hash::make('password123'),
                'theme_preference' => 'dark',
                'locale' => 'zh_TW',
                'is_active' => true,
                'roles' => ['user']
            ],
            [
                'username' => 'bob_wilson',
                'name' => 'Bob Wilson',
                'email' => 'bob.wilson@example.com',
                'password' => Hash::make('password123'),
                'theme_preference' => 'light',
                'locale' => 'en',
                'is_active' => false,
                'roles' => ['admin']
            ],
            
            // 中文名稱使用者
            [
                'username' => 'wang_ming',
                'name' => '王小明',
                'email' => 'wang.ming@example.com',
                'password' => Hash::make('password123'),
                'theme_preference' => 'light',
                'locale' => 'zh_TW',
                'is_active' => true,
                'roles' => ['user']
            ],
            [
                'username' => 'li_hua',
                'name' => '李小華',
                'email' => 'li.hua@example.com',
                'password' => Hash::make('password123'),
                'theme_preference' => 'dark',
                'locale' => 'zh_TW',
                'is_active' => true,
                'roles' => ['admin']
            ],
            [
                'username' => 'chen_wei',
                'name' => '陳小偉',
                'email' => 'chen.wei@example.com',
                'password' => Hash::make('password123'),
                'theme_preference' => 'light',
                'locale' => 'zh_TW',
                'is_active' => false,
                'roles' => ['user']
            ],
        ];

        // 合併所有使用者資料
        $allUsers = array_merge($basicUsers, $testUsers);

        // 建立使用者
        foreach ($allUsers as $userData) {
            $roles = $userData['roles'];
            unset($userData['roles']);

            // 建立或更新使用者
            $user = User::updateOrCreate(
                ['username' => $userData['username']],
                $userData
            );

            // 清除現有角色並重新指派
            $user->roles()->detach();
            
            // 指派角色
            foreach ($roles as $roleName) {
                $role = Role::where('name', $roleName)->first();
                if ($role) {
                    $user->assignRole($role);
                }
            }

            $this->command->info("✓ 使用者: {$user->username} ({$user->name})");
        }

        $this->command->info("已建立 " . count($allUsers) . " 個使用者");
    }

    /**
     * 顯示帳號資訊
     */
    private function displayAccountInfo(): void
    {
        $this->command->info('');
        $this->command->info('=== 測試帳號資訊 ===');
        $this->command->info('');
        
        $this->command->info('🔑 管理員帳號:');
        $this->command->info('  超級管理員: superadmin / password123');
        $this->command->info('  系統管理員: admin / password123');
        $this->command->info('  部門經理: manager / password123');
        $this->command->info('');
        
        $this->command->info('👤 一般使用者:');
        $this->command->info('  啟用使用者: active_user / password123');
        $this->command->info('  停用使用者: inactive_user / password123 (已停用)');
        $this->command->info('  內容編輯: editor / password123');
        $this->command->info('');
        
        $this->command->info('🌐 多語言測試:');
        $this->command->info('  John Doe: john_doe / password123 (英文)');
        $this->command->info('  王小明: wang_ming / password123 (中文)');
        $this->command->info('  李小華: li_hua / password123 (中文)');
        $this->command->info('');
        
        $this->command->info('🔍 搜尋測試:');
        $this->command->info('  可搜尋 "john", "jane", "bob", "王", "李", "陳" 等關鍵字');
        $this->command->info('');
        
        $this->command->info('⚡ 快速重建指令:');
        $this->command->info('  php artisan db:seed --class=DevelopmentSeeder');
        $this->command->info('');
        
        $this->command->warn('⚠️  請在生產環境中修改預設密碼！');
    }
}