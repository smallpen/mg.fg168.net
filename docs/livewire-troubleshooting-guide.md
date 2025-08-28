# Livewire 表單重置故障排除指南

## 概述

本指南提供 Livewire 表單重置功能常見問題的診斷和解決方法，幫助開發人員快速定位和修復 DOM 同步問題。

## 常見 DOM 同步問題和解決方法

### 🚨 問題 1：表單重置後前端沒有更新

#### 症狀
- 呼叫 `resetForm()` 方法後，後端資料已重置
- 前端表單欄位仍顯示舊值
- 沒有錯誤訊息

#### 原因分析
1. 缺少 `$this->dispatch('$refresh')` 強制刷新
2. 使用了 `wire:model.lazy` 導致同步延遲
3. 缺少 `wire:key` 屬性導致 DOM 識別錯誤

#### 解決方案

```php
// ❌ 錯誤的實作
public function resetForm()
{
    $this->reset(['username', 'email']);
    // 缺少強制刷新
}

// ✅ 正確的實作
public function resetForm()
{
    // 1. 重置資料
    $this->reset(['username', 'email', 'selectedRoles']);
    
    // 2. 清除驗證錯誤
    $this->resetValidation();
    
    // 3. 強制重新渲染（關鍵步驟）
    $this->dispatch('$refresh');
    
    // 4. 發送前端事件
    $this->dispatch('form-reset');
}
```

```blade
<!-- ❌ 錯誤的視圖 -->
<input type="text" wire:model.lazy="username">

<!-- ✅ 正確的視圖 -->
<input type="text" wire:model.defer="username" wire:key="username-input">
```

#### 驗證修復
```bash
# 使用 Playwright 測試驗證
php artisan test --filter=FormResetTest
```

### 🚨 問題 2：重置後出現 JavaScript 錯誤

#### 症狀
- 瀏覽器 console 出現 Livewire 相關錯誤
- 表單功能部分失效
- 頁面需要重新整理才能正常使用

#### 原因分析
1. JavaScript 事件監聽器衝突
2. DOM 元素重新渲染後事件綁定丟失
3. 第三方 JavaScript 庫與 Livewire 衝突

#### 解決方案

```blade
<!-- ✅ 正確的事件處理 -->
<script>
document.addEventListener('livewire:init', () => {
    // 監聽重置事件
    Livewire.on('form-reset', () => {
        console.log('🔄 表單已重置，重新初始化...');
        
        // 重新初始化第三方庫
        initializeFormPlugins();
        
        // 清除可能的錯誤狀態
        clearErrorStates();
    });
    
    // 監聽 DOM 更新事件
    Livewire.hook('morph.updated', ({ el, component }) => {
        // 重新綁定事件監聽器
        rebindEventListeners(el);
    });
});

function initializeFormPlugins() {
    // 重新初始化 select2、datepicker 等
    $('.select2').select2();
    $('.datepicker').datepicker();
}

function clearErrorStates() {
    // 清除錯誤樣式
    $('.is-invalid').removeClass('is-invalid');
    $('.error-message').remove();
}

function rebindEventListeners(element) {
    // 重新綁定自定義事件
    $(element).find('.custom-input').off('change').on('change', handleCustomInput);
}
</script>
```

#### 偵錯技巧
```javascript
// 在瀏覽器 console 中執行
// 檢查 Livewire 元件狀態
Livewire.all().forEach(component => {
    console.log('Component:', component.name, 'Data:', component.data);
});

// 檢查事件監聽器
console.log('Livewire listeners:', Livewire.listeners);
```

### 🚨 問題 3：模態對話框重置後無法再次開啟

#### 症狀
- 第一次開啟模態正常
- 重置後模態無法再次開啟
- 或開啟後顯示異常

#### 原因分析
1. 模態狀態沒有正確重置
2. CSS 類別或屬性沒有清除
3. JavaScript 模態控制器狀態錯誤

#### 解決方案

```php
// ✅ 完整的模態重置
public function resetModal()
{
    // 1. 重置表單資料
    $this->reset(['username', 'email', 'selectedRoles']);
    
    // 2. 重置模態狀態
    $this->showModal = false;
    $this->editingUser = null;
    
    // 3. 清除驗證錯誤
    $this->resetValidation();
    
    // 4. 強制刷新
    $this->dispatch('$refresh');
    
    // 5. 發送模態重置事件
    $this->dispatch('modal-reset');
}

public function openModal($userId = null)
{
    if ($userId) {
        $this->editingUser = User::find($userId);
        $this->username = $this->editingUser->username;
        $this->email = $this->editingUser->email;
    }
    
    $this->showModal = true;
    
    // 確保模態正確顯示
    $this->dispatch('modal-opened');
}
```

```blade
<!-- ✅ 正確的模態結構 -->
<div x-data="{ show: @entangle('showModal') }" 
     x-show="show" 
     wire:key="user-modal"
     class="fixed inset-0 z-50">
    
    <div class="modal-content" wire:key="modal-content">
        <form wire:submit.prevent="saveUser" wire:key="modal-form">
            <!-- 表單內容 -->
            <input type="text" 
                   wire:model.defer="username" 
                   wire:key="modal-username-input">
        </form>
        
        <div class="modal-actions" wire:key="modal-actions">
            <button wire:click="resetModal" wire:key="modal-reset-btn">
                取消
            </button>
            <button wire:click="saveUser" wire:key="modal-save-btn">
                儲存
            </button>
        </div>
    </div>
</div>

<script>
Livewire.on('modal-reset', () => {
    // 確保模態完全關閉
    document.body.classList.remove('modal-open');
    
    // 清除可能的背景遮罩
    const backdrop = document.querySelector('.modal-backdrop');
    if (backdrop) backdrop.remove();
});
</script>
```

### 🚨 問題 4：動態列表重置後順序錯亂

#### 症狀
- 重置後列表項目順序改變
- 部分項目重複顯示
- 項目資料錯位

#### 原因分析
1. 缺少唯一的 `wire:key` 識別符
2. 使用了不穩定的 key 值（如陣列索引）
3. 資料重置順序問題

#### 解決方案

```blade
<!-- ❌ 錯誤：使用陣列索引作為 key -->
@foreach($users as $index => $user)
    <tr wire:key="user-{{ $index }}">
        <td>{{ $user->name }}</td>
    </tr>
@endforeach

<!-- ✅ 正確：使用穩定的唯一識別符 -->
@foreach($users as $user)
    <tr wire:key="user-row-{{ $user->id }}">
        <td wire:key="user-name-{{ $user->id }}">{{ $user->name }}</td>
        <td wire:key="user-actions-{{ $user->id }}">
            <button wire:click="editUser({{ $user->id }})" 
                    wire:key="edit-btn-{{ $user->id }}">
                編輯
            </button>
        </td>
    </tr>
@endforeach
```

```php
// ✅ 正確的列表重置
public function resetFilters()
{
    // 1. 重置篩選條件
    $this->reset(['search', 'statusFilter', 'roleFilter']);
    
    // 2. 重置分頁
    $this->resetPage();
    
    // 3. 清除驗證錯誤
    $this->resetValidation();
    
    // 4. 重新載入資料
    $this->loadUsers();
    
    // 5. 強制刷新
    $this->dispatch('$refresh');
    
    // 6. 發送事件
    $this->dispatch('filters-reset');
}

private function loadUsers()
{
    // 確保資料載入順序一致
    $this->users = User::query()
        ->when($this->search, fn($q) => $q->where('name', 'like', "%{$this->search}%"))
        ->when($this->statusFilter, fn($q) => $q->where('is_active', $this->statusFilter))
        ->orderBy('created_at', 'desc')
        ->paginate(10);
}
```

### 🚨 問題 5：驗證錯誤沒有清除

#### 症狀
- 重置後表單仍顯示驗證錯誤訊息
- 錯誤樣式沒有移除
- 影響使用者體驗

#### 原因分析
1. 忘記呼叫 `resetValidation()`
2. 前端錯誤樣式沒有清除
3. 自定義驗證邏輯沒有重置

#### 解決方案

```php
// ✅ 完整的驗證重置
public function resetForm()
{
    // 1. 重置資料
    $this->reset(['username', 'email', 'password']);
    
    // 2. 清除所有驗證錯誤（關鍵步驟）
    $this->resetValidation();
    
    // 3. 清除自定義錯誤狀態
    $this->customErrors = [];
    $this->hasErrors = false;
    
    // 4. 強制刷新
    $this->dispatch('$refresh');
    
    // 5. 發送清除錯誤事件
    $this->dispatch('validation-cleared');
}

// 重置特定欄位的驗證錯誤
public function resetFieldValidation($field)
{
    $this->resetValidation($field);
    $this->dispatch('field-validation-cleared', ['field' => $field]);
}
```

```blade
<!-- ✅ 正確的錯誤顯示和清除 -->
<div wire:key="username-field">
    <input type="text" 
           wire:model.defer="username" 
           wire:key="username-input"
           class="@error('username') is-invalid @enderror">
    
    @error('username')
        <div class="error-message" wire:key="username-error">
            {{ $message }}
        </div>
    @enderror
</div>

<script>
Livewire.on('validation-cleared', () => {
    // 清除所有錯誤樣式
    document.querySelectorAll('.is-invalid').forEach(el => {
        el.classList.remove('is-invalid');
    });
    
    // 移除錯誤訊息
    document.querySelectorAll('.error-message').forEach(el => {
        el.remove();
    });
});

Livewire.on('field-validation-cleared', (data) => {
    // 清除特定欄位的錯誤
    const field = document.querySelector(`[wire\\:key="${data.field}-input"]`);
    if (field) {
        field.classList.remove('is-invalid');
        const errorMsg = document.querySelector(`[wire\\:key="${data.field}-error"]`);
        if (errorMsg) errorMsg.remove();
    }
});
</script>
```

## 偵錯工具和技巧指導

### 🔧 瀏覽器開發者工具使用

#### 1. 檢查 Livewire 元件狀態
```javascript
// 在 Console 中執行
// 列出所有 Livewire 元件
Livewire.all().forEach((component, index) => {
    console.log(`Component ${index}:`, {
        name: component.name,
        id: component.id,
        data: component.data,
        errors: component.errors
    });
});

// 檢查特定元件
const component = Livewire.find('component-id');
console.log('Component data:', component.data);
console.log('Component errors:', component.errors);
```

#### 2. 監控 Livewire 事件
```javascript
// 監控所有 Livewire 事件
Livewire.hook('request', ({ uri, options, payload, respond, succeed, fail }) => {
    console.log('🚀 Livewire Request:', { uri, payload });
});

Livewire.hook('response', ({ status, response }) => {
    console.log('📥 Livewire Response:', { status, response });
});

// 監控 DOM 更新
Livewire.hook('morph.updated', ({ el, component }) => {
    console.log('🔄 DOM Updated:', { element: el, component: component.name });
});
```

#### 3. 檢查 DOM 結構
```javascript
// 檢查 wire:key 屬性
document.querySelectorAll('[wire\\:key]').forEach(el => {
    console.log('Element with wire:key:', {
        key: el.getAttribute('wire:key'),
        element: el,
        parent: el.parentElement
    });
});

// 檢查 wire:model 綁定
document.querySelectorAll('[wire\\:model]').forEach(el => {
    console.log('Wire model binding:', {
        model: el.getAttribute('wire:model'),
        value: el.value,
        element: el
    });
});
```

### 🔍 後端偵錯技巧

#### 1. 日誌記錄
```php
public function resetForm()
{
    // 記錄重置前的狀態
    logger('Form reset started', [
        'component' => static::class,
        'user_id' => auth()->id(),
        'before_data' => [
            'username' => $this->username,
            'email' => $this->email,
            'selectedRoles' => $this->selectedRoles
        ]
    ]);
    
    // 執行重置
    $this->reset(['username', 'email', 'selectedRoles']);
    $this->resetValidation();
    
    // 記錄重置後的狀態
    logger('Form reset completed', [
        'component' => static::class,
        'after_data' => [
            'username' => $this->username,
            'email' => $this->email,
            'selectedRoles' => $this->selectedRoles
        ]
    ]);
    
    $this->dispatch('$refresh');
    $this->dispatch('form-reset');
}
```

#### 2. 除錯輔助方法
```php
/**
 * 偵錯輔助方法
 */
public function debugComponentState()
{
    if (app()->environment('local')) {
        dump([
            'component' => static::class,
            'properties' => $this->all(),
            'errors' => $this->getErrorBag()->toArray(),
            'validation_rules' => $this->rules() ?? [],
        ]);
    }
}

/**
 * 驗證元件狀態
 */
public function validateComponentState()
{
    $issues = [];
    
    // 檢查必要屬性
    if (empty($this->username) && empty($this->email)) {
        $issues[] = 'Both username and email are empty';
    }
    
    // 檢查資料一致性
    if ($this->editingUser && $this->editingUser->id !== $this->userId) {
        $issues[] = 'User ID mismatch';
    }
    
    if (!empty($issues)) {
        logger('Component state validation failed', [
            'component' => static::class,
            'issues' => $issues,
            'current_state' => $this->all()
        ]);
    }
    
    return empty($issues);
}
```

#### 3. 測試輔助工具
```php
// 建立測試輔助 Trait
trait LivewireTestHelpers
{
    protected function assertFormReset($component, array $fields)
    {
        foreach ($fields as $field) {
            $this->assertEquals('', $component->get($field), 
                "Field {$field} was not reset properly");
        }
        
        $this->assertEmpty($component->getErrorBag()->toArray(), 
            'Validation errors were not cleared');
    }
    
    protected function fillForm($component, array $data)
    {
        foreach ($data as $field => $value) {
            $component->set($field, $value);
        }
        
        return $component;
    }
    
    protected function assertFormFilled($component, array $expectedData)
    {
        foreach ($expectedData as $field => $expectedValue) {
            $this->assertEquals($expectedValue, $component->get($field),
                "Field {$field} does not match expected value");
        }
    }
}
```

### 📊 效能偵錯

#### 1. 監控請求次數
```javascript
let livewireRequestCount = 0;

Livewire.hook('request', () => {
    livewireRequestCount++;
    console.log(`📊 Livewire requests: ${livewireRequestCount}`);
    
    if (livewireRequestCount > 10) {
        console.warn('⚠️ Too many Livewire requests detected!');
    }
});
```

#### 2. 記憶體使用監控
```php
public function resetForm()
{
    $memoryBefore = memory_get_usage(true);
    
    // 執行重置邏輯
    $this->reset(['username', 'email', 'selectedRoles']);
    $this->resetValidation();
    
    $memoryAfter = memory_get_usage(true);
    $memoryDiff = $memoryAfter - $memoryBefore;
    
    if ($memoryDiff > 1024 * 1024) { // 1MB
        logger('High memory usage detected in form reset', [
            'component' => static::class,
            'memory_before' => $memoryBefore,
            'memory_after' => $memoryAfter,
            'memory_diff' => $memoryDiff
        ]);
    }
    
    $this->dispatch('$refresh');
}
```

## 問題診斷標準流程

### 🔍 第一階段：問題識別

#### 步驟 1：重現問題
1. **記錄操作步驟**
   - 詳細記錄導致問題的操作序列
   - 記錄瀏覽器類型和版本
   - 記錄網路狀況和載入時間

2. **收集錯誤資訊**
   ```javascript
   // 在瀏覽器 Console 中執行
   console.log('User Agent:', navigator.userAgent);
   console.log('Livewire version:', window.Livewire.version);
   console.log('Current errors:', Livewire.all().map(c => c.errors));
   ```

3. **檢查網路請求**
   - 開啟 Network 標籤
   - 重現問題並觀察 AJAX 請求
   - 檢查請求狀態碼和回應內容

#### 步驟 2：初步診斷
```bash
# 檢查 Laravel 日誌
tail -f storage/logs/laravel.log

# 檢查 Livewire 特定日誌
grep "Livewire" storage/logs/laravel.log | tail -20

# 檢查 PHP 錯誤日誌
tail -f /var/log/php_errors.log
```

### 🔧 第二階段：深入分析

#### 步驟 1：元件狀態分析
```php
// 在元件中添加偵錯方法
public function debugReset()
{
    if (app()->environment('local')) {
        $beforeState = $this->all();
        
        $this->resetForm();
        
        $afterState = $this->all();
        
        dump([
            'before' => $beforeState,
            'after' => $afterState,
            'diff' => array_diff_assoc($beforeState, $afterState)
        ]);
    }
}
```

#### 步驟 2：DOM 狀態檢查
```javascript
// 檢查 DOM 同步狀態
function checkDOMSync() {
    const inputs = document.querySelectorAll('[wire\\:model]');
    const syncIssues = [];
    
    inputs.forEach(input => {
        const model = input.getAttribute('wire:model');
        const wireKey = input.getAttribute('wire:key');
        
        if (!wireKey) {
            syncIssues.push({
                element: input,
                issue: 'Missing wire:key',
                model: model
            });
        }
        
        if (model.includes('.lazy')) {
            syncIssues.push({
                element: input,
                issue: 'Using wire:model.lazy',
                model: model
            });
        }
    });
    
    if (syncIssues.length > 0) {
        console.warn('DOM sync issues found:', syncIssues);
    }
    
    return syncIssues;
}

// 執行檢查
checkDOMSync();
```

#### 步驟 3：事件流程追蹤
```javascript
// 追蹤事件流程
const eventLog = [];

Livewire.hook('request', (payload) => {
    eventLog.push({
        type: 'request',
        timestamp: Date.now(),
        payload: payload
    });
});

Livewire.hook('response', (response) => {
    eventLog.push({
        type: 'response',
        timestamp: Date.now(),
        response: response
    });
});

// 查看事件日誌
console.log('Event log:', eventLog);
```

### 🛠️ 第三階段：問題修復

#### 修復檢查清單
- [ ] 確認 `wire:model.defer` 使用正確
- [ ] 檢查所有動態元素都有 `wire:key`
- [ ] 驗證重置方法包含 `$this->dispatch('$refresh')`
- [ ] 確認 `resetValidation()` 被呼叫
- [ ] 檢查前端事件監聽器正確設定
- [ ] 驗證 JavaScript 沒有衝突
- [ ] 測試修復在不同瀏覽器中的效果

#### 修復驗證腳本
```bash
#!/bin/bash
# fix-verification.sh

echo "🔍 驗證 Livewire 表單重置修復..."

# 執行單元測試
echo "執行單元測試..."
php artisan test --filter=FormReset

# 執行瀏覽器測試
echo "執行瀏覽器測試..."
php artisan dusk --filter=FormReset

# 檢查程式碼品質
echo "檢查程式碼品質..."
./vendor/bin/phpcs app/Livewire/

# 執行靜態分析
echo "執行靜態分析..."
./vendor/bin/phpstan analyse app/Livewire/

echo "✅ 修復驗證完成"
```

### 📋 問題記錄模板

```markdown
## 問題報告

### 基本資訊
- **日期**: 2024-XX-XX
- **報告人**: [姓名]
- **元件**: [元件名稱]
- **優先級**: [高/中/低]

### 問題描述
[詳細描述問題現象]

### 重現步驟
1. [步驟1]
2. [步驟2]
3. [步驟3]

### 預期行為
[描述預期的正確行為]

### 實際行為
[描述實際發生的錯誤行為]

### 環境資訊
- **瀏覽器**: [Chrome/Firefox/Safari] [版本]
- **作業系統**: [Windows/macOS/Linux]
- **Laravel 版本**: [版本號]
- **Livewire 版本**: [版本號]

### 錯誤訊息
```
[貼上錯誤訊息]
```

### 診斷結果
[記錄診斷過程和發現]

### 解決方案
[記錄採用的解決方案]

### 測試結果
[記錄修復後的測試結果]

### 預防措施
[記錄避免類似問題的預防措施]
```

這個故障排除指南提供了系統性的問題診斷和解決方法，幫助開發人員快速定位和修復 Livewire 表單重置相關問題。