<?php

namespace App\Repositories;

use App\Models\Setting;
use App\Models\SettingBackup;
use Illuminate\Support\Collection;

/**
 * 設定資料存取介面
 */
interface SettingsRepositoryInterface
{
    /**
     * 取得所有設定
     * 
     * @return Collection
     */
    public function getAllSettings(): Collection;

    /**
     * 取得按分類分組的設定
     * 
     * @param string|null $category 分類名稱，null 表示所有分類
     * @return Collection
     */
    public function getSettingsByCategory(?string $category = null): Collection;

    /**
     * 根據鍵值取得設定
     * 
     * @param string $key 設定鍵值
     * @return Setting|null
     */
    public function getSetting(string $key): ?Setting;

    /**
     * 批量取得設定
     * 
     * @param array $keys 設定鍵值陣列
     * @return Collection
     */
    public function getSettings(array $keys): Collection;

    /**
     * 更新設定
     * 
     * @param string $key 設定鍵值
     * @param mixed $value 設定值
     * @return bool
     */
    public function updateSetting(string $key, $value): bool;

    /**
     * 批量更新設定
     * 
     * @param array $settings 設定陣列 ['key' => 'value']
     * @return bool
     */
    public function updateSettings(array $settings): bool;

    /**
     * 重設設定為預設值
     * 
     * @param string $key 設定鍵值
     * @return bool
     */
    public function resetSetting(string $key): bool;

    /**
     * 建立新設定
     * 
     * @param array $data 設定資料
     * @return Setting
     */
    public function createSetting(array $data): Setting;

    /**
     * 刪除設定
     * 
     * @param string $key 設定鍵值
     * @return bool
     */
    public function deleteSetting(string $key): bool;

    /**
     * 取得已變更的設定
     * 
     * @return Collection
     */
    public function getChangedSettings(): Collection;

    /**
     * 搜尋設定
     * 
     * @param string $search 搜尋關鍵字
     * @param array $filters 篩選條件
     * @return Collection
     */
    public function searchSettings(string $search, array $filters = []): Collection;

    /**
     * 匯出設定
     * 
     * @param array $categories 要匯出的分類，空陣列表示全部
     * @return array
     */
    public function exportSettings(array $categories = []): array;

    /**
     * 匯入設定
     * 
     * @param array $data 設定資料
     * @param array $options 匯入選項
     * @return array 匯入結果
     */
    public function importSettings(array $data, array $options = []): array;

    /**
     * 驗證設定值
     * 
     * @param string $key 設定鍵值
     * @param mixed $value 設定值
     * @return bool
     */
    public function validateSetting(string $key, $value): bool;

    /**
     * 取得設定的依賴關係
     * 
     * @param string $key 設定鍵值
     * @return array
     */
    public function getSettingDependencies(string $key): array;

    /**
     * 建立設定備份
     * 
     * @param string $name 備份名稱
     * @param string $description 備份描述
     * @param array $categories 要備份的分類，空陣列表示全部
     * @return SettingBackup
     */
    public function createBackup(string $name, string $description = '', array $categories = []): SettingBackup;

    /**
     * 還原設定備份
     * 
     * @param int $backupId 備份 ID
     * @return bool
     */
    public function restoreBackup(int $backupId): bool;

    /**
     * 取得設定備份列表
     * 
     * @param int $limit 限制數量
     * @return Collection
     */
    public function getBackups(int $limit = 50): Collection;

    /**
     * 刪除設定備份
     * 
     * @param int $backupId 備份 ID
     * @return bool
     */
    public function deleteBackup(int $backupId): bool;

    /**
     * 取得設定變更歷史
     * 
     * @param string|null $settingKey 設定鍵值，null 表示所有設定
     * @param int $limit 限制數量
     * @return Collection
     */
    public function getSettingChanges(?string $settingKey = null, int $limit = 100): Collection;

    /**
     * 取得所有可用的分類
     * 
     * @return Collection
     */
    public function getAvailableCategories(): Collection;

    /**
     * 取得所有可用的設定類型
     * 
     * @return Collection
     */
    public function getAvailableTypes(): Collection;

    /**
     * 清除設定快取
     * 
     * @param string|null $key 特定設定鍵值，null 表示清除所有
     * @return void
     */
    public function clearCache(?string $key = null): void;

    /**
     * 取得快取的設定值
     * 
     * @param string $key 設定鍵值
     * @param mixed $default 預設值
     * @return mixed
     */
    public function getCachedSetting(string $key, $default = null);

    /**
     * 設定快取的設定值
     * 
     * @param string $key 設定鍵值
     * @param mixed $value 設定值
     * @param int $ttl 快取時間（秒）
     * @return void
     */
    public function setCachedSetting(string $key, $value, int $ttl = 3600): void;

    /**
     * 解密設定值
     * 
     * @param string $key 設定鍵值
     * @param mixed $value 設定值
     * @return mixed
     */
    public function decryptSettingValue(string $key, $value);

    /**
     * 取得解密後的設定值
     * 
     * @param string $key 設定鍵值
     * @param mixed $default 預設值
     * @return mixed
     */
    public function getDecryptedSetting(string $key, $default = null);

    /**
     * 批量取得解密後的設定
     * 
     * @param array $keys 設定鍵值陣列
     * @return Collection
     */
    public function getDecryptedSettings(array $keys): Collection;

    /**
     * 安全地顯示敏感設定值
     * 
     * @param string $key 設定鍵值
     * @param mixed $value 設定值
     * @return string
     */
    public function maskSensitiveValue(string $key, $value): string;
}