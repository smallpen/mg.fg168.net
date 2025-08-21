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
        'close' => '關閉',
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

    // 系統標題
    'title' => '管理系統',

    // 導航選單
    'navigation' => [
        'dashboard' => '儀表板',
        'users' => '使用者管理',
        'roles' => '角色管理',
        'permissions' => '權限管理',
        'settings' => '系統設定',
    ],

    // 設定選單
    'settings' => [
        'general' => '基本設定',
        'security' => '安全設定',
        'appearance' => '外觀設定',
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
        'description' => '管理系統角色和權限設定',
        'list' => '角色列表',
        'create' => '建立角色',
        'edit' => '編輯角色',
        'delete' => '刪除角色',
        'name' => '角色名稱',
        'display_name' => '顯示名稱',
        'permissions' => '權限',
        'users_count' => '使用者數量',
        'permissions_count' => '權限數量',
        'no_roles' => '沒有找到角色',
        
        // 角色名稱本地化
        'names' => [
            'super_admin' => '超級管理員',
            'admin' => '管理員',
            'moderator' => '版主',
            'editor' => '編輯者',
            'user' => '一般使用者',
            'guest' => '訪客',
        ],
        
        // 角色描述本地化
        'descriptions' => [
            'super_admin' => '擁有系統所有權限的最高管理員',
            'admin' => '擁有大部分管理權限的系統管理員',
            'moderator' => '擁有內容管理權限的版主',
            'editor' => '擁有發布權限的內容編輯者',
            'user' => '擁有基本權限的一般系統使用者',
            'guest' => '擁有有限唯讀權限的訪客使用者',
        ],

        // 統計資訊
        'stats' => [
            'total_roles' => '總角色數',
            'roles_with_users' => '已分配角色',
            'roles_with_permissions' => '有權限角色',
            'system_roles' => '系統角色',
        ],

        // 搜尋和篩選
        'search' => [
            'placeholder' => '搜尋角色名稱、顯示名稱或描述...',
        ],

        'filters' => [
            'toggle' => '篩選器',
            'reset' => '重置篩選',
            'permission_count' => '權限數量',
            'user_count' => '使用者數量',
            'role_type' => '角色類型',
            'status' => '狀態',
            'all_permissions' => '全部',
            'no_permissions' => '無權限',
            'low_permissions' => '少量 (:count)',
            'medium_permissions' => '中等 (:range)',
            'high_permissions' => '大量 (:count)',
            'all_users' => '全部',
            'no_users' => '無使用者',
            'low_users' => '少量 (:count)',
            'medium_users' => '中等 (:range)',
            'high_users' => '大量 (:count)',
            'all_roles' => '全部角色',
            'system_roles' => '系統角色',
            'custom_roles' => '自訂角色',
            'all_status' => '全部狀態',
            'active' => '啟用',
            'inactive' => '停用',
        ],

        // 排序
        'sort' => [
            'name' => '角色名稱',
            'display_name' => '顯示名稱',
            'created_at' => '建立時間',
            'updated_at' => '更新時間',
            'users_count' => '使用者數量',
            'permissions_count' => '權限數量',
        ],

        // 表格
        'table' => [
            'select' => '選擇',
            'select_all' => '全選',
            'name' => '角色名稱',
            'display_name' => '顯示名稱',
            'description' => '描述',
            'permissions_count' => '權限數',
            'users_count' => '使用者數',
            'created_at' => '建立時間',
            'actions' => '操作',
        ],

        // 標籤
        'labels' => [
            'system' => '系統',
            'inactive' => '停用',
        ],

        // 操作
        'actions' => [
            'create' => '建立角色',
            'create_first' => '建立第一個角色',
            'view' => '檢視',
            'edit' => '編輯',
            'duplicate' => '複製',
            'delete' => '刪除',
            'activate' => '啟用',
            'deactivate' => '停用',
            'confirm_delete' => '確認刪除',
            'force_delete' => '強制刪除',
            'deleting' => '刪除中...',
        ],

        // 批量操作
        'bulk_actions' => [
            'selected' => '已選擇 :count 個角色',
            'choose' => '選擇操作',
            'execute' => '執行',
            'activate' => '批量啟用',
            'deactivate' => '批量停用',
            'delete' => '批量刪除',
            'permissions' => '批量權限設定',
        ],

        // 批量權限設定
        'bulk_permissions' => [
            'title' => '批量權限設定',
            'description' => '為 :count 個角色批量設定權限',
            'selected_roles' => '選中的角色',
            'operation_type' => '操作類型',
            'module_filter' => '模組篩選',
            'all_modules' => '全部模組',
            'permissions' => '權限列表',
            'permissions_count' => '個權限',
            'no_permissions' => '沒有可用的權限',
            'select_all' => '全選',
            'clear_all' => '清除',
            'execute' => '執行操作',
            'retry' => '重試',
            'selected_count' => '已選擇 :selected / :total 個權限 (:percentage%)',
            
            'operations' => [
                'add' => '新增權限',
                'remove' => '移除權限',
                'replace' => '替換權限',
            ],
            
            'operation_descriptions' => [
                'add' => '將選中的權限新增到角色（保留現有權限）',
                'remove' => '從角色中移除選中的權限',
                'replace' => '用選中的權限完全替換角色的所有權限',
            ],
            
            'success' => [
                'added' => '成功為角色 ":name" 新增了 :count 個權限',
                'removed' => '成功從角色 ":name" 移除了 :count 個權限',
                'replaced' => '成功替換了角色 ":name" 的權限，共 :count 個',
            ],
            
            'errors' => [
                'title' => '操作錯誤',
                'no_permissions_selected' => '請至少選擇一個權限',
                'system_role_replace' => '無法替換系統角色 ":name" 的權限',
                'operation_failed' => '角色 ":name" 操作失敗：:error',
            ],
            
            'results' => [
                'title' => '操作結果',
            ],
        ],

        // 批量操作結果
        'bulk_results' => [
            'activate_title' => '批量啟用結果',
            'deactivate_title' => '批量停用結果',
            'delete_title' => '批量刪除結果',
            'permissions_add_title' => '批量新增權限結果',
            'permissions_remove_title' => '批量移除權限結果',
            'permissions_replace_title' => '批量替換權限結果',
            'default_title' => '批量操作結果',
            'completed_at' => '完成時間：:time',
            'total_processed' => '處理總數',
            'successful' => '成功',
            'failed' => '失敗',
            'success_rate' => '成功率',
            'successful_operations' => '成功操作 (:count)',
            'failed_operations' => '失敗操作 (:count)',
            'unknown_role' => '未知角色',
            'operation_completed' => '操作已完成',
            'operation_failed' => '操作失敗',
            'retry_failed' => '重試失敗項目',
            'export_csv' => '匯出 CSV',
            'csv' => [
                'role_name' => '角色名稱',
                'status' => '狀態',
                'message' => '訊息',
                'timestamp' => '時間戳記',
                'success' => '成功',
                'failed' => '失敗',
            ],
        ],

        // 狀態
        'status' => [
            'activated' => '已啟用',
            'deactivated' => '已停用',
        ],

        // 空狀態
        'empty' => [
            'title' => '尚無角色',
            'description' => '開始建立第一個角色來管理使用者權限',
        ],

        // 訊息
        'messages' => [
            'duplicated' => '角色 ":name" 已成功複製',
            'deleted' => '角色 ":name" 已成功刪除',
            'deleted_successfully' => '角色 ":name" 已成功刪除',
            'bulk_activated' => '已成功啟用 :count 個角色',
            'bulk_deactivated' => '已成功停用 :count 個角色',
            'bulk_deleted' => '已成功刪除 :count 個角色',
            'bulk_permissions_added' => '已成功為 :count 個角色新增權限',
            'bulk_permissions_removed' => '已成功從 :count 個角色移除權限',
            'bulk_permissions_replaced' => '已成功替換 :count 個角色的權限',
            'bulk_permissions_updated' => '已成功更新 :count 個角色的權限',
            'status_changed' => '角色 ":name" 狀態已變更為 :status',
        ],

        // 錯誤訊息
        'errors' => [
            'title' => '操作失敗',
            'no_action_selected' => '請選擇要執行的操作',
            'invalid_action' => '無效的操作',
            'cannot_delete_role' => '無法刪除角色 ":name"',
            'cannot_modify_system_role' => '無法修改系統角色',
            'unauthorized' => '權限不足',
            'role_not_found' => '找不到指定的角色',
            'cannot_delete_system_role' => '無法刪除系統角色',
            'blocking_issues_exist' => '存在阻塞性問題，無法刪除',
            'confirmation_mismatch' => '確認文字不正確',
            'force_delete_required' => '存在阻塞性問題時必須勾選強制刪除',
            'bulk_permissions_partial_failure' => '批量權限操作部分失敗：:success 個成功，:failed 個失敗',
        ],

        // 確認對話框
        'confirm' => [
            'title' => '確認操作',
            'message' => '請確認您要執行此操作',
            'delete' => '確定要刪除這個角色嗎？此操作無法復原。',
            'bulk_delete' => '確定要刪除選中的角色嗎？此操作無法復原。',
        ],

        // 刪除檢查
        'delete_checks' => [
            'system_role_error' => '系統角色無法刪除',
            'system_role_ok' => '非系統角色，可以刪除',
            'users_warning' => '此角色有 :count 個使用者，刪除後將移除其角色關聯',
            'users_ok' => '此角色沒有使用者關聯',
            'children_warning' => '此角色有 :count 個子角色，刪除後子角色將變為根角色',
            'children_ok' => '此角色沒有子角色',
            'permissions_info' => '此角色有 :count 個權限，刪除後將移除所有權限關聯',
            'permissions_ok' => '此角色沒有權限關聯',
        ],

        // 舊有翻譯保持相容性
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
        'permission_management' => '權限管理',
        'management_description' => '管理系統權限的細粒度控制，包含權限定義、分組、依賴關係管理和使用情況監控',
        'matrix' => '權限矩陣',
        'matrix_description' => '管理角色和權限的對應關係，支援批量操作和即時預覽',
        'name' => '權限名稱',
        'display_name' => '顯示名稱',
        'description' => '描述',
        'module' => '模組',
        'type' => '類型',
        'no_permissions' => '沒有找到權限',
        'search' => '搜尋權限',
        'search_placeholder' => '搜尋權限名稱、顯示名稱或描述...',
        'filter_by_module' => '模組篩選',
        'all_modules' => '全部模組',
        'clear_filters' => '清除篩選',
        'search_help' => '請嘗試調整搜尋條件或篩選設定',
        'no_permission_edit' => '您沒有權限編輯權限設定',
        'no_permission_delete' => '您沒有權限刪除權限',
        'permission_not_found' => '找不到指定的權限',
        'cannot_delete_permission' => '無法刪除此權限',
        'permission_deleted' => '權限「:name」已成功刪除',
        'delete_failed' => '刪除失敗',
        'confirmation_mismatch' => '輸入的權限名稱不正確',
        'create_permission' => '建立權限',
        'create' => '建立權限',
        'create_description' => '建立新的系統權限，定義功能模組的存取控制',
        'edit' => '編輯權限',
        'edit_description' => '修改權限資訊和配置',
        'no_description' => '無描述',
        'export' => '匯出',
        'import' => '匯入',
        'templates' => '權限模板',
        'dependencies' => '依賴關係',
        'test' => '權限測試',
        
        // 權限模組
        'modules' => [
            'users' => '使用者管理',
            'roles' => '角色管理',
            'permissions' => '權限管理',
            'dashboard' => '儀表板',
            'settings' => '系統設定',
            'reports' => '報表管理',
            'logs' => '日誌管理',
            'system' => '系統管理',
        ],
        
        // 權限類型
        'types' => [
            'view' => '檢視',
            'create' => '建立',
            'edit' => '編輯',
            'delete' => '刪除',
            'manage' => '管理',
            'export' => '匯出',
            'import' => '匯入',
            'test' => '測試',
        ],
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
        'view' => '檢視',
        'duplicate' => '複製',
        'activate' => '啟用',
        'deactivate' => '停用',
    ],

    // 通用訊息
    'common' => [
        'confirm' => '確認',
        'cancel' => '取消',
        'close' => '關閉',
        'loading' => '載入中...',
        'processing' => '處理中...',
        'success' => '成功',
        'error' => '錯誤',
        'warning' => '警告',
        'info' => '資訊',
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

    // 錯誤訊息
    'errors' => [
        'unauthorized' => '您沒有權限執行此操作',
        'not_found' => '找不到指定的資源',
        'validation_failed' => '資料驗證失敗',
        'operation_failed' => '操作執行失敗',
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