<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * 使用者自訂快捷鍵模型
 */
class UserKeyboardShortcut extends Model
{
    use HasFactory;

    /**
     * 資料表名稱
     */
    protected $table = 'user_keyboard_shortcuts';

    /**
     * 可批量賦值的屬性
     */
    protected $fillable = [
        'user_id',
        'shortcut_key',
        'action',
        'target',
        'description',
        'category',
        'enabled',
    ];

    /**
     * 屬性轉換
     */
    protected $casts = [
        'enabled' => 'boolean',
    ];

    /**
     * 關聯到使用者
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * 範圍查詢：啟用的快捷鍵
     */
    public function scopeEnabled($query)
    {
        return $query->where('enabled', true);
    }

    /**
     * 範圍查詢：依分類
     */
    public function scopeByCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    /**
     * 範圍查詢：依使用者
     */
    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * 轉換為快捷鍵配置陣列
     */
    public function toShortcutConfig(): array
    {
        return [
            'action' => $this->action,
            'target' => $this->target,
            'description' => $this->description,
            'category' => $this->category,
            'enabled' => $this->enabled,
        ];
    }
}
