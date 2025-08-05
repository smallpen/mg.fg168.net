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
        'list' => '使用者列表',
        'create' => '建立使用者',
        'edit' => '編輯使用者',
        'delete' => '刪除使用者',
        'search' => '搜尋使用者',
        'filter' => '篩選',
        'username' => '使用者名稱',
        'name' => '姓名',
        'email' => '電子郵件',
        'roles' => '角色',
        'status' => '狀態',
        'created_at' => '建立時間',
        'actions' => '操作',
        'active' => '啟用',
        'inactive' => '停用',
        'no_users' => '沒有找到使用者',
        'management' => '管理系統使用者帳號和權限',
        'add_user' => '新增使用者',
        'search_placeholder' => '搜尋姓名、使用者名稱或電子郵件...',
        'filter_by_role' => '角色篩選',
        'filter_by_status' => '狀態篩選',
        'all_roles' => '全部角色',
        'all_status' => '全部狀態',
        'clear_filters' => '清除篩選',
        'total_users' => '總使用者數',
        'active_users' => '啟用使用者',
        'inactive_users' => '停用使用者',
        'users_with_roles' => '已分配角色',
        'no_role' => '無角色',
        'toggle_status' => '切換狀態',
        'cannot_disable_self' => '您不能停用自己的帳號',
        'cannot_modify_super_admin' => '您沒有權限修改超級管理員的狀態',
        'user_activated' => '使用者已啟用',
        'user_deactivated' => '使用者已停用',
        'no_permission_view' => '您沒有權限查看使用者列表',
        'no_permission_edit' => '您沒有權限修改使用者狀態',
        'no_permission_create' => '您沒有權限建立使用者',
        'no_permission_delete' => '您沒有權限刪除使用者',
        'search_help' => '請嘗試調整搜尋條件或篩選設定',
        
        // 使用者刪除相關
        'confirm_delete_title' => '確認永久刪除使用者',
        'confirm_disable_title' => '確認停用使用者',
        'select_action' => '請選擇操作',
        'disable_user' => '停用使用者',
        'delete_permanently' => '永久刪除使用者',
        'recommended' => '建議',
        'irreversible' => '不可復原',
        'delete_action_description' => '此操作將永久刪除使用者及其所有相關資料，包括角色關聯。此操作無法復原，請謹慎考慮。',
        'disable_action_description' => '此操作將停用使用者帳號，使其無法登入系統。使用者資料將被保留，您可以隨時重新啟用此帳號。',
        'confirm_username_label' => '請輸入使用者名稱 ":username" 以確認刪除',
        'confirm_username_required' => '請輸入使用者名稱以確認刪除',
        'confirm_username_mismatch' => '輸入的使用者名稱不正確',
        'confirm_delete' => '確認刪除',
        'confirm_disable' => '確認停用',
        'processing' => '處理中...',
        'delete_failed' => '操作失敗',
        'user_deleted_permanently' => '使用者 ":username" 已永久刪除',
        'user_disabled' => '使用者 ":username" 已停用',
        'cannot_delete_self' => '您不能刪除自己的帳號',
        'cannot_delete_super_admin' => '您沒有權限刪除超級管理員',
        'cannot_disable_self' => '您不能停用自己的帳號',
        'type_username_to_confirm' => '請輸入完整的使用者名稱以確認此操作',
        'user_not_found' => '找不到指定的使用者',
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
    ],

    // 分頁
    'pagination' => [
        'previous' => '上一頁',
        'next' => '下一頁',
        'showing' => '顯示第 :first 到 :last 筆，共 :total 筆記錄',
        'per_page' => '每頁顯示',
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