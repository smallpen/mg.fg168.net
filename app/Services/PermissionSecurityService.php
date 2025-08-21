<?php

namespace App\Services;

use App\Models\Permission;
use App\Models\User;
use App\Services\PermissionAuditService;
use App\Services\PermissionValidationService;
use App\Services\InputValidationService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Collection;

/**
 * 權限安全服務
 * 
 * 提供權限管理的多層級安全控制，包含：
 * - 多層級權限檢查
 * - 系統權限保護機制
 * - 操作審計日誌記錄
 * - 資料驗證和清理
 */
class PermissionSecurityService
{
    protected PermissionAuditService $auditService;
    protected PermissionValidationService $validationService;
    protected InputValidationService $inputValidationService;

    /**
     * 系統核心權限列表（不可刪除或修改）
     */
    protected array $systemCorePermissions = [
        'admin.access',
        'admin.dashboard',
        'system.manage',
        'auth.login',
        'auth.logout',
        'permissions.view',
        'roles.view',
        'users.view',
    ];

    /**
     * 高風險操作列表
     */
    protected array $highRiskOperations = [
        'delete',
        'bulk_delete',
        'import',
        'export',
        'sync_dependencies',
        'modify_system_permission',
    ];

    public function __construct(
        PermissionAuditService $auditService,
        PermissionValidationService $validationService,
        InputValidationService $inputValidationService
    ) {
        $this->auditService = $auditService;
        $this->validationService = $validationService;
        $this->inputValidationService = $inputValidationService;
    }

    /**
     * 多層級權限檢查
     * 
     * @param string $operation 操作類型
     * @param Permission|null $permission 權限物件
     * @param User|null $user 操作使用者
     * @return bool
     * @throws \Exception
     */
    public function checkMultiLevelPermission(string $operation, ?Permission $permission = null, ?User $user = null): bool
    {
        $user = $user ?? Auth::user();
        
        if (!$user) {
            $this->logSecurityEvent('unauthorized_access_attempt', 'high', [
                'operation' => $operation,
                'permission_id' => $permission?->id,
            ]);
            throw new \Exception('未授權的存取嘗試');
        }

        // 第一層：基本權限檢查
        if (!$this->checkBasicPermission($operation, $user)) {
            $this->logSecurityEvent('basic_permission_denied', 'medium', [
                'operation' => $operation,
                'user_id' => $user->id,
                'permission_id' => $permission?->id,
            ]);
            return false;
        }

        // 第二層：操作特定權限檢查
        if (!$this->checkOperationPermission($operation, $permission, $user)) {
            $this->logSecurityEvent('operation_permission_denied', 'medium', [
                'operation' => $operation,
                'user_id' => $user->id,
                'permission_id' => $permission?->id,
            ]);
            return false;
        }

        // 第三層：系統權限保護檢查
        if ($permission && !$this->checkSystemPermissionProtection($operation, $permission, $user)) {
            $this->logSecurityEvent('system_permission_protection_triggered', 'high', [
                'operation' => $operation,
                'user_id' => $user->id,
                'permission_id' => $permission->id,
                'permission_name' => $permission->name,
            ]);
            return false;
        }

        // 第四層：高風險操作檢查
        if (in_array($operation, $this->highRiskOperations)) {
            if (!$this->checkHighRiskOperation($operation, $permission, $user)) {
                $this->logSecurityEvent('high_risk_operation_denied', 'high', [
                    'operation' => $operation,
                    'user_id' => $user->id,
                    'permission_id' => $permission?->id,
                ]);
                return false;
            }
        }

        // 記錄成功的權限檢查
        $this->logSecurityEvent('permission_check_passed', 'low', [
            'operation' => $operation,
            'user_id' => $user->id,
            'permission_id' => $permission?->id,
        ]);

        return true;
    }

    /**
     * 檢查基本權限
     */
    protected function checkBasicPermission(string $operation, User $user): bool
    {
        $requiredPermissions = [
            'create' => 'permissions.create',
            'update' => 'permissions.edit',
            'delete' => 'permissions.delete',
            'view' => 'permissions.view',
            'export' => 'permissions.export',
            'import' => 'permissions.import',
            'bulk_delete' => 'permissions.delete',
            'sync_dependencies' => 'permissions.edit',
            'test' => 'permissions.test',
        ];

        $requiredPermission = $requiredPermissions[$operation] ?? 'permissions.view';
        
        return $user->hasPermission($requiredPermission);
    }

    /**
     * 檢查操作特定權限
     */
    protected function checkOperationPermission(string $operation, ?Permission $permission, User $user): bool
    {
        // 對於涉及特定權限的操作，進行額外檢查
        if ($permission) {
            // 檢查是否有權限操作該模組的權限
            $modulePermission = "permissions.{$permission->module}.{$operation}";
            if (Permission::where('name', $modulePermission)->exists()) {
                if (!$user->hasPermission($modulePermission)) {
                    return false;
                }
            }

            // 檢查是否有權限操作該類型的權限
            $typePermission = "permissions.{$permission->type}.{$operation}";
            if (Permission::where('name', $typePermission)->exists()) {
                if (!$user->hasPermission($typePermission)) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * 系統權限保護檢查
     */
    protected function checkSystemPermissionProtection(string $operation, Permission $permission, User $user): bool
    {
        // 檢查是否為系統核心權限
        if (in_array($permission->name, $this->systemCorePermissions)) {
            // 系統核心權限只能由超級管理員操作
            if (!$user->hasRole('super_admin')) {
                return false;
            }

            // 某些操作完全禁止
            if (in_array($operation, ['delete'])) {
                return false;
            }

            // 修改系統權限需要額外確認
            if ($operation === 'update') {
                return $this->checkSystemPermissionModification($permission, $user);
            }
        }

        // 檢查是否為系統模組權限
        if (in_array($permission->module, ['system', 'auth', 'admin'])) {
            if (!$user->hasPermission('system.permissions.manage')) {
                return false;
            }
        }

        return true;
    }

    /**
     * 檢查系統權限修改
     */
    protected function checkSystemPermissionModification(Permission $permission, User $user): bool
    {
        // 檢查是否有系統權限修改權限
        if (!$user->hasPermission('system.permissions.modify')) {
            return false;
        }

        // 檢查修改頻率限制
        $recentModifications = $this->auditService->getPermissionAuditLog($permission->id, 10)
            ->where('action', 'updated')
            ->where('created_at', '>=', now()->subHours(24));

        if ($recentModifications->count() >= 5) {
            $this->logSecurityEvent('system_permission_modification_rate_limit', 'high', [
                'permission_id' => $permission->id,
                'user_id' => $user->id,
                'recent_modifications' => $recentModifications->count(),
            ]);
            return false;
        }

        return true;
    }

    /**
     * 檢查高風險操作
     */
    protected function checkHighRiskOperation(string $operation, ?Permission $permission, User $user): bool
    {
        // 檢查使用者是否有高風險操作權限
        if (!$user->hasPermission('permissions.high_risk_operations')) {
            return false;
        }

        // 檢查操作頻率限制
        $rateLimitKey = "high_risk_operation_{$user->id}_{$operation}";
        $recentOperations = Cache::get($rateLimitKey, 0);

        $maxOperationsPerHour = [
            'delete' => 10,
            'bulk_delete' => 3,
            'import' => 5,
            'export' => 10,
        ];

        $limit = $maxOperationsPerHour[$operation] ?? 5;

        if ($recentOperations >= $limit) {
            $this->logSecurityEvent('high_risk_operation_rate_limit', 'high', [
                'operation' => $operation,
                'user_id' => $user->id,
                'current_count' => $recentOperations,
                'limit' => $limit,
            ]);
            return false;
        }

        // 更新操作計數
        Cache::put($rateLimitKey, $recentOperations + 1, now()->addHour());

        // 對於刪除操作，進行額外檢查
        if ($operation === 'delete' && $permission) {
            return $this->checkDeleteSafety($permission, $user);
        }

        return true;
    }

    /**
     * 檢查刪除安全性
     */
    protected function checkDeleteSafety(Permission $permission, User $user): bool
    {
        // 檢查權限是否被使用
        if ($permission->roles()->exists()) {
            $this->logSecurityEvent('delete_attempt_on_used_permission', 'medium', [
                'permission_id' => $permission->id,
                'user_id' => $user->id,
                'role_count' => $permission->roles()->count(),
            ]);
            return false;
        }

        // 檢查是否有依賴關係
        if ($permission->dependents()->exists()) {
            $this->logSecurityEvent('delete_attempt_on_dependent_permission', 'medium', [
                'permission_id' => $permission->id,
                'user_id' => $user->id,
                'dependent_count' => $permission->dependents()->count(),
            ]);
            return false;
        }

        return true;
    }

    /**
     * 驗證和清理權限資料
     * 
     * @param array $data 原始資料
     * @param string $operation 操作類型
     * @param Permission|null $permission 現有權限（更新時）
     * @return array 清理後的資料
     * @throws ValidationException
     */
    public function validateAndSanitizeData(array $data, string $operation, ?Permission $permission = null): array
    {
        // 基本輸入清理
        $data = $this->inputValidationService->sanitizeInput($data);

        // 根據操作類型進行驗證
        switch ($operation) {
            case 'create':
                $validatedData = $this->validationService->validateCreateData($data);
                break;
            case 'update':
                if (!$permission) {
                    throw new \InvalidArgumentException('更新操作需要提供權限物件');
                }
                $validatedData = $this->validationService->validateUpdateData($permission, $data);
                break;
            case 'bulk_delete':
                $validatedData = $this->validationService->validateBulkOperation($data);
                break;
            case 'import':
                $validatedData = $this->validationService->validateImportData($data);
                break;
            default:
                $validatedData = $data;
        }

        // 額外的安全清理
        $validatedData = $this->performSecuritySanitization($validatedData, $operation);

        // 記錄資料驗證事件
        $this->auditService->log('data_validation_completed', null, [
            'operation' => $operation,
            'data_fields' => array_keys($validatedData),
            'permission_id' => $permission?->id,
        ]);

        return $validatedData;
    }

    /**
     * 執行安全清理
     */
    protected function performSecuritySanitization(array $data, string $operation): array
    {
        // 清理權限名稱
        if (isset($data['name'])) {
            $data['name'] = $this->sanitizePermissionName($data['name']);
        }

        // 清理顯示名稱
        if (isset($data['display_name'])) {
            $data['display_name'] = $this->sanitizeDisplayName($data['display_name']);
        }

        // 清理描述
        if (isset($data['description'])) {
            $data['description'] = $this->sanitizeDescription($data['description']);
        }

        // 清理模組名稱
        if (isset($data['module'])) {
            $data['module'] = $this->sanitizeModuleName($data['module']);
        }

        // 清理依賴關係
        if (isset($data['dependencies'])) {
            $data['dependencies'] = $this->sanitizeDependencies($data['dependencies']);
        }

        return $data;
    }

    /**
     * 清理權限名稱
     */
    protected function sanitizePermissionName(string $name): string
    {
        // 移除危險字元
        $name = preg_replace('/[^a-z0-9_\.]/', '', strtolower(trim($name)));
        
        // 檢查是否包含危險模式
        $dangerousPatterns = [
            'system.delete',
            'admin.destroy',
            'auth.bypass',
            'security.disable',
        ];

        foreach ($dangerousPatterns as $pattern) {
            if (str_contains($name, $pattern)) {
                throw new ValidationException(validator([], [])->errors()->add('name', '權限名稱包含危險模式'));
            }
        }

        return $name;
    }

    /**
     * 清理顯示名稱
     */
    protected function sanitizeDisplayName(string $displayName): string
    {
        // 移除 HTML 標籤和危險字元
        $displayName = strip_tags(trim($displayName));
        
        // 移除控制字元
        $displayName = preg_replace('/[\x00-\x1F\x7F]/', '', $displayName);
        
        return $displayName;
    }

    /**
     * 清理描述
     */
    protected function sanitizeDescription(?string $description): ?string
    {
        if (!$description) {
            return null;
        }

        // 移除 HTML 標籤和危險字元
        $description = strip_tags(trim($description));
        
        // 移除控制字元
        $description = preg_replace('/[\x00-\x1F\x7F]/', '', $description);
        
        return $description ?: null;
    }

    /**
     * 清理模組名稱
     */
    protected function sanitizeModuleName(string $module): string
    {
        return preg_replace('/[^a-z0-9_]/', '', strtolower(trim($module)));
    }

    /**
     * 清理依賴關係
     */
    protected function sanitizeDependencies(array $dependencies): array
    {
        return array_values(array_unique(array_filter(array_map('intval', $dependencies))));
    }

    /**
     * 記錄操作審計日誌
     * 
     * @param string $operation 操作類型
     * @param Permission|null $permission 權限物件
     * @param array $data 操作資料
     * @param User|null $user 操作使用者
     * @return void
     */
    public function logOperation(string $operation, ?Permission $permission = null, array $data = [], ?User $user = null): void
    {
        $user = $user ?? Auth::user();

        // 記錄到權限審計服務
        if ($permission) {
            $this->auditService->logPermissionChange($operation, $permission, $data, $user);
        } else {
            $this->auditService->log($operation, null, $data, $user);
        }

        // 對於高風險操作，記錄額外的安全事件
        if (in_array($operation, $this->highRiskOperations)) {
            $this->logSecurityEvent("high_risk_operation_{$operation}", 'high', [
                'permission_id' => $permission?->id,
                'user_id' => $user?->id,
                'data' => $data,
            ]);
        }
    }

    /**
     * 記錄安全事件
     */
    protected function logSecurityEvent(string $event, string $severity, array $data = []): void
    {
        $logData = array_merge($data, [
            'event' => $event,
            'severity' => $severity,
            'timestamp' => now()->toISOString(),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'session_id' => session()->getId(),
        ]);

        // 記錄到應用程式日誌
        $logLevel = $severity === 'high' ? 'warning' : ($severity === 'medium' ? 'info' : 'debug');
        Log::$logLevel("權限安全事件: {$event}", $logData);

        // 記錄到審計服務
        $this->auditService->log("security_event_{$event}", null, $logData);

        // 對於高嚴重性事件，觸發額外的安全措施
        if ($severity === 'high') {
            $this->handleHighSeverityEvent($event, $logData);
        }
    }

    /**
     * 處理高嚴重性安全事件
     */
    protected function handleHighSeverityEvent(string $event, array $data): void
    {
        // 增加使用者的風險評分
        if (isset($data['user_id'])) {
            $this->increaseUserRiskScore($data['user_id'], $event);
        }

        // 觸發安全警報（如果配置了）
        if (config('security.alerts.enabled', false)) {
            $this->triggerSecurityAlert($event, $data);
        }

        // 記錄到安全事件表（如果存在）
        $this->recordSecurityIncident($event, $data);
    }

    /**
     * 增加使用者風險評分
     */
    protected function increaseUserRiskScore(int $userId, string $event): void
    {
        $riskScores = [
            'unauthorized_access_attempt' => 50,
            'system_permission_protection_triggered' => 30,
            'high_risk_operation_denied' => 20,
            'high_risk_operation_rate_limit' => 15,
        ];

        $score = $riskScores[$event] ?? 10;
        $cacheKey = "user_risk_score_{$userId}";
        $currentScore = Cache::get($cacheKey, 0);
        $newScore = $currentScore + $score;

        Cache::put($cacheKey, $newScore, now()->addHours(24));

        // 如果風險評分過高，觸發額外的安全措施
        if ($newScore >= 100) {
            $this->handleHighRiskUser($userId, $newScore);
        }
    }

    /**
     * 處理高風險使用者
     */
    protected function handleHighRiskUser(int $userId, int $riskScore): void
    {
        $this->logSecurityEvent('high_risk_user_detected', 'high', [
            'user_id' => $userId,
            'risk_score' => $riskScore,
        ]);

        // 可以在這裡實作額外的安全措施，如：
        // - 要求重新驗證
        // - 暫時限制權限
        // - 通知管理員
        // - 強制登出
    }

    /**
     * 觸發安全警報
     */
    protected function triggerSecurityAlert(string $event, array $data): void
    {
        // 這裡可以實作發送警報的邏輯，如：
        // - 發送電子郵件
        // - 發送 Slack 通知
        // - 調用外部安全系統 API
        
        Log::critical("安全警報觸發: {$event}", $data);
    }

    /**
     * 記錄安全事件
     */
    protected function recordSecurityIncident(string $event, array $data): void
    {
        try {
            DB::table('security_incidents')->insert([
                'event_type' => $event,
                'severity' => $data['severity'] ?? 'medium',
                'user_id' => $data['user_id'] ?? null,
                'ip_address' => $data['ip_address'] ?? null,
                'user_agent' => $data['user_agent'] ?? null,
                'data' => json_encode($data),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (\Exception $e) {
            // 如果記錄失敗，至少要記錄到日誌
            Log::error('記錄安全事件失敗', [
                'event' => $event,
                'error' => $e->getMessage(),
                'data' => $data,
            ]);
        }
    }

    /**
     * 檢查權限操作的安全性
     * 
     * @param string $operation 操作類型
     * @param array $data 操作資料
     * @param Permission|null $permission 權限物件
     * @param User|null $user 操作使用者
     * @return array 安全檢查結果
     */
    public function performSecurityCheck(string $operation, array $data, ?Permission $permission = null, ?User $user = null): array
    {
        $user = $user ?? Auth::user();
        $results = [
            'passed' => true,
            'warnings' => [],
            'errors' => [],
            'risk_level' => 'low',
        ];

        try {
            // 多層級權限檢查
            if (!$this->checkMultiLevelPermission($operation, $permission, $user)) {
                $results['passed'] = false;
                $results['errors'][] = '權限檢查失敗';
                $results['risk_level'] = 'high';
            }

            // 資料驗證
            $this->validateAndSanitizeData($data, $operation, $permission);

            // 檢查操作頻率
            $this->checkOperationFrequency($operation, $user, $results);

            // 檢查系統狀態
            $this->checkSystemStatus($results);

        } catch (ValidationException $e) {
            $results['passed'] = false;
            $results['errors'] = array_merge($results['errors'], $e->errors());
            $results['risk_level'] = 'medium';
        } catch (\Exception $e) {
            $results['passed'] = false;
            $results['errors'][] = $e->getMessage();
            $results['risk_level'] = 'high';
        }

        // 記錄安全檢查結果
        $this->logSecurityEvent('security_check_completed', $results['risk_level'], [
            'operation' => $operation,
            'user_id' => $user?->id,
            'permission_id' => $permission?->id,
            'passed' => $results['passed'],
            'risk_level' => $results['risk_level'],
            'warnings_count' => count($results['warnings']),
            'errors_count' => count($results['errors']),
        ]);

        return $results;
    }

    /**
     * 檢查操作頻率
     */
    protected function checkOperationFrequency(string $operation, ?User $user, array &$results): void
    {
        if (!$user) return;

        $cacheKey = "operation_frequency_{$user->id}_{$operation}";
        $recentOperations = Cache::get($cacheKey, 0);

        $limits = [
            'create' => 50,
            'update' => 100,
            'delete' => 20,
            'bulk_delete' => 5,
            'import' => 10,
            'export' => 20,
        ];

        $limit = $limits[$operation] ?? 30;

        if ($recentOperations >= $limit) {
            $results['warnings'][] = "操作頻率過高，已達到每小時 {$limit} 次的限制";
            $results['risk_level'] = 'medium';
        } elseif ($recentOperations >= $limit * 0.8) {
            $results['warnings'][] = "操作頻率較高，接近每小時限制";
        }

        // 更新操作計數
        Cache::put($cacheKey, $recentOperations + 1, now()->addHour());
    }

    /**
     * 檢查系統狀態
     */
    protected function checkSystemStatus(array &$results): void
    {
        // 檢查系統負載
        if (function_exists('sys_getloadavg')) {
            $load = sys_getloadavg();
            if ($load[0] > 2.0) {
                $results['warnings'][] = '系統負載較高，操作可能較慢';
            }
        }

        // 檢查資料庫連線
        try {
            DB::connection()->getPdo();
        } catch (\Exception $e) {
            $results['errors'][] = '資料庫連線異常';
            $results['passed'] = false;
            $results['risk_level'] = 'high';
        }

        // 檢查快取狀態
        try {
            Cache::put('system_check', true, 1);
            if (!Cache::get('system_check')) {
                $results['warnings'][] = '快取系統異常';
            }
        } catch (\Exception $e) {
            $results['warnings'][] = '快取系統不可用';
        }
    }

    /**
     * 取得使用者的風險評分
     */
    public function getUserRiskScore(int $userId): int
    {
        return Cache::get("user_risk_score_{$userId}", 0);
    }

    /**
     * 重置使用者的風險評分
     */
    public function resetUserRiskScore(int $userId): void
    {
        Cache::forget("user_risk_score_{$userId}");
        
        $this->logSecurityEvent('user_risk_score_reset', 'low', [
            'user_id' => $userId,
            'reset_by' => Auth::id(),
        ]);
    }

    /**
     * 取得系統核心權限列表
     */
    public function getSystemCorePermissions(): array
    {
        return $this->systemCorePermissions;
    }

    /**
     * 檢查是否為系統核心權限
     */
    public function isSystemCorePermission(string $permissionName): bool
    {
        return in_array($permissionName, $this->systemCorePermissions);
    }

    /**
     * 取得高風險操作列表
     */
    public function getHighRiskOperations(): array
    {
        return $this->highRiskOperations;
    }

    /**
     * 檢查是否為高風險操作
     */
    public function isHighRiskOperation(string $operation): bool
    {
        return in_array($operation, $this->highRiskOperations);
    }
}