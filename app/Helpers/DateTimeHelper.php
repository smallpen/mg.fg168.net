<?php

namespace App\Helpers;

use Carbon\Carbon;
use Illuminate\Support\Facades\App;

/**
 * 日期時間格式化輔助類別
 * 
 * 提供本地化的日期時間格式化功能
 */
class DateTimeHelper
{
    /**
     * 格式化日期時間為本地化格式
     *
     * @param Carbon|string|null $datetime
     * @param string $format
     * @return string
     */
    public static function format($datetime, string $format = 'default'): string
    {
        if (!$datetime) {
            return '';
        }

        if (!$datetime instanceof Carbon) {
            $datetime = Carbon::parse($datetime);
        }

        $locale = App::getLocale();
        
        // 設定 Carbon 語言
        $datetime->locale($locale);

        return match ($format) {
            'default' => self::getDefaultFormat($datetime, $locale),
            'short' => self::getShortFormat($datetime, $locale),
            'long' => self::getLongFormat($datetime, $locale),
            'date_only' => self::getDateOnlyFormat($datetime, $locale),
            'time_only' => self::getTimeOnlyFormat($datetime, $locale),
            'relative' => self::getRelativeFormat($datetime, $locale),
            default => $datetime->format($format),
        };
    }

    /**
     * 取得預設格式
     */
    private static function getDefaultFormat(Carbon $datetime, string $locale): string
    {
        return match ($locale) {
            'zh_TW' => $datetime->format('Y年m月d日 H:i'),
            'en' => $datetime->format('M j, Y H:i'),
            default => $datetime->format('Y-m-d H:i'),
        };
    }

    /**
     * 取得簡短格式
     */
    private static function getShortFormat(Carbon $datetime, string $locale): string
    {
        return match ($locale) {
            'zh_TW' => $datetime->format('m/d H:i'),
            'en' => $datetime->format('M j H:i'),
            default => $datetime->format('m/d H:i'),
        };
    }

    /**
     * 取得完整格式
     */
    private static function getLongFormat(Carbon $datetime, string $locale): string
    {
        return match ($locale) {
            'zh_TW' => $datetime->format('Y年m月d日 (l) H:i:s'),
            'en' => $datetime->format('l, F j, Y H:i:s'),
            default => $datetime->format('Y-m-d (l) H:i:s'),
        };
    }

    /**
     * 取得僅日期格式
     */
    private static function getDateOnlyFormat(Carbon $datetime, string $locale): string
    {
        return match ($locale) {
            'zh_TW' => $datetime->format('Y年m月d日'),
            'en' => $datetime->format('M j, Y'),
            default => $datetime->format('Y-m-d'),
        };
    }

    /**
     * 取得僅時間格式
     */
    private static function getTimeOnlyFormat(Carbon $datetime, string $locale): string
    {
        return match ($locale) {
            'zh_TW' => $datetime->format('H:i'),
            'en' => $datetime->format('H:i'),
            default => $datetime->format('H:i'),
        };
    }

    /**
     * 取得相對時間格式
     */
    private static function getRelativeFormat(Carbon $datetime, string $locale): string
    {
        // 設定 Carbon 語言以獲得本地化的相對時間
        $datetime->locale($locale);
        
        return $datetime->diffForHumans();
    }

    /**
     * 格式化狀態變更時間
     */
    public static function formatStatusTime($datetime): string
    {
        if (!$datetime) {
            return __('admin.users.never');
        }

        return self::format($datetime, 'relative');
    }

    /**
     * 格式化建立時間
     */
    public static function formatCreatedAt($datetime): string
    {
        return self::format($datetime, 'default');
    }

    /**
     * 格式化更新時間
     */
    public static function formatUpdatedAt($datetime): string
    {
        return self::format($datetime, 'default');
    }

    /**
     * 取得本地化的星期名稱
     */
    public static function getLocalizedDayName(Carbon $datetime): string
    {
        $locale = App::getLocale();
        $datetime->locale($locale);
        
        return match ($locale) {
            'zh_TW' => $datetime->translatedFormat('l'),
            'en' => $datetime->format('l'),
            default => $datetime->format('l'),
        };
    }

    /**
     * 取得本地化的月份名稱
     */
    public static function getLocalizedMonthName(Carbon $datetime): string
    {
        $locale = App::getLocale();
        $datetime->locale($locale);
        
        return match ($locale) {
            'zh_TW' => $datetime->translatedFormat('F'),
            'en' => $datetime->format('F'),
            default => $datetime->format('F'),
        };
    }

    /**
     * 格式化使用者列表中的日期時間
     */
    public static function formatForUserList($datetime): string
    {
        if (!$datetime) {
            return __('admin.users.never');
        }

        $locale = App::getLocale();
        $carbon = $datetime instanceof Carbon ? $datetime : Carbon::parse($datetime);
        $carbon->locale($locale);

        // 如果是今天，顯示相對時間
        if ($carbon->isToday()) {
            return $carbon->diffForHumans();
        }

        // 如果是本週，顯示星期幾
        if ($carbon->isCurrentWeek()) {
            return match ($locale) {
                'zh_TW' => $carbon->translatedFormat('l H:i'),
                'en' => $carbon->format('l H:i'),
                default => $carbon->format('l H:i'),
            };
        }

        // 如果是今年，不顯示年份
        if ($carbon->isCurrentYear()) {
            return match ($locale) {
                'zh_TW' => $carbon->format('m月d日 H:i'),
                'en' => $carbon->format('M j H:i'),
                default => $carbon->format('m/d H:i'),
            };
        }

        // 其他情況顯示完整日期
        return self::format($carbon, 'default');
    }

    /**
     * 格式化狀態變更的相對時間
     */
    public static function formatStatusChangeTime($datetime): string
    {
        if (!$datetime) {
            return __('admin.users.never');
        }

        $locale = App::getLocale();
        $carbon = $datetime instanceof Carbon ? $datetime : Carbon::parse($datetime);
        $carbon->locale($locale);

        $diffInSeconds = $carbon->diffInSeconds(now());

        // 剛剛（30秒內）
        if ($diffInSeconds < 30) {
            return __('admin.users.just_now');
        }

        // 使用相對時間
        return $carbon->diffForHumans();
    }

    /**
     * 取得本地化的時間範圍描述
     */
    public static function getTimeRangeDescription(Carbon $start, Carbon $end): string
    {
        $locale = App::getLocale();
        $start->locale($locale);
        $end->locale($locale);

        return match ($locale) {
            'zh_TW' => sprintf(
                '%s 至 %s',
                $start->format('Y年m月d日 H:i'),
                $end->format('Y年m月d日 H:i')
            ),
            'en' => sprintf(
                '%s to %s',
                $start->format('M j, Y H:i'),
                $end->format('M j, Y H:i')
            ),
            default => sprintf(
                '%s - %s',
                $start->format('Y-m-d H:i'),
                $end->format('Y-m-d H:i')
            ),
        };
    }
}