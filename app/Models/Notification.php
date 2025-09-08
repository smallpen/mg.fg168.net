<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Notification extends Model
{
    use HasFactory;

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

    protected $casts = [
        'data' => 'array',
        'read_at' => 'datetime',
        'is_browser_notification' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * 通知所屬的使用者
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * 標記通知為已讀
     */
    public function markAsRead(): void
    {
        $this->update(['read_at' => now()]);
    }

    /**
     * 檢查通知是否已讀
     */
    public function isRead(): bool
    {
        return !is_null($this->read_at);
    }

    /**
     * 取得優先級顏色
     */
    public function getPriorityColorAttribute(): string
    {
        return match ($this->priority) {
            'low' => 'text-gray-500',
            'normal' => 'text-blue-500',
            'high' => 'text-orange-500',
            'urgent' => 'text-red-500',
            default => 'text-gray-500',
        };
    }

    /**
     * 取得優先級標籤
     */
    public function getPriorityLabelAttribute(): string
    {
        return match ($this->priority) {
            'low' => '低',
            'normal' => '一般',
            'high' => '高',
            'urgent' => '緊急',
            default => '一般',
        };
    }
}