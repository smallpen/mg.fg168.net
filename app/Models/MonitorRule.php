<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Carbon\Carbon;

/**
 * 監控規則模型
 * 定義安全監控的規則和條件
 */
class MonitorRule extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'conditions',
        'actions',
        'is_active',
        'created_by',
        'priority',
        'threshold',
        'time_window',
        'cooldown_period'
    ];

    protected $casts = [
        'conditions' => 'json',
        'actions' => 'json',
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * 優先級定義
     */
    const PRIORITIES = [
        'low' => 1,
        'medium' => 2,
        'high' => 3,
        'critical' => 4
    ];

    /**
     * 預設監控規則
     */
    const DEFAULT_RULES = [
        'login_failures' => [
            'name' => '登入失敗監控',
            'description' => '監控短時間內多次登入失敗',
            'conditions' => [
                'activity_type' => 'login',
                'result' => 'failed',
                'count_threshold' => 5,
                'time_window' => 300 // 5分鐘
            ],
            'actions' => [
                'create_alert' => true,
                'block_ip' => false,
                'notify_admin' => true
            ]
        ],
        'privilege_escalation' => [
            'name' => '權限提升監控',
            'description' => '監控權限提升操作',
            'conditions' => [
                'activity_type' => ['role_assignment', 'permission_grant'],
                'count_threshold' => 1,
                'time_window' => 0
            ],
            'actions' => [
                'create_alert' => true,
                'notify_admin' => true,
                'require_approval' => true
            ]
        ],
        'bulk_operations' => [
            'name' => '批量操作監控',
            'description' => '監控大量資料操作',
            'conditions' => [
                'activity_type' => ['create', 'update', 'delete'],
                'count_threshold' => 20,
                'time_window' => 600 // 10分鐘
            ],
            'actions' => [
                'create_alert' => true,
                'notify_admin' => false,
                'log_detailed' => true
            ]
        ]
    ];

    // ==================== 關聯關係 ====================

    /**
     * 建立規則的使用者
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * 此規則觸發的警報
     */
    public function alerts(): HasMany
    {
        return $this->hasMany(SecurityAlert::class, 'rule_id');
    }

    // ==================== 計算屬性 ====================

    /**
     * 獲取優先級文字
     */
    public function getPriorityTextAttribute(): string
    {
        $priorities = [
            1 => '低',
            2 => '中',
            3 => '高',
            4 => '嚴重'
        ];

        return $priorities[$this->priority] ?? '未知';
    }

    /**
     * 獲取觸發次數
     */
    public function getTriggeredCountAttribute(): int
    {
        return $this->alerts()->count();
    }

    /**
     * 獲取最後觸發時間
     */
    public function getLastTriggeredAttribute(): ?Carbon
    {
        $lastAlert = $this->alerts()->latest()->first();
        return $lastAlert ? $lastAlert->created_at : null;
    }

    /**
     * 檢查規則是否在冷卻期
     */
    public function getIsInCooldownAttribute(): bool
    {
        if (!$this->cooldown_period || !$this->last_triggered) {
            return false;
        }

        return $this->last_triggered->addSeconds($this->cooldown_period)->isFuture();
    }

    /**
     * 獲取規則效率（觸發次數/天）
     */
    public function getEfficiencyAttribute(): float
    {
        $daysSinceCreated = $this->created_at->diffInDays(now()) ?: 1;
        return round($this->triggered_count / $daysSinceCreated, 2);
    }

    // ==================== 規則檢查方法 ====================

    /**
     * 檢查活動是否符合此規則
     *
     * @param Activity $activity
     * @return bool
     */
    public function matches(Activity $activity): bool
    {
        if (!$this->is_active || $this->is_in_cooldown) {
            return false;
        }

        $conditions = $this->conditions;

        // 檢查活動類型
        if (isset($conditions['activity_type'])) {
            if (is_array($conditions['activity_type'])) {
                if (!in_array($activity->type, $conditions['activity_type'])) {
                    return false;
                }
            } else {
                if ($activity->type !== $conditions['activity_type']) {
                    return false;
                }
            }
        }

        // 檢查結果狀態
        if (isset($conditions['result']) && $activity->result !== $conditions['result']) {
            return false;
        }

        // 檢查風險等級
        if (isset($conditions['min_risk_level']) && $activity->risk_level < $conditions['min_risk_level']) {
            return false;
        }

        // 檢查使用者條件
        if (isset($conditions['user_id']) && $activity->user_id !== $conditions['user_id']) {
            return false;
        }

        // 檢查 IP 條件
        if (isset($conditions['ip_address']) && $activity->ip_address !== $conditions['ip_address']) {
            return false;
        }

        // 檢查時間窗口內的計數閾值
        if (isset($conditions['count_threshold']) && $conditions['count_threshold'] > 1) {
            return $this->checkCountThreshold($activity, $conditions);
        }

        return true;
    }

    /**
     * 執行規則動作
     *
     * @param Activity $activity
     * @return void
     */
    public function execute(Activity $activity): void
    {
        $actions = $this->actions;

        // 建立安全警報
        if ($actions['create_alert'] ?? false) {
            $this->createAlert($activity);
        }

        // 通知管理員
        if ($actions['notify_admin'] ?? false) {
            $this->notifyAdmin($activity);
        }

        // 封鎖 IP
        if ($actions['block_ip'] ?? false) {
            $this->blockIP($activity);
        }

        // 記錄詳細日誌
        if ($actions['log_detailed'] ?? false) {
            $this->logDetailed($activity);
        }

        // 需要審批
        if ($actions['require_approval'] ?? false) {
            $this->requireApproval($activity);
        }

        // 更新觸發統計
        $this->updateTriggerStats();
    }

    // ==================== 查詢範圍 ====================

    /**
     * 啟用的規則
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * 按優先級排序
     */
    public function scopeByPriority($query)
    {
        return $query->orderByDesc('priority');
    }

    /**
     * 按類型篩選
     */
    public function scopeByType($query, string $type)
    {
        return $query->whereJsonContains('conditions->activity_type', $type);
    }

    /**
     * 最近觸發的規則
     */
    public function scopeRecentlyTriggered($query, int $hours = 24)
    {
        return $query->whereHas('alerts', function ($alertQuery) use ($hours) {
            $alertQuery->where('created_at', '>=', now()->subHours($hours));
        });
    }

    // ==================== 私有方法 ====================

    /**
     * 檢查計數閾值
     */
    protected function checkCountThreshold(Activity $activity, array $conditions): bool
    {
        $timeWindow = $conditions['time_window'] ?? 300; // 預設5分鐘
        $threshold = $conditions['count_threshold'];
        $fromTime = now()->subSeconds($timeWindow);

        $query = Activity::where('created_at', '>=', $fromTime);

        // 應用相同的條件
        if (isset($conditions['activity_type'])) {
            if (is_array($conditions['activity_type'])) {
                $query->whereIn('type', $conditions['activity_type']);
            } else {
                $query->where('type', $conditions['activity_type']);
            }
        }

        if (isset($conditions['result'])) {
            $query->where('result', $conditions['result']);
        }

        // 按相關欄位分組檢查
        if (isset($conditions['group_by'])) {
            $groupBy = $conditions['group_by'];
            if ($groupBy === 'ip_address') {
                $query->where('ip_address', $activity->ip_address);
            } elseif ($groupBy === 'user_id') {
                $query->where('user_id', $activity->user_id);
            }
        }

        return $query->count() >= $threshold;
    }

    /**
     * 建立安全警報
     */
    protected function createAlert(Activity $activity): void
    {
        SecurityAlert::create([
            'activity_id' => $activity->id,
            'rule_id' => $this->id,
            'type' => $this->getAlertType(),
            'severity' => $this->getAlertSeverity(),
            'title' => $this->name,
            'description' => $this->generateAlertDescription($activity)
        ]);
    }

    /**
     * 通知管理員
     */
    protected function notifyAdmin(Activity $activity): void
    {
        // 實作管理員通知邏輯
        event('admin-notification', [
            'type' => 'security_rule_triggered',
            'rule' => $this,
            'activity' => $activity,
            'message' => "監控規則「{$this->name}」被觸發"
        ]);
    }

    /**
     * 封鎖 IP
     */
    protected function blockIP(Activity $activity): void
    {
        if ($activity->ip_address) {
            // 實作 IP 封鎖邏輯
            // 可以將 IP 加入黑名單或防火牆規則
            cache()->put("blocked_ip_{$activity->ip_address}", true, now()->addHours(24));
            
            activity()
                ->withProperties(['ip_address' => $activity->ip_address, 'rule_id' => $this->id])
                ->log('ip_blocked_by_rule');
        }
    }

    /**
     * 記錄詳細日誌
     */
    protected function logDetailed(Activity $activity): void
    {
        activity()
            ->performedOn($activity)
            ->withProperties([
                'rule_id' => $this->id,
                'rule_name' => $this->name,
                'triggered_conditions' => $this->conditions,
                'original_activity' => $activity->toArray()
            ])
            ->log('security_rule_detailed_log');
    }

    /**
     * 需要審批
     */
    protected function requireApproval(Activity $activity): void
    {
        // 實作審批流程
        // 可以建立審批請求或暫停相關操作
        event('approval-required', [
            'type' => 'security_rule_approval',
            'rule' => $this,
            'activity' => $activity,
            'reason' => "監控規則「{$this->name}」要求審批"
        ]);
    }

    /**
     * 更新觸發統計
     */
    protected function updateTriggerStats(): void
    {
        // 可以在這裡更新規則的統計資訊
        // 例如觸發次數、最後觸發時間等
    }

    /**
     * 獲取警報類型
     */
    protected function getAlertType(): string
    {
        $conditions = $this->conditions;
        
        if (isset($conditions['activity_type'])) {
            $type = is_array($conditions['activity_type']) 
                ? $conditions['activity_type'][0] 
                : $conditions['activity_type'];
                
            return match($type) {
                'login' => 'login_failure',
                'role_assignment', 'permission_grant' => 'privilege_escalation',
                'create', 'update', 'delete' => 'bulk_operation',
                default => 'security_event'
            };
        }

        return 'security_event';
    }

    /**
     * 獲取警報嚴重性
     */
    protected function getAlertSeverity(): string
    {
        return match($this->priority) {
            4 => 'critical',
            3 => 'high',
            2 => 'medium',
            default => 'low'
        };
    }

    /**
     * 生成警報描述
     */
    protected function generateAlertDescription(Activity $activity): string
    {
        $description = "監控規則「{$this->name}」被觸發\n\n";
        $description .= "觸發活動：\n";
        $description .= "- 時間：{$activity->created_at}\n";
        $description .= "- 類型：{$activity->type}\n";
        $description .= "- 描述：{$activity->description}\n";
        $description .= "- 使用者：{$activity->causer_id}\n";
        $description .= "- IP 位址：{$activity->ip_address}\n\n";
        $description .= "規則條件：\n";
        $description .= json_encode($this->conditions, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        return $description;
    }

    // ==================== 靜態方法 ====================

    /**
     * 建立預設監控規則
     */
    public static function createDefaultRules(User $creator): void
    {
        foreach (self::DEFAULT_RULES as $key => $ruleData) {
            static::firstOrCreate(
                ['name' => $ruleData['name']],
                array_merge($ruleData, [
                    'created_by' => $creator->id,
                    'is_active' => true,
                    'priority' => 2
                ])
            );
        }
    }

    /**
     * 獲取規則統計
     */
    public static function getStatistics(): array
    {
        return [
            'total' => static::count(),
            'active' => static::active()->count(),
            'inactive' => static::where('is_active', false)->count(),
            'by_priority' => static::selectRaw('priority, COUNT(*) as count')
                ->groupBy('priority')
                ->pluck('count', 'priority')
                ->toArray(),
            'recently_triggered' => static::recentlyTriggered()->count(),
            'most_triggered' => static::withCount('alerts')
                ->orderByDesc('alerts_count')
                ->take(5)
                ->get()
                ->map(function ($rule) {
                    return [
                        'name' => $rule->name,
                        'count' => $rule->alerts_count
                    ];
                })->toArray()
        ];
    }
}