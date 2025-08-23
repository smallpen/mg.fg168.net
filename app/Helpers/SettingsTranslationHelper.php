<?php

namespace App\Helpers;

use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;

/**
 * 設定翻譯輔助類別
 * 
 * 提供設定項目的多語言翻譯功能
 */
class SettingsTranslationHelper
{
    /**
     * 支援的語言列表
     */
    protected static array $supportedLocales = [
        'zh_TW' => '正體中文',
        'en' => 'English',
        'zh_CN' => '简体中文',
        'ja' => '日本語',
    ];

    /**
     * 快取鍵前綴
     */
    protected static string $cachePrefix = 'settings_translation';

    /**
     * 快取時間（秒）
     */
    protected static int $cacheTtl = 3600;

    /**
     * 取得設定項目的翻譯名稱
     */
    public static function getSettingName(string $settingKey, string $locale = null): string
    {
        $locale = $locale ?: App::getLocale();
        $cacheKey = static::$cachePrefix . ".name.{$locale}.{$settingKey}";
        
        return Cache::remember($cacheKey, static::$cacheTtl, function () use ($settingKey, $locale) {
            $translationKey = "settings.settings.{$settingKey}.name";
            
            // 嘗試取得翻譯
            $translation = trans($translationKey, [], $locale);
            
            // 如果翻譯不存在，回退到配置檔案
            if ($translation === $translationKey) {
                $config = config("system-settings.settings.{$settingKey}");
                return $config['description'] ?? $settingKey;
            }
            
            return $translation;
        });
    }

    /**
     * 取得設定項目的翻譯描述
     */
    public static function getSettingDescription(string $settingKey, string $locale = null): string
    {
        $locale = $locale ?: App::getLocale();
        $cacheKey = static::$cachePrefix . ".description.{$locale}.{$settingKey}";
        
        return Cache::remember($cacheKey, static::$cacheTtl, function () use ($settingKey, $locale) {
            $translationKey = "settings.settings.{$settingKey}.description";
            
            // 嘗試取得翻譯
            $translation = trans($translationKey, [], $locale);
            
            // 如果翻譯不存在，回退到配置檔案
            if ($translation === $translationKey) {
                $config = config("system-settings.settings.{$settingKey}");
                return $config['help'] ?? '';
            }
            
            return $translation;
        });
    }

    /**
     * 取得分類的翻譯名稱
     */
    public static function getCategoryName(string $categoryKey, string $locale = null): string
    {
        $locale = $locale ?: App::getLocale();
        $cacheKey = static::$cachePrefix . ".category.name.{$locale}.{$categoryKey}";
        
        return Cache::remember($cacheKey, static::$cacheTtl, function () use ($categoryKey, $locale) {
            $translationKey = "settings.categories.{$categoryKey}.name";
            
            // 嘗試取得翻譯
            $translation = trans($translationKey, [], $locale);
            
            // 如果翻譯不存在，回退到配置檔案
            if ($translation === $translationKey) {
                $config = config("system-settings.categories.{$categoryKey}");
                return $config['name'] ?? $categoryKey;
            }
            
            return $translation;
        });
    }

    /**
     * 取得分類的翻譯描述
     */
    public static function getCategoryDescription(string $categoryKey, string $locale = null): string
    {
        $locale = $locale ?: App::getLocale();
        $cacheKey = static::$cachePrefix . ".category.description.{$locale}.{$categoryKey}";
        
        return Cache::remember($cacheKey, static::$cacheTtl, function () use ($categoryKey, $locale) {
            $translationKey = "settings.categories.{$categoryKey}.description";
            
            // 嘗試取得翻譯
            $translation = trans($translationKey, [], $locale);
            
            // 如果翻譯不存在，回退到配置檔案
            if ($translation === $translationKey) {
                $config = config("system-settings.categories.{$categoryKey}");
                return $config['description'] ?? '';
            }
            
            return $translation;
        });
    }

    /**
     * 取得選項值的翻譯
     */
    public static function getOptionLabel(string $optionKey, string $locale = null): string
    {
        $locale = $locale ?: App::getLocale();
        $cacheKey = static::$cachePrefix . ".option.{$locale}.{$optionKey}";
        
        return Cache::remember($cacheKey, static::$cacheTtl, function () use ($optionKey, $locale) {
            $translationKey = "settings.options.{$optionKey}";
            
            // 嘗試取得翻譯
            $translation = trans($translationKey, [], $locale);
            
            // 如果翻譯不存在，返回原始鍵值
            if ($translation === $translationKey) {
                return $optionKey;
            }
            
            return $translation;
        });
    }

    /**
     * 取得錯誤訊息的翻譯
     */
    public static function getErrorMessage(string $errorKey, array $parameters = [], string $locale = null): string
    {
        $locale = $locale ?: App::getLocale();
        $cacheKey = static::$cachePrefix . ".error.{$locale}.{$errorKey}." . md5(serialize($parameters));
        
        return Cache::remember($cacheKey, static::$cacheTtl, function () use ($errorKey, $parameters, $locale) {
            // 嘗試從設定錯誤檔案取得翻譯
            $translationKey = "settings_errors.{$errorKey}";
            $translation = trans($translationKey, $parameters, $locale);
            
            if ($translation !== $translationKey) {
                return $translation;
            }
            
            // 回退到一般驗證訊息
            $validationKey = "settings.validation.{$errorKey}";
            $translation = trans($validationKey, $parameters, $locale);
            
            if ($translation !== $validationKey) {
                return $translation;
            }
            
            // 最後回退到 Laravel 預設驗證訊息
            return trans("validation.{$errorKey}", $parameters, $locale);
        });
    }

    /**
     * 取得支援的語言列表
     */
    public static function getSupportedLocales(): array
    {
        return static::$supportedLocales;
    }

    /**
     * 檢查語言是否支援
     */
    public static function isLocaleSupported(string $locale): bool
    {
        return array_key_exists($locale, static::$supportedLocales);
    }

    /**
     * 取得語言的顯示名稱
     */
    public static function getLocaleName(string $locale): string
    {
        return static::$supportedLocales[$locale] ?? $locale;
    }

    /**
     * 取得所有設定項目的翻譯
     */
    public static function getAllSettingTranslations(string $locale = null): array
    {
        $locale = $locale ?: App::getLocale();
        $cacheKey = static::$cachePrefix . ".all_settings.{$locale}";
        
        return Cache::remember($cacheKey, static::$cacheTtl, function () use ($locale) {
            $translations = [];
            $settings = config('system-settings.settings', []);
            
            foreach (array_keys($settings) as $settingKey) {
                $translations[$settingKey] = [
                    'name' => static::getSettingName($settingKey, $locale),
                    'description' => static::getSettingDescription($settingKey, $locale),
                ];
            }
            
            return $translations;
        });
    }

    /**
     * 取得所有分類的翻譯
     */
    public static function getAllCategoryTranslations(string $locale = null): array
    {
        $locale = $locale ?: App::getLocale();
        $cacheKey = static::$cachePrefix . ".all_categories.{$locale}";
        
        return Cache::remember($cacheKey, static::$cacheTtl, function () use ($locale) {
            $translations = [];
            $categories = config('system-settings.categories', []);
            
            foreach (array_keys($categories) as $categoryKey) {
                $translations[$categoryKey] = [
                    'name' => static::getCategoryName($categoryKey, $locale),
                    'description' => static::getCategoryDescription($categoryKey, $locale),
                ];
            }
            
            return $translations;
        });
    }

    /**
     * 清除翻譯快取
     */
    public static function clearCache(string $locale = null): void
    {
        if ($locale) {
            // 清除特定語言的快取
            $pattern = static::$cachePrefix . ".*.{$locale}.*";
            static::clearCacheByPattern($pattern);
        } else {
            // 清除所有翻譯快取
            $pattern = static::$cachePrefix . ".*";
            static::clearCacheByPattern($pattern);
        }
    }

    /**
     * 根據模式清除快取
     */
    protected static function clearCacheByPattern(string $pattern): void
    {
        $cacheStore = Cache::getStore();
        
        if (method_exists($cacheStore, 'flush')) {
            // 如果支援 flush，直接清除所有快取
            $cacheStore->flush();
        } else {
            // 否則嘗試根據模式清除
            try {
                $keys = Cache::getRedis()->keys($pattern);
                if (!empty($keys)) {
                    Cache::getRedis()->del($keys);
                }
            } catch (\Exception $e) {
                // 如果無法清除，記錄錯誤但不中斷執行
                logger()->warning('Failed to clear translation cache: ' . $e->getMessage());
            }
        }
    }

    /**
     * 預熱翻譯快取
     */
    public static function warmupCache(string $locale = null): void
    {
        $locales = $locale ? [$locale] : array_keys(static::$supportedLocales);
        
        foreach ($locales as $loc) {
            // 預熱設定翻譯
            static::getAllSettingTranslations($loc);
            
            // 預熱分類翻譯
            static::getAllCategoryTranslations($loc);
            
            // 預熱常用選項翻譯
            $commonOptions = ['yes', 'no', 'enabled', 'disabled', 'true', 'false'];
            foreach ($commonOptions as $option) {
                static::getOptionLabel($option, $loc);
            }
        }
    }

    /**
     * 檢查翻譯檔案是否存在
     */
    public static function hasTranslationFile(string $locale, string $file = 'settings'): bool
    {
        $path = resource_path("lang/{$locale}/{$file}.php");
        return File::exists($path);
    }

    /**
     * 取得缺少的翻譯檔案
     */
    public static function getMissingTranslationFiles(): array
    {
        $missing = [];
        $files = ['settings', 'settings_errors'];
        
        foreach (static::$supportedLocales as $locale => $name) {
            foreach ($files as $file) {
                if (!static::hasTranslationFile($locale, $file)) {
                    $missing[] = [
                        'locale' => $locale,
                        'locale_name' => $name,
                        'file' => $file,
                        'path' => "lang/{$locale}/{$file}.php",
                    ];
                }
            }
        }
        
        return $missing;
    }

    /**
     * 驗證翻譯完整性
     */
    public static function validateTranslations(string $locale = null): array
    {
        $locales = $locale ? [$locale] : array_keys(static::$supportedLocales);
        $issues = [];
        
        foreach ($locales as $loc) {
            // 檢查設定翻譯
            $settings = config('system-settings.settings', []);
            foreach (array_keys($settings) as $settingKey) {
                $nameKey = "settings.settings.{$settingKey}.name";
                $descKey = "settings.settings.{$settingKey}.description";
                
                if (trans($nameKey, [], $loc) === $nameKey) {
                    $issues[] = [
                        'locale' => $loc,
                        'type' => 'setting_name',
                        'key' => $settingKey,
                        'translation_key' => $nameKey,
                    ];
                }
                
                if (trans($descKey, [], $loc) === $descKey) {
                    $issues[] = [
                        'locale' => $loc,
                        'type' => 'setting_description',
                        'key' => $settingKey,
                        'translation_key' => $descKey,
                    ];
                }
            }
            
            // 檢查分類翻譯
            $categories = config('system-settings.categories', []);
            foreach (array_keys($categories) as $categoryKey) {
                $nameKey = "settings.categories.{$categoryKey}.name";
                $descKey = "settings.categories.{$categoryKey}.description";
                
                if (trans($nameKey, [], $loc) === $nameKey) {
                    $issues[] = [
                        'locale' => $loc,
                        'type' => 'category_name',
                        'key' => $categoryKey,
                        'translation_key' => $nameKey,
                    ];
                }
                
                if (trans($descKey, [], $loc) === $descKey) {
                    $issues[] = [
                        'locale' => $loc,
                        'type' => 'category_description',
                        'key' => $categoryKey,
                        'translation_key' => $descKey,
                    ];
                }
            }
        }
        
        return $issues;
    }
}