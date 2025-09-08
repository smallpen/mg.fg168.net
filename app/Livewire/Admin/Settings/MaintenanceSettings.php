<?php

namespace App\Livewire\Admin\Settings;

use Livewire\Component;
use App\Services\ConfigurationService;
use App\Services\BackupService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Validation\ValidationException;

/**
 * 維護設定管理元件
 * 
 * 負責處理備份、日誌、快取和維護模式的設定
 */
class MaintenanceSettings extends Component
{
    // 直接使用個別屬性而不是嵌套陣列
    public $auto_backup_enabled = true;
    public $backup_frequency = 'daily';
    public $backup_retention_days = 30;
    public $backup_storage_path = '';
    public $log_level = 'info';
    public $log_retention_days = 14;
    public $cache_driver = 'redis';
    public $cache_ttl = 3600;
    public $maintenance_mode = false;
    public $maintenance_message = '系統正在進行維護，請稍後再試。';
    public $monitoring_enabled = true;
    public $monitoring_interval = 300;
    
    public $settings = [];
    public $storageValidation = [];
    public $testResults = [];
    public $showStorageTest = false;
    public $showMaintenanceWarning = false;
    
    protected $originalSettings;
    protected $configurationService;
    protected $backupService;

    protected function rules()
    {
        return [
            'auto_backup_enabled' => 'required|boolean',
            'backup_frequency' => 'required_if:auto_backup_enabled,true|string|in:hourly,daily,weekly,monthly',
            'backup_retention_days' => 'required|integer|min:1|max:365',
            'backup_storage_path' => 'nullable|string|max:255',
            'log_level' => 'required|string|in:debug,info,notice,warning,error,critical,alert,emergency',
            'log_retention_days' => 'required|integer|min:1|max:90',
            'cache_driver' => 'required|string|in:file,redis,memcached,array',
            'cache_ttl' => 'required|integer|min:60|max:86400',
            'maintenance_mode' => 'required|boolean',
            'maintenance_message' => 'required_if:maintenance_mode,true|string|max:500',
            'monitoring_enabled' => 'required|boolean',
            'monitoring_interval' => 'required_if:monitoring_enabled,true|integer|min:60|max:3600',
        ];
    }

    protected function messages()
    {
        return [
            'settings.maintenance.backup_frequency.required_if' => '啟用自動備份時，備份頻率為必填項。',
            'settings.maintenance.maintenance_message.required_if' => '啟用維護模式時，維護訊息為必填項。',
            'settings.maintenance.monitoring_interval.required_if' => '啟用系統監控時，監控間隔為必填項。',
            'settings.maintenance.backup_storage_path.max' => '備份儲存路徑不能超過 255 個字元。',
            'settings.maintenance.cache_ttl.min' => '快取存活時間不能少於 60 秒。',
            'settings.maintenance.cache_ttl.max' => '快取存活時間不能超過 86400 秒（24小時）。',
        ];
    }

    public function mount()
    {
        try {
            Log::info('🔧 MaintenanceSettings mount() 開始');
            
            // 初始化服務
            $this->configurationService = app(ConfigurationService::class);
            $this->backupService = app(BackupService::class);
            
            $this->loadSettings();
            $this->initializeStorageValidation();
            
            Log::info('✅ MaintenanceSettings mount() 完成', [
                'settings_count' => count($this->settings),
                'settings_keys' => array_keys($this->settings),
                'sample_values' => [
                    'auto_backup_enabled' => $this->settings['maintenance.auto_backup_enabled'] ?? 'not_set',
                    'backup_frequency' => $this->settings['maintenance.backup_frequency'] ?? 'not_set',
                    'maintenance_mode' => $this->settings['maintenance.maintenance_mode'] ?? 'not_set'
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('❌ MaintenanceSettings mount() 失敗', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    /**
     * 監聽清除快取事件
     */
    #[\Livewire\Attributes\On('clearCache')]
    public function handleClearCache()
    {
        $this->clearCache();
    }

    /**
     * 公開方法：重新載入設定
     */
    public function reloadSettings()
    {
        Log::info('🔄 手動重新載入設定');
        $this->loadSettings();
        $this->dispatch('settings-reloaded', [
            'message' => '設定已重新載入',
            'count' => count($this->settings)
        ]);
    }

    public function loadSettings()
    {
        try {
            // 確保服務已初始化
            if (!$this->configurationService) {
                $this->configurationService = app(ConfigurationService::class);
            }
            
            // 載入設定資料
            $settingsData = $this->configurationService->getSettingsByCategory('maintenance');
            
            // 保存到 settings 陣列（用於其他方法）
            $this->settings = $settingsData;
            
            // 設定個別屬性（用於表單綁定）
            $this->auto_backup_enabled = $settingsData['maintenance.auto_backup_enabled'] ?? true;
            $this->backup_frequency = $settingsData['maintenance.backup_frequency'] ?? 'daily';
            $this->backup_retention_days = $settingsData['maintenance.backup_retention_days'] ?? 30;
            $this->backup_storage_path = $settingsData['maintenance.backup_storage_path'] ?? '';
            $this->log_level = $settingsData['maintenance.log_level'] ?? 'info';
            $this->log_retention_days = $settingsData['maintenance.log_retention_days'] ?? 14;
            $this->cache_driver = $settingsData['maintenance.cache_driver'] ?? 'redis';
            $this->cache_ttl = $settingsData['maintenance.cache_ttl'] ?? 3600;
            $this->maintenance_mode = $settingsData['maintenance.maintenance_mode'] ?? false;
            $this->maintenance_message = $settingsData['maintenance.maintenance_message'] ?? '系統正在進行維護，請稍後再試。';
            $this->monitoring_enabled = $settingsData['maintenance.monitoring_enabled'] ?? true;
            $this->monitoring_interval = $settingsData['maintenance.monitoring_interval'] ?? 300;
            
            // 保存原始設定的副本（使用與比較相同的格式）
            $this->originalSettings = [
                'maintenance.auto_backup_enabled' => $this->auto_backup_enabled,
                'maintenance.backup_frequency' => $this->backup_frequency,
                'maintenance.backup_retention_days' => $this->backup_retention_days,
                'maintenance.backup_storage_path' => $this->backup_storage_path,
                'maintenance.log_level' => $this->log_level,
                'maintenance.log_retention_days' => $this->log_retention_days,
                'maintenance.cache_driver' => $this->cache_driver,
                'maintenance.cache_ttl' => $this->cache_ttl,
                'maintenance.maintenance_mode' => $this->maintenance_mode,
                'maintenance.maintenance_message' => $this->maintenance_message,
                'maintenance.monitoring_enabled' => $this->monitoring_enabled,
                'maintenance.monitoring_interval' => $this->monitoring_interval,
            ];
            
            // 重置警告狀態
            $this->showMaintenanceWarning = false;
            
        } catch (\Exception $e) {
            // 設定預設值以防止錯誤
            $this->auto_backup_enabled = true;
            $this->backup_frequency = 'daily';
            $this->backup_retention_days = 30;
            $this->backup_storage_path = '';
            $this->log_level = 'info';
            $this->log_retention_days = 14;
            $this->cache_driver = 'redis';
            $this->cache_ttl = 3600;
            $this->maintenance_mode = false;
            $this->maintenance_message = '系統正在進行維護，請稍後再試。';
            $this->monitoring_enabled = true;
            $this->monitoring_interval = 300;
            
            // 設定原始設定以避免顯示未儲存變更警告
            $this->originalSettings = [
                'maintenance.auto_backup_enabled' => $this->auto_backup_enabled,
                'maintenance.backup_frequency' => $this->backup_frequency,
                'maintenance.backup_retention_days' => $this->backup_retention_days,
                'maintenance.backup_storage_path' => $this->backup_storage_path,
                'maintenance.log_level' => $this->log_level,
                'maintenance.log_retention_days' => $this->log_retention_days,
                'maintenance.cache_driver' => $this->cache_driver,
                'maintenance.cache_ttl' => $this->cache_ttl,
                'maintenance.maintenance_mode' => $this->maintenance_mode,
                'maintenance.maintenance_message' => $this->maintenance_message,
                'maintenance.monitoring_enabled' => $this->monitoring_enabled,
                'maintenance.monitoring_interval' => $this->monitoring_interval,
            ];
            
            $this->dispatch('settings-load-error', [
                'type' => 'error',
                'message' => '載入維護設定失敗：' . $e->getMessage()
            ]);
        }
    }

    public function initializeStorageValidation()
    {
        $this->storageValidation = [
            'backup_path' => null,
            'log_path' => null,
            'cache_connection' => null,
        ];
    }

    public function save()
    {
        try {
            $this->validate();
            
            // 確保服務已初始化
            if (!$this->configurationService) {
                $this->configurationService = app(ConfigurationService::class);
            }
            
            // 檢查維護模式變更
            $oldMaintenanceMode = $this->originalSettings['maintenance.maintenance_mode'] ?? false;
            $newMaintenanceMode = $this->maintenance_mode;
            
            if ($oldMaintenanceMode !== $newMaintenanceMode) {
                $this->showMaintenanceWarning = $newMaintenanceMode;
            }
            
            // 準備要儲存的設定資料
            $settingsToSave = [
                'maintenance.auto_backup_enabled' => $this->auto_backup_enabled,
                'maintenance.backup_frequency' => $this->backup_frequency,
                'maintenance.backup_retention_days' => $this->backup_retention_days,
                'maintenance.backup_storage_path' => $this->backup_storage_path,
                'maintenance.log_level' => $this->log_level,
                'maintenance.log_retention_days' => $this->log_retention_days,
                'maintenance.cache_driver' => $this->cache_driver,
                'maintenance.cache_ttl' => $this->cache_ttl,
                'maintenance.maintenance_mode' => $this->maintenance_mode,
                'maintenance.maintenance_message' => $this->maintenance_message,
                'maintenance.monitoring_enabled' => $this->monitoring_enabled,
                'maintenance.monitoring_interval' => $this->monitoring_interval,
            ];
            
            // 驗證儲存位置
            if ($this->auto_backup_enabled) {
                $this->validateBackupStorage();
            }

            // 驗證快取連線
            $this->validateCacheConnection();

            // 如果啟用維護模式，顯示警告
            if ($newMaintenanceMode && !$oldMaintenanceMode) {
                $this->showMaintenanceWarning = true;
                $this->dispatch('maintenance-mode-warning', [
                    'message' => '啟用維護模式將阻止一般使用者存取系統，請確認您要繼續。'
                ]);
                return;
            }
            
            // 儲存設定
            $this->configurationService->updateSettings($settingsToSave);
            
            // 更新 settings 陣列和原始設定
            $this->settings = $settingsToSave;
            $this->originalSettings = $settingsToSave;
            
            // 如果變更了快取設定，清除快取
            if (($this->originalSettings['maintenance.cache_driver'] ?? '') !== $this->cache_driver) {
                $this->clearCache();
            }
            
            $this->dispatch('settings-saved', [
                'type' => 'success',
                'message' => '維護設定已成功儲存！'
            ]);
            
        } catch (ValidationException $e) {
            $this->dispatch('settings-validation-error', [
                'type' => 'error',
                'message' => '請檢查輸入的資料是否正確',
                'errors' => $e->errors()
            ]);
        } catch (\Exception $e) {
            $this->dispatch('settings-save-error', [
                'type' => 'error',
                'message' => '儲存設定時發生錯誤：' . $e->getMessage()
            ]);
        }
    }

    /**
     * 驗證備份儲存位置
     */
    public function validateBackupStorage()
    {
        $backupPath = $this->backup_storage_path ?: storage_path('backups');
        
        try {
            // 檢查目錄是否存在，不存在則建立
            if (!File::exists($backupPath)) {
                File::makeDirectory($backupPath, 0755, true);
            }

            // 檢查是否可寫入
            if (!File::isWritable($backupPath)) {
                throw new \Exception("備份目錄 {$backupPath} 無法寫入");
            }

            // 檢查磁碟空間（至少需要 1GB）
            $freeSpace = disk_free_space($backupPath);
            if ($freeSpace < 1024 * 1024 * 1024) {
                throw new \Exception("備份目錄磁碟空間不足，至少需要 1GB 可用空間");
            }

            $this->storageValidation['backup_path'] = [
                'status' => 'success',
                'message' => '備份儲存位置驗證成功',
                'path' => $backupPath,
                'free_space' => $this->formatBytes($freeSpace)
            ];

        } catch (\Exception $e) {
            $this->storageValidation['backup_path'] = [
                'status' => 'error',
                'message' => $e->getMessage(),
                'path' => $backupPath
            ];
            throw $e;
        }
    }

    /**
     * 驗證快取連線
     */
    public function validateCacheConnection()
    {
        $driver = $this->cache_driver;
        
        try {
            switch ($driver) {
                case 'redis':
                    $this->testRedisConnection();
                    break;
                case 'memcached':
                    $this->testMemcachedConnection();
                    break;
                case 'file':
                    $this->testFileCache();
                    break;
                case 'array':
                    // Array cache 不需要測試
                    break;
            }

            $this->storageValidation['cache_connection'] = [
                'status' => 'success',
                'message' => "{$driver} 快取連線正常",
                'driver' => $driver
            ];

        } catch (\Exception $e) {
            $this->storageValidation['cache_connection'] = [
                'status' => 'error',
                'message' => "{$driver} 快取連線失敗：" . $e->getMessage(),
                'driver' => $driver
            ];
            throw $e;
        }
    }

    /**
     * 測試 Redis 連線
     */
    protected function testRedisConnection()
    {
        $redis = Cache::store('redis');
        $testKey = 'maintenance_test_' . time();
        $testValue = 'test_value';
        
        $redis->put($testKey, $testValue, 60);
        $retrieved = $redis->get($testKey);
        $redis->forget($testKey);
        
        if ($retrieved !== $testValue) {
            throw new \Exception('Redis 讀寫測試失敗');
        }
    }

    /**
     * 測試 Memcached 連線
     */
    protected function testMemcachedConnection()
    {
        $memcached = Cache::store('memcached');
        $testKey = 'maintenance_test_' . time();
        $testValue = 'test_value';
        
        $memcached->put($testKey, $testValue, 60);
        $retrieved = $memcached->get($testKey);
        $memcached->forget($testKey);
        
        if ($retrieved !== $testValue) {
            throw new \Exception('Memcached 讀寫測試失敗');
        }
    }

    /**
     * 測試檔案快取
     */
    protected function testFileCache()
    {
        $cachePath = storage_path('framework/cache');
        
        if (!File::exists($cachePath)) {
            File::makeDirectory($cachePath, 0755, true);
        }
        
        if (!File::isWritable($cachePath)) {
            throw new \Exception("快取目錄 {$cachePath} 無法寫入");
        }
    }

    /**
     * 清除快取
     */
    public function clearCache()
    {
        try {
            Log::info('開始清除快取');
            
            // 清除各種快取
            $results = [];
            
            $results['cache'] = Artisan::call('cache:clear');
            $results['config'] = Artisan::call('config:clear');
            $results['route'] = Artisan::call('route:clear');
            $results['view'] = Artisan::call('view:clear');
            
            // 嘗試清除 OPcache（如果啟用）
            if (function_exists('opcache_reset')) {
                opcache_reset();
                $results['opcache'] = 'cleared';
            }
            
            Log::info('快取清除完成', $results);
            
            $this->dispatch('show-toast', [
                'type' => 'success',
                'message' => '所有快取已成功清除！'
            ]);
            
        } catch (\Exception $e) {
            Log::error('清除快取失敗', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            $this->dispatch('show-toast', [
                'type' => 'error',
                'message' => '清除快取失敗：' . $e->getMessage()
            ]);
        }
    }

    /**
     * 測試備份功能
     */
    public function testBackup()
    {
        try {
            $this->validateBackupStorage();
            
            // 執行測試備份
            $result = $this->backupService->performTestBackup();
            
            $this->testResults['backup'] = [
                'status' => 'success',
                'message' => '備份測試成功',
                'details' => $result
            ];
            
            $this->dispatch('test-completed', [
                'type' => 'success',
                'message' => '備份功能測試成功'
            ]);
            
        } catch (\Exception $e) {
            $this->testResults['backup'] = [
                'status' => 'error',
                'message' => '備份測試失敗：' . $e->getMessage()
            ];
            
            $this->dispatch('test-completed', [
                'type' => 'error',
                'message' => '備份功能測試失敗：' . $e->getMessage()
            ]);
        }
        
        $this->showStorageTest = true;
    }

    /**
     * 測試系統監控
     */
    public function testMonitoring()
    {
        try {
            $monitoringData = [
                'cpu_usage' => sys_getloadavg()[0] ?? 0,
                'memory_usage' => memory_get_usage(true),
                'disk_usage' => disk_total_space('.') - disk_free_space('.'),
                'timestamp' => now()->toISOString()
            ];
            
            $this->testResults['monitoring'] = [
                'status' => 'success',
                'message' => '系統監控測試成功',
                'data' => $monitoringData
            ];
            
            $this->dispatch('test-completed', [
                'type' => 'success',
                'message' => '系統監控功能正常'
            ]);
            
        } catch (\Exception $e) {
            $this->testResults['monitoring'] = [
                'status' => 'error',
                'message' => '監控測試失敗：' . $e->getMessage()
            ];
            
            $this->dispatch('test-completed', [
                'type' => 'error',
                'message' => '系統監控測試失敗：' . $e->getMessage()
            ]);
        }
    }

    /**
     * 確認維護模式變更
     */
    public function confirmMaintenanceMode()
    {
        $this->showMaintenanceWarning = false;
        
        try {
            // 準備要儲存的設定資料
            $settingsToSave = [
                'maintenance.auto_backup_enabled' => $this->auto_backup_enabled,
                'maintenance.backup_frequency' => $this->backup_frequency,
                'maintenance.backup_retention_days' => $this->backup_retention_days,
                'maintenance.backup_storage_path' => $this->backup_storage_path,
                'maintenance.log_level' => $this->log_level,
                'maintenance.log_retention_days' => $this->log_retention_days,
                'maintenance.cache_driver' => $this->cache_driver,
                'maintenance.cache_ttl' => $this->cache_ttl,
                'maintenance.maintenance_mode' => $this->maintenance_mode,
                'maintenance.maintenance_message' => $this->maintenance_message,
                'maintenance.monitoring_enabled' => $this->monitoring_enabled,
                'maintenance.monitoring_interval' => $this->monitoring_interval,
            ];
            
            $this->configurationService->updateSettings($settingsToSave);
            
            $this->loadSettings();
            
            $this->dispatch('saved', [
                'type' => 'success',
                'message' => '維護模式已啟用'
            ]);
            
        } catch (\Exception $e) {
            $this->dispatch('saved', [
                'type' => 'error',
                'message' => '啟用維護模式失敗：' . $e->getMessage()
            ]);
        }
    }

    /**
     * 取消維護模式變更
     */
    public function cancelMaintenanceMode()
    {
        $this->maintenance_mode = false;
        $this->showMaintenanceWarning = false;
    }

    /**
     * 檢查是否有未儲存的變更
     */
    public function getHasUnsavedChangesProperty()
    {
        $currentSettings = [
            'maintenance.auto_backup_enabled' => $this->auto_backup_enabled,
            'maintenance.backup_frequency' => $this->backup_frequency,
            'maintenance.backup_retention_days' => $this->backup_retention_days,
            'maintenance.backup_storage_path' => $this->backup_storage_path,
            'maintenance.log_level' => $this->log_level,
            'maintenance.log_retention_days' => $this->log_retention_days,
            'maintenance.cache_driver' => $this->cache_driver,
            'maintenance.cache_ttl' => $this->cache_ttl,
            'maintenance.maintenance_mode' => $this->maintenance_mode,
            'maintenance.maintenance_message' => $this->maintenance_message,
            'maintenance.monitoring_enabled' => $this->monitoring_enabled,
            'maintenance.monitoring_interval' => $this->monitoring_interval,
        ];
        
        return $currentSettings !== $this->originalSettings;
    }

    /**
     * 格式化位元組大小
     */
    protected function formatBytes($bytes, $precision = 2)
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, $precision) . ' ' . $units[$i];
    }

    public function render()
    {
        return view('livewire.admin.settings.maintenance-settings');
    }
}
