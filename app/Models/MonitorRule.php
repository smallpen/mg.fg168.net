<?php

namespace App\Models;

use App\Models\Activity;
use App\Models\SecurityAlert;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
        'triggered_count',
        'last_triggered_at'
    ];

    protected $casts = [
        'conditions' => 'json',
        'actions' => 'json',
        'is_active' => 'boolean',
        'last_triggered_at' => 'datetime',
        'triggered_count' => 'integer',
        'priority' => 'integer'
    ];

    /**
     * 關聯：規則建立者
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * 關聯：此規則產生的警報
     */
    public function alerts(): HasMany
    {
        return $this->hasMany(SecurityAlert::class, 'rule_id');
    }

    /**
     * 計算屬性：觸發次數
     */
    public function getTriggeredCountAttribute(): int
    {
        return $this->attributes['triggered_count'] ?? 0;
    }

    /**
     * 計算屬性：最後觸發時間
     */
    public function getLastTriggeredAttribute(): ?Carbon
    {
        return $this->last_triggered_at;
    }

    /**
     * 檢查活動是否符合此規則的條件
     */
    public function matches(Activity $activity): bool
    {
        if (!$this->is_active) {
            return false;
        }

        $conditions = $this->conditions ?? [];
        
        foreach ($conditions as $condition) {
            if (!$this->evaluateCondition($condition, $activity)) {
                return false;
            }
        }

        return true;
    }

    /**
     * 執行規則動作
     */
    public function execute(Activity $activity): void
    {
        $actions = $this->actions ?? [];

        foreach ($actions as $action) {
            $this->executeAction($action, $activity);
        }

        // 更新觸發統計
        $this->increment('triggered_count');
        $this->update(['last_triggered_at' => now()]);
    }

    /**
     * 評估單一條件
     */
    protected function evaluateCondition(array $condition, Activity $activity): bool
    {
        $field = $condition['field'] ?? '';
        $operator = $condition['operator'] ?? '=';
        $value = $condition['value'] ?? '';

        $activityValue = $this->getActivityFieldValue($field, $activity);

        return match ($operator) {
            '=' => $activityValue == $value,
            '!=' => $activityValue != $value,
            '>' => $activityValue > $value,
            '<' => $activityValue < $value,
            '>=' => $activityValue >= $value,
            '<=' => $activityValue <= $value,
            'contains' => str_contains(strtolower($activityValue), strtolower($value)),
            'not_contains' => !str_contains(strtolower($activityValue), strtolower($value)),
            'starts_with' => str_starts_with(strtolower($activityValue), strtolower($value)),
            'ends_with' => str_ends_with(strtolower($activityValue), strtolower($value)),
            'in' => in_array($activityValue, (array) $value),
            'not_in' => !in_array($activityValue, (array) $value),
            'regex' => preg_match($value, $activityValue),
            default => false,
        };
    }

    /**
     * 取得活動記錄的欄位值
     */
    protected function getActivityFieldValue(string $field, Activity $activity): mixed
    {
        return match ($field) {
            'type' => $activity->type,
            'description' => $activity->description,
            'result' => $activity->result,
            'risk_level' => $activity->risk_level,
            'ip_address' => $activity->ip_address,
            'user_agent' => $activity->user_agent,
            'user_id' => $activity->user_id,
            'subject_id' => $activity->subject_id,
            'subject_type' => $activity->subject_type,
            'created_at' => $activity->created_at,
            'properties' => $activity->properties,
            'user_name' => $activity->user?->name,
            'user_email' => $activity->user?->email,
            default => null,
        };
    }

    /**
     * 執行單一動作
     */
    protected function executeAction(array $action, Activity $activity): void
    {
        $type = $action['type'] ?? '';

        match ($type) {
            'create_alert' => $this->createAlert($action, $activity),
            'send_notification' => $this->sendNotification($action, $activity),
            'block_ip' => $this->blockIp($action, $activity),
            'disable_user' => $this->disableUser($action, $activity),
            'log_event' => $this->logEvent($action, $activity),
            default => null,
        };
    }

    /**
     * 建立安全警報
     */
    protected function createAlert(array $action, Activity $activity): void
    {
        $severity = $action['severity'] ?? 'medium';
        $title = $action['title'] ?? "監控規則觸發：{$this->name}";
        $description = $action['description'] ?? "活動 #{$activity->id} 觸發了監控規則";

        SecurityAlert::create([
            'activity_id' => $activity->id,
            'rule_id' => $this->id,
            'type' => 'rule_triggered',
            'severity' => $severity,
            'title' => $title,
            'description' => $description,
        ]);

        // 發送即時事件
        event('security-alert', [
            'title' => $title,
            'description' => $description,
            'severity' => $severity,
            'activity_id' => $activity->id,
            'rule_id' => $this->id,
        ]);
    }

    /**
     * 發送通知
     */
    protected function sendNotification(array $action, Activity $activity): void
    {
        // 這裡可以整合郵件、Slack、SMS 等通知系統
        // 暫時記錄通知事件
        activity()
            ->causedBy($activity->user)
            ->withProperties([
                'rule_id' => $this->id,
                'rule_name' => $this->name,
                'original_activity_id' => $activity->id,
                'notification_type' => $action['notification_type'] ?? 'email',
                'recipients' => $action['recipients'] ?? [],
            ])
            ->log("監控規則通知：{$this->name}");
    }

    /**
     * 封鎖 IP 位址
     */
    protected function blockIp(array $action, Activity $activity): void
    {
        if (!$activity->ip_address) {
            return;
        }

        // 這裡可以整合防火牆或安全系統
        // 暫時記錄封鎖動作
        activity()
            ->causedBy($activity->user)
            ->withProperties([
                'rule_id' => $this->id,
                'rule_name' => $this->name,
                'blocked_ip' => $activity->ip_address,
                'original_activity_id' => $activity->id,
                'block_duration' => $action['duration'] ?? '1 hour',
            ])
            ->log("自動封鎖 IP：{$activity->ip_address}");
    }

    /**
     * 停用使用者
     */
    protected function disableUser(array $action, Activity $activity): void
    {
        if (!$activity->user_id) {
            return;
        }

        $user = $activity->user;
        if ($user && $user->is_active) {
            $user->update(['is_active' => false]);

            activity()
                ->causedBy($user)
                ->withProperties([
                    'rule_id' => $this->id,
                    'rule_name' => $this->name,
                    'disabled_user_id' => $user->id,
                    'original_activity_id' => $activity->id,
                    'reason' => $action['reason'] ?? '觸發安全監控規則',
                ])
                ->log("自動停用使用者：{$user->name}");
        }
    }

    /**
     * 記錄事件
     */
    protected function logEvent(array $action, Activity $activity): void
    {
        $logLevel = $action['level'] ?? 'info';
        $message = $action['message'] ?? "監控規則 {$this->name} 被觸發";

        activity()
            ->causedBy($activity->user)
            ->withProperties([
                'rule_id' => $this->id,
                'rule_name' => $this->name,
                'original_activity_id' => $activity->id,
                'log_level' => $logLevel,
                'custom_message' => $message,
            ])
            ->log($message);
    }

    /**
     * 範圍查詢：啟用的規則
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * 範圍查詢：依優先級排序
     */
    public function scopeByPriority($query)
    {
        return $query->orderBy('priority', 'desc');
    }

    /**
     * 範圍查詢：最近觸發的規則
     */
    public function scopeRecentlyTriggered($query, int $hours = 24)
    {
        return $query->where('last_triggered_at', '>=', now()->subHours($hours));
    }

    /**
     * 預設的監控規則範本
     */
    public static function getDefaultRules(): array
    {
        return [
            [
                'name' => '登入失敗監控',
                'description' => '監控連續登入失敗嘗試',
                'conditions' => [
                    [
                        'field' => 'type',
                        'operator' => '=',
                        'value' => 'login_failed'
                    ]
                ],
                'actions' => [
                    [
                        'type' => 'create_alert',
                        'severity' => 'high',
                        'title' => '檢測到登入失敗',
                        'description' => '使用者嘗試登入失敗'
                    ]
                ],
                'priority' => 5,
                'is_active' => true
            ],
            [
                'name' => '權限提升監控',
                'description' => '監控權限提升操作',
                'conditions' => [
                    [
                        'field' => 'description',
                        'operator' => 'contains',
                        'value' => '權限'
                    ],
                    [
                        'field' => 'risk_level',
                        'operator' => '>',
                        'value' => 3
                    ]
                ],
                'actions' => [
                    [
                        'type' => 'create_alert',
                        'severity' => 'high',
                        'title' => '檢測到權限提升操作',
                        'description' => '使用者執行了權限相關操作'
                    ]
                ],
                'priority' => 4,
                'is_active' => true
            ],
            [
                'name' => '異常 IP 監控',
                'description' => '監控來自異常 IP 的存取',
                'conditions' => [
                    [
                        'field' => 'ip_address',
                        'operator' => 'not_in',
                        'value' => ['192.168.1.0/24', '10.0.0.0/8']
                    ]
                ],
                'actions' => [
                    [
                        'type' => 'create_alert',
                        'severity' => 'medium',
                        'title' => '檢測到異常 IP 存取',
                        'description' => '來自非內網 IP 的存取'
                    ]
                ],
                'priority' => 3,
                'is_active' => false
            ]
        ];
    }
}