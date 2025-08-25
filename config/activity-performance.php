<?php

return [
    /*
    |--------------------------------------------------------------------------
    | 活動記錄效能配置
    |--------------------------------------------------------------------------
    |
    | 此配置檔案包含活動記錄系統的效能相關設定，包含分區、快取、
    | 壓縮、分散式記錄等功能的配置選項。
    |
    */

    /*
    |--------------------------------------------------------------------------
    | 分區配置
    |--------------------------------------------------------------------------
    */
    'partitioning' => [
        'enabled' => env('ACTIVITY_PARTITIONING_ENABLED', true),
        'strategy' => env('ACTIVITY_PARTITION_STRATEGY', 'monthly'), // monthly, weekly, daily
        'retention_months' => env('ACTIVITY_PARTITION_RETENTION', 12),
        'auto_create_future_partitions' => env('ACTIVITY_AUTO_CREATE_PARTITIONS', true),
        'future_partitions_count' => env('ACTIVITY_FUTURE_PARTITIONS', 2),
        'maintenance_schedule' => env('ACTIVITY_PARTITION_MAINTENANCE', '0 2 * * *'), // 每日凌晨2點
    ],

    /*
    |--------------------------------------------------------------------------
    | 快取配置
    |--------------------------------------------------------------------------
    */
    'caching' => [
        'enabled' => env('ACTIVITY_CACHING_ENABLED', true),
        'driver' => env('ACTIVITY_CACHE_DRIVER', 'redis'),
        'prefix' => env('ACTIVITY_CACHE_PREFIX', 'activity_cache'),
        'default_ttl' => env('ACTIVITY_CACHE_TTL', 3600), // 1小時
        'stats_ttl' => env('ACTIVITY_STATS_CACHE_TTL', 1800), // 30分鐘
        'hot_query_ttl' => env('ACTIVITY_HOT_QUERY_TTL', 7200), // 2小時
        'tags' => ['activities', 'activity_stats', 'activity_queries'],
        'warmup_enabled' => env('ACTIVITY_CACHE_WARMUP', true),
        'warmup_schedule' => env('ACTIVITY_CACHE_WARMUP_SCHEDULE', '*/30 * * * *'), // 每30分鐘
    ],

    /*
    |--------------------------------------------------------------------------
    | 查詢最佳化配置
    |--------------------------------------------------------------------------
    */
    'query_optimization' => [
        'enabled' => env('ACTIVITY_QUERY_OPTIMIZATION_ENABLED', true),
        'slow_query_threshold' => env('ACTIVITY_SLOW_QUERY_THRESHOLD', 1000), // 毫秒
        'index_selectivity_threshold' => env('ACTIVITY_INDEX_SELECTIVITY_THRESHOLD', 0.1),
        'performance_monitoring' => env('ACTIVITY_PERFORMANCE_MONITORING', true),
        'auto_index_suggestions' => env('ACTIVITY_AUTO_INDEX_SUGGESTIONS', true),
        'query_sampling_rate' => env('ACTIVITY_QUERY_SAMPLING_RATE', 5), // 百分比
    ],

    /*
    |--------------------------------------------------------------------------
    | 壓縮和歸檔配置
    |--------------------------------------------------------------------------
    */
    'compression' => [
        'enabled' => env('ACTIVITY_COMPRESSION_ENABLED', true),
        'compression_threshold_days' => env('ACTIVITY_COMPRESSION_THRESHOLD', 30),
        'archive_threshold_days' => env('ACTIVITY_ARCHIVE_THRESHOLD', 90),
        'delete_threshold_days' => env('ACTIVITY_DELETE_THRESHOLD', 365),
        'compression_format' => env('ACTIVITY_COMPRESSION_FORMAT', 'gzip'), // gzip, deflate, bzip2
        'batch_size' => env('ACTIVITY_COMPRESSION_BATCH_SIZE', 1000),
        'archive_disk' => env('ACTIVITY_ARCHIVE_DISK', 'local'),
        'maintenance_schedule' => env('ACTIVITY_COMPRESSION_MAINTENANCE', '0 3 * * 0'), // 每週日凌晨3點
    ],

    /*
    |--------------------------------------------------------------------------
    | 分散式記錄配置
    |--------------------------------------------------------------------------
    */
    'distributed' => [
        'enabled' => env('ACTIVITY_DISTRIBUTED_ENABLED', false),
        'shards' => [
            'shard1' => [
                'connection' => env('ACTIVITY_SHARD1_CONNECTION', 'mysql'),
                'weight' => env('ACTIVITY_SHARD1_WEIGHT', 40),
            ],
            'shard2' => [
                'connection' => env('ACTIVITY_SHARD2_CONNECTION', 'mysql_replica1'),
                'weight' => env('ACTIVITY_SHARD2_WEIGHT', 30),
            ],
            'shard3' => [
                'connection' => env('ACTIVITY_SHARD3_CONNECTION', 'mysql_replica2'),
                'weight' => env('ACTIVITY_SHARD3_WEIGHT', 30),
            ],
        ],
        'replication_strategy' => env('ACTIVITY_REPLICATION_STRATEGY', 'async'), // sync, async, eventual
        'consistency_level' => env('ACTIVITY_CONSISTENCY_LEVEL', 'eventual'), // strong, eventual, weak
        'load_balance_strategy' => env('ACTIVITY_LOAD_BALANCE_STRATEGY', 'weighted_round_robin'),
        'failover_threshold' => env('ACTIVITY_FAILOVER_THRESHOLD', 3),
        'health_check_interval' => env('ACTIVITY_HEALTH_CHECK_INTERVAL', 30), // 秒
    ],

    /*
    |--------------------------------------------------------------------------
    | 效能監控配置
    |--------------------------------------------------------------------------
    */
    'monitoring' => [
        'enabled' => env('ACTIVITY_MONITORING_ENABLED', true),
        'metrics_retention_days' => env('ACTIVITY_METRICS_RETENTION', 30),
        'alert_thresholds' => [
            'slow_query_rate' => env('ACTIVITY_ALERT_SLOW_QUERY_RATE', 10), // 百分比
            'cache_hit_rate' => env('ACTIVITY_ALERT_CACHE_HIT_RATE', 80), // 百分比
            'partition_size' => env('ACTIVITY_ALERT_PARTITION_SIZE', 1000000), // 記錄數
            'replication_lag' => env('ACTIVITY_ALERT_REPLICATION_LAG', 300), // 秒
        ],
        'notification_channels' => [
            'email' => env('ACTIVITY_ALERT_EMAIL', 'admin@example.com'),
            'slack' => env('ACTIVITY_ALERT_SLACK_WEBHOOK', null),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | 自動維護配置
    |--------------------------------------------------------------------------
    */
    'auto_maintenance' => [
        'enabled' => env('ACTIVITY_AUTO_MAINTENANCE_ENABLED', true),
        'schedules' => [
            'partition_maintenance' => env('ACTIVITY_PARTITION_MAINTENANCE_SCHEDULE', '0 2 * * *'),
            'compression_maintenance' => env('ACTIVITY_COMPRESSION_MAINTENANCE_SCHEDULE', '0 3 * * 0'),
            'optimization_maintenance' => env('ACTIVITY_OPTIMIZATION_MAINTENANCE_SCHEDULE', '0 4 * * 1'),
            'cache_warmup' => env('ACTIVITY_CACHE_WARMUP_SCHEDULE', '*/30 * * * *'),
        ],
        'maintenance_window' => [
            'start' => env('ACTIVITY_MAINTENANCE_WINDOW_START', '02:00'),
            'end' => env('ACTIVITY_MAINTENANCE_WINDOW_END', '06:00'),
            'timezone' => env('ACTIVITY_MAINTENANCE_TIMEZONE', 'Asia/Taipei'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | 資源限制配置
    |--------------------------------------------------------------------------
    */
    'limits' => [
        'max_query_results' => env('ACTIVITY_MAX_QUERY_RESULTS', 1000),
        'max_export_records' => env('ACTIVITY_MAX_EXPORT_RECORDS', 10000),
        'max_batch_size' => env('ACTIVITY_MAX_BATCH_SIZE', 1000),
        'query_timeout' => env('ACTIVITY_QUERY_TIMEOUT', 30), // 秒
        'maintenance_timeout' => env('ACTIVITY_MAINTENANCE_TIMEOUT', 3600), // 秒
    ],

    /*
    |--------------------------------------------------------------------------
    | 除錯和開發配置
    |--------------------------------------------------------------------------
    */
    'debug' => [
        'enabled' => env('ACTIVITY_DEBUG_ENABLED', false),
        'log_slow_queries' => env('ACTIVITY_LOG_SLOW_QUERIES', true),
        'log_cache_misses' => env('ACTIVITY_LOG_CACHE_MISSES', false),
        'log_partition_operations' => env('ACTIVITY_LOG_PARTITION_OPS', true),
        'performance_profiling' => env('ACTIVITY_PERFORMANCE_PROFILING', false),
    ],
];