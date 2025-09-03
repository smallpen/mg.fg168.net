<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * 安全事件模型
 * 
 * 記錄系統中發生的安全事件，包括登入失敗、權限違規、可疑活動等
 */
class SecurityIncident extends Model
{
    use HasFactory;

    protected $fillable = [
        'event_type',
        'severity',
        'user_id',
        'ip_address',
        'user_agent',
        'data',
        'resolved',
        'resolution_notes',
        'resolved_by',
        'resolved_at',
    ];

    protected $casts = [
        'data' => 'array',
        'resolved' => 'boolean',
        'resolved_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * 關聯到使用者
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * 關聯到解決者
     */
    public function resolver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    /**
     * 取得嚴重程度的中文顯示
     */
    public function getSeverityLabelAttribute(): string
    {
        return match($this->severity) {
            'low' => '低',
            'medium' => '中',
            'high' => '高',
            'critical' => '嚴重',
            default => '未知'
        };
    }

    /**
     * 取得事件類型的中文顯示
     */
    public function getEventTypeLabelAttribute(): string
    {
        return match($this->event_type) {
            'login_failure' => '登入失敗',
            'permission_violation' => '權限違規',
            'suspicious_activity' => '可疑活動',
            'system_anomaly' => '系統異常',
            'brute_force_attack' => '暴力破解攻擊',
            'unauthorized_access' => '未授權存取',
            default => $this->event_type
        };
    }

    /**
     * 取得狀態的中文顯示
     */
    public function getStatusLabelAttribute(): string
    {
        if ($this->resolved) {
            return '已處理';
        }
        
        // 根據嚴重程度和時間判斷狀態
        $hoursAgo = $this->created_at->diffInHours(now());
        
        if ($this->severity === 'critical' && $hoursAgo > 1) {
            return '逾期未處理';
        } elseif ($this->severity === 'high' && $hoursAgo > 4) {
            return '逾期未處理';
        } elseif ($hoursAgo > 24) {
            return '待處理';
        }
        
        return '調查中';
    }

    /**
     * 取得嚴重程度的顏色類別
     */
    public function getSeverityColorAttribute(): string
    {
        return match($this->severity) {
            'low' => 'bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-400',
            'medium' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/20 dark:text-yellow-400',
            'high' => 'bg-red-100 text-red-800 dark:bg-red-900/20 dark:text-red-400',
            'critical' => 'bg-red-100 text-red-800 dark:bg-red-900/20 dark:text-red-400',
            default => 'bg-gray-100 text-gray-800 dark:bg-gray-900/20 dark:text-gray-400'
        };
    }

    /**
     * 取得狀態的顏色類別
     */
    public function getStatusColorAttribute(): string
    {
        if ($this->resolved) {
            return 'bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-400';
        }
        
        $status = $this->getStatusLabelAttribute();
        
        return match($status) {
            '調查中' => 'bg-blue-100 text-blue-800 dark:bg-blue-900/20 dark:text-blue-400',
            '待處理' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/20 dark:text-yellow-400',
            '逾期未處理' => 'bg-red-100 text-red-800 dark:bg-red-900/20 dark:text-red-400',
            default => 'bg-gray-100 text-gray-800 dark:bg-gray-900/20 dark:text-gray-400'
        };
    }

    /**
     * 範圍查詢：未解決的事件
     */
    public function scopeUnresolved($query)
    {
        return $query->where('resolved', false);
    }

    /**
     * 範圍查詢：高風險事件
     */
    public function scopeHighRisk($query)
    {
        return $query->whereIn('severity', ['high', 'critical']);
    }

    /**
     * 範圍查詢：今日事件
     */
    public function scopeToday($query)
    {
        return $query->whereDate('created_at', today());
    }

    /**
     * 範圍查詢：最近事件
     */
    public function scopeRecent($query, int $hours = 24)
    {
        return $query->where('created_at', '>=', now()->subHours($hours));
    }
}