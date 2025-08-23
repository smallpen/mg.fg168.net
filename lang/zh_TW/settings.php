<?php

return [
    /*
    |--------------------------------------------------------------------------
    | 系統設定語言檔案 - 正體中文
    |--------------------------------------------------------------------------
    |
    | 系統設定功能的所有語言字串
    |
    */

    // 頁面標題和導航
    'title' => '系統設定',
    'subtitle' => '管理應用程式的各項設定',
    'breadcrumb' => [
        'home' => '首頁',
        'settings' => '系統設定',
        'category' => '設定分類',
        'backup' => '設定備份',
        'history' => '變更歷史',
        'import_export' => '匯入匯出',
    ],

    // 分類名稱
    'categories' => [
        'basic' => [
            'name' => '基本設定',
            'description' => '應用程式基本資訊和行為設定',
        ],
        'security' => [
            'name' => '安全設定',
            'description' => '系統安全政策和認證設定',
        ],
        'notification' => [
            'name' => '通知設定',
            'description' => '郵件、簡訊和推播通知設定',
        ],
        'appearance' => [
            'name' => '外觀設定',
            'description' => '主題、顏色和使用者介面設定',
        ],
        'integration' => [
            'name' => '整合設定',
            'description' => '第三方服務和 API 整合設定',
        ],
        'maintenance' => [
            'name' => '維護設定',
            'description' => '備份、日誌和系統維護設定',
        ],
        'performance' => [
            'name' => '效能設定',
            'description' => '快取、批量處理和效能優化設定',
        ],
    ],

    // 設定項目名稱和描述
    'settings' => [
        // 基本設定
        'app.name' => [
            'name' => '應用程式名稱',
            'description' => '顯示在瀏覽器標題和系統標頭的應用程式名稱',
        ],
        'app.description' => [
            'name' => '應用程式描述',
            'description' => '應用程式的簡短描述，用於 SEO 和系統介紹',
        ],
        'app.timezone' => [
            'name' => '系統時區',
            'description' => '系統預設時區，影響所有時間顯示和記錄',
        ],
        'app.locale' => [
            'name' => '預設語言',
            'description' => '系統預設語言，新使用者的預設語言設定',
        ],
        'app.date_format' => [
            'name' => '日期格式',
            'description' => '系統顯示日期的格式',
        ],
        'app.time_format' => [
            'name' => '時間格式',
            'description' => '系統顯示時間的格式',
        ],

        // 安全設定
        'security.password_min_length' => [
            'name' => '密碼最小長度',
            'description' => '使用者密碼的最小字元數要求',
        ],
        'security.password_require_uppercase' => [
            'name' => '密碼需要大寫字母',
            'description' => '密碼必須包含至少一個大寫字母',
        ],
        'security.password_require_lowercase' => [
            'name' => '密碼需要小寫字母',
            'description' => '密碼必須包含至少一個小寫字母',
        ],
        'security.password_require_numbers' => [
            'name' => '密碼需要數字',
            'description' => '密碼必須包含至少一個數字',
        ],
        'security.password_require_symbols' => [
            'name' => '密碼需要特殊字元',
            'description' => '密碼必須包含至少一個特殊字元 (!@#$%^&*)',
        ],
        'security.password_expiry_days' => [
            'name' => '密碼過期天數',
            'description' => '密碼過期天數，0 表示永不過期',
        ],
        'security.login_max_attempts' => [
            'name' => '登入失敗鎖定次數',
            'description' => '連續登入失敗多少次後鎖定帳號',
        ],
        'security.lockout_duration' => [
            'name' => '帳號鎖定時間（分鐘）',
            'description' => '帳號被鎖定後多少分鐘才能再次嘗試登入',
        ],
        'security.session_lifetime' => [
            'name' => 'Session 過期時間（分鐘）',
            'description' => '使用者 Session 的有效時間',
        ],
        'security.force_https' => [
            'name' => '強制使用 HTTPS',
            'description' => '強制所有連線使用 HTTPS 協定',
        ],
        'security.two_factor_enabled' => [
            'name' => '啟用雙因子認證',
            'description' => '啟用雙因子認證功能',
        ],

        // 通知設定
        'notification.email_enabled' => [
            'name' => '啟用郵件通知',
            'description' => '啟用系統郵件通知功能',
        ],
        'notification.smtp_host' => [
            'name' => 'SMTP 伺服器主機',
            'description' => 'SMTP 伺服器的主機名稱或 IP 位址',
        ],
        'notification.smtp_port' => [
            'name' => 'SMTP 伺服器埠號',
            'description' => 'SMTP 伺服器的埠號（通常為 25、465 或 587）',
        ],
        'notification.smtp_encryption' => [
            'name' => 'SMTP 加密方式',
            'description' => 'SMTP 連線的加密方式',
        ],
        'notification.smtp_username' => [
            'name' => 'SMTP 使用者名稱',
            'description' => 'SMTP 伺服器的登入使用者名稱',
        ],
        'notification.smtp_password' => [
            'name' => 'SMTP 密碼',
            'description' => 'SMTP 伺服器的登入密碼',
        ],
        'notification.from_name' => [
            'name' => '寄件者名稱',
            'description' => '系統發送郵件時顯示的寄件者名稱',
        ],
        'notification.from_email' => [
            'name' => '寄件者信箱',
            'description' => '系統發送郵件時使用的寄件者信箱',
        ],

        // 外觀設定
        'appearance.default_theme' => [
            'name' => '預設主題',
            'description' => '系統預設主題模式',
        ],
        'appearance.primary_color' => [
            'name' => '主要顏色',
            'description' => '系統主要顏色，用於按鈕、連結等元素',
        ],
        'appearance.secondary_color' => [
            'name' => '次要顏色',
            'description' => '系統次要顏色，用於輔助元素',
        ],
        'appearance.logo_url' => [
            'name' => '系統標誌',
            'description' => '系統標誌圖片，支援 PNG、JPG、SVG 格式，最大 2MB',
        ],
        'appearance.favicon_url' => [
            'name' => '網站圖示',
            'description' => '瀏覽器標籤頁顯示的圖示，建議 32x32 像素',
        ],
        'appearance.login_background_url' => [
            'name' => '登入頁面背景',
            'description' => '登入頁面的背景圖片，最大 5MB',
        ],
        'appearance.page_title_format' => [
            'name' => '頁面標題格式',
            'description' => '瀏覽器標題格式，{page} 為頁面名稱，{app} 為應用程式名稱',
        ],
        'appearance.custom_css' => [
            'name' => '自訂 CSS',
            'description' => '自訂 CSS 樣式，將在所有頁面載入',
        ],

        // 整合設定
        'integration.google_analytics_id' => [
            'name' => 'Google Analytics 追蹤 ID',
            'description' => 'Google Analytics 4 的測量 ID（格式：G-XXXXXXXXXX）',
        ],
        'integration.google_oauth_enabled' => [
            'name' => '啟用 Google 登入',
            'description' => '啟用 Google OAuth 社群登入功能',
        ],
        'integration.google_client_id' => [
            'name' => 'Google Client ID',
            'description' => 'Google OAuth 應用程式的 Client ID',
        ],
        'integration.google_client_secret' => [
            'name' => 'Google Client Secret',
            'description' => 'Google OAuth 應用程式的 Client Secret',
        ],

        // 維護設定
        'maintenance.auto_backup_enabled' => [
            'name' => '啟用自動備份',
            'description' => '啟用系統自動備份功能',
        ],
        'maintenance.backup_frequency' => [
            'name' => '備份頻率',
            'description' => '自動備份的執行頻率',
        ],
        'maintenance.backup_retention_days' => [
            'name' => '備份保留天數',
            'description' => '備份檔案的保留天數，超過此天數的備份將被自動刪除',
        ],
        'maintenance.log_level' => [
            'name' => '日誌等級',
            'description' => '系統日誌記錄的最低等級',
        ],
        'maintenance.maintenance_mode' => [
            'name' => '維護模式',
            'description' => '啟用維護模式，一般使用者將無法存取系統',
        ],

        // 效能設定
        'performance.cache_enabled' => [
            'name' => '啟用多層快取',
            'description' => '啟用記憶體、Redis 和資料庫的多層快取機制',
        ],
        'performance.batch_size' => [
            'name' => '批量處理大小',
            'description' => '批量操作的預設處理大小，範圍 10-1000',
        ],
        'performance.queue_enabled' => [
            'name' => '啟用佇列處理',
            'description' => '對大量操作使用佇列進行背景處理',
        ],
    ],

    // 操作按鈕和動作
    'actions' => [
        'save' => '儲存',
        'cancel' => '取消',
        'reset' => '重設',
        'edit' => '編輯',
        'delete' => '刪除',
        'test' => '測試',
        'preview' => '預覽',
        'export' => '匯出',
        'import' => '匯入',
        'backup' => '備份',
        'restore' => '還原',
        'search' => '搜尋',
        'filter' => '篩選',
        'clear' => '清除',
        'apply' => '套用',
        'close' => '關閉',
        'confirm' => '確認',
        'back' => '返回',
        'next' => '下一步',
        'previous' => '上一步',
        'upload' => '上傳',
        'download' => '下載',
        'copy' => '複製',
        'refresh' => '重新整理',
    ],

    // 狀態和標籤
    'status' => [
        'enabled' => '已啟用',
        'disabled' => '已停用',
        'active' => '啟用',
        'inactive' => '停用',
        'changed' => '已變更',
        'unchanged' => '未變更',
        'default' => '預設值',
        'custom' => '自訂值',
        'required' => '必填',
        'optional' => '選填',
        'encrypted' => '已加密',
        'testing' => '測試中',
        'success' => '成功',
        'failed' => '失敗',
        'warning' => '警告',
        'error' => '錯誤',
        'loading' => '載入中',
        'saving' => '儲存中',
    ],

    // 表單標籤
    'form' => [
        'category' => '分類',
        'name' => '名稱',
        'value' => '值',
        'description' => '描述',
        'help' => '說明',
        'default_value' => '預設值',
        'current_value' => '目前值',
        'new_value' => '新值',
        'search_placeholder' => '搜尋設定...',
        'filter_category' => '篩選分類',
        'filter_status' => '篩選狀態',
        'all_categories' => '所有分類',
        'all_status' => '所有狀態',
        'show_changed_only' => '只顯示已變更',
        'show_all' => '顯示全部',
    ],

    // 訊息和通知
    'messages' => [
        'saved' => '設定已儲存',
        'save_failed' => '設定儲存失敗',
        'reset_success' => '設定已重設為預設值',
        'reset_failed' => '設定重設失敗',
        'test_success' => '測試成功',
        'test_failed' => '測試失敗',
        'connection_success' => '連線測試成功',
        'connection_failed' => '連線測試失敗',
        'no_settings_found' => '找不到符合條件的設定',
        'loading_settings' => '載入設定中...',
        'unsaved_changes' => '您有未儲存的變更',
        'confirm_reset' => '確定要重設此設定為預設值嗎？',
        'confirm_delete' => '確定要刪除此項目嗎？',
        'operation_success' => '操作成功完成',
        'operation_failed' => '操作失敗',
        'validation_error' => '驗證錯誤',
        'permission_denied' => '權限不足',
        'setting_locked' => '此設定已被鎖定，無法修改',
        'dependency_warning' => '此設定的變更可能影響其他相關設定',
    ],

    // 備份管理
    'backup' => [
        'title' => '設定備份',
        'create' => '建立備份',
        'restore' => '還原備份',
        'delete' => '刪除備份',
        'download' => '下載備份',
        'compare' => '比較備份',
        'name' => '備份名稱',
        'description' => '備份描述',
        'created_at' => '建立時間',
        'created_by' => '建立者',
        'size' => '大小',
        'settings_count' => '設定數量',
        'no_backups' => '尚無備份記錄',
        'create_success' => '備份建立成功',
        'create_failed' => '備份建立失敗',
        'restore_success' => '備份還原成功',
        'restore_failed' => '備份還原失敗',
        'delete_success' => '備份刪除成功',
        'delete_failed' => '備份刪除失敗',
        'confirm_restore' => '確定要還原此備份嗎？這將覆蓋目前的設定。',
        'confirm_delete' => '確定要刪除此備份嗎？此操作無法復原。',
    ],

    // 匯入匯出
    'import_export' => [
        'title' => '匯入匯出設定',
        'export' => '匯出設定',
        'import' => '匯入設定',
        'export_success' => '設定匯出成功',
        'export_failed' => '設定匯出失敗',
        'import_success' => '設定匯入成功',
        'import_failed' => '設定匯入失敗',
        'select_file' => '選擇檔案',
        'file_format' => '檔案格式',
        'supported_formats' => '支援的格式：JSON',
        'export_options' => '匯出選項',
        'export_all' => '匯出所有設定',
        'export_category' => '匯出指定分類',
        'export_changed' => '只匯出已變更的設定',
        'import_options' => '匯入選項',
        'import_mode' => '匯入模式',
        'import_mode_merge' => '合併（保留現有設定）',
        'import_mode_replace' => '取代（覆蓋現有設定）',
        'conflict_resolution' => '衝突處理',
        'conflict_skip' => '跳過衝突項目',
        'conflict_update' => '更新衝突項目',
        'preview_changes' => '預覽變更',
        'import_summary' => '匯入摘要',
        'imported_count' => '已匯入',
        'skipped_count' => '已跳過',
        'error_count' => '錯誤',
        'invalid_file' => '無效的檔案格式',
        'file_too_large' => '檔案過大',
        'no_file_selected' => '請選擇要匯入的檔案',
    ],

    // 變更歷史
    'history' => [
        'title' => '設定變更歷史',
        'setting' => '設定項目',
        'old_value' => '舊值',
        'new_value' => '新值',
        'changed_by' => '變更者',
        'changed_at' => '變更時間',
        'reason' => '變更原因',
        'ip_address' => 'IP 位址',
        'user_agent' => '使用者代理',
        'no_history' => '尚無變更記錄',
        'view_details' => '檢視詳情',
        'revert' => '回復此版本',
        'revert_success' => '設定已回復到指定版本',
        'revert_failed' => '設定回復失敗',
        'confirm_revert' => '確定要回復到此版本嗎？',
        'filter_setting' => '篩選設定',
        'filter_user' => '篩選使用者',
        'filter_date' => '篩選日期',
        'date_from' => '開始日期',
        'date_to' => '結束日期',
    ],

    // 預覽功能
    'preview' => [
        'title' => '設定預覽',
        'enable' => '啟用預覽',
        'disable' => '停用預覽',
        'apply' => '套用預覽',
        'reset' => '重設預覽',
        'theme_preview' => '主題預覽',
        'color_preview' => '顏色預覽',
        'layout_preview' => '版面預覽',
        'email_preview' => '郵件預覽',
        'preview_mode' => '預覽模式',
        'live_preview' => '即時預覽',
        'preview_warning' => '預覽模式僅供參考，實際效果可能略有差異',
    ],

    // 測試功能
    'test' => [
        'connection' => '測試連線',
        'email' => '測試郵件',
        'smtp' => '測試 SMTP',
        'oauth' => '測試 OAuth',
        'api' => '測試 API',
        'database' => '測試資料庫',
        'cache' => '測試快取',
        'storage' => '測試儲存',
        'test_email_subject' => '系統設定測試郵件',
        'test_email_body' => '這是一封來自系統設定的測試郵件，如果您收到此郵件，表示郵件設定正常運作。',
        'send_test_email' => '發送測試郵件',
        'test_recipient' => '測試收件者',
        'test_in_progress' => '測試進行中...',
        'test_completed' => '測試完成',
        'test_details' => '測試詳情',
        'connection_timeout' => '連線逾時',
        'authentication_failed' => '認證失敗',
        'invalid_credentials' => '無效的憑證',
        'service_unavailable' => '服務無法使用',
    ],

    // 驗證訊息
    'validation' => [
        'required' => ':attribute 為必填欄位',
        'string' => ':attribute 必須是字串',
        'numeric' => ':attribute 必須是數字',
        'integer' => ':attribute 必須是整數',
        'boolean' => ':attribute 必須是布林值',
        'email' => ':attribute 必須是有效的電子郵件地址',
        'url' => ':attribute 必須是有效的網址',
        'min' => ':attribute 最小值為 :min',
        'max' => ':attribute 最大值為 :max',
        'between' => ':attribute 必須介於 :min 和 :max 之間',
        'in' => ':attribute 必須是以下值之一：:values',
        'regex' => ':attribute 格式不正確',
        'unique' => ':attribute 已經存在',
        'exists' => ':attribute 不存在',
        'file' => ':attribute 必須是檔案',
        'image' => ':attribute 必須是圖片',
        'mimes' => ':attribute 必須是以下格式之一：:values',
        'max_file_size' => ':attribute 檔案大小不能超過 :max KB',
        'json' => ':attribute 必須是有效的 JSON 格式',
        'array' => ':attribute 必須是陣列',
        'date' => ':attribute 必須是有效的日期',
        'after' => ':attribute 必須在 :date 之後',
        'before' => ':attribute 必須在 :date 之前',
        'confirmed' => ':attribute 確認不符',
        'same' => ':attribute 和 :other 必須相同',
        'different' => ':attribute 和 :other 必須不同',
        'alpha' => ':attribute 只能包含字母',
        'alpha_num' => ':attribute 只能包含字母和數字',
        'alpha_dash' => ':attribute 只能包含字母、數字、破折號和底線',
        'ip' => ':attribute 必須是有效的 IP 位址',
        'ipv4' => ':attribute 必須是有效的 IPv4 位址',
        'ipv6' => ':attribute 必須是有效的 IPv6 位址',
        'timezone' => ':attribute 必須是有效的時區',
    ],

    // 選項值
    'options' => [
        'yes' => '是',
        'no' => '否',
        'enabled' => '啟用',
        'disabled' => '停用',
        'true' => '真',
        'false' => '假',
        'on' => '開',
        'off' => '關',
        'light' => '亮色主題',
        'dark' => '暗色主題',
        'auto' => '自動（跟隨系統）',
        'none' => '無',
        'ssl' => 'SSL',
        'tls' => 'TLS',
        'hourly' => '每小時',
        'daily' => '每日',
        'weekly' => '每週',
        'monthly' => '每月',
        'debug' => 'DEBUG（除錯）',
        'info' => 'INFO（資訊）',
        'warning' => 'WARNING（警告）',
        'error' => 'ERROR（錯誤）',
        'critical' => 'CRITICAL（嚴重）',
        'file' => '檔案快取',
        'redis' => 'Redis',
        'memcached' => 'Memcached',
        'array' => '陣列快取（僅測試用）',
        'sandbox' => '沙盒模式（測試）',
        'live' => '正式環境',
    ],

    // 幫助文字
    'help' => [
        'setting_help' => '點擊設定名稱可查看詳細說明',
        'category_help' => '設定按分類組織，方便管理和查找',
        'search_help' => '可搜尋設定名稱、描述或值',
        'filter_help' => '使用篩選器快速找到特定類型的設定',
        'backup_help' => '定期備份設定可避免意外遺失',
        'import_help' => '匯入設定前請先備份現有設定',
        'preview_help' => '預覽功能讓您在套用前查看變更效果',
        'test_help' => '測試功能可驗證設定的正確性',
        'dependency_help' => '某些設定相互依賴，變更時請注意影響範圍',
        'encryption_help' => '敏感設定會自動加密儲存',
        'validation_help' => '系統會自動驗證設定值的格式和範圍',
        'history_help' => '所有設定變更都會記錄在歷史中',
    ],

    // 統計資訊
    'stats' => [
        'total_settings' => '總設定數',
        'changed_settings' => '已變更設定',
        'default_settings' => '預設設定',
        'encrypted_settings' => '加密設定',
        'categories_count' => '分類數量',
        'last_backup' => '最後備份',
        'last_change' => '最後變更',
        'backup_count' => '備份數量',
        'history_count' => '歷史記錄',
    ],
];