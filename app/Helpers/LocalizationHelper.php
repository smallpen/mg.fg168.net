<?php

namespace App\Helpers;

use Carbon\Carbon;
use Illuminate\Support\Facades\App;
use App\Services\LanguageFallbackHandler;

/**
 * 本地化輔助類別
 * 
 * 提供日期時間、數字和貨幣的本地化格式化功能
 */
class LocalizationHelper
{
    /**
     * 本地化設定
     */
    protected static array $localeSettings = [
        'zh_TW' => [
            'date_format' => 'Y年m月d日',
            'time_format' => 'H:i',
            'datetime_format' => 'Y年m月d日 H:i',
            'short_date_format' => 'm/d',
            'long_date_format' => 'Y年m月d日 l',
            'currency_symbol' => 'NT$',
            'currency_position' => 'before', // before or after
            'decimal_separator' => '.',
            'thousands_separator' => ',',
            'week_start' => 1, // 1 = Monday, 0 = Sunday
            'first_day_of_week' => 'monday',
            'timezone' => 'Asia/Taipei',
        ],
        'en' => [
            'date_format' => 'Y-m-d',
            'time_format' => 'H:i',
            'datetime_format' => 'Y-m-d H:i',
            'short_date_format' => 'm/d',
            'long_date_format' => 'l, F j, Y',
            'currency_symbol' => '$',
            'currency_position' => 'before',
            'decimal_separator' => '.',
            'thousands_separator' => ',',
            'week_start' => 0, // 0 = Sunday
            'first_day_of_week' => 'sunday',
            'timezone' => 'UTC',
        ]
    ];

    /**
     * 取得當前語言的本地化設定
     */
    public static function getCurrentSettings(): array
    {
        $locale = App::getLocale();
        return static::$localeSettings[$locale] ?? static::$localeSettings['zh_TW'];
    }

    /**
     * 取得指定語言的本地化設定
     */
    public static function getSettings(string $locale): array
    {
        return static::$localeSettings[$locale] ?? static::$localeSettings['zh_TW'];
    }

    /**
     * 格式化日期時間
     */
    public static function formatDateTime($date, ?string $locale = null, ?string $format = null): string
    {
        if (!$date instanceof Carbon) {
            $date = Carbon::parse($date);
        }

        $settings = $locale ? static::getSettings($locale) : static::getCurrentSettings();
        $format = $format ?? $settings['datetime_format'];

        // 設定時區
        if (isset($settings['timezone'])) {
            $date = $date->setTimezone($settings['timezone']);
        }

        return $date->format($format);
    }

    /**
     * 格式化日期
     */
    public static function formatDate($date, ?string $locale = null, ?string $format = null): string
    {
        if (!$date instanceof Carbon) {
            $date = Carbon::parse($date);
        }

        $settings = $locale ? static::getSettings($locale) : static::getCurrentSettings();
        $format = $format ?? $settings['date_format'];

        // 設定時區
        if (isset($settings['timezone'])) {
            $date = $date->setTimezone($settings['timezone']);
        }

        return $date->format($format);
    }

    /**
     * 格式化時間
     */
    public static function formatTime($date, ?string $locale = null, ?string $format = null): string
    {
        if (!$date instanceof Carbon) {
            $date = Carbon::parse($date);
        }

        $settings = $locale ? static::getSettings($locale) : static::getCurrentSettings();
        $format = $format ?? $settings['time_format'];

        // 設定時區
        if (isset($settings['timezone'])) {
            $date = $date->setTimezone($settings['timezone']);
        }

        return $date->format($format);
    }

    /**
     * 格式化短日期
     */
    public static function formatShortDate($date, ?string $locale = null): string
    {
        if (!$date instanceof Carbon) {
            $date = Carbon::parse($date);
        }

        $settings = $locale ? static::getSettings($locale) : static::getCurrentSettings();
        
        // 設定時區
        if (isset($settings['timezone'])) {
            $date = $date->setTimezone($settings['timezone']);
        }

        return $date->format($settings['short_date_format']);
    }

    /**
     * 格式化長日期
     */
    public static function formatLongDate($date, ?string $locale = null): string
    {
        if (!$date instanceof Carbon) {
            $date = Carbon::parse($date);
        }

        $settings = $locale ? static::getSettings($locale) : static::getCurrentSettings();
        
        // 設定時區
        if (isset($settings['timezone'])) {
            $date = $date->setTimezone($settings['timezone']);
        }

        return $date->format($settings['long_date_format']);
    }

    /**
     * 格式化相對時間
     */
    public static function formatRelativeTime($date, ?string $locale = null): string
    {
        if (!$date instanceof Carbon) {
            $date = Carbon::parse($date);
        }

        $currentLocale = $locale ?? App::getLocale();
        
        // 設定 Carbon 的語言
        $carbonLocale = $currentLocale === 'zh_TW' ? 'zh' : $currentLocale;
        Carbon::setLocale($carbonLocale);

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
     * 格式化數字
     */
    public static function formatNumber(float $number, int $decimals = 0, ?string $locale = null): string
    {
        $settings = $locale ? static::getSettings($locale) : static::getCurrentSettings();

        return number_format(
            $number,
            $decimals,
            $settings['decimal_separator'],
            $settings['thousands_separator']
        );
    }

    /**
     * 格式化貨幣
     */
    public static function formatCurrency(float $amount, ?string $locale = null, int $decimals = 2): string
    {
        $settings = $locale ? static::getSettings($locale) : static::getCurrentSettings();
        
        $formattedAmount = static::formatNumber($amount, $decimals, $locale);
        
        if ($settings['currency_position'] === 'before') {
            return $settings['currency_symbol'] . $formattedAmount;
        } else {
            return $formattedAmount . $settings['currency_symbol'];
        }
    }

    /**
     * 格式化百分比
     */
    public static function formatPercentage(float $percentage, int $decimals = 1, ?string $locale = null): string
    {
        $formattedNumber = static::formatNumber($percentage, $decimals, $locale);
        return $formattedNumber . '%';
    }

    /**
     * 取得本地化的星期名稱
     */
    public static function getWeekdays(?string $locale = null): array
    {
        $currentLocale = $locale ?? App::getLocale();
        
        if ($currentLocale === 'zh_TW') {
            return [
                'sunday' => '星期日',
                'monday' => '星期一',
                'tuesday' => '星期二',
                'wednesday' => '星期三',
                'thursday' => '星期四',
                'friday' => '星期五',
                'saturday' => '星期六',
            ];
        }
        
        return [
            'sunday' => 'Sunday',
            'monday' => 'Monday',
            'tuesday' => 'Tuesday',
            'wednesday' => 'Wednesday',
            'thursday' => 'Thursday',
            'friday' => 'Friday',
            'saturday' => 'Saturday',
        ];
    }

    /**
     * 取得本地化的月份名稱
     */
    public static function getMonths(?string $locale = null): array
    {
        $currentLocale = $locale ?? App::getLocale();
        
        if ($currentLocale === 'zh_TW') {
            return [
                1 => '一月', 2 => '二月', 3 => '三月', 4 => '四月',
                5 => '五月', 6 => '六月', 7 => '七月', 8 => '八月',
                9 => '九月', 10 => '十月', 11 => '十一月', 12 => '十二月',
            ];
        }
        
        return [
            1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
            5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
            9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December',
        ];
    }

    /**
     * 取得一週的開始日
     */
    public static function getWeekStart(?string $locale = null): int
    {
        $settings = $locale ? static::getSettings($locale) : static::getCurrentSettings();
        return $settings['week_start'];
    }

    /**
     * 取得時區
     */
    public static function getTimezone(?string $locale = null): string
    {
        $settings = $locale ? static::getSettings($locale) : static::getCurrentSettings();
        return $settings['timezone'];
    }

    /**
     * 建立本地化的 Carbon 實例
     */
    public static function createLocalizedCarbon($date = null, ?string $locale = null): Carbon
    {
        $settings = $locale ? static::getSettings($locale) : static::getCurrentSettings();
        $carbonLocale = ($locale ?? App::getLocale()) === 'zh_TW' ? 'zh' : ($locale ?? App::getLocale());
        
        $carbon = $date ? Carbon::parse($date) : Carbon::now();
        $carbon->setTimezone($settings['timezone']);
        $carbon->setLocale($carbonLocale);
        
        return $carbon;
    }

    /**
     * 格式化檔案大小
     */
    public static function formatFileSize(int $bytes, ?string $locale = null): string
    {
        $currentLocale = $locale ?? App::getLocale();
        
        $units = $currentLocale === 'zh_TW' 
            ? ['位元組', 'KB', 'MB', 'GB', 'TB']
            : ['bytes', 'KB', 'MB', 'GB', 'TB'];
        
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        
        $bytes /= (1 << (10 * $pow));
        
        return static::formatNumber($bytes, $pow > 0 ? 1 : 0, $locale) . ' ' . $units[$pow];
    }
    
    /**
     * 使用回退機制翻譯文字
     * 
     * @param string $key 翻譯鍵
     * @param array $replace 替換參數
     * @param string|null $locale 指定語言
     * @return string 翻譯結果
     */
    public static function translateWithFallback(string $key, array $replace = [], ?string $locale = null): string
    {
        $handler = app(LanguageFallbackHandler::class);
        return $handler->translate($key, $replace, $locale);
    }
    
    /**
     * 檢查翻譯是否存在（支援回退機制）
     * 
     * @param string $key 翻譯鍵
     * @param string|null $locale 指定語言
     * @return bool 是否存在
     */
    public static function hasTranslationWithFallback(string $key, ?string $locale = null): bool
    {
        $handler = app(LanguageFallbackHandler::class);
        return $handler->hasTranslation($key, $locale);
    }
    
    /**
     * 取得翻譯在各語言中的狀態
     * 
     * @param string $key 翻譯鍵
     * @return array 各語言的存在狀態
     */
    public static function getTranslationStatus(string $key): array
    {
        $handler = app(LanguageFallbackHandler::class);
        return $handler->getTranslationStatus($key);
    }
}