<?php

return [
    /*
    |--------------------------------------------------------------------------
    | 通路管理日誌配置
    |--------------------------------------------------------------------------
    |
    | 此配置檔案定義了通路管理系統的日誌通道和處理器
    |
    */

    'channels' => [
        /*
        |--------------------------------------------------------------------------
        | 通路管理主要日誌通道
        |--------------------------------------------------------------------------
        */
        'channel_management' => [
            'driver' => 'stack',
            'channels' => ['channel_daily', 'channel_database'],
            'ignore_exceptions' => false,
        ],

        /*
        |--------------------------------------------------------------------------
        | 通路管理每日日誌
        |--------------------------------------------------------------------------
        */
        'channel_daily' => [
            'driver' => 'daily',
            'path' => storage_path('logs/channel-management/channel-management.log'),
            'level' => env('CHANNEL_LOG_LEVEL', 'info'),
            'days' => env('CHANNEL_LOG_RETENTION_DAYS', 30),
            'replace_placeholders' => true,
            'formatter' => App\Logging\ChannelManagementFormatter::class,
        ],

        /*
        |--------------------------------------------------------------------------
        | 通路管理資料庫日誌
        |--------------------------------------------------------------------------
        */
        'channel_database' => [
            'driver' => 'custom',
            'via' => App\Logging\ChannelManagementDatabaseLogger::class,
            'level' => env('CHANNEL_DB_LOG_LEVEL', 'warning'),
        ],

        /*
        |--------------------------------------------------------------------------
        | 代理操作日誌
        |--------------------------------------------------------------------------
        */
        'agent_operations' => [
            'driver' => 'daily',
            'path' => storage_path('logs/channel-management/agent-operations.log'),
            'level' => 'info',
            'days' => env('CHANNEL_AGENT_LOG_RETENTION_DAYS', 90),
            'replace_placeholders' => true,
            'formatter' => App\Logging\AgentOperationFormatter::class,
        ],

        /*
        |--------------------------------------------------------------------------
        | 玩家操作日誌
        |--------------------------------------------------------------------------
        */
        'player_operations' => [
            'driver' => 'daily',
            'path' => storage_path('logs/channel-management/player-operations.log'),
            'level' => 'info',
            'days' => env('CHANNEL_PLAYER_LOG_RETENTION_DAYS', 90),
            'replace_placeholders' => true,
            'formatter' => App\Logging\PlayerOperationFormatter::class,
        ],

        /*
        |--------------------------------------------------------------------------
        | 點數交易日誌
        |--------------------------------------------------------------------------
        */
        'point_transactions' => [
            'driver' => 'daily',
            'path' => storage_path('logs/channel-management/point-transactions.log'),
            'level' => 'info',
            'days' => env('CHANNEL_POINT_LOG_RETENTION_DAYS', 365),
            'replace_placeholders' => true,
            'formatter' => App\Logging\PointTransactionFormatter::class,
        ],

        /*
        |--------------------------------------------------------------------------
        | 效能監控日誌
        |--------------------------------------------------------------------------
        */
        'channel_performance' => [
            'driver' => 'daily',
            'path' => storage_path('logs/channel-management/performance.log'),
            'level' => 'debug',
            'days' => env('CHANNEL_PERFORMANCE_LOG_RETENTION_DAYS', 7),
            'replace_placeholders' => true,
        ],

        /*
        |--------------------------------------------------------------------------
        | 安全稽核日誌
        |--------------------------------------------------------------------------
        */
        'channel_security' => [
            'driver' => 'daily',
            'path' => storage_path('logs/channel-management/security-audit.log'),
            'level' => 'info',
            'days' => env('CHANNEL_SECURITY_LOG_RETENTION_DAYS', 365),
            'replace_placeholders' => true,
            'permission' => 0600, // 限制檔案權限
        ],

        /*
        |--------------------------------------------------------------------------
        | 錯誤和異常日誌
        |--------------------------------------------------------------------------
        */
        'channel_errors' => [
            'driver' => 'daily',
            'path' => storage_path('logs/channel-management/errors.log'),
            'level' => 'error',
            'days' => env('CHANNEL_ERROR_LOG_RETENTION_DAYS', 90),
            'replace_placeholders' => true,
        ],

        /*
        |--------------------------------------------------------------------------
        | API 存取日誌
        |--------------------------------------------------------------------------
        */
        'channel_api' => [
            'driver' => 'daily',
            'path' => storage_path('logs/channel-management/api-access.log'),
            'level' => 'info',
            'days' => env('CHANNEL_API_LOG_RETENTION_DAYS', 30),
            'replace_placeholders' => true,
            'formatter' => App\Logging\ApiAccessFormatter::class,
        ],

        /*
        |--------------------------------------------------------------------------
        | 系統維護日誌
        |--------------------------------------------------------------------------
        */
        'channel_maintenance' => [
            'driver' => 'daily',
            'path' => storage_path('logs/channel-management/maintenance.log'),
            'level' => 'info',
            'days' => env('CHANNEL_MAINTENANCE_LOG_RETENTION_DAYS', 180),
            'replace_placeholders' => true,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | 日誌處理器配置
    |--------------------------------------------------------------------------
    */
    'processors' => [
        /*
         * 添加請求 ID 到日誌記錄
         */
        'request_id' => [
            'class' => App\Logging\Processors\RequestIdProcessor::class,
        ],

        /*
         * 添加使用者資訊到日誌記錄
         */
        'user_context' => [
            'class' => App\Logging\Processors\UserContextProcessor::class,
        ],

        /*
         * 添加效能資訊到日誌記錄
         */
        'performance' => [
            'class' => App\Logging\Processors\PerformanceProcessor::class,
        ],

        /*
         * 添加記憶體使用資訊到日誌記錄
         */
        'memory_usage' => [
            'class' => App\Logging\Processors\MemoryUsageProcessor::class,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | 日誌格式化器配置
    |--------------------------------------------------------------------------
    */
    'formatters' => [
        /*
         * 通路管理專用格式化器
         */
        'channel_management' => [
            'class' => App\Logging\ChannelManagementFormatter::class,
            'format' => '[%datetime%] %channel%.%level_name%: %message% %context% %extra%',
            'date_format' => 'Y-m-d H:i:s',
            'include_stack_traces' => true,
        ],

        /*
         * 代理操作格式化器
         */
        'agent_operation' => [
            'class' => App\Logging\AgentOperationFormatter::class,
            'format' => '[%datetime%] [%extra.user_id%] %level_name%: %message% %context%',
            'date_format' => 'Y-m-d H:i:s',
        ],

        /*
         * 點數交易格式化器
         */
        'point_transaction' => [
            'class' => App\Logging\PointTransactionFormatter::class,
            'format' => '[%datetime%] [%extra.transaction_id%] %level_name%: %message% %context%',
            'date_format' => 'Y-m-d H:i:s',
        ],

        /*
         * API 存取格式化器
         */
        'api_access' => [
            'class' => App\Logging\ApiAccessFormatter::class,
            'format' => '[%datetime%] %extra.method% %extra.uri% %extra.status_code% %extra.response_time%ms %message%',
            'date_format' => 'Y-m-d H:i:s',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | 日誌過濾器配置
    |--------------------------------------------------------------------------
    */
    'filters' => [
        /*
         * 敏感資料過濾器
         */
        'sensitive_data' => [
            'class' => App\Logging\Filters\SensitiveDataFilter::class,
            'fields' => ['password', 'token', 'secret', 'key'],
            'replacement' => '[FILTERED]',
        ],

        /*
         * 個人資料過濾器
         */
        'personal_data' => [
            'class' => App\Logging\Filters\PersonalDataFilter::class,
            'fields' => ['email', 'phone', 'id_number'],
            'replacement' => '[REDACTED]',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | 日誌輪轉配置
    |--------------------------------------------------------------------------
    */
    'rotation' => [
        /*
         * 檔案大小限制（MB）
         */
        'max_file_size' => env('CHANNEL_LOG_MAX_FILE_SIZE', 100),

        /*
         * 保留檔案數量
         */
        'max_files' => env('CHANNEL_LOG_MAX_FILES', 10),

        /*
         * 壓縮舊檔案
         */
        'compress_rotated' => env('CHANNEL_LOG_COMPRESS_ROTATED', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | 警報配置
    |--------------------------------------------------------------------------
    */
    'alerts' => [
        /*
         * 錯誤警報
         */
        'error_threshold' => env('CHANNEL_ERROR_ALERT_THRESHOLD', 10),
        'error_time_window' => env('CHANNEL_ERROR_ALERT_TIME_WINDOW', 60), // 分鐘

        /*
         * 效能警報
         */
        'slow_query_threshold' => env('CHANNEL_SLOW_QUERY_ALERT_THRESHOLD', 1000), // 毫秒
        'memory_threshold' => env('CHANNEL_MEMORY_ALERT_THRESHOLD', 128), // MB

        /*
         * 安全警報
         */
        'failed_login_threshold' => env('CHANNEL_FAILED_LOGIN_ALERT_THRESHOLD', 5),
        'suspicious_activity_threshold' => env('CHANNEL_SUSPICIOUS_ACTIVITY_ALERT_THRESHOLD', 3),
    ],

    /*
    |--------------------------------------------------------------------------
    | 日誌分析配置
    |--------------------------------------------------------------------------
    */
    'analysis' => [
        /*
         * 啟用日誌分析
         */
        'enabled' => env('CHANNEL_LOG_ANALYSIS_ENABLED', true),

        /*
         * 分析間隔（分鐘）
         */
        'interval' => env('CHANNEL_LOG_ANALYSIS_INTERVAL', 60),

        /*
         * 分析報告保留天數
         */
        'report_retention_days' => env('CHANNEL_LOG_ANALYSIS_RETENTION', 30),

        /*
         * 分析指標
         */
        'metrics' => [
            'error_rate',
            'response_time',
            'user_activity',
            'point_transaction_volume',
            'agent_operations',
            'player_operations',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | 匯出配置
    |--------------------------------------------------------------------------
    */
    'export' => [
        /*
         * 支援的匯出格式
         */
        'formats' => ['json', 'csv', 'xml'],

        /*
         * 匯出檔案保留天數
         */
        'retention_days' => env('CHANNEL_LOG_EXPORT_RETENTION', 7),

        /*
         * 單次匯出最大記錄數
         */
        'max_records' => env('CHANNEL_LOG_EXPORT_MAX_RECORDS', 10000),

        /*
         * 匯出檔案儲存路徑
         */
        'storage_path' => env('CHANNEL_LOG_EXPORT_PATH', 'exports/logs'),
    ],
];