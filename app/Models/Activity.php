<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Collection;
use Carbon\Carbon;

/**
 * 活動記錄模型
 * 
 * 記錄系統中的各種使用者活動和操作
 */
class Activity extends Model
{
    use HasFactory;

    /**
     * 可批量賦值的屬性
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'type',
        'event',
        'description',
        'module',
        'user_id',
        'subject_id',
        'subject_type',
        'properties',
        'ip_address',
        'user_agent',
        'result',
        'risk_level',
        'signature',
    ];

    /**
     * 屬性轉換
     *
     * @var array<string, string>
     */
    protected $casts = [
        'properties' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'risk_level' => 'integer',
    ];

    /**
     * 活動類型常數
     */
    public const TYPE_LOGIN = 'login';
    public const TYPE_LOGOUT = 'logout';
    public const TYPE_CREATE_USER = 'create_user';
    public const TYPE_UPDATE_USER = 'update_user';
    public const TYPE_DELETE_USER = 'delete_user';
    public const TYPE_CREATE_ROLE = 'create_role';
    public const TYPE_UPDATE_ROLE = 'update_role';
    public const TYPE_DELETE_ROLE = 'delete_role';
    public const TYPE_ASSIGN_ROLE = 'assign_role';
    public const TYPE_REMOVE_ROLE = 'remove_role';
    public const TYPE_UPDATE_PERMISSIONS = 'update_permissions';
    public const TYPE_VIEW_DASHBOARD = 'view_dashboard';
    public const TYPE_EXPORT_DATA = 'export_data';
    public const TYPE_QUICK_ACTION = 'quick_action';

    /**
     * 模組常數
     */
    public const MODULE_AUTH = 'auth';
    public const MODULE_USERS = 'users';
    public const MODULE_ROLES = 'roles';
    public const MODULE_PERMISSIONS = 'permissions';
    public const MODULE_DASHBOARD = 'dashboard';
    public const MODULE_SYSTEM = 'system';

    /**
     * 執行活動的使用者關聯
     *
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * 執行活動的使用者關聯（別名，用於相容性）
     *
     * @return BelongsTo
     */
    public function causer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * 取得 causer_id 屬性（相容性）
     */
    public function getCauserIdAttribute(): ?int
    {
        return $this->user_id;
    }

    /**
     * 取得 causer_type 屬性（相容性）
     */
    public function getCauserTypeAttribute(): string
    {
        return User::class;
    }

    /**
     * 被操作的對象關聯（多型關聯）
     *
     * @return MorphTo
     */
    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * 此活動觸發的安全警報
     *
     * @return HasMany
     */
    public function alerts(): HasMany
    {
        return $this->hasMany(SecurityAlert::class);
    }



    /**
     * 取得活動的圖示
     *
     * @return string
     */
    public function getIconAttribute(): string
    {
        return match ($this->type) {
            self::TYPE_LOGIN => 'login',
            self::TYPE_LOGOUT => 'logout',
            self::TYPE_CREATE_USER, self::TYPE_CREATE_ROLE => 'plus-circle',
            self::TYPE_UPDATE_USER, self::TYPE_UPDATE_ROLE, self::TYPE_UPDATE_PERMISSIONS => 'pencil',
            self::TYPE_DELETE_USER, self::TYPE_DELETE_ROLE => 'trash',
            self::TYPE_ASSIGN_ROLE, self::TYPE_REMOVE_ROLE => 'user-group',
            self::TYPE_VIEW_DASHBOARD => 'chart-bar',
            self::TYPE_EXPORT_DATA => 'download',
            self::TYPE_QUICK_ACTION => 'lightning-bolt',
            default => 'information-circle',
        };
    }

    /**
     * 取得活動的顏色
     *
     * @return string
     */
    public function getColorAttribute(): string
    {
        return match ($this->type) {
            self::TYPE_LOGIN, self::TYPE_CREATE_USER, self::TYPE_CREATE_ROLE => 'success',
            self::TYPE_LOGOUT => 'info',
            self::TYPE_UPDATE_USER, self::TYPE_UPDATE_ROLE, self::TYPE_UPDATE_PERMISSIONS => 'warning',
            self::TYPE_DELETE_USER, self::TYPE_DELETE_ROLE => 'danger',
            self::TYPE_ASSIGN_ROLE, self::TYPE_REMOVE_ROLE => 'primary',
            self::TYPE_VIEW_DASHBOARD, self::TYPE_EXPORT_DATA => 'info',
            self::TYPE_QUICK_ACTION => 'purple',
            default => 'gray',
        };
    }

    /**
     * 取得格式化的時間
     *
     * @return string
     */
    public function getFormattedTimeAttribute(): string
    {
        return $this->created_at->diffForHumans();
    }

    /**
     * 取得詳細的時間
     *
     * @return string
     */
    public function getDetailedTimeAttribute(): string
    {
        return $this->created_at->format('Y-m-d H:i:s');
    }

    /**
     * 範圍查詢：按使用者篩選
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $userId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * 範圍查詢：按類型篩選
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $type
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * 範圍查詢：按模組篩選
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $module
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByModule($query, string $module)
    {
        return $query->where('module', $module);
    }

    /**
     * 範圍查詢：最近的活動
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $days
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeRecent($query, int $days = 7)
    {
        return $query->where('created_at', '>=', Carbon::now()->subDays($days));
    }

    /**
     * 範圍查詢：今天的活動
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeToday($query)
    {
        return $query->whereDate('created_at', Carbon::today());
    }

    /**
     * 範圍查詢：本週的活動
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeThisWeek($query)
    {
        return $query->whereBetween('created_at', [
            Carbon::now()->startOfWeek(),
            Carbon::now()->endOfWeek()
        ]);
    }

    /**
     * 範圍查詢：按日期範圍篩選
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $from
     * @param string $to
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByDateRange($query, string $from, string $to)
    {
        return $query->whereBetween('created_at', [
            Carbon::parse($from)->startOfDay(),
            Carbon::parse($to)->endOfDay()
        ]);
    }

    /**
     * 範圍查詢：安全事件
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeSecurityEvents($query)
    {
        return $query->whereIn('type', [
            'login_failed',
            'permission_escalation',
            'sensitive_data_access',
            'system_config_change',
            'suspicious_ip_access',
            'bulk_operation'
        ]);
    }

    /**
     * 範圍查詢：高風險活動
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeHighRisk($query)
    {
        return $query->where('risk_level', '>=', 7);
    }

    /**
     * 範圍查詢：按結果篩選
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $result
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByResult($query, string $result)
    {
        return $query->where('result', $result);
    }

    /**
     * 範圍查詢：按IP位址篩選
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $ip
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByIp($query, string $ip)
    {
        return $query->where('ip_address', 'like', "%{$ip}%");
    }

    /**
     * 取得格式化的屬性資料
     *
     * @return array
     */
    public function getFormattedPropertiesAttribute(): array
    {
        if (!$this->properties) {
            return [];
        }

        return $this->properties;
    }

    /**
     * 取得風險等級文字
     *
     * @return string
     */
    public function getRiskLevelTextAttribute(): string
    {
        return match ($this->risk_level ?? 0) {
            0, 1, 2 => '低',
            3, 4, 5 => '中',
            6, 7, 8 => '高',
            9, 10 => '極高',
            default => '未知',
        };
    }

    /**
     * 是否為安全事件
     *
     * @return bool
     */
    public function getIsSecurityEventAttribute(): bool
    {
        return in_array($this->type, [
            'login_failed',
            'permission_escalation',
            'sensitive_data_access',
            'system_config_change',
            'suspicious_ip_access',
            'bulk_operation'
        ]);
    }

    /**
     * 取得相關活動
     *
     * @return Collection
     */
    public function getRelatedActivitiesAttribute(): Collection
    {
        return static::where('id', '!=', $this->id)
            ->where(function ($query) {
                $query->where('user_id', $this->user_id)
                      ->orWhere(function ($q) {
                          if ($this->subject_id && $this->subject_type) {
                              $q->where('subject_id', $this->subject_id)
                                ->where('subject_type', $this->subject_type);
                          }
                      })
                      ->orWhere('ip_address', $this->ip_address);
            })
            ->where('created_at', '>=', $this->created_at->subHours(24))
            ->where('created_at', '<=', $this->created_at->addHours(24))
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();
    }

    /**
     * 驗證完整性
     *
     * @return bool
     */
    public function verifyIntegrity(): bool
    {
        if (!config('activity-security.integrity.enabled', true)) {
            return true; // 如果未啟用完整性檢查，視為通過
        }

        $integrityService = app(\App\Services\ActivityIntegrityService::class);
        return $integrityService->verifyActivity($this);
    }

    /**
     * 生成數位簽章
     *
     * @param array|null $data
     * @return string
     */
    public function generateSignature(?array $data = null): string
    {
        if (!config('activity-security.integrity.enabled', true)) {
            return ''; // 如果未啟用完整性檢查，返回空字串
        }

        $integrityService = app(\App\Services\ActivityIntegrityService::class);
        
        if ($data) {
            return $integrityService->generateSignature($data);
        }
        
        return $integrityService->regenerateSignature($this);
    }

    /**
     * 檢查使用者是否有權限存取此活動記錄
     *
     * @param \App\Models\User|null $user
     * @param string $action
     * @return bool
     */
    public function canAccess(?User $user = null, string $action = 'view'): bool
    {
        if (!$user) {
            $user = auth()->user();
        }

        if (!$user) {
            return false;
        }

        $securityService = app(\App\Services\ActivitySecurityService::class);
        $result = $securityService->checkAccessPermission($user, $action, $this);
        
        return $result['allowed'];
    }

    /**
     * 取得過濾後的活動記錄資料
     *
     * @param \App\Models\User|null $user
     * @return array
     */
    public function getFilteredData(?User $user = null): array
    {
        if (!$user) {
            $user = auth()->user();
        }

        if (!$user) {
            return [];
        }

        $securityService = app(\App\Services\ActivitySecurityService::class);
        return $securityService->filterActivityData($this, $user);
    }

    /**
     * 檢查是否為敏感活動記錄
     *
     * @return bool
     */
    public function isSensitive(): bool
    {
        $sensitiveTypes = [
            'login_failed',
            'permission_escalation', 
            'sensitive_data_access',
            'system_config_change',
            'unauthorized_access',
            'data_breach',
            'privilege_abuse'
        ];

        return in_array($this->type, $sensitiveTypes) || ($this->risk_level ?? 0) >= 7;
    }

    /**
     * 檢查是否為受保護的活動記錄
     *
     * @return bool
     */
    public function isProtected(): bool
    {
        $protectedTypes = config('activity-security.protection.protected_types', []);
        $protectionPeriod = config('activity-security.protection.protection_period', 30);

        // 檢查類型是否受保護
        if (in_array($this->type, $protectedTypes)) {
            return true;
        }

        // 檢查是否在保護期限內
        if ($this->created_at->diffInDays(now()) < $protectionPeriod) {
            return true;
        }

        // 檢查是否為敏感活動記錄
        if ($this->isSensitive()) {
            return true;
        }

        return false;
    }

    /**
     * 建立活動記錄的靜態方法
     *
     * @param string $type
     * @param string $description
     * @param array $options
     * @return static
     */
    public static function log(string $type, string $description, array $options = []): static
    {
        // 取得正確的使用者 ID
        $userId = $options['user_id'] ?? null;
        if ($userId === null && auth()->check()) {
            // 如果認證系統使用 username 作為識別符，我們需要取得實際的 ID
            $user = auth()->user();
            $userId = $user ? $user->getKey() : null;
        }

        $data = [
            'type' => $type,
            'description' => $description,
            'module' => $options['module'] ?? null,
            'user_id' => $userId,
            'subject_id' => $options['subject_id'] ?? null,
            'subject_type' => $options['subject_type'] ?? null,
            'properties' => $options['properties'] ?? null,
            'ip_address' => $options['ip_address'] ?? request()->ip(),
            'user_agent' => $options['user_agent'] ?? request()->userAgent(),
            'result' => $options['result'] ?? 'success',
            'risk_level' => $options['risk_level'] ?? 1,
        ];

        // 生成數位簽章
        $payload = json_encode(array_filter($data), 64); // JSON_SORT_KEYS
        $data['signature'] = hash_hmac('sha256', $payload, config('app.key'));

        return static::create($data);
    }
}
