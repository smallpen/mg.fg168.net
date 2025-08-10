# 權限管理功能設計文件

## 概述

權限管理功能提供細粒度的權限控制系統，包含權限 CRUD、依賴關係管理、使用情況分析、匯入匯出和審計功能。

## 架構設計

### 核心元件架構

```
┌─────────────────┐    ┌──────────────────┐    ┌─────────────────┐
│ PermissionList  │    │ PermissionForm   │    │ Permission      │
│   Component     │◄──►│   Component      │◄──►│  Repository     │
└─────────────────┘    └──────────────────┘    └─────────────────┘
         │                        │                        │
         ▼                        ▼                        ▼
┌─────────────────┐    ┌──────────────────┐    ┌─────────────────┐
│ DependencyGraph │    │ PermissionTest   │    │   Audit         │
│   Component     │    │   Component      │    │  Service        │
└─────────────────┘    └──────────────────┘    └─────────────────┘
```

## 元件設計

### 1. PermissionList 元件

**檔案位置**: `app/Livewire/Admin/Permissions/PermissionList.php`

```php
class PermissionList extends Component
{
    // 搜尋和篩選
    public string $search = '';
    public string $moduleFilter = 'all';
    public string $typeFilter = 'all';
    public string $usageFilter = 'all';
    
    // 顯示模式
    public string $viewMode = 'list'; // list, grouped, tree
    public array $expandedGroups = [];
    
    // 分頁和排序
    public int $perPage = 25;
    public string $sortField = 'module';
    public string $sortDirection = 'asc';
    
    // 批量操作
    public array $selectedPermissions = [];
    public string $bulkAction = '';
    
    // 計算屬性
    public function getPermissionsProperty(): LengthAwarePaginator
    public function getGroupedPermissionsProperty(): Collection
    public function getModulesProperty(): Collection
    public function getStatsProperty(): array
    
    // 操作方法
    public function createPermission(): void
    public function editPermission(int $permissionId): void
    public function deletePermission(int $permissionId): void
    public function toggleGroup(string $module): void
    public function exportPermissions(): void
    public function importPermissions(): void
}
```

### 2. PermissionForm 元件

**檔案位置**: `app/Livewire/Admin/Permissions/PermissionForm.php`

```php
class PermissionForm extends Component
{
    public ?Permission $permission = null;
    public string $name = '';
    public string $display_name = '';
    public string $description = '';
    public string $module = '';
    public string $type = '';
    public array $dependencies = [];
    public array $availableModules = [];
    public array $availableTypes = [];
    
    // 驗證規則
    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100', 'regex:/^[a-z_\.]+$/', 
                      Rule::unique('permissions')->ignore($this->permission?->id)],
            'display_name' => ['required', 'string', 'max:50'],
            'description' => ['nullable', 'string', 'max:255'],
            'module' => ['required', 'string', 'in:' . implode(',', $this->availableModules)],
            'type' => ['required', 'string', 'in:' . implode(',', $this->availableTypes)],
            'dependencies' => ['array', 'exists:permissions,id'],
        ];
    }
    
    // 操作方法
    public function save(): void
    public function cancel(): void
    public function addDependency(int $permissionId): void
    public function removeDependency(int $permissionId): void
    public function validateDependencies(): bool
}
```

### 3. DependencyGraph 元件

**檔案位置**: `app/Livewire/Admin/Permissions/DependencyGraph.php`

```php
class DependencyGraph extends Component
{
    public Permission $permission;
    public array $dependencies = [];
    public array $dependents = [];
    public string $graphData = '';
    
    // 計算屬性
    public function getDependencyTreeProperty(): array
    public function getDependentTreeProperty(): array
    
    // 操作方法
    public function generateGraphData(): string
    public function addDependency(int $permissionId): void
    public function removeDependency(int $permissionId): void
    public function validateCircularDependency(int $permissionId): bool
}
```

### 4. PermissionTest 元件

**檔案位置**: `app/Livewire/Admin/Permissions/PermissionTest.php`

```php
class PermissionTest extends Component
{
    public int $selectedUserId = 0;
    public int $selectedRoleId = 0;
    public string $permissionToTest = '';
    public array $testResults = [];
    public string $testMode = 'user'; // user, role
    
    // 計算屬性
    public function getUsersProperty(): Collection
    public function getRolesProperty(): Collection
    public function getPermissionsProperty(): Collection
    
    // 測試方法
    public function testUserPermission(): void
    public function testRolePermission(): void
    public function getPermissionPath(int $userId, string $permission): array
    public function clearResults(): void
}
```

## 資料存取層設計

### PermissionRepository

```php
interface PermissionRepositoryInterface
{
    public function getPaginatedPermissions(array $filters, int $perPage): LengthAwarePaginator;
    public function getPermissionsByModule(): Collection;
    public function getPermissionDependencies(int $permissionId): Collection;
    public function getPermissionDependents(int $permissionId): Collection;
    public function createPermission(array $data): Permission;
    public function updatePermission(Permission $permission, array $data): bool;
    public function deletePermission(Permission $permission): bool;
    public function syncDependencies(Permission $permission, array $dependencyIds): void;
    public function getUnusedPermissions(): Collection;
    public function getPermissionUsageStats(): array;
    public function exportPermissions(): array;
    public function importPermissions(array $data): array;
    public function canDeletePermission(Permission $permission): bool;
    public function hasCircularDependency(int $permissionId, array $dependencyIds): bool;
}
```

### AuditService

```php
class AuditService
{
    public function logPermissionChange(string $action, Permission $permission, array $changes = []): void
    public function getPermissionAuditLog(int $permissionId): Collection
    public function searchAuditLog(array $filters): LengthAwarePaginator
    public function cleanupOldAuditLogs(int $daysToKeep = 365): int
}
```

## 資料模型設計

### Permission 模型擴充

```php
class Permission extends Model
{
    // 關聯關係
    public function roles(): BelongsToMany
    public function dependencies(): BelongsToMany
    public function dependents(): BelongsToMany
    
    // 計算屬性
    public function getRoleCountAttribute(): int
    public function getUserCountAttribute(): int
    public function getIsSystemPermissionAttribute(): bool
    public function getCanBeDeletedAttribute(): bool
    public function getUsageStatsAttribute(): array
    
    // 依賴關係操作
    public function addDependency(Permission $permission): void
    public function removeDependency(Permission $permission): void
    public function getAllDependencies(): Collection
    public function getAllDependents(): Collection
    public function hasCircularDependency(array $dependencyIds): bool
    
    // 使用情況分析
    public function isUsed(): bool
    public function getUsageFrequency(): int
    public function getLastUsedAt(): ?Carbon
}
```

### PermissionDependency 樞紐模型

```php
class PermissionDependency extends Pivot
{
    protected $table = 'permission_dependencies';
    
    public function permission(): BelongsTo
    public function dependency(): BelongsTo
    
    // 驗證方法
    public static function validateNoCycle(int $permissionId, int $dependencyId): bool
    public static function getDependencyPath(int $fromId, int $toId): array
}
```

## 使用者介面設計

### 權限列表頁面佈局

```
┌─────────────────────────────────────────────────────────────┐
│  權限管理                    [匯出] [匯入] [+ 建立權限]       │
├─────────────────────────────────────────────────────────────┤
│  [搜尋框] [模組篩選] [類型篩選] [使用狀態] [檢視模式▼]       │
├─────────────────────────────────────────────────────────────┤
│  權限名稱        顯示名稱  模組    類型  角色數  描述    操作│
│  users.view      檢視使用者 使用者  檢視   3     檢視... [⋯]│
│  users.create    建立使用者 使用者  建立   2     建立... [⋯]│
│  └─ users.edit   編輯使用者 使用者  編輯   2     編輯... [⋯]│
│  └─ users.delete 刪除使用者 使用者  刪除   1     刪除... [⋯]│
├─────────────────────────────────────────────────────────────┤
│                    [← 上一頁] 1 2 3 [下一頁 →]                │
└─────────────────────────────────────────────────────────────┘
```

### 權限依賴關係圖

```
┌─────────────────────────────────────────────────────────────┐
│  權限依賴關係 - users.delete                                │
├─────────────────────────────────────────────────────────────┤
│  依賴權限 (此權限需要以下權限)                               │
│  ┌─────────────┐    ┌─────────────┐                        │
│  │ users.view  │───▶│ users.edit  │───▶ users.delete       │
│  └─────────────┘    └─────────────┘                        │
│                                                            │
│  被依賴權限 (以下權限需要此權限)                             │
│  users.delete ───▶ users.manage                           │
│                                                            │
│  [新增依賴] [移除依賴] [儲存變更]                            │
└─────────────────────────────────────────────────────────────┘
```

### 權限測試介面

```
┌─────────────────────────────────────────────────────────────┐
│  權限測試工具                                               │
├─────────────────────────────────────────────────────────────┤
│  測試模式: ○ 使用者權限  ● 角色權限                          │
│                                                            │
│  選擇角色: [管理員 ▼]                                       │
│  測試權限: [users.delete ▼]                                │
│                                                            │
│  [執行測試]                                                 │
│                                                            │
│  測試結果:                                                  │
│  ✓ 角色「管理員」擁有權限「users.delete」                    │
│                                                            │
│  權限路徑:                                                  │
│  管理員角色 → users.view → users.edit → users.delete       │
│                                                            │
│  [清除結果] [匯出報告]                                       │
└─────────────────────────────────────────────────────────────┘
```

## 安全性設計

### 權限操作安全

```php
// 系統權限保護
public function delete(): bool
{
    if ($this->is_system_permission) {
        throw new UnauthorizedException('不能刪除系統核心權限');
    }
    
    if ($this->roles()->exists()) {
        throw new InvalidOperationException('權限仍被角色使用，無法刪除');
    }
    
    return parent::delete();
}

// 循環依賴檢查
public function addDependency(Permission $dependency): void
{
    if ($this->hasCircularDependency([$dependency->id])) {
        throw new InvalidArgumentException('不能建立循環依賴關係');
    }
    
    $this->dependencies()->attach($dependency->id);
}
```

### 審計日誌記錄

```php
class PermissionAuditObserver
{
    public function created(Permission $permission): void
    {
        AuditService::logPermissionChange('created', $permission);
    }
    
    public function updated(Permission $permission): void
    {
        AuditService::logPermissionChange('updated', $permission, $permission->getChanges());
    }
    
    public function deleted(Permission $permission): void
    {
        AuditService::logPermissionChange('deleted', $permission);
    }
}
```

## 效能優化

### 權限查詢優化

```php
// 預載入關聯資料
Permission::with(['roles:id,name', 'dependencies:id,name', 'dependents:id,name'])
    ->withCount(['roles', 'dependencies', 'dependents'])
    ->paginate($perPage);

// 權限樹狀結構快取
Cache::remember('permission_tree', 3600, function () {
    return Permission::with('dependencies')->get()->groupBy('module');
});
```

### 依賴關係快取

```php
// 快取權限的完整依賴鏈
public function getAllDependencies(): Collection
{
    return Cache::remember("permission_dependencies_{$this->id}", 1800, function () {
        return $this->resolveDependencyChain();
    });
}

// 快取循環依賴檢查結果
public function hasCircularDependency(array $dependencyIds): bool
{
    $cacheKey = "circular_check_{$this->id}_" . md5(implode(',', $dependencyIds));
    return Cache::remember($cacheKey, 300, function () use ($dependencyIds) {
        return $this->checkCircularDependency($dependencyIds);
    });
}
```