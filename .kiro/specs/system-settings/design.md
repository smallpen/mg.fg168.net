# 系統設定功能設計文件

## 概述

系統設定功能提供集中化的應用程式配置管理，採用分類組織、即時驗證、版本控制和備份還原的設計理念。

## 架構設計

### 核心元件架構

```
┌─────────────────┐    ┌──────────────────┐    ┌─────────────────┐
│ SettingsList    │    │ SettingForm      │    │ Settings        │
│   Component     │◄──►│   Component      │◄──►│  Repository     │
└─────────────────┘    └──────────────────┘    └─────────────────┘
         │                        │                        │
         ▼                        ▼                        ▼
┌─────────────────┐    ┌──────────────────┐    ┌─────────────────┐
│ SettingBackup   │    │ SettingPreview   │    │ Configuration   │
│   Component     │    │   Component      │    │   Service       │
└─────────────────┘    └──────────────────┘    └─────────────────┘
```

## 元件設計

### 1. SettingsList 元件

**檔案位置**: `app/Livewire/Admin/Settings/SettingsList.php`

```php
class SettingsList extends Component
{
    // 搜尋和篩選
    public string $search = '';
    public string $categoryFilter = 'all';
    public string $changedFilter = 'all';
    
    // 顯示模式
    public string $viewMode = 'category'; // category, list, tree
    public array $expandedCategories = [];
    
    // 批量操作
    public array $selectedSettings = [];
    public string $bulkAction = '';
    
    // 計算屬性
    public function getSettingsProperty(): Collection
    public function getCategoriesProperty(): Collection
    public function getChangedSettingsProperty(): Collection
    public function getStatsProperty(): array
    
    // 操作方法
    public function editSetting(string $key): void
    public function resetSetting(string $key): void
    public function toggleCategory(string $category): void
    public function exportSettings(): void
    public function importSettings(): void
    public function createBackup(): void
}
```

### 2. SettingForm 元件

**檔案位置**: `app/Livewire/Admin/Settings/SettingForm.php`

```php
class SettingForm extends Component
{
    public string $settingKey = '';
    public mixed $value = null;
    public mixed $originalValue = null;
    public array $settingConfig = [];
    public bool $showPreview = false;
    
    // 計算屬性
    public function getSettingProperty(): ?Setting
    public function getValidationRulesProperty(): array
    public function getInputTypeProperty(): string
    public function getOptionsProperty(): array
    
    // 操作方法
    public function save(): void
    public function cancel(): void
    public function reset(): void
    public function preview(): void
    public function testConnection(): void // 用於測試 SMTP、API 等
    
    // 驗證方法
    public function validateValue(): bool
    public function checkDependencies(): array
}
```

### 3. SettingBackup 元件

**檔案位置**: `app/Livewire/Admin/Settings/SettingBackup.php`

```php
class SettingBackup extends Component
{
    public array $backups = [];
    public string $backupName = '';
    public string $backupDescription = '';
    public bool $showRestoreModal = false;
    public ?array $selectedBackup = null;
    
    // 計算屬性
    public function getBackupsProperty(): Collection
    public function getRestorePreviewProperty(): array
    
    // 操作方法
    public function createBackup(): void
    public function deleteBackup(int $backupId): void
    public function restoreBackup(int $backupId): void
    public function downloadBackup(int $backupId): void
    public function compareBackup(int $backupId): array
}
```

### 4. SettingPreview 元件

**檔案位置**: `app/Livewire/Admin/Settings/SettingPreview.php`

```php
class SettingPreview extends Component
{
    public array $previewSettings = [];
    public string $previewMode = 'theme'; // theme, email, layout
    
    // 計算屬性
    public function getPreviewDataProperty(): array
    
    // 操作方法
    public function updatePreview(string $key, mixed $value): void
    public function applyPreview(): void
    public function resetPreview(): void
}
```

## 資料存取層設計

### SettingsRepository

```php
interface SettingsRepositoryInterface
{
    public function getAllSettings(): Collection;
    public function getSettingsByCategory(string $category): Collection;
    public function getSetting(string $key): ?Setting;
    public function updateSetting(string $key, mixed $value): bool;
    public function resetSetting(string $key): bool;
    public function getChangedSettings(): Collection;
    public function exportSettings(array $categories = []): array;
    public function importSettings(array $data): array;
    public function validateSetting(string $key, mixed $value): bool;
    public function getSettingDependencies(string $key): array;
    public function createBackup(string $name, string $description = ''): SettingBackup;
    public function restoreBackup(int $backupId): bool;
    public function getBackups(): Collection;
}
```

### ConfigurationService

```php
class ConfigurationService
{
    public function getSettingConfig(string $key): array
    public function getCategories(): array
    public function validateSettingValue(string $key, mixed $value): bool
    public function getSettingType(string $key): string
    public function getSettingOptions(string $key): array
    public function getDependentSettings(string $key): array
    public function applySettings(array $settings): void
    public function testConnection(string $type, array $config): bool
    public function generatePreview(array $settings): array
}
```

## 資料模型設計

### Setting 模型

```php
class Setting extends Model
{
    protected $fillable = ['key', 'value', 'category', 'type', 'options', 'description'];
    protected $casts = [
        'value' => 'json',
        'options' => 'json',
        'is_encrypted' => 'boolean',
        'is_system' => 'boolean',
    ];
    
    // 計算屬性
    public function getDisplayValueAttribute(): string
    public function getIsChangedAttribute(): bool
    public function getValidationRulesAttribute(): array
    
    // 操作方法
    public function updateValue(mixed $value): bool
    public function resetToDefault(): bool
    public function encrypt(): void
    public function decrypt(): mixed
    
    // 關聯關係
    public function backups(): HasMany
    public function changes(): HasMany
}
```

### SettingBackup 模型

```php
class SettingBackup extends Model
{
    protected $fillable = ['name', 'description', 'settings_data', 'created_by'];
    protected $casts = [
        'settings_data' => 'json',
        'created_at' => 'datetime',
    ];
    
    // 關聯關係
    public function creator(): BelongsTo
    
    // 操作方法
    public function restore(): bool
    public function compare(): array
    public function download(): string
}
```

### SettingChange 模型

```php
class SettingChange extends Model
{
    protected $fillable = ['setting_key', 'old_value', 'new_value', 'changed_by', 'reason'];
    protected $casts = [
        'old_value' => 'json',
        'new_value' => 'json',
        'created_at' => 'datetime',
    ];
    
    // 關聯關係
    public function setting(): BelongsTo
    public function user(): BelongsTo
}
```

## 設定配置結構

### 設定定義檔案

```php
// config/system-settings.php
return [
    'categories' => [
        'basic' => [
            'name' => '基本設定',
            'icon' => 'cog',
            'description' => '應用程式基本資訊設定',
        ],
        'security' => [
            'name' => '安全設定',
            'icon' => 'shield-check',
            'description' => '系統安全政策設定',
        ],
        // ... 其他分類
    ],
    
    'settings' => [
        'app.name' => [
            'category' => 'basic',
            'type' => 'text',
            'default' => 'Laravel Admin System',
            'validation' => 'required|string|max:100',
            'description' => '應用程式名稱',
        ],
        'security.password_min_length' => [
            'category' => 'security',
            'type' => 'number',
            'default' => 8,
            'validation' => 'required|integer|min:6|max:20',
            'description' => '密碼最小長度',
            'options' => ['min' => 6, 'max' => 20],
        ],
        'theme.primary_color' => [
            'category' => 'appearance',
            'type' => 'color',
            'default' => '#3B82F6',
            'validation' => 'required|regex:/^#[0-9A-Fa-f]{6}$/',
            'description' => '主要顏色',
            'preview' => true,
        ],
        // ... 其他設定
    ],
];
```

## 使用者介面設計

### 設定管理頁面佈局

```
┌─────────────────────────────────────────────────────────────┐
│  系統設定                    [匯出] [匯入] [建立備份]         │
├─────────────────────────────────────────────────────────────┤
│  [搜尋框] [分類篩選] [變更狀態] [檢視模式▼]                  │
├─────────────────────────────────────────────────────────────┤
│  ┌─ 📋 基本設定                                              │
│  │  ├─ 應用程式名稱    [Laravel Admin System    ] [編輯]    │
│  │  ├─ 應用程式描述    [系統管理後台            ] [編輯]    │
│  │  └─ 系統時區        [Asia/Taipei ▼          ] [編輯]    │
│  │                                                          │
│  ┌─ 🔒 安全設定                                              │
│  │  ├─ 密碼最小長度    [8                      ] [編輯]    │
│  │  ├─ 登入失敗鎖定    [5 次                   ] [編輯]    │
│  │  └─ Session 過期    [120 分鐘               ] [編輯]    │
│  │                                                          │
│  ┌─ 🎨 外觀設定                                              │
│  │  ├─ 預設主題        [自動 ▼                 ] [編輯]    │
│  │  ├─ 主要顏色        [🔵 #3B82F6             ] [編輯]    │
│  │  └─ 系統標誌        [上傳檔案...            ] [編輯]    │
└─────────────────────────────────────────────────────────────┘
```

### 設定編輯對話框

```
┌─────────────────────────────────────────────────────────────┐
│  編輯設定 - 密碼最小長度                      [預覽] [測試]  │
├─────────────────────────────────────────────────────────────┤
│  設定值: [8        ] (範圍: 6-20)                           │
│                                                            │
│  描述: 使用者密碼的最小長度要求                              │
│                                                            │
│  影響範圍:                                                  │
│  • 新使用者註冊時的密碼驗證                                  │
│  • 使用者修改密碼時的驗證                                    │
│  • 管理員建立使用者時的密碼要求                              │
│                                                            │
│  相關設定:                                                  │
│  • 密碼複雜度要求                                           │
│  • 密碼過期天數                                             │
│                                                            │
│  [儲存] [取消] [重設為預設值]                                │
└─────────────────────────────────────────────────────────────┘
```

### 設定備份管理

```
┌─────────────────────────────────────────────────────────────┐
│  設定備份管理                                [+ 建立備份]    │
├─────────────────────────────────────────────────────────────┤
│  備份名稱        建立時間      建立者    大小    操作        │
│  生產環境初始設定  2024-01-01   admin    2.3KB  [還原][下載]│
│  安全設定更新     2024-01-15   admin    1.8KB  [還原][下載]│
│  外觀調整備份     2024-02-01   editor   2.1KB  [還原][下載]│
├─────────────────────────────────────────────────────────────┤
│                    [← 上一頁] 1 2 3 [下一頁 →]                │
└─────────────────────────────────────────────────────────────┘
```

## 安全性設計

### 敏感資料加密

```php
class EncryptedSetting extends Setting
{
    protected $casts = [
        'value' => EncryptedJson::class,
    ];
    
    public function setValueAttribute($value)
    {
        if ($this->is_encrypted) {
            $this->attributes['value'] = encrypt($value);
        } else {
            $this->attributes['value'] = $value;
        }
    }
    
    public function getValueAttribute($value)
    {
        if ($this->is_encrypted) {
            return decrypt($value);
        }
        return $value;
    }
}
```

### 設定變更審計

```php
class SettingObserver
{
    public function updating(Setting $setting): void
    {
        if ($setting->isDirty('value')) {
            SettingChange::create([
                'setting_key' => $setting->key,
                'old_value' => $setting->getOriginal('value'),
                'new_value' => $setting->value,
                'changed_by' => auth()->id(),
                'ip_address' => request()->ip(),
            ]);
        }
    }
}
```

## 效能優化

### 設定快取機制

```php
class CachedSettingsRepository implements SettingsRepositoryInterface
{
    public function getSetting(string $key): ?Setting
    {
        return Cache::remember("setting_{$key}", 3600, function () use ($key) {
            return Setting::where('key', $key)->first();
        });
    }
    
    public function updateSetting(string $key, mixed $value): bool
    {
        $result = Setting::where('key', $key)->update(['value' => $value]);
        
        if ($result) {
            Cache::forget("setting_{$key}");
            Cache::forget('all_settings');
            
            // 觸發設定變更事件
            event(new SettingUpdated($key, $value));
        }
        
        return $result;
    }
}
```

### 批量設定更新

```php
public function updateMultipleSettings(array $settings): bool
{
    DB::transaction(function () use ($settings) {
        foreach ($settings as $key => $value) {
            $this->updateSetting($key, $value);
        }
    });
    
    // 清除相關快取
    Cache::tags(['settings'])->flush();
    
    return true;
}
```