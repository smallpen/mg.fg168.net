<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Traits\HasPermissions;

/**
 * 使用者模型
 * 
 * 處理使用者認證、角色和權限管理
 */
class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, HasPermissions, SoftDeletes;

    /**
     * 可批量賦值的屬性
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'username',
        'name',
        'email',
        'password',
        'avatar',
        'theme_preference',
        'custom_themes',
        'locale',
        'is_active',
        'accessibility_preferences',
        'preferences',
    ];

    /**
     * 應該隱藏的屬性
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * 屬性類型轉換
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'is_active' => 'boolean',
        'accessibility_preferences' => 'array',
        'custom_themes' => 'array',
        'preferences' => 'array',
    ];

    /**
     * 使用者擁有的角色關聯
     * 
     * @return BelongsToMany
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'user_roles')
                    ->withTimestamps();
    }

    /**
     * 使用者的通知關聯
     * 
     * @return HasMany
     */
    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class)->latest();
    }

    /**
     * 使用者的設定變更記錄關聯
     * 
     * @return HasMany
     */
    public function settingChanges(): HasMany
    {
        return $this->hasMany(SettingChange::class, 'changed_by');
    }

    /**
     * 檢查使用者是否擁有特定角色
     * 
     * @param string $role
     * @return bool
     */
    public function hasRole(string $role): bool
    {
        return $this->roles()->where('name', $role)->exists();
    }

    /**
     * 檢查使用者是否擁有任一指定角色
     * 
     * @param array $roles
     * @return bool
     */
    public function hasAnyRole(array $roles): bool
    {
        return $this->roles()->whereIn('name', $roles)->exists();
    }

    /**
     * 為使用者指派角色
     * 
     * @param string|Role $role
     * @return void
     */
    public function assignRole($role): void
    {
        if (is_string($role)) {
            $role = Role::where('name', $role)->firstOrFail();
        }

        if (!$this->roles()->where('role_id', $role->id)->exists()) {
            $this->roles()->attach($role->id);
        }
    }

    /**
     * 移除使用者的角色
     * 
     * @param string|Role $role
     * @return void
     */
    public function removeRole($role): void
    {
        if (is_string($role)) {
            $role = Role::where('name', $role)->firstOrFail();
        }

        $this->roles()->detach($role->id);
    }

    /**
     * 取得使用者的顯示名稱
     * 
     * @return string
     */
    public function getDisplayNameAttribute(): string
    {
        return $this->name ?: $this->username;
    }

    /**
     * 檢查使用者是否為超級管理員
     * 
     * @return bool
     */
    public function isSuperAdmin(): bool
    {
        return $this->hasRole('super_admin');
    }

    /**
     * 檢查使用者是否為管理員
     * 
     * @return bool
     */
    public function isAdmin(): bool
    {
        return $this->hasRole('admin') || $this->isSuperAdmin();
    }

    /**
     * 取得用於認證的使用者名稱欄位
     * 
     * @return string
     */
    public function username()
    {
        return 'username';
    }

    /**
     * 取得用於認證的使用者名稱
     * 
     * @return string
     */
    public function getAuthIdentifierName()
    {
        return 'username';
    }

    /**
     * 取得使用者的主要角色
     *
     * @return string|null
     */
    public function getPrimaryRoleAttribute(): ?string
    {
        $role = $this->roles()->orderBy('name')->first();
        return $role ? $role->display_name : null;
    }

    /**
     * 取得本地化的主要角色名稱
     *
     * @return string|null
     */
    public function getLocalizedPrimaryRoleAttribute(): ?string
    {
        $role = $this->roles()->orderBy('name')->first();
        
        if (!$role) {
            return __('admin.users.no_role');
        }

        // 嘗試從語言檔案取得本地化名稱
        $localizedName = __("admin.roles.names.{$role->name}");
        
        // 如果找不到本地化名稱，使用 display_name
        return $localizedName !== "admin.roles.names.{$role->name}" 
            ? $localizedName 
            : $role->display_name;
    }

    /**
     * 取得所有角色的本地化名稱
     *
     * @return array
     */
    public function getLocalizedRolesAttribute(): array
    {
        return $this->roles->map(function ($role) {
            $localizedName = __("admin.roles.names.{$role->name}");
            return $localizedName !== "admin.roles.names.{$role->name}" 
                ? $localizedName 
                : $role->display_name;
        })->toArray();
    }

    /**
     * 取得角色數量的本地化顯示
     *
     * @return string
     */
    public function getRoleCountDisplayAttribute(): string
    {
        $count = $this->roles()->count();
        
        if ($count === 0) {
            return __('admin.users.no_role');
        }
        
        if ($count === 1) {
            return $this->localized_primary_role;
        }
        
        return $this->localized_primary_role . ' +' . ($count - 1);
    }

    /**
     * 取得使用者頭像 URL
     *
     * @return string
     */
    public function getAvatarUrlAttribute(): string
    {
        if ($this->avatar) {
            return asset('storage/avatars/' . $this->avatar);
        }
        
        // 使用 Gravatar 作為預設頭像
        $hash = md5(strtolower(trim($this->email ?? $this->username)));
        return "https://www.gravatar.com/avatar/{$hash}?d=identicon&s=40";
    }

    /**
     * 取得格式化的建立時間
     *
     * @return string
     */
    public function getFormattedCreatedAtAttribute(): string
    {
        return \App\Helpers\DateTimeHelper::formatForUserList($this->created_at);
    }

    /**
     * 取得格式化的更新時間
     *
     * @return string
     */
    public function getFormattedUpdatedAtAttribute(): string
    {
        return \App\Helpers\DateTimeHelper::formatForUserList($this->updated_at);
    }

    /**
     * 取得相對時間格式的建立時間
     *
     * @return string
     */
    public function getCreatedAtRelativeAttribute(): string
    {
        return \App\Helpers\DateTimeHelper::formatRelative($this->created_at);
    }

    /**
     * 取得最後登入時間的格式化顯示
     *
     * @return string
     */
    public function getFormattedLastLoginAttribute(): string
    {
        return $this->last_login_at ? \App\Helpers\DateTimeHelper::formatDateTime($this->last_login_at) : '從未登入';
    }

    /**
     * 取得帳號狀態變更時間的格式化顯示
     *
     * @return string
     */
    public function getFormattedStatusChangedAtAttribute(): string
    {
        return \App\Helpers\DateTimeHelper::formatDateTime($this->updated_at);
    }

    /**
     * 取得本地化的狀態文字
     *
     * @return string
     */
    public function getLocalizedStatusAttribute(): string
    {
        return $this->is_active 
            ? __('admin.users.active') 
            : __('admin.users.inactive');
    }

    /**
     * 檢查是否可以被刪除
     *
     * @return bool
     */
    public function canBeDeleted(): bool
    {
        // 檢查是否為超級管理員
        if ($this->isSuperAdmin()) {
            return false;
        }

        // 檢查是否為當前登入使用者
        if (auth()->check() && auth()->id() === $this->id) {
            return false;
        }

        return true;
    }

    /**
     * 軟刪除使用者
     *
     * @return bool
     */
    public function softDelete(): bool
    {
        if (!$this->canBeDeleted()) {
            return false;
        }

        // 先停用使用者
        $this->update(['is_active' => false]);
        
        // 移除所有角色關聯
        $this->roles()->detach();
        
        // 執行軟刪除
        return $this->delete();
    }

    /**
     * 恢復軟刪除的使用者
     *
     * @return bool
     */
    public function restoreUser(): bool
    {
        return $this->restore();
    }

    /**
     * 切換使用者狀態
     *
     * @return bool
     */
    public function toggleStatus(): bool
    {
        return $this->update(['is_active' => !$this->is_active]);
    }
}