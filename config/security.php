<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Session 安全設定
    |--------------------------------------------------------------------------
    |
    | 這些設定控制 Session 的安全性行為
    |
    */
    'session' => [
        // Session 生命週期（分鐘）
        'lifetime' => env('SESSION_SECURITY_LIFETIME', 120),
        
        // 閒置超時時間（分鐘）
        'idle_timeout' => env('SESSION_IDLE_TIMEOUT', 30),
        
        // 最大並發 Session 數量
        'max_concurrent' => env('SESSION_MAX_CONCURRENT', 5),
        
        // Session 過期前警告時間（分鐘）
        'warning_time' => env('SESSION_WARNING_TIME', 5),
        
        // 是否啟用 Session 安全檢查
        'security_check_enabled' => env('SESSION_SECURITY_CHECK', true),
        
        // 是否記錄 Session 活動
        'log_activity' => env('SESSION_LOG_ACTIVITY', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | 異常活動檢測設定
    |--------------------------------------------------------------------------
    |
    | 這些設定控制異常活動的檢測和處理
    |
    */
    'suspicious_activity' => [
        // 是否啟用異常活動檢測
        'enabled' => env('SUSPICIOUS_ACTIVITY_DETECTION', true),
        
        // 風險評分閾值
        'risk_thresholds' => [
            'low' => 10,
            'medium' => 30,
            'high' => 50,
        ],
        
        // IP 地址變更的風險分數
        'ip_change_score' => 30,
        
        // User Agent 變更的風險分數
        'user_agent_change_score' => 50,
        
        // 異常時間登入的風險分數
        'unusual_time_score' => 10,
        
        // 多次失敗嘗試的風險分數
        'failed_attempts_score' => 20,
        
        // 異常時間範圍（小時）
        'unusual_hours' => [
            'start' => 23, // 晚上 11 點後
            'end' => 6,    // 早上 6 點前
        ],
        
        // 是否自動處理高風險活動
        'auto_handle_high_risk' => env('AUTO_HANDLE_HIGH_RISK', true),
        
        // 是否發送安全通知
        'send_notifications' => env('SEND_SECURITY_NOTIFICATIONS', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | 維護模式設定
    |--------------------------------------------------------------------------
    |
    | 這些設定控制維護模式的行為
    |
    */
    'maintenance' => [
        // 預設維護訊息
        'default_message' => '系統正在維護中，請稍後再試。',
        
        // 是否顯示維護進度
        'show_progress' => env('MAINTENANCE_SHOW_PROGRESS', false),
        
        // 是否允許管理員存取
        'allow_admin_access' => env('MAINTENANCE_ALLOW_ADMIN', true),
        
        // 重定向 URL（如果設定）
        'redirect_url' => env('MAINTENANCE_REDIRECT_URL', ''),
        
        // 是否使用自訂模板
        'custom_template' => env('MAINTENANCE_CUSTOM_TEMPLATE', false),
        
        // 自訂模板路徑
        'template_path' => env('MAINTENANCE_TEMPLATE_PATH', 'errors.503'),
        
        // 是否記錄維護模式操作
        'log_operations' => env('MAINTENANCE_LOG_OPERATIONS', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | 安全性監控設定
    |--------------------------------------------------------------------------
    |
    | 這些設定控制安全性監控和警報
    |
    */
    'monitoring' => [
        // 是否啟用安全性監控
        'enabled' => env('SECURITY_MONITORING_ENABLED', true),
        
        // 監控檢查間隔（秒）
        'check_interval' => env('SECURITY_CHECK_INTERVAL', 30),
        
        // 是否啟用即時警報
        'real_time_alerts' => env('SECURITY_REAL_TIME_ALERTS', true),
        
        // 警報通知方式
        'alert_channels' => [
            'database' => true,
            'email' => env('SECURITY_EMAIL_ALERTS', false),
            'slack' => env('SECURITY_SLACK_ALERTS', false),
            'browser' => true,
        ],
        
        // 安全事件保留天數
        'event_retention_days' => env('SECURITY_EVENT_RETENTION', 90),
        
        // 是否記錄所有安全事件
        'log_all_events' => env('SECURITY_LOG_ALL_EVENTS', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | IP 地址和地理位置設定
    |--------------------------------------------------------------------------
    |
    | 這些設定控制 IP 地址檢查和地理位置服務
    |
    */
    'ip_security' => [
        // 是否啟用 IP 地址檢查
        'enabled' => env('IP_SECURITY_ENABLED', true),
        
        // 信任的 IP 地址範圍
        'trusted_ranges' => [
            '127.0.0.1',
            '::1',
            '192.168.0.0/16',
            '10.0.0.0/8',
            '172.16.0.0/12',
        ],
        
        // 是否啟用地理位置檢查
        'geolocation_enabled' => env('GEOLOCATION_ENABLED', false),
        
        // 地理位置服務提供商
        'geolocation_provider' => env('GEOLOCATION_PROVIDER', 'ipapi'),
        
        // 地理位置 API 金鑰
        'geolocation_api_key' => env('GEOLOCATION_API_KEY', ''),
        
        // 地理位置變更的風險分數
        'location_change_score' => 25,
        
        // 是否快取地理位置資訊
        'cache_geolocation' => env('CACHE_GEOLOCATION', true),
        
        // 地理位置快取時間（小時）
        'geolocation_cache_hours' => env('GEOLOCATION_CACHE_HOURS', 24),
    ],

    /*
    |--------------------------------------------------------------------------
    | 密碼和認證安全設定
    |--------------------------------------------------------------------------
    |
    | 這些設定控制密碼和認證的安全性
    |
    */
    'authentication' => [
        // 密碼最小長度
        'password_min_length' => env('PASSWORD_MIN_LENGTH', 8),
        
        // 是否要求複雜密碼
        'require_complex_password' => env('REQUIRE_COMPLEX_PASSWORD', true),
        
        // 密碼過期天數（0 表示不過期）
        'password_expiry_days' => env('PASSWORD_EXPIRY_DAYS', 90),
        
        // 登入失敗最大嘗試次數
        'max_login_attempts' => env('MAX_LOGIN_ATTEMPTS', 5),
        
        // 登入失敗鎖定時間（分鐘）
        'lockout_duration' => env('LOCKOUT_DURATION', 15),
        
        // 是否啟用兩步驟驗證
        'two_factor_enabled' => env('TWO_FACTOR_ENABLED', false),
        
        // 是否強制管理員使用兩步驟驗證
        'force_2fa_for_admin' => env('FORCE_2FA_FOR_ADMIN', false),
        
        // 記住登入的天數
        'remember_me_duration' => env('REMEMBER_ME_DURATION', 30),
    ],

    /*
    |--------------------------------------------------------------------------
    | 資料保護設定
    |--------------------------------------------------------------------------
    |
    | 這些設定控制敏感資料的保護
    |
    */
    'data_protection' => [
        // 是否啟用資料加密
        'encryption_enabled' => env('DATA_ENCRYPTION_ENABLED', true),
        
        // 敏感欄位列表
        'sensitive_fields' => [
            'password',
            'remember_token',
            'api_token',
            'two_factor_secret',
            'two_factor_recovery_codes',
        ],
        
        // 是否記錄敏感資料存取
        'log_sensitive_access' => env('LOG_SENSITIVE_ACCESS', true),
        
        // 資料遮罩設定
        'data_masking' => [
            'enabled' => env('DATA_MASKING_ENABLED', true),
            'mask_character' => '*',
            'show_last_chars' => 4,
        ],
        
        // 是否啟用資料完整性檢查
        'integrity_check' => env('DATA_INTEGRITY_CHECK', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | 安全標頭設定
    |--------------------------------------------------------------------------
    |
    | 這些設定控制 HTTP 安全標頭
    |
    */
    'headers' => [
        // 是否啟用安全標頭
        'enabled' => env('SECURITY_HEADERS_ENABLED', true),
        
        // Content Security Policy
        'csp' => [
            'enabled' => env('CSP_ENABLED', true),
            'policy' => env('CSP_POLICY', "default-src 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval'; style-src 'self' 'unsafe-inline';"),
        ],
        
        // X-Frame-Options
        'frame_options' => env('X_FRAME_OPTIONS', 'DENY'),
        
        // X-Content-Type-Options
        'content_type_options' => env('X_CONTENT_TYPE_OPTIONS', 'nosniff'),
        
        // X-XSS-Protection
        'xss_protection' => env('X_XSS_PROTECTION', '1; mode=block'),
        
        // Strict-Transport-Security
        'hsts' => [
            'enabled' => env('HSTS_ENABLED', true),
            'max_age' => env('HSTS_MAX_AGE', 31536000),
            'include_subdomains' => env('HSTS_INCLUDE_SUBDOMAINS', true),
        ],
        
        // Referrer-Policy
        'referrer_policy' => env('REFERRER_POLICY', 'strict-origin-when-cross-origin'),
    ],
];