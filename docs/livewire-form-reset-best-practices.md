# Livewire 表單重置功能最佳實踐指南

## 概述

本指南基於 ProfileForm 成功修復經驗，提供 Livewire 3.0 表單重置功能的標準化開發方法。遵循這些最佳實踐可以避免常見的 DOM 同步問題，確保表單重置功能的穩定性和一致性。

## wire:model 指令使用指南

### 1. wire:model.defer vs wire:model.lazy vs wire:model.live

#### wire:model.defer（推薦用於表單重置場景）
```blade
<!-- ✅ 推薦：用於需要重置功能的表單欄位 -->
<input type="text" wire:model.defer="username" wire:key="username-field">
<input type="email" wire:model.defer="email" wire:key="email-field">
<select wire:model.defer="roleId" wire:key="role-select">
    <option value="">請選擇角色</option>
    @foreach($roles as $role)
        <option value="{{ $role->id }}">{{ $role->display_name }}</option>
    @endforeach
</select>
```

**使用時機：**
- 表單提交場景
- 需要重置功能的表單
- 批次資料更新
- 避免頻繁的伺服器請求

**優點：**
- 減少伺服器請求次數
- 更好的效能表現
- 與重置機制相容性最佳
- 避免即時驗證衝突

#### wire:model.lazy（謹慎使用）
```blade
<!-- ⚠️ 謹慎使用：可能導致重置同步問題 -->
<input type="text" wire:model.lazy="search" wire:key="search-field">
```

**使用時機：**
- 搜尋欄位（但建議改用 defer）
- 不需要重置功能的獨立欄位
- 簡單的資料綁定場景

**注意事項：**
- 可能與重置機制產生衝突
- 需要額外的同步處理
- 建議逐步遷移到 defer

#### wire:model.live（避免用於重置場景）
```blade
<!-- ❌ 避免：不適合需要重置的表單 -->
<input type="text" wire:model.live="instantSearch">
```

**使用時機：**
- 即時搜尋功能
- 即時驗證回饋
- 動態內容更新

**避免使用的場景：**
- 需要重置功能的表單
- 複雜的表單結構
- 效能敏感的頁面

### 2. 使用時機決策樹

```
需要表單重置功能？
├── 是 → 使用 wire:model.defer
└── 否 → 需要即時回饋？
    ├── 是 → 使用 wire:model.live
    └── 否 → 使用 wire:model.defer（預設選擇）
```

## DOM 結構和 wire:key 使用規範

### 1. wire:key 屬性的重要性

```blade
<!-- ✅ 正確：每個動態元素都有唯一的 wire:key -->
<div wire:key="user-form-container">
    <input type="text" wire:model.defer="username" wire:key="username-input">
    <input type="email" wire:model.defer="email" wire:key="email-input">
    
    @foreach($roles as $role)
        <label wire:key="role-{{ $role->id }}-label">
            <input type="checkbox" 
                   wire:model.defer="selectedRoles" 
                   value="{{ $role->id }}"
                   wire:key="role-{{ $role->id }}-checkbox">
            {{ $role->display_name }}
        </label>
    @endforeach
</div>
```

### 2. wire:key 命名規範

```blade
<!-- 表單欄位 -->
<input wire:key="field-name-input">

<!-- 動態列表項目 -->
<div wire:key="item-{{ $item->id }}-container">

<!-- 條件渲染元素 -->
<div wire:key="conditional-{{ $condition }}-block">

<!-- 巢狀元件 -->
<livewire:component-name wire:key="component-{{ $id }}" />
```

### 3. DOM 結構最佳實踐

```blade
<!-- ✅ 推薦的表單結構 -->
<form wire:submit.prevent="submitForm" wire:key="main-form">
    <div class="space-y-6" wire:key="form-fields-container">
        <!-- 基本資訊區塊 -->
        <div class="bg-white rounded-lg p-6" wire:key="basic-info-section">
            <h3 wire:key="basic-info-title">基本資訊</h3>
            
            <div class="grid grid-cols-2 gap-4" wire:key="basic-fields-grid">
                <div wire:key="username-field-wrapper">
                    <label wire:key="username-label">使用者名稱</label>
                    <input type="text" 
                           wire:model.defer="username" 
                           wire:key="username-input"
                           class="form-input">
                </div>
                
                <div wire:key="email-field-wrapper">
                    <label wire:key="email-label">電子郵件</label>
                    <input type="email" 
                           wire:model.defer="email" 
                           wire:key="email-input"
                           class="form-input">
                </div>
            </div>
        </div>
        
        <!-- 角色選擇區塊 -->
        <div class="bg-white rounded-lg p-6" wire:key="roles-section">
            <h3 wire:key="roles-title">角色權限</h3>
            
            <div class="space-y-2" wire:key="roles-list">
                @foreach($availableRoles as $role)
                    <label class="flex items-center" wire:key="role-{{ $role->id }}-wrapper">
                        <input type="checkbox" 
                               wire:model.defer="selectedRoles" 
                               value="{{ $role->id }}"
                               wire:key="role-{{ $role->id }}-checkbox"
                               class="form-checkbox">
                        <span wire:key="role-{{ $role->id }}-label">{{ $role->display_name }}</span>
                    </label>
                @endforeach
            </div>
        </div>
    </div>
    
    <!-- 操作按鈕 -->
    <div class="flex justify-end space-x-3 mt-6" wire:key="form-actions">
        <button type="button" 
                wire:click="resetForm" 
                wire:key="reset-button"
                class="btn-secondary">
            重置
        </button>
        <button type="submit" 
                wire:key="submit-button"
                class="btn-primary">
            儲存
        </button>
    </div>
</form>
```

## 刷新機制和事件處理標準模式

### 1. 標準重置方法實作

```php
<?php

namespace App\Livewire\Admin\Users;

use Livewire\Component;

class UserForm extends Component
{
    // 表單屬性
    public $username = '';
    public $email = '';
    public $selectedRoles = [];
    
    // 狀態屬性
    public $showForm = false;
    public $editingUser = null;
    
    /**
     * 標準重置方法
     */
    public function resetForm()
    {
        // 1. 重置所有表單屬性
        $this->reset([
            'username',
            'email',
            'selectedRoles'
        ]);
        
        // 2. 重置狀態屬性
        $this->showForm = false;
        $this->editingUser = null;
        
        // 3. 清除驗證錯誤
        $this->resetValidation();
        
        // 4. 強制重新渲染元件（關鍵步驟）
        $this->dispatch('$refresh');
        
        // 5. 發送自定義事件通知前端
        $this->dispatch('user-form-reset');
        
        // 6. 記錄操作日誌（可選）
        logger('User form reset by user: ' . auth()->id());
    }
    
    /**
     * 開啟編輯表單
     */
    public function editUser($userId)
    {
        $user = User::findOrFail($userId);
        
        // 載入資料到表單
        $this->username = $user->username;
        $this->email = $user->email;
        $this->selectedRoles = $user->roles->pluck('id')->toArray();
        
        $this->editingUser = $user;
        $this->showForm = true;
        
        // 強制刷新確保資料同步
        $this->dispatch('$refresh');
    }
    
    /**
     * 關閉表單（包含重置）
     */
    public function closeForm()
    {
        $this->resetForm();
    }
}
```

### 2. 前端 JavaScript 事件處理

```blade
<script>
document.addEventListener('livewire:init', () => {
    // 監聽表單重置事件
    Livewire.on('user-form-reset', () => {
        console.log('🔄 收到表單重置事件，執行前端同步...');
        
        // 重置表單元素狀態
        const form = document.querySelector('[wire\\:key="main-form"]');
        if (form) {
            // 清除所有輸入欄位
            form.querySelectorAll('input, select, textarea').forEach(element => {
                if (element.type === 'checkbox' || element.type === 'radio') {
                    element.checked = false;
                } else {
                    element.value = '';
                }
            });
            
            // 移除驗證錯誤樣式
            form.querySelectorAll('.error, .is-invalid').forEach(element => {
                element.classList.remove('error', 'is-invalid');
            });
        }
        
        // 可選：顯示重置成功訊息
        showNotification('表單已重置', 'success');
    });
    
    // 監聽 Livewire 更新事件
    Livewire.hook('morph.updated', ({ el, component }) => {
        console.log('🔄 Livewire 元件已更新:', component.name);
        
        // 重新初始化需要的 JavaScript 功能
        initializeFormValidation();
        initializeTooltips();
    });
});

/**
 * 顯示通知訊息
 */
function showNotification(message, type = 'info') {
    // 實作通知顯示邏輯
    console.log(`${type.toUpperCase()}: ${message}`);
}

/**
 * 初始化表單驗證
 */
function initializeFormValidation() {
    // 重新綁定驗證事件
}

/**
 * 初始化工具提示
 */
function initializeTooltips() {
    // 重新初始化 tooltip
}
</script>
```

### 3. 進階刷新模式

#### 條件式刷新
```php
public function resetForm($forceRefresh = true)
{
    $this->reset(['username', 'email', 'selectedRoles']);
    $this->resetValidation();
    
    // 只在需要時強制刷新
    if ($forceRefresh) {
        $this->dispatch('$refresh');
    }
    
    $this->dispatch('user-form-reset', ['forced' => $forceRefresh]);
}
```

#### 延遲刷新
```php
public function resetForm()
{
    $this->reset(['username', 'email', 'selectedRoles']);
    $this->resetValidation();
    
    // 延遲刷新避免衝突
    $this->dispatch('delayed-refresh');
}
```

```blade
<script>
Livewire.on('delayed-refresh', () => {
    setTimeout(() => {
        Livewire.find('{{ $this->getId() }}').call('$refresh');
    }, 100);
});
</script>
```

#### 選擇性刷新
```php
public function resetForm($sections = ['all'])
{
    if (in_array('all', $sections) || in_array('basic', $sections)) {
        $this->reset(['username', 'email']);
    }
    
    if (in_array('all', $sections) || in_array('roles', $sections)) {
        $this->reset(['selectedRoles']);
    }
    
    $this->resetValidation();
    $this->dispatch('$refresh');
    $this->dispatch('form-section-reset', ['sections' => $sections]);
}
```

## 錯誤處理和驗證

### 1. 重置時的驗證處理

```php
public function resetForm()
{
    try {
        // 重置前驗證當前狀態（可選）
        if ($this->hasUnsavedChanges()) {
            $this->dispatch('confirm-reset', [
                'message' => '有未儲存的變更，確定要重置嗎？'
            ]);
            return;
        }
        
        // 執行重置
        $this->performReset();
        
        // 重置成功回饋
        $this->dispatch('reset-success', [
            'message' => '表單已成功重置'
        ]);
        
    } catch (\Exception $e) {
        // 重置失敗處理
        logger('Form reset failed: ' . $e->getMessage());
        
        $this->dispatch('reset-error', [
            'message' => '重置失敗，請重新整理頁面後再試'
        ]);
    }
}

private function hasUnsavedChanges()
{
    return !empty($this->username) || 
           !empty($this->email) || 
           !empty($this->selectedRoles);
}

private function performReset()
{
    $this->reset(['username', 'email', 'selectedRoles']);
    $this->resetValidation();
    $this->dispatch('$refresh');
    $this->dispatch('user-form-reset');
}
```

### 2. 前端確認對話框

```blade
<script>
Livewire.on('confirm-reset', (data) => {
    if (confirm(data.message)) {
        @this.call('performReset');
    }
});

Livewire.on('reset-success', (data) => {
    showNotification(data.message, 'success');
});

Livewire.on('reset-error', (data) => {
    showNotification(data.message, 'error');
});
</script>
```

## 效能最佳化

### 1. 避免不必要的重新渲染

```php
// ✅ 好的做法：只重置需要的屬性
public function resetSearchFilters()
{
    $this->reset(['search', 'statusFilter', 'roleFilter']);
    // 不重置分頁和其他狀態
}

// ❌ 避免：重置所有屬性
public function resetSearchFilters()
{
    $this->reset(); // 會重置所有屬性，包括不需要的
}
```

### 2. 批次操作優化

```php
public function resetMultipleForms()
{
    // 批次重置多個表單區塊
    $this->reset([
        'userForm.username',
        'userForm.email',
        'profileForm.bio',
        'settingsForm.notifications'
    ]);
    
    $this->resetValidation();
    
    // 只觸發一次刷新
    $this->dispatch('$refresh');
    $this->dispatch('multiple-forms-reset');
}
```

### 3. 記憶體管理

```php
public function resetForm()
{
    // 清理大型物件引用
    $this->editingUser = null;
    $this->uploadedFiles = [];
    
    // 重置屬性
    $this->reset(['username', 'email', 'selectedRoles']);
    $this->resetValidation();
    
    // 強制垃圾回收（在處理大量資料時）
    if (memory_get_usage() > 50 * 1024 * 1024) { // 50MB
        gc_collect_cycles();
    }
    
    $this->dispatch('$refresh');
}
```

## 測試最佳實踐

### 1. 單元測試

```php
<?php

namespace Tests\Unit\Livewire;

use Tests\TestCase;
use App\Livewire\Admin\Users\UserForm;
use Livewire\Livewire;

class UserFormTest extends TestCase
{
    /** @test */
    public function it_can_reset_form_properly()
    {
        $component = Livewire::test(UserForm::class)
            ->set('username', 'testuser')
            ->set('email', 'test@example.com')
            ->set('selectedRoles', [1, 2]);
        
        $component->call('resetForm');
        
        $component
            ->assertSet('username', '')
            ->assertSet('email', '')
            ->assertSet('selectedRoles', [])
            ->assertHasNoErrors();
    }
    
    /** @test */
    public function it_dispatches_events_on_reset()
    {
        Livewire::test(UserForm::class)
            ->set('username', 'testuser')
            ->call('resetForm')
            ->assertDispatched('$refresh')
            ->assertDispatched('user-form-reset');
    }
}
```

### 2. 瀏覽器測試

```php
<?php

namespace Tests\Browser;

use Tests\DuskTestCase;
use Laravel\Dusk\Browser;

class UserFormResetTest extends DuskTestCase
{
    /** @test */
    public function user_can_reset_form_successfully()
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/admin/users/create')
                ->type('username', 'testuser')
                ->type('email', 'test@example.com')
                ->check('roles[]', 1)
                ->click('@reset-button')
                ->waitFor('.notification')
                ->assertValue('username', '')
                ->assertValue('email', '')
                ->assertNotChecked('roles[]', 1);
        });
    }
}
```

## 常見陷阱和解決方案

### 1. 避免的常見錯誤

```php
// ❌ 錯誤：忘記觸發刷新
public function resetForm()
{
    $this->reset(['username', 'email']);
    // 缺少 $this->dispatch('$refresh');
}

// ❌ 錯誤：沒有清除驗證錯誤
public function resetForm()
{
    $this->reset(['username', 'email']);
    $this->dispatch('$refresh');
    // 缺少 $this->resetValidation();
}

// ❌ 錯誤：重置順序不當
public function resetForm()
{
    $this->dispatch('$refresh'); // 太早觸發
    $this->reset(['username', 'email']);
}
```

### 2. 正確的實作模式

```php
// ✅ 正確：完整的重置流程
public function resetForm()
{
    // 1. 重置資料
    $this->reset(['username', 'email', 'selectedRoles']);
    
    // 2. 清除驗證
    $this->resetValidation();
    
    // 3. 重置狀態
    $this->showForm = false;
    $this->editingUser = null;
    
    // 4. 觸發刷新
    $this->dispatch('$refresh');
    
    // 5. 發送事件
    $this->dispatch('user-form-reset');
}
```

這個最佳實踐指南提供了完整的 Livewire 表單重置功能開發標準，遵循這些規範可以確保功能的穩定性和一致性。