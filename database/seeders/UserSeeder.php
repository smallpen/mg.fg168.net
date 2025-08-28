<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\Role;

/**
 * 使用者種子檔案
 * 
 * 建立系統必要的預設管理員帳號
 * 確保系統部署後可以立即使用
 */
class UserSeeder extends Seeder
{
    /**
     * 執行使用者種子
     */
    public function run(): void
    {
        // 定義系統必要的預設使用者
        $users = [
            [
                'username' => 'admin',
                'name' => '系統管理員',
                'email' => 'admin@system.local',
                'password' => Hash::make('admin123'),
                'theme_preference' => 'light',
                'locale' => 'zh_TW',
                'is_active' => true,
                'email_verified_at' => now(),
                'roles' => ['admin'],
                'description' => '系統預設管理員帳號，擁有完整管理權限'
            ],
        ];

        // 建立使用者並指派角色
        foreach ($users as $userData) {
            $roles = $userData['roles'];
            $description = $userData['description'] ?? '';
            unset($userData['roles'], $userData['description']);

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

            $this->command->info("✓ 已建立使用者: {$user->username} ({$user->name})");
            if ($description) {
                $this->command->line("  說明: {$description}");
            }
        }

        // 顯示系統部署資訊
        $this->displayDeploymentInfo();
    }

    /**
     * 顯示系統部署資訊
     */
    private function displayDeploymentInfo(): void
    {
        $this->command->info('');
        $this->command->info('=== 系統部署完成 ===');
        $this->command->info('預設管理員帳號: admin');
        $this->command->info('預設密碼: admin123');
        $this->command->info('登入網址: /admin/login');
        $this->command->info('');
        $this->command->warn('⚠️  重要提醒:');
        $this->command->warn('   1. 請立即登入系統並修改預設密碼');
        $this->command->warn('   2. 建議建立專屬的管理員帳號');
        $this->command->warn('   3. 在生產環境中請停用或刪除預設帳號');
        $this->command->info('');
        $this->command->info('系統現在可以正常使用了！');
    }
}