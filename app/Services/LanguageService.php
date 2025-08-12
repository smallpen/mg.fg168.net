<?php

namespace App\Services;

use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Carbon\Carbon;

/**
 * 語言服務類別
 * 
 * 提供多語言支援的核心功能，包括語言切換、本地化設定和語言資源管理
 */
class LanguageService
{
    /**
     * 支援的語言列表
     */
    protected array $supportedLocales = [
        'zh_TW' => [
            'name' => '正體中文',
            'native_name' => '正體中文',
            'flag' => '🇹🇼',
            'code' => 'zh_TW',
            'direction' => 'ltr',
            'date_format' => 'Y年m月d日',
            'time_format' => 'H:i',
            'datetime_format' => 'Y年m月d日 H:i',
            'currency' => 'TWD',
            'currency_symbol' => 'NT$',
            'decimal_separator' => '.',
            'thousands_separator' => ',',
        ],
        'en' => [
            'name' => 'English',
            'native_name' => 'English',
            'flag' => '🇺🇸',
            'code' => 'en',
            'direction' => 'ltr',
            'date_format' => 'Y-m-d',
            'time_format' => 'H:i',
            'datetime_format' => 'Y-m-d H:i',
            'currency' => 'USD',
            'currency_symbol' => '$',
            'decimal_separator' => '.',
            'thousands_separator' => ',',
        ]
    ];

    /**
     * 取得所有支援的語言
     */
    public function getSupportedLocales(): array
    {
        return $this->supportedLocales;
    }

    /**
     * 取得特定語言的資訊
     */
    public function getLocaleInfo(string $locale): ?array
    {
        return $this->supportedLocales[$locale] ?? null;
    }

    /**
     * 檢查語言是否支援
     */
    public function isLocaleSupported(string $locale): bool
    {
        return array_key_exists($locale, $this->supportedLocales);
    }

    /**
     * 取得當前語言
     */
    public function getCurrentLocale(): string
    {
        return App::getLocale();
    }

    /**
     * 取得當前語言資訊
     */
    public function getCurrentLocaleInfo(): array
    {
        return $this->getLocaleInfo($this->getCurrentLocale()) ?? $this->supportedLocales['zh_TW'];
    }

    /**
     * 切換語言
     */
    public function switchLocale(string $locale): bool
    {
        if (!$this->isLocaleSupported($locale)) {
            return false;
        }

        // 設定應用程式語言
        App::setLocale($locale);

        // 儲存到 Session
        Session::put('locale', $locale);

        // 如果使用者已登入，儲存到使用者偏好設定
        if (auth()->check()) {
            auth()->user()->update(['locale' => $locale]);
        }

        // 清除語言相關的快取
        $this->clearLanguageCache();

        return true;
    }

    /**
     * 格式化日期時間
     */
    public function formatDateTime(Carbon $date, ?string $locale = null): string
    {
        $locale = $locale ?? $this->getCurrentLocale();
        $localeInfo = $this->getLocaleInfo($locale);
        
        if (!$localeInfo) {
            return $date->format('Y-m-d H:i');
        }

        return $date->format($localeInfo['datetime_format']);
    }

    /**
     * 格式化日期
     */
    public function formatDate(Carbon $date, ?string $locale = null): string
    {
        $locale = $locale ?? $this->getCurrentLocale();
        $localeInfo = $this->getLocaleInfo($locale);
        
        if (!$localeInfo) {
            return $date->format('Y-m-d');
        }

        return $date->format($localeInfo['date_format']);
    }

    /**
     * 格式化時間
     */
    public function formatTime(Carbon $date, ?string $locale = null): string
    {
        $locale = $locale ?? $this->getCurrentLocale();
        $localeInfo = $this->getLocaleInfo($locale);
        
        if (!$localeInfo) {
            return $date->format('H:i');
        }

        return $date->format($localeInfo['time_format']);
    }

    /**
     * 格式化數字
     */
    public function formatNumber(float $number, int $decimals = 0, ?string $locale = null): string
    {
        $locale = $locale ?? $this->getCurrentLocale();
        $localeInfo = $this->getLocaleInfo($locale);
        
        if (!$localeInfo) {
            return number_format($number, $decimals);
        }

        return number_format(
            $number,
            $decimals,
            $localeInfo['decimal_separator'],
            $localeInfo['thousands_separator']
        );
    }

    /**
     * 格式化貨幣
     */
    public function formatCurrency(float $amount, ?string $locale = null): string
    {
        $locale = $locale ?? $this->getCurrentLocale();
        $localeInfo = $this->getLocaleInfo($locale);
        
        if (!$localeInfo) {
            return '$' . number_format($amount, 2);
        }

        $formattedAmount = $this->formatNumber($amount, 2, $locale);
        
        return $localeInfo['currency_symbol'] . $formattedAmount;
    }

    /**
     * 取得相對時間顯示
     */
    public function getRelativeTime(Carbon $date, ?string $locale = null): string
    {
        $locale = $locale ?? $this->getCurrentLocale();
        
        // 設定 Carbon 的語言
        Carbon::setLocale($locale === 'zh_TW' ? 'zh' : $locale);
        
        $now = Carbon::now();
        $diffInSeconds = $now->diffInSeconds($date);
        $diffInMinutes = $now->diffInMinutes($date);
        $diffInHours = $now->diffInHours($date);
        $diffInDays = $now->diffInDays($date);

        if ($diffInSeconds < 60) {
            return __('layout.notifications.just_now');
        } elseif ($diffInMinutes < 60) {
            return __('layout.notifications.minutes_ago', ['count' => $diffInMinutes]);
        } elseif ($diffInHours < 24) {
            return __('layout.notifications.hours_ago', ['count' => $diffInHours]);
        } elseif ($diffInDays < 7) {
            return __('layout.notifications.days_ago', ['count' => $diffInDays]);
        } elseif ($diffInDays < 30) {
            $weeks = floor($diffInDays / 7);
            return __('layout.notifications.weeks_ago', ['count' => $weeks]);
        } elseif ($diffInDays < 365) {
            $months = floor($diffInDays / 30);
            return __('layout.notifications.months_ago', ['count' => $months]);
        } else {
            $years = floor($diffInDays / 365);
            return __('layout.notifications.years_ago', ['count' => $years]);
        }
    }

    /**
     * 檢查語言是否為 RTL（右到左）
     */
    public function isRtl(?string $locale = null): bool
    {
        $locale = $locale ?? $this->getCurrentLocale();
        $localeInfo = $this->getLocaleInfo($locale);
        
        return $localeInfo ? $localeInfo['direction'] === 'rtl' : false;
    }

    /**
     * 取得語言方向
     */
    public function getDirection(?string $locale = null): string
    {
        $locale = $locale ?? $this->getCurrentLocale();
        $localeInfo = $this->getLocaleInfo($locale);
        
        return $localeInfo ? $localeInfo['direction'] : 'ltr';
    }

    /**
     * 載入語言檔案
     */
    public function loadLanguageFile(string $file, ?string $locale = null): array
    {
        $locale = $locale ?? $this->getCurrentLocale();
        
        $cacheKey = "lang.{$locale}.{$file}";
        
        return Cache::remember($cacheKey, 3600, function () use ($locale, $file) {
            $path = resource_path("lang/{$locale}/{$file}.php");
            
            if (File::exists($path)) {
                return include $path;
            }
            
            // 如果找不到語言檔案，嘗試載入預設語言
            $defaultPath = resource_path("lang/zh_TW/{$file}.php");
            if (File::exists($defaultPath)) {
                return include $defaultPath;
            }
            
            return [];
        });
    }

    /**
     * 清除語言快取
     */
    public function clearLanguageCache(): void
    {
        $locales = array_keys($this->supportedLocales);
        $files = ['admin', 'auth', 'layout', 'validation', 'passwords'];
        
        foreach ($locales as $locale) {
            foreach ($files as $file) {
                Cache::forget("lang.{$locale}.{$file}");
            }
        }
    }

    /**
     * 取得語言檔案的所有翻譯鍵值
     */
    public function getTranslationKeys(string $file, ?string $locale = null): array
    {
        $translations = $this->loadLanguageFile($file, $locale);
        return $this->flattenArray($translations);
    }

    /**
     * 將多維陣列扁平化為點記法鍵值
     */
    private function flattenArray(array $array, string $prefix = ''): array
    {
        $result = [];
        
        foreach ($array as $key => $value) {
            $newKey = $prefix ? "{$prefix}.{$key}" : $key;
            
            if (is_array($value)) {
                $result = array_merge($result, $this->flattenArray($value, $newKey));
            } else {
                $result[$newKey] = $value;
            }
        }
        
        return $result;
    }

    /**
     * 檢查翻譯是否存在
     */
    public function hasTranslation(string $key, ?string $locale = null): bool
    {
        $locale = $locale ?? $this->getCurrentLocale();
        
        // 分解鍵值以取得檔案名稱
        $parts = explode('.', $key);
        $file = array_shift($parts);
        $translationKey = implode('.', $parts);
        
        $translations = $this->loadLanguageFile($file, $locale);
        
        return data_get($translations, $translationKey) !== null;
    }

    /**
     * 取得翻譯文字
     */
    public function getTranslation(string $key, array $replace = [], ?string $locale = null): string
    {
        $locale = $locale ?? $this->getCurrentLocale();
        
        // 暫時切換語言以取得翻譯
        $currentLocale = App::getLocale();
        App::setLocale($locale);
        
        $translation = __($key, $replace);
        
        // 恢復原本的語言
        App::setLocale($currentLocale);
        
        return $translation;
    }
}