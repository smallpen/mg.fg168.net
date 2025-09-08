<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NotificationTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'key',
        'name',
        'category',
        'subject',
        'content',
        'variables',
        'is_active',
        'is_system',
        'description',
    ];

    protected $casts = [
        'variables' => 'array',
        'is_active' => 'boolean',
        'is_system' => 'boolean',
    ];

    /**
     * 取得分類標籤
     */
    public function getCategoryLabelAttribute(): string
    {
        return match ($this->category) {
            'system' => '系統',
            'user' => '使用者',
            'security' => '安全',
            'maintenance' => '維護',
            default => '其他',
        };
    }
}