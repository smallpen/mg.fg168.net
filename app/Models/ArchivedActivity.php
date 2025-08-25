<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

/**
 * 歸檔活動記錄模型
 * 
 * 儲存已歸檔的活動記錄資料
 */
class ArchivedActivity extends Model
{
    use HasFactory;

    /**
     * 可批量賦值的屬性
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'original_id',
        'type',
        'event',
        'description',
        'module',
        'user_id',
        'user_name',
        'subject_id',
        'subject_type',
        'properties',
        'ip_address',
        'user_agent',
        'result',
        'risk_level',
        'signature',
        'original_created_at',
        'archived_at',
        'archived_by',
        'archive_reason',
    ];

    /**
     * 屬性轉換
     *
     * @var array<string, string>
     */
    protected $casts = [
        'properties' => 'array',
        'risk_level' => 'integer',
        'original_created_at' => 'datetime',
        'archived_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * 歸檔執行者關聯
     *
     * @return BelongsTo
     */
    public function archivedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'archived_by');
    }

    /**
     * 範圍查詢：按原始ID篩選
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $originalId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByOriginalId($query, int $originalId)
    {
        return $query->where('original_id', $originalId);
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
     * 範圍查詢：按原始建立日期範圍篩選
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $from
     * @param string $to
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByOriginalDateRange($query, string $from, string $to)
    {
        return $query->whereBetween('original_created_at', [
            Carbon::parse($from)->startOfDay(),
            Carbon::parse($to)->endOfDay()
        ]);
    }

    /**
     * 範圍查詢：按歸檔日期範圍篩選
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $from
     * @param string $to
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByArchivedDateRange($query, string $from, string $to)
    {
        return $query->whereBetween('archived_at', [
            Carbon::parse($from)->startOfDay(),
            Carbon::parse($to)->endOfDay()
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
     * 取得格式化的原始建立時間
     *
     * @return string
     */
    public function getFormattedOriginalTimeAttribute(): string
    {
        return $this->original_created_at->format('Y-m-d H:i:s');
    }

    /**
     * 取得格式化的歸檔時間
     *
     * @return string
     */
    public function getFormattedArchivedTimeAttribute(): string
    {
        return $this->archived_at->format('Y-m-d H:i:s');
    }

    /**
     * 取得歸檔時間差
     *
     * @return string
     */
    public function getArchiveDelayAttribute(): string
    {
        return $this->original_created_at->diffForHumans($this->archived_at);
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
     * 從活動記錄建立歸檔記錄
     *
     * @param Activity $activity
     * @param string $reason
     * @param int|null $archivedBy
     * @return static
     */
    public static function createFromActivity(Activity $activity, string $reason = '保留政策歸檔', ?int $archivedBy = null): static
    {
        return static::create([
            'original_id' => $activity->id,
            'type' => $activity->type,
            'event' => $activity->event,
            'description' => $activity->description,
            'module' => $activity->module,
            'user_id' => $activity->user_id,
            'user_name' => $activity->user?->name ?? '未知使用者',
            'subject_id' => $activity->subject_id,
            'subject_type' => $activity->subject_type,
            'properties' => $activity->properties,
            'ip_address' => $activity->ip_address,
            'user_agent' => $activity->user_agent,
            'result' => $activity->result,
            'risk_level' => $activity->risk_level,
            'signature' => $activity->signature,
            'original_created_at' => $activity->created_at,
            'archived_at' => Carbon::now(),
            'archived_by' => $archivedBy ?? auth()->id(),
            'archive_reason' => $reason,
        ]);
    }

    /**
     * 批量從活動記錄建立歸檔記錄
     *
     * @param \Illuminate\Database\Eloquent\Collection $activities
     * @param string $reason
     * @param int|null $archivedBy
     * @return int
     */
    public static function createBatchFromActivities($activities, string $reason = '保留政策歸檔', ?int $archivedBy = null): int
    {
        $archivedBy = $archivedBy ?? auth()->id() ?? 1;
        $now = Carbon::now();
        $batchData = [];

        foreach ($activities as $activity) {
            $batchData[] = [
                'original_id' => $activity->id,
                'type' => $activity->type,
                'event' => $activity->event,
                'description' => $activity->description,
                'module' => $activity->module,
                'user_id' => $activity->user_id,
                'user_name' => $activity->user?->name ?? '未知使用者',
                'subject_id' => $activity->subject_id,
                'subject_type' => $activity->subject_type,
                'properties' => json_encode($activity->properties),
                'ip_address' => $activity->ip_address,
                'user_agent' => $activity->user_agent,
                'result' => $activity->result,
                'risk_level' => $activity->risk_level,
                'signature' => $activity->signature,
                'original_created_at' => $activity->created_at,
                'archived_at' => $now,
                'archived_by' => $archivedBy,
                'archive_reason' => $reason,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        // 分批插入以避免記憶體問題
        $chunks = array_chunk($batchData, 100);
        $totalInserted = 0;

        foreach ($chunks as $chunk) {
            static::insert($chunk);
            $totalInserted += count($chunk);
        }

        return $totalInserted;
    }

    /**
     * 搜尋歸檔記錄
     *
     * @param string $query
     * @param array $filters
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public static function search(string $query, array $filters = [])
    {
        $searchQuery = static::where(function ($q) use ($query) {
            $q->where('description', 'like', "%{$query}%")
              ->orWhere('type', 'like', "%{$query}%")
              ->orWhere('user_name', 'like', "%{$query}%")
              ->orWhere('ip_address', 'like', "%{$query}%");
        });

        // 應用篩選條件
        if (!empty($filters['type'])) {
            $searchQuery->byType($filters['type']);
        }

        if (!empty($filters['module'])) {
            $searchQuery->byModule($filters['module']);
        }

        if (!empty($filters['user_id'])) {
            $searchQuery->byUser($filters['user_id']);
        }

        if (!empty($filters['date_from'])) {
            $searchQuery->where('original_created_at', '>=', Carbon::parse($filters['date_from']));
        }

        if (!empty($filters['date_to'])) {
            $searchQuery->where('original_created_at', '<=', Carbon::parse($filters['date_to'])->endOfDay());
        }

        return $searchQuery->orderBy('original_created_at', 'desc');
    }

    /**
     * 取得歸檔統計
     *
     * @param string $timeRange
     * @return array
     */
    public static function getArchiveStats(string $timeRange = '30d'): array
    {
        $days = match ($timeRange) {
            '7d' => 7,
            '30d' => 30,
            '90d' => 90,
            default => 30,
        };

        $startDate = Carbon::now()->subDays($days);
        
        $query = static::where('archived_at', '>=', $startDate);

        return [
            'total_archived' => $query->count(),
            'archived_by_type' => $query->selectRaw('type, COUNT(*) as count')
                ->groupBy('type')
                ->orderBy('count', 'desc')
                ->pluck('count', 'type')
                ->toArray(),
            'archived_by_module' => $query->selectRaw('module, COUNT(*) as count')
                ->whereNotNull('module')
                ->groupBy('module')
                ->orderBy('count', 'desc')
                ->pluck('count', 'module')
                ->toArray(),
            'high_risk_archived' => $query->highRisk()->count(),
            'archive_size_estimate' => $query->count() * 2, // 每筆約 2KB
            'oldest_archived' => $query->min('original_created_at'),
            'newest_archived' => $query->max('original_created_at'),
        ];
    }

    /**
     * 清理舊的歸檔記錄
     *
     * @param int $daysToKeep
     * @return int
     */
    public static function cleanupOldArchives(int $daysToKeep = 2555): int // 預設保留 7 年
    {
        $cutoffDate = Carbon::now()->subDays($daysToKeep);
        
        return static::where('archived_at', '<', $cutoffDate)->delete();
    }
}