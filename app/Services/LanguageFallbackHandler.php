<?php

namespace App\Services;

use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;

/**
 * 語言回退處理器
 * 
 * 提供語言翻譯的回退機制，當翻譯鍵不存在時自動回退到預設語言
 */
class LanguageFallbackHandler
{
    /**
     * 語言回退鏈
     * 按優先級排序，當前語言 -> 正體中文 -> 英文
     */
    private array $fallbackChain;
    
    /**
     * 預設語言
     */
    private string $defaultLocale = 'zh_TW';
    
    /**
     * 語言檔案快取時間（秒）
     */
    private int $cacheTime = 3600;
    
    /**
     * 是否啟用日誌記錄
     */
    private bool $enableLogging = true;
    
    /**
     * 建構函數
     */
    public function __construct()
    {
        $this->initializeFallbackChain();
    }
    
    /**
     * 初始化回退鏈
     */
    private function initializeFallbackChain(): void
    {
        $currentLocale = App::getLocale();
        
        // 建立回退鏈：當前語言 -> 預設語言 -> 英文
        $this->fallbackChain = array_unique([
            $currentLocale,
            $this->defaultLocale,
            'en'
        ]);
    }
    
    /**
     * 翻譯文字，支援回退機制
     * 
     * @param string $key 翻譯鍵
     * @param array $replace 替換參數
     * @param string|null $locale 指定語言（可選）
     * @return string 翻譯結果
     */
    public function translate(string $key, array $replace = [], ?string $locale = null): string
    {
        // 如果指定了語言，優先使用指定語言
        if ($locale) {
            $translation = $this->getTranslationFromLocale($key, $locale);
            if ($translation !== null) {
                return $this->replaceParameters($translation, $replace);
            }
        }
        
        // 使用回退鏈尋找翻譯
        foreach ($this->fallbackChain as $fallbackLocale) {
            $translation = $this->getTranslationFromLocale($key, $fallbackLocale);
            
            if ($translation !== null) {
                // 如果不是使用第一個語言（當前語言），記錄回退事件
                if ($fallbackLocale !== $this->fallbackChain[0] && $this->enableLogging) {
                    $this->logFallbackUsage($key, $this->fallbackChain[0], $fallbackLocale);
                }
                
                return $this->replaceParameters($translation, $replace);
            }
        }
        
        // 所有回退都失敗，記錄錯誤並返回鍵值本身
        $this->logMissingTranslation($key);
        
        return $this->replaceParameters($key, $replace);
    }
    
    /**
     * 從指定語言取得翻譯
     * 
     * @param string $key 翻譯鍵
     * @param string $locale 語言代碼
     * @return string|null 翻譯結果或 null
     */
    private function getTranslationFromLocale(string $key, string $locale): ?string
    {
        try {
            // 分解翻譯鍵以取得檔案名稱和鍵值路徑
            $keyParts = explode('.', $key, 2);
            
            if (count($keyParts) < 2) {
                return null;
            }
            
            $file = $keyParts[0];
            $keyPath = $keyParts[1];
            
            // 載入語言檔案
            $translations = $this->loadLanguageFile($file, $locale);
            
            if (empty($translations)) {
                return null;
            }
            
            // 使用點記法取得翻譯值
            $translation = data_get($translations, $keyPath);
            
            // 確保返回的是字串，如果是陣列則返回 null
            return is_string($translation) ? $translation : null;
            
        } catch (\Exception $e) {
            // 載入失敗，記錄錯誤
            if ($this->enableLogging) {
                Log::channel('multilingual')->error('Failed to load translation', [
                    'key' => $key,
                    'locale' => $locale,
                    'error' => $e->getMessage()
                ]);
            }
            
            return null;
        }
    }
    
    /**
     * 載入語言檔案
     * 
     * @param string $file 檔案名稱
     * @param string $locale 語言代碼
     * @return array 語言檔案內容
     */
    private function loadLanguageFile(string $file, string $locale): array
    {
        $cacheKey = "lang_fallback.{$locale}.{$file}";
        
        return Cache::remember($cacheKey, $this->cacheTime, function () use ($file, $locale) {
            $filePath = lang_path("{$locale}/{$file}.php");
            
            if (!File::exists($filePath)) {
                return [];
            }
            
            try {
                $content = include $filePath;
                return is_array($content) ? $content : [];
            } catch (\Exception $e) {
                if ($this->enableLogging) {
                    Log::channel('multilingual')->error('Failed to include language file', [
                        'file' => $filePath,
                        'error' => $e->getMessage()
                    ]);
                }
                return [];
            }
        });
    }
    
    /**
     * 替換翻譯參數
     * 
     * @param string $translation 翻譯文字
     * @param array $replace 替換參數
     * @return string 處理後的翻譯文字
     */
    private function replaceParameters(string $translation, array $replace = []): string
    {
        if (empty($replace)) {
            return $translation;
        }
        
        foreach ($replace as $key => $value) {
            // 支援 :key 和 {key} 兩種參數格式，區分大小寫
            $translation = str_replace([
                ":{$key}",
                "{{$key}}"
            ], $value, $translation);
        }
        
        return $translation;
    }
    
    /**
     * 記錄回退使用情況
     * 
     * @param string $key 翻譯鍵
     * @param string $originalLocale 原始語言
     * @param string $fallbackLocale 回退語言
     */
    private function logFallbackUsage(string $key, string $originalLocale, string $fallbackLocale): void
    {
        Log::channel('multilingual')->info('Translation fallback used', [
            'key' => $key,
            'original_locale' => $originalLocale,
            'fallback_locale' => $fallbackLocale,
            'url' => request()->url(),
            'user_id' => auth()->id(),
            'ip' => request()->ip(),
            'user_agent' => request()->userAgent()
        ]);
    }
    
    /**
     * 記錄缺少的翻譯
     * 
     * @param string $key 翻譯鍵
     */
    private function logMissingTranslation(string $key): void
    {
        Log::channel('multilingual')->warning('Missing translation key', [
            'key' => $key,
            'locale' => App::getLocale(),
            'fallback_chain' => $this->fallbackChain,
            'url' => request()->url(),
            'user_id' => auth()->id(),
            'ip' => request()->ip(),
            'user_agent' => request()->userAgent()
        ]);
    }
    
    /**
     * 檢查翻譯鍵是否存在
     * 
     * @param string $key 翻譯鍵
     * @param string|null $locale 指定語言（可選）
     * @return bool 是否存在
     */
    public function hasTranslation(string $key, ?string $locale = null): bool
    {
        if ($locale) {
            return $this->getTranslationFromLocale($key, $locale) !== null;
        }
        
        // 檢查回退鏈中是否有任何語言包含此翻譯
        foreach ($this->fallbackChain as $fallbackLocale) {
            if ($this->getTranslationFromLocale($key, $fallbackLocale) !== null) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * 取得翻譯鍵在各語言中的存在狀態
     * 
     * @param string $key 翻譯鍵
     * @return array 各語言的存在狀態
     */
    public function getTranslationStatus(string $key): array
    {
        $status = [];
        
        foreach ($this->fallbackChain as $locale) {
            $status[$locale] = $this->getTranslationFromLocale($key, $locale) !== null;
        }
        
        return $status;
    }
    
    /**
     * 設定回退鏈
     * 
     * @param array $chain 回退鏈
     */
    public function setFallbackChain(array $chain): void
    {
        $this->fallbackChain = $chain;
    }
    
    /**
     * 取得當前回退鏈
     * 
     * @return array 回退鏈
     */
    public function getFallbackChain(): array
    {
        return $this->fallbackChain;
    }
    
    /**
     * 設定預設語言
     * 
     * @param string $locale 語言代碼
     */
    public function setDefaultLocale(string $locale): void
    {
        $this->defaultLocale = $locale;
        $this->initializeFallbackChain();
    }
    
    /**
     * 取得預設語言
     * 
     * @return string 語言代碼
     */
    public function getDefaultLocale(): string
    {
        return $this->defaultLocale;
    }
    
    /**
     * 啟用或停用日誌記錄
     * 
     * @param bool $enable 是否啟用
     */
    public function setLogging(bool $enable): void
    {
        $this->enableLogging = $enable;
    }
    
    /**
     * 清除語言檔案快取
     * 
     * @param string|null $locale 指定語言（可選）
     * @param string|null $file 指定檔案（可選）
     */
    public function clearCache(?string $locale = null, ?string $file = null): void
    {
        if ($locale && $file) {
            Cache::forget("lang_fallback.{$locale}.{$file}");
        } elseif ($locale) {
            // 清除指定語言的所有快取
            $pattern = "lang_fallback.{$locale}.*";
            $this->clearCacheByPattern($pattern);
        } else {
            // 清除所有語言快取
            $this->clearCacheByPattern('lang_fallback.*');
        }
    }
    
    /**
     * 根據模式清除快取
     * 
     * @param string $pattern 快取鍵模式
     */
    private function clearCacheByPattern(string $pattern): void
    {
        // 這裡使用簡單的方法，實際應用中可能需要更複雜的快取清除邏輯
        $locales = ['zh_TW', 'en'];
        $commonFiles = [
            'admin', 'auth', 'layout', 'validation', 'passwords', 
            'theme', 'permissions', 'settings', 'activity_logs'
        ];
        
        foreach ($locales as $locale) {
            foreach ($commonFiles as $file) {
                Cache::forget("lang_fallback.{$locale}.{$file}");
            }
        }
    }
    
    /**
     * 取得回退統計資訊
     * 
     * @return array 統計資訊
     */
    public function getFallbackStatistics(): array
    {
        // 這個方法可以用來取得回退使用的統計資訊
        // 實際實作可能需要更複雜的統計邏輯
        return [
            'fallback_chain' => $this->fallbackChain,
            'default_locale' => $this->defaultLocale,
            'cache_time' => $this->cacheTime,
            'logging_enabled' => $this->enableLogging
        ];
    }
}