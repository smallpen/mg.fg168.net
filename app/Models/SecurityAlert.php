<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

/**
 * 安全警報模型
 * 記錄系統檢測到的安全事件和警報
 */
class SecurityAlert extends Model
{
    use HasFactory;

    protected $fillable = [
        'activity_id',
        'type',
        'severity',
        'title',
        'description',
        'rule_id',
        'acknowledged_at',
        'acknowledged_by'
    ];

    protected $casts = [
        'acknowledged_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * 嚴重性等級定義
     */
    const SEVERITY_LEVELS = [
        'low' => '低',
        'medium' => '中',
        'high' => '高',
        'critical' => '嚴重'
    ];

    /**
     * 嚴重性顏色對應
     */
    const SEVERITY_COLORS = [
        'low' => 'green',
        'medium' => 'yellow',
        'high' => 'orange',
        'critical' => 'red'
    ];

    // ==================== 關聯關係 ====================

    /**
     * 關聯的活動記錄
     */
    public function activity(): BelongsTo
    {
        return $this->belongsTo(Activity::class);
    }

    /**
     * 確認警報的使用者
     */
    public function acknowledgedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'acknowledged_by');
    }

    /**
     * 觸發警報的監控規則
     */
    public function rule(): BelongsTo
    {
        return $this->belongsTo(MonitorRule::class, 'rule_id');
    }

    // ==================== 計算屬性 ====================

    /**
     * 檢查警報是否已確認
     */
    public function getIsAcknowledgedAttribute(): bool
    {
        return !is_null($this->acknowledged_at);
    }

    /**
     * 獲取嚴重性顏色
     */
    public function getSeverityColorAttribute(): string
    {
        return self::SEVERITY_COLORS[$this->severity] ?? 'gray';
    }

    /**
     * 獲取嚴重性文字
     */
    public function getSeverityTextAttribute(): string
    {
        return self::SEVERITY_LEVELS[$this->severity] ?? '未知';
    }

    /**
     * 獲取警報年齡（多久前建立）
     */
    public function getAgeAttribute(): string
    {
        return $this->created_at->diffForHumans();
    }

    /**
     * 檢查是否為新警報（24小時內）
     */
    public function getIsNewAttribute(): bool
    {
        return $this->created_at->isAfter(now()->subDay());
    }

    /**
     * 檢查是否為過期警報（超過7天未處理）
     */
    public function getIsOverdueAttribute(): bool
    {
        return !$this->is_acknowledged && $this->created_at->isBefore(now()->subWeek());
    }

    // ==================== 操作方法 ====================

    /**
     * 確認警報
     *
     * @param User $user
     * @param string|null $note
     * @return void
     */
    public function acknowledge(User $user, ?string $note = null): void
    {
        $this->update([
            'acknowledged_at' => now(),
            'acknowledged_by' => $user->id,
        ]);

        // 如果有備註，可以記錄到活動日誌中
        if ($note) {
            activity()
                ->performedOn($this)
                ->causedBy($user)
                ->withProperties(['note' => $note])
                ->log('security_alert_acknowledged');
        }

        // 觸發警報確認事件
        event('security-alert-acknowledged', [
            'alert' => $this,
            'user' => $user,
            'note' => $note
        ]);
    }

    /**
     * 升級警報嚴重性
     *
     * @param string $newSeverity
     * @param User $user
     * @param string|null $reason
     * @return void
     */
    public function escalate(string $newSeverity, User $user, ?string $reason = null): void
    {
        $oldSeverity = $this->severity;
        
        $this->update([
            'severity' => $newSeverity
        ]);

        // 記錄升級操作
        activity()
            ->performedOn($this)
            ->causedBy($user)
            ->withProperties([
                'old_severity' => $oldSeverity,
                'new_severity' => $newSeverity,
                'reason' => $reason
            ])
            ->log('security_alert_escalated');

        // 觸發警報升級事件
        event('security-alert-escalated', [
            'alert' => $this,
            'user' => $user,
            'old_severity' => $oldSeverity,
            'new_severity' => $newSeverity,
            'reason' => $reason
        ]);
    }

    /**
     * 關閉警報（標記為已解決）
     *
     * @param User $user
     * @param string|null $resolution
     * @return void
     */
    public function resolve(User $user, ?string $resolution = null): void
    {
        if (!$this->is_acknowledged) {
            $this->acknowledge($user);
        }

        // 記錄解決操作
        activity()
            ->performedOn($this)
            ->causedBy($user)
            ->withProperties(['resolution' => $resolution])
            ->log('security_alert_resolved');

        // 觸發警報解決事件
        event('security-alert-resolved', [
            'alert' => $this,
            'user' => $user,
            'resolution' => $resolution
        ]);
    }

    // ==================== 查詢範圍 ====================

    /**
     * 未確認的警報
     */
    public function scopeUnacknowledged($query)
    {
        return $query->whereNull('acknowledged_at');
    }

    /**
     * 已確認的警報
     */
    public function scopeAcknowledged($query)
    {
        return $query->whereNotNull('acknowledged_at');
    }

    /**
     * 按嚴重性篩選
     */
    public function scopeBySeverity($query, string $severity)
    {
        return $query->where('severity', $severity);
    }

    /**
     * 高嚴重性警報（高和嚴重）
     */
    public function scopeHighSeverity($query)
    {
        return $query->whereIn('severity', ['high', 'critical']);
    }

    /**
     * 最近的警報
     */
    public function scopeRecent($query, int $hours = 24)
    {
        return $query->where('created_at', '>=', now()->subHours($hours));
    }

    /**
     * 過期的警報
     */
    public function scopeOverdue($query, int $days = 7)
    {
        return $query->whereNull('acknowledged_at')
            ->where('created_at', '<=', now()->subDays($days));
    }

    /**
     * 按類型篩選
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * 按時間範圍篩選
     */
    public function scopeInTimeRange($query, Carbon $from, Carbon $to)
    {
        return $query->whereBetween('created_at', [$from, $to]);
    }

    // ==================== 靜態方法 ====================

    /**
     * 獲取警報統計
     */
    public static function getStatistics(int $days = 30): array
    {
        $from = now()->subDays($days);
        
        return [
            'total' => static::where('created_at', '>=', $from)->count(),
            'unacknowledged' => static::unacknowledged()->where('created_at', '>=', $from)->count(),
            'high_severity' => static::highSeverity()->where('created_at', '>=', $from)->count(),
            'by_severity' => static::where('created_at', '>=', $from)
                ->selectRaw('severity, COUNT(*) as count')
                ->groupBy('severity')
                ->pluck('count', 'severity')
                ->toArray(),
            'by_type' => static::where('created_at', '>=', $from)
                ->selectRaw('type, COUNT(*) as count')
                ->groupBy('type')
                ->orderByDesc('count')
                ->pluck('count', 'type')
                ->toArray(),
            'daily_trend' => static::where('created_at', '>=', $from)
                ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
                ->groupBy('date')
                ->orderBy('date')
                ->pluck('count', 'date')
                ->toArray()
        ];
    }

    /**
     * 清理舊警報
     */
    public static function cleanup(int $daysToKeep = 90): int
    {
        $cutoffDate = now()->subDays($daysToKeep);
        
        return static::where('created_at', '<', $cutoffDate)
            ->where('acknowledged_at', '<', $cutoffDate)
            ->delete();
    }

    /**
     * 獲取需要關注的警報
     */
    public static function getAlertsNeedingAttention(): Collection
    {
        return static::query()
            ->with(['activity', 'acknowledgedBy'])
            ->where(function ($query) {
                $query->whereIn('severity', ['high', 'critical'])
                    ->orWhere(function ($subQuery) {
                        $subQuery->whereNull('acknowledged_at')
                            ->where('created_at', '<=', now()->subHours(4));
                    });
            })
            ->orderByDesc('created_at')
            ->get();
    }
}