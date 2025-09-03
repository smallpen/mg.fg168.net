<?php

namespace App\Livewire\Admin\Settings;

use Livewire\Component;
use Illuminate\Support\Facades\Cache;
use App\Repositories\SettingsRepositoryInterface;

/**
 * 基本設定管理元件
 */
class BasicSettings extends Component
{
    /**
     * 設定值
     */
    public array $settings = [
        'app' => [
            'name' => '',
            'description' => '',
            'timezone' => '',
            'locale' => '',
            'date_format' => '',
            'time_format' => '',
        ]
    ];

    /**
     * 原始設定值
     */
    public array $originalSettings = [];

    /**
     * 設定儲存庫
     */
    protected ?SettingsRepositoryInterface $settingsRepository = null;

    /**
     * 取得設定儲存庫
     */
    protected function getSettingsRepository(): SettingsRepositoryInterface
    {
        if ($this->settingsRepository === null) {
            try {
                // 確保服務容器可用
                if (!app()->bound(SettingsRepositoryInterface::class)) {
                    \Log::error('SettingsRepositoryInterface 未綁定到服務容器');
                    throw new \Exception('SettingsRepositoryInterface 未註冊');
                }
                
                $this->settingsRepository = app(SettingsRepositoryInterface::class);
                
                if ($this->settingsRepository === null) {
                    \Log::error('服務容器返回 null');
                    throw new \Exception('服務容器解析返回 null');
                }
                
                \Log::info('SettingsRepository 成功解析', [
                    'class' => get_class($this->settingsRepository),
                    'bound' => app()->bound(SettingsRepositoryInterface::class),
                    'resolved' => $this->settingsRepository !== null
                ]);
                
            } catch (\Exception $e) {
                \Log::error('SettingsRepository 解析失敗', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                    'bound' => app()->bound(SettingsRepositoryInterface::class),
                    'app_available' => app() !== null
                ]);
                throw $e;
            }
        }
        return $this->settingsRepository;
    }

    /**
     * 掛載元件
     */
    public function mount()
    {
        $this->authorize('settings.view');
        $this->loadSettings();
    }

    /**
     * 載入設定
     */
    public function loadSettings()
    {
        try {
            \Log::info('開始載入設定', [
                'component' => static::class,
                'method' => 'loadSettings'
            ]);
            
            // 從資料庫載入基本設定
            $repository = $this->getSettingsRepository();
            
            \Log::info('取得設定儲存庫', [
                'repository_class' => $repository ? get_class($repository) : 'null',
                'repository_null' => $repository === null
            ]);
            
            if ($repository === null) {
                throw new \Exception('SettingsRepository 為 null，無法載入設定');
            }
            
            $basicSettings = $repository->getSettingsByCategory('basic');
            
            // 初始化預設值
            $this->settings = [
                'app' => [
                    'name' => 'Laravel Admin System',
                    'description' => '功能完整的管理系統',
                    'timezone' => 'Asia/Taipei',
                    'locale' => 'zh_TW',
                    'date_format' => 'Y-m-d',
                    'time_format' => 'H:i',
                ]
            ];
            
            // 從資料庫覆蓋設定值
            foreach ($basicSettings as $setting) {
                // 將 app.name 轉換為 ['app']['name']
                $keyParts = explode('.', $setting->key);
                if (count($keyParts) === 2 && $keyParts[0] === 'app') {
                    $this->settings['app'][$keyParts[1]] = $setting->value;
                }
            }
            
            $this->originalSettings = $this->settings;
            
        } catch (\Exception $e) {
            // 如果載入失敗，使用預設值
            \Log::warning('載入基本設定失敗，使用預設值', [
                'error' => $e->getMessage(),
                'component' => static::class,
            ]);
            
            $this->settings = [
                'app' => [
                    'name' => config('app.name', 'Laravel Admin System'),
                    'description' => '功能完整的管理系統',
                    'timezone' => config('app.timezone', 'Asia/Taipei'),
                    'locale' => config('app.locale', 'zh_TW'),
                    'date_format' => 'Y-m-d',
                    'time_format' => 'H:i',
                ]
            ];
            
            $this->originalSettings = $this->settings;
        }
    }

    /**
     * 儲存設定
     */
    public function save()
    {
        try {
            // 檢查權限
            $this->authorize('settings.edit');
            
            // 將嵌套結構轉換為平面結構
            $flatSettings = [];
            foreach ($this->settings['app'] as $key => $value) {
                $flatSettings["app.{$key}"] = $value;
            }
            
            // 批量更新設定
            $result = $this->getSettingsRepository()->updateSettings($flatSettings);
            
            if ($result) {
                $this->originalSettings = $this->settings;
                
                // 清除相關快取
                $this->getSettingsRepository()->clearCache();
                Cache::forget('basic_settings_applied');
                
                $this->dispatch('show-toast', [
                    'type' => 'success',
                    'message' => '基本設定已成功儲存'
                ]);
                
                // 記錄操作日誌
                \Log::info('基本設定已更新', [
                    'settings' => $this->getChangedSettingsProperty(),
                    'user_id' => auth()->id(),
                    'component' => static::class,
                ]);
                
            } else {
                $this->dispatch('show-toast', [
                    'type' => 'error',
                    'message' => '儲存設定時發生錯誤，請重試'
                ]);
            }
            
        } catch (\Exception $e) {
            \Log::error('儲存基本設定失敗', [
                'error' => $e->getMessage(),
                'settings' => $this->settings,
                'user_id' => auth()->id(),
                'component' => static::class,
            ]);
            
            $this->dispatch('show-toast', [
                'type' => 'error',
                'message' => '儲存設定失敗：' . $e->getMessage()
            ]);
        }
    }

    /**
     * 重設設定
     */
    public function resetAll()
    {
        try {
            // 檢查權限
            $this->authorize('settings.reset');
            
            // 重設所有基本設定為預設值
            $settingKeys = [];
            foreach ($this->settings['app'] as $key => $value) {
                $settingKeys[] = "app.{$key}";
            }
            $resetCount = 0;
            
            foreach ($settingKeys as $key) {
                if ($this->getSettingsRepository()->resetSetting($key)) {
                    $resetCount++;
                }
            }
            
            // 重新載入設定
            $this->loadSettings();
            
            // 清除快取
            $this->getSettingsRepository()->clearCache();
            Cache::forget('basic_settings_applied');
            
            $this->dispatch('show-toast', [
                'type' => 'success',
                'message' => "已重設 {$resetCount} 項基本設定為預設值"
            ]);
            
            // 記錄操作日誌
            \Log::info('基本設定已重設', [
                'reset_count' => $resetCount,
                'user_id' => auth()->id(),
                'component' => static::class,
            ]);
            
        } catch (\Exception $e) {
            \Log::error('重設基本設定失敗', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
                'component' => static::class,
            ]);
            
            $this->dispatch('show-toast', [
                'type' => 'error',
                'message' => '重設設定失敗：' . $e->getMessage()
            ]);
        }
    }

    /**
     * 檢查是否有變更
     */
    public function getHasChangesProperty()
    {
        return $this->settings !== $this->originalSettings;
    }

    /**
     * 取得變更的設定
     */
    public function getChangedSettingsProperty()
    {
        $changes = [];
        
        // 比較 app 設定
        if (isset($this->settings['app']) && isset($this->originalSettings['app'])) {
            foreach ($this->settings['app'] as $key => $value) {
                $originalValue = $this->originalSettings['app'][$key] ?? '';
                if ($originalValue !== $value) {
                    $changes["app.{$key}"] = [
                        'old' => $originalValue,
                        'new' => $value
                    ];
                }
            }
        }
        
        return $changes;
    }

    /**
     * 取得時區選項
     */
    public function getTimezoneOptionsProperty()
    {
        return [
            'Asia/Taipei' => '台北 (UTC+8)',
            'Asia/Shanghai' => '上海 (UTC+8)',
            'Asia/Tokyo' => '東京 (UTC+9)',
            'UTC' => 'UTC (UTC+0)',
            'America/New_York' => '紐約 (UTC-5)',
        ];
    }

    /**
     * 取得語言選項
     */
    public function getLocaleOptionsProperty()
    {
        return [
            'zh_TW' => '正體中文',
            'en' => 'English',
        ];
    }

    /**
     * 取得日期格式選項
     */
    public function getDateFormatOptionsProperty()
    {
        return [
            'Y-m-d' => '2025-01-01',
            'Y/m/d' => '2025/01/01',
            'd/m/Y' => '01/01/2025',
            'd-m-Y' => '01-01-2025',
        ];
    }

    /**
     * 取得時間格式選項
     */
    public function getTimeFormatOptionsProperty()
    {
        return [
            'H:i' => '24小時制 (14:30)',
            'H:i:s' => '24小時制含秒 (14:30:45)',
            'g:i A' => '12小時制 (2:30 PM)',
            'g:i:s A' => '12小時制含秒 (2:30:45 PM)',
        ];
    }

    /**
     * 渲染元件
     */
    public function render()
    {
        return view('livewire.admin.settings.basic-settings');
    }
}