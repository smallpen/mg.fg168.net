<?php

return [

    /*
    |--------------------------------------------------------------------------
    | 管理介面語言檔案
    |--------------------------------------------------------------------------
    |
    | 以下語言行用於管理介面的各種元素和訊息。
    |
    */

    'title' => '後台管理系統',
    'welcome' => '歡迎使用後台管理系統',

    // 通用翻譯
    'common' => [
        'back' => '返回',
        'save' => '儲存',
        'cancel' => '取消',
        'delete' => '刪除',
        'edit' => '編輯',
        'view' => '檢視',
        'create' => '建立',
        'update' => '更新',
        'confirm' => '確認',
        'loading' => '載入中...',
        'search' => '搜尋',
        'filter' => '篩選',
        'clear' => '清除',
        'refresh' => '重新整理',
        'show_details' => '顯示詳細',
        'statistics' => '統計資訊',
    ],

    // 動作翻譯
    'actions' => [
        'back' => '返回',
        'save' => '儲存',
        'cancel' => '取消',
        'delete' => '刪除',
        'edit' => '編輯',
        'view' => '檢視',
        'create' => '建立',
        'update' => '更新',
        'confirm' => '確認',
    ],

    // 導航選單
    'navigation' => [
        'dashboard' => '儀表板',
        'users' => '使用者管理',
        'roles' => '角色管理',
        'permissions' => '權限管理',
        'settings' => '系統設定',
    ],

    // 儀表板
    'dashboard' => [
        'title' => '儀表板',
        'stats' => [
            'total_users' => '總使用者數',
            'total_roles' => '總角色數',
            'total_permissions' => '總權限數',
            'online_users' => '線上使用者',
        ],
        'recent_activity' => '最近活動',
        'quick_actions' => '快速操作',
    ],

    // 使用者管理
    'users' => [
        'title' => '使用者管理',
        'description' => '管理系統中的所有使用者帳號',
        'list' => '使用者列表',
        'create' => '建立使用者',
        'edit' => '編輯使用者',
        'delete' => '刪除使用者',
        'search' => '搜尋使用者',
        'filter' => '篩選',
        'username' => '使用者名稱',
        'name' => '姓名',
        'email' => '電子郵件',
        'view_user' => '檢視使用者：:name',
        'edit_user' => '編輯使用者：:name',
        'status' => '狀態',
        'active' => '啟用',
        'inactive' => '停用',
        'role' => '角色',
        'created_at' => '建立時間',
        'actions' => '操作',
        'view' => '檢視',
        'confirm_delete' => '確認刪除',
        'delete_warning' => '您確定要刪除此使用者嗎？此操作無法復原。',
        'cancel' => '取消',
        'confirm' => '確認',
        
        // 表單相關翻譯
        'create_description' => '建立新的系統使用者帳號',
        'create_user_description' => '建立新的系統使用者帳號',
        'edit_description' => '修改使用者的基本資訊和設定',
        'edit_user_description' => '修改使用者的基本資訊和設定',
        'view_description' => '檢視使用者的詳細資訊和設定',
        'basic_info' => '基本資訊',
        'username_placeholder' => '請輸入使用者名稱',
        'name_placeholder' => '請輸入使用者姓名',
        'email_placeholder' => '請輸入電子郵件地址（選填）',
        'password' => '密碼',
        'password_settings' => '密碼設定',
        'password_placeholder' => '請輸入密碼',
        'password_optional' => '留空則不修改密碼',
        'password_confirmation' => '確認密碼',
        'password_confirmation_placeholder' => '請再次輸入密碼',
        'password_edit_help' => '留空則不修改現有密碼',
        'status_help' => '停用的使用者將無法登入系統',
        'cannot_modify_status' => '您沒有權限修改此使用者的狀態',
        'role_assignment' => '角色分配',
        'current_roles' => '目前角色',
        'cannot_modify_roles' => '您沒有權限修改此使用者的角色',
        'no_roles_available' => '沒有可分配的角色',
        'no_roles_assigned' => '尚未分配任何角色',
        'user' => '使用者',
        
        // 權限相關
        'no_permission_view' => '您沒有權限查看使用者',
        'no_permission_create' => '您沒有權限建立使用者',
        'no_permission_edit' => '您沒有權限編輯使用者',
        'no_permission_delete' => '您沒有權限刪除使用者',
    ],



    // 麵包屑導航
    'breadcrumb' => [
        'navigation' => '導航路徑',
    ],



    // 角色管理
    'roles' => [
        'title' => '角色管理',
        'list' => '角色列表',
        'create' => '建立角色',
        'edit' => '編輯角色',
        'delete' => '刪除角色',
        'name' => '角色名稱',
        'display_name' => '顯示名稱',
        'description' => '描述',
        'permissions' => '權限',
        'users_count' => '使用者數量',
        'permissions_count' => '權限數量',
        'no_roles' => '沒有找到角色',
        
        // 角色名稱本地化
        'names' => [
            'super_admin' => '超級管理員',
            'admin' => '管理員',
            'moderator' => '版主',
            'user' => '一般使用者',
            'guest' => '訪客',
        ],
        
        // 角色描述本地化
        'descriptions' => [
            'super_admin' => '擁有系統所有權限的最高管理員',
            'admin' => '擁有大部分管理權限的系統管理員',
            'moderator' => '擁有內容管理權限的版主',
            'user' => '一般系統使用者',
            'guest' => '訪客使用者，權限有限',
        ],
        'management' => '管理系統角色和權限設定',
        'add_role' => '新增角色',
        'search' => '搜尋角色',
        'search_placeholder' => '搜尋角色名稱、顯示名稱或描述...',
        'filter_by_status' => '狀態篩選',
        'all_status' => '全部狀態',
        'active' => '啟用',
        'inactive' => '停用',
        'status' => '狀態',
        'created_at' => '建立時間',
        'actions' => '操作',
        'clear_filters' => '清除篩選',
        'total_roles' => '總角色數',
        'active_roles' => '啟用角色',
        'inactive_roles' => '停用角色',
        'total_assigned_users' => '已分配使用者總數',
        'search_help' => '請嘗試調整搜尋條件或篩選設定',
        'manage_permissions' => '管理權限',
        'cannot_disable_super_admin' => '無法停用超級管理員角色',
        'status_not_supported' => '此系統版本不支援角色狀態管理',
        'role_activated' => '角色已啟用',
        'role_deactivated' => '角色已停用',
        'no_permission_view' => '您沒有權限查看角色列表',
        'no_permission_edit' => '您沒有權限修改角色',
        'no_permission_create' => '您沒有權限建立角色',
        'no_permission_delete' => '您沒有權限刪除角色',
        'basic_info' => '基本資訊',
        'cannot_modify_super_admin' => '無法修改超級管理員角色',
        'cannot_change_super_admin_name' => '無法修改超級管理員角色的名稱',
        
        // 角色刪除相關
        'confirm_delete_title' => '確認刪除角色',
        'confirm_role_name_label' => '請輸入角色名稱 ":name" 以確認刪除',
        'confirm_role_name_required' => '請輸入角色名稱以確認刪除',
        'confirm_role_name_mismatch' => '輸入的角色名稱不正確',
        'type_role_name_to_confirm' => '請輸入完整的角色名稱以確認此操作',
        'confirm_delete' => '確認刪除',
        'processing' => '處理中...',
        'delete_failed' => '刪除失敗',
        'role_not_found' => '找不到指定的角色',
        'cannot_delete_super_admin' => '無法刪除超級管理員角色',
        'cannot_delete_system_role' => '您沒有權限刪除系統預設角色',
        'role_has_users' => '此角色目前有 :count 個使用者正在使用，請先勾選強制刪除選項',
        'delete_warning_title' => '刪除警告',
        'delete_warning_users' => '此操作將影響 :count 個使用者，他們將失去此角色的所有權限。',
        'delete_warning_permissions' => '此角色擁有 :count 個權限，這些權限關聯將被移除。',
        'delete_warning_irreversible' => '此操作無法復原，請謹慎考慮。',
        'users_will_be_affected' => '使用者將受到影響',
        'users_affected_description' => '刪除此角色將影響 :count 個使用者，他們將失去此角色提供的所有權限。',
        'force_delete_confirmation' => '我了解此操作的影響，仍要強制刪除此角色',
        'role_deleted_successfully' => '角色 ":name" 已成功刪除，影響了 :users_affected 個使用者',
    ],

    // 權限管理
    'permissions' => [
        'title' => '權限管理',
        'matrix' => '權限矩陣',
        'matrix_description' => '管理角色和權限的對應關係，支援批量操作和即時預覽',
        'name' => '權限名稱',
        'display_name' => '顯示名稱',
        'description' => '描述',
        'module' => '模組',
        'no_permissions' => '沒有找到權限',
        'search' => '搜尋權限',
        'search_placeholder' => '搜尋權限名稱、顯示名稱或描述...',
        'filter_by_module' => '模組篩選',
        'all_modules' => '全部模組',
        'clear_filters' => '清除篩選',
        'search_help' => '請嘗試調整搜尋條件或篩選設定',
        'no_permission_edit' => '您沒有權限編輯權限設定',
    ],

    // 通用操作
    'actions' => [
        'create' => '建立',
        'edit' => '編輯',
        'delete' => '刪除',
        'save' => '儲存',
        'cancel' => '取消',
        'confirm' => '確認',
        'back' => '返回',
        'search' => '搜尋',
        'filter' => '篩選',
        'reset' => '重設',
        'submit' => '提交',
        'close' => '關閉',
        'view' => '檢視',
        'update' => '更新',
    ],

    // 狀態訊息
    'messages' => [
        'success' => [
            'created' => ':item 已成功建立',
            'updated' => ':item 已成功更新',
            'deleted' => ':item 已成功刪除',
            'saved' => '資料已成功儲存',
        ],
        'error' => [
            'create_failed' => '建立 :item 失敗',
            'update_failed' => '更新 :item 失敗',
            'delete_failed' => '刪除 :item 失敗',
            'not_found' => '找不到指定的 :item',
            'permission_denied' => '權限不足',
        ],
        'confirm' => [
            'delete' => '確定要刪除這個 :item 嗎？此操作無法復原。',
        ],
    ],

    // 表單驗證
    'validation' => [
        'required' => ':attribute 欄位為必填',
        'unique' => ':attribute 已經存在',
        'min' => ':attribute 至少需要 :min 個字元',
        'max' => ':attribute 不能超過 :max 個字元',
        'email' => ':attribute 必須是有效的電子郵件地址',
        'confirmed' => ':attribute 確認不符',
        'invalid_search_content' => '搜尋條件包含無效內容',
        'search_format_error' => '搜尋條件格式錯誤',
        'invalid_user_id' => '無效的使用者 ID',
        'invalid_user_ids' => '選中的使用者 ID 無效',
    ],

    // 分頁
    'pagination' => [
        'previous' => '上一頁',
        'next' => '下一頁',
        'showing' => '顯示第 :first 到 :last 筆，共 :total 筆記錄',
        'per_page' => '每頁顯示',
        'navigation' => '分頁導航',
    ],

    // 日期時間
    'datetime' => [
        'formats' => [
            'default' => 'Y年m月d日 H:i',
            'short' => 'm/d H:i',
            'long' => 'Y年m月d日 (l) H:i:s',
            'date_only' => 'Y年m月d日',
            'time_only' => 'H:i',
        ],
        'relative' => [
            'just_now' => '剛剛',
            'seconds_ago' => ':count 秒前',
            'minutes_ago' => ':count 分鐘前',
            'hours_ago' => ':count 小時前',
            'days_ago' => ':count 天前',
            'weeks_ago' => ':count 週前',
            'months_ago' => ':count 個月前',
            'years_ago' => ':count 年前',
        ],
        'weekdays' => [
            'monday' => '星期一',
            'tuesday' => '星期二',
            'wednesday' => '星期三',
            'thursday' => '星期四',
            'friday' => '星期五',
            'saturday' => '星期六',
            'sunday' => '星期日',
        ],
        'months' => [
            'january' => '一月',
            'february' => '二月',
            'march' => '三月',
            'april' => '四月',
            'may' => '五月',
            'june' => '六月',
            'july' => '七月',
            'august' => '八月',
            'september' => '九月',
            'october' => '十月',
            'november' => '十一月',
            'december' => '十二月',
        ],
    ],

    // 主題和語言
    'theme' => [
        'title' => '主題設定',
        'light' => '淺色主題',
        'dark' => '暗黑主題',
        'toggle' => '切換主題',
    ],

    'language' => [
        'title' => '語言設定',
        'current' => '目前語言',
        'select' => '選擇語言',
        'zh_TW' => '正體中文',
        'en' => 'English',
        'unsupported' => '不支援的語言',
        'switched' => '語言已切換為 :language',
    ],

];