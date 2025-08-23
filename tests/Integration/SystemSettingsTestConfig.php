<?php

namespace Tests\Integration;

/**
 * 系統設定整合測試配置
 * 
 * 定義所有測試的配置參數、測試資料和預期結果
 */
class SystemSettingsTestConfig
{
    /**
     * 測試使用者配置
     */
    public const TEST_USERS = [
        'admin' => [
            'username' => 'test_admin',
            'name' => '測試管理員',
            'email' => 'admin@test.local',
            'password' => 'TestPassword123!',
            'permissions' => ['settings.view', 'settings.edit', 'settings.backup', 'settings.import', 'settings.export'],
        ],
        'editor' => [
            'username' => 'test_editor',
            'name' => '測試編輯者',
            'email' => 'editor@test.local',
            'password' => 'TestPassword123!',
            'permissions' => ['settings.view', 'settings.edit'],
        ],
        'viewer' => [
            'username' => 'test_viewer',
            'name' => '測試檢視者',
            'email' => 'viewer@test.local',
            'password' => 'TestPassword123!',
            'permissions' => ['settings.view'],
        ],
        'regular' => [
            'username' => 'test_regular',
            'name' => '測試一般使用者',
            'email' => 'regular@test.local',
            'password' => 'TestPassword123!',
            'permissions' => [],
        ],
    ];

    /**
     * 測試設定資料
     */
    public const TEST_SETTINGS = [
        'basic' => [
            'test.app.name' => [
                'value' => 'Integration Test Application',
                'type' => 'text',
                'description' => '整合測試應用程式名稱',
                'default_value' => 'Default App Name',
                'validation' => 'required|string|max:100',
                'is_system' => false,
                'is_encrypted' => false,
            ],
            'test.app.version' => [
                'value' => '1.0.0',
                'type' => 'text',
                'description' => '整合測試應用程式版本',
                'default_value' => '1.0.0',
                'validation' => 'required|string',
                'is_system' => true,
                'is_encrypted' => false,
            ],
        ],
        'security' => [
            'test.security.password_length' => [
                'value' => 10,
                'type' => 'number',
                'description' => '整合測試密碼最小長度',
                'default_value' => 8,
                'validation' => 'required|integer|min:6|max:20',
                'is_system' => false,
                'is_encrypted' => false,
            ],
            'test.security.api_key' => [
                'value' => 'test_api_key_12345',
                'type' => 'password',
                'description' => '整合測試 API 金鑰',
                'default_value' => '',
                'validation' => 'nullable|string',
                'is_system' => false,
                'is_encrypted' => true,
            ],
        ],
        'appearance' => [
            'test.theme.primary_color' => [
                'value' => '#FF5722',
                'type' => 'color',
                'description' => '整合測試主要顏色',
                'default_value' => '#3B82F6',
                'validation' => 'required|regex:/^#[0-9A-Fa-f]{6}$/',
                'is_system' => false,
                'is_encrypted' => false,
            ],
            'test.theme.mode' => [
                'value' => 'auto',
                'type' => 'select',
                'description' => '整合測試主題模式',
                'default_value' => 'light',
                'validation' => 'required|in:light,dark,auto',
                'options' => ['light' => '亮色', 'dark' => '暗色', 'auto' => '自動'],
                'is_system' => false,
                'is_encrypted' => false,
            ],
        ],
    ];

    /**
     * 測試場景配置
     */
    public const TEST_SCENARIOS = [
        'basic_workflow' => [
            'name' => '基本工作流程測試',
            'description' => '測試設定的基本 CRUD 操作',
            'steps' => [
                'login_as_admin',
                'navigate_to_settings',
                'view_settings_list',
                'search_settings',
                'filter_by_category',
                'edit_setting',
                'validate_changes',
                'reset_setting',
                'logout',
            ],
            'expected_duration' => 120, // 秒
        ],
        'backup_restore' => [
            'name' => '備份還原測試',
            'description' => '測試設定備份和還原功能',
            'steps' => [
                'login_as_admin',
                'navigate_to_settings',
                'create_backup',
                'modify_settings',
                'restore_backup',
                'verify_restoration',
                'cleanup_backup',
                'logout',
            ],
            'expected_duration' => 180, // 秒
        ],
        'import_export' => [
            'name' => '匯入匯出測試',
            'description' => '測試設定匯入和匯出功能',
            'steps' => [
                'login_as_admin',
                'navigate_to_settings',
                'export_settings',
                'verify_export_file',
                'modify_settings',
                'import_settings',
                'verify_import_results',
                'cleanup_files',
                'logout',
            ],
            'expected_duration' => 150, // 秒
        ],
        'permission_control' => [
            'name' => '權限控制測試',
            'description' => '測試不同使用者角色的權限控制',
            'steps' => [
                'test_admin_permissions',
                'test_editor_permissions',
                'test_viewer_permissions',
                'test_regular_user_access',
                'verify_access_logs',
            ],
            'expected_duration' => 200, // 秒
        ],
        'responsive_design' => [
            'name' => '響應式設計測試',
            'description' => '測試不同螢幕尺寸下的介面顯示',
            'steps' => [
                'login_as_admin',
                'navigate_to_settings',
                'test_desktop_layout',
                'test_tablet_layout',
                'test_mobile_layout',
                'test_touch_interactions',
                'logout',
            ],
            'expected_duration' => 100, // 秒
        ],
    ];

    /**
     * 效能測試基準
     */
    public const PERFORMANCE_BENCHMARKS = [
        'page_load_time' => [
            'threshold' => 2000, // 毫秒
            'description' => '設定頁面載入時間',
        ],
        'setting_update_time' => [
            'threshold' => 1000, // 毫秒
            'description' => '設定更新響應時間',
        ],
        'search_response_time' => [
            'threshold' => 500, // 毫秒
            'description' => '設定搜尋響應時間',
        ],
        'backup_creation_time' => [
            'threshold' => 5000, // 毫秒
            'description' => '備份建立時間',
        ],
        'import_processing_time' => [
            'threshold' => 3000, // 毫秒
            'description' => '匯入處理時間',
        ],
        'memory_usage' => [
            'threshold' => 50, // MB
            'description' => '記憶體使用量',
        ],
    ];

    /**
     * 瀏覽器測試配置
     */
    public const BROWSER_CONFIG = [
        'default' => [
            'browser' => 'chromium',
            'headless' => true,
            'viewport' => ['width' => 1280, 'height' => 720],
            'timeout' => 30000,
        ],
        'desktop' => [
            'browser' => 'chromium',
            'headless' => false,
            'viewport' => ['width' => 1920, 'height' => 1080],
            'timeout' => 30000,
        ],
        'tablet' => [
            'browser' => 'chromium',
            'headless' => true,
            'viewport' => ['width' => 768, 'height' => 1024],
            'timeout' => 30000,
        ],
        'mobile' => [
            'browser' => 'chromium',
            'headless' => true,
            'viewport' => ['width' => 375, 'height' => 667],
            'timeout' => 30000,
        ],
    ];

    /**
     * 資料庫測試配置
     */
    public const DATABASE_CONFIG = [
        'connection' => 'testing',
        'cleanup_after_test' => true,
        'backup_test_data' => true,
        'verify_constraints' => true,
        'check_indexes' => true,
    ];

    /**
     * 檔案和路徑配置
     */
    public const FILE_PATHS = [
        'screenshots' => 'storage/app/screenshots/integration-tests',
        'exports' => 'storage/app/exports/test-exports',
        'imports' => 'storage/app/imports/test-imports',
        'backups' => 'storage/app/backups/test-backups',
        'logs' => 'storage/logs/integration-tests',
        'reports' => 'storage/logs/test-reports',
    ];

    /**
     * 測試資料驗證規則
     */
    public const VALIDATION_RULES = [
        'setting_key_format' => '/^[a-z0-9._]+$/',
        'setting_value_max_length' => 10000,
        'backup_name_max_length' => 100,
        'export_file_max_size' => 10485760, // 10MB
        'import_file_max_size' => 10485760, // 10MB
        'screenshot_max_size' => 5242880, // 5MB
    ];

    /**
     * 錯誤處理配置
     */
    public const ERROR_HANDLING = [
        'retry_attempts' => 3,
        'retry_delay' => 1000, // 毫秒
        'screenshot_on_failure' => true,
        'log_detailed_errors' => true,
        'continue_on_non_critical_errors' => true,
    ];

    /**
     * 通知配置
     */
    public const NOTIFICATION_CONFIG = [
        'send_email_on_completion' => false,
        'send_slack_notification' => false,
        'webhook_url' => null,
        'notification_recipients' => [],
    ];

    /**
     * 取得測試使用者配置
     */
    public static function getTestUser(string $role): array
    {
        return self::TEST_USERS[$role] ?? throw new \InvalidArgumentException("未知的使用者角色: {$role}");
    }

    /**
     * 取得測試設定資料
     */
    public static function getTestSettings(string $category = null): array
    {
        if ($category) {
            return self::TEST_SETTINGS[$category] ?? [];
        }
        
        $allSettings = [];
        foreach (self::TEST_SETTINGS as $categorySettings) {
            $allSettings = array_merge($allSettings, $categorySettings);
        }
        
        return $allSettings;
    }

    /**
     * 取得測試場景配置
     */
    public static function getTestScenario(string $scenario): array
    {
        return self::TEST_SCENARIOS[$scenario] ?? throw new \InvalidArgumentException("未知的測試場景: {$scenario}");
    }

    /**
     * 取得效能基準
     */
    public static function getPerformanceBenchmark(string $metric): array
    {
        return self::PERFORMANCE_BENCHMARKS[$metric] ?? throw new \InvalidArgumentException("未知的效能指標: {$metric}");
    }

    /**
     * 取得瀏覽器配置
     */
    public static function getBrowserConfig(string $type = 'default'): array
    {
        return self::BROWSER_CONFIG[$type] ?? self::BROWSER_CONFIG['default'];
    }

    /**
     * 取得檔案路徑
     */
    public static function getFilePath(string $type): string
    {
        $path = self::FILE_PATHS[$type] ?? throw new \InvalidArgumentException("未知的檔案類型: {$type}");
        
        // 確保目錄存在
        $fullPath = base_path($path);
        if (!is_dir($fullPath)) {
            mkdir($fullPath, 0755, true);
        }
        
        return $fullPath;
    }

    /**
     * 驗證設定鍵格式
     */
    public static function validateSettingKey(string $key): bool
    {
        return preg_match(self::VALIDATION_RULES['setting_key_format'], $key) === 1;
    }

    /**
     * 驗證設定值長度
     */
    public static function validateSettingValue(string $value): bool
    {
        return strlen($value) <= self::VALIDATION_RULES['setting_value_max_length'];
    }

    /**
     * 取得重試配置
     */
    public static function getRetryConfig(): array
    {
        return [
            'attempts' => self::ERROR_HANDLING['retry_attempts'],
            'delay' => self::ERROR_HANDLING['retry_delay'],
        ];
    }

    /**
     * 檢查是否應該在失敗時截圖
     */
    public static function shouldScreenshotOnFailure(): bool
    {
        return self::ERROR_HANDLING['screenshot_on_failure'];
    }

    /**
     * 檢查是否應該記錄詳細錯誤
     */
    public static function shouldLogDetailedErrors(): bool
    {
        return self::ERROR_HANDLING['log_detailed_errors'];
    }

    /**
     * 檢查是否應該在非關鍵錯誤時繼續執行
     */
    public static function shouldContinueOnNonCriticalErrors(): bool
    {
        return self::ERROR_HANDLING['continue_on_non_critical_errors'];
    }
}