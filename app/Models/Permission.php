<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * 權限模型
 * 
 * 管理系統權限和角色關聯
 */
class Permission extends Model
{
    use HasFactory;

    /**
     * 可批量賦值的屬性
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'display_name',
        'description',
        'module',
    ];

    /**
     * 權限屬於的角色關聯
     * 
     * @return BelongsToMany
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'role_permissions')
                    ->withTimestamps();
    }

    /**
     * 根據模組分組權限
     * 
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function groupedByModule()
    {
        return static::all()->groupBy('module');
    }

    /**
     * 取得特定模組的權限
     * 
     * @param string $module
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function getByModule(string $module)
    {
        return static::where('module', $module)->get();
    }

    /**
     * 檢查權限是否被任何角色使用
     * 
     * @return bool
     */
    public function isInUse(): bool
    {
        return $this->roles()->exists();
    }

    /**
     * 取得使用此權限的角色數量
     * 
     * @return int
     */
    public function getRoleCountAttribute(): int
    {
        return $this->roles()->count();
    }
}