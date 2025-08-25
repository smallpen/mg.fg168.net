<?php

namespace App\Services;

use App\Models\Activity;
use App\Models\User;
use App\Jobs\LogActivityJob;
use App\Jobs\LogActivitiesBatchJob;
use App\Services\ActivityIntegrityService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;

/**
 * 活動記錄服務
 * 
 * 記錄和追蹤系統中的使用者活動，支援同步和非同步記錄
 */
class ActivityLogger
{
    /**
     * 快取鍵前綴
     * 
     * @var string
     */
    protected $cachePrefix = 'activity_log';

    /**
     * 敏感欄位列表
     * 
     * @var array
     */
    protected array $sensitiveFields = [
        'password', 'token', 'secret', 'key', 'credit_card', 'ssn', 'api_key'
    ];

    /**
     * 記錄使用者活動（同步）
     * 
     * @param string $type 活動類型
     * @param string $description 活動描述
     * @param array $data 活動資料
     * @return Activity
     */
    public function log(string $type, string $description, array $data = []): Activity
    {
        $activityData = $this->prepareActivityData($type, $description, $data);
        
        // 過濾敏感資料
        if (isset($activityData['properties'])) {
            $activityData['properties'] = $this->filterSensitiveData($activityData['properties']);
        }

        // 建立活動記錄
        $activity = Activity::create($activityData);

        // 記錄到日誌檔案
        Log::channel('activity')->info('User Activity', [
            'activity_id' => $activity->id,
            'type' => $type,
            'description' => $description,
            'user_id' => $activity->user_id,
            'ip_address' => $activity->ip_address,
        ]);

        // 清除相關快取
        $this->clearRecentActivitiesCache();

        return $activity;
    }

    /**
     * 記錄使用者操作（針對特定模型）
     * 
     * @param string $action 操作類型
     * @param Model|null $subject 操作對象
     * @param array $data 額外資料
     * @return Activity
     */
    public function logUserAction(string $action, ?Model $subject = null, array $data = []): Activity
    {
        $description = $this->generateActionDescription($action, $subject);
        
        $activityData = array_merge($data, [
            'subject_id' => $subject?->getKey(),
            'subject_type' => $subject ? get_class($subject) : null,
        ]);

        return $this->log($action, $description, $activityData);
    }

    /**
     * 記錄安全事件
     * 
     * @param string $event 事件類型
     * @param string $description 事件描述
     * @param array $context 事件上下文
     * @return Activity
     */
    public function logSecurityEvent(string $event, string $description, array $context = []): Activity
    {
        $riskLevel = $this->calculateSecurityRiskLevel($event, $context);
        
        return $this->log($event, $description, array_merge($context, [
            'result' => $context['result'] ?? 'warning',
            'risk_level' => $riskLevel,
            'module' => 'security',
        ]));
    }

    /**
     * 記錄系統事件
     * 
     * @param string $event 事件類型
     * @param array $data 事件資料
     * @return Activity
     */
    public function logSystemEvent(string $event, array $data = []): Activity
    {
        return $this->log($event, "系統事件: {$event}", array_merge($data, [
            'user_id' => null,
            'module' => 'system',
        ]));
    }

    /**
     * 記錄 API 存取
     * 
     * @param string $endpoint API 端點
     * @param array $data 請求資料
     * @return Activity
     */
    public function logApiAccess(string $endpoint, array $data = []): Activity
    {
        return $this->log('api_access', "API 存取: {$endpoint}", array_merge($data, [
            'module' => 'api',
            'properties' => [
                'endpoint' => $endpoint,
                'method' => request()->method(),
                'response_code' => $data['response_code'] ?? null,
            ],
        ]));
    }

    /**
     * 批量記錄活動
     * 
     * @param array $activities 活動陣列
     * @return void
     */
    public function logBatch(array $activities): void
    {
        $preparedActivities = [];
        
        foreach ($activities as $activity) {
            $preparedActivities[] = $this->prepareActivityData(
                $activity['type'],
                $activity['description'],
                $activity['data'] ?? []
            );
        }

        // 分批插入資料庫
        foreach (array_chunk($preparedActivities, 100) as $chunk) {
            Activity::insert($chunk);
        }

        $this->clearRecentActivitiesCache();
    }

    /**
     * 非同步記錄活動
     * 
     * @param string $type 活動類型
     * @param string $description 活動描述
     * @param array $data 活動資料
     * @return void
     */
    public function logAsync(string $type, string $description, array $data = []): void
    {
        // 檢查是否啟用非同步記錄
        if (!config('activity-log.async.enabled', true)) {
            $this->log($type, $description, $data);
            return;
        }

        try {
            $asyncLogger = app(AsyncActivityLogger::class);
            $asyncLogger->logAsync($type, $description, $data);
        } catch (\Exception $e) {
            Log::warning('非同步記錄失敗，改用同步記錄', [
                'error' => $e->getMessage(),
                'type' => $type,
            ]);
            
            // 降級到同步記錄
            $this->log($type, $description, $data);
        }
    }

    /**
     * 新增活動到批量佇列
     * 
     * @param string $type 活動類型
     * @param string $description 活動描述
     * @param array $data 活動資料
     * @return void
     */
    public function addToBatch(string $type, string $description, array $data = []): void
    {
        if (!config('activity-log.async.enabled', true)) {
            $this->log($type, $description, $data);
            return;
        }

        try {
            $asyncLogger = app(AsyncActivityLogger::class);
            $asyncLogger->addToBatch($type, $description, $data);
        } catch (\Exception $e) {
            Log::warning('批量記錄失敗，改用同步記錄', [
                'error' => $e->getMessage(),
                'type' => $type,
            ]);
            
            $this->log($type, $description, $data);
        }
    }

    /**
     * 記錄使用者登入活動
     * 
     * @param int $userId
     * @param array $context
     * @return Activity
     */
    public function logLogin(int $userId, array $context = []): Activity
    {
        return $this->log(
            'user_login',
            '使用者登入系統',
            array_merge([
                'module' => 'auth',
                'user_id' => $userId,
                'properties' => [
                    'login_method' => $context['method'] ?? 'username_password',
                    'success' => true,
                    'session_id' => session()->getId(),
                    'remember_me' => $context['remember'] ?? false,
                ],
                'result' => 'success',
                'risk_level' => 2,
            ], $context)
        );
    }

    /**
     * 記錄使用者登出活動
     * 
     * @param int $userId
     * @param array $context
     * @return Activity
     */
    public function logLogout(int $userId, array $context = []): Activity
    {
        return $this->log(
            'user_logout',
            '使用者登出系統',
            array_merge([
                'module' => 'auth',
                'user_id' => $userId,
                'properties' => [
                    'logout_method' => $context['method'] ?? 'manual',
                    'session_duration' => $this->calculateSessionDuration(),
                    'session_id' => session()->getId(),
                ],
                'result' => 'success',
                'risk_level' => 1,
            ], $context)
        );
    }

    /**
     * 記錄使用者建立活動
     * 
     * @param int $createdUserId 被建立的使用者 ID
     * @param int|null $creatorId 建立者 ID
     * @return void
     */
    public function logUserCreated(int $createdUserId, ?int $creatorId = null): void
    {
        $createdUser = \App\Models\User::find($createdUserId);
        
        $this->log(
            'user_created',
            "建立新使用者：{$createdUser?->display_name}",
            [
                'created_user_id' => $createdUserId,
                'created_username' => $createdUser?->username,
                'assigned_roles' => $createdUser?->roles->pluck('name')->toArray() ?? []
            ],
            $creatorId
        );
    }

    /**
     * 記錄使用者更新活動
     * 
     * @param int $updatedUserId 被更新的使用者 ID
     * @param array $changes 變更內容
     * @param int|null $updaterId 更新者 ID
     * @return void
     */
    public function logUserUpdated(int $updatedUserId, array $changes, ?int $updaterId = null): void
    {
        $updatedUser = \App\Models\User::find($updatedUserId);
        
        $this->log(
            'user_updated',
            "更新使用者：{$updatedUser?->display_name}",
            [
                'updated_user_id' => $updatedUserId,
                'changes' => $changes,
                'fields_changed' => array_keys($changes)
            ],
            $updaterId
        );
    }

    /**
     * 記錄角色建立活動
     * 
     * @param int $roleId 角色 ID
     * @param int|null $creatorId 建立者 ID
     * @return void
     */
    public function logRoleCreated(int $roleId, ?int $creatorId = null): void
    {
        $role = \App\Models\Role::find($roleId);
        
        $this->log(
            'role_created',
            "建立新角色：{$role?->display_name}",
            [
                'role_id' => $roleId,
                'role_name' => $role?->name,
                'permissions_count' => $role?->permissions->count() ?? 0
            ],
            $creatorId
        );
    }

    /**
     * 記錄權限變更活動
     * 
     * @param int $roleId 角色 ID
     * @param array $addedPermissions 新增的權限
     * @param array $removedPermissions 移除的權限
     * @param int|null $updaterId 更新者 ID
     * @return void
     */
    public function logPermissionsChanged(int $roleId, array $addedPermissions, array $removedPermissions, ?int $updaterId = null): void
    {
        $role = \App\Models\Role::find($roleId);
        
        $this->log(
            'permissions_changed',
            "變更角色權限：{$role?->display_name}",
            [
                'role_id' => $roleId,
                'added_permissions' => $addedPermissions,
                'removed_permissions' => $removedPermissions,
                'total_permissions' => $role?->permissions->count() ?? 0
            ],
            $updaterId
        );
    }

    /**
     * 記錄登入失敗活動
     * 
     * @param string $username
     * @param array $context
     * @return Activity
     */
    public function logLoginFailed(string $username, array $context = []): Activity
    {
        return $this->logSecurityEvent(
            'login_failed',
            "登入失敗：{$username}",
            array_merge([
                'username' => $username,
                'attempt_count' => $context['attempt_count'] ?? 1,
                'reason' => $context['reason'] ?? 'invalid_credentials',
            ], $context)
        );
    }

    /**
     * 記錄密碼變更活動
     * 
     * @param int $userId
     * @param array $context
     * @return Activity
     */
    public function logPasswordChanged(int $userId, array $context = []): Activity
    {
        return $this->log(
            'password_changed',
            '使用者變更密碼',
            array_merge([
                'module' => 'auth',
                'user_id' => $userId,
                'properties' => [
                    'change_method' => $context['method'] ?? 'self_service',
                    'forced_change' => $context['forced'] ?? false,
                ],
                'result' => 'success',
                'risk_level' => 4,
            ], $context)
        );
    }

    /**
     * 記錄角色指派活動
     * 
     * @param int $userId
     * @param array $roleIds
     * @param array $context
     * @return Activity
     */
    public function logRoleAssigned(int $userId, array $roleIds, array $context = []): Activity
    {
        $user = \App\Models\User::find($userId);
        $roles = \App\Models\Role::whereIn('id', $roleIds)->pluck('display_name')->toArray();
        
        return $this->log(
            'role_assigned',
            "指派角色給使用者：{$user?->display_name}",
            array_merge([
                'module' => 'users',
                'subject_id' => $userId,
                'subject_type' => \App\Models\User::class,
                'properties' => [
                    'assigned_roles' => $roles,
                    'role_ids' => $roleIds,
                    'username' => $user?->username,
                ],
                'result' => 'success',
                'risk_level' => 5,
            ], $context)
        );
    }

    /**
     * 記錄角色移除活動
     * 
     * @param int $userId
     * @param array $roleIds
     * @param array $context
     * @return Activity
     */
    public function logRoleRemoved(int $userId, array $roleIds, array $context = []): Activity
    {
        $user = \App\Models\User::find($userId);
        $roles = \App\Models\Role::whereIn('id', $roleIds)->pluck('display_name')->toArray();
        
        return $this->log(
            'role_removed',
            "移除使用者角色：{$user?->display_name}",
            array_merge([
                'module' => 'users',
                'subject_id' => $userId,
                'subject_type' => \App\Models\User::class,
                'properties' => [
                    'removed_roles' => $roles,
                    'role_ids' => $roleIds,
                    'username' => $user?->username,
                ],
                'result' => 'success',
                'risk_level' => 5,
            ], $context)
        );
    }

    /**
     * 記錄使用者狀態變更活動
     * 
     * @param int $userId
     * @param bool $isActive
     * @param array $context
     * @return Activity
     */
    public function logUserStatusChanged(int $userId, bool $isActive, array $context = []): Activity
    {
        $user = \App\Models\User::find($userId);
        $action = $isActive ? '啟用' : '停用';
        
        return $this->log(
            'user_status_changed',
            "{$action}使用者：{$user?->display_name}",
            array_merge([
                'module' => 'users',
                'subject_id' => $userId,
                'subject_type' => \App\Models\User::class,
                'properties' => [
                    'new_status' => $isActive,
                    'previous_status' => !$isActive,
                    'username' => $user?->username,
                    'reason' => $context['reason'] ?? null,
                ],
                'result' => 'success',
                'risk_level' => $isActive ? 3 : 6,
            ], $context)
        );
    }

    /**
     * 記錄資料匯出活動
     * 
     * @param string $dataType
     * @param array $context
     * @return Activity
     */
    public function logDataExport(string $dataType, array $context = []): Activity
    {
        return $this->log(
            'data_export',
            "匯出資料：{$dataType}",
            array_merge([
                'module' => 'system',
                'properties' => [
                    'data_type' => $dataType,
                    'export_format' => $context['format'] ?? 'csv',
                    'record_count' => $context['count'] ?? 0,
                    'file_size' => $context['size'] ?? 0,
                ],
                'result' => 'success',
                'risk_level' => 4,
            ], $context)
        );
    }

    /**
     * 記錄資料匯入活動
     * 
     * @param string $dataType
     * @param array $context
     * @return Activity
     */
    public function logDataImport(string $dataType, array $context = []): Activity
    {
        return $this->log(
            'data_import',
            "匯入資料：{$dataType}",
            array_merge([
                'module' => 'system',
                'properties' => [
                    'data_type' => $dataType,
                    'import_format' => $context['format'] ?? 'csv',
                    'record_count' => $context['count'] ?? 0,
                    'success_count' => $context['success'] ?? 0,
                    'error_count' => $context['errors'] ?? 0,
                ],
                'result' => ($context['errors'] ?? 0) > 0 ? 'partial_success' : 'success',
                'risk_level' => 5,
            ], $context)
        );
    }

    /**
     * 記錄系統設定變更活動
     * 
     * @param string $settingKey
     * @param mixed $oldValue
     * @param mixed $newValue
     * @param array $context
     * @return Activity
     */
    public function logSettingChanged(string $settingKey, $oldValue, $newValue, array $context = []): Activity
    {
        return $this->log(
            'setting_changed',
            "變更系統設定：{$settingKey}",
            array_merge([
                'module' => 'settings',
                'properties' => [
                    'setting_key' => $settingKey,
                    'old_value' => $this->sanitizeSettingValue($settingKey, $oldValue),
                    'new_value' => $this->sanitizeSettingValue($settingKey, $newValue),
                    'setting_category' => $context['category'] ?? 'general',
                ],
                'result' => 'success',
                'risk_level' => $this->calculateSettingRiskLevel($settingKey),
            ], $context)
        );
    }

    /**
     * 記錄系統活動
     * 
     * @param string $type 活動類型
     * @param string $description 活動描述
     * @param array $details 活動詳細資訊
     * @return Activity
     */
    public function logSystemActivity(string $type, string $description, array $details = []): Activity
    {
        return $this->log($type, $description, array_merge([
            'module' => 'system',
            'user_id' => null,
        ], $details));
    }

    /**
     * 取得最近的活動記錄
     * 
     * @param int $limit 限制數量
     * @return array
     */
    public function getRecentActivities(int $limit = 20): array
    {
        $cacheKey = "{$this->cachePrefix}.recent.{$limit}";
        
        return Cache::get($cacheKey, []);
    }

    /**
     * 清除活動記錄快取
     * 
     * @return void
     */
    public function clearCache(): void
    {
        $keys = [
            "{$this->cachePrefix}.recent.10",
            "{$this->cachePrefix}.recent.20",
            "{$this->cachePrefix}.recent.50",
        ];

        foreach ($keys as $key) {
            Cache::forget($key);
        }
    }

    /**
     * 將活動記錄儲存到快取
     * 
     * @param array $activity
     * @return void
     */
    protected function cacheActivity(array $activity): void
    {
        $limits = [10, 20, 50];
        
        foreach ($limits as $limit) {
            $cacheKey = "{$this->cachePrefix}.recent.{$limit}";
            $activities = Cache::get($cacheKey, []);
            
            // 新增活動到陣列開頭
            array_unshift($activities, $activity);
            
            // 限制陣列大小
            $activities = array_slice($activities, 0, $limit);
            
            // 儲存回快取（快取 1 小時）
            Cache::put($cacheKey, $activities, 3600);
        }
    }

    /**
     * 取得使用者名稱
     * 
     * @param int $userId
     * @return string
     */
    protected function getUserName(int $userId): string
    {
        $user = \App\Models\User::find($userId);
        return $user?->display_name ?? "使用者 #{$userId}";
    }

    /**
     * 計算 session 持續時間
     * 
     * @return int 秒數
     */
    protected function calculateSessionDuration(): int
    {
        $loginTime = session('login_time');
        
        if (!$loginTime) {
            return 0;
        }
        
        return now()->diffInSeconds($loginTime);
    }

    /**
     * 準備活動資料
     * 
     * @param string $type
     * @param string $description
     * @param array $data
     * @return array
     */
    protected function prepareActivityData(string $type, string $description, array $data = []): array
    {
        $userId = $data['user_id'] ?? auth()->id();
        
        $activityData = [
            'type' => $type,
            'description' => $description,
            'module' => $data['module'] ?? null,
            'user_id' => is_numeric($userId) ? (int) $userId : null,
            'subject_id' => $data['subject_id'] ?? null,
            'subject_type' => $data['subject_type'] ?? null,
            'properties' => $data['properties'] ?? null,
            'ip_address' => $data['ip_address'] ?? request()->ip(),
            'user_agent' => $data['user_agent'] ?? request()->userAgent(),
            'result' => $data['result'] ?? 'success',
            'risk_level' => $data['risk_level'] ?? 1,
            'created_at' => now(),
            'updated_at' => now(),
        ];

        // 使用完整性服務生成數位簽章
        if (config('activity-log.integrity.enabled', true)) {
            $integrityService = app(ActivityIntegrityService::class);
            $activityData['signature'] = $integrityService->generateSignature($activityData);
        }

        return $activityData;
    }

    /**
     * 過濾敏感資料
     * 
     * @param array $data
     * @return array
     */
    protected function filterSensitiveData(array $data): array
    {
        return $this->recursiveFilter($data);
    }

    /**
     * 遞迴過濾敏感欄位
     * 
     * @param array $data
     * @return array
     */
    protected function recursiveFilter(array $data): array
    {
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $data[$key] = $this->recursiveFilter($value);
            } elseif ($this->isSensitiveField($key)) {
                $data[$key] = '[FILTERED]';
            }
        }
        
        return $data;
    }

    /**
     * 檢查是否為敏感欄位
     * 
     * @param string $field
     * @return bool
     */
    protected function isSensitiveField(string $field): bool
    {
        foreach ($this->sensitiveFields as $sensitive) {
            if (stripos($field, $sensitive) !== false) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * 生成操作描述
     * 
     * @param string $action
     * @param Model|null $subject
     * @return string
     */
    protected function generateActionDescription(string $action, ?Model $subject = null): string
    {
        if (!$subject) {
            return "執行操作: {$action}";
        }

        $modelName = class_basename($subject);
        $identifier = method_exists($subject, 'getDisplayName') 
            ? $subject->getDisplayName() 
            : ($subject->name ?? $subject->getKey());

        return match ($action) {
            'created' => "建立 {$modelName}: {$identifier}",
            'updated' => "更新 {$modelName}: {$identifier}",
            'deleted' => "刪除 {$modelName}: {$identifier}",
            'viewed' => "檢視 {$modelName}: {$identifier}",
            default => "對 {$modelName} {$identifier} 執行 {$action}",
        };
    }

    /**
     * 計算安全風險等級
     * 
     * @param string $event
     * @param array $context
     * @return int
     */
    protected function calculateSecurityRiskLevel(string $event, array $context): int
    {
        $baseRisk = match ($event) {
            'login_failed' => 3,
            'permission_escalation' => 8,
            'sensitive_data_access' => 6,
            'system_config_change' => 7,
            'suspicious_ip_access' => 5,
            'bulk_operation' => 4,
            default => 2,
        };

        // 根據上下文調整風險等級
        if (isset($context['failed_attempts']) && $context['failed_attempts'] > 5) {
            $baseRisk += 2;
        }

        if (isset($context['unusual_time']) && $context['unusual_time']) {
            $baseRisk += 1;
        }

        if (isset($context['unknown_ip']) && $context['unknown_ip']) {
            $baseRisk += 2;
        }

        return min($baseRisk, 10); // 最高風險等級為 10
    }

    /**
     * 清除最近活動快取
     * 
     * @return void
     */
    protected function clearRecentActivitiesCache(): void
    {
        $keys = [
            "{$this->cachePrefix}.recent.10",
            "{$this->cachePrefix}.recent.20",
            "{$this->cachePrefix}.recent.50",
        ];

        foreach ($keys as $key) {
            Cache::forget($key);
        }
    }

    /**
     * 清理設定值（移除敏感資訊）
     * 
     * @param string $key
     * @param mixed $value
     * @return mixed
     */
    protected function sanitizeSettingValue(string $key, $value)
    {
        $sensitiveKeys = [
            'password', 'secret', 'key', 'token', 'api_key',
            'database_password', 'mail_password', 'redis_password'
        ];
        
        foreach ($sensitiveKeys as $sensitiveKey) {
            if (stripos($key, $sensitiveKey) !== false) {
                return '[FILTERED]';
            }
        }
        
        return $value;
    }

    /**
     * 計算設定變更的風險等級
     * 
     * @param string $settingKey
     * @return int
     */
    protected function calculateSettingRiskLevel(string $settingKey): int
    {
        $highRiskSettings = [
            'app_debug', 'app_env', 'database_', 'mail_', 'cache_',
            'session_', 'queue_', 'broadcast_', 'filesystem_'
        ];
        
        $mediumRiskSettings = [
            'app_name', 'app_url', 'timezone', 'locale'
        ];
        
        foreach ($highRiskSettings as $pattern) {
            if (stripos($settingKey, $pattern) !== false) {
                return 7;
            }
        }
        
        foreach ($mediumRiskSettings as $pattern) {
            if (stripos($settingKey, $pattern) !== false) {
                return 4;
            }
        }
        
        return 2;
    }
}