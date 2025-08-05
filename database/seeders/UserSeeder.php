<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\Role;

/**
 * 使用者種子檔案
 * 
 * 建立預設管理員帳號和測試使用者
 */
class UserSeeder extends Seeder
{
    /**
     * 執行使用者種子
     */
    public function run(): void
    {
        // 定義預設使用者
        $users = [
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
            [
                'username' => 'testuser',
                'name' => '測試使用者',
                'email' => 'testuser@example.com',
                'password' => Hash::make('password123'),
                'theme_preference' => 'light',
                'locale' => 'zh_TW',
                'is_active' => true,
                'roles' => ['user']
            ],
            [
                'username' => 'demo',
                'name' => '示範帳號',
                'email' => 'demo@example.com',
                'password' => Hash::make('demo123'),
                'theme_preference' => 'dark',
                'locale' => 'en',
                'is_active' => true,
                'roles' => ['user']
            ],
        ];

        // 建立使用者並指派角色
        foreach ($users as $userData) {
            $roles = $userData['roles'];
            unset($userData['roles']);

            // 建立使用者
            $user = User::firstOrCreate(
                ['username' => $userData['username']],
                $userData
            );

            // 指派角色
            foreach ($roles as $roleName) {
                $role = Role::where('name', $roleName)->first();
                if ($role && !$user->hasRole($roleName)) {
                    $user->assignRole($role);
                }
            }

            $this->command->info("已建立使用者: {$user->username} ({$user->name})");
        }

        // 顯示預設帳號資訊
        $this->command->info('');
        $this->command->info('=== 預設帳號資訊 ===');
        $this->command->info('超級管理員: superadmin / password123');
        $this->command->info('系統管理員: admin / password123');
        $this->command->info('測試使用者: testuser / password123');
        $this->command->info('示範帳號: demo / demo123');
        $this->command->info('');
        $this->command->warn('請在生產環境中修改預設密碼！');
    }
}