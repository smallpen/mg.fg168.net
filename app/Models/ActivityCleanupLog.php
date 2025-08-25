<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

/**
 * 活動記錄清理日誌模型
 * 
 * 記錄活動記錄清理和歸檔操作的詳細資訊
 */
class ActivityCleanupLog extends Model
{
    use HasFactory;

    /**
     * 可批量賦值的屬性
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'policy_id',
        'type',
        'action',
        'activity_type',
        'module',
        'date_from',
        'date_to',
        'records_processed',
        'records_deleted',
        'records_archived',
        'archive_path',
        'status',
        'error_message',
        'execution_time_seconds',
        'summary',
        'executed_by',
        'started_at',
        'completed_at',
    ];

    /**
     * 屬性轉換
     *
     * @var array<string, string>
     */
    protected $casts = [
        'date_from' => 'date',
        'date_to' => 'date',
        'records_processed' => 'integer',
        'records_deleted' => 'integer',
        'records_archived' => 'integer',
        'execution_time_seconds' => 'integer',
        'summary' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * 清理類型常數
     */
    public const TYPE_MANUAL = 'manual';
    public const TYPE_AUTOMATIC = 'automatic';

    /**
     * 動作類型常數
     */
    public const ACTION_DELETE = 'delete';
    public const ACTION_ARCHIVE = 'archive';

    /**
     * 狀態常數
     */
    public const STATUS_RUNNING = 'running';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_FAILED = 'failed';

    /**
     * 關聯的保留政策
     *
     * @return BelongsTo
     */
    public function policy(): BelongsTo
    {
        return $this->belongsTo(ActivityRetentionPolicy::class, 'policy_id');
    }

    /**
     * 執行者關聯
     *
     * @return BelongsTo
     */
    public function executor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'executed_by');
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
     * 範圍查詢：按狀態篩選
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $status
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * 範圍查詢：按動作篩選
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $action
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByAction($query, string $action)
    {
        return $query->where('action', $action);
    }

    /**
     * 範圍查詢：最近的記錄
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $days
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeRecent($query, int $days = 30)
    {
        return $query->where('started_at', '>=', Carbon::now()->subDays($days));
    }

    /**
     * 範圍查詢：已完成的記錄
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeCompleted($query)
    {
        return $query->byStatus(self::STATUS_COMPLETED);
    }

    /**
     * 範圍查詢：失敗的記錄
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeFailed($query)
    {
        return $query->byStatus(self::STATUS_FAILED);
    }

    /**
     * 範圍查詢：正在執行的記錄
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeRunning($query)
    {
        return $query->byStatus(self::STATUS_RUNNING);
    }

    /**
     * 取得類型文字
     *
     * @return string
     */
    public function getTypeTextAttribute(): string
    {
        return match ($this->type) {
            self::TYPE_MANUAL => '手動',
            self::TYPE_AUTOMATIC => '自動',
            default => '未知',
        };
    }

    /**
     * 取得動作文字
     *
     * @return string
     */
    public function getActionTextAttribute(): string
    {
        return match ($this->action) {
            self::ACTION_DELETE => '刪除',
            self::ACTION_ARCHIVE => '歸檔',
            default => '未知',
        };
    }

    /**
     * 取得狀態文字
     *
     * @return string
     */
    public function getStatusTextAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_RUNNING => '執行中',
            self::STATUS_COMPLETED => '已完成',
            self::STATUS_FAILED => '失敗',
            default => '未知',
        };
    }

    /**
     * 取得狀態顏色
     *
     * @return string
     */
    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_RUNNING => 'warning',
            self::STATUS_COMPLETED => 'success',
            self::STATUS_FAILED => 'danger',
            default => 'secondary',
        };
    }

    /**
     * 取得執行時間文字
     *
     * @return string
     */
    public function getExecutionTimeTextAttribute(): string
    {
        if (!$this->execution_time_seconds) {
            return '未知';
        }

        $seconds = $this->execution_time_seconds;
        
        if ($seconds < 60) {
            return "{$seconds} 秒";
        }
        
        $minutes = floor($seconds / 60);
        $remainingSeconds = $seconds % 60;
        
        if ($minutes < 60) {
            return "{$minutes} 分 {$remainingSeconds} 秒";
        }
        
        $hours = floor($minutes / 60);
        $remainingMinutes = $minutes % 60;
        
        return "{$hours} 小時 {$remainingMinutes} 分 {$remainingSeconds} 秒";
    }

    /**
     * 取得處理效率（每秒處理記錄數）
     *
     * @return float
     */
    public function getProcessingRateAttribute(): float
    {
        if (!$this->execution_time_seconds || $this->execution_time_seconds === 0) {
            return 0;
        }

        return round($this->records_processed / $this->execution_time_seconds, 2);
    }

    /**
     * 取得成功率
     *
     * @return float
     */
    public function getSuccessRateAttribute(): float
    {
        if ($this->records_processed === 0) {
            return 0;
        }

        $successful = $this->records_deleted + $this->records_archived;
        return round(($successful / $this->records_processed) * 100, 2);
    }

    /**
     * 是否已完成
     *
     * @return bool
     */
    public function getIsCompletedAttribute(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    /**
     * 是否失敗
     *
     * @return bool
     */
    public function getIsFailedAttribute(): bool
    {
        return $this->status === self::STATUS_FAILED;
    }

    /**
     * 是否正在執行
     *
     * @return bool
     */
    public function getIsRunningAttribute(): bool
    {
        return $this->status === self::STATUS_RUNNING;
    }

    /**
     * 標記為完成
     *
     * @param array $summary
     * @return void
     */
    public function markAsCompleted(array $summary = []): void
    {
        $this->update([
            'status' => self::STATUS_COMPLETED,
            'completed_at' => Carbon::now(),
            'execution_time_seconds' => $this->started_at->diffInSeconds(Carbon::now()),
            'summary' => array_merge($this->summary ?? [], $summary),
        ]);
    }

    /**
     * 標記為失敗
     *
     * @param string $errorMessage
     * @return void
     */
    public function markAsFailed(string $errorMessage): void
    {
        $this->update([
            'status' => self::STATUS_FAILED,
            'completed_at' => Carbon::now(),
            'execution_time_seconds' => $this->started_at->diffInSeconds(Carbon::now()),
            'error_message' => $errorMessage,
        ]);
    }

    /**
     * 更新進度
     *
     * @param int $processed
     * @param int $deleted
     * @param int $archived
     * @return void
     */
    public function updateProgress(int $processed, int $deleted = 0, int $archived = 0): void
    {
        $this->update([
            'records_processed' => $processed,
            'records_deleted' => $deleted,
            'records_archived' => $archived,
        ]);
    }

    /**
     * 建立新的清理日誌
     *
     * @param array $data
     * @return static
     */
    public static function createLog(array $data): static
    {
        return static::create(array_merge([
            'started_at' => Carbon::now(),
            'status' => self::STATUS_RUNNING,
            'records_processed' => 0,
            'records_deleted' => 0,
            'records_archived' => 0,
        ], $data));
    }

    /**
     * 取得清理統計
     *
     * @param string $timeRange
     * @return array
     */
    public static function getCleanupStats(string $timeRange = '30d'): array
    {
        $days = match ($timeRange) {
            '7d' => 7,
            '30d' => 30,
            '90d' => 90,
            default => 30,
        };

        $startDate = Carbon::now()->subDays($days);
        
        $logs = static::where('started_at', '>=', $startDate)->get();

        return [
            'total_operations' => $logs->count(),
            'successful_operations' => $logs->where('status', self::STATUS_COMPLETED)->count(),
            'failed_operations' => $logs->where('status', self::STATUS_FAILED)->count(),
            'total_records_processed' => $logs->sum('records_processed'),
            'total_records_deleted' => $logs->sum('records_deleted'),
            'total_records_archived' => $logs->sum('records_archived'),
            'average_execution_time' => $logs->where('status', self::STATUS_COMPLETED)->avg('execution_time_seconds'),
            'total_execution_time' => $logs->where('status', self::STATUS_COMPLETED)->sum('execution_time_seconds'),
            'operations_by_type' => [
                'manual' => $logs->where('type', self::TYPE_MANUAL)->count(),
                'automatic' => $logs->where('type', self::TYPE_AUTOMATIC)->count(),
            ],
            'operations_by_action' => [
                'delete' => $logs->where('action', self::ACTION_DELETE)->count(),
                'archive' => $logs->where('action', self::ACTION_ARCHIVE)->count(),
            ],
        ];
    }
}