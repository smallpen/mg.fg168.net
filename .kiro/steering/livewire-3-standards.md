# Livewire 3.0 開發規範

## 命名空間和目錄結構

### 必須遵循的規範
- **命名空間**: 使用 `App\Livewire` 而非 `App\Http\Livewire`
- **目錄結構**: 檔案放在 `app/Livewire/` 目錄下
- **視圖檔案**: 放在 `resources/views/livewire/` 目錄下

### 元件命名規範
```php
// 正確的命名空間宣告
namespace App\Livewire\Admin\Dashboard;

// 正確的類別名稱 (PascalCase)
class QuickActions extends Component
```

### 目錄結構範例
```
app/Livewire/
├── Admin/
│   ├── Auth/
│   │   ├── LoginForm.php
│   │   └── LogoutButton.php
│   ├── Dashboard/
│   │   ├── DashboardStats.php
│   │   ├── QuickActions.php
│   │   └── RecentActivity.php
│   └── Users/
│       ├── UserList.php
│       └── UserForm.php
```

## Livewire 3.0 語法規範

### 元件引用語法
```blade
<!-- 使用新的標籤語法 (推薦) -->
<livewire:admin.dashboard.quick-actions />

<!-- 或使用 @livewire 指令 -->
@livewire('admin.dashboard.quick-actions')
```

### 屬性傳遞
```blade
<!-- 傳遞屬性 -->
<livewire:admin.users.user-form :user="$user" />
```

### 事件處理
```php
// 使用新的 dispatch 方法
$this->dispatch('user-updated', userId: $user->id);

// 監聽事件
#[On('user-updated')]
public function handleUserUpdate($userId) { }
```

## 元件基礎結構

### 標準元件模板
```php
<?php

namespace App\Livewire\Admin\[Module];

use Livewire\Component;

class [ComponentName] extends Component
{
    // 公開屬性
    public $property = '';
    
    // 計算屬性 (使用 Property 後綴)
    public function getDataProperty()
    {
        return collect([]);
    }
    
    // 動作方法
    public function handleAction()
    {
        // 處理邏輯
    }
    
    // 渲染方法
    public function render()
    {
        return view('livewire.admin.[module].[component-name]');
    }
}
```

### 視圖檔案命名
- 檔案名使用 kebab-case: `quick-actions.blade.php`
- 對應元件類別: `QuickActions.php`

## 配置檢查清單

### 必須確認的配置
1. `config/livewire.php` 中的 `class_namespace` 設為 `App\\Livewire`
2. `config/livewire.php` 中的 `view_path` 設為 `resource_path('views/livewire')`
3. 所有元件都繼承自 `Livewire\Component`

### 開發時的檢查點
- [ ] 命名空間是否正確
- [ ] 檔案位置是否符合規範
- [ ] 視圖檔案名稱是否使用 kebab-case
- [ ] 是否使用了正確的 Livewire 3.0 語法

## 常見錯誤避免

### 不要使用的舊語法
```php
// 舊的事件觸發方式
$this->emit('event-name'); // ❌

// 舊的事件監聽方式
protected $listeners = ['event-name' => 'method']; // ❌
```

### 應該使用的新語法
```php
// 新的事件觸發方式
$this->dispatch('event-name'); // ✅

// 新的事件監聽方式
#[On('event-name')]
public function method() { } // ✅
```

## 效能最佳實踐

### 計算屬性快取
```php
// 使用計算屬性而非方法
public function getExpensiveDataProperty()
{
    return cache()->remember('expensive-data', 3600, function () {
        return $this->performExpensiveOperation();
    });
}
```

### 延遲載入
```blade
<!-- 使用 lazy 載入 -->
<livewire:admin.dashboard.stats-chart lazy />
```

這些規範將確保所有 Livewire 元件都遵循 3.0 的最佳實踐。