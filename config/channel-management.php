<?php

return [

    /*
    |--------------------------------------------------------------------------
    | 通路管理系統配置
    |--------------------------------------------------------------------------
    |
    | 此配置檔案包含通路管理系統的所有配置選項，包括效能設定、
    | 日誌配置、快取策略等。
    |
    */

    /*
    |--------------------------------------------------------------------------
    | 效能監控設定
    |--------------------------------------------------------------------------
    */

    'performance' => [
        
        // 是否啟用效能監控
        'monitoring_enabled' => env('CHANNEL_PERFORMANCE_MONITORING', true),
        
        // 慢查詢閾值（毫秒）
        'slow_query_threshold' => env('CHANNEL_SLOW_QUERY_THRESHOLD', 1000),
        
        // 記憶體使用警告閾值（MB）
        'memory_warning_threshold' => env('CHANNEL_MEMORY_WARNING', 128),
        
        // 記憶體使用錯誤閾值（MB）
        'memory_error_threshold' => env('CHANNEL_MEMORY_ERROR', 256),
        
        // 是否記錄查詢執行時間
        'log_query_time' => env('CHANNEL_LOG_QUERY_TIME', true),
        
        // 是否記錄記憶體使用情況
        'log_memory_usage' => env('CHANNEL_LOG_MEMORY_USAGE', true),
        
        // 效能報告生成間隔（分鐘）
        'report_interval' => env('CHANNEL_PERFORMANCE_REPORT_INTERVAL', 60),
        
    ],

    /*
    |--------------------------------------------------------------------------
    | 快取設定
    |--------------------------------------------------------------------------
    */

    'cache' => [
        
        // 代理樹狀結構快取時間（秒）
        'agent_tree_ttl' => env('CHANNEL_AGENT_TREE_CACHE_TTL', 3600),
        
        // 點數統計快取時間（秒）
        'point_stats_ttl' => env('CHANNEL_POINT_STATS_CACHE_TTL', 1800),
        
        // 組織架構快取時間（秒）
        'organization_chart_ttl' => env('CHANNEL_ORG_CHART_CACHE_TTL', 7200),
        
        // 使用者權限快取時間（秒）
        'user_permissions_ttl' => env('CHANNEL_USER_PERMISSIONS_CACHE_TTL', 3600),
        
        // 快取前綴
        'prefix' => env('CHANNEL_CACHE_PREFIX', 'channel_mgmt'),
        
        // 是否啟用查詢結果快取
        'query_cache_enabled' => env('CHANNEL_QUERY_CACHE_ENABLED', true),
        
    ],

    /*
    |--------------------------------------------------------------------------
    | 日誌設定
    |--------------------------------------------------------------------------
    */

    'logging' => [
        
        // 是否啟用通路管理專用日誌
        'enabled' => env('CHANNEL_LOGGING_ENABLED', true),
        
        // 日誌等級
        'level' => env('CHANNEL_LOG_LEVEL', 'info'),
        
        // 日誌保留天數
        'retention_days' => env('CHANNEL_LOG_RETENTION_DAYS', 30),
        
        // 是否記錄點數異動
        'log_point_transactions' => env('CHANNEL_LOG_POINT_TRANSACTIONS', true),
        
        // 是否記錄代理操作
        'log_agent_operations' => env('CHANNEL_LOG_AGENT_OPERATIONS', true),
        
        // 是否記錄玩家操作
        'log_player_operations' => env('CHANNEL_LOG_PLAYER_OPERATIONS', true),
        
        // 是否記錄權限檢查
        'log_permission_checks' => env('CHANNEL_LOG_PERMISSION_CHECKS', false),
        
        // 是否記錄 API 請求
        'log_api_requests' => env('CHANNEL_LOG_API_REQUESTS', true),
        
        // 敏感資料遮罩
        'mask_sensitive_data' => env('CHANNEL_MASK_SENSITIVE_DATA', true),
        
    ],

    /*
    |--------------------------------------------------------------------------
    | 資料庫設定
    |--------------------------------------------------------------------------
    */

    'database' => [
        
        // 查詢超時時間（秒）
        'query_timeout' => env('CHANNEL_DB_QUERY_TIMEOUT', 30),
        
        // 批次處理大小
        'batch_size' => env('CHANNEL_DB_BATCH_SIZE', 1000),
        
        // 是否啟用查詢日誌
        'log_queries' => env('CHANNEL_DB_LOG_QUERIES', false),
        
        // 連線池設定
        'connection_pool' => [
            'min_connections' => env('CHANNEL_DB_MIN_CONNECTIONS', 5),
            'max_connections' => env('CHANNEL_DB_MAX_CONNECTIONS', 20),
            'max_idle_time' => env('CHANNEL_DB_MAX_IDLE_TIME', 60),
        ],
        
    ],

    /*
    |--------------------------------------------------------------------------
    | 安全設定
    |--------------------------------------------------------------------------
    */

    'security' => [
        
        // 是否啟用操作稽核
        'audit_enabled' => env('CHANNEL_AUDIT_ENABLED', true),
        
        // 稽核日誌保留天數
        'audit_retention_days' => env('CHANNEL_AUDIT_RETENTION_DAYS', 90),
        
        // 是否記錄失敗的操作
        'log_failed_operations' => env('CHANNEL_LOG_FAILED_OPERATIONS', true),
        
        // 是否記錄權限違規
        'log_permission_violations' => env('CHANNEL_LOG_PERMISSION_VIOLATIONS', true),
        
        // 異常操作警告閾值
        'suspicious_activity_threshold' => env('CHANNEL_SUSPICIOUS_ACTIVITY_THRESHOLD', 10),
        
        // IP 白名單（管理員操作）
        'admin_ip_whitelist' => env('CHANNEL_ADMIN_IP_WHITELIST', ''),
        
    ],

    /*
    |--------------------------------------------------------------------------
    | 業務規則設定
    |--------------------------------------------------------------------------
    */

    'business_rules' => [
        
        // 最大代理層級（0 表示無限制）
        'max_agent_levels' => env('CHANNEL_MAX_AGENT_LEVELS', 0),
        
        // 單一代理最大下層代理數量（0 表示無限制）
        'max_sub_agents' => env('CHANNEL_MAX_SUB_AGENTS', 0),
        
        // 單一代理最大玩家數量（0 表示無限制）
        'max_players_per_agent' => env('CHANNEL_MAX_PLAYERS_PER_AGENT', 0),
        
        // 最小點數分配數量
        'min_point_allocation' => env('CHANNEL_MIN_POINT_ALLOCATION', 1),
        
        // 最大點數分配數量（0 表示無限制）
        'max_point_allocation' => env('CHANNEL_MAX_POINT_ALLOCATION', 0),
        
        // 是否允許負點數
        'allow_negative_points' => env('CHANNEL_ALLOW_NEGATIVE_POINTS', false),
        
        // 點數精確度（小數位數）
        'point_precision' => env('CHANNEL_POINT_PRECISION', 2),
        
    ],

    /*
    |--------------------------------------------------------------------------
    | 通知設定
    |--------------------------------------------------------------------------
    */

    'notifications' => [
        
        // 是否啟用系統通知
        'enabled' => env('CHANNEL_NOTIFICATIONS_ENABLED', true),
        
        // 點數不足警告閾值
        'low_points_threshold' => env('CHANNEL_LOW_POINTS_THRESHOLD', 100),
        
        // 是否通知點數異動
        'notify_point_changes' => env('CHANNEL_NOTIFY_POINT_CHANGES', true),
        
        // 是否通知代理建立
        'notify_agent_creation' => env('CHANNEL_NOTIFY_AGENT_CREATION', true),
        
        // 是否通知玩家建立
        'notify_player_creation' => env('CHANNEL_NOTIFY_PLAYER_CREATION', false),
        
        // 通知保留天數
        'retention_days' => env('CHANNEL_NOTIFICATION_RETENTION_DAYS', 30),
        
    ],

    /*
    |--------------------------------------------------------------------------
    | API 設定
    |--------------------------------------------------------------------------
    */

    'api' => [
        
        // 是否啟用 API
        'enabled' => env('CHANNEL_API_ENABLED', false),
        
        // API 版本
        'version' => env('CHANNEL_API_VERSION', 'v1'),
        
        // API 速率限制（每分鐘請求數）
        'rate_limit' => env('CHANNEL_API_RATE_LIMIT', 60),
        
        // API 認證方式
        'auth_method' => env('CHANNEL_API_AUTH_METHOD', 'sanctum'),
        
        // 是否記錄 API 請求
        'log_requests' => env('CHANNEL_API_LOG_REQUESTS', true),
        
    ],

    /*
    |--------------------------------------------------------------------------
    | 匯出設定
    |--------------------------------------------------------------------------
    */

    'export' => [
        
        // 是否啟用資料匯出
        'enabled' => env('CHANNEL_EXPORT_ENABLED', true),
        
        // 支援的匯出格式
        'formats' => ['excel', 'csv', 'pdf'],
        
        // 單次匯出最大記錄數
        'max_records' => env('CHANNEL_EXPORT_MAX_RECORDS', 10000),
        
        // 匯出檔案保留時間（小時）
        'file_retention_hours' => env('CHANNEL_EXPORT_FILE_RETENTION', 24),
        
        // 匯出檔案存放路徑
        'storage_path' => env('CHANNEL_EXPORT_STORAGE_PATH', 'exports/channel-management'),
        
    ],

    /*
    |--------------------------------------------------------------------------
    | 系統維護設定
    |--------------------------------------------------------------------------
    */

    'maintenance' => [
        
        // 是否啟用自動維護
        'auto_maintenance_enabled' => env('CHANNEL_AUTO_MAINTENANCE_ENABLED', true),
        
        // 自動清理日誌的時間（cron 表達式）
        'log_cleanup_schedule' => env('CHANNEL_LOG_CLEANUP_SCHEDULE', '0 2 * * *'),
        
        // 自動備份的時間（cron 表達式）
        'backup_schedule' => env('CHANNEL_BACKUP_SCHEDULE', '0 3 * * *'),
        
        // 自動優化資料庫的時間（cron 表達式）
        'db_optimize_schedule' => env('CHANNEL_DB_OPTIMIZE_SCHEDULE', '0 4 * * 0'),
        
        // 維護模式下的提示訊息
        'maintenance_message' => env('CHANNEL_MAINTENANCE_MESSAGE', '系統維護中，請稍後再試'),
        
    ],

];