<?php

namespace Tests\Integration;

/**
 * 活動記錄整合測試配置
 * 
 * 定義測試環境的配置參數和常數
 */
class ActivityLogTestConfig
{
    /**
     * 效能測試閾值
     */
    public const PERFORMANCE_THRESHOLDS = [
        // 寫入效能 (毫秒)
        'bulk_write_max_time' => 30000,        // 批量寫入最大時間 30 秒
        'async_write_max_time' => 5000,        // 非同步寫入最大時間 5 秒
        'single_write_max_time' => 100,        // 單筆寫入最大時間 100 毫秒
        
        // 查詢效能 (毫秒)
        'basic_query_max_time' => 1000,        // 基本查詢最大時間 1 秒
        'complex_query_max_time' => 2000,      // 複雜查詢最大時間 2 秒
        'search_query_max_time' => 2000,       // 搜尋查詢最大時間 2 秒
        'stats_query_max_time' => 3000,        // 統計查詢最大時間 3 秒
        
        // 頁面載入效能 (毫秒)
        'page_load_max_time' => 3000,          // 頁面載入最大時間 3 秒
        'search_response_max_time' => 1000,    // 搜尋響應最大時間 1 秒
        'pagination_max_time' => 500,          // 分頁載入最大時間 0.5 秒
        
        // 記憶體使用 (MB)
        'max_memory_usage' => 100,             // 最大記憶體使用量 100 MB
        'query_memory_limit' => 50,            // 查詢記憶體限制 50 MB
        
        // 處理量 (筆/秒)
        'min_write_throughput' => 300,         // 最小寫入處理量 300 筆/秒
        'min_async_throughput' => 1000,       // 最小非同步處理量 1000 筆/秒
    ];

    /**
     * 測試資料配置
     */
    public const TEST_DATA_CONFIG = [
        // 大量資料測試
        'large_dataset_size' => 50000,         // 大量資料集大小
        'bulk_test_size' => 10000,             // 批量測試大小
        'performance_test_size' => 5000,       // 效能測試大小
        
        // 批次處理
        'batch_size' => 1000,                  // 批次大小
        'max_batch_count' => 50,               // 最大批次數量
        
        // 搜尋測試
        'search_test_records' => 10000,        // 搜尋測試記錄數
        'search_keywords' => ['登入', '建立', '更新', '刪除', '權限'],
        
        // 統計測試
        'stats_test_records' => 20000,         // 統計測試記錄數
        'stats_time_range_days' => 30,         // 統計時間範圍天數
    ];

    /**
     * 安全測試配置
     */
    public const SECURITY_TEST_CONFIG = [
        // 登入失敗測試
        'max_login_failures' => 6,             // 最大登入失敗次數
        'login_failure_window' => 15,          // 登入失敗時間窗口 (分鐘)
        
        // IP 安全測試
        'suspicious_ip_threshold' => 10,       // 可疑 IP 活動閾值
        'foreign_ip_test' => '10.0.0.1',      // 測試用外網 IP
        
        // 風險等級
        'high_risk_threshold' => 7,            // 高風險閾值
        'critical_risk_threshold' => 9,        // 極高風險閾值
        
        // 完整性測試
        'integrity_check_sample_size' => 100,  // 完整性檢查樣本大小
    ];

    /**
     * MCP 測試配置
     */
    public const MCP_TEST_CONFIG = [
        // Playwright 配置
        'playwright' => [
            'base_url' => 'http://localhost',
            'timeout' => 30000,                 // 30 秒超時
            'viewport' => [
                'width' => 1280,
                'height' => 720
            ],
            'mobile_viewport' => [
                'width' => 375,
                'height' => 667
            ]
        ],
        
        // MySQL 配置
        'mysql' => [
            'host' => 'localhost',
            'database' => 'laravel_admin_test',
            'timeout' => 10000,                 // 10 秒超時
        ],
        
        // 測試重試配置
        'retry_attempts' => 3,                  // 重試次數
        'retry_delay' => 1000,                  // 重試延遲 (毫秒)
    ];

    /**
     * 瀏覽器測試配置
     */
    public const BROWSER_TEST_CONFIG = [
        // 測試頁面
        'test_pages' => [
            '/admin/activities',
            '/admin/activities/monitor',
            '/admin/activities/stats',
            '/admin/security/alerts'
        ],
        
        // 測試操作
        'test_operations' => [
            'search_activities',
            'filter_activities',
            'view_activity_detail',
            'export_activities',
            'real_time_monitoring'
        ],
        
        // 響應式測試
        'responsive_breakpoints' => [
            'mobile' => ['width' => 375, 'height' => 667],
            'tablet' => ['width' => 768, 'height' => 1024],
            'desktop' => ['width' => 1280, 'height' => 720],
            'large' => ['width' => 1920, 'height' => 1080]
        ],
        
        // 效能測試
        'performance_metrics' => [
            'first_contentful_paint',
            'largest_contentful_paint',
            'cumulative_layout_shift',
            'time_to_interactive'
        ]
    ];

    /**
     * 測試使用者配置
     */
    public const TEST_USERS = [
        'admin' => [
            'username' => 'test_admin',
            'name' => '測試管理員',
            'email' => 'admin@test.com',
            'password' => 'password123',
            'permissions' => 'all'
        ],
        'user' => [
            'username' => 'test_user',
            'name' => '測試使用者',
            'email' => 'user@test.com',
            'password' => 'password123',
            'permissions' => 'limited'
        ],
        'viewer' => [
            'username' => 'test_viewer',
            'name' => '測試檢視者',
            'email' => 'viewer@test.com',
            'password' => 'password123',
            'permissions' => 'view_only'
        ]
    ];

    /**
     * 測試權限配置
     */
    public const TEST_PERMISSIONS = [
        'admin_permissions' => [
            'activity_logs.view',
            'activity_logs.export',
            'activity_logs.delete',
            'users.view',
            'users.create',
            'users.edit',
            'users.delete',
            'system.logs',
            'security.view',
            'security.incidents'
        ],
        'user_permissions' => [
            'activity_logs.view',
            'users.view',
            'profile.view',
            'profile.edit'
        ],
        'viewer_permissions' => [
            'activity_logs.view'
        ]
    ];

    /**
     * 測試環境配置
     */
    public const TEST_ENVIRONMENT = [
        // 資料庫配置
        'database' => [
            'connection' => 'testing',
            'cleanup_after_test' => true,
            'seed_test_data' => true
        ],
        
        // 快取配置
        'cache' => [
            'driver' => 'array',
            'clear_before_test' => true
        ],
        
        // 佇列配置
        'queue' => [
            'driver' => 'sync',
            'fake_for_testing' => true
        ],
        
        // 日誌配置
        'logging' => [
            'level' => 'debug',
            'channels' => ['single', 'activity']
        ]
    ];

    /**
     * 報告配置
     */
    public const REPORT_CONFIG = [
        // 報告格式
        'formats' => ['json', 'html', 'xml'],
        
        // 報告路徑
        'output_path' => 'storage/logs/test-reports',
        
        // 報告內容
        'include_screenshots' => true,
        'include_performance_metrics' => true,
        'include_error_details' => true,
        'include_coverage_report' => true,
        
        // 報告保留
        'retention_days' => 30,
        'max_report_files' => 100
    ];

    /**
     * 取得效能閾值
     */
    public static function getPerformanceThreshold(string $metric): int
    {
        return self::PERFORMANCE_THRESHOLDS[$metric] ?? 0;
    }

    /**
     * 取得測試資料配置
     */
    public static function getTestDataConfig(string $key): mixed
    {
        return self::TEST_DATA_CONFIG[$key] ?? null;
    }

    /**
     * 取得安全測試配置
     */
    public static function getSecurityTestConfig(string $key): mixed
    {
        return self::SECURITY_TEST_CONFIG[$key] ?? null;
    }

    /**
     * 取得 MCP 測試配置
     */
    public static function getMcpTestConfig(string $service = null): mixed
    {
        if ($service) {
            return self::MCP_TEST_CONFIG[$service] ?? null;
        }
        return self::MCP_TEST_CONFIG;
    }

    /**
     * 取得瀏覽器測試配置
     */
    public static function getBrowserTestConfig(string $key = null): mixed
    {
        if ($key) {
            return self::BROWSER_TEST_CONFIG[$key] ?? null;
        }
        return self::BROWSER_TEST_CONFIG;
    }

    /**
     * 取得測試使用者配置
     */
    public static function getTestUser(string $type): array
    {
        return self::TEST_USERS[$type] ?? [];
    }

    /**
     * 取得測試權限配置
     */
    public static function getTestPermissions(string $type): array
    {
        return self::TEST_PERMISSIONS[$type . '_permissions'] ?? [];
    }

    /**
     * 取得測試環境配置
     */
    public static function getTestEnvironment(string $key = null): mixed
    {
        if ($key) {
            return self::TEST_ENVIRONMENT[$key] ?? null;
        }
        return self::TEST_ENVIRONMENT;
    }

    /**
     * 取得報告配置
     */
    public static function getReportConfig(string $key = null): mixed
    {
        if ($key) {
            return self::REPORT_CONFIG[$key] ?? null;
        }
        return self::REPORT_CONFIG;
    }

    /**
     * 驗證配置完整性
     */
    public static function validateConfig(): array
    {
        $errors = [];
        
        // 檢查必要的配置項目
        $requiredConfigs = [
            'PERFORMANCE_THRESHOLDS',
            'TEST_DATA_CONFIG',
            'SECURITY_TEST_CONFIG',
            'MCP_TEST_CONFIG',
            'BROWSER_TEST_CONFIG'
        ];
        
        foreach ($requiredConfigs as $config) {
            if (!defined("self::{$config}")) {
                $errors[] = "缺少必要配置: {$config}";
            }
        }
        
        // 檢查效能閾值合理性
        if (self::PERFORMANCE_THRESHOLDS['basic_query_max_time'] > self::PERFORMANCE_THRESHOLDS['complex_query_max_time']) {
            $errors[] = "基本查詢時間閾值不應大於複雜查詢時間閾值";
        }
        
        // 檢查測試資料大小合理性
        if (self::TEST_DATA_CONFIG['large_dataset_size'] < self::TEST_DATA_CONFIG['bulk_test_size']) {
            $errors[] = "大量資料集大小應大於批量測試大小";
        }
        
        return $errors;
    }

    /**
     * 取得測試摘要資訊
     */
    public static function getTestSummary(): array
    {
        return [
            'total_performance_thresholds' => count(self::PERFORMANCE_THRESHOLDS),
            'total_test_users' => count(self::TEST_USERS),
            'total_test_pages' => count(self::BROWSER_TEST_CONFIG['test_pages']),
            'total_test_operations' => count(self::BROWSER_TEST_CONFIG['test_operations']),
            'max_dataset_size' => self::TEST_DATA_CONFIG['large_dataset_size'],
            'supported_formats' => self::REPORT_CONFIG['formats'],
            'config_validation' => empty(self::validateConfig())
        ];
    }
}