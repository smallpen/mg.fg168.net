# Livewire 元件標準模板和範例

## 概述

本文件提供標準化的 Livewire 元件模板和實際範例，確保所有新開發的元件都遵循最佳實踐，特別是在表單重置功能的實作上。

## 基礎元件模板

### 1. 標準表單元件模板

```php
<?php

namespace App\Livewire\Admin\[Module];

use Livewire\Component;
use Livewire\Attributes\On;
use Livewire\Attributes\Rule;
use Illuminate\Support\Facades\DB;

/**
 * [ComponentName] - [元件功能描述]
 * 
 * 此元件實作了標準的表單重置功能，遵循 Livewire 最佳實踐
 */
class [ComponentName] extends Component
{
    // ==================== 表單屬性 ====================
    
    #[Rule('required|string|max:255')]
    public string $fieldName = '';
    
    #[Rule('required|email|max:255')]
    public string $email = '';
    
    #[Rule('array')]
    public array $selectedItems = [];
    
    // ==================== 狀態屬性 ====================
    
    public bool $showModal = false;
    public bool $isEditing = false;
    public ?object $editingItem = null;
    
    // ==================== 計算屬性 ====================
    
    public function getItemsProperty()
    {
        return collect($this->selectedItems);
    }
    
    public function getHasDataProperty(): bool
    {
        return !empty($this->fieldName) || !empty($this->email);
    }
    
    // ==================== 生命週期方法 ====================
    
    public function mount($itemId = null)
    {
        if ($itemId) {
            $this->loadItem($itemId);
        }
    }
    
    // ==================== 表單操作方法 ====================
    
    /**
     * 標準表單重置方法
     * 遵循最佳實踐的重置流程
     */
    public function resetForm(): void
    {
        try {
            // 1. 重置表單資料
            $this->reset([
                'fieldName',
                'email',
                'selectedItems'
            ]);
            
            // 2. 重置狀態屬性
            $this->showModal = false;
            $this->isEditing = false;
            $this->editingItem = null;
            
            // 3. 清除驗證錯誤
            $this->resetValidation();
            
            // 4. 強制重新渲染元件（關鍵步驟）
            $this->dispatch('$refresh');
            
            // 5. 發送前端事件
            $this->dispatch('component-name-reset');
            
            // 6. 記錄操作（可選）
            logger('Form reset completed', [
                'component' => static::class,
                'user_id' => auth()->id()
            ]);
            
        } catch (\Exception $e) {
            logger('Form reset failed', [
                'component' => static::class,
                'error' => $e->getMessage()
            ]);
            
            $this->dispatch('reset-error', [
                'message' => '重置失敗，請重新整理頁面後再試'
            ]);
        }
    }
    
    /**
     * 開啟編輯模式
     */
    public function editItem($itemId): void
    {
        $item = $this->findItem($itemId);
        
        if (!$item) {
            $this->dispatch('item-not-found');
            return;
        }
        
        // 載入資料到表單
        $this->fieldName = $item->field_name;
        $this->email = $item->email;
        $this->selectedItems = $item->selected_items ?? [];
        
        // 設定編輯狀態
        $this->editingItem = $item;
        $this->isEditing = true;
        $this->showModal = true;
        
        // 強制刷新確保資料同步
        $this->dispatch('$refresh');
        $this->dispatch('edit-mode-activated', ['itemId' => $itemId]);
    }
    
    /**
     * 儲存表單資料
     */
    public function saveForm(): void
    {
        // 驗證資料
        $this->validate();
        
        try {
            DB::transaction(function () {
                if ($this->isEditing && $this->editingItem) {
                    $this->updateItem();
                } else {
                    $this->createItem();
                }
            });
            
            // 成功後重置表單
            $this->resetForm();
            
            // 發送成功事件
            $this->dispatch('save-success', [
                'message' => $this->isEditing ? '更新成功' : '建立成功'
            ]);
            
        } catch (\Exception $e) {
            logger('Save form failed', [
                'component' => static::class,
                'error' => $e->getMessage(),
                'data' => $this->all()
            ]);
            
            $this->dispatch('save-error', [
                'message' => '儲存失敗，請稍後再試'
            ]);
        }
    }
    
    /**
     * 關閉模態（包含重置）
     */
    public function closeModal(): void
    {
        $this->resetForm();
    }
    
    // ==================== 輔助方法 ====================
    
    private function loadItem($itemId): void
    {
        // 實作項目載入邏輯
    }
    
    private function findItem($itemId): ?object
    {
        // 實作項目查找邏輯
        return null;
    }
    
    private function createItem(): void
    {
        // 實作項目建立邏輯
    }
    
    private function updateItem(): void
    {
        // 實作項目更新邏輯
    }
    
    // ==================== 事件監聽器 ====================
    
    #[On('external-data-updated')]
    public function handleExternalUpdate($data): void
    {
        // 處理外部資料更新
        $this->dispatch('$refresh');
    }
    
    // ==================== 渲染方法 ====================
    
    public function render()
    {
        return view('livewire.admin.[module].[component-name]');
    }
}
```

### 2. 標準視圖模板

```blade
{{-- resources/views/livewire/admin/[module]/[component-name].blade.php --}}

<div class="space-y-6" wire:key="component-container">
    
    {{-- 控制項區域 --}}
    <div class="flex justify-end" wire:key="controls">
        <div class="flex items-center space-x-3">
            <button wire:click="resetForm" 
                    wire:key="reset-button"
                    class="btn-secondary">
                <svg class="w-4 h-4 mr-2" wire:key="reset-icon">
                    <!-- 重置圖示 -->
                </svg>
                重置
            </button>
            
            <button wire:click="$set('showModal', true)" 
                    wire:key="create-button"
                    class="btn-primary">
                <svg class="w-4 h-4 mr-2" wire:key="create-icon">
                    <!-- 新增圖示 -->
                </svg>
                新增
            </button>
        </div>
    </div>
    
    {{-- 主要內容區域 --}}
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow" wire:key="main-content">
        {{-- 內容實作 --}}
    </div>
    
    {{-- 模態對話框 --}}
    <div x-data="{ show: @entangle('showModal') }" 
         x-show="show" 
         wire:key="modal-container"
         class="fixed inset-0 z-50"
         style="display: none;">
        
        {{-- 背景遮罩 --}}
        <div class="fixed inset-0 bg-black bg-opacity-50" 
             wire:key="modal-backdrop"
             @click="show = false"></div>
        
        {{-- 模態內容 --}}
        <div class="flex items-center justify-center min-h-screen p-4" wire:key="modal-wrapper">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-md w-full" 
                 wire:key="modal-content">
                
                {{-- 模態標題 --}}
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700" 
                     wire:key="modal-header">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white" 
                        wire:key="modal-title">
                        {{ $isEditing ? '編輯項目' : '新增項目' }}
                    </h3>
                </div>
                
                {{-- 表單內容 --}}
                <form wire:submit.prevent="saveForm" wire:key="modal-form">
                    <div class="px-6 py-4 space-y-4" wire:key="form-fields">
                        
                        {{-- 欄位名稱 --}}
                        <div wire:key="field-name-wrapper">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300" 
                                   wire:key="field-name-label">
                                欄位名稱
                            </label>
                            <input type="text" 
                                   wire:model.defer="fieldName" 
                                   wire:key="field-name-input"
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white @error('fieldName') border-red-500 @enderror">
                            @error('fieldName')
                                <p class="mt-1 text-sm text-red-600" wire:key="field-name-error">
                                    {{ $message }}
                                </p>
                            @enderror
                        </div>
                        
                        {{-- 電子郵件 --}}
                        <div wire:key="email-wrapper">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300" 
                                   wire:key="email-label">
                                電子郵件
                            </label>
                            <input type="email" 
                                   wire:model.defer="email" 
                                   wire:key="email-input"
                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white @error('email') border-red-500 @enderror">
                            @error('email')
                                <p class="mt-1 text-sm text-red-600" wire:key="email-error">
                                    {{ $message }}
                                </p>
                            @enderror
                        </div>
                        
                        {{-- 選項列表 --}}
                        <div wire:key="selected-items-wrapper">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300" 
                                   wire:key="selected-items-label">
                                選擇項目
                            </label>
                            <div class="mt-2 space-y-2" wire:key="items-list">
                                @foreach($availableItems as $item)
                                    <label class="flex items-center" wire:key="item-{{ $item->id }}-wrapper">
                                        <input type="checkbox" 
                                               wire:model.defer="selectedItems" 
                                               value="{{ $item->id }}"
                                               wire:key="item-{{ $item->id }}-checkbox"
                                               class="form-checkbox h-4 w-4 text-primary-600">
                                        <span class="ml-2 text-sm text-gray-700 dark:text-gray-300" 
                                              wire:key="item-{{ $item->id }}-label">
                                            {{ $item->name }}
                                        </span>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                        
                    </div>
                    
                    {{-- 模態操作按鈕 --}}
                    <div class="px-6 py-4 bg-gray-50 dark:bg-gray-700 flex justify-end space-x-3" 
                         wire:key="modal-actions">
                        <button type="button" 
                                wire:click="closeModal" 
                                wire:key="cancel-button"
                                class="btn-secondary">
                            取消
                        </button>
                        <button type="submit" 
                                wire:key="save-button"
                                class="btn-primary">
                            {{ $isEditing ? '更新' : '建立' }}
                        </button>
                    </div>
                </form>
                
            </div>
        </div>
    </div>
    
    {{-- JavaScript 事件處理 --}}
    <script wire:key="component-scripts">
        document.addEventListener('livewire:init', () => {
            // 監聽重置事件
            Livewire.on('component-name-reset', () => {
                console.log('🔄 元件已重置');
                
                // 清除可能的前端狀態
                clearFormErrors();
                resetCustomStates();
                
                // 顯示成功訊息
                showNotification('表單已重置', 'success');
            });
            
            // 監聽編輯模式啟動
            Livewire.on('edit-mode-activated', (data) => {
                console.log('✏️ 編輯模式已啟動:', data.itemId);
            });
            
            // 監聽儲存成功
            Livewire.on('save-success', (data) => {
                showNotification(data.message, 'success');
            });
            
            // 監聽錯誤事件
            Livewire.on('save-error', (data) => {
                showNotification(data.message, 'error');
            });
            
            Livewire.on('reset-error', (data) => {
                showNotification(data.message, 'error');
            });
            
            // 監聽 DOM 更新
            Livewire.hook('morph.updated', ({ el, component }) => {
                // 重新初始化需要的功能
                initializeTooltips(el);
                initializeFormValidation(el);
            });
        });
        
        function clearFormErrors() {
            document.querySelectorAll('.border-red-500').forEach(el => {
                el.classList.remove('border-red-500');
            });
            
            document.querySelectorAll('.text-red-600').forEach(el => {
                if (el.getAttribute('wire:key')?.includes('-error')) {
                    el.remove();
                }
            });
        }
        
        function resetCustomStates() {
            // 重置自定義前端狀態
        }
        
        function showNotification(message, type = 'info') {
            // 實作通知顯示邏輯
            console.log(`${type.toUpperCase()}: ${message}`);
        }
        
        function initializeTooltips(container = document) {
            // 重新初始化工具提示
        }
        
        function initializeFormValidation(container = document) {
            // 重新初始化表單驗證
        }
    </script>
    
</div>
```

## 實際應用範例

### 1. 使用者管理元件範例

```php
<?php

namespace App\Livewire\Admin\Users;

use App\Models\User;
use App\Models\Role;
use Livewire\Component;
use Livewire\Attributes\On;
use Livewire\Attributes\Rule;
use Illuminate\Support\Facades\Hash;

class UserForm extends Component
{
    #[Rule('required|string|max:255|unique:users,username')]
    public string $username = '';
    
    #[Rule('required|string|max:255')]
    public string $name = '';
    
    #[Rule('required|email|max:255|unique:users,email')]
    public string $email = '';
    
    #[Rule('nullable|string|min:8')]
    public string $password = '';
    
    #[Rule('array')]
    public array $selectedRoles = [];
    
    public bool $showModal = false;
    public bool $isEditing = false;
    public ?User $editingUser = null;
    
    public function getRolesProperty()
    {
        return Role::all();
    }
    
    public function resetForm(): void
    {
        $this->reset([
            'username',
            'name', 
            'email',
            'password',
            'selectedRoles'
        ]);
        
        $this->showModal = false;
        $this->isEditing = false;
        $this->editingUser = null;
        
        $this->resetValidation();
        $this->dispatch('$refresh');
        $this->dispatch('user-form-reset');
    }
    
    public function editUser($userId): void
    {
        $user = User::with('roles')->findOrFail($userId);
        
        $this->username = $user->username;
        $this->name = $user->name;
        $this->email = $user->email;
        $this->selectedRoles = $user->roles->pluck('id')->toArray();
        
        $this->editingUser = $user;
        $this->isEditing = true;
        $this->showModal = true;
        
        // 更新驗證規則以排除當前使用者
        $this->rules['username'] = "required|string|max:255|unique:users,username,{$user->id}";
        $this->rules['email'] = "required|email|max:255|unique:users,email,{$user->id}";
        
        $this->dispatch('$refresh');
    }
    
    public function saveUser(): void
    {
        $this->validate();
        
        try {
            if ($this->isEditing) {
                $this->editingUser->update([
                    'username' => $this->username,
                    'name' => $this->name,
                    'email' => $this->email,
                ]);
                
                if (!empty($this->password)) {
                    $this->editingUser->update([
                        'password' => Hash::make($this->password)
                    ]);
                }
                
                $this->editingUser->roles()->sync($this->selectedRoles);
                
                $message = '使用者更新成功';
            } else {
                $user = User::create([
                    'username' => $this->username,
                    'name' => $this->name,
                    'email' => $this->email,
                    'password' => Hash::make($this->password),
                ]);
                
                $user->roles()->sync($this->selectedRoles);
                
                $message = '使用者建立成功';
            }
            
            $this->resetForm();
            $this->dispatch('user-saved', ['message' => $message]);
            $this->dispatch('refresh-user-list');
            
        } catch (\Exception $e) {
            logger('User save failed', [
                'error' => $e->getMessage(),
                'data' => $this->all()
            ]);
            
            $this->dispatch('save-error', [
                'message' => '儲存失敗，請稍後再試'
            ]);
        }
    }
    
    public function render()
    {
        return view('livewire.admin.users.user-form');
    }
}
```

### 2. 設定管理元件範例

```php
<?php

namespace App\Livewire\Admin\Settings;

use Livewire\Component;
use Livewire\Attributes\Rule;
use Illuminate\Support\Facades\Cache;

class SystemSettings extends Component
{
    #[Rule('required|string|max:255')]
    public string $siteName = '';
    
    #[Rule('nullable|string|max:500')]
    public string $siteDescription = '';
    
    #[Rule('required|email')]
    public string $adminEmail = '';
    
    #[Rule('required|boolean')]
    public bool $maintenanceMode = false;
    
    #[Rule('required|integer|min:1|max:100')]
    public int $itemsPerPage = 10;
    
    private array $originalSettings = [];
    
    public function mount(): void
    {
        $this->loadSettings();
        $this->originalSettings = $this->getCurrentSettings();
    }
    
    public function resetForm(): void
    {
        // 重置為原始設定值
        $this->loadSettings();
        
        $this->resetValidation();
        $this->dispatch('$refresh');
        $this->dispatch('settings-reset');
    }
    
    public function resetToDefaults(): void
    {
        $this->siteName = config('app.name');
        $this->siteDescription = '';
        $this->adminEmail = config('mail.from.address');
        $this->maintenanceMode = false;
        $this->itemsPerPage = 10;
        
        $this->resetValidation();
        $this->dispatch('$refresh');
        $this->dispatch('settings-reset-to-defaults');
    }
    
    public function saveSettings(): void
    {
        $this->validate();
        
        try {
            $settings = [
                'site_name' => $this->siteName,
                'site_description' => $this->siteDescription,
                'admin_email' => $this->adminEmail,
                'maintenance_mode' => $this->maintenanceMode,
                'items_per_page' => $this->itemsPerPage,
            ];
            
            foreach ($settings as $key => $value) {
                setting([$key => $value]);
            }
            
            setting()->save();
            
            // 清除相關快取
            Cache::tags(['settings'])->flush();
            
            $this->originalSettings = $this->getCurrentSettings();
            
            $this->dispatch('settings-saved', [
                'message' => '設定儲存成功'
            ]);
            
        } catch (\Exception $e) {
            logger('Settings save failed', [
                'error' => $e->getMessage(),
                'settings' => $settings ?? []
            ]);
            
            $this->dispatch('save-error', [
                'message' => '設定儲存失敗，請稍後再試'
            ]);
        }
    }
    
    public function getHasChangesProperty(): bool
    {
        return $this->getCurrentSettings() !== $this->originalSettings;
    }
    
    private function loadSettings(): void
    {
        $this->siteName = setting('site_name', config('app.name'));
        $this->siteDescription = setting('site_description', '');
        $this->adminEmail = setting('admin_email', config('mail.from.address'));
        $this->maintenanceMode = setting('maintenance_mode', false);
        $this->itemsPerPage = setting('items_per_page', 10);
    }
    
    private function getCurrentSettings(): array
    {
        return [
            'siteName' => $this->siteName,
            'siteDescription' => $this->siteDescription,
            'adminEmail' => $this->adminEmail,
            'maintenanceMode' => $this->maintenanceMode,
            'itemsPerPage' => $this->itemsPerPage,
        ];
    }
    
    public function render()
    {
        return view('livewire.admin.settings.system-settings');
    }
}
```

### 3. 列表篩選元件範例

```php
<?php

namespace App\Livewire\Admin\Users;

use App\Models\User;
use App\Models\Role;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Url;

class UserList extends Component
{
    use WithPagination;
    
    #[Url(as: 'search')]
    public string $search = '';
    
    #[Url(as: 'status')]
    public string $statusFilter = '';
    
    #[Url(as: 'role')]
    public string $roleFilter = '';
    
    #[Url(as: 'sort')]
    public string $sortField = 'created_at';
    
    #[Url(as: 'direction')]
    public string $sortDirection = 'desc';
    
    public function resetFilters(): void
    {
        // 重置篩選條件
        $this->reset([
            'search',
            'statusFilter', 
            'roleFilter'
        ]);
        
        // 重置分頁
        $this->resetPage();
        
        // 重置排序
        $this->sortField = 'created_at';
        $this->sortDirection = 'desc';
        
        $this->dispatch('$refresh');
        $this->dispatch('filters-reset');
    }
    
    public function sortBy($field): void
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }
        
        $this->resetPage();
    }
    
    public function getUsersProperty()
    {
        return User::query()
            ->with('roles')
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('name', 'like', "%{$this->search}%")
                      ->orWhere('username', 'like', "%{$this->search}%")
                      ->orWhere('email', 'like', "%{$this->search}%");
                });
            })
            ->when($this->statusFilter !== '', function ($query) {
                $query->where('is_active', $this->statusFilter);
            })
            ->when($this->roleFilter, function ($query) {
                $query->whereHas('roles', function ($q) {
                    $q->where('roles.id', $this->roleFilter);
                });
            })
            ->orderBy($this->sortField, $this->sortDirection)
            ->paginate(10);
    }
    
    public function getRolesProperty()
    {
        return Role::orderBy('display_name')->get();
    }
    
    public function render()
    {
        return view('livewire.admin.users.user-list');
    }
}
```

## 測試模板

### 1. 單元測試模板

```php
<?php

namespace Tests\Unit\Livewire\Admin\Users;

use Tests\TestCase;
use App\Models\User;
use App\Models\Role;
use App\Livewire\Admin\Users\UserForm;
use Livewire\Livewire;
use Illuminate\Foundation\Testing\RefreshDatabase;

class UserFormTest extends TestCase
{
    use RefreshDatabase;
    
    protected function setUp(): void
    {
        parent::setUp();
        
        // 建立測試資料
        $this->actingAs(User::factory()->create());
        Role::factory()->count(3)->create();
    }
    
    /** @test */
    public function it_can_reset_form_properly()
    {
        $component = Livewire::test(UserForm::class)
            ->set('username', 'testuser')
            ->set('name', 'Test User')
            ->set('email', 'test@example.com')
            ->set('selectedRoles', [1, 2]);
        
        $component->call('resetForm');
        
        $component
            ->assertSet('username', '')
            ->assertSet('name', '')
            ->assertSet('email', '')
            ->assertSet('selectedRoles', [])
            ->assertSet('showModal', false)
            ->assertSet('isEditing', false)
            ->assertSet('editingUser', null)
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
    
    /** @test */
    public function it_can_edit_existing_user()
    {
        $user = User::factory()->create();
        $roles = Role::factory()->count(2)->create();
        $user->roles()->attach($roles->pluck('id'));
        
        $component = Livewire::test(UserForm::class)
            ->call('editUser', $user->id);
        
        $component
            ->assertSet('username', $user->username)
            ->assertSet('name', $user->name)
            ->assertSet('email', $user->email)
            ->assertSet('selectedRoles', $roles->pluck('id')->toArray())
            ->assertSet('isEditing', true)
            ->assertSet('showModal', true)
            ->assertSet('editingUser.id', $user->id);
    }
    
    /** @test */
    public function it_validates_required_fields()
    {
        Livewire::test(UserForm::class)
            ->call('saveUser')
            ->assertHasErrors([
                'username' => 'required',
                'name' => 'required',
                'email' => 'required',
                'password' => 'required'
            ]);
    }
    
    /** @test */
    public function it_can_create_new_user()
    {
        $roles = Role::factory()->count(2)->create();
        
        Livewire::test(UserForm::class)
            ->set('username', 'newuser')
            ->set('name', 'New User')
            ->set('email', 'newuser@example.com')
            ->set('password', 'password123')
            ->set('selectedRoles', $roles->pluck('id')->toArray())
            ->call('saveUser')
            ->assertDispatched('user-saved')
            ->assertDispatched('refresh-user-list');
        
        $this->assertDatabaseHas('users', [
            'username' => 'newuser',
            'name' => 'New User',
            'email' => 'newuser@example.com'
        ]);
    }
}
```

### 2. 瀏覽器測試模板

```php
<?php

namespace Tests\Browser\Admin\Users;

use Tests\DuskTestCase;
use App\Models\User;
use App\Models\Role;
use Laravel\Dusk\Browser;
use Illuminate\Foundation\Testing\DatabaseMigrations;

class UserFormTest extends DuskTestCase
{
    use DatabaseMigrations;
    
    protected function setUp(): void
    {
        parent::setUp();
        
        // 建立測試資料
        $this->seed();
    }
    
    /** @test */
    public function user_can_reset_form_successfully()
    {
        $admin = User::where('username', 'admin')->first();
        
        $this->browse(function (Browser $browser) use ($admin) {
            $browser->loginAs($admin)
                ->visit('/admin/users')
                ->click('@create-user-button')
                ->waitFor('@user-modal')
                ->type('@username-input', 'testuser')
                ->type('@name-input', 'Test User')
                ->type('@email-input', 'test@example.com')
                ->type('@password-input', 'password123')
                ->check('@role-checkbox-1')
                ->click('@reset-button')
                ->waitFor('.notification')
                ->assertValue('@username-input', '')
                ->assertValue('@name-input', '')
                ->assertValue('@email-input', '')
                ->assertValue('@password-input', '')
                ->assertNotChecked('@role-checkbox-1');
        });
    }
    
    /** @test */
    public function user_can_create_new_user_successfully()
    {
        $admin = User::where('username', 'admin')->first();
        
        $this->browse(function (Browser $browser) use ($admin) {
            $browser->loginAs($admin)
                ->visit('/admin/users')
                ->click('@create-user-button')
                ->waitFor('@user-modal')
                ->type('@username-input', 'newuser')
                ->type('@name-input', 'New User')
                ->type('@email-input', 'newuser@example.com')
                ->type('@password-input', 'password123')
                ->check('@role-checkbox-2')
                ->click('@save-button')
                ->waitFor('.notification')
                ->assertSee('使用者建立成功');
        });
        
        $this->assertDatabaseHas('users', [
            'username' => 'newuser',
            'name' => 'New User',
            'email' => 'newuser@example.com'
        ]);
    }
}
```

## 使用指南

### 1. 建立新元件步驟

1. **複製基礎模板**
   ```bash
   cp docs/templates/LivewireComponent.php app/Livewire/Admin/[Module]/[ComponentName].php
   cp docs/templates/livewire-component.blade.php resources/views/livewire/admin/[module]/[component-name].blade.php
   ```

2. **自定義元件內容**
   - 更新命名空間和類別名稱
   - 定義表單屬性和驗證規則
   - 實作業務邏輯方法
   - 自定義視圖結構

3. **建立測試檔案**
   ```bash
   cp docs/templates/LivewireComponentTest.php tests/Unit/Livewire/Admin/[Module]/[ComponentName]Test.php
   ```

4. **執行測試驗證**
   ```bash
   php artisan test --filter=[ComponentName]Test
   ```

### 2. 檢查清單

使用模板建立元件後，請確認以下項目：

- [ ] 所有動態元素都有 `wire:key` 屬性
- [ ] 使用 `wire:model.defer` 而非 `wire:model.lazy`
- [ ] `resetForm()` 方法包含完整的重置流程
- [ ] 前端事件監聽器正確設定
- [ ] 驗證規則完整且正確
- [ ] 測試覆蓋所有主要功能
- [ ] 遵循專案的 UI 設計標準
- [ ] 支援深色模式
- [ ] 響應式設計實作

這些模板和範例提供了標準化的 Livewire 元件開發基礎，確保所有新元件都遵循最佳實踐和一致的程式碼品質。