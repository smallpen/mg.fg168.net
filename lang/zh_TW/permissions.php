<?php

return [

    /*
    |--------------------------------------------------------------------------
    | 權限管理語言行
    |--------------------------------------------------------------------------
    |
    | 以下語言行用於權限管理介面、訊息和標籤。
    |
    */

    // 頁面標題和標頭
    'titles' => [
        'permission_management' => '權限管理',
        'permission_list' => '權限列表',
        'create_permission' => '建立權限',
        'edit_permission' => '編輯權限',
        'permission_details' => '權限詳情',
        'dependency_graph' => '依賴關係圖',
        'permission_test' => '權限測試',
        'usage_analysis' => '使用情況分析',
        'audit_log' => '審計日誌',
        'import_export' => '匯入/匯出',
        'templates' => '權限模板',
    ],

    // 導航和選單項目
    'navigation' => [
        'permissions' => '權限',
        'list' => '列表',
        'create' => '建立',
        'dependencies' => '依賴關係',
        'templates' => '模板',
        'test_tool' => '測試工具',
        'usage_stats' => '使用統計',
        'audit' => '審計',
        'settings' => '設定',
    ],

    // 表單標籤和佔位符
    'form' => [
        'name' => '權限名稱',
        'name_placeholder' => '例如：users.create',
        'display_name' => '顯示名稱',
        'display_name_placeholder' => '例如：建立使用者',
        'description' => '描述',
        'description_placeholder' => '此權限的簡要描述',
        'module' => '模組',
        'module_placeholder' => '選擇模組',
        'type' => '權限類型',
        'type_placeholder' => '選擇類型',
        'dependencies' => '依賴權限',
        'dependencies_placeholder' => '選擇依賴的權限',
        'is_system' => '系統權限',
        'is_active' => '啟用',
    ],

    // 權限類型
    'types' => [
        'view' => '檢視',
        'create' => '建立',
        'edit' => '編輯',
        'delete' => '刪除',
        'manage' => '管理',
        'admin' => '管理員',
        'system' => '系統',
    ],

    // 模組（可根據您的應用程式擴展）
    'modules' => [
        'users' => '使用者',
        'roles' => '角色',
        'permissions' => '權限',
        'dashboard' => '儀表板',
        'settings' => '設定',
        'reports' => '報告',
        'audit' => '審計',
        'system' => '系統',
    ],

    // 表格標頭
    'table' => [
        'name' => '名稱',
        'display_name' => '顯示名稱',
        'description' => '描述',
        'module' => '模組',
        'type' => '類型',
        'roles_count' => '角色數',
        'users_count' => '使用者數',
        'dependencies_count' => '依賴數',
        'dependents_count' => '被依賴數',
        'created_at' => '建立時間',
        'updated_at' => '更新時間',
        'status' => '狀態',
        'actions' => '操作',
    ],

    // 狀態標籤
    'status' => [
        'active' => '啟用',
        'inactive' => '停用',
        'system' => '系統',
        'used' => '已使用',
        'unused' => '未使用',
        'deprecated' => '已棄用',
    ],

    // 操作按鈕和連結
    'actions' => [
        'create' => '建立權限',
        'edit' => '編輯',
        'delete' => '刪除',
        'view' => '檢視',
        'duplicate' => '複製',
        'activate' => '啟用',
        'deactivate' => '停用',
        'manage_dependencies' => '管理依賴關係',
        'view_usage' => '檢視使用情況',
        'test_permission' => '測試權限',
        'export' => '匯出',
        'import' => '匯入',
        'save' => '儲存',
        'cancel' => '取消',
        'back' => '返回',
        'refresh' => '重新整理',
        'clear' => '清除',
        'apply' => '套用',
        'reset' => '重設',
    ],

    // 搜尋和篩選
    'search' => [
        'search_placeholder' => '搜尋權限...',
        'filter_by_module' => '按模組篩選',
        'filter_by_type' => '按類型篩選',
        'filter_by_status' => '按狀態篩選',
        'filter_by_usage' => '按使用情況篩選',
        'all_modules' => '所有模組',
        'all_types' => '所有類型',
        'all_statuses' => '所有狀態',
        'show_system' => '顯示系統權限',
        'show_unused' => '僅顯示未使用',
        'advanced_search' => '進階搜尋',
    ],

    // 檢視模式
    'view_modes' => [
        'list' => '列表檢視',
        'grid' => '網格檢視',
        'tree' => '樹狀檢視',
        'grouped' => '分組檢視',
    ],

    // 批量操作
    'bulk' => [
        'select_all' => '全選',
        'deselect_all' => '取消全選',
        'selected_count' => '已選擇 :count 項',
        'bulk_actions' => '批量操作',
        'bulk_delete' => '刪除選中項',
        'bulk_activate' => '啟用選中項',
        'bulk_deactivate' => '停用選中項',
        'bulk_export' => '匯出選中項',
        'confirm_bulk_delete' => '您確定要刪除 :count 個權限嗎？',
    ],

    // 訊息和通知
    'messages' => [
        'created_successfully' => '權限建立成功。',
        'updated_successfully' => '權限更新成功。',
        'deleted_successfully' => '權限刪除成功。',
        'activated_successfully' => '權限啟用成功。',
        'deactivated_successfully' => '權限停用成功。',
        'duplicated_successfully' => '權限複製成功。',
        'exported_successfully' => '權限匯出成功。',
        'imported_successfully' => '權限匯入成功。',
        'no_permissions_found' => '找不到權限。',
        'loading' => '載入權限中...',
        'processing' => '處理中...',
        'operation_completed' => '操作成功完成。',
        'bulk_operation_completed' => '批量操作完成。成功 :success 項，失敗 :failed 項。',
    ],

    // 確認對話框
    'confirmations' => [
        'delete_title' => '刪除權限',
        'delete_message' => '您確定要刪除此權限嗎？',
        'delete_warning' => '此操作無法復原。',
        'delete_system_warning' => '這是系統權限，無法刪除。',
        'delete_used_warning' => '此權限被 :count 個角色使用，無法刪除。',
        'force_delete' => '強制刪除',
        'type_permission_name' => '輸入權限名稱以確認：',
        'confirm' => '確認',
        'cancel' => '取消',
    ],

    // 依賴關係管理
    'dependencies' => [
        'title' => '權限依賴關係',
        'description' => '管理權限依賴關係',
        'add_dependency' => '新增依賴',
        'remove_dependency' => '移除依賴',
        'no_dependencies' => '未配置依賴關係',
        'circular_dependency_warning' => '偵測到循環依賴',
        'dependency_chain' => '依賴鏈',
        'depends_on' => '依賴於',
        'required_by' => '被需要於',
        'auto_assign' => '自動指派依賴權限',
        'dependency_graph' => '依賴關係圖',
        'view_graph' => '檢視圖表',
    ],

    // 使用情況分析
    'usage' => [
        'title' => '權限使用情況分析',
        'total_permissions' => '總權限數',
        'used_permissions' => '已使用權限',
        'unused_permissions' => '未使用權限',
        'system_permissions' => '系統權限',
        'usage_frequency' => '使用頻率',
        'last_used' => '最後使用',
        'never_used' => '從未使用',
        'most_used' => '最常使用的權限',
        'least_used' => '最少使用的權限',
        'usage_by_module' => '按模組使用情況',
        'usage_trend' => '使用趨勢',
        'roles_using' => '使用此權限的角色',
        'users_affected' => '受影響的使用者',
    ],

    // 權限測試
    'testing' => [
        'title' => '權限測試工具',
        'description' => '測試權限指派和存取控制',
        'test_user_permission' => '測試使用者權限',
        'test_role_permission' => '測試角色權限',
        'select_user' => '選擇使用者',
        'select_role' => '選擇角色',
        'select_permission' => '選擇權限',
        'test_result' => '測試結果',
        'has_permission' => '擁有權限',
        'no_permission' => '無權限',
        'permission_path' => '權限路徑',
        'direct_assignment' => '直接指派',
        'inherited_from_role' => '從角色繼承',
        'dependency_chain' => '依賴鏈',
        'run_test' => '執行測試',
        'clear_results' => '清除結果',
        'batch_test' => '批量測試',
        'export_results' => '匯出結果',
    ],

    // 匯入/匯出
    'import_export' => [
        'export_title' => '匯出權限',
        'import_title' => '匯入權限',
        'export_description' => '將權限匯出為 JSON 格式',
        'import_description' => '從 JSON 檔案匯入權限',
        'select_file' => '選擇檔案',
        'file_format' => '檔案格式',
        'include_dependencies' => '包含依賴關係',
        'include_system_permissions' => '包含系統權限',
        'conflict_resolution' => '衝突解決',
        'skip_conflicts' => '跳過衝突',
        'overwrite_existing' => '覆寫現有',
        'merge_permissions' => '合併權限',
        'preview_import' => '預覽匯入',
        'import_summary' => '匯入摘要',
        'permissions_to_create' => '要建立的權限',
        'permissions_to_update' => '要更新的權限',
        'conflicts_detected' => '偵測到衝突',
        'proceed_import' => '繼續匯入',
    ],

    // 模板
    'templates' => [
        'title' => '權限模板',
        'description' => '管理權限模板以快速設定',
        'create_template' => '建立模板',
        'apply_template' => '套用模板',
        'template_name' => '模板名稱',
        'template_description' => '模板描述',
        'template_permissions' => '模板權限',
        'available_templates' => '可用模板',
        'custom_templates' => '自定義模板',
        'system_templates' => '系統模板',
        'template_applied' => '模板套用成功',
        'permissions_created' => '從模板建立了 :count 個權限',
    ],

    // 審計日誌
    'audit' => [
        'title' => '權限審計日誌',
        'description' => '追蹤所有權限相關變更',
        'event_type' => '事件類型',
        'permission_name' => '權限',
        'user' => '使用者',
        'timestamp' => '時間戳',
        'ip_address' => 'IP 位址',
        'user_agent' => '使用者代理',
        'changes' => '變更',
        'old_value' => '舊值',
        'new_value' => '新值',
        'event_types' => [
            'created' => '已建立',
            'updated' => '已更新',
            'deleted' => '已刪除',
            'activated' => '已啟用',
            'deactivated' => '已停用',
            'dependency_added' => '已新增依賴',
            'dependency_removed' => '已移除依賴',
        ],
        'filter_by_event' => '按事件篩選',
        'filter_by_user' => '按使用者篩選',
        'filter_by_date' => '按日期篩選',
        'export_log' => '匯出日誌',
        'clear_old_logs' => '清除舊日誌',
    ],

    // 驗證訊息
    'validation' => [
        'name_format' => '權限名稱必須遵循格式：模組.動作',
        'name_unique' => '此權限名稱已存在',
        'display_name_required' => '顯示名稱為必填',
        'module_required' => '必須選擇模組',
        'type_required' => '必須選擇權限類型',
        'circular_dependency' => '在權限鏈中偵測到循環依賴',
        'invalid_dependency' => '無效的依賴選擇',
        'system_permission_restriction' => '系統權限的修改有限制',
    ],

    // 說明和工具提示
    'help' => [
        'permission_name' => '使用格式：模組.動作（例如：users.create、posts.edit）',
        'display_name' => '在介面中顯示的人類可讀名稱',
        'description' => '此權限允許什麼的簡要描述',
        'module' => '相關權限的邏輯分組',
        'type' => '此權限控制的操作類別',
        'dependencies' => '在授予此權限之前需要的其他權限',
        'system_permission' => '系統運作所需的核心權限',
        'dependency_graph' => '權限關係的視覺化表示',
        'usage_analysis' => '關於權限在系統中如何使用的統計',
        'permission_test' => '驗證權限指派是否正確工作的工具',
    ],

    // 分頁
    'pagination' => [
        'showing' => '顯示第 :from 到 :to 項，共 :total 個權限',
        'per_page' => '每頁',
        'go_to_page' => '前往頁面',
        'first' => '第一頁',
        'last' => '最後一頁',
        'previous' => '上一頁',
        'next' => '下一頁',
    ],

    // 空狀態
    'empty' => [
        'no_permissions' => '找不到權限',
        'no_search_results' => '沒有權限符合您的搜尋條件',
        'no_dependencies' => '未配置依賴關係',
        'no_usage_data' => '無可用的使用資料',
        'no_audit_logs' => '找不到審計日誌',
        'no_templates' => '無可用模板',
        'create_first_permission' => '建立您的第一個權限',
        'adjust_filters' => '嘗試調整您的搜尋篩選器',
    ],

];