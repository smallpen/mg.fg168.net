<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Carbon\Carbon;

/**
 * 活動記錄保留政策模型
 * 
 * 定義活動記錄的保留規則和清理策略
 */
class ActivityRetentionPolicy extends Model
{
    use HasFactory;

    /**
     * 可批量賦值的屬性
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'activity_type',
        'module',
        'retention_days',
        'action',
        'is_active',
        'priority',
        'conditions',
        'description',
        'created_by',
        'last_executed_at',
    ];

    /**
     * 屬性轉換
     *
     * @var array<string, string>
     */
    protected $casts = [
        'conditions' => 'array',
        'is_active' => 'boolean',
        'priority' => 'integer',
        'retention_days' => 'integer',
        'last_executed_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * 動作類型常數
     */
    public const ACTION_DELETE = 'delete';
    public const ACTION_ARCHIVE = 'archive';

    /**
     * 預設保留政策
     */
    public const DEFAULT_POLICIES = [
        [
            'name' => '一般活動記錄',
            'activity_type' => null,
            'module' => null,
            'retention_days' => 90,
            'action' => self::ACTION_ARCHIVE,
            'priority' => 1,
            'description' => '一般活動記錄保留 90 天後歸檔',
        ],
        [
            'name' => '安全事件記錄',
            'activity_type' => null,
            'module' => null,
            'retention_days' => 365,
            'action' => self::ACTION_ARCHIVE,
            'priority' => 10,
            'conditions' => ['risk_level' => ['>=', 5]],
            'description' => '安全事件記錄保留 365 天後歸檔',
        ],
        [
            'name' => '系統管理操作',
            'activity_type' => null,
            'module' => 'system',
            'retention_days' => 1095,
            'action' => self::ACTION_ARCHIVE,
            'priority' => 5,
            'description' => '系統管理操作保留 3 年後歸檔',
        ],
    ];

    /**
     * 建立者關聯
     *
     * @return BelongsTo
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * 清理日誌關聯
     *
     * @return HasMany
     */
    public function cleanupLogs(): HasMany
    {
        return $this->hasMany(ActivityCleanupLog::class, 'policy_id');
    }

    /**
     * 範圍查詢：啟用的政策
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * 範圍查詢：按優先級排序
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByPriority($query)
    {
        return $query->orderBy('priority', 'desc');
    }

    /**
     * 範圍查詢：適用於特定活動類型
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $activityType
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForActivityType($query, string $activityType)
    {
        return $query->where(function ($q) use ($activityType) {
            $q->whereNull('activity_type')
              ->orWhere('activity_type', $activityType);
        });
    }

    /**
     * 範圍查詢：適用於特定模組
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $module
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForModule($query, string $module)
    {
        return $query->where(function ($q) use ($module) {
            $q->whereNull('module')
              ->orWhere('module', $module);
        });
    }

    /**
     * 檢查政策是否適用於特定活動
     *
     * @param Activity $activity
     * @return bool
     */
    public function appliesTo(Activity $activity): bool
    {
        // 檢查活動類型
        if ($this->activity_type && $this->activity_type !== $activity->type) {
            return false;
        }

        // 檢查模組
        if ($this->module && $this->module !== $activity->module) {
            return false;
        }

        // 檢查額外條件
        if ($this->conditions) {
            return $this->checkConditions($activity);
        }

        return true;
    }

    /**
     * 檢查額外條件
     *
     * @param Activity $activity
     * @return bool
     */
    protected function checkConditions(Activity $activity): bool
    {
        foreach ($this->conditions as $field => $condition) {
            $value = $activity->getAttribute($field);
            
            if (is_array($condition) && count($condition) === 2) {
                [$operator, $compareValue] = $condition;
                
                switch ($operator) {
                    case '>=':
                        if ($value < $compareValue) return false;
                        break;
                    case '<=':
                        if ($value > $compareValue) return false;
                        break;
                    case '>':
                        if ($value <= $compareValue) return false;
                        break;
                    case '<':
                        if ($value >= $compareValue) return false;
                        break;
                    case '=':
                    case '==':
                        if ($value != $compareValue) return false;
                        break;
                    case '!=':
                        if ($value == $compareValue) return false;
                        break;
                    case 'in':
                        if (!in_array($value, (array)$compareValue)) return false;
                        break;
                    case 'not_in':
                        if (in_array($value, (array)$compareValue)) return false;
                        break;
                }
            } else {
                // 簡單相等比較
                if ($value != $condition) return false;
            }
        }

        return true;
    }

    /**
     * 取得到期日期
     *
     * @return Carbon
     */
    public function getExpiryDate(): Carbon
    {
        return Carbon::now()->subDays($this->retention_days);
    }

    /**
     * 取得適用的活動記錄查詢
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function getApplicableActivitiesQuery()
    {
        $query = Activity::where('created_at', '<', $this->getExpiryDate());

        // 應用活動類型篩選
        if ($this->activity_type) {
            $query->where('type', $this->activity_type);
        }

        // 應用模組篩選
        if ($this->module) {
            $query->where('module', $this->module);
        }

        // 應用額外條件
        if ($this->conditions) {
            $query->where(function ($q) {
                foreach ($this->conditions as $field => $condition) {
                    if (is_array($condition) && count($condition) === 2) {
                        [$operator, $value] = $condition;
                        $q->where($field, $operator, $value);
                    } else {
                        $q->where($field, $condition);
                    }
                }
            });
        }

        return $query;
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
     * 取得適用範圍描述
     *
     * @return string
     */
    public function getScopeDescriptionAttribute(): string
    {
        $parts = [];

        if ($this->activity_type) {
            $parts[] = "類型: {$this->activity_type}";
        }

        if ($this->module) {
            $parts[] = "模組: {$this->module}";
        }

        if ($this->conditions) {
            foreach ($this->conditions as $field => $condition) {
                if (is_array($condition)) {
                    $parts[] = "{$field} {$condition[0]} {$condition[1]}";
                } else {
                    $parts[] = "{$field} = {$condition}";
                }
            }
        }

        return empty($parts) ? '所有活動' : implode(', ', $parts);
    }

    /**
     * 取得下次執行時間預估
     *
     * @return Carbon|null
     */
    public function getNextExecutionEstimate(): ?Carbon
    {
        if (!$this->is_active) {
            return null;
        }

        // 如果從未執行過，返回現在
        if (!$this->last_executed_at) {
            return Carbon::now();
        }

        // 建議每日執行一次
        return $this->last_executed_at->addDay();
    }

    /**
     * 更新最後執行時間
     *
     * @return void
     */
    public function markAsExecuted(): void
    {
        $this->update(['last_executed_at' => Carbon::now()]);
    }

    /**
     * 取得統計資訊
     *
     * @return array
     */
    public function getStats(): array
    {
        $applicableQuery = $this->getApplicableActivitiesQuery();
        
        return [
            'applicable_records' => $applicableQuery->count(),
            'total_size_mb' => $this->estimateDataSize($applicableQuery),
            'oldest_record' => $applicableQuery->min('created_at'),
            'newest_record' => $applicableQuery->max('created_at'),
            'last_executed' => $this->last_executed_at?->format('Y-m-d H:i:s'),
            'next_execution' => $this->getNextExecutionEstimate()?->format('Y-m-d H:i:s'),
        ];
    }

    /**
     * 估算資料大小
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return float
     */
    protected function estimateDataSize($query): float
    {
        // 簡單估算：每筆記錄約 2KB
        $recordCount = $query->count();
        return round(($recordCount * 2) / 1024, 2); // 轉換為 MB
    }

    /**
     * 建立預設保留政策
     *
     * @return void
     */
    public static function createDefaultPolicies(): void
    {
        foreach (self::DEFAULT_POLICIES as $policyData) {
            self::firstOrCreate(
                ['name' => $policyData['name']],
                $policyData
            );
        }
    }

    /**
     * 取得最適用的政策
     *
     * @param Activity $activity
     * @return static|null
     */
    public static function getMostApplicablePolicy(Activity $activity): ?static
    {
        return static::active()
            ->byPriority()
            ->get()
            ->first(function ($policy) use ($activity) {
                return $policy->appliesTo($activity);
            });
    }

    /**
     * 取得所有適用的政策
     *
     * @param Activity $activity
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function getApplicablePolicies(Activity $activity)
    {
        return static::active()
            ->byPriority()
            ->get()
            ->filter(function ($policy) use ($activity) {
                return $policy->appliesTo($activity);
            });
    }
}