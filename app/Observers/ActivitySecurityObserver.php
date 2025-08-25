<?php

namespace App\Observers;

use App\Models\Activity;
use App\Services\ActivityIntegrityService;
use App\Services\SensitiveDataFilter;
use App\Services\ActivitySecurityService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

/**
 * 活動記錄安全觀察者
 * 
 * 監控活動記錄的建立、更新和刪除操作
 * 確保安全控制和完整性保護
 */
class ActivitySecurityObserver
{
    protected ActivityIntegrityService $integrityService;
    protected SensitiveDataFilter $sensitiveDataFilter;
    protected ActivitySecurityService $securityService;

    public function __construct(
        ActivityIntegrityService $integrityService,
        SensitiveDataFilter $sensitiveDataFilter,
        ActivitySecurityService $securityService
    ) {
        $this->integrityService = $integrityService;
        $this->sensitiveDataFilter = $sensitiveDataFilter;
        $this->securityService = $securityService;
    }

    /**
     * 活動記錄建立前的處理
     *
     * @param Activity $activity
     * @return void
     */
    public function creating(Activity $activity): void
    {
        // 1. 過濾敏感資料
        if ($activity->properties) {
            $activity->properties = $this->sensitiveDataFilter->filterProperties($activity->properties);
        }

        // 2. 加密敏感欄位（如果啟用）
        if (config('activity-log.encryption.enabled', false)) {
            $encryptedData = $this->securityService->encryptSensitiveData($activity->toArray());
            $activity->fill($encryptedData);
        }

        // 3. 設定風險等級（如果未設定）
        if (!$activity->risk_level) {
            $activity->risk_level = $this->calculateRiskLevel($activity);
        }

        // 4. 生成數位簽章
        if (config('activity-log.integrity.enabled', true)) {
            $signatureData = $this->prepareSignatureData($activity);
            $activity->signature = $this->integrityService->generateSignature($signatureData);
        }

        // 5. 記錄建立操作
        $this->logActivityCreation($activity);
    }

    /**
     * 活動記錄建立後的處理
     *
     * @param Activity $activity
     * @return void
     */
    public function created(Activity $activity): void
    {
        // 檢查是否為高風險活動
        if (($activity->risk_level ?? 0) >= 7) {
            $this->handleHighRiskActivity($activity);
        }

        // 檢查是否觸發安全警報
        $this->checkSecurityAlerts($activity);

        // 更新統計快取
        $this->updateSecurityStatistics($activity);
    }

    /**
     * 活動記錄更新前的處理
     *
     * @param Activity $activity
     * @return void
     */
    public function updating(Activity $activity): void
    {
        // 檢查是否嘗試篡改受保護的欄位
        $originalData = $activity->getOriginal();
        $tamperingResult = $this->securityService->detectTamperingAttempt($activity, $originalData);

        if ($tamperingResult['tampering_detected']) {
            // 記錄篡改嘗試
            Log::critical('檢測到活動記錄篡改嘗試', [
                'activity_id' => $activity->id,
                'user_id' => Auth::id(),
                'ip_address' => request()->ip(),
                'tampered_fields' => $tamperingResult['tampered_fields'],
                'severity' => $tamperingResult['severity']
            ]);

            // 根據設定決定是否阻止更新
            if (config('activity-log.security.prevent_tampering', true)) {
                throw new \Exception('活動記錄受到保護，無法修改');
            }
        }

        // 重新生成簽章（如果允許更新）
        if (config('activity-log.integrity.enabled', true)) {
            $signatureData = $this->prepareSignatureData($activity);
            $activity->signature = $this->integrityService->generateSignature($signatureData);
        }
    }

    /**
     * 活動記錄更新後的處理
     *
     * @param Activity $activity
     * @return void
     */
    public function updated(Activity $activity): void
    {
        // 記錄更新操作
        $this->logActivityUpdate($activity);

        // 如果是敏感活動記錄的更新，發送警報
        if ($this->isSensitiveActivity($activity)) {
            $this->sendSecurityAlert('activity_record_modified', [
                'activity_id' => $activity->id,
                'activity_type' => $activity->type,
                'modified_by' => Auth::id(),
                'modified_at' => now(),
                'ip_address' => request()->ip()
            ]);
        }
    }

    /**
     * 活動記錄刪除前的處理
     *
     * @param Activity $activity
     * @return void
     */
    public function deleting(Activity $activity): void
    {
        // 檢查是否為受保護的活動記錄
        if ($this->isProtectedActivity($activity)) {
            throw new \Exception('此活動記錄受到保護，無法刪除');
        }

        // 記錄刪除操作
        $this->logActivityDeletion($activity);

        // 備份即將刪除的記錄
        $this->backupActivityBeforeDeletion($activity);
    }

    /**
     * 活動記錄刪除後的處理
     *
     * @param Activity $activity
     * @return void
     */
    public function deleted(Activity $activity): void
    {
        // 發送刪除通知
        $this->sendSecurityAlert('activity_record_deleted', [
            'activity_id' => $activity->id,
            'activity_type' => $activity->type,
            'deleted_by' => Auth::id(),
            'deleted_at' => now(),
            'ip_address' => request()->ip()
        ]);

        // 更新統計
        $this->updateDeletionStatistics($activity);
    }

    /**
     * 計算活動記錄的風險等級
     *
     * @param Activity $activity
     * @return int
     */
    private function calculateRiskLevel(Activity $activity): int
    {
        $riskLevel = 1;

        // 根據活動類型調整風險等級
        $highRiskTypes = [
            'login_failed' => 6,
            'permission_escalation' => 9,
            'sensitive_data_access' => 7,
            'system_config_change' => 8,
            'unauthorized_access' => 10,
            'data_breach' => 10,
            'privilege_abuse' => 9
        ];

        if (isset($highRiskTypes[$activity->type])) {
            $riskLevel = $highRiskTypes[$activity->type];
        }

        // 根據結果調整風險等級
        if ($activity->result === 'failed' || $activity->result === 'error') {
            $riskLevel += 2;
        }

        // 根據時間調整風險等級（非工作時間的活動風險較高）
        $hour = (int) now()->format('H');
        if ($hour < 6 || $hour > 22) {
            $riskLevel += 1;
        }

        // 根據 IP 位址調整風險等級
        if ($this->isSuspiciousIp($activity->ip_address)) {
            $riskLevel += 3;
        }

        return min($riskLevel, 10);
    }

    /**
     * 準備用於簽章的資料
     *
     * @param Activity $activity
     * @return array
     */
    private function prepareSignatureData(Activity $activity): array
    {
        return [
            'type' => $activity->type,
            'description' => $activity->description,
            'user_id' => $activity->user_id,
            'subject_id' => $activity->subject_id,
            'subject_type' => $activity->subject_type,
            'properties' => $activity->properties,
            'ip_address' => $activity->ip_address,
            'user_agent' => $activity->user_agent,
            'result' => $activity->result,
            'risk_level' => $activity->risk_level,
            'created_at' => $activity->created_at ?? now()
        ];
    }

    /**
     * 記錄活動記錄的建立
     *
     * @param Activity $activity
     * @return void
     */
    private function logActivityCreation(Activity $activity): void
    {
        Log::info('活動記錄建立', [
            'activity_type' => $activity->type,
            'user_id' => $activity->user_id,
            'risk_level' => $activity->risk_level,
            'ip_address' => $activity->ip_address,
            'has_signature' => !empty($activity->signature)
        ]);
    }

    /**
     * 處理高風險活動
     *
     * @param Activity $activity
     * @return void
     */
    private function handleHighRiskActivity(Activity $activity): void
    {
        // 發送即時警報
        $this->sendSecurityAlert('high_risk_activity_detected', [
            'activity_id' => $activity->id,
            'activity_type' => $activity->type,
            'risk_level' => $activity->risk_level,
            'user_id' => $activity->user_id,
            'ip_address' => $activity->ip_address,
            'detected_at' => now()
        ]);

        // 記錄到安全日誌
        Log::warning('檢測到高風險活動', [
            'activity_id' => $activity->id,
            'type' => $activity->type,
            'risk_level' => $activity->risk_level,
            'user_id' => $activity->user_id,
            'ip_address' => $activity->ip_address
        ]);
    }

    /**
     * 檢查安全警報觸發條件
     *
     * @param Activity $activity
     * @return void
     */
    private function checkSecurityAlerts(Activity $activity): void
    {
        // 檢查登入失敗次數
        if ($activity->type === 'login_failed') {
            $this->checkFailedLoginAttempts($activity);
        }

        // 檢查異常 IP 存取
        if ($this->isSuspiciousIp($activity->ip_address)) {
            $this->sendSecurityAlert('suspicious_ip_access', [
                'ip_address' => $activity->ip_address,
                'activity_type' => $activity->type,
                'user_id' => $activity->user_id
            ]);
        }

        // 檢查權限提升操作
        if ($activity->type === 'permission_escalation') {
            $this->sendSecurityAlert('privilege_escalation_detected', [
                'activity_id' => $activity->id,
                'user_id' => $activity->user_id,
                'target_user' => $activity->subject_id
            ]);
        }
    }

    /**
     * 其他輔助方法
     */
    private function updateSecurityStatistics(Activity $activity): void
    {
        // 實作統計更新邏輯
    }

    private function logActivityUpdate(Activity $activity): void
    {
        Log::info('活動記錄更新', [
            'activity_id' => $activity->id,
            'updated_by' => Auth::id(),
            'ip_address' => request()->ip()
        ]);
    }

    private function logActivityDeletion(Activity $activity): void
    {
        Log::warning('活動記錄刪除', [
            'activity_id' => $activity->id,
            'activity_type' => $activity->type,
            'deleted_by' => Auth::id(),
            'ip_address' => request()->ip()
        ]);
    }

    private function backupActivityBeforeDeletion(Activity $activity): void
    {
        // 實作備份邏輯
    }

    private function sendSecurityAlert(string $type, array $data): void
    {
        // 實作安全警報發送邏輯
    }

    private function updateDeletionStatistics(Activity $activity): void
    {
        // 實作刪除統計更新邏輯
    }

    private function isSensitiveActivity(Activity $activity): bool
    {
        $sensitiveTypes = [
            'login_failed', 'permission_escalation', 'sensitive_data_access',
            'system_config_change', 'security_incident', 'unauthorized_access'
        ];

        return in_array($activity->type, $sensitiveTypes) || ($activity->risk_level ?? 0) >= 7;
    }

    private function isProtectedActivity(Activity $activity): bool
    {
        // 安全相關活動記錄不可刪除
        if ($this->isSensitiveActivity($activity)) {
            return true;
        }

        // 30 天內的記錄不可刪除
        if ($activity->created_at->diffInDays(now()) < 30) {
            return true;
        }

        return false;
    }

    private function isSuspiciousIp(string $ip): bool
    {
        // 實作可疑 IP 檢測邏輯
        $suspiciousIps = config('activity-log.security.suspicious_ips', []);
        return in_array($ip, $suspiciousIps);
    }

    private function checkFailedLoginAttempts(Activity $activity): void
    {
        // 實作登入失敗檢查邏輯
    }
}