<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cache;

/**
 * 多語系日誌記錄服務
 * 
 * 專門處理多語系相關的日誌記錄，包括缺少翻譯鍵、語言切換失敗等事件
 */
class MultilingualLogger
{
    /**
     * 日誌頻道名稱
     */
    private const CHANNEL = 'multilingual';
    private const ERROR_CHANNEL = 'multilingual_errors';
    private const PERFORMANCE_CHANNEL = 'multilingual_performance';

    /**
     * 記錄缺少翻譯鍵的錯誤
     *
     * @param string $key 翻譯鍵
     * @param string|null $locale 語言代碼
     * @param array $context 額外上下文
     * @return void
     */
    public function logMissingTranslationKey(string $key, ?string $locale = null, array $context = []): void
    {
        $locale = $locale ?? App::getLocale();
        
        $logContext = array_merge([
            'type' => 'missing_translation_key',
            'translation_key' => $key,
            'locale' => $locale,
            'url' => request()->fullUrl(),
            'method' => request()->method(),
            'route' => request()->route()?->getName(),
            'user_id' => auth()->id(),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'referer' => request()->header('referer'),
            'session_id' => session()->getId(),
        ], $context);

        Log::channel(self::ERROR_CHANNEL)->warning('Missing translation key detected', $logContext);
        
        // 增加統計計數
        $this->incrementMissingKeyCounter($key, $locale);
    }

    /**
     * 記錄語言切換失敗事件
     *
     * @param string $targetLocale 目標語言
     * @param string $reason 失敗原因
     * @param array $context 額外上下文
     * @return void
     */
    public function logLanguageSwitchFailure(string $targetLocale, string $reason, array $context = []): void
    {
        $logContext = array_merge([
            'type' => 'language_switch_failure',
            'target_locale' => $targetLocale,
            'current_locale' => App::getLocale(),
            'failure_reason' => $reason,
            'url' => request()->fullUrl(),
            'method' => request()->method(),
            'user_id' => auth()->id(),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'session_id' => session()->getId(),
        ], $context);

        Log::channel(self::ERROR_CHANNEL)->error('Language switch failed', $logContext);
    }

    /**
     * 記錄語言檔案載入問題
     *
     * @param string $file 語言檔案名稱
     * @param string $locale 語言代碼
     * @param string $error 錯誤訊息
     * @param array $context 額外上下文
     * @return void
     */
    public function logLanguageFileLoadError(string $file, string $locale, string $error, array $context = []): void
    {
        $logContext = array_merge([
            'type' => 'language_file_load_error',
            'language_file' => $file,
            'locale' => $locale,
            'error_message' => $error,
            'file_path' => lang_path("{$locale}/{$file}.php"),
            'file_exists' => file_exists(lang_path("{$locale}/{$file}.php")),
            'url' => request()->fullUrl(),
            'user_id' => auth()->id(),
        ], $context);

        Log::channel(self::ERROR_CHANNEL)->error('Language file load error', $logContext);
    }

    /**
     * 記錄語言回退使用情況
     *
     * @param string $key 翻譯鍵
     * @param string $originalLocale 原始語言
     * @param string $fallbackLocale 回退語言
     * @param array $context 額外上下文
     * @return void
     */
    public function logLanguageFallback(string $key, string $originalLocale, string $fallbackLocale, array $context = []): void
    {
        $logContext = array_merge([
            'type' => 'language_fallback_used',
            'translation_key' => $key,
            'original_locale' => $originalLocale,
            'fallback_locale' => $fallbackLocale,
            'url' => request()->fullUrl(),
            'route' => request()->route()?->getName(),
            'user_id' => auth()->id(),
        ], $context);

        Log::channel(self::CHANNEL)->info('Language fallback used', $logContext);
        
        // 增加回退使用統計
        $this->incrementFallbackCounter($originalLocale, $fallbackLocale);
    }

    /**
     * 記錄語言切換成功事件
     *
     * @param string $fromLocale 原語言
     * @param string $toLocale 目標語言
     * @param string $source 切換來源（url, session, user_preference, browser）
     * @param array $context 額外上下文
     * @return void
     */
    public function logLanguageSwitchSuccess(string $fromLocale, string $toLocale, string $source, array $context = []): void
    {
        $logContext = array_merge([
            'type' => 'language_switch_success',
            'from_locale' => $fromLocale,
            'to_locale' => $toLocale,
            'switch_source' => $source,
            'url' => request()->fullUrl(),
            'user_id' => auth()->id(),
            'ip_address' => request()->ip(),
            'session_id' => session()->getId(),
        ], $context);

        Log::channel(self::CHANNEL)->info('Language switched successfully', $logContext);
        
        // 增加語言切換統計
        $this->incrementLanguageSwitchCounter($fromLocale, $toLocale, $source);
    }

    /**
     * 記錄語言檔案效能資訊
     *
     * @param string $file 語言檔案名稱
     * @param string $locale 語言代碼
     * @param float $loadTime 載入時間（毫秒）
     * @param bool $fromCache 是否來自快取
     * @param array $context 額外上下文
     * @return void
     */
    public function logLanguageFilePerformance(string $file, string $locale, float $loadTime, bool $fromCache, array $context = []): void
    {
        $logContext = array_merge([
            'type' => 'language_file_performance',
            'language_file' => $file,
            'locale' => $locale,
            'load_time_ms' => round($loadTime, 2),
            'from_cache' => $fromCache,
            'url' => request()->fullUrl(),
        ], $context);

        // 只記錄載入時間超過閾值的情況
        $threshold = config('multilingual.performance_log_threshold', 100); // 預設 100ms
        
        if ($loadTime > $threshold) {
            Log::channel(self::PERFORMANCE_CHANNEL)->warning('Slow language file load', $logContext);
        } else {
            Log::channel(self::PERFORMANCE_CHANNEL)->debug('Language file loaded', $logContext);
        }
    }

    /**
     * 記錄翻譯參數錯誤
     *
     * @param string $key 翻譯鍵
     * @param array $expectedParams 預期參數
     * @param array $actualParams 實際參數
     * @param array $context 額外上下文
     * @return void
     */
    public function logTranslationParameterError(string $key, array $expectedParams, array $actualParams, array $context = []): void
    {
        $logContext = array_merge([
            'type' => 'translation_parameter_error',
            'translation_key' => $key,
            'expected_parameters' => $expectedParams,
            'actual_parameters' => array_keys($actualParams),
            'missing_parameters' => array_diff($expectedParams, array_keys($actualParams)),
            'extra_parameters' => array_diff(array_keys($actualParams), $expectedParams),
            'locale' => App::getLocale(),
            'url' => request()->fullUrl(),
            'user_id' => auth()->id(),
        ], $context);

        Log::channel(self::ERROR_CHANNEL)->warning('Translation parameter mismatch', $logContext);
    }

    /**
     * 記錄語言偏好設定更新
     *
     * @param string $locale 新語言設定
     * @param string $updateSource 更新來源（user_action, middleware, system）
     * @param array $context 額外上下文
     * @return void
     */
    public function logLanguagePreferenceUpdate(string $locale, string $updateSource, array $context = []): void
    {
        $logContext = array_merge([
            'type' => 'language_preference_update',
            'new_locale' => $locale,
            'previous_locale' => App::getLocale(),
            'update_source' => $updateSource,
            'user_id' => auth()->id(),
            'ip_address' => request()->ip(),
            'session_id' => session()->getId(),
        ], $context);

        Log::channel(self::CHANNEL)->info('Language preference updated', $logContext);
    }

    /**
     * 增加缺少翻譯鍵的統計計數
     *
     * @param string $key 翻譯鍵
     * @param string $locale 語言代碼
     * @return void
     */
    private function incrementMissingKeyCounter(string $key, string $locale): void
    {
        $cacheKey = "multilingual_stats:missing_keys:{$locale}:" . md5($key);
        $dailyKey = "multilingual_stats:missing_keys_daily:" . now()->format('Y-m-d');
        
        Cache::increment($cacheKey, 1);
        Cache::increment($dailyKey, 1);
        
        // 設定過期時間為 7 天
        Cache::put($cacheKey, Cache::get($cacheKey, 0), now()->addDays(7));
        Cache::put($dailyKey, Cache::get($dailyKey, 0), now()->addDay());
    }

    /**
     * 增加語言回退使用統計
     *
     * @param string $originalLocale 原始語言
     * @param string $fallbackLocale 回退語言
     * @return void
     */
    private function incrementFallbackCounter(string $originalLocale, string $fallbackLocale): void
    {
        $cacheKey = "multilingual_stats:fallback:{$originalLocale}:{$fallbackLocale}";
        $dailyKey = "multilingual_stats:fallback_daily:" . now()->format('Y-m-d');
        
        Cache::increment($cacheKey, 1);
        Cache::increment($dailyKey, 1);
        
        // 設定過期時間為 30 天
        Cache::put($cacheKey, Cache::get($cacheKey, 0), now()->addDays(30));
        Cache::put($dailyKey, Cache::get($dailyKey, 0), now()->addDay());
    }

    /**
     * 增加語言切換統計
     *
     * @param string $fromLocale 原語言
     * @param string $toLocale 目標語言
     * @param string $source 切換來源
     * @return void
     */
    private function incrementLanguageSwitchCounter(string $fromLocale, string $toLocale, string $source): void
    {
        $cacheKey = "multilingual_stats:switch:{$fromLocale}:{$toLocale}:{$source}";
        $dailyKey = "multilingual_stats:switch_daily:" . now()->format('Y-m-d');
        
        Cache::increment($cacheKey, 1);
        Cache::increment($dailyKey, 1);
        
        // 設定過期時間為 30 天
        Cache::put($cacheKey, Cache::get($cacheKey, 0), now()->addDays(30));
        Cache::put($dailyKey, Cache::get($dailyKey, 0), now()->addDay());
    }

    /**
     * 取得多語系統計資訊
     *
     * @return array 統計資訊
     */
    public function getStatistics(): array
    {
        $today = now()->format('Y-m-d');
        
        return [
            'daily_stats' => [
                'missing_keys' => Cache::get("multilingual_stats:missing_keys_daily:{$today}", 0),
                'fallback_usage' => Cache::get("multilingual_stats:fallback_daily:{$today}", 0),
                'language_switches' => Cache::get("multilingual_stats:switch_daily:{$today}", 0),
            ],
            'current_locale' => App::getLocale(),
            'supported_locales' => config('app.supported_locales', ['zh_TW', 'en']),
            'cache_stats' => $this->getCacheStatistics(),
        ];
    }

    /**
     * 取得快取統計資訊
     *
     * @return array 快取統計
     */
    private function getCacheStatistics(): array
    {
        // 這裡可以實作更詳細的快取統計邏輯
        return [
            'cache_driver' => config('cache.default'),
            'language_cache_prefix' => 'lang_fallback',
        ];
    }

    /**
     * 清除統計快取
     *
     * @param int $days 清除幾天前的資料
     * @return void
     */
    public function clearStatistics(int $days = 7): void
    {
        $patterns = [
            'multilingual_stats:missing_keys:*',
            'multilingual_stats:fallback:*',
            'multilingual_stats:switch:*',
        ];

        // 清除指定天數前的每日統計
        for ($i = $days; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');
            Cache::forget("multilingual_stats:missing_keys_daily:{$date}");
            Cache::forget("multilingual_stats:fallback_daily:{$date}");
            Cache::forget("multilingual_stats:switch_daily:{$date}");
        }
    }
}