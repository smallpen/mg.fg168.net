<?php

return [

    /*
    |--------------------------------------------------------------------------
    | 權限 UI 語言行
    |--------------------------------------------------------------------------
    |
    | 以下語言行用於權限管理使用者介面元件、按鈕和互動元素。
    |
    */

    // 元件標題
    'components' => [
        'permission_list' => '權限列表',
        'permission_form' => '權限表單',
        'dependency_graph' => '依賴關係圖',
        'permission_test' => '權限測試工具',
        'usage_analysis' => '使用情況分析',
        'audit_viewer' => '審計日誌檢視器',
        'import_wizard' => '匯入精靈',
        'export_dialog' => '匯出對話框',
        'template_manager' => '模板管理器',
        'bulk_operations' => '批量操作',
    ],

    // 表單元素
    'form_elements' => [
        'search_input' => '搜尋權限...',
        'module_select' => '選擇模組',
        'type_select' => '選擇類型',
        'status_select' => '選擇狀態',
        'dependency_multiselect' => '選擇依賴關係',
        'template_select' => '選擇模板',
        'user_select' => '選擇使用者',
        'role_select' => '選擇角色',
        'date_picker' => '選擇日期',
        'file_upload' => '選擇檔案',
    ],

    // 按鈕和操作
    'buttons' => [
        'create_new' => '建立新權限',
        'edit_permission' => '編輯權限',
        'delete_permission' => '刪除權限',
        'duplicate_permission' => '複製權限',
        'view_details' => '檢視詳情',
        'manage_dependencies' => '管理依賴關係',
        'test_permission' => '測試權限',
        'export_permissions' => '匯出權限',
        'import_permissions' => '匯入權限',
        'apply_template' => '套用模板',
        'save_template' => '儲存為模板',
        'run_analysis' => '執行分析',
        'clear_cache' => '清除快取',
        'refresh_data' => '重新整理資料',
        'download_report' => '下載報告',
        'view_audit_log' => '檢視審計日誌',
        'bulk_select' => '批量選擇',
        'select_all' => '全選',
        'deselect_all' => '取消全選',
        'apply_filters' => '套用篩選器',
        'clear_filters' => '清除篩選器',
        'advanced_search' => '進階搜尋',
        'save_search' => '儲存搜尋',
        'load_search' => '載入搜尋',
    ],

    // 狀態指示器
    'status_indicators' => [
        'active' => '啟用',
        'inactive' => '停用',
        'system' => '系統',
        'deprecated' => '已棄用',
        'loading' => '載入中...',
        'processing' => '處理中...',
        'completed' => '已完成',
        'failed' => '失敗',
        'pending' => '待處理',
        'in_use' => '使用中',
        'unused' => '未使用',
        'has_dependencies' => '有依賴關係',
        'no_dependencies' => '無依賴關係',
    ],

    // 模態對話框
    'modals' => [
        'confirm_delete' => '確認刪除',
        'permission_details' => '權限詳情',
        'dependency_manager' => '依賴關係管理器',
        'test_results' => '測試結果',
        'import_preview' => '匯入預覽',
        'export_options' => '匯出選項',
        'template_selector' => '模板選擇器',
        'bulk_actions' => '批量操作',
        'error_details' => '錯誤詳情',
        'success_notification' => '成功',
    ],

    // 標籤頁和導航
    'tabs' => [
        'overview' => '概覽',
        'details' => '詳情',
        'dependencies' => '依賴關係',
        'usage' => '使用情況',
        'audit' => '審計',
        'settings' => '設定',
        'advanced' => '進階',
        'import' => '匯入',
        'export' => '匯出',
        'templates' => '模板',
        'test' => '測試',
        'reports' => '報告',
    ],

    // 資料表格元素
    'table' => [
        'no_data' => '無可用資料',
        'loading_data' => '載入資料中...',
        'search_results' => '搜尋結果',
        'filtered_results' => '篩選結果',
        'showing_entries' => '顯示第 :start 到 :end 項，共 :total 項',
        'no_matching_records' => '找不到符合的記錄',
        'sort_ascending' => '升序排列',
        'sort_descending' => '降序排列',
        'column_visibility' => '欄位可見性',
        'export_table' => '匯出表格',
        'print_table' => '列印表格',
        'refresh_table' => '重新整理表格',
    ],

    // 篩選器和搜尋
    'filters' => [
        'all_modules' => '所有模組',
        'all_types' => '所有類型',
        'all_statuses' => '所有狀態',
        'active_only' => '僅啟用',
        'inactive_only' => '僅停用',
        'system_only' => '僅系統',
        'used_only' => '僅已使用',
        'unused_only' => '僅未使用',
        'with_dependencies' => '有依賴關係',
        'without_dependencies' => '無依賴關係',
        'created_today' => '今日建立',
        'created_this_week' => '本週建立',
        'created_this_month' => '本月建立',
        'modified_recently' => '最近修改',
    ],

    // 工具提示和說明文字
    'tooltips' => [
        'permission_name_help' => '使用格式：模組.動作（例如：users.create）',
        'display_name_help' => '此權限的人類可讀名稱',
        'description_help' => '此權限允許什麼的簡要描述',
        'module_help' => '相關權限的邏輯分組',
        'type_help' => '此權限控制的操作類別',
        'dependencies_help' => '此權限之前需要的其他權限',
        'system_permission_help' => '系統運作所需的核心權限',
        'active_status_help' => '此權限目前是否啟用',
        'usage_count_help' => '使用此權限的角色數量',
        'dependency_count_help' => '此權限依賴的權限數量',
        'dependent_count_help' => '依賴此權限的權限數量',
        'last_used_help' => '此權限最後存取時間',
        'created_date_help' => '此權限建立時間',
        'modified_date_help' => '此權限最後修改時間',
    ],

    // 進度指示器
    'progress' => [
        'initializing' => '初始化中...',
        'loading_permissions' => '載入權限中...',
        'processing_request' => '處理請求中...',
        'saving_changes' => '儲存變更中...',
        'deleting_permission' => '刪除權限中...',
        'updating_dependencies' => '更新依賴關係中...',
        'running_analysis' => '執行分析中...',
        'generating_report' => '生成報告中...',
        'importing_data' => '匯入資料中...',
        'exporting_data' => '匯出資料中...',
        'validating_data' => '驗證資料中...',
        'completing_operation' => '完成操作中...',
    ],

    // 麵包屑導航
    'breadcrumbs' => [
        'home' => '首頁',
        'admin' => '管理',
        'permissions' => '權限',
        'create' => '建立',
        'edit' => '編輯',
        'view' => '檢視',
        'dependencies' => '依賴關係',
        'test' => '測試',
        'usage' => '使用情況',
        'audit' => '審計',
        'templates' => '模板',
        'import' => '匯入',
        'export' => '匯出',
        'settings' => '設定',
    ],

    // 鍵盤快捷鍵
    'shortcuts' => [
        'create_new' => 'Ctrl+N',
        'save' => 'Ctrl+S',
        'search' => 'Ctrl+F',
        'refresh' => 'F5',
        'delete' => 'Delete',
        'edit' => 'Enter',
        'cancel' => 'Escape',
        'select_all' => 'Ctrl+A',
        'copy' => 'Ctrl+C',
        'paste' => 'Ctrl+V',
    ],

    // 無障礙標籤
    'accessibility' => [
        'main_content' => '主要內容',
        'navigation_menu' => '導航選單',
        'search_form' => '搜尋表單',
        'data_table' => '權限資料表格',
        'action_buttons' => '操作按鈕',
        'filter_controls' => '篩選控制項',
        'pagination_controls' => '分頁控制項',
        'modal_dialog' => '模態對話框',
        'close_button' => '關閉',
        'expand_button' => '展開',
        'collapse_button' => '收合',
        'sort_button' => '排序',
        'filter_button' => '篩選',
        'menu_button' => '選單',
    ],

];