<?php

namespace Database\Seeders;

use App\Models\NotificationRule;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class NotificationRuleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 取得第一個管理員使用者作為建立者
        $admin = User::whereHas('roles', function ($query) {
            $query->whereIn('name', ['super_admin', 'admin']);
        })->first();

        if (!$admin) {
            $this->command->warn('找不到管理員使用者，跳過通知規則建立');
            return;
        }

        $rules = [
            [
                'name' => '高風險安全事件警報',
                'description' => '當發生高風險安全事件時立即通知所有管理員',
                'conditions' => [
                    'activity_types' => ['security', 'login', 'delete'],
                    'min_risk_level' => 7,
                ],
                'actions' => [
                    'recipients' => [
                        ['type' => 'all_admins']
                    ],
                    'title_template' => '🚨 高風險安全事件：{activity_type}',
                    'message_template' => '偵測到高風險活動：使用者 {user_name} 在 {time} 從 IP {ip_address} 執行了 {activity_type} 操作。詳細描述：{description}',
                    'merge_similar' => true,
                    'merge_window' => 300,
                    'actions' => [
                        ['type' => 'email', 'template' => 'security_alert'],
                        ['type' => 'browser'],
                        ['type' => 'security_alert', 'severity' => 'high']
                    ]
                ],
                'is_active' => true,
                'priority' => 4, // 緊急
                'created_by' => $admin->id,
            ],
            [
                'name' => '登入失敗監控',
                'description' => '監控登入失敗嘗試，防止暴力破解攻擊',
                'conditions' => [
                    'activity_types' => ['login'],
                    'min_risk_level' => 5,
                ],
                'actions' => [
                    'recipients' => [
                        ['type' => 'all_admins']
                    ],
                    'title_template' => '⚠️ 登入失敗警報',
                    'message_template' => '偵測到可疑登入嘗試：IP {ip_address} 在 {time} 嘗試登入失敗。使用者：{user_name}',
                    'merge_similar' => true,
                    'merge_window' => 600,
                    'actions' => [
                        ['type' => 'email', 'template' => 'login_failure_alert'],
                        ['type' => 'browser']
                    ]
                ],
                'is_active' => true,
                'priority' => 3, // 高
                'created_by' => $admin->id,
            ],
            [
                'name' => '系統管理操作通知',
                'description' => '通知重要的系統管理操作，如使用者建立、角色變更等',
                'conditions' => [
                    'activity_types' => ['create', 'update', 'delete'],
                    'min_risk_level' => 3,
                ],
                'actions' => [
                    'recipients' => [
                        ['type' => 'all_admins']
                    ],
                    'title_template' => '📋 系統管理操作：{activity_type}',
                    'message_template' => '系統管理操作通知：{user_name} 在 {time} 執行了 {activity_type} 操作。操作描述：{description}',
                    'merge_similar' => true,
                    'merge_window' => 1800,
                    'actions' => [
                        ['type' => 'browser']
                    ]
                ],
                'is_active' => true,
                'priority' => 2, // 一般
                'created_by' => $admin->id,
            ],
            [
                'name' => '異常 IP 存取監控',
                'description' => '監控來自異常 IP 位址的存取嘗試',
                'conditions' => [
                    'ip_patterns' => ['10.0.0.*', '172.16.*.*'],
                    'min_risk_level' => 4,
                ],
                'actions' => [
                    'recipients' => [
                        ['type' => 'all_admins']
                    ],
                    'title_template' => '🌐 異常 IP 存取警報',
                    'message_template' => '偵測到來自異常 IP 的存取：{ip_address} 在 {time} 執行了 {activity_type} 操作。使用者：{user_name}',
                    'merge_similar' => true,
                    'merge_window' => 900,
                    'actions' => [
                        ['type' => 'email', 'template' => 'suspicious_ip_alert'],
                        ['type' => 'security_alert', 'severity' => 'warning']
                    ]
                ],
                'is_active' => false, // 預設停用，需要根據實際環境調整 IP 模式
                'priority' => 3, // 高
                'created_by' => $admin->id,
            ],
            [
                'name' => '工作時間外活動監控',
                'description' => '監控非工作時間的系統活動',
                'conditions' => [
                    'activity_types' => ['login', 'create', 'update', 'delete'],
                    'time_range' => [
                        'hours' => [0, 1, 2, 3, 4, 5, 6, 22, 23], // 晚上 10 點到早上 7 點
                    ],
                    'min_risk_level' => 2,
                ],
                'actions' => [
                    'recipients' => [
                        ['type' => 'all_admins']
                    ],
                    'title_template' => '🌙 非工作時間活動通知',
                    'message_template' => '非工作時間活動：{user_name} 在 {time} 執行了 {activity_type} 操作。請注意是否為正常操作。',
                    'merge_similar' => true,
                    'merge_window' => 3600,
                    'actions' => [
                        ['type' => 'email', 'template' => 'after_hours_activity']
                    ]
                ],
                'is_active' => true,
                'priority' => 2, // 一般
                'created_by' => $admin->id,
            ],
        ];

        foreach ($rules as $ruleData) {
            NotificationRule::create($ruleData);
        }

        $this->command->info('已建立 ' . count($rules) . ' 個預設通知規則');
    }
}
