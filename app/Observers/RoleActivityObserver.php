<?php

namespace App\Observers;

use App\Models\Role;
use App\Services\ActivityLogger;

/**
 * 角色活動觀察者
 * 
 * 專門記錄角色相關的操作活動
 */
class RoleActivityObserver
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
     * 角色建立後的處理
     *
     * @param Role $role
     * @return void
     */
    public function created(Role $role): void
    {
        $this->activityLogger->log(
            'role_created',
            "建立新角色：{$role->display_name}",
            [
                'module' => 'roles',
                'subject_id' => $role->id,
                'subject_type' => Role::class,
                'properties' => [
                    'name' => $role->name,
                    'display_name' => $role->display_name,
                    'description' => $role->description,
                    'is_system' => $role->is_system ?? false,
                ],
                'result' => 'success',
                'risk_level' => 4,
            ]
        );
    }

    /**
     * 角色更新後的處理
     *
     * @param Role $role
     * @return void
     */
    public function updated(Role $role): void
    {
        $changes = $role->getChanges();
        
        if (!empty($changes)) {
            $riskLevel = $this->calculateUpdateRiskLevel($changes, $role);
            
            $this->activityLogger->log(
                'role_updated',
                "更新角色：{$role->display_name}",
                [
                    'module' => 'roles',
                    'subject_id' => $role->id,
                    'subject_type' => Role::class,
                    'properties' => [
                        'changes' => $changes,
                        'fields_changed' => array_keys($changes),
                        'role_name' => $role->name,
                        'is_system_role' => $role->is_system ?? false,
                    ],
                    'result' => 'success',
                    'risk_level' => $riskLevel,
                ]
            );
        }
    }

    /**
     * 角色刪除後的處理
     *
     * @param Role $role
     * @return void
     */
    public function deleted(Role $role): void
    {
        $usersCount = $role->users()->count();
        
        $this->activityLogger->log(
            'role_deleted',
            "刪除角色：{$role->display_name}",
            [
                'module' => 'roles',
                'subject_id' => $role->id,
                'subject_type' => Role::class,
                'properties' => [
                    'name' => $role->name,
                    'display_name' => $role->display_name,
                    'affected_users_count' => $usersCount,
                    'is_system_role' => $role->is_system ?? false,
                ],
                'result' => 'success',
                'risk_level' => $this->calculateDeleteRiskLevel($role, $usersCount),
            ]
        );
    }

    /**
     * 角色強制刪除後的處理
     *
     * @param Role $role
     * @return void
     */
    public function forceDeleted(Role $role): void
    {
        $this->activityLogger->log(
            'role_force_deleted',
            "永久刪除角色：{$role->display_name}",
            [
                'module' => 'roles',
                'subject_id' => $role->id,
                'subject_type' => Role::class,
                'properties' => [
                    'name' => $role->name,
                    'display_name' => $role->display_name,
                    'permanent_delete' => true,
                    'is_system_role' => $role->is_system ?? false,
                ],
                'result' => 'success',
                'risk_level' => 9,
            ]
        );
    }

    /**
     * 角色還原後的處理
     *
     * @param Role $role
     * @return void
     */
    public function restored(Role $role): void
    {
        $this->activityLogger->log(
            'role_restored',
            "還原角色：{$role->display_name}",
            [
                'module' => 'roles',
                'subject_id' => $role->id,
                'subject_type' => Role::class,
                'properties' => [
                    'name' => $role->name,
                    'display_name' => $role->display_name,
                    'is_system_role' => $role->is_system ?? false,
                ],
                'result' => 'success',
                'risk_level' => 5,
            ]
        );
    }

    /**
     * 計算更新操作的風險等級
     *
     * @param array $changes
     * @param Role $role
     * @return int
     */
    protected function calculateUpdateRiskLevel(array $changes, Role $role): int
    {
        $baseRisk = 3;
        
        // 系統角色的變更風險較高
        if ($role->is_system ?? false) {
            $baseRisk += 2;
        }
        
        // 高風險欄位變更
        $highRiskFields = ['name'];
        $mediumRiskFields = ['display_name', 'description'];
        
        foreach ($changes as $field => $value) {
            if (in_array($field, $highRiskFields)) {
                $baseRisk += 2;
            } elseif (in_array($field, $mediumRiskFields)) {
                $baseRisk += 1;
            }
        }

        return min($baseRisk, 10);
    }

    /**
     * 計算刪除操作的風險等級
     *
     * @param Role $role
     * @param int $usersCount
     * @return int
     */
    protected function calculateDeleteRiskLevel(Role $role, int $usersCount): int
    {
        $baseRisk = 5;
        
        // 系統角色刪除風險極高
        if ($role->is_system ?? false) {
            $baseRisk += 4;
        }
        
        // 根據影響的使用者數量調整風險
        if ($usersCount > 10) {
            $baseRisk += 2;
        } elseif ($usersCount > 0) {
            $baseRisk += 1;
        }

        return min($baseRisk, 10);
    }
}