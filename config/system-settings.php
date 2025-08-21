<?php

return [
    /*
    |--------------------------------------------------------------------------
    | 系統設定分類
    |--------------------------------------------------------------------------
    |
    | 定義所有系統設定的分類，包含名稱、圖示和描述
    |
    */
    'categories' => [
        'basic' => [
            'name' => '基本設定',
            'icon' => 'cog',
            'description' => '應用程式基本資訊和行為設定',
            'order' => 1,
        ],
        'security' => [
            'name' => '安全設定',
            'icon' => 'shield-check',
            'description' => '系統安全政策和認證設定',
            'order' => 2,
        ],
        'notification' => [
            'name' => '通知設定',
            'icon' => 'bell',
            'description' => '郵件、簡訊和推播通知設定',
            'order' => 3,
        ],
        'appearance' => [
            'name' => '外觀設定',
            'icon' => 'palette',
            'description' => '主題、顏色和使用者介面設定',
            'order' => 4,
        ],
        'integration' => [
            'name' => '整合設定',
            'icon' => 'link',
            'description' => '第三方服務和 API 整合設定',
            'order' => 5,
        ],
        'maintenance' => [
            'name' => '維護設定',
            'icon' => 'wrench',
            'description' => '備份、日誌和系統維護設定',
            'order' => 6,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | 系統設定定義
    |--------------------------------------------------------------------------
    |
    | 定義所有系統設定項目，包含類型、預設值、驗證規則等
    |
    */
    'settings' => [
        // 基本設定
        'app.name' => [
            'category' => 'basic',
            'type' => 'text',
            'default' => 'Laravel Admin System',
            'validation' => 'required|string|max:100',
            'description' => '應用程式名稱',
            'help' => '顯示在瀏覽器標題和系統標頭的應用程式名稱',
            'order' => 1,
        ],
        'app.description' => [
            'category' => 'basic',
            'type' => 'textarea',
            'default' => '功能完整的管理系統',
            'validation' => 'nullable|string|max:500',
            'description' => '應用程式描述',
            'help' => '應用程式的簡短描述，用於 SEO 和系統介紹',
            'order' => 2,
        ],
        'app.timezone' => [
            'category' => 'basic',
            'type' => 'select',
            'default' => 'Asia/Taipei',
            'validation' => 'required|string|in:' . implode(',', timezone_identifiers_list()),
            'description' => '系統時區',
            'help' => '系統預設時區，影響所有時間顯示和記錄',
            'options' => [
                'Asia/Taipei' => '台北 (UTC+8)',
                'Asia/Shanghai' => '上海 (UTC+8)',
                'Asia/Hong_Kong' => '香港 (UTC+8)',
                'Asia/Tokyo' => '東京 (UTC+9)',
                'UTC' => 'UTC (UTC+0)',
                'America/New_York' => '紐約 (UTC-5)',
                'Europe/London' => '倫敦 (UTC+0)',
            ],
            'order' => 3,
        ],
        'app.locale' => [
            'category' => 'basic',
            'type' => 'select',
            'default' => 'zh_TW',
            'validation' => 'required|string|in:zh_TW,zh_CN,en,ja',
            'description' => '預設語言',
            'help' => '系統預設語言，新使用者的預設語言設定',
            'options' => [
                'zh_TW' => '正體中文',
                'zh_CN' => '简体中文',
                'en' => 'English',
                'ja' => '日本語',
            ],
            'order' => 4,
        ],
        'app.date_format' => [
            'category' => 'basic',
            'type' => 'select',
            'default' => 'Y-m-d',
            'validation' => 'required|string',
            'description' => '日期格式',
            'help' => '系統顯示日期的格式',
            'options' => [
                'Y-m-d' => '2024-01-15',
                'd/m/Y' => '15/01/2024',
                'm/d/Y' => '01/15/2024',
                'd-m-Y' => '15-01-2024',
            ],
            'order' => 5,
        ],
        'app.time_format' => [
            'category' => 'basic',
            'type' => 'select',
            'default' => 'H:i',
            'validation' => 'required|string',
            'description' => '時間格式',
            'help' => '系統顯示時間的格式',
            'options' => [
                'H:i' => '24 小時制 (14:30)',
                'g:i A' => '12 小時制 (2:30 PM)',
            ],
            'order' => 6,
        ],

        // 安全設定
        'security.password_min_length' => [
            'category' => 'security',
            'type' => 'number',
            'default' => 8,
            'validation' => 'required|integer|min:6|max:20',
            'description' => '密碼最小長度',
            'help' => '使用者密碼的最小字元數要求',
            'options' => ['min' => 6, 'max' => 20],
            'order' => 1,
        ],
        'security.password_require_uppercase' => [
            'category' => 'security',
            'type' => 'boolean',
            'default' => true,
            'validation' => 'required|boolean',
            'description' => '密碼需要大寫字母',
            'help' => '密碼必須包含至少一個大寫字母',
            'order' => 2,
        ],
        'security.password_require_lowercase' => [
            'category' => 'security',
            'type' => 'boolean',
            'default' => true,
            'validation' => 'required|boolean',
            'description' => '密碼需要小寫字母',
            'help' => '密碼必須包含至少一個小寫字母',
            'order' => 3,
        ],
        'security.password_require_numbers' => [
            'category' => 'security',
            'type' => 'boolean',
            'default' => true,
            'validation' => 'required|boolean',
            'description' => '密碼需要數字',
            'help' => '密碼必須包含至少一個數字',
            'order' => 4,
        ],
        'security.password_require_symbols' => [
            'category' => 'security',
            'type' => 'boolean',
            'default' => false,
            'validation' => 'required|boolean',
            'description' => '密碼需要特殊字元',
            'help' => '密碼必須包含至少一個特殊字元 (!@#$%^&*)',
            'order' => 5,
        ],
        'security.password_expiry_days' => [
            'category' => 'security',
            'type' => 'number',
            'default' => 0,
            'validation' => 'required|integer|min:0|max:365',
            'description' => '密碼過期天數',
            'help' => '密碼過期天數，0 表示永不過期',
            'options' => ['min' => 0, 'max' => 365],
            'order' => 6,
        ],
        'security.login_max_attempts' => [
            'category' => 'security',
            'type' => 'number',
            'default' => 5,
            'validation' => 'required|integer|min:3|max:10',
            'description' => '登入失敗鎖定次數',
            'help' => '連續登入失敗多少次後鎖定帳號',
            'options' => ['min' => 3, 'max' => 10],
            'order' => 7,
        ],
        'security.lockout_duration' => [
            'category' => 'security',
            'type' => 'number',
            'default' => 15,
            'validation' => 'required|integer|min:1|max:1440',
            'description' => '帳號鎖定時間（分鐘）',
            'help' => '帳號被鎖定後多少分鐘才能再次嘗試登入',
            'options' => ['min' => 1, 'max' => 1440],
            'order' => 8,
        ],
        'security.session_lifetime' => [
            'category' => 'security',
            'type' => 'number',
            'default' => 120,
            'validation' => 'required|integer|min:5|max:1440',
            'description' => 'Session 過期時間（分鐘）',
            'help' => '使用者 Session 的有效時間',
            'options' => ['min' => 5, 'max' => 1440],
            'order' => 9,
        ],
        'security.force_https' => [
            'category' => 'security',
            'type' => 'boolean',
            'default' => false,
            'validation' => 'required|boolean',
            'description' => '強制使用 HTTPS',
            'help' => '強制所有連線使用 HTTPS 協定',
            'order' => 10,
        ],
        'security.two_factor_enabled' => [
            'category' => 'security',
            'type' => 'boolean',
            'default' => false,
            'validation' => 'required|boolean',
            'description' => '啟用雙因子認證',
            'help' => '啟用雙因子認證功能',
            'order' => 11,
        ],

        // 通知設定
        'notification.email_enabled' => [
            'category' => 'notification',
            'type' => 'boolean',
            'default' => true,
            'validation' => 'required|boolean',
            'description' => '啟用郵件通知',
            'help' => '啟用系統郵件通知功能',
            'order' => 1,
        ],
        'notification.smtp_host' => [
            'category' => 'notification',
            'type' => 'text',
            'default' => 'smtp.gmail.com',
            'validation' => 'required_if:notification.email_enabled,true|string|max:255',
            'description' => 'SMTP 伺服器主機',
            'help' => 'SMTP 伺服器的主機名稱或 IP 位址',
            'depends_on' => ['notification.email_enabled' => true],
            'order' => 2,
        ],
        'notification.smtp_port' => [
            'category' => 'notification',
            'type' => 'number',
            'default' => 587,
            'validation' => 'required_if:notification.email_enabled,true|integer|min:1|max:65535',
            'description' => 'SMTP 伺服器埠號',
            'help' => 'SMTP 伺服器的埠號（通常為 25、465 或 587）',
            'depends_on' => ['notification.email_enabled' => true],
            'options' => ['min' => 1, 'max' => 65535],
            'order' => 3,
        ],
        'notification.smtp_encryption' => [
            'category' => 'notification',
            'type' => 'select',
            'default' => 'tls',
            'validation' => 'required_if:notification.email_enabled,true|string|in:none,ssl,tls',
            'description' => 'SMTP 加密方式',
            'help' => 'SMTP 連線的加密方式',
            'depends_on' => ['notification.email_enabled' => true],
            'options' => [
                'none' => '無加密',
                'ssl' => 'SSL',
                'tls' => 'TLS',
            ],
            'order' => 4,
        ],
        'notification.smtp_username' => [
            'category' => 'notification',
            'type' => 'text',
            'default' => '',
            'validation' => 'nullable|string|max:255',
            'description' => 'SMTP 使用者名稱',
            'help' => 'SMTP 伺服器的登入使用者名稱',
            'depends_on' => ['notification.email_enabled' => true],
            'order' => 5,
        ],
        'notification.smtp_password' => [
            'category' => 'notification',
            'type' => 'password',
            'default' => '',
            'validation' => 'nullable|string|max:255',
            'description' => 'SMTP 密碼',
            'help' => 'SMTP 伺服器的登入密碼',
            'depends_on' => ['notification.email_enabled' => true],
            'encrypted' => true,
            'order' => 6,
        ],
        'notification.from_name' => [
            'category' => 'notification',
            'type' => 'text',
            'default' => 'Laravel Admin System',
            'validation' => 'required|string|max:255',
            'description' => '寄件者名稱',
            'help' => '系統發送郵件時顯示的寄件者名稱',
            'order' => 7,
        ],
        'notification.from_email' => [
            'category' => 'notification',
            'type' => 'email',
            'default' => 'noreply@example.com',
            'validation' => 'required|email|max:255',
            'description' => '寄件者信箱',
            'help' => '系統發送郵件時使用的寄件者信箱',
            'order' => 8,
        ],
        'notification.rate_limit_per_minute' => [
            'category' => 'notification',
            'type' => 'number',
            'default' => 10,
            'validation' => 'required|integer|min:1|max:100',
            'description' => '每分鐘通知限制',
            'help' => '每分鐘最多發送的通知數量，防止垃圾通知',
            'options' => ['min' => 1, 'max' => 100],
            'order' => 9,
        ],

        // 外觀設定
        'appearance.default_theme' => [
            'category' => 'appearance',
            'type' => 'select',
            'default' => 'auto',
            'validation' => 'required|string|in:light,dark,auto',
            'description' => '預設主題',
            'help' => '系統預設主題模式',
            'options' => [
                'light' => '亮色主題',
                'dark' => '暗色主題',
                'auto' => '自動（跟隨系統）',
            ],
            'preview' => true,
            'order' => 1,
        ],
        'appearance.primary_color' => [
            'category' => 'appearance',
            'type' => 'color',
            'default' => '#3B82F6',
            'validation' => 'required|regex:/^#[0-9A-Fa-f]{6}$/',
            'description' => '主要顏色',
            'help' => '系統主要顏色，用於按鈕、連結等元素',
            'preview' => true,
            'order' => 2,
        ],
        'appearance.secondary_color' => [
            'category' => 'appearance',
            'type' => 'color',
            'default' => '#6B7280',
            'validation' => 'required|regex:/^#[0-9A-Fa-f]{6}$/',
            'description' => '次要顏色',
            'help' => '系統次要顏色，用於輔助元素',
            'preview' => true,
            'order' => 3,
        ],
        'appearance.logo_url' => [
            'category' => 'appearance',
            'type' => 'file',
            'default' => '',
            'validation' => 'nullable|image|mimes:png,jpg,jpeg,svg|max:2048',
            'description' => '系統標誌',
            'help' => '系統標誌圖片，支援 PNG、JPG、SVG 格式，最大 2MB',
            'preview' => true,
            'order' => 4,
        ],
        'appearance.favicon_url' => [
            'category' => 'appearance',
            'type' => 'file',
            'default' => '',
            'validation' => 'nullable|image|mimes:png,ico|max:512',
            'description' => '網站圖示',
            'help' => '瀏覽器標籤頁顯示的圖示，建議 32x32 像素',
            'order' => 5,
        ],
        'appearance.login_background_url' => [
            'category' => 'appearance',
            'type' => 'file',
            'default' => '',
            'validation' => 'nullable|image|mimes:png,jpg,jpeg|max:5120',
            'description' => '登入頁面背景',
            'help' => '登入頁面的背景圖片，最大 5MB',
            'preview' => true,
            'order' => 6,
        ],
        'appearance.page_title_format' => [
            'category' => 'appearance',
            'type' => 'text',
            'default' => '{page} - {app}',
            'validation' => 'required|string|max:100',
            'description' => '頁面標題格式',
            'help' => '瀏覽器標題格式，{page} 為頁面名稱，{app} 為應用程式名稱',
            'order' => 7,
        ],

        // 整合設定
        'integration.google_analytics_id' => [
            'category' => 'integration',
            'type' => 'text',
            'default' => '',
            'validation' => 'nullable|string|regex:/^G-[A-Z0-9]+$/',
            'description' => 'Google Analytics 追蹤 ID',
            'help' => 'Google Analytics 4 的測量 ID（格式：G-XXXXXXXXXX）',
            'order' => 1,
        ],
        'integration.google_oauth_enabled' => [
            'category' => 'integration',
            'type' => 'boolean',
            'default' => false,
            'validation' => 'required|boolean',
            'description' => '啟用 Google 登入',
            'help' => '啟用 Google OAuth 社群登入功能',
            'order' => 2,
        ],
        'integration.google_client_id' => [
            'category' => 'integration',
            'type' => 'text',
            'default' => '',
            'validation' => 'required_if:integration.google_oauth_enabled,true|string|max:255',
            'description' => 'Google Client ID',
            'help' => 'Google OAuth 應用程式的 Client ID',
            'depends_on' => ['integration.google_oauth_enabled' => true],
            'order' => 3,
        ],
        'integration.google_client_secret' => [
            'category' => 'integration',
            'type' => 'password',
            'default' => '',
            'validation' => 'required_if:integration.google_oauth_enabled,true|string|max:255',
            'description' => 'Google Client Secret',
            'help' => 'Google OAuth 應用程式的 Client Secret',
            'depends_on' => ['integration.google_oauth_enabled' => true],
            'encrypted' => true,
            'order' => 4,
        ],
        'integration.facebook_oauth_enabled' => [
            'category' => 'integration',
            'type' => 'boolean',
            'default' => false,
            'validation' => 'required|boolean',
            'description' => '啟用 Facebook 登入',
            'help' => '啟用 Facebook OAuth 社群登入功能',
            'order' => 5,
        ],
        'integration.aws_s3_enabled' => [
            'category' => 'integration',
            'type' => 'boolean',
            'default' => false,
            'validation' => 'required|boolean',
            'description' => '啟用 AWS S3 儲存',
            'help' => '啟用 Amazon S3 雲端儲存功能',
            'order' => 6,
        ],
        'integration.aws_access_key' => [
            'category' => 'integration',
            'type' => 'text',
            'default' => '',
            'validation' => 'required_if:integration.aws_s3_enabled,true|string|max:255',
            'description' => 'AWS Access Key',
            'help' => 'AWS 存取金鑰 ID',
            'depends_on' => ['integration.aws_s3_enabled' => true],
            'encrypted' => true,
            'order' => 7,
        ],
        'integration.aws_secret_key' => [
            'category' => 'integration',
            'type' => 'password',
            'default' => '',
            'validation' => 'required_if:integration.aws_s3_enabled,true|string|max:255',
            'description' => 'AWS Secret Key',
            'help' => 'AWS 秘密存取金鑰',
            'depends_on' => ['integration.aws_s3_enabled' => true],
            'encrypted' => true,
            'order' => 8,
        ],
        'integration.aws_region' => [
            'category' => 'integration',
            'type' => 'select',
            'default' => 'ap-northeast-1',
            'validation' => 'required_if:integration.aws_s3_enabled,true|string',
            'description' => 'AWS 區域',
            'help' => 'AWS S3 儲存桶所在區域',
            'depends_on' => ['integration.aws_s3_enabled' => true],
            'options' => [
                'us-east-1' => '美國東部（維吉尼亞北部）',
                'us-west-2' => '美國西部（奧勒岡）',
                'ap-northeast-1' => '亞太地區（東京）',
                'ap-southeast-1' => '亞太地區（新加坡）',
                'eu-west-1' => '歐洲（愛爾蘭）',
            ],
            'order' => 9,
        ],
        'integration.aws_bucket' => [
            'category' => 'integration',
            'type' => 'text',
            'default' => '',
            'validation' => 'required_if:integration.aws_s3_enabled,true|string|max:255',
            'description' => 'S3 儲存桶名稱',
            'help' => 'AWS S3 儲存桶的名稱',
            'depends_on' => ['integration.aws_s3_enabled' => true],
            'order' => 10,
        ],

        // 維護設定
        'maintenance.auto_backup_enabled' => [
            'category' => 'maintenance',
            'type' => 'boolean',
            'default' => true,
            'validation' => 'required|boolean',
            'description' => '啟用自動備份',
            'help' => '啟用系統自動備份功能',
            'order' => 1,
        ],
        'maintenance.backup_frequency' => [
            'category' => 'maintenance',
            'type' => 'select',
            'default' => 'daily',
            'validation' => 'required_if:maintenance.auto_backup_enabled,true|string|in:hourly,daily,weekly,monthly',
            'description' => '備份頻率',
            'help' => '自動備份的執行頻率',
            'depends_on' => ['maintenance.auto_backup_enabled' => true],
            'options' => [
                'hourly' => '每小時',
                'daily' => '每日',
                'weekly' => '每週',
                'monthly' => '每月',
            ],
            'order' => 2,
        ],
        'maintenance.backup_retention_days' => [
            'category' => 'maintenance',
            'type' => 'number',
            'default' => 30,
            'validation' => 'required|integer|min:1|max:365',
            'description' => '備份保留天數',
            'help' => '備份檔案的保留天數，超過此天數的備份將被自動刪除',
            'options' => ['min' => 1, 'max' => 365],
            'order' => 3,
        ],
        'maintenance.log_level' => [
            'category' => 'maintenance',
            'type' => 'select',
            'default' => 'info',
            'validation' => 'required|string|in:debug,info,notice,warning,error,critical,alert,emergency',
            'description' => '日誌等級',
            'help' => '系統日誌記錄的最低等級',
            'options' => [
                'debug' => 'DEBUG（除錯）',
                'info' => 'INFO（資訊）',
                'notice' => 'NOTICE（注意）',
                'warning' => 'WARNING（警告）',
                'error' => 'ERROR（錯誤）',
                'critical' => 'CRITICAL（嚴重）',
                'alert' => 'ALERT（警報）',
                'emergency' => 'EMERGENCY（緊急）',
            ],
            'order' => 4,
        ],
        'maintenance.log_retention_days' => [
            'category' => 'maintenance',
            'type' => 'number',
            'default' => 14,
            'validation' => 'required|integer|min:1|max:90',
            'description' => '日誌保留天數',
            'help' => '系統日誌檔案的保留天數',
            'options' => ['min' => 1, 'max' => 90],
            'order' => 5,
        ],
        'maintenance.cache_driver' => [
            'category' => 'maintenance',
            'type' => 'select',
            'default' => 'redis',
            'validation' => 'required|string|in:file,redis,memcached,array',
            'description' => '快取驅動',
            'help' => '系統快取使用的驅動程式',
            'options' => [
                'file' => '檔案快取',
                'redis' => 'Redis',
                'memcached' => 'Memcached',
                'array' => '陣列快取（僅測試用）',
            ],
            'order' => 6,
        ],
        'maintenance.maintenance_mode' => [
            'category' => 'maintenance',
            'type' => 'boolean',
            'default' => false,
            'validation' => 'required|boolean',
            'description' => '維護模式',
            'help' => '啟用維護模式，一般使用者將無法存取系統',
            'order' => 7,
        ],
        'maintenance.maintenance_message' => [
            'category' => 'maintenance',
            'type' => 'textarea',
            'default' => '系統正在進行維護，請稍後再試。',
            'validation' => 'required_if:maintenance.maintenance_mode,true|string|max:500',
            'description' => '維護模式訊息',
            'help' => '維護模式時顯示給使用者的訊息',
            'depends_on' => ['maintenance.maintenance_mode' => true],
            'order' => 8,
        ],
        'maintenance.monitoring_enabled' => [
            'category' => 'maintenance',
            'type' => 'boolean',
            'default' => true,
            'validation' => 'required|boolean',
            'description' => '啟用系統監控',
            'help' => '啟用系統效能和健康狀態監控',
            'order' => 9,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | 設定類型和輸入元件映射
    |--------------------------------------------------------------------------
    |
    | 定義不同設定類型對應的輸入元件
    |
    */
    'input_types' => [
        'text' => [
            'component' => 'text-input',
            'validation_js' => 'validateText',
        ],
        'textarea' => [
            'component' => 'textarea-input',
            'validation_js' => 'validateText',
        ],
        'number' => [
            'component' => 'number-input',
            'validation_js' => 'validateNumber',
        ],
        'email' => [
            'component' => 'email-input',
            'validation_js' => 'validateEmail',
        ],
        'password' => [
            'component' => 'password-input',
            'validation_js' => 'validatePassword',
        ],
        'boolean' => [
            'component' => 'toggle-input',
            'validation_js' => 'validateBoolean',
        ],
        'select' => [
            'component' => 'select-input',
            'validation_js' => 'validateSelect',
        ],
        'color' => [
            'component' => 'color-input',
            'validation_js' => 'validateColor',
        ],
        'file' => [
            'component' => 'file-input',
            'validation_js' => 'validateFile',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | 設定依賴關係
    |--------------------------------------------------------------------------
    |
    | 定義設定之間的依賴關係，用於動態顯示和驗證
    |
    */
    'dependencies' => [
        // 通知設定依賴
        'notification.smtp_host' => ['notification.email_enabled'],
        'notification.smtp_port' => ['notification.email_enabled'],
        'notification.smtp_encryption' => ['notification.email_enabled'],
        'notification.smtp_username' => ['notification.email_enabled'],
        'notification.smtp_password' => ['notification.email_enabled'],

        // Google OAuth 依賴
        'integration.google_client_id' => ['integration.google_oauth_enabled'],
        'integration.google_client_secret' => ['integration.google_oauth_enabled'],

        // AWS S3 依賴
        'integration.aws_access_key' => ['integration.aws_s3_enabled'],
        'integration.aws_secret_key' => ['integration.aws_s3_enabled'],
        'integration.aws_region' => ['integration.aws_s3_enabled'],
        'integration.aws_bucket' => ['integration.aws_s3_enabled'],

        // 備份設定依賴
        'maintenance.backup_frequency' => ['maintenance.auto_backup_enabled'],

        // 維護模式依賴
        'maintenance.maintenance_message' => ['maintenance.maintenance_mode'],
    ],

    /*
    |--------------------------------------------------------------------------
    | 可測試的設定
    |--------------------------------------------------------------------------
    |
    | 定義哪些設定可以進行連線測試
    |
    */
    'testable_settings' => [
        'smtp' => [
            'settings' => [
                'notification.smtp_host',
                'notification.smtp_port',
                'notification.smtp_encryption',
                'notification.smtp_username',
                'notification.smtp_password',
            ],
            'test_method' => 'testSmtpConnection',
        ],
        'aws_s3' => [
            'settings' => [
                'integration.aws_access_key',
                'integration.aws_secret_key',
                'integration.aws_region',
                'integration.aws_bucket',
            ],
            'test_method' => 'testAwsS3Connection',
        ],
        'google_oauth' => [
            'settings' => [
                'integration.google_client_id',
                'integration.google_client_secret',
            ],
            'test_method' => 'testGoogleOAuthConnection',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | 預覽功能設定
    |--------------------------------------------------------------------------
    |
    | 定義哪些設定支援即時預覽
    |
    */
    'preview_settings' => [
        'appearance.default_theme',
        'appearance.primary_color',
        'appearance.secondary_color',
        'appearance.logo_url',
        'appearance.login_background_url',
    ],
];