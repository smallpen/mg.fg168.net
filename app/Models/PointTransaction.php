<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class PointTransaction extends Model
{
    use HasFactory, LogsActivity;

    // 交易類型常數
    const TYPE_AGENT_ALLOCATION = 'agent_allocation';
    const TYPE_AGENT_RECOVERY = 'agent_recovery';
    const TYPE_PLAYER_ALLOCATION = 'player_allocation';
    const TYPE_PLAYER_RECOVERY = 'player_recovery';
    const TYPE_PLAYER_CONSUMPTION = 'player_consumption';
    const TYPE_PLAYER_TRANSFER = 'player_transfer';
    const TYPE_SYSTEM_ADJUSTMENT = 'system_adjustment';

    protected $fillable = [
        'agent_id',             // 代理ID (可為空)
        'player_id',            // 玩家ID (可為空)
        'type',                 // 交易類型
        'amount',               // 交易金額
        'balance_before',       // 交易前餘額
        'balance_after',        // 交易後餘額
        'description',          // 交易描述
        'reference_id',         // 參考ID
        'created_by',           // 操作者
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'balance_before' => 'decimal:2',
        'balance_after' => 'decimal:2',
    ];

    /**
     * 活動日誌配置
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'agent_id', 'player_id', 'type', 'amount', 
                'balance_before', 'balance_after', 'description', 'reference_id'
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    /**
     * 關聯關係：代理
     */
    public function agent(): BelongsTo
    {
        return $this->belongsTo(Agent::class);
    }

    /**
     * 關聯關係：玩家
     */
    public function player(): BelongsTo
    {
        return $this->belongsTo(Player::class);
    }

    /**
     * 關聯關係：操作者
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * 取得交易類型的中文名稱
     */
    public function getTypeNameAttribute(): string
    {
        return match($this->type) {
            self::TYPE_AGENT_ALLOCATION => '代理點數分配',
            self::TYPE_AGENT_RECOVERY => '代理點數回收',
            self::TYPE_PLAYER_ALLOCATION => '玩家點數分配',
            self::TYPE_PLAYER_RECOVERY => '玩家點數回收',
            self::TYPE_PLAYER_CONSUMPTION => '玩家點數消費',
            self::TYPE_PLAYER_TRANSFER => '玩家轉移',
            self::TYPE_SYSTEM_ADJUSTMENT => '系統調整',
            default => '未知類型',
        };
    }

    /**
     * 檢查是否為正向交易（增加點數）
     */
    public function isPositiveTransaction(): bool
    {
        return $this->amount > 0;
    }

    /**
     * 檢查是否為負向交易（減少點數）
     */
    public function isNegativeTransaction(): bool
    {
        return $this->amount < 0;
    }

    /**
     * 取得交易對象（代理或玩家）
     */
    public function getTargetAttribute()
    {
        if ($this->agent_id) {
            return $this->agent;
        }
        
        if ($this->player_id) {
            return $this->player;
        }
        
        return null;
    }

    /**
     * 取得交易對象名稱
     */
    public function getTargetNameAttribute(): string
    {
        $target = $this->target;
        
        if (!$target) {
            return '未知對象';
        }
        
        $type = $this->agent_id ? '代理' : '玩家';
        return "{$type}: {$target->name}";
    }

    /**
     * 範圍查詢：代理相關交易
     */
    public function scopeForAgent($query, int $agentId)
    {
        return $query->where('agent_id', $agentId);
    }

    /**
     * 範圍查詢：玩家相關交易
     */
    public function scopeForPlayer($query, int $playerId)
    {
        return $query->where('player_id', $playerId);
    }

    /**
     * 範圍查詢：指定類型的交易
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * 範圍查詢：正向交易（增加點數）
     */
    public function scopePositive($query)
    {
        return $query->where('amount', '>', 0);
    }

    /**
     * 範圍查詢：負向交易（減少點數）
     */
    public function scopeNegative($query)
    {
        return $query->where('amount', '<', 0);
    }

    /**
     * 範圍查詢：指定日期範圍的交易
     */
    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }

    /**
     * 取得所有交易類型
     */
    public static function getTransactionTypes(): array
    {
        return [
            self::TYPE_AGENT_ALLOCATION => '代理點數分配',
            self::TYPE_AGENT_RECOVERY => '代理點數回收',
            self::TYPE_PLAYER_ALLOCATION => '玩家點數分配',
            self::TYPE_PLAYER_RECOVERY => '玩家點數回收',
            self::TYPE_PLAYER_CONSUMPTION => '玩家點數消費',
            self::TYPE_PLAYER_TRANSFER => '玩家轉移',
            self::TYPE_SYSTEM_ADJUSTMENT => '系統調整',
        ];
    }
}
