<?php

namespace App\Models;

use App\Models\Activity;
use App\Models\MonitorRule;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SecurityAlert extends Model
{
    use HasFactory;

    protected $fillable = [
        'activity_id',
        'rule_id',
        'type',
        'severity',
        'title',
        'description',
        'acknowledged_at',
        'acknowledged_by',
        'escalated_at',
        'escalated_by',
        'resolved_at',
        'resolved_by',
        'metadata'
    ];

    protected $casts = [
        'acknowledged_at' => 'datetime',
        'escalated_at' => 'datetime',
        'resolved_at' => 'datetime',
        'metadata' => 'json'
    ];

    /**
     * 關聯：相關的活動記錄
     */
    public function activity(): BelongsTo
    {
        return $this->belongsTo(Activity::class);
    }

    /**
     * 關聯：觸發的監控規則
     */
    public function rule(): BelongsTo
    {
        return $this->belongsTo(MonitorRule::class);
    }

    /**
     * 關聯：確認警報的使用者
     */
    public function acknowledgedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'acknowledged_by');
    }

    /**
     * 關聯：升級警報的使用者
     */
    public function escalatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'escalated_by');
    }

    /**
     * 關聯：解決警報的使用者
     */
    public function resolvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    /**
     * 計算屬性：是否已確認
     */
    public function getIsAcknowledgedAttribute(): bool
    {
        return !is_null($this->acknowledged_at);
    }

    /**
     * 計算屬性：是否已升級
     */
    public function getIsEscalatedAttribute(): bool
    {
        return !is_null($this->escalated_at);
    }

    /**
     * 計算屬性：是否已解決
     */
    public function getIsResolvedAttribute(): bool
    {
        return !is_null($this->resolved_at);
    }

    /**
     * 計算屬性：嚴重程度顏色
     */
    public function getSeverityColorAttribute(): string
    {
        return match ($this->severity) {
            'low' => 'green',
            'medium' => 'yellow',
            'high' => 'red',
            'critical' => 'purple',
            default => 'gray',
        };
    }

    /**
     * 計算屬性：嚴重程度文字
     */
    public function getSeverityTextAttribute(): string
    {
        return match ($this->severity) {
            'low' => '低',
            'medium' => '中',
            'high' => '高',
            'critical' => '嚴重',
            default => '未知',
        };
    }

    /**
     * 計算屬性：狀態文字
     */
    public function getStatusTextAttribute(): string
    {
        if ($this->is_resolved) {
            return '已解決';
        }
        
        if ($this->is_escalated) {
            return '已升級';
        }
        
        if ($this->is_acknowledged) {
            return '已確認';
        }
        
        return '待處理';
    }

    /**
     * 計算屬性：狀態顏色
     */
    public function getStatusColorAttribute(): string
    {
        if ($this->is_resolved) {
            return 'green';
        }
        
        if ($this->is_escalated) {
            return 'purple';
        }
        
        if ($this->is_acknowledged) {
            return 'blue';
        }
        
        return 'red';
    }

    /**
     * 確認警報
     */
    public function acknowledge(User $user): void
    {
        $this->update([
            'acknowledged_at' => now(),
            'acknowledged_by' => $user->id,
        ]);

        // 記錄確認動作
        activity()
            ->causedBy($user)
            ->performedOn($this)
            ->withProperties([
                'alert_id' => $this->id,
                'alert_title' => $this->title,
                'alert_severity' => $this->severity,
            ])
            ->log("確認安全警報：{$this->title}");
    }

    /**
     * 升級警報
     */
    public function escalate(User $user, string $reason = ''): void
    {
        $this->update([
            'escalated_at' => now(),
            'escalated_by' => $user->id,
            'metadata' => array_merge($this->metadata ?? [], [
                'escalation_reason' => $reason,
                'escalated_from_severity' => $this->severity,
            ]),
            'severity' => $this->getNextSeverityLevel(),
        ]);

        // 記錄升級動作
        activity()
            ->causedBy($user)
            ->performedOn($this)
            ->withProperties([
                'alert_id' => $this->id,
                'alert_title' => $this->title,
                'old_severity' => $this->metadata['escalated_from_severity'] ?? $this->severity,
                'new_severity' => $this->severity,
                'reason' => $reason,
            ])
            ->log("升級安全警報：{$this->title}");
    }

    /**
     * 解決警報
     */
    public function resolve(User $user, string $resolution = ''): void
    {
        $this->update([
            'resolved_at' => now(),
            'resolved_by' => $user->id,
            'metadata' => array_merge($this->metadata ?? [], [
                'resolution' => $resolution,
            ]),
        ]);

        // 記錄解決動作
        activity()
            ->causedBy($user)
            ->performedOn($this)
            ->withProperties([
                'alert_id' => $this->id,
                'alert_title' => $this->title,
                'resolution' => $resolution,
            ])
            ->log("解決安全警報：{$this->title}");
    }

    /**
     * 取得下一個嚴重程度等級
     */
    protected function getNextSeverityLevel(): string
    {
        return match ($this->severity) {
            'low' => 'medium',
            'medium' => 'high',
            'high' => 'critical',
            'critical' => 'critical', // 已經是最高等級
            default => 'medium',
        };
    }

    /**
     * 範圍查詢：未確認的警報
     */
    public function scopeUnacknowledged($query)
    {
        return $query->whereNull('acknowledged_at');
    }

    /**
     * 範圍查詢：已確認的警報
     */
    public function scopeAcknowledged($query)
    {
        return $query->whereNotNull('acknowledged_at');
    }

    /**
     * 範圍查詢：未解決的警報
     */
    public function scopeUnresolved($query)
    {
        return $query->whereNull('resolved_at');
    }

    /**
     * 範圍查詢：已解決的警報
     */
    public function scopeResolved($query)
    {
        return $query->whereNotNull('resolved_at');
    }

    /**
     * 範圍查詢：依嚴重程度篩選
     */
    public function scopeBySeverity($query, string $severity)
    {
        return $query->where('severity', $severity);
    }

    /**
     * 範圍查詢：高嚴重程度警報
     */
    public function scopeHighSeverity($query)
    {
        return $query->whereIn('severity', ['high', 'critical']);
    }

    /**
     * 範圍查詢：最近的警報
     */
    public function scopeRecent($query, int $hours = 24)
    {
        return $query->where('created_at', '>=', now()->subHours($hours));
    }

    /**
     * 範圍查詢：依類型篩選
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * 取得警報統計資料
     */
    public static function getStatistics(int $days = 7): array
    {
        $startDate = now()->subDays($days);

        return [
            'total' => static::where('created_at', '>=', $startDate)->count(),
            'unacknowledged' => static::unacknowledged()
                ->where('created_at', '>=', $startDate)
                ->count(),
            'high_severity' => static::highSeverity()
                ->where('created_at', '>=', $startDate)
                ->count(),
            'resolved' => static::resolved()
                ->where('created_at', '>=', $startDate)
                ->count(),
            'by_severity' => static::where('created_at', '>=', $startDate)
                ->selectRaw('severity, COUNT(*) as count')
                ->groupBy('severity')
                ->pluck('count', 'severity')
                ->toArray(),
            'by_type' => static::where('created_at', '>=', $startDate)
                ->selectRaw('type, COUNT(*) as count')
                ->groupBy('type')
                ->pluck('count', 'type')
                ->toArray(),
        ];
    }

    /**
     * 取得警報趨勢資料
     */
    public static function getTrendData(int $days = 7): array
    {
        $data = [];
        $startDate = now()->subDays($days);

        for ($i = 0; $i < $days; $i++) {
            $date = $startDate->copy()->addDays($i);
            $dayStart = $date->startOfDay();
            $dayEnd = $date->endOfDay();

            $data[] = [
                'date' => $date->format('Y-m-d'),
                'total' => static::whereBetween('created_at', [$dayStart, $dayEnd])->count(),
                'high_severity' => static::highSeverity()
                    ->whereBetween('created_at', [$dayStart, $dayEnd])
                    ->count(),
                'resolved' => static::resolved()
                    ->whereBetween('created_at', [$dayStart, $dayEnd])
                    ->count(),
            ];
        }

        return $data;
    }
}