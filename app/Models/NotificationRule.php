<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;

/**
 * 通知規則模型
 * 
 * 管理活動記錄的通知規則，包含條件判斷和動作執行
 */
class NotificationRule extends Model
{
    use HasFactory;

    /**
     * 可批量賦值的屬性
     */
    protected $fillable = [
        'name',
        'description',
        'conditions',
        'actions',
        'is_active',
        'priority',
        'created_by',
        'triggered_count',
        'last_triggered_at'
    ];

    /**
     * 屬性轉換
     */
    protected $casts = [
        'conditions' => 'json',
        'actions' => 'json',
        'is_active' => 'boolean',
        'last_triggered_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * 優先級常數
     */
    const PRIORITY_LOW = 1;
    const PRIORITY_NORMAL = 2;
    const PRIORITY_HIGH = 3;
    const PRIORITY_URGENT = 4;

    /**
     * 條件類型常數
     */
    const CONDITION_ACTIVITY_TYPE = 'activity_type';
    const CONDITION_RISK_LEVEL = 'risk_level';
    const CONDITION_USER = 'user';
    const CONDITION_IP_PATTERN = 'ip_pattern';
    const CONDITION_TIME_RANGE = 'time_range';
    const CONDITION_FREQUENCY = 'frequency';

    /**
     * 動作類型常數
     */
    const ACTION_EMAIL = 'email';
    const ACTION_BROWSER = 'browser';
    const ACTION_WEBHOOK = 'webhook';
    const ACTION_SECURITY_ALERT = 'security_alert';

    /**
     * 關聯到建立者
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * 關聯到安全警報
     */
    public function alerts(): HasMany
    {
        return $this->hasMany(SecurityAlert::class, 'rule_id');
    }

    /**
     * 關聯到通知
     */
    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class, 'data->rule_id');
    }

    /**
     * 查詢範圍：啟用的規則
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * 查詢範圍：停用的規則
     */
    public function scopeInactive(Builder $query): Builder
    {
        return $query->where('is_active', false);
    }

    /**
     * 查詢範圍：按優先級排序
     */
    public function scopeByPriority(Builder $query): Builder
    {
        return $query->orderBy('priority', 'desc');
    }

    /**
     * 查詢範圍：最近觸發的規則
     */
    public function scopeRecentlyTriggered(Builder $query, int $hours = 24): Builder
    {
        return $query->where('last_triggered_at', '>=', Carbon::now()->subHours($hours));
    }

    /**
     * 查詢範圍：按建立者篩選
     */
    public function scopeByCreator(Builder $query, int $userId): Builder
    {
        return $query->where('created_by', $userId);
    }

    /**
     * 檢查規則是否符合活動
     */
    public function matches(Activity $activity): bool
    {
        $conditions = $this->conditions ?? [];

        // 檢查活動類型
        if (isset($conditions['activity_types']) && !empty($conditions['activity_types'])) {
            if (!in_array($activity->type, $conditions['activity_types'])) {
                return false;
            }
        }

        // 檢查風險等級
        if (isset($conditions['min_risk_level'])) {
            if ($activity->risk_level < $conditions['min_risk_level']) {
                return false;
            }
        }

        // 檢查使用者
        if (isset($conditions['user_ids']) && !empty($conditions['user_ids'])) {
            if (!in_array($activity->causer_id, $conditions['user_ids'])) {
                return false;
            }
        }

        // 檢查 IP 位址模式
        if (isset($conditions['ip_patterns']) && !empty($conditions['ip_patterns'])) {
            $matched = false;
            foreach ($conditions['ip_patterns'] as $pattern) {
                if ($this->matchesIpPattern($activity->ip_address, $pattern)) {
                    $matched = true;
                    break;
                }
            }
            if (!$matched) {
                return false;
            }
        }

        // 檢查時間範圍
        if (isset($conditions['time_range'])) {
            if (!$this->isWithinTimeRange($activity->created_at, $conditions['time_range'])) {
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
            try {
                $this->executeAction($action, $activity);
            } catch (\Exception $e) {
                \Log::error('執行通知規則動作失敗', [
                    'rule_id' => $this->id,
                    'action' => $action,
                    'activity_id' => $activity->id,
                    'error' => $e->getMessage()
                ]);
            }
        }

        // 更新觸發統計
        $this->increment('triggered_count');
        $this->update(['last_triggered_at' => Carbon::now()]);
    }

    /**
     * 執行單個動作
     */
    protected function executeAction(array $action, Activity $activity): void
    {
        switch ($action['type']) {
            case self::ACTION_EMAIL:
                $this->executeEmailAction($action, $activity);
                break;

            case self::ACTION_BROWSER:
                $this->executeBrowserAction($action, $activity);
                break;

            case self::ACTION_WEBHOOK:
                $this->executeWebhookAction($action, $activity);
                break;

            case self::ACTION_SECURITY_ALERT:
                $this->executeSecurityAlertAction($action, $activity);
                break;

            default:
                throw new \InvalidArgumentException("未知的動作類型: {$action['type']}");
        }
    }

    /**
     * 執行郵件動作
     */
    protected function executeEmailAction(array $action, Activity $activity): void
    {
        // 實作郵件發送邏輯
        // 這裡會被 ActivityNotificationService 處理
    }

    /**
     * 執行瀏覽器通知動作
     */
    protected function executeBrowserAction(array $action, Activity $activity): void
    {
        // 實作瀏覽器通知邏輯
        // 這裡會被 ActivityNotificationService 處理
    }

    /**
     * 執行 Webhook 動作
     */
    protected function executeWebhookAction(array $action, Activity $activity): void
    {
        // 實作 Webhook 發送邏輯
        // 這裡會被 ActivityNotificationService 處理
    }

    /**
     * 執行安全警報動作
     */
    protected function executeSecurityAlertAction(array $action, Activity $activity): void
    {
        SecurityAlert::create([
            'activity_id' => $activity->id,
            'type' => $action['alert_type'] ?? 'rule_triggered',
            'severity' => $action['severity'] ?? 'warning',
            'title' => $action['title'] ?? "規則觸發: {$this->name}",
            'description' => $action['description'] ?? "通知規則 '{$this->name}' 被觸發",
            'rule_id' => $this->id
        ]);
    }

    /**
     * 檢查 IP 模式匹配
     */
    protected function matchesIpPattern(string $ip, string $pattern): bool
    {
        if (strpos($pattern, '/') !== false) {
            // CIDR 表示法
            return $this->ipInCidr($ip, $pattern);
        } elseif (strpos($pattern, '*') !== false) {
            // 萬用字元模式
            $pattern = str_replace('*', '.*', $pattern);
            return preg_match("/^{$pattern}$/", $ip);
        } else {
            // 精確匹配
            return $ip === $pattern;
        }
    }

    /**
     * 檢查 IP 是否在 CIDR 範圍內
     */
    protected function ipInCidr(string $ip, string $cidr): bool
    {
        list($subnet, $mask) = explode('/', $cidr);
        
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            return (ip2long($ip) & ~((1 << (32 - $mask)) - 1)) === ip2long($subnet);
        }
        
        return false;
    }

    /**
     * 檢查是否在時間範圍內
     */
    protected function isWithinTimeRange(Carbon $time, array $timeRange): bool
    {
        $hour = $time->hour;
        $dayOfWeek = $time->dayOfWeek;

        if (isset($timeRange['hours'])) {
            if (!in_array($hour, $timeRange['hours'])) {
                return false;
            }
        }

        if (isset($timeRange['days'])) {
            if (!in_array($dayOfWeek, $timeRange['days'])) {
                return false;
            }
        }

        return true;
    }

    /**
     * 取得優先級標籤
     */
    public function getPriorityLabelAttribute(): string
    {
        $labels = [
            self::PRIORITY_LOW => '低',
            self::PRIORITY_NORMAL => '一般',
            self::PRIORITY_HIGH => '高',
            self::PRIORITY_URGENT => '緊急',
        ];

        return $labels[$this->priority] ?? '未知';
    }

    /**
     * 取得優先級顏色
     */
    public function getPriorityColorAttribute(): string
    {
        $colors = [
            self::PRIORITY_LOW => 'gray',
            self::PRIORITY_NORMAL => 'blue',
            self::PRIORITY_HIGH => 'yellow',
            self::PRIORITY_URGENT => 'red',
        ];

        return $colors[$this->priority] ?? 'gray';
    }

    /**
     * 取得條件摘要
     */
    public function getConditionsSummaryAttribute(): string
    {
        $conditions = $this->conditions ?? [];
        $summary = [];

        if (isset($conditions['activity_types']) && !empty($conditions['activity_types'])) {
            $summary[] = '活動類型: ' . implode(', ', $conditions['activity_types']);
        }

        if (isset($conditions['min_risk_level'])) {
            $summary[] = '最低風險等級: ' . $conditions['min_risk_level'];
        }

        if (isset($conditions['user_ids']) && !empty($conditions['user_ids'])) {
            $summary[] = '特定使用者: ' . count($conditions['user_ids']) . ' 個';
        }

        if (isset($conditions['ip_patterns']) && !empty($conditions['ip_patterns'])) {
            $summary[] = 'IP 模式: ' . count($conditions['ip_patterns']) . ' 個';
        }

        return implode('; ', $summary) ?: '無特定條件';
    }

    /**
     * 取得動作摘要
     */
    public function getActionsSummaryAttribute(): string
    {
        $actions = $this->actions ?? [];
        $summary = [];

        foreach ($actions as $action) {
            switch ($action['type']) {
                case self::ACTION_EMAIL:
                    $summary[] = '郵件通知';
                    break;
                case self::ACTION_BROWSER:
                    $summary[] = '瀏覽器通知';
                    break;
                case self::ACTION_WEBHOOK:
                    $summary[] = 'Webhook';
                    break;
                case self::ACTION_SECURITY_ALERT:
                    $summary[] = '安全警報';
                    break;
            }
        }

        return implode(', ', $summary) ?: '無動作';
    }

    /**
     * 取得觸發頻率
     */
    public function getTriggerFrequencyAttribute(): string
    {
        if (!$this->triggered_count || !$this->created_at) {
            return '從未觸發';
        }

        $days = $this->created_at->diffInDays(Carbon::now());
        if ($days === 0) {
            return "今日觸發 {$this->triggered_count} 次";
        }

        $avgPerDay = round($this->triggered_count / max($days, 1), 1);
        return "平均每日觸發 {$avgPerDay} 次";
    }

    /**
     * 驗證規則配置
     */
    public function validate(): array
    {
        $errors = [];

        // 檢查必要欄位
        if (empty($this->name)) {
            $errors[] = '規則名稱不能為空';
        }

        if (empty($this->conditions)) {
            $errors[] = '必須設定至少一個條件';
        }

        if (empty($this->actions)) {
            $errors[] = '必須設定至少一個動作';
        }

        // 檢查條件格式
        if ($this->conditions) {
            $this->validateConditions($errors);
        }

        // 檢查動作格式
        if ($this->actions) {
            $this->validateActions($errors);
        }

        return $errors;
    }

    /**
     * 驗證條件配置
     */
    protected function validateConditions(array &$errors): void
    {
        $conditions = $this->conditions;

        if (isset($conditions['min_risk_level'])) {
            if (!is_numeric($conditions['min_risk_level']) || 
                $conditions['min_risk_level'] < 0 || 
                $conditions['min_risk_level'] > 10) {
                $errors[] = '風險等級必須是 0-10 之間的數字';
            }
        }

        if (isset($conditions['ip_patterns'])) {
            foreach ($conditions['ip_patterns'] as $pattern) {
                if (!$this->isValidIpPattern($pattern)) {
                    $errors[] = "無效的 IP 模式: {$pattern}";
                }
            }
        }
    }

    /**
     * 驗證動作配置
     */
    protected function validateActions(array &$errors): void
    {
        foreach ($this->actions as $action) {
            if (!isset($action['type'])) {
                $errors[] = '動作必須指定類型';
                continue;
            }

            switch ($action['type']) {
                case self::ACTION_EMAIL:
                    if (!isset($action['template'])) {
                        $errors[] = '郵件動作必須指定範本';
                    }
                    break;

                case self::ACTION_WEBHOOK:
                    if (!isset($action['url']) || !filter_var($action['url'], FILTER_VALIDATE_URL)) {
                        $errors[] = 'Webhook 動作必須指定有效的 URL';
                    }
                    break;
            }
        }
    }

    /**
     * 檢查 IP 模式是否有效
     */
    protected function isValidIpPattern(string $pattern): bool
    {
        // 檢查 CIDR 表示法
        if (strpos($pattern, '/') !== false) {
            list($ip, $mask) = explode('/', $pattern);
            return filter_var($ip, FILTER_VALIDATE_IP) && is_numeric($mask) && $mask >= 0 && $mask <= 32;
        }

        // 檢查萬用字元模式
        if (strpos($pattern, '*') !== false) {
            $testPattern = str_replace('*', '1', $pattern);
            return filter_var($testPattern, FILTER_VALIDATE_IP) !== false;
        }

        // 檢查精確 IP
        return filter_var($pattern, FILTER_VALIDATE_IP) !== false;
    }

    /**
     * 複製規則
     */
    public function duplicate(string $newName = null): self
    {
        $newName = $newName ?? $this->name . ' (副本)';

        return self::create([
            'name' => $newName,
            'description' => $this->description,
            'conditions' => $this->conditions,
            'actions' => $this->actions,
            'is_active' => false, // 副本預設為停用
            'priority' => $this->priority,
            'created_by' => auth()->id() ?? $this->created_by,
        ]);
    }

    /**
     * 取得規則統計資訊
     */
    public static function getStatistics(): array
    {
        return [
            'total' => self::count(),
            'active' => self::active()->count(),
            'inactive' => self::inactive()->count(),
            'recently_triggered' => self::recentlyTriggered()->count(),
            'by_priority' => self::selectRaw('priority, COUNT(*) as count')
                                ->groupBy('priority')
                                ->pluck('count', 'priority')
                                ->toArray(),
        ];
    }
}