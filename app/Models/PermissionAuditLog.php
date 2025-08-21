<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * 權限審計日誌模型
 * 
 * 記錄權限相關的所有操作和變更
 */
class PermissionAuditLog extends Model
{
    use HasFactory;

    /**
     * 資料表名稱
     *
     * @var string
     */
    protected $table = 'permission_audit_logs';

    /**
     * 可批量賦值的屬性
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'action',
        'permission_id',
        'permission_name',
        'permission_module',
        'user_id',
        'username',
        'ip_address',
        'user_agent',
        'url',
        'method',
        'data',
    ];

    /**
     * 屬性轉換
     *
     * @var array<string, string>
     */
    protected $casts = [
        'data' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * 關聯到權限模型
     * 
     * @return BelongsTo
     */
    public function permission(): BelongsTo
    {
        return $this->belongsTo(Permission::class);
    }

    /**
     * 關聯到使用者模型
     * 
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * 取得操作類型的本地化名稱
     * 
     * @return string
     */
    public function getActionDisplayNameAttribute(): string
    {
        $actionNames = [
            'created' => '建立',
            'updated' => '更新',
            'deleted' => '刪除',
            'dependency_added' => '新增依賴',
            'dependency_removed' => '移除依賴',
            'dependency_synced' => '同步依賴',
            'role_assigned' => '指派角色',
            'role_unassigned' => '取消角色',
            'permission_exported' => '匯出權限',
            'permission_imported' => '匯入權限',
            'permission_test' => '權限測試',
        ];

        return $actionNames[$this->action] ?? $this->action;
    }

    /**
     * 取得變更摘要
     * 
     * @return string
     */
    public function getChangesSummaryAttribute(): string
    {
        if (!isset($this->data['changes']) || empty($this->data['changes'])) {
            return '';
        }

        $changes = $this->data['changes'];
        $summary = [];

        foreach ($changes as $field => $change) {
            if (is_array($change) && isset($change['old'], $change['new'])) {
                $summary[] = "{$field}: {$change['old']} → {$change['new']}";
            }
        }

        return implode(', ', $summary);
    }

    /**
     * 檢查是否為重要操作
     * 
     * @return bool
     */
    public function getIsCriticalActionAttribute(): bool
    {
        $criticalActions = [
            'deleted',
            'dependency_added',
            'dependency_removed',
            'role_assigned',
            'role_unassigned',
        ];

        return in_array($this->action, $criticalActions);
    }

    /**
     * 取得操作的嚴重程度
     * 
     * @return string
     */
    public function getSeverityLevelAttribute(): string
    {
        $severityMap = [
            'created' => 'info',
            'updated' => 'info',
            'deleted' => 'critical',
            'dependency_added' => 'warning',
            'dependency_removed' => 'warning',
            'dependency_synced' => 'info',
            'role_assigned' => 'warning',
            'role_unassigned' => 'warning',
            'permission_exported' => 'info',
            'permission_imported' => 'warning',
            'permission_test' => 'info',
        ];

        return $severityMap[$this->action] ?? 'info';
    }

    /**
     * 範圍查詢：按操作類型
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $action
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByAction($query, string $action)
    {
        return $query->where('action', $action);
    }

    /**
     * 範圍查詢：按權限
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $permissionId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByPermission($query, int $permissionId)
    {
        return $query->where('permission_id', $permissionId);
    }

    /**
     * 範圍查詢：按使用者
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $userId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * 範圍查詢：按時間範圍
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $startDate
     * @param string $endDate
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByDateRange($query, string $startDate, string $endDate)
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }

    /**
     * 範圍查詢：重要操作
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeCriticalActions($query)
    {
        return $query->whereIn('action', [
            'deleted',
            'dependency_added',
            'dependency_removed',
            'role_assigned',
            'role_unassigned',
        ]);
    }

    /**
     * 範圍查詢：最近的記錄
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $days
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeRecent($query, int $days = 30)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }
}
