<?php

return [
    /*
    |--------------------------------------------------------------------------
    | 角色安全配置
    |--------------------------------------------------------------------------
    |
    | 此配置檔案包含角色管理系統的安全相關設定
    |
    */

    /*
    |--------------------------------------------------------------------------
    | 系統保護角色
    |--------------------------------------------------------------------------
    |
    | 這些角色受到系統保護，不能被刪除或修改核心屬性
    |
    */
    'protected_system_roles' => [
        'super_admin',
        'admin',
        'system'
    ],

    /*
    |--------------------------------------------------------------------------
    | 核心權限
    |--------------------------------------------------------------------------
    |
    | 系統角色必須保留的核心權限，不能被移除
    |
    */
    'core_permissions' => [
        'admin.access',
        'roles.view',
        'users.view',
        'system.manage'
    ],

    /*
    |--------------------------------------------------------------------------
    | 保留角色名稱
    |--------------------------------------------------------------------------
    |
    | 這些名稱不能用於建立新角色
    |
    */
    'reserved_role_names' => [
        'root', 'admin', 'administrator', 'system', 'guest', 'anonymous',
        'public', 'user', 'users', 'role', 'roles', 'permission', 'permissions',
        'null', 'undefined', 'true', 'false', 'select', 'insert', 'update',
        'delete', 'drop', 'create', 'alter', 'exec', 'union', 'script'
    ],

    /*
    |--------------------------------------------------------------------------
    | 操作頻率限制
    |--------------------------------------------------------------------------
    |
    | 每分鐘允許的操作次數限制
    |
    */
    'rate_limits' => [
        'create' => 5,      // 每分鐘最多建立5個角色
        'edit' => 10,       // 每分鐘最多編輯10個角色
        'delete' => 3,      // 每分鐘最多刪除3個角色
        'bulk' => 2,        // 每分鐘最多2次批量操作
        'permission_assign' => 20,  // 每分鐘最多20次權限指派
        'default' => 10     // 預設限制
    ],

    /*
    |--------------------------------------------------------------------------
    | 資料驗證設定
    |--------------------------------------------------------------------------
    |
    | 角色資料的驗證和清理設定
    |
    */
    'validation' => [
        'role_name' => [
            'min_length' => 2,
            'max_length' => 50,
            'pattern' => '/^[a-z_][a-z0-9_]*$/',
        ],
        'display_name' => [
            'min_length' => 2,
            'max_length' => 50,
        ],
        'description' => [
            'max_length' => 255,
        ],
        'bulk_operation_limit' => 100,  // 批量操作最大數量
        'permission_assignment_limit' => 100,  // 一次最多指派的權限數量
    ],

    /*
    |--------------------------------------------------------------------------
    | 危險內容模式
    |--------------------------------------------------------------------------
    |
    | 用於檢測和移除危險內容的正規表達式模式
    |
    */
    'dangerous_patterns' => [
        '/(<script[^>]*>.*?<\/script>)/is',  // Script 標籤
        '/(<iframe[^>]*>.*?<\/iframe>)/is',  // Iframe 標籤
        '/(<object[^>]*>.*?<\/object>)/is',  // Object 標籤
        '/(<embed[^>]*>.*?<\/embed>)/is',    // Embed 標籤
        '/(<link[^>]*>)/is',                 // Link 標籤
        '/(<meta[^>]*>)/is',                 // Meta 標籤
        '/(javascript:|vbscript:|data:)/i',  // 危險協議
        '/(on\w+\s*=)/i',                    // 事件處理器
        '/(\beval\s*\()/i',                  // eval 函數
        '/(\bexec\s*\()/i',                  // exec 函數
    ],

    /*
    |--------------------------------------------------------------------------
    | SQL 注入模式
    |--------------------------------------------------------------------------
    |
    | 用於檢測 SQL 注入嘗試的模式
    |
    */
    'sql_injection_patterns' => [
        '/(\b(SELECT|INSERT|UPDATE|DELETE|DROP|CREATE|ALTER|EXEC|UNION|SCRIPT)\b)/i',
        '/(\'|\"|;|--|\*|\/\*|\*\/)/i',
        '/(\bOR\b.*\b=\b|\bAND\b.*\b=\b)/i',
        '/(\b1\s*=\s*1\b|\b1\s*=\s*0\b)/i',
    ],

    /*
    |--------------------------------------------------------------------------
    | 審計日誌設定
    |--------------------------------------------------------------------------
    |
    | 審計日誌的相關設定
    |
    */
    'audit_log' => [
        'enabled' => true,
        'log_successful_operations' => true,
        'log_failed_operations' => true,
        'log_permission_checks' => true,
        'log_security_events' => true,
        'retention_days' => 90,  // 審計日誌保留天數
    ],

    /*
    |--------------------------------------------------------------------------
    | 安全檢查設定
    |--------------------------------------------------------------------------
    |
    | 各種安全檢查的設定
    |
    */
    'security_checks' => [
        'enable_multi_level_permission_check' => true,
        'enable_role_hierarchy_check' => true,
        'enable_system_role_protection' => true,
        'enable_circular_dependency_check' => true,
        'enable_rate_limiting' => true,
        'enable_input_sanitization' => true,
        'enable_sql_injection_protection' => true,
        'enable_xss_protection' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | 快取設定
    |--------------------------------------------------------------------------
    |
    | 安全相關資料的快取設定
    |
    */
    'cache' => [
        'permission_cache_ttl' => 3600,     // 權限快取時間（秒）
        'role_hierarchy_cache_ttl' => 1800, // 角色層級快取時間（秒）
        'security_check_cache_ttl' => 300,  // 安全檢查快取時間（秒）
    ],

    /*
    |--------------------------------------------------------------------------
    | 通知設定
    |--------------------------------------------------------------------------
    |
    | 安全事件通知設定
    |
    */
    'notifications' => [
        'notify_on_system_role_modification' => true,
        'notify_on_bulk_operations' => true,
        'notify_on_security_violations' => true,
        'notify_on_rate_limit_exceeded' => true,
        'notification_channels' => ['log', 'mail'], // 可用: log, mail, slack, database
    ],

    /*
    |--------------------------------------------------------------------------
    | 開發模式設定
    |--------------------------------------------------------------------------
    |
    | 開發環境的特殊設定
    |
    */
    'development' => [
        'disable_rate_limiting' => env('APP_ENV') === 'local',
        'log_all_operations' => env('APP_ENV') === 'local',
        'enable_debug_mode' => env('APP_DEBUG', false),
    ],
];