<?php

namespace App\Repositories;

use App\Events\SettingUpdated;
use App\Events\SettingsBatchUpdated;
use App\Events\SettingCacheCleared;
use App\Models\Setting;
use App\Models\SettingBackup;
use App\Models\SettingChange;
use App\Models\SettingPerformanceMetric;
use App\Models\SettingCache;
use App\Services\EncryptionService;
use App\Services\SettingsCacheService;
use App\Services\SettingsPerformanceService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

/**
 * 設定資料存取實作
 */
class SettingsRepository implements SettingsRepositoryInterface
{
    /**
     * 快取前綴
     */
    private const CACHE_PREFIX = 'settings_';
    
    /**
     * 預設快取時間（秒）
     */
    private const DEFAULT_CACHE_TTL = 3600;

    /**
     * 加密服務
     */
    protected EncryptionService $encryptionService;

    /**
     * 快取服務
     */
    protected ?SettingsCacheService $cacheService = null;

    /**
     * 效能服務
     */
    protected ?SettingsPerformanceService $performanceService = null;

    /**
     * 建構函式
     */
    public function __construct(EncryptionService $encryptionService)
    {
        $this->encryptionService = $encryptionService;
    }

    /**
     * 設定快取服務
     */
    public function setCacheService(SettingsCacheService $cacheService): void
    {
        $this->cacheService = $cacheService;
    }

    /**
     * 設定效能服務
     */
    public function setPerformanceService(SettingsPerformanceService $performanceService): void
    {
        $this->performanceService = $performanceService;
    }

    /**
     * 取得所有設定
     * 
     * @return Collection
     */
    public function getAllSettings(): Collection
    {
        return Cache::remember(self::CACHE_PREFIX . 'all', self::DEFAULT_CACHE_TTL, function () {
            return Setting::orderBy('category')
                          ->orderBy('sort_order')
                          ->orderBy('key')
                          ->get();
        });
    }

    /**
     * 取得按分類分組的設定
     * 
     * @param string|null $category 分類名稱，null 表示所有分類
     * @return Collection
     */
    public function getSettingsByCategory(?string $category = null): Collection
    {
        if ($category) {
            return Cache::remember(self::CACHE_PREFIX . "category_{$category}", self::DEFAULT_CACHE_TTL, function () use ($category) {
                return Setting::byCategory($category)
                              ->orderBy('sort_order')
                              ->orderBy('key')
                              ->get();
            });
        }

        return Cache::remember(self::CACHE_PREFIX . 'by_category', self::DEFAULT_CACHE_TTL, function () {
            return Setting::orderBy('category')
                          ->orderBy('sort_order')
                          ->orderBy('key')
                          ->get()
                          ->groupBy('category');
        });
    }

    /**
     * 根據鍵值取得設定
     * 
     * @param string $key 設定鍵值
     * @return Setting|null
     */
    public function getSetting(string $key): ?Setting
    {
        $startTime = microtime(true);
        
        // 如果有快取服務，使用多層快取
        if ($this->cacheService) {
            $setting = $this->cacheService->get($key);
            
            if ($setting === null) {
                $setting = Setting::where('key', $key)->first();
                if ($setting) {
                    $this->cacheService->set($key, $setting);
                }
                
                // 記錄快取未命中
                $this->recordCacheMetric('cache_miss', $startTime);
            } else {
                // 記錄快取命中
                $this->recordCacheMetric('cache_hit', $startTime);
            }
            
            return $setting;
        }

        // 回退到原始快取實作
        return Cache::remember(self::CACHE_PREFIX . "key_{$key}", self::DEFAULT_CACHE_TTL, function () use ($key) {
            return Setting::where('key', $key)->first();
        });
    }

    /**
     * 批量取得設定
     * 
     * @param array $keys 設定鍵值陣列
     * @return Collection
     */
    public function getSettings(array $keys): Collection
    {
        if (empty($keys)) {
            return collect();
        }

        sort($keys);
        $cacheKey = self::CACHE_PREFIX . 'batch_' . md5(implode(',', $keys));
        
        return Cache::remember($cacheKey, self::DEFAULT_CACHE_TTL, function () use ($keys) {
            return Setting::whereIn('key', $keys)
                          ->orderBy('category')
                          ->orderBy('sort_order')
                          ->get()
                          ->keyBy('key');
        });
    }

    /**
     * 更新設定
     * 
     * @param string $key 設定鍵值
     * @param mixed $value 設定值
     * @return bool
     */
    public function updateSetting(string $key, $value): bool
    {
        $setting = $this->getSetting($key);
        
        if (!$setting) {
            return false;
        }

        // 驗證設定值
        if (!$this->validateSetting($key, $value)) {
            return false;
        }

        // 處理加密
        if ($this->shouldEncryptSetting($key)) {
            try {
                $value = $this->encryptionService->encrypt($value);
            } catch (\Exception $e) {
                Log::error("Failed to encrypt setting {$key}", ['error' => $e->getMessage()]);
                return false;
            }
        }

        $result = $setting->updateValue($value);
        
        if ($result) {
            $this->clearCache($key);
        }

        return $result;
    }

    /**
     * 批量更新設定
     * 
     * @param array $settings 設定陣列 ['key' => 'value']
     * @return bool
     */
    public function updateSettings(array $settings): bool
    {
        if (empty($settings)) {
            return true;
        }

        // 如果有效能服務，使用優化的批量更新
        if ($this->performanceService) {
            $results = $this->performanceService->batchUpdateSettings($settings);
            return $results['success'];
        }

        // 回退到原始實作
        DB::beginTransaction();
        
        try {
            foreach ($settings as $key => $value) {
                if (!$this->updateSetting($key, $value)) {
                    throw new \Exception("無法更新設定: {$key}");
                }
            }
            
            DB::commit();
            $this->clearCache();
            
            // 觸發批量更新事件
            $affectedCategories = $this->getAffectedCategories(array_keys($settings));
            event(new SettingsBatchUpdated($settings, $affectedCategories));
            
            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('批量更新設定失敗', [
                'settings' => array_keys($settings),
                'error' => $e->getMessage(),
            ]);
            
            return false;
        }
    }

    /**
     * 取得受影響的分類
     * 
     * @param array $keys 設定鍵值陣列
     * @return array 分類陣列
     */
    protected function getAffectedCategories(array $keys): array
    {
        $categories = [];
        
        foreach ($keys as $key) {
            $setting = $this->getSetting($key);
            if ($setting && !in_array($setting->category, $categories)) {
                $categories[] = $setting->category;
            }
        }

        return $categories;
    }

    /**
     * 重設設定為預設值
     * 
     * @param string $key 設定鍵值
     * @return bool
     */
    public function resetSetting(string $key): bool
    {
        $setting = $this->getSetting($key);
        
        if (!$setting) {
            return false;
        }

        $result = $setting->resetToDefault();
        
        if ($result) {
            $this->clearCache($key);
        }

        return $result;
    }

    /**
     * 建立新設定
     * 
     * @param array $data 設定資料
     * @return Setting
     */
    public function createSetting(array $data): Setting
    {
        $setting = Setting::create($data);
        $this->clearCache();
        
        return $setting;
    }

    /**
     * 刪除設定
     * 
     * @param string $key 設定鍵值
     * @return bool
     */
    public function deleteSetting(string $key): bool
    {
        $setting = $this->getSetting($key);
        
        if (!$setting) {
            return false;
        }

        // 檢查是否為系統設定
        if ($setting->is_system) {
            return false;
        }

        $result = $setting->delete();
        
        if ($result) {
            $this->clearCache($key);
        }

        return $result;
    }

    /**
     * 取得已變更的設定
     * 
     * @return Collection
     */
    public function getChangedSettings(): Collection
    {
        return Cache::remember(self::CACHE_PREFIX . 'changed', 1800, function () {
            return Setting::changed()
                          ->orderBy('category')
                          ->orderBy('key')
                          ->get();
        });
    }

    /**
     * 搜尋設定
     * 
     * @param string $search 搜尋關鍵字
     * @param array $filters 篩選條件
     * @return Collection
     */
    public function searchSettings(string $search, array $filters = []): Collection
    {
        $cacheKey = self::CACHE_PREFIX . 'search_' . md5($search . serialize($filters));
        
        return Cache::remember($cacheKey, 900, function () use ($search, $filters) {
            $query = Setting::query();

            // 搜尋條件
            if (!empty($search)) {
                $query->where(function ($q) use ($search) {
                    $q->where('key', 'like', "%{$search}%")
                      ->orWhere('description', 'like', "%{$search}%");
                });
            }

            // 分類篩選
            if (!empty($filters['category'])) {
                $query->where('category', $filters['category']);
            }

            // 類型篩選
            if (!empty($filters['type'])) {
                $query->where('type', $filters['type']);
            }

            // 變更狀態篩選
            if (isset($filters['changed'])) {
                if ($filters['changed']) {
                    $query->changed();
                } else {
                    $query->whereRaw('JSON_EXTRACT(value, "$") = JSON_EXTRACT(default_value, "$")');
                }
            }

            // 系統設定篩選
            if (isset($filters['is_system'])) {
                $query->where('is_system', $filters['is_system']);
            }

            return $query->orderBy('category')
                         ->orderBy('sort_order')
                         ->orderBy('key')
                         ->get();
        });
    }

    /**
     * 匯出設定
     * 
     * @param array $categories 要匯出的分類，空陣列表示全部
     * @return array
     */
    public function exportSettings(array $categories = []): array
    {
        $query = Setting::query();

        if (!empty($categories)) {
            $query->whereIn('category', $categories);
        }

        $settings = $query->orderBy('category')
                          ->orderBy('sort_order')
                          ->orderBy('key')
                          ->get();

        return $settings->map(function ($setting) {
            return [
                'key' => $setting->key,
                'value' => $setting->value,
                'category' => $setting->category,
                'type' => $setting->type,
                'options' => $setting->options,
                'description' => $setting->description,
                'default_value' => $setting->default_value,
                'is_encrypted' => $setting->is_encrypted,
                'is_system' => $setting->is_system,
                'is_public' => $setting->is_public,
                'sort_order' => $setting->sort_order,
            ];
        })->toArray();
    }

    /**
     * 匯入設定
     * 
     * @param array $data 設定資料
     * @param array $options 匯入選項
     * @return array 匯入結果
     */
    public function importSettings(array $data, array $options = []): array
    {
        $results = [
            'success' => false,
            'created' => 0,
            'updated' => 0,
            'skipped' => 0,
            'errors' => [],
            'details' => [],
        ];

        $defaultOptions = [
            'conflict_resolution' => 'skip', // skip, update, merge
            'validate_data' => true,
            'dry_run' => false,
            'selected_keys' => [], // 僅匯入指定的設定鍵值
        ];
        
        $options = array_merge($defaultOptions, $options);

        // 如果指定了特定鍵值，只處理這些設定
        if (!empty($options['selected_keys'])) {
            $data = array_filter($data, function ($item) use ($options) {
                return in_array($item['key'], $options['selected_keys']);
            });
        }

        DB::beginTransaction();

        try {
            foreach ($data as $settingData) {
                $key = $settingData['key'] ?? 'unknown';
                
                try {
                    // 驗證資料格式
                    if ($options['validate_data'] && !$this->validateImportData($settingData)) {
                        $results['errors'][] = "無效的設定資料格式: {$key}";
                        $results['details'][$key] = ['status' => 'error', 'message' => '資料格式無效'];
                        continue;
                    }

                    // 驗證設定值
                    if ($options['validate_data'] && !$this->validateSettingValue($key, $settingData['value'])) {
                        $results['errors'][] = "設定值驗證失敗: {$key}";
                        $results['details'][$key] = ['status' => 'error', 'message' => '設定值不符合驗證規則'];
                        continue;
                    }

                    $existingSetting = Setting::where('key', $key)->first();

                    if ($existingSetting) {
                        // 處理衝突
                        switch ($options['conflict_resolution']) {
                            case 'skip':
                                $results['skipped']++;
                                $results['details'][$key] = ['status' => 'skipped', 'message' => '跳過現有設定'];
                                break;
                                
                            case 'update':
                                if (!$options['dry_run']) {
                                    $this->updateSettingFromImport($existingSetting, $settingData);
                                }
                                $results['updated']++;
                                $results['details'][$key] = ['status' => 'updated', 'message' => '覆蓋現有設定'];
                                break;
                                
                            case 'merge':
                                if (!$options['dry_run']) {
                                    $this->mergeSettingFromImport($existingSetting, $settingData);
                                }
                                $results['updated']++;
                                $results['details'][$key] = ['status' => 'merged', 'message' => '智慧合併設定'];
                                break;
                        }
                    } else {
                        // 建立新設定
                        if (!$options['dry_run']) {
                            $this->createSettingFromImport($settingData);
                        }
                        $results['created']++;
                        $results['details'][$key] = ['status' => 'created', 'message' => '建立新設定'];
                    }
                    
                } catch (\Exception $e) {
                    $results['errors'][] = "處理設定 {$key} 時發生錯誤: " . $e->getMessage();
                    $results['details'][$key] = ['status' => 'error', 'message' => $e->getMessage()];
                }
            }

            if ($options['dry_run']) {
                DB::rollBack();
            } else {
                DB::commit();
                $this->clearCache();
            }

            $results['success'] = empty($results['errors']);

        } catch (\Exception $e) {
            DB::rollBack();
            $results['errors'][] = $e->getMessage();
            Log::error('設定匯入失敗', [
                'error' => $e->getMessage(),
                'options' => $options,
            ]);
        }

        return $results;
    }

    /**
     * 從匯入資料更新設定
     * 
     * @param Setting $setting 現有設定
     * @param array $data 匯入資料
     * @return void
     */
    protected function updateSettingFromImport(Setting $setting, array $data): void
    {
        // 保留系統屬性，更新其他所有欄位
        $updateData = array_merge($data, [
            'is_system' => $setting->is_system,
            'is_encrypted' => $setting->is_encrypted,
        ]);
        
        // 移除不應該更新的欄位
        unset($updateData['exported_at'], $updateData['exported_by']);
        
        $setting->update($updateData);
    }

    /**
     * 智慧合併匯入設定
     * 
     * @param Setting $setting 現有設定
     * @param array $data 匯入資料
     * @return void
     */
    protected function mergeSettingFromImport(Setting $setting, array $data): void
    {
        // 智慧合併：只更新值和描述，保留其他屬性
        $mergeData = [
            'value' => $data['value'],
        ];
        
        // 如果描述不同且不為空，則更新描述
        if (!empty($data['description']) && $data['description'] !== $setting->description) {
            $mergeData['description'] = $data['description'];
        }
        
        // 如果選項不同，則更新選項
        if (isset($data['options']) && $data['options'] !== $setting->options) {
            $mergeData['options'] = $data['options'];
        }
        
        $setting->update($mergeData);
    }

    /**
     * 從匯入資料建立設定
     * 
     * @param array $data 匯入資料
     * @return Setting
     */
    protected function createSettingFromImport(array $data): Setting
    {
        // 移除匯出相關欄位
        unset($data['exported_at'], $data['exported_by']);
        
        // 設定預設值
        $data = array_merge([
            'is_system' => false,
            'is_encrypted' => false,
            'is_public' => true,
            'sort_order' => 0,
        ], $data);
        
        return Setting::create($data);
    }

    /**
     * 驗證設定值
     * 
     * @param string $key 設定鍵值
     * @param mixed $value 設定值
     * @return bool
     */
    protected function validateSettingValue(string $key, $value): bool
    {
        try {
            $configService = app(ConfigurationService::class);
            return $configService->validateSettingValue($key, $value);
        } catch (\Exception $e) {
            // 如果配置服務無法驗證，則進行基本驗證
            return $this->basicValidateSettingValue($key, $value);
        }
    }

    /**
     * 基本設定值驗證
     * 
     * @param string $key 設定鍵值
     * @param mixed $value 設定值
     * @return bool
     */
    protected function basicValidateSettingValue(string $key, $value): bool
    {
        // 基本驗證邏輯
        if ($value === null) {
            return false;
        }

        // 檢查是否為有效的 JSON（如果值是陣列或物件）
        if (is_array($value) || is_object($value)) {
            return json_encode($value) !== false;
        }

        return true;
    }

    /**
     * 驗證設定值
     * 
     * @param string $key 設定鍵值
     * @param mixed $value 設定值
     * @return bool
     */
    public function validateSetting(string $key, $value): bool
    {
        $setting = $this->getSetting($key);
        
        if (!$setting) {
            return false;
        }

        $rules = $setting->validation_rules;
        
        if (empty($rules)) {
            return true;
        }

        $validator = Validator::make(['value' => $value], ['value' => $rules]);
        
        return !$validator->fails();
    }

    /**
     * 取得設定的依賴關係
     * 
     * @param string $key 設定鍵值
     * @return array
     */
    public function getSettingDependencies(string $key): array
    {
        $setting = $this->getSetting($key);
        
        if (!$setting || !isset($setting->options['dependencies'])) {
            return [];
        }

        return $setting->options['dependencies'];
    }

    /**
     * 建立設定備份
     * 
     * @param string $name 備份名稱
     * @param string $description 備份描述
     * @param array $categories 要備份的分類，空陣列表示全部
     * @return SettingBackup
     */
    public function createBackup(string $name, string $description = '', array $categories = []): SettingBackup
    {
        $settingsData = $this->exportSettings($categories);
        
        return SettingBackup::create([
            'name' => $name,
            'description' => $description,
            'settings_data' => $settingsData,
            'created_by' => auth()->id(),
        ]);
    }

    /**
     * 還原設定備份
     * 
     * @param int $backupId 備份 ID
     * @return bool
     */
    public function restoreBackup(int $backupId): bool
    {
        $backup = SettingBackup::find($backupId);
        
        if (!$backup) {
            return false;
        }

        $result = $backup->restore();
        
        if ($result) {
            $this->clearCache();
        }

        return $result;
    }

    /**
     * 取得設定備份列表
     * 
     * @param int $limit 限制數量
     * @return Collection
     */
    public function getBackups(int $limit = 50): Collection
    {
        return SettingBackup::with('creator')
                           ->orderBy('created_at', 'desc')
                           ->limit($limit)
                           ->get();
    }

    /**
     * 刪除設定備份
     * 
     * @param int $backupId 備份 ID
     * @return bool
     */
    public function deleteBackup(int $backupId): bool
    {
        $backup = SettingBackup::find($backupId);
        
        if (!$backup) {
            return false;
        }

        return $backup->delete();
    }

    /**
     * 取得設定變更歷史
     * 
     * @param string|null $settingKey 設定鍵值，null 表示所有設定
     * @param int $limit 限制數量
     * @return Collection
     */
    public function getSettingChanges(?string $settingKey = null, int $limit = 100): Collection
    {
        $query = SettingChange::with(['setting', 'user']);

        if ($settingKey) {
            $query->bySetting($settingKey);
        }

        return $query->orderBy('created_at', 'desc')
                     ->limit($limit)
                     ->get();
    }

    /**
     * 取得所有可用的分類
     * 
     * @return Collection
     */
    public function getAvailableCategories(): Collection
    {
        return Cache::remember(self::CACHE_PREFIX . 'categories', self::DEFAULT_CACHE_TTL, function () {
            return Setting::select('category')
                          ->distinct()
                          ->orderBy('category')
                          ->pluck('category');
        });
    }

    /**
     * 取得所有可用的設定類型
     * 
     * @return Collection
     */
    public function getAvailableTypes(): Collection
    {
        return Cache::remember(self::CACHE_PREFIX . 'types', self::DEFAULT_CACHE_TTL, function () {
            return Setting::select('type')
                          ->distinct()
                          ->orderBy('type')
                          ->pluck('type');
        });
    }

    /**
     * 清除設定快取
     * 
     * @param string|null $key 特定設定鍵值，null 表示清除所有
     * @return void
     */
    public function clearCache(?string $key = null): void
    {
        if ($key) {
            Cache::forget(self::CACHE_PREFIX . "key_{$key}");
        } else {
            Cache::tags(['settings'])->flush();
            // 清除所有設定相關快取
            $patterns = [
                self::CACHE_PREFIX . 'all',
                self::CACHE_PREFIX . 'by_category',
                self::CACHE_PREFIX . 'changed',
                self::CACHE_PREFIX . 'categories',
                self::CACHE_PREFIX . 'types',
            ];
            
            foreach ($patterns as $pattern) {
                Cache::forget($pattern);
            }
        }
    }

    /**
     * 取得快取的設定值
     * 
     * @param string $key 設定鍵值
     * @param mixed $default 預設值
     * @return mixed
     */
    public function getCachedSetting(string $key, $default = null)
    {
        return Cache::remember(self::CACHE_PREFIX . "value_{$key}", self::DEFAULT_CACHE_TTL, function () use ($key, $default) {
            $setting = $this->getSetting($key);
            return $setting ? $setting->value : $default;
        });
    }

    /**
     * 設定快取的設定值
     * 
     * @param string $key 設定鍵值
     * @param mixed $value 設定值
     * @param int $ttl 快取時間（秒）
     * @return void
     */
    public function setCachedSetting(string $key, $value, int $ttl = 3600): void
    {
        Cache::put(self::CACHE_PREFIX . "value_{$key}", $value, $ttl);
    }

    /**
     * 驗證匯入資料格式
     * 
     * @param array $data 設定資料
     * @return bool
     */
    protected function validateImportData(array $data): bool
    {
        $required = ['key', 'value', 'category', 'type'];
        
        foreach ($required as $field) {
            if (!isset($data[$field])) {
                return false;
            }
        }

        return true;
    }

    /**
     * 檢查設定是否需要加密
     * 
     * @param string $key 設定鍵值
     * @return bool
     */
    protected function shouldEncryptSetting(string $key): bool
    {
        // 從配置檔案中取得需要加密的設定
        $encryptedSettings = config('system-settings.settings', []);
        
        if (isset($encryptedSettings[$key]['encrypted']) && $encryptedSettings[$key]['encrypted']) {
            return true;
        }

        // 根據鍵值模式判斷
        $encryptPatterns = [
            '*_secret*',
            '*_password*',
            '*_key*',
            '*_token*',
            '*client_secret*',
            '*webhook_secret*',
            '*api_keys*',
        ];

        foreach ($encryptPatterns as $pattern) {
            if (fnmatch($pattern, $key)) {
                return true;
            }
        }

        return false;
    }

    /**
     * 解密設定值
     * 
     * @param string $key 設定鍵值
     * @param mixed $value 設定值
     * @return mixed
     */
    public function decryptSettingValue(string $key, $value)
    {
        if (!$this->shouldEncryptSetting($key) || empty($value)) {
            return $value;
        }

        try {
            return $this->encryptionService->decrypt($value);
        } catch (\Exception $e) {
            Log::warning("Failed to decrypt setting {$key}, returning original value", [
                'error' => $e->getMessage()
            ]);
            return $value;
        }
    }

    /**
     * 取得解密後的設定值
     * 
     * @param string $key 設定鍵值
     * @param mixed $default 預設值
     * @return mixed
     */
    public function getDecryptedSetting(string $key, $default = null)
    {
        $setting = $this->getSetting($key);
        
        if (!$setting) {
            return $default;
        }

        return $this->decryptSettingValue($key, $setting->value);
    }

    /**
     * 批量取得解密後的設定
     * 
     * @param array $keys 設定鍵值陣列
     * @return Collection
     */
    public function getDecryptedSettings(array $keys): Collection
    {
        $settings = $this->getSettings($keys);
        
        return $settings->map(function ($setting) {
            $decryptedValue = $this->decryptSettingValue($setting->key, $setting->value);
            $setting->decrypted_value = $decryptedValue;
            return $setting;
        });
    }

    /**
     * 安全地顯示敏感設定值
     * 
     * @param string $key 設定鍵值
     * @param mixed $value 設定值
     * @return string
     */
    public function maskSensitiveValue(string $key, $value): string
    {
        if (!$this->shouldEncryptSetting($key) || empty($value)) {
            return (string) $value;
        }

        // 先解密再遮罩
        $decryptedValue = $this->decryptSettingValue($key, $value);
        return $this->encryptionService->maskSensitiveData($decryptedValue);
    }

    /**
     * 記錄快取效能指標
     * 
     * @param string $operation 操作名稱
     * @param float $startTime 開始時間
     * @return void
     */
    protected function recordCacheMetric(string $operation, float $startTime): void
    {
        try {
            $executionTime = (microtime(true) - $startTime) * 1000;
            SettingPerformanceMetric::record('cache', $operation, $executionTime, 'ms');
        } catch (\Exception $e) {
            // 效能指標記錄失敗不應影響主要功能
            Log::debug('記錄快取效能指標失敗', [
                'operation' => $operation,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * 延遲載入設定
     * 
     * @param string $category 分類
     * @param callable|null $callback 載入完成回調
     * @return \Generator
     */
    public function lazyLoadSettingsByCategory(string $category, ?callable $callback = null): \Generator
    {
        if ($this->performanceService) {
            yield from $this->performanceService->lazyLoadSettingsByCategory($category, $callback);
        } else {
            // 回退到簡單的分批載入
            $offset = 0;
            $limit = 50;
            
            do {
                $settings = Setting::byCategory($category)
                    ->orderBy('sort_order')
                    ->orderBy('key')
                    ->offset($offset)
                    ->limit($limit)
                    ->get();

                foreach ($settings as $setting) {
                    yield $setting;
                }

                if ($callback && $settings->isNotEmpty()) {
                    $callback($settings, $offset, $offset + $settings->count());
                }

                $offset += $limit;
            } while ($settings->count() === $limit);
        }
    }

    /**
     * 批量載入設定（優化版本）
     * 
     * @param array $keys 設定鍵值陣列
     * @param bool $useCache 是否使用快取
     * @return Collection
     */
    public function batchLoadSettings(array $keys, bool $useCache = true): Collection
    {
        if ($this->performanceService) {
            return $this->performanceService->batchLoadSettings($keys, $useCache);
        }

        // 回退到原始實作
        return $this->getSettings($keys);
    }
}