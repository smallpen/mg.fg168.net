<?php

namespace App\Services;

use App\Models\Activity;
use App\Models\User;
use App\Services\ActivityIntegrityService;
use App\Services\SensitiveDataFilter;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

/**
 * 活動記錄安全服務
 * 
 * 提供活動記錄的安全控制、審計和監控功能
 */
class ActivitySecurityService
{
    protected ActivityIntegrityService $integrityService;
    protected SensitiveDataFilter $sensitiveDataFilter;

    public function __construct(
        ActivityIntegrityService $integrityService,
        SensitiveDataFilter $sensitiveDataFilter
    ) {
        $this->integrityService = $integrityService;
        $this->sensitiveDataFilter = $sensitiveDataFilter;
    }

    /**
     * 檢查使用者對活動記錄的存取權限
     *
     * @param User $user
     * @param string $action
     * @param Activity|null $activity
     * @return array
     */
    public function checkAccessPermission(User $user, string $action, ?Activity $activity = null): array
    {
        $result = [
            'allowed' => false,
            'reason' => '',
            'risk_level' => 0,
            'restrictions' => []
        ];

        // 基本權限檢查
        $requiredPermission = $this->getRequiredPermission($action);
        if (!$user->hasPermission($requiredPermission)) {
            $result['reason'] = "缺少必要權限: {$requiredPermission}";
            $result['risk_level'] = 8;
            return $result;
        }

        // 特定活動記錄的額外檢查
        if ($activity) {
            $activityCheck = $this->checkActivitySpecificAccess($user, $action, $activity);
            if (!$activityCheck['allowed']) {
                return $activityCheck;
            }
            $result['restrictions'] = array_merge($result['restrictions'], $activityCheck['restrictions']);
        }

        // 時間限制檢查
        $timeCheck = $this->checkTimeRestrictions($user, $action);
        if (!$timeCheck['allowed']) {
            return $timeCheck;
        }

        // IP 位址檢查
        $ipCheck = $this->checkIpRestrictions($user);
        if (!$ipCheck['allowed']) {
            return $ipCheck;
        }

        // 頻率限制檢查
        $rateCheck = $this->checkRateLimit($user, $action);
        if (!$rateCheck['allowed']) {
            return $rateCheck;
        }

        $result['allowed'] = true;
        $result['risk_level'] = $this->calculateAccessRiskLevel($user, $action, $activity);
        
        return $result;
    }

    /**
     * 過濾活動記錄資料以移除敏感資訊
     *
     * @param Activity $activity
     * @param User $user
     * @return array
     */
    public function filterActivityData(Activity $activity, User $user): array
    {
        $data = $activity->toArray();

        // 檢查使用者是否有檢視原始資料的權限
        $canViewRawData = $user->hasPermission('security.audit');

        if (!$canViewRawData) {
            // 過濾敏感屬性
            if (isset($data['properties'])) {
                $data['properties'] = $this->sensitiveDataFilter->filterProperties($data['properties']);
            }

            // 遮蔽 IP 位址（只顯示前三段）
            if (isset($data['ip_address'])) {
                $data['ip_address'] = $this->maskIpAddress($data['ip_address']);
            }

            // 遮蔽使用者代理資訊
            if (isset($data['user_agent'])) {
                $data['user_agent'] = $this->maskUserAgent($data['user_agent']);
            }

            // 移除簽章資訊
            unset($data['signature']);
        }

        // 根據使用者權限決定顯示的欄位
        $allowedFields = $this->getAllowedFields($user);
        $data = array_intersect_key($data, array_flip($allowedFields));

        return $data;
    }

    /**
     * 執行活動記錄安全審計
     *
     * @param array $options
     * @return array
     */
    public function performSecurityAudit(array $options = []): array
    {
        $startTime = microtime(true);
        
        $report = [
            'audit_id' => uniqid('audit_'),
            'started_at' => Carbon::now(),
            'performed_by' => auth()->id(),
            'scope' => $options['scope'] ?? 'full',
            'results' => [
                'integrity_check' => [],
                'access_violations' => [],
                'suspicious_patterns' => [],
                'security_incidents' => [],
                'recommendations' => []
            ],
            'statistics' => [
                'total_activities' => 0,
                'integrity_violations' => 0,
                'access_violations' => 0,
                'high_risk_activities' => 0
            ]
        ];

        try {
            // 1. 完整性檢查
            $report['results']['integrity_check'] = $this->auditIntegrity($options);
            
            // 2. 存取違規檢查
            $report['results']['access_violations'] = $this->auditAccessViolations($options);
            
            // 3. 可疑模式檢測
            $report['results']['suspicious_patterns'] = $this->detectSuspiciousPatterns($options);
            
            // 4. 安全事件分析
            $report['results']['security_incidents'] = $this->analyzeSecurityIncidents($options);
            
            // 5. 生成建議
            $report['results']['recommendations'] = $this->generateSecurityRecommendations($report['results']);
            
            // 更新統計資訊
            $this->updateAuditStatistics($report);
            
        } catch (\Exception $e) {
            $report['status'] = 'failed';
            $report['error'] = $e->getMessage();
            
            Log::error('安全審計執行失敗', [
                'audit_id' => $report['audit_id'],
                'error' => $e->getMessage(),
                'options' => $options
            ]);
        }

        $report['execution_time'] = round(microtime(true) - $startTime, 2);
        $report['completed_at'] = Carbon::now();
        
        // 記錄審計操作
        $this->logSecurityAudit($report);
        
        return $report;
    }

    /**
     * 檢測防篡改保護機制
     *
     * @param Activity $activity
     * @param array $originalData
     * @return array
     */
    public function detectTamperingAttempt(Activity $activity, array $originalData): array
    {
        $result = [
            'tampering_detected' => false,
            'tampered_fields' => [],
            'severity' => 'low',
            'recommendations' => []
        ];

        // 檢查關鍵欄位是否被修改
        $criticalFields = ['type', 'description', 'user_id', 'subject_id', 'created_at', 'properties'];
        
        foreach ($criticalFields as $field) {
            $currentValue = $activity->getAttribute($field);
            $originalValue = $originalData[$field] ?? null;
            
            if ($this->valuesAreDifferent($currentValue, $originalValue)) {
                $result['tampering_detected'] = true;
                $result['tampered_fields'][] = [
                    'field' => $field,
                    'original_value' => $this->sanitizeForLog($originalValue),
                    'current_value' => $this->sanitizeForLog($currentValue),
                    'change_detected_at' => Carbon::now()
                ];
            }
        }

        if ($result['tampering_detected']) {
            $result['severity'] = count($result['tampered_fields']) > 2 ? 'high' : 'medium';
            $result['recommendations'] = $this->generateTamperingRecommendations($result);
            
            // 記錄篡改嘗試
            $this->logTamperingAttempt($activity, $result);
        }

        return $result;
    }

    /**
     * 加密敏感活動記錄資料
     *
     * @param array $data
     * @return array
     */
    public function encryptSensitiveData(array $data): array
    {
        if (!config('activity-log.encryption.enabled', false)) {
            return $data;
        }

        $encryptedData = $data;
        $sensitiveFields = config('activity-log.encryption.fields', ['properties', 'description']);

        foreach ($sensitiveFields as $field) {
            if (isset($encryptedData[$field]) && !empty($encryptedData[$field])) {
                $encryptedData[$field] = encrypt($encryptedData[$field]);
                $encryptedData["{$field}_encrypted"] = true;
            }
        }

        return $encryptedData;
    }

    /**
     * 解密敏感活動記錄資料
     *
     * @param array $data
     * @return array
     */
    public function decryptSensitiveData(array $data): array
    {
        if (!config('activity-log.encryption.enabled', false)) {
            return $data;
        }

        $decryptedData = $data;
        $sensitiveFields = config('activity-log.encryption.fields', ['properties', 'description']);

        foreach ($sensitiveFields as $field) {
            if (isset($decryptedData["{$field}_encrypted"]) && $decryptedData["{$field}_encrypted"]) {
                try {
                    $decryptedData[$field] = decrypt($decryptedData[$field]);
                    unset($decryptedData["{$field}_encrypted"]);
                } catch (\Exception $e) {
                    Log::error('解密活動記錄資料失敗', [
                        'field' => $field,
                        'error' => $e->getMessage()
                    ]);
                    $decryptedData[$field] = '[DECRYPTION_FAILED]';
                }
            }
        }

        return $decryptedData;
    }

    /**
     * 取得所需權限
     */
    private function getRequiredPermission(string $action): string
    {
        return match ($action) {
            'view' => 'activity_logs.view',
            'export' => 'activity_logs.export',
            'delete' => 'activity_logs.delete',
            'audit' => 'security.audit',
            'view_raw' => 'security.audit',
            default => 'activity_logs.view'
        };
    }

    /**
     * 檢查特定活動記錄的存取權限
     */
    private function checkActivitySpecificAccess(User $user, string $action, Activity $activity): array
    {
        $result = ['allowed' => true, 'restrictions' => []];

        // 檢查是否為敏感活動記錄
        if ($this->isSensitiveActivity($activity) && !$user->hasPermission('security.view')) {
            return [
                'allowed' => false,
                'reason' => '此為敏感活動記錄，需要安全檢視權限',
                'risk_level' => 9
            ];
        }

        // 一般使用者只能檢視自己的活動記錄
        if (!$user->hasPermission('security.view') && $activity->user_id !== $user->id) {
            return [
                'allowed' => false,
                'reason' => '您只能檢視自己的活動記錄',
                'risk_level' => 6
            ];
        }

        return $result;
    }

    /**
     * 檢查時間限制
     */
    private function checkTimeRestrictions(User $user, string $action): array
    {
        // 檢查是否在允許的時間範圍內存取
        $allowedHours = config('activity-log.security.allowed_hours', []);
        
        if (!empty($allowedHours) && !$user->hasPermission('security.audit')) {
            $currentHour = (int) Carbon::now()->format('H');
            if (!in_array($currentHour, $allowedHours)) {
                return [
                    'allowed' => false,
                    'reason' => '當前時間不在允許存取的時間範圍內',
                    'risk_level' => 5
                ];
            }
        }

        return ['allowed' => true];
    }

    /**
     * 檢查 IP 限制
     */
    private function checkIpRestrictions(User $user): array
    {
        $allowedIps = config('activity-log.security.allowed_ips', []);
        $currentIp = request()->ip();

        if (!empty($allowedIps) && !$user->hasPermission('security.audit')) {
            if (!in_array($currentIp, $allowedIps)) {
                return [
                    'allowed' => false,
                    'reason' => 'IP 位址不在允許的範圍內',
                    'risk_level' => 8
                ];
            }
        }

        return ['allowed' => true];
    }

    /**
     * 檢查頻率限制
     */
    private function checkRateLimit(User $user, string $action): array
    {
        $key = "activity_access:{$user->id}:{$action}";
        $limit = config('activity-log.security.rate_limits.' . $action, 100);
        $window = config('activity-log.security.rate_window', 3600); // 1 hour

        $current = Cache::get($key, 0);
        
        if ($current >= $limit) {
            return [
                'allowed' => false,
                'reason' => '存取頻率超過限制',
                'risk_level' => 7
            ];
        }

        Cache::put($key, $current + 1, $window);
        
        return ['allowed' => true];
    }

    /**
     * 計算存取風險等級
     */
    private function calculateAccessRiskLevel(User $user, string $action, ?Activity $activity): int
    {
        $riskLevel = 1;

        // 根據動作類型調整風險等級
        $riskLevel += match ($action) {
            'export' => 3,
            'delete' => 4,
            'audit' => 2,
            'view_raw' => 3,
            default => 1
        };

        // 根據活動記錄類型調整
        if ($activity && $this->isSensitiveActivity($activity)) {
            $riskLevel += 2;
        }

        // 根據使用者角色調整
        if (!$user->hasPermission('security.audit')) {
            $riskLevel += 1;
        }

        return min($riskLevel, 10);
    }

    /**
     * 其他輔助方法...
     */
    private function isSensitiveActivity(Activity $activity): bool
    {
        $sensitiveTypes = [
            'login_failed', 'permission_escalation', 'sensitive_data_access',
            'system_config_change', 'security_incident', 'unauthorized_access'
        ];

        return in_array($activity->type, $sensitiveTypes) || ($activity->risk_level ?? 0) >= 7;
    }

    private function maskIpAddress(string $ip): string
    {
        $parts = explode('.', $ip);
        if (count($parts) === 4) {
            return $parts[0] . '.' . $parts[1] . '.' . $parts[2] . '.***';
        }
        return '***';
    }

    private function maskUserAgent(string $userAgent): string
    {
        return substr($userAgent, 0, 50) . '...';
    }

    private function getAllowedFields(User $user): array
    {
        $baseFields = ['id', 'type', 'description', 'created_at', 'user_id'];
        
        if ($user->hasPermission('security.view')) {
            $baseFields = array_merge($baseFields, ['ip_address', 'user_agent', 'risk_level', 'result']);
        }
        
        if ($user->hasPermission('security.audit')) {
            $baseFields = array_merge($baseFields, ['properties', 'signature', 'subject_id', 'subject_type']);
        }
        
        return $baseFields;
    }

    // 其他審計相關的私有方法將在後續實作...
    private function auditIntegrity(array $options): array { return []; }
    private function auditAccessViolations(array $options): array { return []; }
    private function detectSuspiciousPatterns(array $options): array { return []; }
    private function analyzeSecurityIncidents(array $options): array { return []; }
    private function generateSecurityRecommendations(array $results): array { return []; }
    private function updateAuditStatistics(array &$report): void {}
    private function logSecurityAudit(array $report): void {}
    private function valuesAreDifferent($value1, $value2): bool { return $value1 !== $value2; }
    private function sanitizeForLog($value): string { return is_string($value) ? substr($value, 0, 100) : (string) $value; }
    private function generateTamperingRecommendations(array $result): array { return []; }
    private function logTamperingAttempt(Activity $activity, array $result): void {}
}