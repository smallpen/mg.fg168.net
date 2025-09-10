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

class Player extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $fillable = [
        'name',                 // 玩家姓名
        'username',             // 原始帳號
        'account',              // 完整帳號 (prefix + username)
        'email',                // 電子郵件
        'phone',                // 電話號碼
        'agent_id',             // 隸屬代理ID
        'total_points',         // 總點數
        'available_points',     // 可用點數
        'is_active',            // 是否啟用
        'created_by',           // 建立者
        'notes',                // 備註
    ];

    protected $casts = [
        'total_points' => 'decimal:2',
        'available_points' => 'decimal:2',
        'is_active' => 'boolean',
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
                'agent_id', 'points', 'is_active', 'notes'
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    /**
     * 關聯關係：隸屬代理
     */
    public function agent(): BelongsTo
    {
        return $this->belongsTo(Agent::class);
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
     * 取得代理路徑（從根代理到隸屬代理）
     */
    public function getAgentPathAttribute(): Collection
    {
        if (!$this->agent) {
            return collect();
        }

        $path = collect([$this->agent]);
        $current = $this->agent;

        while ($current->parent) {
            $current = $current->parent;
            $path->prepend($current);
        }

        return $path;
    }

    /**
     * 取得完整前置符號（繼承自隸屬代理）
     */
    public function getFullPrefixAttribute(): string
    {
        return $this->agent ? $this->agent->full_prefix : '';
    }

    /**
     * 檢查是否可以扣除指定點數
     */
    public function canDeductPoints(float $amount): bool
    {
        return $this->available_points >= $amount;
    }

    /**
     * 增加點數
     */
    public function addPoints(float $amount): void
    {
        $this->increment('total_points', $amount);
        $this->increment('available_points', $amount);
    }

    /**
     * 扣除點數
     */
    public function deductPoints(float $amount): void
    {
        if (!$this->canDeductPoints($amount)) {
            throw new InsufficientPointsException('玩家點數不足，無法扣除');
        }

        $this->decrement('available_points', $amount);
    }

    /**
     * 取得代理層級路徑字串
     */
    public function getAgentPathStringAttribute(): string
    {
        return $this->agent_path->pluck('name')->implode(' > ');
    }

    /**
     * 範圍查詢：啟用的玩家
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * 範圍查詢：隸屬於指定代理的玩家
     */
    public function scopeByAgent($query, int $agentId)
    {
        return $query->where('agent_id', $agentId);
    }

    /**
     * 範圍查詢：點數大於指定數量的玩家
     */
    public function scopeWithPointsGreaterThan($query, float $amount)
    {
        return $query->where('available_points', '>', $amount);
    }
}
