<?php

return [
    // 基本標籤
    'permissions' => '權限',
    'permission' => '權限',
    'permission_management' => '權限管理',
    'permission_name' => '權限名稱',
    'display_name' => '顯示名稱',
    'description' => '描述',
    'module' => '模組',
    'type' => '類型',
    'usage_status' => '使用狀態',
    'created_at' => '建立時間',
    'actions' => '操作',

    // 統計資訊
    'total_permissions' => '總權限數',
    'used_permissions' => '已使用權限',
    'unused_permissions' => '未使用權限',
    'usage_percentage' => '使用率',

    // 搜尋和篩選
    'search' => '搜尋',
    'search_placeholder' => '搜尋權限名稱、顯示名稱或描述...',
    'filter_by_module' => '按模組篩選',
    'filter_by_type' => '按類型篩選',
    'filter_by_usage' => '按使用狀態篩選',
    'all_modules' => '所有模組',
    'all_types' => '所有類型',
    'all_usage' => '所有狀態',
    'used' => '已使用',
    'unused' => '未使用',
    'active' => '啟用',
    'inactive' => '停用',
    'clear_filters' => '清除篩選',

    // 檢視模式
    'view_mode' => '檢視模式',
    'view_list' => '列表檢視',
    'view_grouped' => '分組檢視',
    'view_tree' => '樹狀檢視',

    // 權限類型
    'type_view' => '檢視',
    'type_create' => '建立',
    'type_edit' => '編輯',
    'type_delete' => '刪除',
    'type_manage' => '管理',

    // 操作
    'create_permission' => '建立權限',
    'create_first_permission' => '建立第一個權限',
    'edit' => '編輯',
    'delete' => '刪除',
    'export' => '匯出',
    'import' => '匯入',
    'more_actions' => '更多操作',
    'view_dependencies' => '檢視依賴關係',
    'view_usage' => '檢視使用情況',
    'duplicate' => '複製',

    // 批量操作
    'selected_permissions' => '已選擇 :count 個權限',
    'cancel_selection' => '取消選擇',

    // 分組檢視
    'expand_all' => '展開全部',
    'collapse_all' => '收合全部',
    'modules' => '模組',
    'types' => '類型',
    'total' => '總計',
    'in' => '在',

    // 權限詳細資訊
    'roles' => '角色',
    'roles_count' => '角色數量',
    'dependencies' => '依賴關係',
    'system_permission' => '系統權限',
    'has_dependencies' => '有依賴關係',
    'view_details' => '檢視詳情',

    // 狀態和訊息
    'loading' => '載入中...',
    'no_permissions_found' => '找不到權限',
    'no_permissions_description' => '目前沒有符合條件的權限，您可以建立新的權限。',

    // 權限檢查
    'no_permission_create' => '您沒有建立權限的權限',
    'no_permission_edit' => '您沒有編輯權限的權限',
    'no_permission_delete' => '您沒有刪除權限的權限',

    // 錯誤訊息
    'permission_not_found' => '找不到指定的權限',
    'cannot_delete_permission' => '無法刪除此權限',
    'delete_failed' => '刪除失敗',
    'permission_deleted' => '權限 :name 已成功刪除',

    // 成功訊息
    'permission_created' => '權限建立成功',
    'permission_updated' => '權限更新成功',

    // 額外的檢視條目
    'management_description' => '管理系統權限的細粒度控制，包含權限定義、分組、依賴關係管理和使用情況監控',
    'matrix' => '權限矩陣',
    'templates' => '權限模板',

    // 模組名稱
    'modules' => [
        'users' => '使用者管理',
        'roles' => '角色管理',
        'permissions' => '權限管理',
        'dashboard' => '儀表板',
        'system' => '系統管理',
        'reports' => '報表管理',
        'settings' => '設定管理',
        'audit' => '審計管理',
        'monitoring' => '監控管理',
        'security' => '安全管理',
    ],

    // 匯入匯出功能
    'import_export' => [
        // 匯出相關
        'export_title' => '匯出權限',
        'export_description' => '將權限資料匯出為 JSON 格式檔案',
        'export_filters' => '匯出篩選條件',
        'select_modules' => '選擇模組',
        'select_types' => '選擇權限類型',
        'select_usage_status' => '選擇使用狀態',
        'export_all' => '匯出全部權限',
        'export_selected' => '匯出選中權限',
        'export_filtered' => '匯出篩選結果',
        'reset_filters' => '重置篩選',
        'export_button' => '匯出權限',
        'exporting' => '匯出中...',
        'export_success' => '成功匯出 :count 個權限',
        'export_failed' => '匯出失敗：:error',

        // 匯入相關
        'import_title' => '匯入權限',
        'import_description' => '從 JSON 格式檔案匯入權限資料',
        'select_file' => '選擇匯入檔案 (JSON 格式)',
        'file_requirements' => '檔案要求：JSON 格式，最大 10MB',
        'import_options' => '匯入選項',
        'conflict_resolution' => '衝突處理策略',
        'conflict_skip' => '跳過（保留現有）',
        'conflict_update' => '更新（覆蓋現有）',
        'conflict_merge' => '合併（智慧合併）',
        'validate_dependencies' => '驗證依賴關係',
        'create_missing_dependencies' => '建立缺失的依賴',
        'dry_run' => '試運行（不實際執行）',
        'import_button' => '開始匯入',
        'importing' => '匯入中...',

        // 預覽相關
        'preview_title' => '匯入預覽',
        'preview_summary' => '匯入摘要',
        'will_create' => '將建立',
        'will_update' => '將更新',
        'will_skip' => '將跳過',
        'total_permissions' => '總權限數',
        'conflicts_found' => '發現 :count 個衝突',
        'show_conflicts' => '顯示衝突詳情',
        'hide_conflicts' => '隱藏衝突詳情',
        'bulk_resolution' => '批量設定',
        'conflict_details' => '衝突詳情',
        'differences' => '差異',
        'existing_value' => '現有值',
        'import_value' => '匯入值',
        'confirm_import' => '確認匯入',
        'cancel_import' => '取消匯入',

        // 結果相關
        'import_results' => '匯入結果',
        'import_summary' => '匯入摘要',
        'total_processed' => '總處理',
        'created' => '已建立',
        'updated' => '已更新',
        'skipped' => '已跳過',
        'errors' => '錯誤',
        'warnings' => '警告',
        'recommendations' => '建議',
        'detailed_errors' => '詳細錯誤',
        'close_results' => '關閉結果',

        // 錯誤和警告
        'file_format_error' => 'JSON 檔案格式錯誤',
        'file_too_large' => '檔案過大，請選擇小於 10MB 的檔案',
        'invalid_file_type' => '無效的檔案類型，請選擇 JSON 檔案',
        'version_incompatible' => '不支援的匯入檔案版本',
        'validation_failed' => '資料驗證失敗',
        'permission_name_duplicate' => '匯入資料中存在重複的權限名稱',
        'permission_name_invalid' => '權限名稱格式不正確',
        'required_field_missing' => '必填欄位不能為空',
        'dependency_not_found' => '依賴權限不存在',
        'circular_dependency' => '檢測到循環依賴',
        'import_general_error' => '匯入過程發生錯誤',

        // 建議訊息
        'recommendation_conflicts' => '發現權限名稱衝突，建議檢查衝突處理策略',
        'recommendation_dependencies' => '發現依賴關係問題，建議檢查並手動建立缺失的依賴權限',
        'recommendation_skipped' => '有權限被跳過，如需更新請調整衝突處理策略',

        // 成功訊息
        'import_success' => '匯入完成！建立 :created 個，更新 :updated 個，跳過 :skipped 個權限',
        'import_partial_success' => '匯入完成但有錯誤，請檢查詳細報告',
        'preview_generated' => '預覽生成成功',
        'permissions_updated' => '權限資料已更新，列表已重新載入',

        // 統計資訊
        'export_stats' => '匯出統計',
        'import_stats' => '匯入統計',
        'recent_exports' => '最近匯出',
        'recent_imports' => '最近匯入',
        'no_recent_operations' => '暫無最近操作記錄',
    ],

    // 權限表單
    'form' => [
        // 表單標題
        'create_title' => '建立權限',
        'edit_title' => '編輯權限',
        'duplicate_title' => '複製權限',
        
        // 表單欄位
        'name_label' => '權限名稱',
        'name_placeholder' => '例如：users.view',
        'name_help' => '使用模組.動作格式，僅限小寫字母、數字、點號和底線',
        'display_name_label' => '顯示名稱',
        'display_name_placeholder' => '例如：檢視使用者',
        'description_label' => '描述',
        'description_placeholder' => '權限的詳細描述',
        'module_label' => '模組',
        'module_placeholder' => '請選擇模組',
        'type_label' => '類型',
        'type_placeholder' => '請選擇類型',
        'dependencies_label' => '依賴權限',
        'dependencies_help' => '此權限需要的其他權限',
        
        // 系統權限警告
        'system_permission_warning' => '這是系統權限，某些欄位無法修改',
        'system_permission_name_readonly' => '系統權限的名稱不能修改',
        'system_permission_module_readonly' => '系統權限的模組不能修改',
        
        // 按鈕
        'save' => '儲存',
        'cancel' => '取消',
        'saving' => '儲存中...',
        
        // 驗證訊息
        'name_required' => '權限名稱為必填',
        'name_format_invalid' => '權限名稱格式無效，請使用模組.動作格式',
        'name_exists' => '此權限名稱已存在',
        'display_name_required' => '顯示名稱為必填',
        'module_required' => '請選擇模組',
        'type_required' => '請選擇類型',
        'dependencies_invalid' => '選擇的依賴權限無效',
        'circular_dependency' => '不能建立循環依賴關係',
    ],

    // 權限刪除
    'delete' => [
        'title' => '刪除權限',
        'confirm_message' => '您確定要刪除權限「:name」嗎？',
        'warning_message' => '此操作無法復原。',
        'cannot_delete_system' => '系統權限無法刪除',
        'cannot_delete_in_use' => '此權限正被以下角色使用，無法刪除：',
        'cannot_delete_has_dependents' => '此權限被其他權限依賴，無法刪除：',
        'force_delete' => '強制刪除',
        'force_delete_warning' => '強制刪除將移除所有相關的角色指派和依賴關係',
        'delete_button' => '刪除',
        'cancel_button' => '取消',
        'deleting' => '刪除中...',
        'success' => '權限已成功刪除',
        'failed' => '刪除權限失敗',
    ],

    // 依賴關係管理
    'dependencies' => [
        'title' => '權限依賴關係',
        'description' => '管理權限之間的依賴關係',
        'depends_on' => '依賴權限',
        'depends_on_description' => '此權限需要以下權限',
        'dependents' => '被依賴權限',
        'dependents_description' => '以下權限需要此權限',
        'add_dependency' => '新增依賴',
        'remove_dependency' => '移除依賴',
        'no_dependencies' => '此權限沒有依賴其他權限',
        'no_dependents' => '沒有其他權限依賴此權限',
        'circular_dependency_error' => '不能建立循環依賴關係',
        'dependency_added' => '依賴關係已新增',
        'dependency_removed' => '依賴關係已移除',
        'dependency_graph' => '依賴關係圖',
        'show_graph' => '顯示依賴圖',
        'hide_graph' => '隱藏依賴圖',
    ],

    // 權限模板
    'templates' => [
        'title' => '權限模板',
        'description' => '使用預定義模板快速建立權限',
        'select_template' => '選擇模板',
        'apply_template' => '應用模板',
        'create_template' => '建立模板',
        'template_name' => '模板名稱',
        'template_description' => '模板描述',
        'template_permissions' => '模板權限',
        'save_template' => '儲存模板',
        'delete_template' => '刪除模板',
        'template_applied' => '模板已應用，建立了 :count 個權限',
        'template_saved' => '模板已儲存',
        'template_deleted' => '模板已刪除',
        'no_templates' => '沒有可用的模板',
        
        // 預定義模板
        'crud_template' => 'CRUD 權限模板',
        'crud_template_description' => '建立、讀取、更新、刪除權限',
        'view_template' => '檢視權限模板',
        'view_template_description' => '僅檢視權限',
        'admin_template' => '管理權限模板',
        'admin_template_description' => '完整管理權限',
    ],

    // 權限審計
    'audit' => [
        'title' => '權限審計日誌',
        'description' => '檢視權限變更歷史',
        'action' => '操作',
        'user' => '操作者',
        'timestamp' => '時間',
        'changes' => '變更內容',
        'ip_address' => 'IP 位址',
        'user_agent' => '使用者代理',
        'old_value' => '原值',
        'new_value' => '新值',
        'no_changes' => '沒有變更記錄',
        
        // 操作類型
        'action_created' => '建立',
        'action_updated' => '更新',
        'action_deleted' => '刪除',
        'action_dependency_added' => '新增依賴',
        'action_dependency_removed' => '移除依賴',
        
        // 篩選
        'filter_by_action' => '按操作篩選',
        'filter_by_user' => '按使用者篩選',
        'filter_by_date' => '按日期篩選',
        'all_actions' => '所有操作',
        'all_users' => '所有使用者',
    ],

    // 權限使用情況分析
    'usage_analysis' => [
        'title' => '權限使用情況分析',
        'description' => '分析權限的使用情況和統計',
        'usage_statistics' => '使用統計',
        'role_usage' => '角色使用情況',
        'user_impact' => '使用者影響',
        'usage_frequency' => '使用頻率',
        'last_used' => '最後使用',
        'never_used' => '從未使用',
        'high_usage' => '高使用率',
        'medium_usage' => '中使用率',
        'low_usage' => '低使用率',
        'unused' => '未使用',
        'roles_using' => '使用此權限的角色',
        'users_affected' => '受影響的使用者',
        'usage_trend' => '使用趨勢',
        'recommendation' => '建議',
        'consider_removal' => '考慮移除未使用的權限',
        'review_necessity' => '檢視權限的必要性',
    ],

    // 權限測試功能
    'test' => [
        // 基本標籤
        'title' => '權限測試工具',
        'description' => '測試使用者或角色的權限配置，驗證權限系統是否正常運作',
        'test_configuration' => '測試配置',
        'test_mode' => '測試模式',
        'test_mode_description' => '選擇要測試的對象類型',
        'user_permission' => '使用者權限',
        'role_permission' => '角色權限',

        // 選擇器
        'select_user' => '選擇使用者',
        'select_role' => '選擇角色',
        'select_permission' => '選擇權限',
        'choose_user' => '請選擇使用者',
        'choose_role' => '請選擇角色',
        'choose_permission' => '請選擇權限',
        'users' => '使用者',

        // 測試執行
        'run_test' => '執行測試',
        'testing' => '測試中...',
        'test_results' => '測試結果',
        'tested_at' => '測試時間',
        'tested_user' => '測試使用者',
        'tested_role' => '測試角色',
        'tested_permission' => '測試權限',

        // 測試結果
        'user_has_permission' => '使用者「:user」擁有權限「:permission」',
        'user_lacks_permission' => '使用者「:user」沒有權限「:permission」',
        'role_has_permission' => '角色「:role」擁有權限「:permission」',
        'role_lacks_permission' => '角色「:role」沒有權限「:permission」',
        'permission_granted_through_roles' => '透過 :count 個角色路徑取得權限',
        'permission_not_found_in_roles' => '在使用者的角色中找不到此權限',
        'permission_granted_through' => '透過以下方式取得權限：:sources',
        'permission_not_assigned_to_role' => '此權限未指派給該角色',

        // 權限路徑
        'permission_path' => '權限路徑',
        'show_details' => '顯示詳情',
        'hide_details' => '隱藏詳情',
        'through_role' => '透過角色',
        'direct_assignment' => '直接指派',
        'inheritance' => '繼承',
        'dependency' => '依賴',
        'inherited_from' => '繼承自',
        'from_parent' => '來自父角色',
        'via_dependency' => '透過依賴',

        // 超級管理員
        'super_admin_access' => '超級管理員存取',
        'super_admin_description' => '超級管理員擁有所有權限',

        // 詳細資訊
        'username' => '使用者名稱',
        'system_name' => '系統名稱',
        'user_count' => '使用者數量',
        'module' => '模組',
        'type' => '類型',

        // 操作
        'clear_results' => '清除結果',
        'export_report' => '匯出報告',
        'no_results_to_export' => '沒有測試結果可匯出',

        // 驗證錯誤
        'user_required' => '請選擇要測試的使用者',
        'role_required' => '請選擇要測試的角色',
        'permission_required' => '請選擇要測試的權限',
        'user_not_found' => '找不到指定的使用者',
        'role_not_found' => '找不到指定的角色',
        'permission_not_found' => '找不到指定的權限',
        'invalid_selection' => '選擇的使用者或權限無效',

        // 批量測試
        'batch_test' => '批量測試',
        'select_permissions' => '選擇多個權限進行測試',
        'batch_results' => '批量測試結果',
        'permissions_tested' => '已測試 :count 個權限',

        // 測試報告
        'report_title' => '權限測試報告',
        'report_generated_at' => '報告生成時間',
        'report_generated_by' => '報告生成者',
        'test_summary' => '測試摘要',
        'detailed_results' => '詳細結果',

        // 成功和錯誤訊息
        'test_completed' => '權限測試完成',
        'test_failed' => '權限測試失敗',
        'report_exported' => '測試報告已匯出',
        'export_failed' => '報告匯出失敗',
    ],
];