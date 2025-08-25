<?php

namespace App\Policies;

use App\Models\Activity;
use App\Models\User;
use Illuminate\Auth\Access\Response;

/**
 * 活動記錄存取政策
 * 
 * 定義活動記錄的存取權限規則
 */
class ActivityPolicy
{
    /**
     * 檢視活動記錄列表權限
     *
     * @param User $user
     * @return Response
     */
    public function viewAny(User $user): Response
    {
        return $user->hasPermission('activity_logs.view')
            ? Response::allow()
            : Response::deny('您沒有權限檢視活動記錄');
    }

    /**
     * 檢視特定活動記錄權限
     *
     * @param User $user
     * @param Activity $activity
     * @return Response
     */
    public function view(User $user, Activity $activity): Response
    {
        // 基本權限檢查
        if (!$user->hasPermission('activity_logs.view')) {
            return Response::deny('您沒有權限檢視活動記錄');
        }

        // 檢查是否為敏感活動記錄
        if ($this->isSensitiveActivity($activity)) {
            if (!$user->hasPermission('security.view')) {
                return Response::deny('您沒有權限檢視敏感活動記錄');
            }
        }

        // 檢查是否為自己的活動記錄（一般使用者只能看自己的）
        if (!$user->hasPermission('security.view') && $activity->user_id !== $user->id) {
            return Response::deny('您只能檢視自己的活動記錄');
        }

        return Response::allow();
    }

    /**
     * 匯出活動記錄權限
     *
     * @param User $user
     * @return Response
     */
    public function export(User $user): Response
    {
        if (!$user->hasPermission('activity_logs.export')) {
            return Response::deny('您沒有權限匯出活動記錄');
        }

        // 記錄匯出操作
        activity()
            ->causedBy($user)
            ->withProperties([
                'action' => 'export_attempt',
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'risk_level' => 5,
            ])
            ->log('activity_export_authorization');

        return Response::allow();
    }

    /**
     * 刪除活動記錄權限
     *
     * @param User $user
     * @param Activity $activity
     * @return Response
     */
    public function delete(User $user, Activity $activity): Response
    {
        if (!$user->hasPermission('activity_logs.delete')) {
            return Response::deny('您沒有權限刪除活動記錄');
        }

        // 檢查是否為受保護的活動記錄
        if ($this->isProtectedActivity($activity)) {
            return Response::deny('此活動記錄受到保護，無法刪除');
        }

        // 記錄刪除嘗試
        activity()
            ->causedBy($user)
            ->performedOn($activity)
            ->withProperties([
                'action' => 'delete_attempt',
                'activity_type' => $activity->type,
                'activity_created_at' => $activity->created_at,
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'risk_level' => 8,
            ])
            ->log('activity_delete_authorization');

        return Response::allow();
    }

    /**
     * 檢視原始資料權限
     *
     * @param User $user
     * @param Activity $activity
     * @return Response
     */
    public function viewRawData(User $user, Activity $activity): Response
    {
        if (!$user->hasPermission('security.audit')) {
            return Response::deny('您沒有權限檢視原始資料');
        }

        // 記錄原始資料存取
        activity()
            ->causedBy($user)
            ->performedOn($activity)
            ->withProperties([
                'action' => 'view_raw_data',
                'activity_type' => $activity->type,
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'risk_level' => 6,
            ])
            ->log('activity_raw_data_access');

        return Response::allow();
    }

    /**
     * 執行完整性檢查權限
     *
     * @param User $user
     * @return Response
     */
    public function performIntegrityCheck(User $user): Response
    {
        if (!$user->hasPermission('security.audit')) {
            return Response::deny('您沒有權限執行完整性檢查');
        }

        // 記錄完整性檢查操作
        activity()
            ->causedBy($user)
            ->withProperties([
                'action' => 'integrity_check_attempt',
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'risk_level' => 4,
            ])
            ->log('activity_integrity_check_authorization');

        return Response::allow();
    }

    /**
     * 檢查是否為敏感活動記錄
     *
     * @param Activity $activity
     * @return bool
     */
    private function isSensitiveActivity(Activity $activity): bool
    {
        $sensitiveTypes = [
            'login_failed',
            'permission_escalation',
            'sensitive_data_access',
            'system_config_change',
            'security_incident',
            'unauthorized_access',
            'data_breach',
            'privilege_abuse'
        ];

        return in_array($activity->type, $sensitiveTypes) || 
               ($activity->risk_level ?? 0) >= 7;
    }

    /**
     * 檢查是否為受保護的活動記錄
     *
     * @param Activity $activity
     * @return bool
     */
    private function isProtectedActivity(Activity $activity): bool
    {
        // 安全相關活動記錄不可刪除
        if ($this->isSensitiveActivity($activity)) {
            return true;
        }

        // 系統管理員操作記錄不可刪除
        if ($activity->type === 'admin_action' || $activity->module === 'system') {
            return true;
        }

        // 30 天內的記錄不可刪除
        if ($activity->created_at->diffInDays(now()) < 30) {
            return true;
        }

        return false;
    }
}