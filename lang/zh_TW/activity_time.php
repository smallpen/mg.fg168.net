<?php

return [

    /*
    |--------------------------------------------------------------------------
    | 活動記錄時間本地化
    |--------------------------------------------------------------------------
    |
    | 以下語言行用於活動記錄系統中的時間格式化和本地化。
    |
    */

    // 日期和時間格式
    'formats' => [
        'full_datetime' => 'Y年m月d日 H:i:s',               // 2024年1月15日 15:30:45
        'short_datetime' => 'Y/m/d H:i',                    // 2024/01/15 15:30
        'date_only' => 'Y年m月d日',                         // 2024年1月15日
        'time_only' => 'H:i:s',                             // 15:30:45
        'compact' => 'm/d H:i',                             // 01/15 15:30
        'iso_datetime' => 'Y-m-d\TH:i:s\Z',                // 2024-01-15T15:30:45Z
        'human_date' => 'Y年m月d日 (l)',                    // 2024年1月15日 (星期一)
        'human_time' => 'H:i',                              // 15:30
        'log_format' => 'Y-m-d H:i:s',                      // 2024-01-15 15:30:45
        'export_format' => 'Y-m-d H:i:s T',                // 2024-01-15 15:30:45 UTC
        'chart_hour' => 'H時',                              // 15時
        'chart_day' => 'm月d日',                            // 1月15日
        'chart_month' => 'Y年m月',                          // 2024年1月
        'filename_format' => 'Y-m-d_H-i-s',                // 2024-01-15_15-30-45
    ],

    // 相對時間表達
    'relative' => [
        'just_now' => '剛剛',
        'seconds_ago' => [
            'one' => '1 秒前',
            'other' => ':count 秒前',
        ],
        'minutes_ago' => [
            'one' => '1 分鐘前',
            'other' => ':count 分鐘前',
        ],
        'hours_ago' => [
            'one' => '1 小時前',
            'other' => ':count 小時前',
        ],
        'days_ago' => [
            'one' => '1 天前',
            'other' => ':count 天前',
        ],
        'weeks_ago' => [
            'one' => '1 週前',
            'other' => ':count 週前',
        ],
        'months_ago' => [
            'one' => '1 個月前',
            'other' => ':count 個月前',
        ],
        'years_ago' => [
            'one' => '1 年前',
            'other' => ':count 年前',
        ],
        'in_seconds' => [
            'one' => '1 秒後',
            'other' => ':count 秒後',
        ],
        'in_minutes' => [
            'one' => '1 分鐘後',
            'other' => ':count 分鐘後',
        ],
        'in_hours' => [
            'one' => '1 小時後',
            'other' => ':count 小時後',
        ],
        'in_days' => [
            'one' => '1 天後',
            'other' => ':count 天後',
        ],
        'in_weeks' => [
            'one' => '1 週後',
            'other' => ':count 週後',
        ],
        'in_months' => [
            'one' => '1 個月後',
            'other' => ':count 個月後',
        ],
        'in_years' => [
            'one' => '1 年後',
            'other' => ':count 年後',
        ],
    ],

    // 持續時間表達
    'duration' => [
        'milliseconds' => [
            'short' => '毫秒',
            'long' => '毫秒',
            'one' => '1 毫秒',
            'other' => ':count 毫秒',
        ],
        'seconds' => [
            'short' => '秒',
            'long' => '秒',
            'one' => '1 秒',
            'other' => ':count 秒',
        ],
        'minutes' => [
            'short' => '分',
            'long' => '分鐘',
            'one' => '1 分鐘',
            'other' => ':count 分鐘',
        ],
        'hours' => [
            'short' => '時',
            'long' => '小時',
            'one' => '1 小時',
            'other' => ':count 小時',
        ],
        'days' => [
            'short' => '天',
            'long' => '天',
            'one' => '1 天',
            'other' => ':count 天',
        ],
        'weeks' => [
            'short' => '週',
            'long' => '週',
            'one' => '1 週',
            'other' => ':count 週',
        ],
        'months' => [
            'short' => '月',
            'long' => '個月',
            'one' => '1 個月',
            'other' => ':count 個月',
        ],
        'years' => [
            'short' => '年',
            'long' => '年',
            'one' => '1 年',
            'other' => ':count 年',
        ],
    ],

    // 時間週期
    'periods' => [
        'today' => '今天',
        'yesterday' => '昨天',
        'tomorrow' => '明天',
        'this_week' => '本週',
        'last_week' => '上週',
        'next_week' => '下週',
        'this_month' => '本月',
        'last_month' => '上月',
        'next_month' => '下月',
        'this_year' => '今年',
        'last_year' => '去年',
        'next_year' => '明年',
        'last_7_days' => '過去 7 天',
        'last_30_days' => '過去 30 天',
        'last_90_days' => '過去 90 天',
        'last_6_months' => '過去 6 個月',
        'last_12_months' => '過去 12 個月',
        'custom_range' => '自訂範圍',
    ],

    // 星期
    'weekdays' => [
        'monday' => '星期一',
        'tuesday' => '星期二',
        'wednesday' => '星期三',
        'thursday' => '星期四',
        'friday' => '星期五',
        'saturday' => '星期六',
        'sunday' => '星期日',
    ],

    // 星期（簡寫）
    'weekdays_short' => [
        'mon' => '一',
        'tue' => '二',
        'wed' => '三',
        'thu' => '四',
        'fri' => '五',
        'sat' => '六',
        'sun' => '日',
    ],

    // 月份
    'months' => [
        'january' => '一月',
        'february' => '二月',
        'march' => '三月',
        'april' => '四月',
        'may' => '五月',
        'june' => '六月',
        'july' => '七月',
        'august' => '八月',
        'september' => '九月',
        'october' => '十月',
        'november' => '十一月',
        'december' => '十二月',
    ],

    // 月份（簡寫）
    'months_short' => [
        'jan' => '1月',
        'feb' => '2月',
        'mar' => '3月',
        'apr' => '4月',
        'may' => '5月',
        'jun' => '6月',
        'jul' => '7月',
        'aug' => '8月',
        'sep' => '9月',
        'oct' => '10月',
        'nov' => '11月',
        'dec' => '12月',
    ],

    // 時區
    'timezones' => [
        'utc' => 'UTC',
        'local' => '本地時間',
        'server' => '伺服器時間',
        'user' => '使用者時間',
        'system' => '系統時間',
    ],

    // 時間範圍描述
    'ranges' => [
        'all_time' => '所有時間',
        'real_time' => '即時',
        'live' => '即時',
        'recent' => '最近',
        'historical' => '歷史',
        'archived' => '已歸檔',
        'between' => ':start 到 :end',
        'from' => '從 :start',
        'until' => '直到 :end',
        'during' => '在 :period 期間',
        'before' => ':time 之前',
        'after' => ':time 之後',
        'around' => ':time 前後',
    ],

    // 頻率表達
    'frequency' => [
        'never' => '從不',
        'once' => '一次',
        'rarely' => '很少',
        'sometimes' => '有時',
        'often' => '經常',
        'frequently' => '頻繁',
        'always' => '總是',
        'per_second' => '每秒',
        'per_minute' => '每分鐘',
        'per_hour' => '每小時',
        'per_day' => '每天',
        'per_week' => '每週',
        'per_month' => '每月',
        'per_year' => '每年',
        'times_per_second' => '每秒 :count 次',
        'times_per_minute' => '每分鐘 :count 次',
        'times_per_hour' => '每小時 :count 次',
        'times_per_day' => '每天 :count 次',
        'times_per_week' => '每週 :count 次',
        'times_per_month' => '每月 :count 次',
        'times_per_year' => '每年 :count 次',
    ],

    // 活動時間
    'activity_timing' => [
        'logged_at' => '記錄於',
        'occurred_at' => '發生於',
        'created_at' => '建立於',
        'updated_at' => '更新於',
        'started_at' => '開始於',
        'completed_at' => '完成於',
        'failed_at' => '失敗於',
        'expired_at' => '過期於',
        'scheduled_at' => '排程於',
        'processed_at' => '處理於',
        'first_seen' => '首次發現',
        'last_seen' => '最後發現',
        'duration' => '持續時間',
        'elapsed_time' => '經過時間',
        'response_time' => '回應時間',
        'processing_time' => '處理時間',
        'execution_time' => '執行時間',
        'wait_time' => '等待時間',
        'timeout' => '逾時',
        'deadline' => '截止時間',
    ],

    // 基於時間的篩選
    'filters' => [
        'any_time' => '任何時間',
        'specific_date' => '特定日期',
        'date_range' => '日期範圍',
        'relative_time' => '相對時間',
        'business_hours' => '營業時間',
        'after_hours' => '非營業時間',
        'weekdays' => '工作日',
        'weekends' => '週末',
        'holidays' => '假日',
        'working_days' => '工作天',
        'peak_hours' => '尖峰時段',
        'off_peak_hours' => '離峰時段',
    ],

    // 時間驗證訊息
    'validation' => [
        'invalid_date' => '無效的日期格式',
        'invalid_time' => '無效的時間格式',
        'invalid_datetime' => '無效的日期時間格式',
        'date_in_future' => '日期不能是未來時間',
        'date_in_past' => '日期不能是過去時間',
        'date_too_old' => '日期過於久遠',
        'date_too_recent' => '日期過於接近',
        'invalid_range' => '無效的日期範圍',
        'range_too_large' => '日期範圍過大',
        'start_after_end' => '開始日期必須早於結束日期',
        'invalid_timezone' => '無效的時區',
        'invalid_duration' => '無效的持續時間格式',
    ],

];