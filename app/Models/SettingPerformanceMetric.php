<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * 設定效能指標模型
 * 
 * @property int $id
 * @property string $metric_type 指標類型
 * @property string $operation 操作名稱
 * @property float $value 指標值
 * @property string $unit 單位
 * @property array|null $metadata 額外資料
 * @property \Illuminate\Support\Carbon $recorded_at 記錄時間
 */
class SettingPerformanceMetric extends Model
{
    use HasFactory;

    /**
     * 資料表名稱
     *
     * @var string
     */
    protected $table = 'setting_performance_metrics';

    /**
     * 可批量賦值的屬性
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'metric_type',
        'operation',
        'value',
        'unit',
        'metadata',
        'recorded_at',
    ];

    /**
     * 屬性轉換
     *
     * @var array<string, string>
     */
    protected $casts = [
        'value' => 'decimal:4',
        'metadata' => 'json',
        'recorded_at' => 'datetime',
    ];

    /**
     * 時間戳欄位
     *
     * @var array
     */
    protected $dates = [
        'recorded_at',
        'created_at',
        'updated_at',
    ];

    /**
     * 記錄效能指標
     *
     * @param string $type 指標類型
     * @param string $operation 操作名稱
     * @param float $value 指標值
     * @param string $unit 單位
     * @param array $metadata 額外資料
     * @return static
     */
    public static function record(string $type, string $operation, float $value, string $unit = 'ms', array $metadata = []): static
    {
        return static::create([
            'metric_type' => $type,
            'operation' => $operation,
            'value' => $value,
            'unit' => $unit,
            'metadata' => $metadata,
            'recorded_at' => now(),
        ]);
    }

    /**
     * 範圍查詢：按指標類型
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $type
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('metric_type', $type);
    }

    /**
     * 範圍查詢：按操作名稱
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $operation
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByOperation($query, string $operation)
    {
        return $query->where('operation', $operation);
    }

    /**
     * 範圍查詢：按時間範圍
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param \Carbon\Carbon $start
     * @param \Carbon\Carbon $end
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeInTimeRange($query, \Carbon\Carbon $start, \Carbon\Carbon $end)
    {
        return $query->whereBetween('recorded_at', [$start, $end]);
    }

    /**
     * 範圍查詢：最近的記錄
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $hours 小時數
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeRecent($query, int $hours = 24)
    {
        return $query->where('recorded_at', '>=', now()->subHours($hours));
    }

    /**
     * 取得平均值
     *
     * @param string $type 指標類型
     * @param string|null $operation 操作名稱
     * @param int $hours 時間範圍（小時）
     * @return float
     */
    public static function getAverage(string $type, ?string $operation = null, int $hours = 24): float
    {
        $query = static::byType($type)->recent($hours);
        
        if ($operation) {
            $query->byOperation($operation);
        }
        
        return $query->avg('value') ?? 0.0;
    }

    /**
     * 取得最大值
     *
     * @param string $type 指標類型
     * @param string|null $operation 操作名稱
     * @param int $hours 時間範圍（小時）
     * @return float
     */
    public static function getMax(string $type, ?string $operation = null, int $hours = 24): float
    {
        $query = static::byType($type)->recent($hours);
        
        if ($operation) {
            $query->byOperation($operation);
        }
        
        return $query->max('value') ?? 0.0;
    }

    /**
     * 取得最小值
     *
     * @param string $type 指標類型
     * @param string|null $operation 操作名稱
     * @param int $hours 時間範圍（小時）
     * @return float
     */
    public static function getMin(string $type, ?string $operation = null, int $hours = 24): float
    {
        $query = static::byType($type)->recent($hours);
        
        if ($operation) {
            $query->byOperation($operation);
        }
        
        return $query->min('value') ?? 0.0;
    }

    /**
     * 清理舊的指標資料
     *
     * @param int $days 保留天數
     * @return int 刪除的記錄數
     */
    public static function cleanup(int $days = 30): int
    {
        return static::where('recorded_at', '<', now()->subDays($days))->delete();
    }
}