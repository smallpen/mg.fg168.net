<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Activity;
use App\Models\User;

class ActivityTestSeeder extends Seeder
{
    /**
     * 執行資料庫種子
     *
     * @return void
     */
    public function run()
    {
        $user = User::first();
        
        if (!$user) {
            $this->command->info('沒有找到使用者，請先建立使用者');
            return;
        }

        // 建立測試活動記錄
        $activities = [
            [
                'type' => 'login',
                'description' => '使用者登入系統',
                'module' => 'auth',
                'user_id' => $user->id,
                'ip_address' => '127.0.0.1',
                'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
                'created_at' => now()->subMinutes(30)
            ],
            [
                'type' => 'quick_action',
                'description' => '使用快速操作：建立使用者',
                'module' => 'dashboard',
                'user_id' => $user->id,
                'properties' => json_encode([
                    'route' => 'admin.users.create',
                    'action_title' => '建立使用者'
                ]),
                'ip_address' => '127.0.0.1',
                'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
                'created_at' => now()->subMinutes(25)
            ],
            [
                'type' => 'create_user',
                'description' => '建立新使用者：測試使用者',
                'module' => 'users',
                'user_id' => $user->id,
                'subject_id' => $user->id,
                'subject_type' => User::class,
                'properties' => json_encode([
                    'created_username' => 'testuser',
                    'created_name' => '測試使用者',
                    'created_email' => 'test@example.com'
                ]),
                'ip_address' => '127.0.0.1',
                'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
                'created_at' => now()->subMinutes(20)
            ],
            [
                'type' => 'quick_action',
                'description' => '使用快速操作：管理使用者',
                'module' => 'dashboard',
                'user_id' => $user->id,
                'properties' => json_encode([
                    'route' => 'admin.users.index',
                    'action_title' => '管理使用者'
                ]),
                'ip_address' => '127.0.0.1',
                'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
                'created_at' => now()->subMinutes(15)
            ],
            [
                'type' => 'view_dashboard',
                'description' => '檢視儀表板',
                'module' => 'dashboard',
                'user_id' => $user->id,
                'ip_address' => '127.0.0.1',
                'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
                'created_at' => now()->subMinutes(10)
            ],
            [
                'type' => 'update_user',
                'description' => '更新使用者：' . $user->name,
                'module' => 'users',
                'user_id' => $user->id,
                'subject_id' => $user->id,
                'subject_type' => User::class,
                'properties' => json_encode([
                    'updated_username' => $user->username,
                    'changes' => ['name' => ['舊名稱', $user->name]]
                ]),
                'ip_address' => '127.0.0.1',
                'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
                'created_at' => now()->subMinutes(5)
            ],
            [
                'type' => 'quick_action',
                'description' => '使用快速操作：建立角色',
                'module' => 'dashboard',
                'user_id' => $user->id,
                'properties' => json_encode([
                    'route' => 'admin.roles.create',
                    'action_title' => '建立角色'
                ]),
                'ip_address' => '127.0.0.1',
                'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
                'created_at' => now()->subMinutes(2)
            ]
        ];

        foreach ($activities as $activity) {
            Activity::create($activity);
        }

        $this->command->info('測試活動記錄建立完成！');
    }
}