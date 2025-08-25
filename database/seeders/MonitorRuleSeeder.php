<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class MonitorRuleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $adminUser = \App\Models\User::where('username', 'admin')->first();
        
        if (!$adminUser) {
            $this->command->warn('Admin user not found. Skipping monitor rule seeding.');
            return;
        }

        $rules = [
            [
                'name' => '登入失敗監控',
                'description' => '監控連續登入失敗嘗試，當同一 IP 在短時間內多次登入失敗時觸發警報',
                'conditions' => [
                    [
                        'field' => 'type',
                        'operator' => '=',
                        'value' => 'login_failed'
                    ]
                ],
                'actions' => [
                    [
                        'type' => 'create_alert',
                        'severity' => 'high',
                        'title' => '檢測到登入失敗',
                        'description' => '使用者嘗試登入失敗，可能存在暴力破解攻擊'
                    ]
                ],
                'priority' => 5,
                'is_active' => true,
                'created_by' => $adminUser->id
            ],
            [
                'name' => '權限提升監控',
                'description' => '監控權限提升操作，包括角色指派、權限變更等敏感操作',
                'conditions' => [
                    [
                        'field' => 'description',
                        'operator' => 'contains',
                        'value' => '權限'
                    ],
                    [
                        'field' => 'risk_level',
                        'operator' => '>',
                        'value' => 3
                    ]
                ],
                'actions' => [
                    [
                        'type' => 'create_alert',
                        'severity' => 'high',
                        'title' => '檢測到權限提升操作',
                        'description' => '使用者執行了權限相關的敏感操作'
                    ]
                ],
                'priority' => 4,
                'is_active' => true,
                'created_by' => $adminUser->id
            ],
            [
                'name' => '異常 IP 監控',
                'description' => '監控來自異常 IP 的存取，檢測可能的外部攻擊',
                'conditions' => [
                    [
                        'field' => 'ip_address',
                        'operator' => 'not_contains',
                        'value' => '192.168.'
                    ]
                ],
                'actions' => [
                    [
                        'type' => 'create_alert',
                        'severity' => 'medium',
                        'title' => '檢測到異常 IP 存取',
                        'description' => '來自非內網 IP 的存取活動'
                    ]
                ],
                'priority' => 3,
                'is_active' => false, // 預設停用，避免過多警報
                'created_by' => $adminUser->id
            ],
            [
                'name' => '批量操作監控',
                'description' => '監控短時間內的大量操作，檢測可能的自動化攻擊或異常行為',
                'conditions' => [
                    [
                        'field' => 'type',
                        'operator' => 'in',
                        'value' => ['user_created', 'user_deleted', 'role_created', 'role_deleted']
                    ]
                ],
                'actions' => [
                    [
                        'type' => 'create_alert',
                        'severity' => 'medium',
                        'title' => '檢測到批量操作',
                        'description' => '使用者在短時間內執行了大量操作'
                    ]
                ],
                'priority' => 2,
                'is_active' => true,
                'created_by' => $adminUser->id
            ],
            [
                'name' => '非工作時間活動監控',
                'description' => '監控非工作時間的系統活動，檢測可能的異常存取',
                'conditions' => [
                    [
                        'field' => 'created_at',
                        'operator' => 'time_range',
                        'value' => ['22:00', '06:00'] // 晚上10點到早上6點
                    ]
                ],
                'actions' => [
                    [
                        'type' => 'create_alert',
                        'severity' => 'low',
                        'title' => '檢測到非工作時間活動',
                        'description' => '在非工作時間檢測到系統活動'
                    ]
                ],
                'priority' => 1,
                'is_active' => false, // 預設停用
                'created_by' => $adminUser->id
            ],
            [
                'name' => '高風險操作監控',
                'description' => '監控高風險操作，如刪除使用者、修改系統設定等',
                'conditions' => [
                    [
                        'field' => 'risk_level',
                        'operator' => '>=',
                        'value' => 7
                    ]
                ],
                'actions' => [
                    [
                        'type' => 'create_alert',
                        'severity' => 'critical',
                        'title' => '檢測到高風險操作',
                        'description' => '執行了高風險等級的系統操作'
                    ]
                ],
                'priority' => 5,
                'is_active' => true,
                'created_by' => $adminUser->id
            ]
        ];

        foreach ($rules as $ruleData) {
            \App\Models\MonitorRule::updateOrCreate(
                ['name' => $ruleData['name']],
                $ruleData
            );
        }

        $this->command->info('Monitor rules seeded successfully.');
    }
}
