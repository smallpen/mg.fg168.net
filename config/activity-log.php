<?php

return [
    /*
    |--------------------------------------------------------------------------
    | 活動記錄完整性設定
    |--------------------------------------------------------------------------
    |
    | 此配置檔案包含活動記錄系統的完整性保護和敏感資料過濾設定
    |
    */

    /*
    |--------------------------------------------------------------------------
    | 完整性保護設定
    |--------------------------------------------------------------------------
    */
    'integrity' => [
        // 是否啟用完整性保護
        'enabled' => env('ACTIVITY_INTEGRITY_ENABLED', true),
        
        // 簽章演算法
        'signature_algorithm' => env('ACTIVITY_SIGNATURE_ALGORITHM', 'sha256'),
        
        // 簽章版本
        'signature_version' => env('ACTIVITY_SIGNATURE_VERSION', 'v1'),
        
        // 是否在生產環境中強制完整性檢查
        'strict_mode' => env('ACTIVITY_INTEGRITY_STRICT_MODE', true),
        
        // 完整性檢查的預設批次大小
        'default_batch_size' => env('ACTIVITY_INTEGRITY_BATCH_SIZE', 1000),
    ],

    /*
    |--------------------------------------------------------------------------
    | 敏感資料過濾設定
    |--------------------------------------------------------------------------
    */
    'sensitive_data' => [
        // 是否啟用敏感資料過濾
        'enabled' => env('ACTIVITY_SENSITIVE_FILTER_ENABLED', true),
        
        // 遮蔽字元
        'mask_character' => env('ACTIVITY_MASK_CHARACTER', '*'),
        
        // 可見字元數量
        'visible_chars' => env('ACTIVITY_VISIBLE_CHARS', 4),
        
        // 額外的敏感欄位關鍵字
        'sensitive_fields' => [
            // 可以在這裡新增專案特定的敏感欄位
            'bank_account',
            'id_number',
            'passport',
            'license_plate',
        ],
        
        // 額外的敏感值模式（正規表達式）
        'sensitive_patterns' => [
            // 台灣身分證字號
            '/[A-Z][12]\d{8}/',
            // 台灣手機號碼
            '/09\d{8}/',
            // 銀行帳號（假設為 10-16 位數字）
            '/\b\d{10,16}\b/',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | 防篡改設定
    |--------------------------------------------------------------------------
    */
    'tamper_protection' => [
        // 是否啟用防篡改保護
        'enabled' => env('ACTIVITY_TAMPER_PROTECTION_ENABLED', true),
        
        // 受保護的欄位列表
        'protected_fields' => [
            'type',
            'description',
            'causer_id',
            'subject_id',
            'created_at',
            'signature',
            'properties',
        ],
        
        // 是否記錄篡改嘗試
        'log_tamper_attempts' => env('ACTIVITY_LOG_TAMPER_ATTEMPTS', true),
        
        // 是否阻止篡改操作
        'block_tamper_attempts' => env('ACTIVITY_BLOCK_TAMPER_ATTEMPTS', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | 完整性檢查排程設定
    |--------------------------------------------------------------------------
    */
    'integrity_check' => [
        // 是否啟用定期完整性檢查
        'scheduled_enabled' => env('ACTIVITY_SCHEDULED_INTEGRITY_CHECK', false),
        
        // 檢查頻率（cron 表達式）
        'schedule' => env('ACTIVITY_INTEGRITY_CHECK_SCHEDULE', '0 2 * * *'), // 每日凌晨 2 點
        
        // 檢查範圍（天數）
        'check_days' => env('ACTIVITY_INTEGRITY_CHECK_DAYS', 7),
        
        // 是否自動匯出報告
        'auto_export_report' => env('ACTIVITY_AUTO_EXPORT_REPORT', true),
        
        // 報告儲存路徑
        'report_path' => env('ACTIVITY_REPORT_PATH', storage_path('logs/integrity_reports')),
    ],

    /*
    |--------------------------------------------------------------------------
    | 通知設定
    |--------------------------------------------------------------------------
    */
    'notifications' => [
        // 是否啟用完整性問題通知
        'enabled' => env('ACTIVITY_NOTIFICATIONS_ENABLED', true),
        
        // 通知管道
        'channels' => ['mail', 'database'],
        
        // 通知收件者
        'recipients' => [
            // 可以在這裡設定管理員郵件地址
        ],
        
        // 通知觸發條件
        'triggers' => [
            'integrity_violation' => true,
            'tamper_attempt' => true,
            'signature_failure' => true,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | 效能設定
    |--------------------------------------------------------------------------
    */
    'performance' => [
        // 是否啟用簽章快取
        'cache_signatures' => env('ACTIVITY_CACHE_SIGNATURES', false),
        
        // 快取時間（秒）
        'cache_ttl' => env('ACTIVITY_CACHE_TTL', 3600),
        
        // 是否使用佇列處理完整性檢查
        'queue_integrity_checks' => env('ACTIVITY_QUEUE_INTEGRITY_CHECKS', true),
        
        // 佇列名稱
        'queue_name' => env('ACTIVITY_QUEUE_NAME', 'integrity'),
    ],

    /*
    |--------------------------------------------------------------------------
    | 除錯設定
    |--------------------------------------------------------------------------
    */
    'debug' => [
        // 是否啟用除錯模式
        'enabled' => env('ACTIVITY_DEBUG_ENABLED', false),
        
        // 是否記錄詳細的完整性檢查日誌
        'log_integrity_checks' => env('ACTIVITY_LOG_INTEGRITY_CHECKS', false),
        
        // 是否記錄敏感資料過濾操作
        'log_sensitive_filtering' => env('ACTIVITY_LOG_SENSITIVE_FILTERING', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | 系統事件記錄設定
    |--------------------------------------------------------------------------
    */
    'system_events' => [
        // 是否記錄快取事件（預設關閉，因為太頻繁）
        'log_cache_events' => env('ACTIVITY_LOG_CACHE_EVENTS', false),
        
        // 快取事件記錄節流時間（秒）
        'cache_event_throttle' => env('ACTIVITY_CACHE_EVENT_THROTTLE', 300),
        
        // 是否記錄資料庫查詢事件
        'log_query_events' => env('ACTIVITY_LOG_QUERY_EVENTS', false),
        
        // 慢查詢閾值（毫秒）
        'slow_query_threshold' => env('ACTIVITY_SLOW_QUERY_THRESHOLD', 1000),
        
        // 是否記錄佇列事件
        'log_queue_events' => env('ACTIVITY_LOG_QUEUE_EVENTS', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | 非同步記錄設定
    |--------------------------------------------------------------------------
    */
    'async' => [
        // 是否啟用非同步記錄
        'enabled' => env('ACTIVITY_ASYNC_ENABLED', true),
        
        // 預設佇列名稱
        'default_queue' => env('ACTIVITY_DEFAULT_QUEUE', 'activities'),
        
        // 高優先級佇列名稱
        'high_priority_queue' => env('ACTIVITY_HIGH_PRIORITY_QUEUE', 'activities-high'),
        
        // 批量處理佇列名稱
        'batch_queue' => env('ACTIVITY_BATCH_QUEUE', 'activities-batch'),
        
        // 批量處理大小
        'batch_size' => env('ACTIVITY_BATCH_SIZE', 100),
        
        // 批量等待時間（秒）
        'batch_wait_time' => env('ACTIVITY_BATCH_WAIT_TIME', 30),
        
        // 重試設定
        'retry' => [
            // 最大重試次數
            'max_attempts' => env('ACTIVITY_MAX_RETRY_ATTEMPTS', 5),
            
            // 重試策略 (exponential, linear, fixed, custom)
            'strategy' => env('ACTIVITY_RETRY_STRATEGY', 'exponential'),
            
            // 自訂重試延遲時間（秒）
            'backoff' => [1, 5, 15, 30, 60],
        ],
        
        // 效能監控設定
        'performance' => [
            // 是否啟用效能監控
            'enabled' => env('ACTIVITY_PERFORMANCE_MONITORING_ENABLED', true),
            
            // 監控間隔（分鐘）
            'monitor_interval' => env('ACTIVITY_MONITOR_INTERVAL', 5),
            
            // 效能閾值（毫秒）
            'performance_threshold' => env('ACTIVITY_PERFORMANCE_THRESHOLD', 1000),
            
            // 是否自動優化
            'auto_optimization' => env('ACTIVITY_AUTO_OPTIMIZATION', false),
        ],
        
        // 失敗處理設定
        'failure_handling' => [
            // 是否儲存失敗記錄到備用檔案
            'backup_failed_jobs' => env('ACTIVITY_BACKUP_FAILED_JOBS', true),
            
            // 備用檔案路徑
            'backup_path' => env('ACTIVITY_BACKUP_PATH', storage_path('logs/failed_activities')),
            
            // 是否發送失敗通知
            'send_failure_notifications' => env('ACTIVITY_SEND_FAILURE_NOTIFICATIONS', true),
            
            // 高風險活動失敗通知閾值
            'high_risk_threshold' => env('ACTIVITY_HIGH_RISK_THRESHOLD', 7),
        ],
    ],
];