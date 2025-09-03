<?php

return [

    /*
    |--------------------------------------------------------------------------
    | 佈局和導航語言檔案
    |--------------------------------------------------------------------------
    |
    | 以下語言行用於管理後台佈局和導航系統的各種元素。
    |
    */

    // 頂部導航列
    'topnav' => [
        'toggle_sidebar' => '切換側邊選單',
        'search_placeholder' => '搜尋任何內容...',
        'notifications' => '通知',
        'no_notifications' => '沒有新通知',
        'mark_all_read' => '全部標記已讀',
        'view_all' => '查看全部通知',
        'theme_toggle' => '切換主題',
        'language_selector' => '語言選擇',
        'user_menu' => '使用者選單',
        'profile' => '個人資料',
        'settings' => '設定',
        'logout' => '登出',
    ],

    // 側邊選單
    'sidebar' => [
        'dashboard' => '儀表板',
        'user_management' => '使用者管理',
        'user_list' => '使用者列表',
        'create_user' => '建立使用者',
        'user_roles' => '使用者角色',
        'role_management' => '角色管理',
        'role_list' => '角色列表',
        'permission_settings' => '權限設定',
        'role_hierarchy' => '角色層級',
        'permission_management' => '權限管理',
        'permission_list' => '權限列表',
        'permission_groups' => '權限分組',
        'permission_dependencies' => '依賴關係',
        'permission_templates' => '權限模板',
        'permission_test' => '權限測試',
        'permission_usage' => '使用情況',
        'permission_audit' => '權限審計',
        'permission_import_export' => '匯入/匯出',
        'system_settings' => '系統設定',
        'basic_settings' => '基本設定',
        'security_settings' => '安全設定',
        'appearance_settings' => '外觀設定',
        'activity_logs' => '活動記錄',
        'activity_list' => '活動列表',
        'activity_monitoring' => '即時監控',
        'activity_statistics' => '活動統計',
        'activity_export' => '匯出活動',
        'activity_settings' => '活動設定',
        'operation_logs' => '操作日誌',
        'security_events' => '安全事件',
        'statistical_analysis' => '統計分析',
        'search_menu' => '搜尋選單...',
        'collapse_menu' => '收合選單',
        'expand_menu' => '展開選單',
        'general' => '基本設定',
        'security' => '安全設定',
        'appearance' => '外觀設定',
        'notifications' => '通知設定',
        'integration' => '整合設定',
        'maintenance' => '維護設定',
        'backups' => '備份管理',
    ],

    // 麵包屑導航
    'breadcrumb' => [
        'home' => '首頁',
        'separator' => '/',
        'current_page' => '當前頁面',
    ],

    // 主題系統
    'theme' => [
        'light' => '亮色主題',
        'dark' => '暗色主題',
        'auto' => '自動模式',
        'system' => '跟隨系統',
        'toggle_tooltip' => '切換主題模式',
        'current_theme' => '當前主題：:theme',
    ],

    // 語言系統
    'language' => [
        'zh_TW' => '正體中文',
        'en' => 'English',
        'current' => '當前語言',
        'switch_to' => '選擇語言',
        'switched' => '語言已切換為 :language',
        'unsupported' => '不支援的語言',
        'loading' => '正在切換語言...',
        'switching' => '正在切換語言...',
        'please_wait' => '請稍候，頁面即將重新載入',
        'confirm_switch_title' => '確認語言切換',
        'confirm_switch_message' => '您確定要切換語言嗎？頁面將會重新載入以套用新的語言設定。',
        'from' => '從',
        'to' => '到',
    ],

    // 通用翻譯
    'welcome_back' => '歡迎回來，:name',
    'basic_settings' => '基本設定',
    'mail_settings' => '郵件設定',
    'cache_management' => '快取管理',

    // 通知中心
    'notifications' => [
        'title' => '通知中心',
        'empty' => '沒有通知',
        'empty_description' => '您目前沒有任何通知',
        'mark_read' => '標記為已讀',
        'mark_unread' => '標記為未讀',
        'delete' => '刪除通知',
        'filter_all' => '全部',
        'filter_unread' => '未讀',
        'filter_security' => '安全事件',
        'time_ago' => ':time 前',
        'just_now' => '剛剛',
        'minutes_ago' => ':count 分鐘前',
        'hours_ago' => ':count 小時前',
        'days_ago' => ':count 天前',
        'weeks_ago' => ':count 週前',
        'months_ago' => ':count 個月前',
        'years_ago' => ':count 年前',
    ],

    // 全域搜尋
    'search' => [
        'placeholder' => '搜尋頁面、使用者、角色...',
        'no_results' => '沒有找到相關結果',
        'no_results_description' => '請嘗試使用不同的關鍵字',
        'recent_searches' => '最近搜尋',
        'clear_recent' => '清除最近搜尋',
        'categories' => [
            'all' => '全部',
            'pages' => '頁面',
            'users' => '使用者',
            'roles' => '角色',
            'permissions' => '權限',
            'activities' => '活動',
        ],
        'results_count' => '找到 :count 個結果',
        'keyboard_shortcuts' => [
            'open' => '按 Ctrl+K 開啟搜尋',
            'navigate' => '使用 ↑↓ 導航',
            'select' => '按 Enter 選擇',
            'close' => '按 Esc 關閉',
        ],
    ],

    // 載入狀態
    'loading' => [
        'default' => '載入中...',
        'saving' => '儲存中...',
        'deleting' => '刪除中...',
        'processing' => '處理中...',
        'searching' => '搜尋中...',
        'switching_language' => '切換語言中...',
        'switching_theme' => '切換主題中...',
    ],

    // 錯誤訊息
    'errors' => [
        'network_error' => '網路連線錯誤',
        'server_error' => '伺服器錯誤',
        'permission_denied' => '權限不足',
        'not_found' => '找不到頁面',
        'session_expired' => 'Session 已過期，請重新登入',
        'maintenance_mode' => '系統維護中，請稍後再試',
    ],

    // 成功訊息
    'success' => [
        'saved' => '儲存成功',
        'deleted' => '刪除成功',
        'updated' => '更新成功',
        'created' => '建立成功',
        'language_switched' => '語言切換成功',
        'theme_switched' => '主題切換成功',
    ],

    // 確認對話框
    'confirm' => [
        'title' => '確認操作',
        'message' => '您確定要執行此操作嗎？',
        'yes' => '確認',
        'no' => '取消',
        'delete_title' => '確認刪除',
        'delete_message' => '此操作無法復原，您確定要刪除嗎？',
    ],

    // 響應式佈局
    'responsive' => [
        'mobile_menu' => '行動版選單',
        'desktop_view' => '桌面版檢視',
        'tablet_view' => '平板版檢視',
        'mobile_view' => '手機版檢視',
    ],

    // 無障礙功能
    'accessibility' => [
        'skip_to_content' => '跳到主要內容',
        'skip_to_navigation' => '跳到導航選單',
        'menu_button' => '選單按鈕',
        'close_menu' => '關閉選單',
        'open_menu' => '開啟選單',
        'current_page' => '當前頁面',
        'external_link' => '外部連結',
        'new_window' => '在新視窗開啟',
    ],

];