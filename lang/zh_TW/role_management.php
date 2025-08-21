<?php

return [

    /*
    |--------------------------------------------------------------------------
    | 角色管理語言檔案
    |--------------------------------------------------------------------------
    |
    | 以下語言行用於角色管理功能，包括角色 CRUD 操作、權限矩陣和層級管理。
    |
    */

    // 頁面標題和導航
    'title' => '角色管理',
    'subtitle' => '管理系統角色和權限分配',
    'breadcrumb' => '角色管理',

    // 角色列表頁面
    'list' => [
        'title' => '角色列表',
        'description' => '管理系統角色和權限設定',
        'create_button' => '建立角色',
        'create_first' => '建立第一個角色',
        'search_placeholder' => '搜尋角色名稱、顯示名稱或描述...',
        'no_results' => '沒有找到角色',
        'empty_state' => [
            'title' => '尚無角色',
            'description' => '開始建立第一個角色來管理使用者權限',
        ],
    ],

    // 角色表單（建立/編輯）
    'form' => [
        'create_title' => '建立角色',
        'edit_title' => '編輯角色',
        'basic_info' => '基本資訊',
        'hierarchy' => '角色層級',
        'permissions' => '權限設定',
        
        'fields' => [
            'name' => '角色名稱',
            'name_placeholder' => '輸入角色名稱（例如：admin、editor）',
            'name_help' => '角色的系統識別名稱，僅使用小寫字母和底線。',
            'display_name' => '顯示名稱',
            'display_name_placeholder' => '輸入顯示名稱',
            'display_name_help' => '在介面中顯示的使用者友善名稱。',
            'description' => '描述',
            'description_placeholder' => '輸入角色描述（選填）',
            'description_help' => '角色用途和職責的簡要說明。',
            'parent_role' => '父角色',
            'parent_role_placeholder' => '選擇父角色（選填）',
            'parent_role_help' => '子角色會繼承父角色的權限。',
            'no_parent' => '無父角色（根角色）',
        ],
        
        'actions' => [
            'save' => '儲存角色',
            'save_and_permissions' => '儲存並設定權限',
            'cancel' => '取消',
            'reset' => '重設表單',
        ],
    ],

    // 權限矩陣
    'permissions' => [
        'title' => '權限矩陣',
        'subtitle' => '使用視覺化矩陣介面管理角色權限',
        'role_permissions' => ':role 的權限',
        'module_filter' => '模組篩選',
        'all_modules' => '全部模組',
        'search_permissions' => '搜尋權限...',
        'select_all' => '全選',
        'clear_all' => '清除',
        'save_permissions' => '儲存權限',
        'inherited_from' => '繼承自 :parent',
        'direct_permission' => '直接權限',
        'dependency_required' => '依賴關係必需',
        
        'stats' => [
            'total_permissions' => '總權限數',
            'selected_permissions' => '已選權限',
            'inherited_permissions' => '繼承權限',
            'module_coverage' => '模組覆蓋率',
        ],
        
        'bulk_actions' => [
            'select_module' => '選擇模組',
            'clear_module' => '清除模組',
            'toggle_module' => '切換模組',
        ],
    ],

    // 角色層級
    'hierarchy' => [
        'title' => '角色層級',
        'subtitle' => '管理角色父子關係',
        'root_roles' => '根角色',
        'child_roles' => '子角色',
        'no_children' => '無子角色',
        'create_child' => '建立子角色',
        'move_role' => '移動角色',
        'circular_dependency' => '偵測到循環依賴',
        'inheritance_info' => '子角色會自動繼承父角色的所有權限。',
    ],

    // 角色刪除
    'delete' => [
        'title' => '刪除角色',
        'confirm_title' => '確認刪除角色',
        'warning' => '此操作無法復原',
        'type_name_to_confirm' => '輸入角色名稱 ":name" 以確認刪除',
        'name_placeholder' => '輸入角色名稱以確認',
        'force_delete' => '強制刪除（忽略警告）',
        'force_delete_help' => '勾選此項可刪除有使用者或子角色的角色',
        
        'checks' => [
            'system_role_error' => '系統角色無法刪除',
            'system_role_ok' => '非系統角色，可以刪除',
            'users_warning' => '此角色有 :count 個使用者，刪除後將移除其角色關聯。',
            'users_ok' => '此角色沒有使用者關聯',
            'children_warning' => '此角色有 :count 個子角色，刪除後將變為根角色。',
            'children_ok' => '此角色沒有子角色',
            'permissions_info' => '此角色有 :count 個權限，所有權限關聯將被移除。',
            'permissions_ok' => '此角色沒有權限關聯',
        ],
        
        'actions' => [
            'confirm_delete' => '確認刪除',
            'cancel' => '取消',
        ],
    ],

    // 批量操作
    'bulk' => [
        'title' => '批量操作',
        'selected_count' => '已選擇 :count 個角色',
        'select_action' => '選擇操作',
        'actions' => [
            'delete' => '刪除選中項目',
            'activate' => '啟用選中項目',
            'deactivate' => '停用選中項目',
            'assign_permissions' => '指派權限',
            'remove_permissions' => '移除權限',
            'replace_permissions' => '替換權限',
        ],
        
        'permissions' => [
            'title' => '批量權限指派',
            'description' => '為 :count 個選中角色指派權限',
            'operation_type' => '操作類型',
            'selected_roles' => '選中的角色',
            'available_permissions' => '可用權限',
            'execute' => '執行操作',
            
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
        ],
        
        'results' => [
            'title' => '操作結果',
            'completed_at' => '完成時間：:time',
            'total_processed' => '處理總數',
            'successful' => '成功',
            'failed' => '失敗',
            'success_rate' => '成功率',
            'retry_failed' => '重試失敗項目',
            'export_csv' => '匯出 CSV',
        ],
    ],

    // 表格欄位
    'table' => [
        'select' => '選擇',
        'select_all' => '全選',
        'name' => '角色名稱',
        'display_name' => '顯示名稱',
        'description' => '描述',
        'permissions_count' => '權限數',
        'users_count' => '使用者數',
        'parent_role' => '父角色',
        'created_at' => '建立時間',
        'updated_at' => '更新時間',
        'actions' => '操作',
        'status' => '狀態',
    ],

    // 篩選和排序
    'filters' => [
        'title' => '篩選器',
        'reset' => '重設篩選',
        'permission_count' => '權限數量',
        'user_count' => '使用者數量',
        'role_type' => '角色類型',
        'status' => '狀態',
        'parent_role' => '父角色',
        
        'options' => [
            'all' => '全部',
            'none' => '無',
            'system_roles' => '系統角色',
            'custom_roles' => '自訂角色',
            'root_roles' => '根角色',
            'child_roles' => '子角色',
            'active' => '啟用',
            'inactive' => '停用',
            'low' => '少量（:count）',
            'medium' => '中等（:range）',
            'high' => '大量（:count+）',
        ],
    ],

    'sort' => [
        'name' => '角色名稱',
        'display_name' => '顯示名稱',
        'created_at' => '建立日期',
        'updated_at' => '更新日期',
        'users_count' => '使用者數量',
        'permissions_count' => '權限數量',
    ],

    // 操作
    'actions' => [
        'view' => '檢視',
        'edit' => '編輯',
        'duplicate' => '複製',
        'delete' => '刪除',
        'activate' => '啟用',
        'deactivate' => '停用',
        'manage_permissions' => '管理權限',
        'view_hierarchy' => '檢視層級',
        'create_child' => '建立子角色',
    ],

    // 狀態標籤
    'status' => [
        'active' => '啟用',
        'inactive' => '停用',
        'system' => '系統',
        'custom' => '自訂',
        'root' => '根',
        'child' => '子',
    ],

    // 統計資訊
    'stats' => [
        'total_roles' => '總角色數',
        'active_roles' => '啟用角色',
        'inactive_roles' => '停用角色',
        'system_roles' => '系統角色',
        'custom_roles' => '自訂角色',
        'roles_with_users' => '有使用者的角色',
        'roles_with_permissions' => '有權限的角色',
        'total_assigned_users' => '已分配使用者總數',
        'average_permissions_per_role' => '每角色平均權限數',
    ],

    // 成功訊息
    'messages' => [
        'created' => '角色 ":name" 已成功建立',
        'updated' => '角色 ":name" 已成功更新',
        'deleted' => '角色 ":name" 已成功刪除',
        'duplicated' => '角色 ":name" 已成功複製',
        'activated' => '角色 ":name" 已啟用',
        'deactivated' => '角色 ":name" 已停用',
        'permissions_updated' => '角色 ":name" 的權限已更新',
        'hierarchy_updated' => '角色層級已更新',
        'bulk_operation_completed' => '批量操作已成功完成',
        'bulk_permissions_updated' => '已為 :count 個角色更新權限',
    ],

    // 錯誤訊息
    'errors' => [
        'not_found' => '找不到角色',
        'creation_failed' => '建立角色失敗',
        'update_failed' => '更新角色失敗',
        'deletion_failed' => '刪除角色失敗',
        'duplicate_name' => '角色名稱已存在',
        'invalid_parent' => '選擇的父角色無效',
        'circular_dependency' => '無法在角色層級中建立循環依賴',
        'system_role_modification' => '系統角色無法修改',
        'system_role_deletion' => '系統角色無法刪除',
        'role_has_users' => '無法刪除有指派使用者的角色',
        'role_has_children' => '無法刪除有子角色的角色',
        'permission_assignment_failed' => '權限指派失敗',
        'unauthorized' => '您沒有權限執行此操作',
        'validation_failed' => '驗證失敗',
        'bulk_operation_failed' => '批量操作失敗',
        'confirmation_mismatch' => '確認文字不符',
        'force_delete_required' => '有依賴關係的角色必須啟用強制刪除',
    ],

    // 驗證訊息
    'validation' => [
        'name_required' => '角色名稱為必填',
        'name_unique' => '角色名稱必須唯一',
        'name_format' => '角色名稱只能包含小寫字母、數字和底線',
        'name_min' => '角色名稱至少需要 :min 個字元',
        'name_max' => '角色名稱不能超過 :max 個字元',
        'display_name_required' => '顯示名稱為必填',
        'display_name_max' => '顯示名稱不能超過 :max 個字元',
        'description_max' => '描述不能超過 :max 個字元',
        'parent_exists' => '選擇的父角色不存在',
        'parent_not_self' => '角色不能是自己的父角色',
        'parent_no_circular' => '父角色選擇會造成循環依賴',
    ],

    // 權限名稱本地化
    'permission_names' => [
        // 角色管理權限
        'roles.view' => '檢視角色',
        'roles.create' => '建立角色',
        'roles.edit' => '編輯角色',
        'roles.delete' => '刪除角色',
        'roles.manage_permissions' => '管理角色權限',
        
        // 使用者管理權限
        'users.view' => '檢視使用者',
        'users.create' => '建立使用者',
        'users.edit' => '編輯使用者',
        'users.delete' => '刪除使用者',
        'users.assign_roles' => '指派使用者角色',
        
        // 權限管理權限
        'permissions.view' => '檢視權限',
        'permissions.create' => '建立權限',
        'permissions.edit' => '編輯權限',
        'permissions.delete' => '刪除權限',
        
        // 儀表板權限
        'dashboard.view' => '檢視儀表板',
        'dashboard.stats' => '檢視統計資訊',
        
        // 系統權限
        'system.settings' => '系統設定',
        'system.logs' => '檢視系統日誌',
        'system.maintenance' => '系統維護',
        
        // 個人資料權限
        'profile.view' => '檢視個人資料',
        'profile.edit' => '編輯個人資料',
    ],

    // 權限描述本地化
    'permission_descriptions' => [
        // 角色管理權限
        'roles.view' => '可以檢視角色列表和詳細資訊',
        'roles.create' => '可以建立新的角色',
        'roles.edit' => '可以編輯角色資訊和設定',
        'roles.delete' => '可以刪除角色',
        'roles.manage_permissions' => '可以為角色指派或移除權限',
        
        // 使用者管理權限
        'users.view' => '可以檢視使用者列表和詳細資訊',
        'users.create' => '可以建立新的使用者帳號',
        'users.edit' => '可以編輯使用者資訊和設定',
        'users.delete' => '可以刪除使用者帳號',
        'users.assign_roles' => '可以為使用者指派或移除角色',
        
        // 權限管理權限
        'permissions.view' => '可以檢視權限列表和詳細資訊',
        'permissions.create' => '可以建立新的權限',
        'permissions.edit' => '可以編輯權限資訊',
        'permissions.delete' => '可以刪除權限',
        
        // 儀表板權限
        'dashboard.view' => '可以存取管理後台儀表板',
        'dashboard.stats' => '可以檢視系統統計資訊',
        
        // 系統權限
        'system.settings' => '可以修改系統設定',
        'system.logs' => '可以檢視系統日誌和錯誤記錄',
        'system.maintenance' => '可以執行系統維護操作',
        
        // 個人資料權限
        'profile.view' => '可以檢視自己的個人資料',
        'profile.edit' => '可以編輯自己的個人資料',
    ],

    // 模組名稱本地化
    'modules' => [
        'roles' => '角色管理',
        'users' => '使用者管理',
        'permissions' => '權限管理',
        'dashboard' => '儀表板',
        'system' => '系統管理',
        'profile' => '個人資料管理',
    ],

    // 角色名稱本地化（系統角色）
    'role_names' => [
        'super_admin' => '超級管理員',
        'admin' => '管理員',
        'moderator' => '版主',
        'editor' => '編輯者',
        'user' => '一般使用者',
        'guest' => '訪客',
    ],

    // 角色描述本地化（系統角色）
    'role_descriptions' => [
        'super_admin' => '擁有系統所有權限的最高管理員',
        'admin' => '擁有大部分管理權限的系統管理員',
        'moderator' => '擁有內容管理權限的版主',
        'editor' => '擁有發布權限的內容編輯者',
        'user' => '擁有基本權限的一般系統使用者',
        'guest' => '擁有有限唯讀權限的訪客使用者',
    ],

];