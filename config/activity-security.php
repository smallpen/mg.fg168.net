<?php

return [
    /*
    |--------------------------------------------------------------------------
    | 活動記錄安全設定
    |--------------------------------------------------------------------------
    |
    | 此設定檔案包含活動記錄系統的安全相關配置
    |
    */

    /*
    |--------------------------------------------------------------------------
    | 完整性保護
    |--------------------------------------------------------------------------
    |
    | 啟用活動記錄的完整性保護功能，包括數位簽章和防篡改機制
    |
    */
    'integrity' => [
        'enabled' => env('ACTIVITY_INTEGRITY_ENABLED', true),
        'algorithm' => env('ACTIVITY_SIGNATURE_ALGORITHM', 'sha256'),
        'version' => 'v1',
        'prevent_tampering' => env('ACTIVITY_PREVENT_TAMPERING', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | 敏感資料加密
    |--------------------------------------------------------------------------
    |
    | 設定敏感活動記錄資料的加密選項
    |
    */
    'encryption' => [
        'enabled' => env('ACTIVITY_ENCRYPTION_ENABLED', false),
        'fields' => [
            'properties',
            'description',
        ],
        'cipher' => env('ACTIVITY_ENCRYPTION_CIPHER', 'AES-256-CBC'),
    ],

    /*
    |--------------------------------------------------------------------------
    | 存取控制
    |--------------------------------------------------------------------------
    |
    | 活動記錄存取的安全控制設定
    |
    */
    'access_control' => [
        'enabled' => env('ACTIVITY_ACCESS_CONTROL_ENABLED', true),
        
        // 時間限制（24小時制，空陣列表示無限制）
        'allowed_hours' => env('ACTIVITY_ALLOWED_HOURS', []),
        
        // IP 白名單（空陣列表示無限制）
        'allowed_ips' => env('ACTIVITY_ALLOWED_IPS', []),
        
        // 頻率限制
        'rate_limits' => [
            'view' => env('ACTIVITY_RATE_LIMIT_VIEW', 1000),
            'export' => env('ACTIVITY_RATE_LIMIT_EXPORT', 10),
            'delete' => env('ACTIVITY_RATE_LIMIT_DELETE', 5),
            'audit' => env('ACTIVITY_RATE_LIMIT_AUDIT', 3),
        ],
        
        // 頻率限制時間窗口（秒）
        'rate_window' => env('ACTIVITY_RATE_WINDOW', 3600),
    ],

    /*
    |--------------------------------------------------------------------------
    | 敏感資料過濾
    |--------------------------------------------------------------------------
    |
    | 敏感資料過濾和遮蔽的設定
    |
    */
    'sensitive_data' => [
        'enabled' => env('ACTIVITY_SENSITIVE_FILTER_ENABLED', true),
        
        // 敏感欄位關鍵字
        'sensitive_fields' => [
            'password', 'passwd', 'pwd', 'token', 'secret', 'key',
            'api_key', 'access_token', 'refresh_token', 'credit_card',
            'card_number', 'cvv', 'ssn', 'social_security', 'phone',
            'mobile', 'email', 'address', 'location', 'ip_address',
            'session', 'cookie', 'auth', 'private', 'confidential'
        ],
        
        // 敏感值模式（正規表達式）
        'sensitive_patterns' => [
            '/\b\d{4}[\s-]?\d{4}[\s-]?\d{4}[\s-]?\d{4}\b/', // 信用卡號
            '/\b[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Z|a-z]{2,}\b/', // 電子郵件
            '/\b\d{3}[-.]?\d{3}[-.]?\d{4}\b/', // 電話號碼
            '/\b\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}\b/', // IP 位址
            '/eyJ[A-Za-z0-9_-]*\.[A-Za-z0-9_-]*\.[A-Za-z0-9_-]*/', // JWT Token
            '/[A-Za-z0-9]{32,}/', // API Key 格式
        ],
        
        // 遮蔽字元
        'mask_character' => '*',
        
        // 可見字元數量
        'visible_chars' => 4,
    ],

    /*
    |--------------------------------------------------------------------------
    | 安全監控
    |--------------------------------------------------------------------------
    |
    | 安全事件監控和警報設定
    |
    */
    'monitoring' => [
        'enabled' => env('ACTIVITY_MONITORING_ENABLED', true),
        
        // 高風險活動類型
        'high_risk_types' => [
            'login_failed',
            'permission_escalation',
            'sensitive_data_access',
            'system_config_change',
            'unauthorized_access',
            'data_breach',
            'privilege_abuse',
        ],
        
        // 風險等級閾值
        'risk_thresholds' => [
            'low' => 3,
            'medium' => 6,
            'high' => 8,
            'critical' => 9,
        ],
        
        // 可疑 IP 位址列表
        'suspicious_ips' => env('ACTIVITY_SUSPICIOUS_IPS', []),
        
        // 登入失敗警報閾值
        'failed_login_threshold' => env('ACTIVITY_FAILED_LOGIN_THRESHOLD', 5),
        
        // 警報通知設定
        'alerts' => [
            'enabled' => env('ACTIVITY_ALERTS_ENABLED', true),
            'channels' => ['log', 'database'], // log, database, mail, slack
            'recipients' => env('ACTIVITY_ALERT_RECIPIENTS', ''),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | 審計設定
    |--------------------------------------------------------------------------
    |
    | 安全審計功能的設定
    |
    */
    'audit' => [
        'enabled' => env('ACTIVITY_AUDIT_ENABLED', true),
        
        // 自動審計頻率（cron 表達式）
        'schedule' => env('ACTIVITY_AUDIT_SCHEDULE', '0 2 * * *'), // 每日凌晨 2 點
        
        // 審計範圍
        'scope' => [
            'integrity_check' => true,
            'access_violations' => true,
            'suspicious_patterns' => true,
            'security_incidents' => true,
        ],
        
        // 審計報告保留天數
        'report_retention_days' => env('ACTIVITY_AUDIT_RETENTION', 90),
        
        // 批次處理大小
        'batch_size' => env('ACTIVITY_AUDIT_BATCH_SIZE', 1000),
    ],

    /*
    |--------------------------------------------------------------------------
    | 資料保護
    |--------------------------------------------------------------------------
    |
    | 活動記錄資料保護的設定
    |
    */
    'protection' => [
        // 受保護的活動類型（不可刪除）
        'protected_types' => [
            'security_incident',
            'unauthorized_access',
            'permission_escalation',
            'system_config_change',
            'admin_action',
        ],
        
        // 保護期限（天數）
        'protection_period' => env('ACTIVITY_PROTECTION_PERIOD', 30),
        
        // 備份設定
        'backup' => [
            'enabled' => env('ACTIVITY_BACKUP_ENABLED', true),
            'path' => env('ACTIVITY_BACKUP_PATH', storage_path('backups/activities')),
            'compression' => env('ACTIVITY_BACKUP_COMPRESSION', true),
            'encryption' => env('ACTIVITY_BACKUP_ENCRYPTION', true),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | 效能優化
    |--------------------------------------------------------------------------
    |
    | 安全功能的效能優化設定
    |
    */
    'performance' => [
        // 快取設定
        'cache' => [
            'enabled' => env('ACTIVITY_CACHE_ENABLED', true),
            'ttl' => env('ACTIVITY_CACHE_TTL', 3600), // 1 小時
            'prefix' => 'activity_security:',
        ],
        
        // 非同步處理
        'async' => [
            'enabled' => env('ACTIVITY_ASYNC_ENABLED', true),
            'queue' => env('ACTIVITY_QUEUE', 'activities'),
            'delay' => env('ACTIVITY_ASYNC_DELAY', 0),
        ],
        
        // 批次處理
        'batch' => [
            'size' => env('ACTIVITY_BATCH_SIZE', 100),
            'timeout' => env('ACTIVITY_BATCH_TIMEOUT', 300), // 5 分鐘
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | 日誌設定
    |--------------------------------------------------------------------------
    |
    | 安全相關日誌的設定
    |
    */
    'logging' => [
        'enabled' => env('ACTIVITY_SECURITY_LOGGING_ENABLED', true),
        'channel' => env('ACTIVITY_SECURITY_LOG_CHANNEL', 'security'),
        'level' => env('ACTIVITY_SECURITY_LOG_LEVEL', 'info'),
        
        // 日誌類型
        'log_types' => [
            'access_control' => true,
            'integrity_violations' => true,
            'tampering_attempts' => true,
            'security_alerts' => true,
            'audit_operations' => true,
        ],
    ],
];