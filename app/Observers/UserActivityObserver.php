<?php

namespace App\Observers;

use App\Models\User;
use App\Services\ActivityLogger;

/**
 * 使用者活動觀察者
 * 
 * 專門記錄使用者相關的操作活動
 */
class UserActivityObserver
{
    /**
     * 活動記錄服務
     *
     * @var ActivityLogger
     */
    protected ActivityLogger $activityLogger;

    /**
     * 建構函式
     *
     * @param ActivityLogger $activityLogger
     */
    public function __construct(ActivityLogger $activityLogger)
    {
        $this->activityLogger = $activityLogger;
    }

    /**
     * 使用者建立後的處理
     *
     * @param User $user
     * @return void
     */
    public function created(User $user): void
    {
        $this->activityLogger->log(
            'user_created',
            "建立新使用者：{$user->display_name}",
            [
                'module' => 'users',
                'subject_id' => $user->id,
                'subject_type' => User::class,
                'properties' => [
                    'username' => $user->username,
                    'name' => $user->name,
                    'email' => $user->email,
                    'is_active' => $user->is_active,
                ],
                'result' => 'success',
                'risk_level' => 3,
            ]
        );
    }

    /**
     * 使用者更新後的處理
     *
     * @param User $user
     * @return void
     */
    public function updated(User $user): void
    {
        $changes = $user->getChanges();
        
        // 過濾敏感欄位
        if (isset($changes['password'])) {
            $changes['password'] = '[FILTERED]';
        }
        
        if (isset($changes['remember_token'])) {
            unset($changes['remember_token']);
        }

        if (!empty($changes)) {
            $riskLevel = $this->calculateUpdateRiskLevel($changes);
            
            $this->activityLogger->log(
                'user_updated',
                "更新使用者：{$user->display_name}",
                [
                    'module' => 'users',
                    'subject_id' => $user->id,
                    'subject_type' => User::class,
                    'properties' => [
                        'changes' => $changes,
                        'fields_changed' => array_keys($user->getChanges()),
                        'username' => $user->username,
                    ],
                    'result' => 'success',
                    'risk_level' => $riskLevel,
                ]
            );
        }
    }

    /**
     * 使用者刪除後的處理
     *
     * @param User $user
     * @return void
     */
    public function deleted(User $user): void
    {
        $this->activityLogger->log(
            'user_deleted',
            "刪除使用者：{$user->display_name}",
            [
                'module' => 'users',
                'subject_id' => $user->id,
                'subject_type' => User::class,
                'properties' => [
                    'username' => $user->username,
                    'name' => $user->name,
                    'email' => $user->email,
                    'soft_delete' => true,
                ],
                'result' => 'success',
                'risk_level' => 6,
            ]
        );
    }

    /**
     * 使用者強制刪除後的處理
     *
     * @param User $user
     * @return void
     */
    public function forceDeleted(User $user): void
    {
        $this->activityLogger->log(
            'user_force_deleted',
            "永久刪除使用者：{$user->display_name}",
            [
                'module' => 'users',
                'subject_id' => $user->id,
                'subject_type' => User::class,
                'properties' => [
                    'username' => $user->username,
                    'name' => $user->name,
                    'email' => $user->email,
                    'permanent_delete' => true,
                ],
                'result' => 'success',
                'risk_level' => 9,
            ]
        );
    }

    /**
     * 使用者還原後的處理
     *
     * @param User $user
     * @return void
     */
    public function restored(User $user): void
    {
        $this->activityLogger->log(
            'user_restored',
            "還原使用者：{$user->display_name}",
            [
                'module' => 'users',
                'subject_id' => $user->id,
                'subject_type' => User::class,
                'properties' => [
                    'username' => $user->username,
                    'name' => $user->name,
                    'email' => $user->email,
                ],
                'result' => 'success',
                'risk_level' => 4,
            ]
        );
    }

    /**
     * 計算更新操作的風險等級
     *
     * @param array $changes
     * @return int
     */
    protected function calculateUpdateRiskLevel(array $changes): int
    {
        $baseRisk = 2;
        
        // 高風險欄位變更
        $highRiskFields = ['password', 'email', 'is_active'];
        $mediumRiskFields = ['name', 'username'];
        
        foreach ($changes as $field => $value) {
            if (in_array($field, $highRiskFields)) {
                $baseRisk += 3;
            } elseif (in_array($field, $mediumRiskFields)) {
                $baseRisk += 1;
            }
        }

        // 特殊情況：停用使用者
        if (isset($changes['is_active']) && !$changes['is_active']) {
            $baseRisk += 2;
        }

        return min($baseRisk, 10);
    }
}