<?php

return [

    /*
    |--------------------------------------------------------------------------
    | 權限驗證語言行
    |--------------------------------------------------------------------------
    |
    | 以下語言行包含權限管理系統使用的預設驗證訊息。
    |
    */

    'name' => [
        'required' => '權限名稱欄位為必填。',
        'string' => '權限名稱必須是字串。',
        'max' => '權限名稱不能超過 :max 個字元。',
        'min' => '權限名稱至少需要 :min 個字元。',
        'unique' => '權限名稱已被使用。',
        'regex' => '權限名稱格式無效。請使用格式：模組.動作',
        'reserved' => '權限名稱「:name」為保留名稱，無法使用。',
    ],

    'display_name' => [
        'required' => '顯示名稱欄位為必填。',
        'string' => '顯示名稱必須是字串。',
        'max' => '顯示名稱不能超過 :max 個字元。',
        'min' => '顯示名稱至少需要 :min 個字元。',
    ],

    'description' => [
        'string' => '描述必須是字串。',
        'max' => '描述不能超過 :max 個字元。',
    ],

    'module' => [
        'required' => '模組欄位為必填。',
        'string' => '模組必須是字串。',
        'in' => '選擇的模組無效。',
        'exists' => '選擇的模組不存在。',
    ],

    'type' => [
        'required' => '權限類型欄位為必填。',
        'string' => '權限類型必須是字串。',
        'in' => '選擇的權限類型無效。',
    ],

    'dependencies' => [
        'array' => '依賴關係必須是陣列。',
        'exists' => '一個或多個選擇的依賴關係無效。',
        'circular' => '偵測到循環依賴。權限不能依賴自己或建立循環鏈。',
        'max_depth' => '依賴鏈超過最大深度 :max 層。',
        'self_reference' => '權限不能依賴自己。',
        'invalid_permission' => '依賴關係中的權限 ID 無效：:id',
    ],

    'is_system' => [
        'boolean' => '系統權限欄位必須是 true 或 false。',
        'immutable' => '現有權限的系統權限狀態無法變更。',
    ],

    'is_active' => [
        'boolean' => '啟用狀態欄位必須是 true 或 false。',
    ],

    // 自定義驗證規則
    'custom' => [
        'permission_name_format' => '權限名稱必須遵循格式：模組.動作（例如：users.create）',
        'system_permission_modification' => '系統權限無法以此方式修改。',
        'permission_in_use' => '此權限目前正在使用中，無法刪除。',
        'dependency_exists' => '此依賴關係已存在。',
        'invalid_dependency_target' => '無法對指定權限建立依賴關係。',
        'module_mismatch' => '權限模組與預期模組不符。',
        'type_restriction' => '選擇的模組不允許此權限類型。',
        'name_pattern' => '權限名稱只能包含小寫字母、數字、點號和底線。',
        'reserved_name' => '此權限名稱為系統保留使用。',
        'duplicate_display_name' => '同一模組中已存在此顯示名稱的權限。',
    ],

    // 批量操作驗證
    'bulk' => [
        'no_selection' => '沒有選擇權限進行批量操作。',
        'invalid_action' => '指定的批量操作無效。',
        'mixed_types' => '無法對混合權限類型執行批量操作。',
        'system_permissions_included' => '批量操作不能包含系統權限。',
        'permissions_in_use' => '某些選擇的權限正在使用中，無法修改。',
        'max_selection_exceeded' => '超過最大選擇限制 :max 個權限。',
    ],

    // 匯入驗證
    'import' => [
        'invalid_format' => '匯入檔案格式無效。',
        'missing_required_fields' => '匯入資料缺少必填欄位：:fields',
        'invalid_permission_structure' => '匯入檔案中的權限資料結構無效。',
        'version_mismatch' => '匯入檔案版本與目前系統不相容。',
        'duplicate_names' => '匯入資料中發現重複的權限名稱：:names',
        'invalid_dependencies' => '匯入資料中發現無效的依賴關係參考。',
        'file_too_large' => '匯入檔案大小超過最大限制 :max MB。',
        'corrupted_data' => '匯入檔案包含損壞或無效的資料。',
    ],

    // 模板驗證
    'template' => [
        'name_required' => '模板名稱為必填。',
        'name_unique' => '此名稱的模板已存在。',
        'description_required' => '模板描述為必填。',
        'permissions_required' => '模板必須包含至少一個權限。',
        'invalid_permission_data' => '模板包含無效的權限資料。',
        'circular_dependencies' => '模板包含循環依賴關係。',
    ],

    // 測試驗證
    'test' => [
        'user_required' => '使用者權限測試需要選擇使用者。',
        'role_required' => '角色權限測試需要選擇角色。',
        'permission_required' => '測試需要選擇權限。',
        'invalid_user' => '選擇的使用者不存在或未啟用。',
        'invalid_role' => '選擇的角色不存在或未啟用。',
        'invalid_permission' => '選擇的權限不存在或未啟用。',
        'test_mode_required' => '必須指定測試模式。',
        'invalid_test_mode' => '指定的測試模式無效。',
    ],

];