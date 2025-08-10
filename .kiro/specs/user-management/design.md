# 使用者管理功能設計文件

## 概述

本設計文件詳細說明使用者管理功能的技術架構、元件設計、資料流程和使用者介面設計。此功能將使用 Livewire 3.0 技術棧，遵循 Laravel 最佳實踐和既有的系統架構模式。

## 架構設計

### 整體架構

```
┌─────────────────┐    ┌──────────────────┐    ┌─────────────────┐
│   Blade View    │◄──►│  Livewire 3.0    │◄──►│   Repository    │
│  (Presentation) │    │   Component      │    │    Pattern      │
└─────────────────┘    └──────────────────┘    └─────────────────┘
                                │                        │
                                ▼                        ▼
                       ┌──────────────────┐    ┌─────────────────┐
                       │   Permission     │    │   Eloquent      │
                       │   Middleware     │    │    Models       │
                       └──────────────────┘    └─────────────────┘
```

### 技術棧

- **前端框架**: Livewire 3.0
- **CSS 框架**: Tailwind CSS
- **圖示系統**: Heroicons
- **資料庫**: MySQL 8.0
- **快取系統**: Redis
- **權限系統**: 自定義 RBAC

## 元件設計

### 主要 Livewire 元件

#### 1. UserList 元件

**檔案位置**: `app/Livewire/Admin/Users/UserList.php`
**視圖檔案**: `resources/views/livewire/admin/users/user-list.blade.php`

**屬性設計**:
```php
class UserList extends Component
{
    // 搜尋相關
    public string $search = '';
    
    // 篩選相關
    public string $statusFilter = 'all';
    public string $roleFilter = 'all';
    
    // 分頁相關
    public int $perPage = 15;
    
    // 排序相關
    public string $sortField = 'created_at';
    public string $sortDirection = 'desc';
    
    // 批量操作
    public array $selectedUsers = [];
    public bool $selectAll = false;
}
```

**計算屬性**:
```php
// 取得篩選後的使用者資料
public function getUsersProperty(): LengthAwarePaginator

// 取得可用的角色選項
public function getAvailableRolesProperty(): Collection

// 取得統計資訊
public function getStatsProperty(): array
```

**方法設計**:
```php
// 搜尋和篩選
public function updatedSearch(): void
public function updatedStatusFilter(): void
public function updatedRoleFilter(): void

// 排序功能
public function sortBy(string $field): void

// 批量操作
public function toggleSelectAll(): void
public function toggleUserSelection(int $userId): void

// CRUD 操作
public function viewUser(int $userId): void
public function editUser(int $userId): void
public function deleteUser(int $userId): void
public function confirmDelete(int $userId): void

// 狀態切換
public function toggleUserStatus(int $userId): void

// 重置功能
public function resetFilters(): void
```

#### 2. UserDeleteModal 元件

**檔案位置**: `app/Livewire/Admin/Users/UserDeleteModal.php`

**屬性設計**:
```php
class UserDeleteModal extends Component
{
    public ?User $user = null;
    public bool $showModal = false;
    public string $confirmText = '';
}
```

### 資料存取層設計

#### UserRepository

**檔案位置**: `app/Repositories/UserRepository.php`

```php
interface UserRepositoryInterface
{
    public function getPaginatedUsers(array $filters, int $perPage): LengthAwarePaginator;
    public function searchUsers(string $search, array $filters): Builder;
    public function getUsersByRole(string $role): Collection;
    public function getUsersByStatus(bool $isActive): Collection;
    public function getUserStats(): array;
    public function softDeleteUser(int $userId): bool;
    public function restoreUser(int $userId): bool;
    public function toggleUserStatus(int $userId): bool;
}
```

**實作方法**:
```php
public function getPaginatedUsers(array $filters, int $perPage): LengthAwarePaginator
{
    $query = User::with(['roles'])
        ->when($filters['search'] ?? null, function ($query, $search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('username', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        })
        ->when($filters['status'] ?? null, function ($query, $status) {
            if ($status !== 'all') {
                $query->where('is_active', $status === 'active');
            }
        })
        ->when($filters['role'] ?? null, function ($query, $role) {
            if ($role !== 'all') {
                $query->whereHas('roles', function ($q) use ($role) {
                    $q->where('name', $role);
                });
            }
        });
    
    return $query->orderBy($filters['sort_field'] ?? 'created_at', 
                          $filters['sort_direction'] ?? 'desc')
                 ->paginate($perPage);
}
```

## 資料模型設計

### User 模型擴充

**新增方法**:
```php
// 取得使用者的主要角色
public function getPrimaryRoleAttribute(): ?Role

// 取得使用者頭像 URL
public function getAvatarUrlAttribute(): string

// 取得格式化的建立時間
public function getFormattedCreatedAtAttribute(): string

// 檢查是否可以被刪除
public function canBeDeleted(): bool

// 軟刪除相關
public function softDelete(): bool
public function restore(): bool
```

### 資料庫索引優化

```sql
-- 搜尋優化索引
CREATE INDEX idx_users_search ON users(name, username, email);

-- 狀態篩選索引
CREATE INDEX idx_users_status ON users(is_active);

-- 建立時間排序索引
CREATE INDEX idx_users_created_at ON users(created_at);

-- 複合索引用於常見查詢
CREATE INDEX idx_users_status_created ON users(is_active, created_at);
```

## 使用者介面設計

### 頁面佈局

```
┌─────────────────────────────────────────────────────────────┐
│                        頁面標題                              │
│  使用者管理                                    [+ 新增使用者] │
├─────────────────────────────────────────────────────────────┤
│  [搜尋框]  [狀態篩選]  [角色篩選]  [重置]     [匯出] [批量操作] │
├─────────────────────────────────────────────────────────────┤
│  ☐ 頭像  姓名    使用者名稱  電子郵件  角色  狀態  建立時間  操作 │
│  ☐ [頭像] 張三   zhangsan   z@ex.com  管理員 啟用  2024-01-01 [⋯] │
│  ☐ [頭像] 李四   lisi       l@ex.com  使用者 停用  2024-01-02 [⋯] │
│  ...                                                        │
├─────────────────────────────────────────────────────────────┤
│                    [← 上一頁] 1 2 3 [下一頁 →]                │
└─────────────────────────────────────────────────────────────┘
```

### 響應式設計

#### 桌面版 (≥1024px)
- 完整表格顯示所有欄位
- 每行顯示完整資訊
- 操作按鈕以下拉選單形式顯示

#### 平板版 (768px-1023px)
- 隱藏次要欄位（如建立時間）
- 調整欄位寬度
- 操作按鈕簡化

#### 手機版 (<768px)
- 卡片式佈局
- 每個使用者一張卡片
- 重要資訊優先顯示

### 元件樣式設計

#### 搜尋框
```html
<div class="relative">
    <input type="text" 
           wire:model.live.debounce.300ms="search"
           class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md leading-5 bg-white placeholder-gray-500 focus:outline-none focus:placeholder-gray-400 focus:ring-1 focus:ring-primary-500 focus:border-primary-500"
           placeholder="搜尋使用者姓名、使用者名稱或電子郵件...">
    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
        <svg class="h-5 w-5 text-gray-400">...</svg>
    </div>
</div>
```

#### 狀態標籤
```html
@if($user->is_active)
    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
        啟用
    </span>
@else
    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
        停用
    </span>
@endif
```

## 錯誤處理設計

### 錯誤類型和處理策略

1. **權限錯誤**
   - 檢查點：元件載入時、操作執行前
   - 處理方式：顯示權限不足訊息，隱藏相關按鈕

2. **資料驗證錯誤**
   - 檢查點：表單提交時
   - 處理方式：顯示具體錯誤訊息，標記錯誤欄位

3. **網路錯誤**
   - 檢查點：AJAX 請求失敗時
   - 處理方式：顯示重試按鈕，保存使用者輸入

4. **資料庫錯誤**
   - 檢查點：資料操作時
   - 處理方式：記錄錯誤日誌，顯示通用錯誤訊息

### 錯誤訊息設計

```php
// 錯誤訊息常數
const ERROR_MESSAGES = [
    'permission_denied' => '您沒有權限執行此操作',
    'user_not_found' => '找不到指定的使用者',
    'delete_failed' => '刪除使用者失敗，請稍後再試',
    'network_error' => '網路連線異常，請檢查網路設定',
    'validation_failed' => '資料驗證失敗，請檢查輸入內容',
];
```

## 效能優化設計

### 查詢優化

1. **預載入關聯資料**
   ```php
   User::with(['roles:id,name', 'profile:user_id,avatar'])
   ```

2. **分頁優化**
   ```php
   // 使用 simplePaginate 減少查詢
   $users->simplePaginate($perPage);
   ```

3. **搜尋優化**
   ```php
   // 使用全文搜尋索引
   User::whereRaw('MATCH(name, username, email) AGAINST(? IN BOOLEAN MODE)', [$search])
   ```

### 快取策略

1. **角色列表快取**
   ```php
   Cache::remember('user_roles_list', 3600, function () {
       return Role::select('id', 'name', 'display_name')->get();
   });
   ```

2. **使用者統計快取**
   ```php
   Cache::remember('user_stats', 1800, function () {
       return [
           'total' => User::count(),
           'active' => User::where('is_active', true)->count(),
           'inactive' => User::where('is_active', false)->count(),
       ];
   });
   ```

### 前端優化

1. **延遲載入**
   ```blade
   <livewire:admin.users.user-list lazy />
   ```

2. **防抖搜尋**
   ```blade
   wire:model.live.debounce.300ms="search"
   ```

## 安全性設計

### 權限檢查

1. **元件層級權限**
   ```php
   public function mount()
   {
       $this->authorize('viewAny', User::class);
   }
   ```

2. **操作層級權限**
   ```php
   public function deleteUser(int $userId)
   {
       $this->authorize('delete', User::find($userId));
   }
   ```

### 資料驗證

1. **輸入驗證**
   ```php
   protected function rules(): array
   {
       return [
           'search' => 'nullable|string|max:255',
           'statusFilter' => 'in:all,active,inactive',
           'roleFilter' => 'exists:roles,name',
       ];
   }
   ```

2. **SQL 注入防護**
   - 使用 Eloquent ORM
   - 參數化查詢
   - 輸入清理

### 審計日誌

```php
// 記錄使用者管理操作
ActivityLogger::log([
    'action' => 'user.deleted',
    'user_id' => $userId,
    'admin_id' => auth()->id(),
    'ip_address' => request()->ip(),
    'user_agent' => request()->userAgent(),
]);
```

## 測試策略

### 單元測試

1. **Repository 測試**
   - 資料查詢邏輯
   - 篩選功能
   - 分頁功能

2. **Model 測試**
   - 關聯關係
   - 存取器和修改器
   - 驗證規則

### 功能測試

1. **Livewire 元件測試**
   ```php
   public function test_can_search_users()
   {
       Livewire::test(UserList::class)
           ->set('search', 'john')
           ->assertSee('john@example.com');
   }
   ```

2. **權限測試**
   ```php
   public function test_unauthorized_user_cannot_access_user_list()
   {
       $this->actingAs($userWithoutPermission)
           ->get(route('admin.users.index'))
           ->assertStatus(403);
   }
   ```

### 瀏覽器測試

1. **使用 Laravel Dusk**
   - 完整的使用者互動流程
   - JavaScript 功能測試
   - 響應式設計測試

這個設計文件涵蓋了使用者管理功能的所有技術細節，為後續的實作提供了完整的指導。