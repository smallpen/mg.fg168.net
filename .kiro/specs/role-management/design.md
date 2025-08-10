# 角色管理功能設計文件

## 概述

角色管理功能提供完整的角色 CRUD 操作和權限矩陣管理，採用 Livewire 3.0 和 Repository Pattern 設計，支援角色層級、權限繼承和批量操作功能。

## 架構設計

### 核心元件架構

```
┌─────────────────┐    ┌──────────────────┐    ┌─────────────────┐
│   RoleList      │    │  PermissionMatrix│    │  RoleRepository │
│   Component     │◄──►│    Component     │◄──►│   Interface     │
└─────────────────┘    └──────────────────┘    └─────────────────┘
         │                        │                        │
         ▼                        ▼                        ▼
┌─────────────────┐    ┌──────────────────┐    ┌─────────────────┐
│   RoleForm      │    │  RoleDelete      │    │   Permission    │
│   Component     │    │   Modal          │    │   Repository    │
└─────────────────┘    └──────────────────┘    └─────────────────┘
```

## 元件設計

### 1. RoleList 元件

**檔案位置**: `app/Livewire/Admin/Roles/RoleList.php`

```php
class RoleList extends Component
{
    // 搜尋和篩選
    public string $search = '';
    public string $permissionCountFilter = 'all';
    public string $userCountFilter = 'all';
    
    // 分頁和排序
    public int $perPage = 20;
    public string $sortField = 'created_at';
    public string $sortDirection = 'desc';
    
    // 批量操作
    public array $selectedRoles = [];
    public bool $selectAll = false;
    public string $bulkAction = '';
    
    // 計算屬性
    public function getRolesProperty(): LengthAwarePaginator
    public function getStatsProperty(): array
    public function getFilterOptionsProperty(): array
    
    // 操作方法
    public function createRole(): void
    public function editRole(int $roleId): void
    public function duplicateRole(int $roleId): void
    public function deleteRole(int $roleId): void
    public function executeBulkAction(): void
}
```

### 2. RoleForm 元件

**檔案位置**: `app/Livewire/Admin/Roles/RoleForm.php`

```php
class RoleForm extends Component
{
    public ?Role $role = null;
    public string $name = '';
    public string $display_name = '';
    public string $description = '';
    public ?int $parent_id = null;
    public array $selectedPermissions = [];
    
    // 驗證規則
    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:50', 'regex:/^[a-z_]+$/', 
                      Rule::unique('roles')->ignore($this->role?->id)],
            'display_name' => ['required', 'string', 'max:50'],
            'description' => ['nullable', 'string', 'max:255'],
            'parent_id' => ['nullable', 'exists:roles,id'],
        ];
    }
    
    // 操作方法
    public function save(): void
    public function cancel(): void
    public function resetForm(): void
}
```

### 3. PermissionMatrix 元件

**檔案位置**: `app/Livewire/Admin/Roles/PermissionMatrix.php`

```php
class PermissionMatrix extends Component
{
    public Role $role;
    public array $permissions = [];
    public array $modules = [];
    public string $selectedModule = 'all';
    
    // 計算屬性
    public function getGroupedPermissionsProperty(): Collection
    public function getModuleStatsProperty(): array
    
    // 權限操作
    public function togglePermission(int $permissionId): void
    public function toggleModulePermissions(string $module): void
    public function selectAllPermissions(): void
    public function clearAllPermissions(): void
    public function savePermissions(): void
}
```

## 資料存取層設計

### RoleRepository

```php
interface RoleRepositoryInterface
{
    public function getPaginatedRoles(array $filters, int $perPage): LengthAwarePaginator;
    public function getRoleWithPermissions(int $roleId): Role;
    public function getRoleHierarchy(): Collection;
    public function createRole(array $data): Role;
    public function updateRole(Role $role, array $data): bool;
    public function deleteRole(Role $role): bool;
    public function duplicateRole(Role $role, string $newName): Role;
    public function syncPermissions(Role $role, array $permissionIds): void;
    public function getRoleStats(): array;
    public function canDeleteRole(Role $role): bool;
}
```

### PermissionRepository

```php
interface PermissionRepositoryInterface
{
    public function getAllPermissions(): Collection;
    public function getPermissionsByModule(): Collection;
    public function getPermissionDependencies(): array;
    public function resolvePermissionDependencies(array $permissionIds): array;
}
```

## 資料模型設計

### Role 模型擴充

```php
class Role extends Model
{
    // 關聯關係
    public function permissions(): BelongsToMany
    public function users(): BelongsToMany
    public function parent(): BelongsTo
    public function children(): HasMany
    
    // 計算屬性
    public function getUserCountAttribute(): int
    public function getPermissionCountAttribute(): int
    public function getIsSystemRoleAttribute(): bool
    public function getCanBeDeletedAttribute(): bool
    
    // 權限操作
    public function givePermissionTo(Permission $permission): void
    public function revokePermissionTo(Permission $permission): void
    public function syncPermissions(array $permissions): void
    public function hasPermission(string $permission): bool
    
    // 層級操作
    public function getAllPermissions(): Collection
    public function getInheritedPermissions(): Collection
    public function hasCircularDependency(int $parentId): bool
}
```

## 使用者介面設計

### 角色列表頁面佈局

```
┌─────────────────────────────────────────────────────────────┐
│  角色管理                                    [+ 建立角色]     │
├─────────────────────────────────────────────────────────────┤
│  [搜尋框] [權限數篩選] [使用者數篩選] [重置] [批量操作▼]      │
├─────────────────────────────────────────────────────────────┤
│  ☐ 角色名稱  顯示名稱  描述    權限數  使用者數  建立時間  操作│
│  ☐ admin    管理員   系統管理   15     3      2024-01-01 [⋯]│
│  ☐ editor   編輯者   內容編輯    8     5      2024-01-02 [⋯]│
├─────────────────────────────────────────────────────────────┤
│                    [← 上一頁] 1 2 3 [下一頁 →]                │
└─────────────────────────────────────────────────────────────┘
```

### 權限矩陣介面

```
┌─────────────────────────────────────────────────────────────┐
│  權限設定 - 管理員角色                        [儲存] [取消]   │
├─────────────────────────────────────────────────────────────┤
│  模組篩選: [全部▼] [使用者管理] [角色管理] [系統設定]         │
├─────────────────────────────────────────────────────────────┤
│  使用者管理                                    [全選] [清除]  │
│  ☑ users.view     檢視使用者列表                            │
│  ☑ users.create   建立使用者                               │
│  ☑ users.edit     編輯使用者                               │
│  ☑ users.delete   刪除使用者                               │
│                                                            │
│  角色管理                                      [全選] [清除]  │
│  ☑ roles.view     檢視角色列表                             │
│  ☑ roles.create   建立角色                                │
│  ☐ roles.edit     編輯角色                                │
│  ☐ roles.delete   刪除角色                                │
└─────────────────────────────────────────────────────────────┘
```

## 安全性設計

### 權限檢查層級

1. **路由層級**: 中介軟體檢查基本存取權限
2. **元件層級**: mount() 方法檢查功能權限
3. **操作層級**: 每個操作方法檢查具體權限
4. **資料層級**: Repository 檢查資料存取權限

### 角色層級安全

```php
// 防止循環依賴
public function setParentRole(int $parentId): bool
{
    if ($this->hasCircularDependency($parentId)) {
        throw new InvalidArgumentException('不能設定循環依賴的父角色');
    }
    return $this->update(['parent_id' => $parentId]);
}

// 系統角色保護
public function delete(): bool
{
    if ($this->is_system_role) {
        throw new UnauthorizedException('不能刪除系統預設角色');
    }
    return parent::delete();
}
```

## 效能優化

### 查詢優化

```php
// 預載入關聯資料
Role::with(['permissions:id,name,module', 'users:id,name'])
    ->withCount(['permissions', 'users'])
    ->paginate($perPage);

// 權限矩陣快取
Cache::remember("role_permissions_{$roleId}", 3600, function () use ($roleId) {
    return Role::with('permissions')->find($roleId);
});
```

### 權限繼承快取

```php
// 快取角色的完整權限（包含繼承）
public function getAllPermissions(): Collection
{
    return Cache::remember("role_all_permissions_{$this->id}", 1800, function () {
        $permissions = $this->permissions;
        if ($this->parent) {
            $permissions = $permissions->merge($this->parent->getAllPermissions());
        }
        return $permissions->unique('id');
    });
}
```