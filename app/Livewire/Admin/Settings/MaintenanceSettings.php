<?php

namespace App\Livewire\Admin\Settings;

use Livewire\Component;
use App\Services\ConfigurationService;
use App\Services\BackupService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Artisan;

/**
 * 維護設定管理元件
 * 
 * 負責處理備份、日誌、快取和維護模式的設定
 */
class MaintenanceSettings extends Component
{
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
            'settings.maintenance.auto_backup_enabled' => 'required|boolean',
            'settings.maintenance.backup_frequency' => 'required_if:settings.maintenance.auto_backup_enabled,true|string|in:hourly,daily,weekly,monthly',
            'settings.maintenance.backup_retention_days' => 'required|integer|min:1|max:365',
            'settings.maintenance.backup_storage_path' => 'nullable|string|max:255',
            'settings.maintenance.log_level' => 'required|string|in:debug,info,notice,warning,error,critical,alert,emergency',
            'settings.maintenance.log_retention_days' => 'required|integer|min:1|max:90',
            'settings.maintenance.cache_driver' => 'required|string|in:file,redis,memcached,array',
            'settings.maintenance.cache_ttl' => 'required|integer|min:60|max:86400',
            'settings.maintenance.maintenance_mode' => 'required|boolean',
            'settings.maintenance.maintenance_message' => 'required_if:settings.maintenance.maintenance_mode,true|string|max:500',
            'settings.maintenance.monitoring_enabled' => 'required|boolean',
            'settings.maintenance.monitoring_interval' => 'required_if:settings.maintenance.monitoring_enabled,true|integer|min:60|max:3600',
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

    public function boot(ConfigurationService $configurationService, BackupService $backupService)
    {
        $this->configurationService = $configurationService;
        $this->backupService = $backupService;
    }

    public function mount()
    {
        $this->loadSettings();
        $this->initializeStorageValidation();
    }

    public function loadSettings()
    {
        $this->settings = $this->configurationService->getSettingsByCategory('maintenance');
        $this->originalSettings = $this->settings;
        
        // 確保所有必要的設定都存在，如果不存在則設定預設值
        $defaultSettings = [
            'maintenance.auto_backup_enabled' => true,
            'maintenance.backup_frequency' => 'daily',
            'maintenance.backup_retention_days' => 30,
            'maintenance.backup_storage_path' => '',
            'maintenance.log_level' => 'info',
            'maintenance.log_retention_days' => 14,
            'maintenance.cache_driver' => 'redis',
            'maintenance.cache_ttl' => 3600,
            'maintenance.maintenance_mode' => false,
            'maintenance.maintenance_message' => '系統正在進行維護，請稍後再試。',
            'maintenance.monitoring_enabled' => true,
            'maintenance.monitoring_interval' => 300,
        ];
        
        foreach ($defaultSettings as $key => $defaultValue) {
            if (!array_key_exists($key, $this->settings)) {
                $this->settings[$key] = $defaultValue;
            }
        }
        
        // 檢查維護模式變更警告
        $this->showMaintenanceWarning = $this->settings['maintenance.maintenance_mode'] ?? false;
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
        $this->validate();

        try {
            // 驗證儲存位置
            if ($this->settings['maintenance.auto_backup_enabled']) {
                $this->validateBackupStorage();
            }

            // 驗證快取連線
            $this->validateCacheConnection();

            // 如果啟用維護模式，顯示警告
            if ($this->settings['maintenance.maintenance_mode'] && !$this->originalSettings['maintenance.maintenance_mode']) {
                $this->showMaintenanceWarning = true;
                $this->dispatch('maintenance-mode-warning', [
                    'message' => '啟用維護模式將阻止一般使用者存取系統，請確認您要繼續。'
                ]);
                return;
            }

            $this->configurationService->updateSettings($this->settings);
            $this->loadSettings();
            
            // 如果變更了快取設定，清除快取
            if ($this->originalSettings['maintenance.cache_driver'] !== $this->settings['maintenance.cache_driver']) {
                $this->clearCache();
            }
            
            $this->dispatch('saved', [
                'type' => 'success',
                'message' => '維護設定已成功儲存！'
            ]);

        } catch (\Exception $e) {
            Log::error('儲存維護設定失敗', [
                'error' => $e->getMessage(),
                'settings' => $this->settings,
            ]);
            
            $this->dispatch('saved', [
                'type' => 'error',
                'message' => '儲存維護設定時發生錯誤：' . $e->getMessage()
            ]);
        }
    }

    /**
     * 驗證備份儲存位置
     */
    public function validateBackupStorage()
    {
        $backupPath = $this->settings['maintenance.backup_storage_path'] ?? storage_path('backups');
        
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
        $driver = $this->settings['maintenance.cache_driver'];
        
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
            Artisan::call('cache:clear');
            Artisan::call('config:clear');
            Artisan::call('route:clear');
            Artisan::call('view:clear');
            
            $this->dispatch('cache-cleared', [
                'type' => 'success',
                'message' => '快取已成功清除'
            ]);
            
        } catch (\Exception $e) {
            Log::error('清除快取失敗', ['error' => $e->getMessage()]);
            
            $this->dispatch('cache-cleared', [
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
            $this->configurationService->updateSettings($this->settings);
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
        $this->settings['maintenance.maintenance_mode'] = false;
        $this->showMaintenanceWarning = false;
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
        return view('livewire.admin.settings.maintenance-settings')
            ->layout('layouts.admin');
    }
}
