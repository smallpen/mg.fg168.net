<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

/**
 * 語言檔案快取服務
 * 
 * 提供高效能的語言檔案載入和快取機制
 */
class LanguageFileCache
{
    /**
     * 快取前綴
     */
    private const CACHE_PREFIX = 'lang_file_cache';
    
    /**
     * 快取版本前綴
     */
    private const VERSION_PREFIX = 'lang_file_version';
    
    /**
     * 多語系日誌記錄器
     */
    private MultilingualLogger $logger;
    
    /**
     * 快取 TTL（秒）
     */
    private int $cacheTtl;
    
    /**
     * 是否啟用快取
     */
    private bool $cacheEnabled;

    /**
     * 建構函數
     */
    public function __construct(MultilingualLogger $logger)
    {
        $this->logger = $logger;
        $this->cacheTtl = config('multilingual.performance.cache.ttl', 3600);
        $this->cacheEnabled = config('multilingual.performance.cache.enabled', true);
    }

    /**
     * 載入語言檔案（帶快取）
     *
     * @param string $locale 語言代碼
     * @param string $group 語言檔案群組
     * @return array 語言資料
     */
    public function loadLanguageFile(string $locale, string $group): array
    {
        $startTime = microtime(true);
        
        if (!$this->cacheEnabled) {
            $data = $this->loadFromFile($locale, $group);
            $this->logPerformance($locale, $group, $startTime, false);
            return $data;
        }

        $cacheKey = $this->getCacheKey($locale, $group);
        $versionKey = $this->getVersionKey($locale, $group);
        
        // 檢查快取是否存在且有效
        $cachedData = Cache::get($cacheKey);
        $cachedVersion = Cache::get($versionKey);
        $currentVersion = $this->getFileVersion($locale, $group);
        
        if ($cachedData !== null && $cachedVersion === $currentVersion) {
            $this->logPerformance($locale, $group, $startTime, true);
            return $cachedData;
        }
        
        // 載入檔案並快取
        $data = $this->loadFromFile($locale, $group);
        
        if (!empty($data)) {
            Cache::put($cacheKey, $data, $this->cacheTtl);
            Cache::put($versionKey, $currentVersion, $this->cacheTtl);
        }
        
        $this->logPerformance($locale, $group, $startTime, false);
        
        return $data;
    }

    /**
     * 從檔案載入語言資料
     *
     * @param string $locale 語言代碼
     * @param string $group 語言檔案群組
     * @return array 語言資料
     */
    private function loadFromFile(string $locale, string $group): array
    {
        $filePath = $this->getFilePath($locale, $group);
        
        if (!File::exists($filePath)) {
            $this->logger->logLanguageFileLoadError(
                $group,
                $locale,
                "Language file not found: {$filePath}"
            );
            return [];
        }

        try {
            $data = include $filePath;
            
            if (!is_array($data)) {
                $this->logger->logLanguageFileLoadError(
                    $group,
                    $locale,
                    "Language file does not return array: {$filePath}"
                );
                return [];
            }
            
            return $data;
            
        } catch (\Exception $e) {
            $this->logger->logLanguageFileLoadError(
                $group,
                $locale,
                "Error loading language file: {$e->getMessage()}",
                ['file_path' => $filePath, 'exception' => $e->getTraceAsString()]
            );
            return [];
        }
    }

    /**
     * 取得語言檔案路徑
     *
     * @param string $locale 語言代碼
     * @param string $group 語言檔案群組
     * @return string 檔案路徑
     */
    private function getFilePath(string $locale, string $group): string
    {
        return lang_path("{$locale}/{$group}.php");
    }

    /**
     * 取得快取鍵
     *
     * @param string $locale 語言代碼
     * @param string $group 語言檔案群組
     * @return string 快取鍵
     */
    private function getCacheKey(string $locale, string $group): string
    {
        return self::CACHE_PREFIX . ":{$locale}:{$group}";
    }

    /**
     * 取得版本快取鍵
     *
     * @param string $locale 語言代碼
     * @param string $group 語言檔案群組
     * @return string 版本快取鍵
     */
    private function getVersionKey(string $locale, string $group): string
    {
        return self::VERSION_PREFIX . ":{$locale}:{$group}";
    }

    /**
     * 取得檔案版本（基於修改時間）
     *
     * @param string $locale 語言代碼
     * @param string $group 語言檔案群組
     * @return string 檔案版本
     */
    private function getFileVersion(string $locale, string $group): string
    {
        $filePath = $this->getFilePath($locale, $group);
        
        if (!File::exists($filePath)) {
            return 'not_found';
        }
        
        return (string) File::lastModified($filePath);
    }

    /**
     * 記錄效能資訊
     *
     * @param string $locale 語言代碼
     * @param string $group 語言檔案群組
     * @param float $startTime 開始時間
     * @param bool $fromCache 是否來自快取
     * @return void
     */
    private function logPerformance(string $locale, string $group, float $startTime, bool $fromCache): void
    {
        $loadTime = (microtime(true) - $startTime) * 1000; // 轉換為毫秒
        
        $this->logger->logLanguageFilePerformance(
            $group,
            $locale,
            $loadTime,
            $fromCache
        );
    }

    /**
     * 預熱快取
     *
     * @param array $locales 語言列表
     * @param array $groups 語言檔案群組列表
     * @return array 預熱結果
     */
    public function warmupCache(array $locales = null, array $groups = null): array
    {
        $locales = $locales ?? array_keys(config('multilingual.supported_locales', ['zh_TW', 'en']));
        $groups = $groups ?? config('multilingual.validation.required_files', [
            'admin', 'auth', 'layout', 'validation', 'passwords', 'theme', 'permissions', 'settings', 'activity_logs'
        ]);

        $results = [
            'success' => 0,
            'failed' => 0,
            'details' => []
        ];

        foreach ($locales as $locale) {
            foreach ($groups as $group) {
                try {
                    $startTime = microtime(true);
                    $data = $this->loadLanguageFile($locale, $group);
                    $loadTime = (microtime(true) - $startTime) * 1000;
                    
                    if (!empty($data)) {
                        $results['success']++;
                        $results['details'][] = [
                            'locale' => $locale,
                            'group' => $group,
                            'status' => 'success',
                            'load_time_ms' => round($loadTime, 2),
                            'keys_count' => $this->countKeys($data)
                        ];
                    } else {
                        $results['failed']++;
                        $results['details'][] = [
                            'locale' => $locale,
                            'group' => $group,
                            'status' => 'empty_or_failed',
                            'load_time_ms' => round($loadTime, 2)
                        ];
                    }
                } catch (\Exception $e) {
                    $results['failed']++;
                    $results['details'][] = [
                        'locale' => $locale,
                        'group' => $group,
                        'status' => 'exception',
                        'error' => $e->getMessage()
                    ];
                }
            }
        }

        Log::channel('multilingual_performance')->info('Language cache warmup completed', $results);

        return $results;
    }

    /**
     * 清除語言檔案快取
     *
     * @param string|null $locale 特定語言（null 表示全部）
     * @param string|null $group 特定群組（null 表示全部）
     * @return int 清除的快取數量
     */
    public function clearCache(?string $locale = null, ?string $group = null): int
    {
        $cleared = 0;
        
        if ($locale && $group) {
            // 清除特定語言檔案快取
            $cacheKey = $this->getCacheKey($locale, $group);
            $versionKey = $this->getVersionKey($locale, $group);
            
            if (Cache::forget($cacheKey)) $cleared++;
            if (Cache::forget($versionKey)) $cleared++;
            
        } else {
            // 清除所有語言檔案快取
            $locales = array_keys(config('multilingual.supported_locales', ['zh_TW', 'en']));
            $groups = config('multilingual.validation.required_files', [
                'admin', 'auth', 'layout', 'validation', 'passwords', 'theme', 'permissions', 'settings', 'activity_logs'
            ]);

            foreach ($locales as $localeItem) {
                if ($locale && $localeItem !== $locale) continue;
                
                foreach ($groups as $groupItem) {
                    if ($group && $groupItem !== $group) continue;
                    
                    $cacheKey = $this->getCacheKey($localeItem, $groupItem);
                    $versionKey = $this->getVersionKey($localeItem, $groupItem);
                    
                    if (Cache::forget($cacheKey)) $cleared++;
                    if (Cache::forget($versionKey)) $cleared++;
                }
            }
        }

        Log::channel('multilingual_performance')->info('Language cache cleared', [
            'cleared_count' => $cleared,
            'locale' => $locale,
            'group' => $group
        ]);

        return $cleared;
    }

    /**
     * 取得快取統計資訊
     *
     * @return array 快取統計
     */
    public function getCacheStats(): array
    {
        $locales = array_keys(config('multilingual.supported_locales', ['zh_TW', 'en']));
        $groups = config('multilingual.validation.required_files', [
            'admin', 'auth', 'layout', 'validation', 'passwords', 'theme', 'permissions', 'settings', 'activity_logs'
        ]);

        $stats = [
            'cache_enabled' => $this->cacheEnabled,
            'cache_ttl' => $this->cacheTtl,
            'total_possible' => count($locales) * count($groups),
            'cached_files' => 0,
            'cache_hit_rate' => 0,
            'details' => []
        ];

        $cached = 0;
        foreach ($locales as $locale) {
            foreach ($groups as $group) {
                $cacheKey = $this->getCacheKey($locale, $group);
                $isCached = Cache::has($cacheKey);
                
                if ($isCached) {
                    $cached++;
                }
                
                $stats['details'][] = [
                    'locale' => $locale,
                    'group' => $group,
                    'cached' => $isCached
                ];
            }
        }

        $stats['cached_files'] = $cached;
        $stats['cache_hit_rate'] = $stats['total_possible'] > 0 
            ? round(($cached / $stats['total_possible']) * 100, 2) 
            : 0;

        return $stats;
    }

    /**
     * 計算陣列中的鍵數量（遞迴）
     *
     * @param array $array 要計算的陣列
     * @return int 鍵的總數
     */
    private function countKeys(array $array): int
    {
        $count = 0;
        
        foreach ($array as $value) {
            $count++;
            if (is_array($value)) {
                $count += $this->countKeys($value);
            }
        }
        
        return $count;
    }
}