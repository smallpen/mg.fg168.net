<?php

return [

    /*
    |--------------------------------------------------------------------------
    | 角色管理錯誤訊息
    |--------------------------------------------------------------------------
    |
    | 以下語言行用於角色管理的錯誤訊息和驗證失敗。
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

    // 角色 CRUD 錯誤
    'crud' => [
        'role_not_found' => '找不到指定的角色',
        'role_creation_failed' => '建立角色失敗',
        'role_update_failed' => '更新角色失敗',
        'role_deletion_failed' => '刪除角色失敗',
        'role_duplication_failed' => '複製角色失敗',
        'role_activation_failed' => '啟用角色失敗',
        'role_deactivation_failed' => '停用角色失敗',
    ],

    // 權限錯誤
    'permissions' => [
        'permission_assignment_failed' => '為角色指派權限失敗',
        'permission_removal_failed' => '從角色移除權限失敗',
        'permission_sync_failed' => '同步角色權限失敗',
        'invalid_permission' => '指定的權限無效',
        'permission_not_found' => '找不到權限',
        'permission_dependency_error' => '權限依賴關係衝突',
        'circular_permission_dependency' => '偵測到循環權限依賴',
    ],

    // 層級錯誤
    'hierarchy' => [
        'invalid_parent_role' => '指定的父角色無效',
        'circular_dependency' => '在角色層級中偵測到循環依賴',
        'parent_role_not_found' => '找不到父角色',
        'cannot_set_self_as_parent' => '角色不能是自己的父角色',
        'hierarchy_depth_exceeded' => '超過最大層級深度',
        'child_role_conflict' => '與現有子角色衝突',
    ],

    // 系統角色錯誤
    'system_roles' => [
        'cannot_modify_system_role' => '系統角色無法修改',
        'cannot_delete_system_role' => '系統角色無法刪除',
        'cannot_change_system_role_name' => '系統角色名稱無法變更',
        'cannot_remove_core_permissions' => '核心權限無法從系統角色中移除',
        'system_role_required' => '至少必須存在一個系統角色',
    ],

    // 驗證錯誤
    'validation' => [
        'name_required' => '角色名稱為必填',
        'name_invalid_format' => '角色名稱格式無效。僅使用小寫字母、數字和底線',
        'name_too_short' => '角色名稱至少需要 :min 個字元',
        'name_too_long' => '角色名稱不能超過 :max 個字元',
        'name_already_exists' => '此名稱的角色已存在',
        'display_name_required' => '顯示名稱為必填',
        'display_name_too_long' => '顯示名稱不能超過 :max 個字元',
        'description_too_long' => '描述不能超過 :max 個字元',
        'invalid_parent_selection' => '父角色選擇無效',
        'permissions_invalid' => '一個或多個選擇的權限無效',
    ],

    // 授權錯誤
    'authorization' => [
        'insufficient_permissions' => '您沒有足夠的權限執行此操作',
        'role_view_denied' => '您沒有權限檢視角色',
        'role_create_denied' => '您沒有權限建立角色',
        'role_edit_denied' => '您沒有權限編輯角色',
        'role_delete_denied' => '您沒有權限刪除角色',
        'permission_manage_denied' => '您沒有權限管理角色權限',
        'system_role_access_denied' => '您沒有權限存取系統角色',
    ],

    // 刪除限制
    'deletion' => [
        'role_has_users' => '無法刪除角色，因為它有 :count 個指派的使用者',
        'role_has_child_roles' => '無法刪除角色，因為它有 :count 個子角色',
        'confirmation_required' => '需要刪除確認',
        'confirmation_mismatch' => '確認文字與角色名稱不符',
        'force_delete_required' => '必須啟用強制刪除才能刪除有依賴關係的角色',
        'system_role_deletion_blocked' => '系統角色無法刪除',
        'last_admin_role' => '無法刪除最後一個管理員角色',
    ],

    // 批量操作錯誤
    'bulk' => [
        'no_roles_selected' => '沒有選擇角色進行批量操作',
        'invalid_bulk_action' => '指定的批量操作無效',
        'bulk_operation_failed' => '批量操作失敗',
        'partial_bulk_failure' => '批量操作完成，但 :total 個操作中有 :failed 個失敗',
        'bulk_permission_assignment_failed' => '為某些角色指派權限失敗',
        'bulk_deletion_blocked' => '某些角色因限制無法刪除',
        'mixed_role_types_error' => '無法對混合角色類型執行批量操作',
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

    // 匯入/匯出錯誤
    'import_export' => [
        'import_failed' => '角色匯入失敗',
        'export_failed' => '角色匯出失敗',
        'invalid_file_format' => '檔案格式無效',
        'file_too_large' => '檔案大小超過最大限制',
        'corrupted_data' => '偵測到損壞的資料',
        'missing_required_fields' => '匯入資料中缺少必填欄位',
        'invalid_role_data' => '角色資料格式無效',
    ],

    // 快取錯誤
    'cache' => [
        'cache_clear_failed' => '清除角色快取失敗',
        'cache_update_failed' => '更新角色快取失敗',
        'cache_corruption' => '偵測到角色快取損壞',
        'cache_unavailable' => '角色快取服務無法使用',
    ],

    // 搜尋和篩選錯誤
    'search' => [
        'search_failed' => '角色搜尋失敗',
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
        'concurrent_modification' => '角色已被其他使用者修改',
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
        'invalid_configuration' => '角色管理配置無效',
        'missing_configuration' => '缺少必要的配置',
        'configuration_load_failed' => '載入配置失敗',
        'permission_config_error' => '權限配置錯誤',
        'role_config_mismatch' => '角色配置不符',
    ],

    // 本地化錯誤
    'localization' => [
        'translation_missing' => '角色管理的翻譯缺失',
        'invalid_locale' => '指定的語言環境無效',
        'localization_load_failed' => '載入本地化檔案失敗',
        'unsupported_language' => '角色管理不支援的語言',
    ],

];