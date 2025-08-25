<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Activity Log Time Localization
    |--------------------------------------------------------------------------
    |
    | The following language lines are used for time formatting and
    | localization in the activity log system.
    |
    */

    // Date and time formats
    'formats' => [
        'full_datetime' => 'F j, Y \a\t g:i:s A',           // January 15, 2024 at 3:30:45 PM
        'short_datetime' => 'm/d/Y H:i',                    // 01/15/2024 15:30
        'date_only' => 'F j, Y',                            // January 15, 2024
        'time_only' => 'g:i:s A',                           // 3:30:45 PM
        'compact' => 'm/d H:i',                             // 01/15 15:30
        'iso_datetime' => 'Y-m-d\TH:i:s\Z',                // 2024-01-15T15:30:45Z
        'human_date' => 'l, F j, Y',                        // Monday, January 15, 2024
        'human_time' => 'g:i A',                            // 3:30 PM
        'log_format' => 'Y-m-d H:i:s',                      // 2024-01-15 15:30:45
        'export_format' => 'Y-m-d H:i:s T',                // 2024-01-15 15:30:45 UTC
        'chart_hour' => 'g A',                              // 3 PM
        'chart_day' => 'M j',                               // Jan 15
        'chart_month' => 'M Y',                             // Jan 2024
        'filename_format' => 'Y-m-d_H-i-s',                // 2024-01-15_15-30-45
    ],

    // Relative time expressions
    'relative' => [
        'just_now' => 'just now',
        'seconds_ago' => [
            'one' => '1 second ago',
            'other' => ':count seconds ago',
        ],
        'minutes_ago' => [
            'one' => '1 minute ago',
            'other' => ':count minutes ago',
        ],
        'hours_ago' => [
            'one' => '1 hour ago',
            'other' => ':count hours ago',
        ],
        'days_ago' => [
            'one' => '1 day ago',
            'other' => ':count days ago',
        ],
        'weeks_ago' => [
            'one' => '1 week ago',
            'other' => ':count weeks ago',
        ],
        'months_ago' => [
            'one' => '1 month ago',
            'other' => ':count months ago',
        ],
        'years_ago' => [
            'one' => '1 year ago',
            'other' => ':count years ago',
        ],
        'in_seconds' => [
            'one' => 'in 1 second',
            'other' => 'in :count seconds',
        ],
        'in_minutes' => [
            'one' => 'in 1 minute',
            'other' => 'in :count minutes',
        ],
        'in_hours' => [
            'one' => 'in 1 hour',
            'other' => 'in :count hours',
        ],
        'in_days' => [
            'one' => 'in 1 day',
            'other' => 'in :count days',
        ],
        'in_weeks' => [
            'one' => 'in 1 week',
            'other' => 'in :count weeks',
        ],
        'in_months' => [
            'one' => 'in 1 month',
            'other' => 'in :count months',
        ],
        'in_years' => [
            'one' => 'in 1 year',
            'other' => 'in :count years',
        ],
    ],

    // Duration expressions
    'duration' => [
        'milliseconds' => [
            'short' => 'ms',
            'long' => 'milliseconds',
            'one' => '1 millisecond',
            'other' => ':count milliseconds',
        ],
        'seconds' => [
            'short' => 's',
            'long' => 'seconds',
            'one' => '1 second',
            'other' => ':count seconds',
        ],
        'minutes' => [
            'short' => 'm',
            'long' => 'minutes',
            'one' => '1 minute',
            'other' => ':count minutes',
        ],
        'hours' => [
            'short' => 'h',
            'long' => 'hours',
            'one' => '1 hour',
            'other' => ':count hours',
        ],
        'days' => [
            'short' => 'd',
            'long' => 'days',
            'one' => '1 day',
            'other' => ':count days',
        ],
        'weeks' => [
            'short' => 'w',
            'long' => 'weeks',
            'one' => '1 week',
            'other' => ':count weeks',
        ],
        'months' => [
            'short' => 'mo',
            'long' => 'months',
            'one' => '1 month',
            'other' => ':count months',
        ],
        'years' => [
            'short' => 'y',
            'long' => 'years',
            'one' => '1 year',
            'other' => ':count years',
        ],
    ],

    // Time periods
    'periods' => [
        'today' => 'Today',
        'yesterday' => 'Yesterday',
        'tomorrow' => 'Tomorrow',
        'this_week' => 'This Week',
        'last_week' => 'Last Week',
        'next_week' => 'Next Week',
        'this_month' => 'This Month',
        'last_month' => 'Last Month',
        'next_month' => 'Next Month',
        'this_year' => 'This Year',
        'last_year' => 'Last Year',
        'next_year' => 'Next Year',
        'last_7_days' => 'Last 7 Days',
        'last_30_days' => 'Last 30 Days',
        'last_90_days' => 'Last 90 Days',
        'last_6_months' => 'Last 6 Months',
        'last_12_months' => 'Last 12 Months',
        'custom_range' => 'Custom Range',
    ],

    // Weekdays
    'weekdays' => [
        'monday' => 'Monday',
        'tuesday' => 'Tuesday',
        'wednesday' => 'Wednesday',
        'thursday' => 'Thursday',
        'friday' => 'Friday',
        'saturday' => 'Saturday',
        'sunday' => 'Sunday',
    ],

    // Weekdays (short)
    'weekdays_short' => [
        'mon' => 'Mon',
        'tue' => 'Tue',
        'wed' => 'Wed',
        'thu' => 'Thu',
        'fri' => 'Fri',
        'sat' => 'Sat',
        'sun' => 'Sun',
    ],

    // Months
    'months' => [
        'january' => 'January',
        'february' => 'February',
        'march' => 'March',
        'april' => 'April',
        'may' => 'May',
        'june' => 'June',
        'july' => 'July',
        'august' => 'August',
        'september' => 'September',
        'october' => 'October',
        'november' => 'November',
        'december' => 'December',
    ],

    // Months (short)
    'months_short' => [
        'jan' => 'Jan',
        'feb' => 'Feb',
        'mar' => 'Mar',
        'apr' => 'Apr',
        'may' => 'May',
        'jun' => 'Jun',
        'jul' => 'Jul',
        'aug' => 'Aug',
        'sep' => 'Sep',
        'oct' => 'Oct',
        'nov' => 'Nov',
        'dec' => 'Dec',
    ],

    // Time zones
    'timezones' => [
        'utc' => 'UTC',
        'local' => 'Local Time',
        'server' => 'Server Time',
        'user' => 'User Time',
        'system' => 'System Time',
    ],

    // Time range descriptions
    'ranges' => [
        'all_time' => 'All Time',
        'real_time' => 'Real Time',
        'live' => 'Live',
        'recent' => 'Recent',
        'historical' => 'Historical',
        'archived' => 'Archived',
        'between' => 'Between :start and :end',
        'from' => 'From :start',
        'until' => 'Until :end',
        'during' => 'During :period',
        'before' => 'Before :time',
        'after' => 'After :time',
        'around' => 'Around :time',
    ],

    // Frequency expressions
    'frequency' => [
        'never' => 'Never',
        'once' => 'Once',
        'rarely' => 'Rarely',
        'sometimes' => 'Sometimes',
        'often' => 'Often',
        'frequently' => 'Frequently',
        'always' => 'Always',
        'per_second' => 'per second',
        'per_minute' => 'per minute',
        'per_hour' => 'per hour',
        'per_day' => 'per day',
        'per_week' => 'per week',
        'per_month' => 'per month',
        'per_year' => 'per year',
        'times_per_second' => ':count times per second',
        'times_per_minute' => ':count times per minute',
        'times_per_hour' => ':count times per hour',
        'times_per_day' => ':count times per day',
        'times_per_week' => ':count times per week',
        'times_per_month' => ':count times per month',
        'times_per_year' => ':count times per year',
    ],

    // Activity timing
    'activity_timing' => [
        'logged_at' => 'Logged at',
        'occurred_at' => 'Occurred at',
        'created_at' => 'Created at',
        'updated_at' => 'Updated at',
        'started_at' => 'Started at',
        'completed_at' => 'Completed at',
        'failed_at' => 'Failed at',
        'expired_at' => 'Expired at',
        'scheduled_at' => 'Scheduled at',
        'processed_at' => 'Processed at',
        'first_seen' => 'First seen',
        'last_seen' => 'Last seen',
        'duration' => 'Duration',
        'elapsed_time' => 'Elapsed time',
        'response_time' => 'Response time',
        'processing_time' => 'Processing time',
        'execution_time' => 'Execution time',
        'wait_time' => 'Wait time',
        'timeout' => 'Timeout',
        'deadline' => 'Deadline',
    ],

    // Time-based filters
    'filters' => [
        'any_time' => 'Any Time',
        'specific_date' => 'Specific Date',
        'date_range' => 'Date Range',
        'relative_time' => 'Relative Time',
        'business_hours' => 'Business Hours',
        'after_hours' => 'After Hours',
        'weekdays' => 'Weekdays',
        'weekends' => 'Weekends',
        'holidays' => 'Holidays',
        'working_days' => 'Working Days',
        'peak_hours' => 'Peak Hours',
        'off_peak_hours' => 'Off-peak Hours',
    ],

    // Time validation messages
    'validation' => [
        'invalid_date' => 'Invalid date format',
        'invalid_time' => 'Invalid time format',
        'invalid_datetime' => 'Invalid date and time format',
        'date_in_future' => 'Date cannot be in the future',
        'date_in_past' => 'Date cannot be in the past',
        'date_too_old' => 'Date is too old',
        'date_too_recent' => 'Date is too recent',
        'invalid_range' => 'Invalid date range',
        'range_too_large' => 'Date range is too large',
        'start_after_end' => 'Start date must be before end date',
        'invalid_timezone' => 'Invalid timezone',
        'invalid_duration' => 'Invalid duration format',
    ],

];