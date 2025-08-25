<?php

namespace App\Helpers;

use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use App\Repositories\SettingsRepositoryInterface;

/**
 * 日期時間格式化輔助類別
 * 
 * 根據系統設定格式化日期和時間
 */
class DateTimeHelper
{
    /**
     * 設定資料庫介面
     */
    protected static ?SettingsRepositoryInterface $settingsRepository = null;

    /**
     * 取得設定資料庫實例
     */
    protected static function getSettingsRepository(): SettingsRepositoryInterface
    {
        if (static::$settingsRepository === null) {
            static::$settingsRepository = app(SettingsRepositoryInterface::class);
        }
        
        return static::$settingsRepository;
    }

    /**
     * 取得系統日期格式
     */
    public static function getDateFormat(): string
    {
        return Cache::remember('app.date_format', 3600, function () {
            $setting = static::getSettingsRepository()->getSetting('app.date_format');
            return $setting ? $setting->value : 'Y-m-d';
        });
    }

    /**
     * 取得系統時間格式
     */
    public static function getTimeFormat(): string
    {
        return Cache::remember('app.time_format', 3600, function () {
            $setting = static::getSettingsRepository()->getSetting('app.time_format');
            return $setting ? $setting->value : 'H:i';
        });
    }

    /**
     * 取得系統日期時間格式
     */
    public static function getDateTimeFormat(): string
    {
        return static::getDateFormat() . ' ' . static::getTimeFormat();
    }

    /**
     * 格式化日期
     */
    public static function formatDate($date, ?string $format = null): string
    {
        if (!$date) {
            return '';
        }

        $carbon = $date instanceof Carbon ? $date : Carbon::parse($date);
        $format = $format ?: static::getDateFormat();
        
        return $carbon->format($format);
    }

    /**
     * 格式化時間
     */
    public static function formatTime($time, ?string $format = null): string
    {
        if (!$time) {
            return '';
        }

        $carbon = $time instanceof Carbon ? $time : Carbon::parse($time);
        $format = $format ?: static::getTimeFormat();
        
        return $carbon->format($format);
    }

    /**
     * 格式化日期時間
     */
    public static function formatDateTime($datetime, ?string $format = null): string
    {
        if (!$datetime) {
            return '';
        }

        $carbon = $datetime instanceof Carbon ? $datetime : Carbon::parse($datetime);
        $format = $format ?: static::getDateTimeFormat();
        
        return $carbon->format($format);
    }

    /**
     * 格式化相對時間（如：2小時前）
     */
    public static function formatRelative($datetime): string
    {
        if (!$datetime) {
            return '';
        }

        $carbon = $datetime instanceof Carbon ? $datetime : Carbon::parse($datetime);
        
        return $carbon->diffForHumans();
    }

    /**
     * 格式化用於使用者列表顯示的日期時間
     * 根據時間距離現在的長短，使用不同的格式
     */
    public static function formatForUserList($datetime): string
    {
        if (!$datetime) {
            return '';
        }

        $carbon = $datetime instanceof Carbon ? $datetime : Carbon::parse($datetime);
        $now = Carbon::now();
        
        // 如果是今天，顯示相對時間
        if ($carbon->isToday()) {
            return $carbon->diffForHumans();
        }
        
        // 如果是本週，顯示星期幾和時間
        if ($carbon->isCurrentWeek()) {
            $locale = app()->getLocale();
            if ($locale === 'zh_TW') {
                $weekdays = ['日', '一', '二', '三', '四', '五', '六'];
                $weekday = '星期' . $weekdays[$carbon->dayOfWeek];
                return $weekday . ' ' . $carbon->format('H:i');
            } else {
                return $carbon->format('l H:i');
            }
        }
        
        // 如果是今年，顯示月日和時間
        if ($carbon->isCurrentYear()) {
            $locale = app()->getLocale();
            if ($locale === 'zh_TW') {
                return $carbon->format('n月j日 H:i');
            } else {
                return $carbon->format('M j, H:i');
            }
        }
        
        // 其他情況顯示完整日期時間
        return static::formatDateTime($carbon);
    }

    /**
     * 格式化為人類可讀的日期時間
     */
    public static function formatHuman($datetime, bool $includeTime = true): string
    {
        if (!$datetime) {
            return '';
        }

        $carbon = $datetime instanceof Carbon ? $datetime : Carbon::parse($datetime);
        
        if ($includeTime) {
            return static::formatDateTime($carbon);
        } else {
            return static::formatDate($carbon);
        }
    }

    /**
     * 取得當前時間的格式化字串
     */
    public static function now(?string $format = null): string
    {
        $format = $format ?: static::getDateTimeFormat();
        return Carbon::now()->format($format);
    }

    /**
     * 取得今天的格式化字串
     */
    public static function today(?string $format = null): string
    {
        $format = $format ?: static::getDateFormat();
        return Carbon::today()->format($format);
    }

    /**
     * 清除日期時間格式快取
     */
    public static function clearCache(): void
    {
        Cache::forget('app.date_format');
        Cache::forget('app.time_format');
    }

    /**
     * 驗證日期格式
     */
    public static function validateDateFormat(string $format): bool
    {
        try {
            Carbon::now()->format($format);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * 取得常用日期格式選項
     */
    public static function getDateFormatOptions(): array
    {
        $now = Carbon::now();
        
        return [
            'Y-m-d' => $now->format('Y-m-d') . ' (ISO 標準)',
            'd/m/Y' => $now->format('d/m/Y') . ' (歐洲格式)',
            'm/d/Y' => $now->format('m/d/Y') . ' (美國格式)',
            'd-m-Y' => $now->format('d-m-Y') . ' (短橫線)',
            'Y年m月d日' => $now->format('Y年m月d日') . ' (中文格式)',
            'M j, Y' => $now->format('M j, Y') . ' (英文格式)',
            'j F Y' => $now->format('j F Y') . ' (完整英文)',
        ];
    }

    /**
     * 取得常用時間格式選項
     */
    public static function getTimeFormatOptions(): array
    {
        $now = Carbon::now();
        
        return [
            'H:i' => $now->format('H:i') . ' (24小時制)',
            'g:i A' => $now->format('g:i A') . ' (12小時制)',
            'H:i:s' => $now->format('H:i:s') . ' (24小時制含秒)',
            'g:i:s A' => $now->format('g:i:s A') . ' (12小時制含秒)',
        ];
    }
}