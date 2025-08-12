<?php

namespace Database\Seeders;

use App\Models\Activity;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class ActivitySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = User::all();
        
        if ($users->isEmpty()) {
            $this->command->warn('沒有找到使用者，請先執行 UserSeeder');
            return;
        }
        
        $events = ['created', 'updated', 'deleted', 'login', 'logout', 'password_changed', 'role_assigned', 'permission_granted'];
        $types = [
            Activity::TYPE_LOGIN,
            Activity::TYPE_LOGOUT,
            Activity::TYPE_CREATE_USER,
            Activity::TYPE_UPDATE_USER,
            Activity::TYPE_DELETE_USER,
            Activity::TYPE_CREATE_ROLE,
            Activity::TYPE_UPDATE_ROLE,
            Activity::TYPE_ASSIGN_ROLE,
            Activity::TYPE_VIEW_DASHBOARD,
            Activity::TYPE_QUICK_ACTION
        ];
        
        $descriptions = [
            '登入系統',
            '登出系統',
            '建立新使用者',
            '更新使用者資料',
            '刪除使用者',
            '建立新角色',
            '更新角色權限',
            '指派角色給使用者',
            '查看儀表板',
            '執行快速操作',
            '變更密碼',
            '更新個人資料',
            '匯出資料',
            '檢視使用者列表',
            '修改系統設定'
        ];
        
        // 建立過去 30 天的活動記錄
        for ($i = 0; $i < 200; $i++) {
            $user = $users->random();
            $event = $events[array_rand($events)];
            $type = $types[array_rand($types)];
            $description = $descriptions[array_rand($descriptions)];
            
            // 隨機生成過去 30 天內的時間
            $createdAt = Carbon::now()->subDays(rand(0, 30))->subHours(rand(0, 23))->subMinutes(rand(0, 59));
            
            Activity::create([
                'type' => $type,
                'event' => $event,
                'description' => $description,
                'module' => $this->getRandomModule(),
                'user_id' => $user->id,
                'subject_id' => rand(1, 100),
                'subject_type' => $this->getRandomSubjectType(),
                'properties' => [
                    'ip_address' => $this->getRandomIp(),
                    'user_agent' => $this->getRandomUserAgent(),
                    'additional_data' => [
                        'action' => $event,
                        'timestamp' => $createdAt->timestamp
                    ]
                ],
                'ip_address' => $this->getRandomIp(),
                'user_agent' => $this->getRandomUserAgent(),
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
            ]);
        }
        
        $this->command->info('已建立 200 筆活動記錄');
    }
    
    /**
     * 取得隨機模組名稱
     */
    private function getRandomModule(): string
    {
        $modules = [
            Activity::MODULE_AUTH,
            Activity::MODULE_USERS,
            Activity::MODULE_ROLES,
            Activity::MODULE_PERMISSIONS,
            Activity::MODULE_DASHBOARD,
            Activity::MODULE_SYSTEM
        ];
        
        return $modules[array_rand($modules)];
    }
    
    /**
     * 取得隨機主體類型
     */
    private function getRandomSubjectType(): ?string
    {
        $types = [
            'App\Models\User',
            'App\Models\Role',
            'App\Models\Permission',
            null
        ];
        
        return $types[array_rand($types)];
    }
    
    /**
     * 取得隨機 IP 位址
     */
    private function getRandomIp(): string
    {
        return rand(1, 255) . '.' . rand(1, 255) . '.' . rand(1, 255) . '.' . rand(1, 255);
    }
    
    /**
     * 取得隨機使用者代理
     */
    private function getRandomUserAgent(): string
    {
        $userAgents = [
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
            'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
            'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:89.0) Gecko/20100101 Firefox/89.0',
            'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/14.1.1 Safari/605.1.15'
        ];
        
        return $userAgents[array_rand($userAgents)];
    }
}
