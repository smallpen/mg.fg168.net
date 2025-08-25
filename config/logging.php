<?php

return [

    /*
    |--------------------------------------------------------------------------
    | 預設日誌頻道
    |--------------------------------------------------------------------------
    |
    | 此選項定義應用程式使用的預設日誌頻道。
    |
    */

    'default' => env('LOG_CHANNEL', 'stack'),

    /*
    |--------------------------------------------------------------------------
    | 棄用警告
    |--------------------------------------------------------------------------
    |
    | 此選項控制是否記錄棄用警告。
    |
    */

    'deprecations' => [
        'channel' => env('LOG_DEPRECATIONS_CHANNEL', 'null'),
        'trace' => false,
    ],

    /*
    |--------------------------------------------------------------------------
    | 日誌頻道
    |--------------------------------------------------------------------------
    |
    | 在此處定義所有日誌頻道及其驅動程式。
    |
    */

    'channels' => [

        'stack' => [
            'driver' => 'stack',
            'channels' => ['single', 'admin_activity', 'security'],
            'ignore_exceptions' => false,
        ],

        'single' => [
            'driver' => 'single',
            'path' => storage_path('logs/laravel.log'),
            'level' => env('LOG_LEVEL', 'debug'),
            'replace_placeholders' => true,
        ],

        'daily' => [
            'driver' => 'daily',
            'path' => storage_path('logs/laravel.log'),
            'level' => env('LOG_LEVEL', 'debug'),
            'days' => 14,
            'replace_placeholders' => true,
        ],

        'slack' => [
            'driver' => 'slack',
            'url' => env('LOG_SLACK_WEBHOOK_URL'),
            'username' => 'Laravel Log',
            'emoji' => ':boom:',
            'level' => env('LOG_LEVEL', 'critical'),
            'replace_placeholders' => true,
        ],

        'papertrail' => [
            'driver' => 'monolog',
            'level' => env('LOG_LEVEL', 'debug'),
            'handler' => env('LOG_PAPERTRAIL_HANDLER', SyslogUdpHandler::class),
            'handler_with' => [
                'host' => env('PAPERTRAIL_URL'),
                'port' => env('PAPERTRAIL_PORT'),
                'connectionString' => 'tls://'.env('PAPERTRAIL_URL').':'.env('PAPERTRAIL_PORT'),
            ],
            'processors' => [PsrLogMessageProcessor::class],
        ],

        'stderr' => [
            'driver' => 'monolog',
            'level' => env('LOG_LEVEL', 'debug'),
            'handler' => StreamHandler::class,
            'formatter' => env('LOG_STDERR_FORMATTER'),
            'with' => [
                'stream' => 'php://stderr',
            ],
            'processors' => [PsrLogMessageProcessor::class],
        ],

        'syslog' => [
            'driver' => 'syslog',
            'level' => env('LOG_LEVEL', 'debug'),
            'facility' => LOG_USER,
            'replace_placeholders' => true,
        ],

        // 使用者管理錯誤日誌
        'user_management' => [
            'driver' => 'daily',
            'path' => storage_path('logs/user_management.log'),
            'level' => env('LOG_LEVEL', 'debug'),
            'days' => 30,
            'replace_placeholders' => true,
        ],

        // 驗證錯誤日誌
        'validation' => [
            'driver' => 'daily',
            'path' => storage_path('logs/validation.log'),
            'level' => env('LOG_LEVEL', 'debug'),
            'days' => 14,
            'replace_placeholders' => true,
        ],

        // 網路錯誤日誌
        'network' => [
            'driver' => 'daily',
            'path' => storage_path('logs/network.log'),
            'level' => env('LOG_LEVEL', 'debug'),
            'days' => 7,
            'replace_placeholders' => true,
        ],

        // 資料庫錯誤日誌
        'database' => [
            'driver' => 'daily',
            'path' => storage_path('logs/database.log'),
            'level' => env('LOG_LEVEL', 'debug'),
            'days' => 30,
            'replace_placeholders' => true,
        ],

        // 系統錯誤日誌
        'system' => [
            'driver' => 'daily',
            'path' => storage_path('logs/system.log'),
            'level' => env('LOG_LEVEL', 'debug'),
            'days' => 30,
            'replace_placeholders' => true,
        ],

        // 健康狀態日誌
        'health' => [
            'driver' => 'daily',
            'path' => storage_path('logs/health.log'),
            'level' => env('LOG_LEVEL', 'debug'),
            'days' => 7,
            'replace_placeholders' => true,
        ],

        'errorlog' => [
            'driver' => 'errorlog',
            'level' => env('LOG_LEVEL', 'debug'),
            'replace_placeholders' => true,
        ],

        'null' => [
            'driver' => 'monolog',
            'handler' => NullHandler::class,
        ],

        'emergency' => [
            'path' => storage_path('logs/laravel.log'),
        ],

        // 自定義頻道：管理員活動日誌
        'admin_activity' => [
            'driver' => 'daily',
            'path' => storage_path('logs/admin_activity.log'),
            'level' => 'info',
            'days' => 30,
            'replace_placeholders' => true,
        ],

        // 自定義頻道：安全事件日誌
        'security' => [
            'driver' => 'daily',
            'path' => storage_path('logs/security.log'),
            'level' => 'warning',
            'days' => 90,
            'replace_placeholders' => true,
        ],

        // 自定義頻道：效能監控日誌
        'performance' => [
            'driver' => 'daily',
            'path' => storage_path('logs/performance.log'),
            'level' => 'info',
            'days' => 7,
            'replace_placeholders' => true,
        ],

        // 自定義頻道：系統健康檢查日誌
        'health' => [
            'driver' => 'daily',
            'path' => storage_path('logs/health.log'),
            'level' => 'info',
            'days' => 14,
            'replace_placeholders' => true,
        ],

        // 自定義頻道：備份日誌
        'backup' => [
            'driver' => 'daily',
            'path' => storage_path('logs/backup.log'),
            'level' => 'info',
            'days' => 30,
            'replace_placeholders' => true,
        ],

        // 自定義頻道：活動記錄日誌
        'activity' => [
            'driver' => 'daily',
            'path' => storage_path('logs/activity.log'),
            'level' => 'info',
            'days' => 90,
            'replace_placeholders' => true,
        ],

        // 自定義頻道：多語系日誌
        'multilingual' => [
            'driver' => 'daily',
            'path' => storage_path('logs/multilingual.log'),
            'level' => env('MULTILINGUAL_LOG_LEVEL', 'info'),
            'days' => env('MULTILINGUAL_LOG_RETENTION_DAYS', 30),
            'replace_placeholders' => true,
            'tap' => [App\Logging\MultilingualLogFormatter::class],
        ],

        // 多語系錯誤專用頻道
        'multilingual_errors' => [
            'driver' => 'daily',
            'path' => storage_path('logs/multilingual_errors.log'),
            'level' => 'warning',
            'days' => env('MULTILINGUAL_ERROR_LOG_RETENTION_DAYS', 60),
            'replace_placeholders' => true,
            'tap' => [App\Logging\MultilingualLogFormatter::class],
        ],

        // 多語系效能監控頻道
        'multilingual_performance' => [
            'driver' => 'daily',
            'path' => storage_path('logs/multilingual_performance.log'),
            'level' => 'info',
            'days' => env('MULTILINGUAL_PERFORMANCE_LOG_RETENTION_DAYS', 14),
            'replace_placeholders' => true,
        ],

    ],

];