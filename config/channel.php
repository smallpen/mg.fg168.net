<?php

return [
    /*
    |--------------------------------------------------------------------------
    | 通路管理系統配置
    |--------------------------------------------------------------------------
    |
    | 這裡包含通路管理系統的各項配置設定
    |
    */

    /*
    |--------------------------------------------------------------------------
    | 監控系統配置
    |--------------------------------------------------------------------------
    */
    'monitoring' => [
        /*
        | 監控閾值設定
        */
        'thresholds' => [
            'points_discrepancy' => env('CHANNEL_POINTS_DISCREPANCY_THRESHOLD', 100.0),
            'negative_points_count' => env('CHANNEL_NEGATIVE_POINTS_THRESHOLD', 1),
            'orphaned_records_count' => env('CHANNEL_ORPHANED_RECORDS_THRESHOLD', 5),
            'failed_transactions_rate' => env('CHANNEL_FAILED_TRANSACTIONS_RATE', 0.05),
            'response_time_ms' => env('CHANNEL_RESPONSE_TIME_THRESHOLD', 1000),
        ],

        /*
        | 警報通道設定
        */
        'alert_channels' => [
            'log',        // 記錄到日誌
            'database',   // 儲存到資料庫
            // 'notification', // 發送通知（需要配置通知設定）
            // 'email',       // 發送郵件
            // 'slack',       // 發送到 Slack
        ],

        /*
        | 監控排程設定
        */
        'schedule' => [
            'integrity_check' => env('CHANNEL_INTEGRITY_CHECK_SCHEDULE', '0 */6 * * *'), // 每6小時
            'monitoring_check' => env('CHANNEL_MONITORING_CHECK_SCHEDULE', '*/15 * * * *'), // 每15分鐘
            'backup' => env('CHANNEL_BACKUP_SCHEDULE', '0 2 * * *'), // 每日凌晨2點
            'cleanup' => env('CHANNEL_CLEANUP_SCHEDULE', '0 3 * * 0'), // 每週日凌晨3點
        ],

        /*
        | 自動修復設定
        */
        'auto_repair' => [
            'enabled' => env('CHANNEL_AUTO_REPAIR_ENABLED', false),
            'max_attempts' => env('CHANNEL_AUTO_REPAIR_MAX_ATTEMPTS', 3),
            'retry_delay' => env('CHANNEL_AUTO_REPAIR_RETRY_DELAY', 300), // 5分鐘
        ],

        /*
        | 資料保留設定
        */
        'retention' => [
            'alerts_days' => env('CHANNEL_ALERTS_RETENTION_DAYS', 30),
            'backups_count' => env('CHANNEL_BACKUPS_RETENTION_COUNT', 10),
            'logs_days' => env('CHANNEL_LOGS_RETENTION_DAYS', 90),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | 業務規則配置
    |--------------------------------------------------------------------------
    */
    'business_rules' => [
        /*
        | 前置符號規則
        */
        'prefix' => [
            'pattern' => '/^[a-z]$/',
            'required_for_level_1' => true,
            'inherit_from_parent' => true,
        ],

        /*
        | 帳號格式規則
        */
        'account' => [
            'pattern' => '/^[a-z][a-zA-Z0-9_]+$/',
            'min_length' => 2,
            'max_length' => 50,
        ],

        /*
        | 點數管理規則
        */
        'points' => [
            'allow_negative' => false,
            'precision' => 2,
            'min_allocation' => 0.01,
            'max_allocation' => 999999999.99,
        ],

        /*
        | 層級結構規則
        */
        'hierarchy' => [
            'max_levels' => env('CHANNEL_MAX_LEVELS', null), // null = 無限制
            'allow_circular_reference' => false,
            'require_parent_for_non_root' => true,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | 效能配置
    |--------------------------------------------------------------------------
    */
    'performance' => [
        /*
        | 快取設定
        */
        'cache' => [
            'enabled' => env('CHANNEL_CACHE_ENABLED', true),
            'ttl' => env('CHANNEL_CACHE_TTL', 3600), // 1小時
            'prefix' => 'channel:',
        ],

        /*
        | 分頁設定
        */
        'pagination' => [
            'default_per_page' => 25,
            'max_per_page' => 100,
            'per_page_options' => [10, 25, 50, 100],
        ],

        /*
        | 批次處理設定
        */
        'batch' => [
            'chunk_size' => env('CHANNEL_BATCH_CHUNK_SIZE', 1000),
            'max_execution_time' => env('CHANNEL_MAX_EXECUTION_TIME', 300), // 5分鐘
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | 安全配置
    |--------------------------------------------------------------------------
    */
    'security' => [
        /*
        | 稽核日誌
        */
        'audit' => [
            'enabled' => env('CHANNEL_AUDIT_ENABLED', true),
            'log_all_operations' => true,
            'sensitive_fields' => ['password', 'token', 'secret'],
        ],

        /*
        | 存取控制
        */
        'access_control' => [
            'require_permission_check' => true,
            'log_unauthorized_access' => true,
            'rate_limiting' => [
                'enabled' => env('CHANNEL_RATE_LIMITING_ENABLED', true),
                'max_attempts' => 60,
                'decay_minutes' => 1,
            ],
        ],

        /*
        | 資料驗證
        */
        'validation' => [
            'strict_mode' => env('CHANNEL_STRICT_VALIDATION', true),
            'sanitize_input' => true,
            'validate_relationships' => true,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | 備份配置
    |--------------------------------------------------------------------------
    */
    'backup' => [
        /*
        | 備份設定
        */
        'enabled' => env('CHANNEL_BACKUP_ENABLED', true),
        'storage_path' => env('CHANNEL_BACKUP_PATH', storage_path('backups')),
        'compress' => env('CHANNEL_BACKUP_COMPRESS', true),
        'encrypt' => env('CHANNEL_BACKUP_ENCRYPT', false),
        'verify_after_backup' => env('CHANNEL_BACKUP_VERIFY', true),

        /*
        | 包含的表格
        */
        'tables' => [
            'agents',
            'players',
            'point_transactions',
        ],

        /*
        | 排除的欄位
        */
        'exclude_fields' => [
            // 'password', // 如果有敏感欄位
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | 通知配置
    |--------------------------------------------------------------------------
    */
    'notifications' => [
        /*
        | 警報通知
        */
        'alerts' => [
            'enabled' => env('CHANNEL_NOTIFICATIONS_ENABLED', true),
            'channels' => ['mail', 'database'],
            'recipients' => [
                // 'admin@example.com',
            ],
        ],

        /*
        | 郵件設定
        */
        'mail' => [
            'from_address' => env('CHANNEL_MAIL_FROM', env('MAIL_FROM_ADDRESS')),
            'from_name' => env('CHANNEL_MAIL_FROM_NAME', '通路管理系統'),
            'subject_prefix' => '[通路管理] ',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | 開發和除錯配置
    |--------------------------------------------------------------------------
    */
    'debug' => [
        /*
        | 除錯模式
        */
        'enabled' => env('CHANNEL_DEBUG', env('APP_DEBUG', false)),
        'log_queries' => env('CHANNEL_LOG_QUERIES', false),
        'log_performance' => env('CHANNEL_LOG_PERFORMANCE', false),

        /*
        | 測試模式
        */
        'test_mode' => env('CHANNEL_TEST_MODE', false),
        'mock_external_services' => env('CHANNEL_MOCK_EXTERNAL', false),
    ],
];