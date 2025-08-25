<?php

namespace Database\Seeders;

use App\Models\ActivityRetentionPolicy;
use Illuminate\Database\Seeder;

/**
 * 活動記錄保留政策種子資料
 */
class ActivityRetentionPolicySeeder extends Seeder
{
    /**
     * 執行種子資料建立
     */
    public function run(): void
    {
        // 建立預設保留政策
        ActivityRetentionPolicy::createDefaultPolicies();

        // 建立額外的特定政策
        $additionalPolicies = [
            [
                'name' => '登入失敗記錄',
                'activity_type' => 'login_failed',
                'module' => 'auth',
                'retention_days' => 180,
                'action' => ActivityRetentionPolicy::ACTION_ARCHIVE,
                'priority' => 8,
                'description' => '登入失敗記錄保留 6 個月後歸檔',
                'is_active' => true,
            ],
            [
                'name' => '權限變更記錄',
                'activity_type' => null,
                'module' => 'permissions',
                'retention_days' => 730,
                'action' => ActivityRetentionPolicy::ACTION_ARCHIVE,
                'priority' => 7,
                'description' => '權限相關操作記錄保留 2 年後歸檔',
                'is_active' => true,
            ],
            [
                'name' => '使用者管理記錄',
                'activity_type' => null,
                'module' => 'users',
                'retention_days' => 365,
                'action' => ActivityRetentionPolicy::ACTION_ARCHIVE,
                'priority' => 6,
                'description' => '使用者管理操作記錄保留 1 年後歸檔',
                'is_active' => true,
            ],
            [
                'name' => '儀表板檢視記錄',
                'activity_type' => 'view_dashboard',
                'module' => 'dashboard',
                'retention_days' => 30,
                'action' => ActivityRetentionPolicy::ACTION_DELETE,
                'priority' => 2,
                'description' => '儀表板檢視記錄保留 30 天後刪除',
                'is_active' => true,
            ],
            [
                'name' => '高風險活動記錄',
                'activity_type' => null,
                'module' => null,
                'retention_days' => 1825, // 5 年
                'action' => ActivityRetentionPolicy::ACTION_ARCHIVE,
                'priority' => 15,
                'conditions' => ['risk_level' => ['>=', 8]],
                'description' => '高風險活動記錄（風險等級 >= 8）保留 5 年後歸檔',
                'is_active' => true,
            ],
            [
                'name' => '測試環境記錄清理',
                'activity_type' => null,
                'module' => null,
                'retention_days' => 7,
                'action' => ActivityRetentionPolicy::ACTION_DELETE,
                'priority' => 0,
                'conditions' => ['ip_address' => ['like', '127.0.0.1']],
                'description' => '測試環境記錄保留 7 天後刪除',
                'is_active' => false, // 預設停用
            ],
        ];

        foreach ($additionalPolicies as $policyData) {
            ActivityRetentionPolicy::firstOrCreate(
                ['name' => $policyData['name']],
                $policyData
            );
        }

        $this->command->info('活動記錄保留政策種子資料建立完成');
    }
}