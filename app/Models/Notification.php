<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;

class Notification extends Model
{
    use HasFactory;

    /**
     * 可批量賦值的屬性
     */
    protected $fillable = [
        'user_id',
        'type',
        'title',
        'message',
        'data',
        'priority',
        'read_at',
        'is_browser_notification',
        'icon',
        'color',
        'action_url',
    ];

    /**
     * 屬性轉換
     */
    protected $casts = [
        'data' => 'array',
        'read_at' => 'datetime',
        'is_browser_notification' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * 通知類型常數
     */
    const TYPE_SECURITY = 'security';
    const TYPE_SYSTEM = 'system';
    const TYPE_USER_ACTION = 'user_action';
    const TYPE_REPORT = 'report';

    /**
     * 優先級常數
     */
    const PRIORITY_LOW = 'low';
    const PRIORITY_NORMAL = 'normal';
    const PRIORITY_HIGH = 'high';
    const PRIORITY_URGENT = 'urgent';

    /**
     * 通知類型配置
     */
    public static function getTypeConfig(): array
    {
        return [
            self::TYPE_SECURITY => [
                'name' => '安全事件',
                'icon' => 'shield-exclamation',
                'color' => 'red',
            ],
            self::TYPE_SYSTEM => [
                'name' => '系統通知',
                'icon' => 'cog',
                'color' => 'blue',
            ],
            self::TYPE_USER_ACTION => [
                'name' => '使用者操作',
                'icon' => 'user',
                'color' => 'yellow',
            ],
            self::TYPE_REPORT => [
                'name' => '統計報告',
                'icon' => 'chart-bar',
                'color' => 'green',
            ],
        ];
    }

    /**
     * 關聯到使用者
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * 查詢範圍：未讀通知
     */
    public function scopeUnread(Builder $query): Builder
    {
        return $query->whereNull('read_at');
    }

    /**
     * 查詢範圍：已讀通知
     */
    public function scopeRead(Builder $query): Builder
    {
        return $query->whereNotNull('read_at');
    }

    /**
     * 查詢範圍：按類型篩選
     */
    public function scopeOfType(Builder $query, string $type): Builder
    {
        return $query->where('type', $type);
    }

    /**
     * 查詢範圍：按優先級篩選
     */
    public function scopeOfPriority(Builder $query, string $priority): Builder
    {
        return $query->where('priority', $priority);
    }

    /**
     * 查詢範圍：高優先級通知
     */
    public function scopeHighPriority(Builder $query): Builder
    {
        return $query->whereIn('priority', [self::PRIORITY_HIGH, self::PRIORITY_URGENT]);
    }

    /**
     * 查詢範圍：最近的通知
     */
    public function scopeRecent(Builder $query, int $days = 7): Builder
    {
        return $query->where('created_at', '>=', Carbon::now()->subDays($days));
    }

    /**
     * 檢查是否已讀
     */
    public function isRead(): bool
    {
        return !is_null($this->read_at);
    }

    /**
     * 檢查是否未讀
     */
    public function isUnread(): bool
    {
        return is_null($this->read_at);
    }

    /**
     * 標記為已讀
     */
    public function markAsRead(): bool
    {
        if ($this->isUnread()) {
            return $this->update(['read_at' => Carbon::now()]);
        }
        
        return true;
    }

    /**
     * 標記為未讀
     */
    public function markAsUnread(): bool
    {
        return $this->update(['read_at' => null]);
    }

    /**
     * 獲取通知類型配置
     */
    public function getTypeConfigAttribute(): array
    {
        $config = self::getTypeConfig();
        return $config[$this->type] ?? [
            'name' => '未知類型',
            'icon' => 'bell',
            'color' => 'gray',
        ];
    }

    /**
     * 獲取相對時間
     */
    public function getRelativeTimeAttribute(): string
    {
        return $this->created_at->diffForHumans();
    }

    /**
     * 獲取優先級標籤
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
     * 獲取優先級顏色
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
}
