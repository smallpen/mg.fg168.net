<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Carbon\Carbon;

/**
 * 活動記錄模型
 * 
 * 記錄系統中的各種使用者活動和操作
 */
class Activity extends Model
{
    use HasFactory;

    /**
     * 可批量賦值的屬性
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'type',
        'event',
        'description',
        'module',
        'user_id',
        'subject_id',
        'subject_type',
        'properties',
        'ip_address',
        'user_agent',
    ];

    /**
     * 屬性轉換
     *
     * @var array<string, string>
     */
    protected $casts = [
        'properties' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * 活動類型常數
     */
    public const TYPE_LOGIN = 'login';
    public const TYPE_LOGOUT = 'logout';
    public const TYPE_CREATE_USER = 'create_user';
    public const TYPE_UPDATE_USER = 'update_user';
    public const TYPE_DELETE_USER = 'delete_user';
    public const TYPE_CREATE_ROLE = 'create_role';
    public const TYPE_UPDATE_ROLE = 'update_role';
    public const TYPE_DELETE_ROLE = 'delete_role';
    public const TYPE_ASSIGN_ROLE = 'assign_role';
    public const TYPE_REMOVE_ROLE = 'remove_role';
    public const TYPE_UPDATE_PERMISSIONS = 'update_permissions';
    public const TYPE_VIEW_DASHBOARD = 'view_dashboard';
    public const TYPE_EXPORT_DATA = 'export_data';
    public const TYPE_QUICK_ACTION = 'quick_action';

    /**
     * 模組常數
     */
    public const MODULE_AUTH = 'auth';
    public const MODULE_USERS = 'users';
    public const MODULE_ROLES = 'roles';
    public const MODULE_PERMISSIONS = 'permissions';
    public const MODULE_DASHBOARD = 'dashboard';
    public const MODULE_SYSTEM = 'system';

    /**
     * 執行活動的使用者關聯
     *
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * 執行活動的使用者關聯（別名，用於相容性）
     *
     * @return BelongsTo
     */
    public function causer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * 被操作的對象關聯（多型關聯）
     *
     * @return MorphTo
     */
    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * 取得活動的圖示
     *
     * @return string
     */
    public function getIconAttribute(): string
    {
        return match ($this->type) {
            self::TYPE_LOGIN => 'login',
            self::TYPE_LOGOUT => 'logout',
            self::TYPE_CREATE_USER, self::TYPE_CREATE_ROLE => 'plus-circle',
            self::TYPE_UPDATE_USER, self::TYPE_UPDATE_ROLE, self::TYPE_UPDATE_PERMISSIONS => 'pencil',
            self::TYPE_DELETE_USER, self::TYPE_DELETE_ROLE => 'trash',
            self::TYPE_ASSIGN_ROLE, self::TYPE_REMOVE_ROLE => 'user-group',
            self::TYPE_VIEW_DASHBOARD => 'chart-bar',
            self::TYPE_EXPORT_DATA => 'download',
            self::TYPE_QUICK_ACTION => 'lightning-bolt',
            default => 'information-circle',
        };
    }

    /**
     * 取得活動的顏色
     *
     * @return string
     */
    public function getColorAttribute(): string
    {
        return match ($this->type) {
            self::TYPE_LOGIN, self::TYPE_CREATE_USER, self::TYPE_CREATE_ROLE => 'success',
            self::TYPE_LOGOUT => 'info',
            self::TYPE_UPDATE_USER, self::TYPE_UPDATE_ROLE, self::TYPE_UPDATE_PERMISSIONS => 'warning',
            self::TYPE_DELETE_USER, self::TYPE_DELETE_ROLE => 'danger',
            self::TYPE_ASSIGN_ROLE, self::TYPE_REMOVE_ROLE => 'primary',
            self::TYPE_VIEW_DASHBOARD, self::TYPE_EXPORT_DATA => 'info',
            self::TYPE_QUICK_ACTION => 'purple',
            default => 'gray',
        };
    }

    /**
     * 取得格式化的時間
     *
     * @return string
     */
    public function getFormattedTimeAttribute(): string
    {
        return $this->created_at->diffForHumans();
    }

    /**
     * 取得詳細的時間
     *
     * @return string
     */
    public function getDetailedTimeAttribute(): string
    {
        return $this->created_at->format('Y-m-d H:i:s');
    }

    /**
     * 範圍查詢：按使用者篩選
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
     * 範圍查詢：按類型篩選
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $type
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * 範圍查詢：按模組篩選
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $module
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByModule($query, string $module)
    {
        return $query->where('module', $module);
    }

    /**
     * 範圍查詢：最近的活動
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $days
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeRecent($query, int $days = 7)
    {
        return $query->where('created_at', '>=', Carbon::now()->subDays($days));
    }

    /**
     * 範圍查詢：今天的活動
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeToday($query)
    {
        return $query->whereDate('created_at', Carbon::today());
    }

    /**
     * 範圍查詢：本週的活動
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeThisWeek($query)
    {
        return $query->whereBetween('created_at', [
            Carbon::now()->startOfWeek(),
            Carbon::now()->endOfWeek()
        ]);
    }

    /**
     * 建立活動記錄的靜態方法
     *
     * @param string $type
     * @param string $description
     * @param array $options
     * @return static
     */
    public static function log(string $type, string $description, array $options = []): static
    {
        return static::create([
            'type' => $type,
            'description' => $description,
            'module' => $options['module'] ?? null,
            'user_id' => $options['user_id'] ?? auth()->id(),
            'subject_id' => $options['subject_id'] ?? null,
            'subject_type' => $options['subject_type'] ?? null,
            'properties' => $options['properties'] ?? null,
            'ip_address' => $options['ip_address'] ?? request()->ip(),
            'user_agent' => $options['user_agent'] ?? request()->userAgent(),
        ]);
    }
}
