<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

/**
 * 角色模型
 * 
 * 管理系統角色和權限關聯
 */
class Role extends Model
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
        'parent_id',
        'is_active',
        'is_system_role',
    ];

    /**
     * 屬性轉換
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_active' => 'boolean',
        'is_system_role' => 'boolean',
    ];

    /**
     * 角色擁有的使用者關聯
     * 
     * @return BelongsToMany
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_roles')
                    ->withTimestamps();
    }

    /**
     * 角色擁有的權限關聯
     * 
     * @return BelongsToMany
     */
    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class, 'role_permissions')
                    ->withTimestamps();
    }

    /**
     * 父角色關聯
     * 
     * @return BelongsTo
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'parent_id');
    }

    /**
     * 子角色關聯
     * 
     * @return HasMany
     */
    public function children(): HasMany
    {
        return $this->hasMany(Role::class, 'parent_id');
    }

    /**
     * 檢查角色是否擁有特定權限
     * 
     * @param string $permission
     * @return bool
     */
    public function hasPermission(string $permission): bool
    {
        return $this->permissions()->where('permissions.name', $permission)->exists();
    }

    /**
     * 為角色指派權限
     * 
     * @param string|Permission $permission
     * @return void
     */
    public function givePermission($permission): void
    {
        if (is_string($permission)) {
            $permission = Permission::where('name', $permission)->firstOrFail();
        }

        if (!$this->permissions()->where('permissions.id', $permission->id)->exists()) {
            $this->permissions()->attach($permission->id);
        }
    }

    /**
     * 移除角色的權限
     * 
     * @param string|Permission $permission
     * @return void
     */
    public function revokePermission($permission): void
    {
        if (is_string($permission)) {
            $permission = Permission::where('name', $permission)->firstOrFail();
        }

        $this->permissions()->detach($permission->id);
    }

    /**
     * 同步角色的權限
     * 
     * @param array $permissions
     * @return void
     */
    public function syncPermissions(array $permissions): void
    {
        $permissionIds = collect($permissions)->map(function ($permission) {
            if (is_string($permission)) {
                return Permission::where('name', $permission)->firstOrFail()->id;
            }
            return $permission instanceof Permission ? $permission->id : $permission;
        })->toArray();

        $this->permissions()->sync($permissionIds);
    }

    /**
     * 取得角色的使用者數量
     * 
     * @return int
     */
    public function getUserCountAttribute(): int
    {
        return $this->users()->count();
    }

    /**
     * 取得本地化的角色顯示名稱
     *
     * @return string
     */
    public function getLocalizedDisplayNameAttribute(): string
    {
        // 檢查是否有對應的語言鍵
        $langKey = "admin.roles.names.{$this->name}";
        $translated = __($langKey);
        
        // 如果翻譯存在且不等於語言鍵本身，使用翻譯
        if ($translated !== $langKey) {
            return $translated;
        }
        
        // 否則使用原始的 display_name
        return $this->display_name;
    }

    /**
     * 取得本地化的角色描述
     *
     * @return string
     */
    public function getLocalizedDescriptionAttribute(): string
    {
        // 檢查是否有對應的語言鍵
        $langKey = "admin.roles.descriptions.{$this->name}";
        $translated = __($langKey);
        
        // 如果翻譯存在且不等於語言鍵本身，使用翻譯
        if ($translated !== $langKey) {
            return $translated;
        }
        
        // 否則使用原始的 description
        return $this->description ?? '';
    }

    /**
     * 取得格式化的建立時間
     *
     * @return string
     */
    public function getFormattedCreatedAtAttribute(): string
    {
        return \App\Helpers\DateTimeHelper::formatDateTime($this->created_at);
    }

    /**
     * 取得格式化的更新時間
     *
     * @return string
     */
    public function getFormattedUpdatedAtAttribute(): string
    {
        return \App\Helpers\DateTimeHelper::formatDateTime($this->updated_at);
    }

    /**
     * 取得角色的權限數量
     * 
     * @return int
     */
    public function getPermissionCountAttribute(): int
    {
        return $this->permissions()->count();
    }

    /**
     * 檢查是否為系統角色
     * 
     * @return bool
     */
    public function getIsSystemRoleAttribute(): bool
    {
        return $this->attributes['is_system_role'] ?? false;
    }

    /**
     * 檢查角色是否可以被刪除
     * 
     * @return bool
     */
    public function getCanBeDeletedAttribute(): bool
    {
        // 系統角色不能刪除
        if ($this->is_system_role) {
            return false;
        }

        // 有使用者的角色不能刪除
        if ($this->users()->exists()) {
            return false;
        }

        // 有子角色的角色不能刪除
        if ($this->children()->exists()) {
            return false;
        }

        return true;
    }

    /**
     * 取得角色的所有權限（包含繼承的權限）
     * 
     * @param bool $useCache 是否使用快取
     * @return Collection
     */
    public function getAllPermissions(bool $useCache = true): Collection
    {
        if ($useCache) {
            return app(\App\Services\RoleCacheService::class)->getRoleAllPermissions($this);
        }

        $permissions = $this->permissions;
        
        // 如果有父角色，合併父角色的權限
        if ($this->parent) {
            $parentPermissions = $this->parent->getAllPermissions($useCache);
            $permissions = $permissions->merge($parentPermissions);
        }
        
        return $permissions->unique('id');
    }

    /**
     * 取得繼承的權限（僅來自父角色）
     * 
     * @param bool $useCache 是否使用快取
     * @return Collection
     */
    public function getInheritedPermissions(bool $useCache = true): Collection
    {
        if ($useCache) {
            return app(\App\Services\RoleCacheService::class)->getRoleInheritedPermissions($this);
        }

        if (!$this->parent) {
            return collect();
        }
        
        return $this->parent->getAllPermissions($useCache);
    }

    /**
     * 檢查設定父角色是否會造成循環依賴
     * 
     * @param int $parentId
     * @return bool
     */
    public function hasCircularDependency(int $parentId): bool
    {
        // 不能設定自己為父角色
        if ($parentId === $this->id) {
            return true;
        }

        // 檢查目標父角色是否已經是此角色的後代
        $targetParent = static::find($parentId);
        if (!$targetParent) {
            return false;
        }

        // 檢查目標父角色是否在此角色的後代中
        $descendants = $this->getDescendants();
        return $descendants->contains('id', $parentId);
    }

    /**
     * 為角色新增權限（支援權限依賴解析）
     * 
     * @param Permission $permission
     * @return void
     */
    public function givePermissionTo(Permission $permission): void
    {
        if (!$this->permissions()->where('permissions.id', $permission->id)->exists()) {
            // 檢查並新增依賴的權限
            $dependencies = $permission->dependencies;
            foreach ($dependencies as $dependency) {
                $this->givePermissionTo($dependency);
            }
            
            $this->permissions()->attach($permission->id);
        }
    }

    /**
     * 移除角色的權限（支援依賴檢查）
     * 
     * @param Permission $permission
     * @return void
     */
    public function revokePermissionTo(Permission $permission): void
    {
        // 檢查是否有其他權限依賴此權限
        $dependents = $permission->dependents;
        $currentPermissions = $this->permissions;
        
        foreach ($dependents as $dependent) {
            if ($currentPermissions->contains('id', $dependent->id)) {
                // 如果有依賴此權限的其他權限，先移除依賴的權限
                $this->revokePermissionTo($dependent);
            }
        }
        
        $this->permissions()->detach($permission->id);
    }

    /**
     * 檢查角色是否擁有特定權限（包含繼承的權限）
     * 
     * @param string $permission
     * @return bool
     */
    public function hasPermissionIncludingInherited(string $permission): bool
    {
        return $this->getAllPermissions()->contains('name', $permission);
    }

    /**
     * 取得角色層級深度
     * 
     * @return int
     */
    public function getDepth(): int
    {
        $depth = 0;
        $current = $this->parent;
        
        while ($current) {
            $depth++;
            $current = $current->parent;
        }
        
        return $depth;
    }

    /**
     * 取得角色的所有祖先角色
     * 
     * @return Collection
     */
    public function getAncestors(): Collection
    {
        $ancestors = collect();
        $current = $this->parent;
        
        while ($current) {
            $ancestors->push($current);
            $current = $current->parent;
        }
        
        return $ancestors;
    }

    /**
     * 取得角色的所有後代角色
     * 
     * @return Collection
     */
    public function getDescendants(): Collection
    {
        $descendants = collect();
        
        foreach ($this->children as $child) {
            $descendants->push($child);
            $descendants = $descendants->merge($child->getDescendants());
        }
        
        return $descendants;
    }

    /**
     * 取得角色的直接權限（不包含繼承）
     * 
     * @param bool $useCache 是否使用快取
     * @return Collection
     */
    public function getDirectPermissions(bool $useCache = true): Collection
    {
        if ($useCache) {
            return app(\App\Services\RoleCacheService::class)->getRoleDirectPermissions($this);
        }

        return $this->permissions;
    }

    /**
     * 檢查角色是否有子角色
     * 
     * @return bool
     */
    public function hasChildren(): bool
    {
        return $this->children()->exists();
    }

    /**
     * 檢查角色是否有父角色
     * 
     * @return bool
     */
    public function hasParent(): bool
    {
        return !is_null($this->parent_id);
    }

    /**
     * 取得角色在層級中的位置路徑
     * 
     * @return string
     */
    public function getHierarchyPath(): string
    {
        $path = collect();
        $current = $this;
        
        while ($current) {
            $path->prepend($current->display_name);
            $current = $current->parent;
        }
        
        return $path->implode(' > ');
    }

    /**
     * 檢查是否為根角色（沒有父角色）
     * 
     * @return bool
     */
    public function isRoot(): bool
    {
        return is_null($this->parent_id);
    }

    /**
     * 檢查是否為葉子角色（沒有子角色）
     * 
     * @return bool
     */
    public function isLeaf(): bool
    {
        return !$this->hasChildren();
    }

    /**
     * 取得角色的權限統計
     * 
     * @return array
     */
    public function getPermissionStats(): array
    {
        $directPermissions = $this->getDirectPermissions();
        $allPermissions = $this->getAllPermissions();
        $inheritedPermissions = $this->getInheritedPermissions();
        
        return [
            'direct_count' => $directPermissions->count(),
            'inherited_count' => $inheritedPermissions->count(),
            'total_count' => $allPermissions->count(),
            'by_module' => $allPermissions->groupBy('module')->map->count()->toArray()
        ];
    }

    /**
     * 清除角色的所有權限
     * 
     * @return void
     */
    public function clearAllPermissions(): void
    {
        $this->permissions()->detach();
    }

    /**
     * 檢查角色是否擁有任何權限
     * 
     * @return bool
     */
    public function hasAnyPermission(): bool
    {
        return $this->permissions()->exists();
    }

    /**
     * 檢查角色是否擁有所有指定的權限
     * 
     * @param array $permissions
     * @return bool
     */
    public function hasAllPermissions(array $permissions): bool
    {
        $rolePermissions = $this->getAllPermissions()->pluck('name')->toArray();
        
        foreach ($permissions as $permission) {
            if (!in_array($permission, $rolePermissions)) {
                return false;
            }
        }
        
        return true;
    }

    /**
     * 檢查角色是否擁有任一指定的權限
     * 
     * @param array $permissions
     * @return bool
     */
    public function hasAnyOfPermissions(array $permissions): bool
    {
        $rolePermissions = $this->getAllPermissions()->pluck('name')->toArray();
        
        foreach ($permissions as $permission) {
            if (in_array($permission, $rolePermissions)) {
                return true;
            }
        }
        
        return false;
    }
}