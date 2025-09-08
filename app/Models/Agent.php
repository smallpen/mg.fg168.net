<?php

namespace App\Models;

use App\Exceptions\InsufficientPointsException;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Agent extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $fillable = [
        'name',                 // 代理姓名
        'username',             // 原始帳號
        'account',              // 完整帳號 (prefix + username)
        'email',                // 電子郵件
        'phone',                // 電話號碼
        'prefix',               // 前置符號 (僅第一層代理)
        'level',                // 代理層級
        'parent_id',            // 上層代理ID
        'total_points',         // 總點數
        'allocated_points',     // 已分配點數
        'remaining_points',     // 剩餘點數
        'is_active',            // 是否啟用
        'created_by',           // 建立者
        'notes',                // 備註
    ];

    protected $casts = [
        'total_points' => 'decimal:2',
        'allocated_points' => 'decimal:2',
        'remaining_points' => 'decimal:2',
        'is_active' => 'boolean',
        'level' => 'integer',
    ];

    protected $dates = [
        'deleted_at',
    ];

    /**
     * 活動日誌配置
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'name', 'username', 'account', 'email', 'phone', 
                'prefix', 'level', 'parent_id', 'total_points', 
                'allocated_points', 'remaining_points', 'is_active', 'notes'
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    /**
     * 關聯關係：上層代理
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Agent::class, 'parent_id');
    }

    /**
     * 關聯關係：下層代理
     */
    public function children(): HasMany
    {
        return $this->hasMany(Agent::class, 'parent_id');
    }

    /**
     * 關聯關係：隸屬玩家
     */
    public function players(): HasMany
    {
        return $this->hasMany(Player::class);
    }

    /**
     * 關聯關係：點數交易記錄
     */
    public function pointTransactions(): HasMany
    {
        return $this->hasMany(PointTransaction::class);
    }

    /**
     * 關聯關係：建立者
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * 取得完整前置符號
     */
    public function getFullPrefixAttribute(): string
    {
        if ($this->level === 1) {
            return $this->prefix ?? '';
        }
        
        return $this->parent ? $this->parent->full_prefix : '';
    }

    /**
     * 取得所有下層代理（遞迴）
     */
    public function getAllDescendants(): Collection
    {
        return $this->children->flatMap(function ($child) {
            return collect([$child])->merge($child->getAllDescendants());
        });
    }

    /**
     * 檢查是否可以分配指定點數
     */
    public function canAllocatePoints(float $amount): bool
    {
        return $this->remaining_points >= $amount;
    }

    /**
     * 分配點數（從剩餘點數轉為已分配點數）
     */
    public function allocatePoints(float $amount): void
    {
        if (!$this->canAllocatePoints($amount)) {
            throw new InsufficientPointsException('代理點數不足，無法分配');
        }

        $this->increment('allocated_points', $amount);
        $this->decrement('remaining_points', $amount);
    }

    /**
     * 回收點數（從已分配點數轉回剩餘點數）
     */
    public function recoverPoints(float $amount): void
    {
        $this->decrement('allocated_points', $amount);
        $this->increment('remaining_points', $amount);
    }

    /**
     * 取得代理路徑（從根代理到當前代理）
     */
    public function getAgentPathAttribute(): Collection
    {
        $path = collect([$this]);
        $current = $this;

        while ($current->parent) {
            $current = $current->parent;
            $path->prepend($current);
        }

        return $path;
    }

    /**
     * 檢查是否為根代理（第一層）
     */
    public function isRootAgent(): bool
    {
        return $this->level === 1;
    }

    /**
     * 檢查是否有下層關聯
     */
    public function hasDependencies(): bool
    {
        return $this->children()->exists() || $this->players()->exists();
    }

    /**
     * 範圍查詢：啟用的代理
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * 範圍查詢：指定層級的代理
     */
    public function scopeLevel($query, int $level)
    {
        return $query->where('level', $level);
    }

    /**
     * 範圍查詢：根代理（第一層）
     */
    public function scopeRoot($query)
    {
        return $query->where('level', 1);
    }
}
