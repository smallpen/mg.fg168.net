<?php

return [
    /*
    |--------------------------------------------------------------------------
    | 安全設定
    |--------------------------------------------------------------------------
    |
    | 這個檔案包含應用程式的安全相關設定
    |
    */

    /*
    |--------------------------------------------------------------------------
    | 審計日誌設定
    |--------------------------------------------------------------------------
    */
    'audit' => [
        // 是否啟用審計日誌
        'enabled' => env('AUDIT_LOG_ENABLED', true),
        
        // 審計日誌保留天數
        'retention_days' => env('AUDIT_LOG_RETENTION_DAYS', 90),
        
        // 是否記錄到資料庫
        'store_in_database' => env('AUDIT_LOG_DATABASE', false),
        
        // 是否記錄到檔案
        'store_in_file' => env('AUDIT_LOG_FILE', true),
        
        // 需要記錄的事件類型
        'log_events' => [
            'user_management',
            'authentication',
            'permission_denied',
            'security_events',
            'data_access',
            'bulk_operations',
            'configuration_changes',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | 輸入驗證設定
    |--------------------------------------------------------------------------
    */
    'input_validation' => [
        // 最大搜尋字串長度
        'max_search_length' => 255,
        
        // 最大批量操作數量
        'max_bulk_operations' => 100,
        
        // 允許的排序欄位
        'allowed_sort_fields' => [
            'name',
            'username', 
            'email',
            'created_at',
            'is_active',
        ],
        
        // 是否啟用惡意內容檢測
        'malicious_content_detection' => true,
        
        // 是否啟用 SQL 注入檢測
        'sql_injection_detection' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | 權限檢查設定
    |--------------------------------------------------------------------------
    */
    'permissions' => [
        // 權限快取時間（秒）
        'cache_ttl' => 3600,
        
        // 是否記錄權限檢查失敗
        'log_permission_denied' => true,
        
        // 超級管理員角色名稱
        'super_admin_role' => 'super_admin',
        
        // 管理員角色名稱
        'admin_roles' => ['super_admin', 'admin'],
        
        // 使用者管理相關權限
        'user_management_permissions' => [
            'users.view',
            'users.create',
            'users.edit',
            'users.delete',
            'users.export',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | CSRF 保護設定
    |--------------------------------------------------------------------------
    */
    'csrf' => [
        // 是否記錄 CSRF 失敗
        'log_failures' => true,
        
        // CSRF token 有效期（分鐘）
        'token_lifetime' => 120,
        
        // 排除的路由模式
        'except_patterns' => [
            'api/*',
            'webhooks/*',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | 安全事件設定
    |--------------------------------------------------------------------------
    */
    'security_events' => [
        // 安全事件嚴重程度
        'severity_levels' => [
            'low' => 1,
            'medium' => 2,
            'high' => 3,
            'critical' => 4,
        ],
        
        // 需要立即通知的事件
        'critical_events' => [
            'csrf_token_mismatch',
            'permission_bypass_attempt',
            'attempt_delete_super_admin',
            'malicious_input_detected',
        ],
        
        // 事件通知設定
        'notifications' => [
            'enabled' => env('SECURITY_NOTIFICATIONS_ENABLED', false),
            'channels' => ['mail', 'slack'],
            'recipients' => [
                'admin@example.com',
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | 速率限制設定
    |--------------------------------------------------------------------------
    */
    'rate_limiting' => [
        // 登入嘗試限制
        'login_attempts' => [
            'max_attempts' => 5,
            'decay_minutes' => 15,
        ],
        
        // API 請求限制
        'api_requests' => [
            'max_requests' => 60,
            'per_minutes' => 1,
        ],
        
        // 批量操作限制
        'bulk_operations' => [
            'max_operations' => 10,
            'per_minutes' => 5,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | 資料清理設定
    |--------------------------------------------------------------------------
    */
    'data_cleanup' => [
        // 是否自動清理舊日誌
        'auto_cleanup' => env('AUTO_CLEANUP_LOGS', true),
        
        // 清理間隔（天）
        'cleanup_interval' => 7,
        
        // 保留的日誌類型
        'keep_log_types' => [
            'critical_security_events',
            'user_deletions',
            'permission_changes',
        ],
    ],
];