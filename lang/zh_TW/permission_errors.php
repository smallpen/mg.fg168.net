<?php

return [

    /*
    |--------------------------------------------------------------------------
    | 權限管理錯誤訊息
    |--------------------------------------------------------------------------
    |
    | 以下語言行用於權限管理的錯誤訊息和驗證失敗。
    |
    */

    // 一般錯誤
    'general' => [
        'operation_failed' => '操作失敗',
        'unexpected_error' => '發生未預期的錯誤',
        'server_error' => '伺服器錯誤',
        'network_error' => '網路連線錯誤',
        'timeout_error' => '操作逾時',
        'access_denied' => '存取被拒絕',
        'resource_not_found' => '找不到資源',
        'invalid_request' => '無效的請求',
    ],

    // 權限 CRUD 錯誤
    'crud' => [
        'permission_not_found' => '找不到指定的權限',
        'permission_creation_failed' => '建立權限失敗',
        'permission_update_failed' => '更新權限失敗',
        'permission_deletion_failed' => '刪除權限失敗',
        'permission_duplication_failed' => '複製權限失敗',
        'permission_activation_failed' => '啟用權限失敗',
        'permission_deactivation_failed' => '停用權限失敗',
    ],

    // 依賴關係錯誤
    'dependencies' => [
        'dependency_creation_failed' => '建立權限依賴關係失敗',
        'dependency_removal_failed' => '移除權限依賴關係失敗',
        'dependency_sync_failed' => '同步權限依賴關係失敗',
        'invalid_dependency' => '指定的依賴權限無效',
        'dependency_not_found' => '找不到依賴權限',
        'circular_dependency' => '偵測到循環依賴關係',
        'dependency_chain_too_deep' => '依賴鏈過深',
        'self_dependency' => '權限不能依賴自己',
    ],

    // 系統權限錯誤
    'system_permissions' => [
        'cannot_modify_system_permission' => '系統權限無法修改',
        'cannot_delete_system_permission' => '系統權限無法刪除',
        'cannot_change_system_permission_name' => '系統權限名稱無法變更',
        'cannot_remove_system_dependencies' => '系統權限的依賴關係無法移除',
        'system_permission_required' => '至少必須存在一個系統權限',
    ],

    // 驗證錯誤
    'validation' => [
        'name_required' => '權限名稱為必填',
        'name_invalid_format' => '權限名稱格式無效。請使用模組.動作格式',
        'name_too_short' => '權限名稱至少需要 :min 個字元',
        'name_too_long' => '權限名稱不能超過 :max 個字元',
        'name_already_exists' => '此名稱的權限已存在',
        'display_name_required' => '顯示名稱為必填',
        'display_name_too_long' => '顯示名稱不能超過 :max 個字元',
        'description_too_long' => '描述不能超過 :max 個字元',
        'invalid_module' => '模組選擇無效',
        'invalid_type' => '權限類型選擇無效',
        'dependencies_invalid' => '一個或多個選擇的依賴權限無效',
        'module_required' => '模組為必填',
        'type_required' => '權限類型為必填',
    ],

    // 授權錯誤
    'authorization' => [
        'insufficient_permissions' => '您沒有足夠的權限執行此操作',
        'permission_view_denied' => '您沒有權限檢視權限',
        'permission_create_denied' => '您沒有權限建立權限',
        'permission_edit_denied' => '您沒有權限編輯權限',
        'permission_delete_denied' => '您沒有權限刪除權限',
        'dependency_manage_denied' => '您沒有權限管理權限依賴關係',
        'system_permission_access_denied' => '您沒有權限存取系統權限',
        'template_manage_denied' => '您沒有權限管理權限模板',
        'audit_view_denied' => '您沒有權限檢視審計日誌',
        'test_permission_denied' => '您沒有權限測試權限',
    ],

    // 刪除限制
    'deletion' => [
        'permission_has_roles' => '無法刪除權限，因為它被 :count 個角色使用',
        'permission_has_dependents' => '無法刪除權限，因為有 :count 個權限依賴它',
        'confirmation_required' => '需要刪除確認',
        'confirmation_mismatch' => '確認文字與權限名稱不符',
        'force_delete_required' => '必須啟用強制刪除才能刪除有依賴關係的權限',
        'system_permission_deletion_blocked' => '系統權限無法刪除',
        'core_permission_deletion_blocked' => '核心權限無法刪除',
    ],

    // 批量操作錯誤
    'bulk' => [
        'no_permissions_selected' => '沒有選擇權限進行批量操作',
        'invalid_bulk_action' => '指定的批量操作無效',
        'bulk_operation_failed' => '批量操作失敗',
        'partial_bulk_failure' => '批量操作完成，但 :total 個操作中有 :failed 個失敗',
        'bulk_dependency_assignment_failed' => '為某些權限指派依賴關係失敗',
        'bulk_deletion_blocked' => '某些權限因限制無法刪除',
        'mixed_permission_types_error' => '無法對混合權限類型執行批量操作',
    ],

    // 匯入/匯出錯誤
    'import_export' => [
        'import_failed' => '權限匯入失敗',
        'export_failed' => '權限匯出失敗',
        'invalid_file_format' => '檔案格式無效',
        'file_too_large' => '檔案大小超過最大限制',
        'corrupted_data' => '偵測到損壞的資料',
        'missing_required_fields' => '匯入資料中缺少必填欄位',
        'invalid_permission_data' => '權限資料格式無效',
        'version_incompatible' => '匯入檔案版本不相容',
        'conflict_resolution_failed' => '衝突解決失敗',
        'dependency_resolution_failed' => '依賴關係解析失敗',
        'preview_generation_failed' => '預覽生成失敗',
    ],

    // 模板錯誤
    'templates' => [
        'template_not_found' => '找不到指定的模板',
        'template_creation_failed' => '建立模板失敗',
        'template_update_failed' => '更新模板失敗',
        'template_deletion_failed' => '刪除模板失敗',
        'template_application_failed' => '應用模板失敗',
        'invalid_template_data' => '模板資料無效',
        'template_name_exists' => '模板名稱已存在',
        'template_permissions_invalid' => '模板權限配置無效',
    ],

    // 測試錯誤
    'testing' => [
        'test_execution_failed' => '權限測試執行失敗',
        'invalid_test_subject' => '測試對象無效',
        'test_user_not_found' => '測試使用者不存在',
        'test_role_not_found' => '測試角色不存在',
        'test_permission_not_found' => '測試權限不存在',
        'batch_test_failed' => '批量測試失敗',
        'test_report_generation_failed' => '測試報告生成失敗',
        'test_data_invalid' => '測試資料無效',
    ],

    // 審計錯誤
    'audit' => [
        'audit_log_creation_failed' => '建立審計日誌失敗',
        'audit_log_retrieval_failed' => '取得審計日誌失敗',
        'audit_log_cleanup_failed' => '清理審計日誌失敗',
        'audit_data_corruption' => '審計資料損壞',
        'audit_permission_denied' => '沒有權限存取審計日誌',
    ],

    // 使用情況分析錯誤
    'usage_analysis' => [
        'analysis_failed' => '使用情況分析失敗',
        'statistics_calculation_failed' => '統計計算失敗',
        'usage_data_unavailable' => '使用情況資料不可用',
        'analysis_timeout' => '分析操作逾時',
        'cache_update_failed' => '快取更新失敗',
    ],

    // 資料庫錯誤
    'database' => [
        'connection_failed' => '資料庫連線失敗',
        'query_failed' => '資料庫查詢失敗',
        'transaction_failed' => '資料庫交易失敗',
        'constraint_violation' => '資料庫約束違反',
        'duplicate_entry' => '偵測到重複項目',
        'foreign_key_constraint' => '外鍵約束違反',
        'data_integrity_error' => '資料完整性錯誤',
    ],

    // 快取錯誤
    'cache' => [
        'cache_clear_failed' => '清除權限快取失敗',
        'cache_update_failed' => '更新權限快取失敗',
        'cache_corruption' => '偵測到權限快取損壞',
        'cache_unavailable' => '權限快取服務無法使用',
    ],

    // 搜尋和篩選錯誤
    'search' => [
        'search_failed' => '權限搜尋失敗',
        'invalid_search_criteria' => '搜尋條件無效',
        'search_timeout' => '搜尋操作逾時',
        'filter_error' => '篩選器應用失敗',
        'sorting_error' => '排序操作失敗',
    ],

    // API 錯誤
    'api' => [
        'invalid_api_request' => 'API 請求無效',
        'api_rate_limit_exceeded' => 'API 速率限制超過',
        'api_authentication_failed' => 'API 認證失敗',
        'api_authorization_failed' => 'API 授權失敗',
        'malformed_request_data' => '請求資料格式錯誤',
        'unsupported_api_version' => '不支援的 API 版本',
    ],

    // 會話和狀態錯誤
    'session' => [
        'session_expired' => '您的會話已過期，請重新登入',
        'invalid_session_state' => '會話狀態無效',
        'concurrent_modification' => '權限已被其他使用者修改',
        'stale_data_error' => '您嘗試修改的資料已過時',
        'session_conflict' => '偵測到會話衝突',
    ],

    // 檔案系統錯誤
    'filesystem' => [
        'file_not_found' => '找不到必要的檔案',
        'file_permission_denied' => '檔案權限被拒絕',
        'disk_space_insufficient' => '磁碟空間不足',
        'file_write_failed' => '寫入檔案失敗',
        'file_read_failed' => '讀取檔案失敗',
        'directory_creation_failed' => '建立目錄失敗',
    ],

    // 配置錯誤
    'config' => [
        'invalid_configuration' => '權限管理配置無效',
        'missing_configuration' => '缺少必要的配置',
        'configuration_load_failed' => '載入配置失敗',
        'permission_config_error' => '權限配置錯誤',
        'module_config_mismatch' => '模組配置不符',
    ],

    // 本地化錯誤
    'localization' => [
        'translation_missing' => '權限管理的翻譯缺失',
        'invalid_locale' => '指定的語言環境無效',
        'localization_load_failed' => '載入本地化檔案失敗',
        'unsupported_language' => '權限管理不支援的語言',
    ],

];