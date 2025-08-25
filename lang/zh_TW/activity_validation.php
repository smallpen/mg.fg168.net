<?php

return [

    /*
    |--------------------------------------------------------------------------
    | 活動記錄驗證語言檔案
    |--------------------------------------------------------------------------
    |
    | 以下語言行包含活動記錄系統驗證類別使用的預設驗證訊息。
    |
    */

    // 活動建立驗證
    'activity' => [
        'type_required' => '活動類型為必填項目',
        'type_string' => '活動類型必須是字串',
        'type_max' => '活動類型不能超過 :max 個字元',
        'type_in' => '選擇的活動類型無效',
        
        'description_required' => '活動描述為必填項目',
        'description_string' => '活動描述必須是字串',
        'description_max' => '活動描述不能超過 :max 個字元',
        
        'subject_type_string' => '操作對象類型必須是字串',
        'subject_type_max' => '操作對象類型不能超過 :max 個字元',
        
        'subject_id_integer' => '操作對象 ID 必須是整數',
        'subject_id_min' => '操作對象 ID 至少為 :min',
        
        'causer_type_string' => '操作者類型必須是字串',
        'causer_type_max' => '操作者類型不能超過 :max 個字元',
        
        'causer_id_integer' => '操作者 ID 必須是整數',
        'causer_id_min' => '操作者 ID 至少為 :min',
        
        'properties_array' => '屬性必須是陣列',
        'properties_json' => '屬性必須是有效的 JSON',
        
        'ip_address_ip' => 'IP 位址必須是有效的 IP 位址',
        'ip_address_max' => 'IP 位址不能超過 :max 個字元',
        
        'user_agent_string' => '使用者代理必須是字串',
        'user_agent_max' => '使用者代理不能超過 :max 個字元',
        
        'result_string' => '結果必須是字串',
        'result_max' => '結果不能超過 :max 個字元',
        'result_in' => '選擇的結果無效',
        
        'risk_level_integer' => '風險等級必須是整數',
        'risk_level_between' => '風險等級必須在 :min 和 :max 之間',
        
        'signature_string' => '簽章必須是字串',
        'signature_max' => '簽章不能超過 :max 個字元',
    ],

    // 篩選器驗證
    'filters' => [
        'date_from_date' => '開始日期必須是有效日期',
        'date_from_date_format' => '開始日期必須是 Y-m-d 格式',
        'date_from_before_or_equal' => '開始日期必須早於或等於結束日期',
        
        'date_to_date' => '結束日期必須是有效日期',
        'date_to_date_format' => '結束日期必須是 Y-m-d 格式',
        'date_to_after_or_equal' => '結束日期必須晚於或等於開始日期',
        
        'user_filter_string' => '使用者篩選必須是字串',
        'user_filter_max' => '使用者篩選不能超過 :max 個字元',
        
        'type_filter_string' => '類型篩選必須是字串',
        'type_filter_in' => '選擇的類型篩選無效',
        
        'subject_filter_string' => '對象篩選必須是字串',
        'subject_filter_max' => '對象篩選不能超過 :max 個字元',
        
        'result_filter_string' => '結果篩選必須是字串',
        'result_filter_in' => '選擇的結果篩選無效',
        
        'risk_filter_integer' => '風險篩選必須是整數',
        'risk_filter_between' => '風險篩選必須在 :min 和 :max 之間',
        
        'ip_filter_string' => 'IP 篩選必須是字串',
        'ip_filter_ip' => 'IP 篩選必須是有效的 IP 位址',
        
        'session_filter_string' => 'Session 篩選必須是字串',
        'session_filter_max' => 'Session 篩選不能超過 :max 個字元',
        
        'search_string' => '搜尋查詢必須是字串',
        'search_min' => '搜尋查詢至少需要 :min 個字元',
        'search_max' => '搜尋查詢不能超過 :max 個字元',
        
        'per_page_integer' => '每頁數量必須是整數',
        'per_page_between' => '每頁數量必須在 :min 和 :max 之間',
        
        'sort_field_string' => '排序欄位必須是字串',
        'sort_field_in' => '選擇的排序欄位無效',
        
        'sort_direction_string' => '排序方向必須是字串',
        'sort_direction_in' => '排序方向必須是 asc 或 desc',
    ],

    // 匯出驗證
    'export' => [
        'format_required' => '匯出格式為必填項目',
        'format_string' => '匯出格式必須是字串',
        'format_in' => '選擇的匯出格式無效',
        
        'range_required' => '匯出範圍為必填項目',
        'range_string' => '匯出範圍必須是字串',
        'range_in' => '選擇的匯出範圍無效',
        
        'columns_array' => '欄位必須是陣列',
        'columns_min' => '至少需要選擇 :min 個欄位',
        'columns_max' => '最多只能選擇 :max 個欄位',
        
        'file_name_string' => '檔案名稱必須是字串',
        'file_name_max' => '檔案名稱不能超過 :max 個字元',
        'file_name_regex' => '檔案名稱包含無效字元',
        
        'compression_string' => '壓縮類型必須是字串',
        'compression_in' => '選擇的壓縮類型無效',
        
        'include_properties_boolean' => '包含屬性必須是 true 或 false',
        'include_notes_boolean' => '包含註記必須是 true 或 false',
        'include_related_boolean' => '包含相關必須是 true 或 false',
        
        'email_notification_boolean' => '電子郵件通知必須是 true 或 false',
        'email_address_email' => '電子郵件地址必須是有效的電子郵件地址',
    ],

    // 匯入驗證
    'import' => [
        'file_required' => '匯入檔案為必填項目',
        'file_file' => '匯入必須是有效檔案',
        'file_mimes' => '匯入檔案必須是以下類型：:values',
        'file_max' => '匯入檔案不能超過 :max KB',
        
        'format_required' => '匯入格式為必填項目',
        'format_string' => '匯入格式必須是字串',
        'format_in' => '選擇的匯入格式無效',
        
        'validate_data_boolean' => '驗證資料必須是 true 或 false',
        'skip_duplicates_boolean' => '跳過重複必須是 true 或 false',
        'update_existing_boolean' => '更新現有必須是 true 或 false',
        
        'batch_size_integer' => '批次大小必須是整數',
        'batch_size_between' => '批次大小必須在 :min 和 :max 之間',
        
        'password_string' => '密碼必須是字串',
        'password_min' => '密碼至少需要 :min 個字元',
    ],

    // 監控規則驗證
    'monitoring_rule' => [
        'name_required' => '規則名稱為必填項目',
        'name_string' => '規則名稱必須是字串',
        'name_max' => '規則名稱不能超過 :max 個字元',
        'name_unique' => '規則名稱已被使用',
        
        'description_string' => '規則描述必須是字串',
        'description_max' => '規則描述不能超過 :max 個字元',
        
        'conditions_required' => '規則條件為必填項目',
        'conditions_array' => '規則條件必須是陣列',
        'conditions_min' => '至少需要指定 :min 個條件',
        
        'actions_required' => '規則動作為必填項目',
        'actions_array' => '規則動作必須是陣列',
        'actions_min' => '至少需要指定 :min 個動作',
        
        'priority_integer' => '優先級必須是整數',
        'priority_between' => '優先級必須在 :min 和 :max 之間',
        
        'is_active_boolean' => '啟用狀態必須是 true 或 false',
        
        'threshold_numeric' => '閾值必須是數字',
        'threshold_min' => '閾值至少為 :min',
        
        'time_window_integer' => '時間窗口必須是整數',
        'time_window_min' => '時間窗口至少為 :min 分鐘',
    ],

    // 備份驗證
    'backup' => [
        'name_required' => '備份名稱為必填項目',
        'name_string' => '備份名稱必須是字串',
        'name_max' => '備份名稱不能超過 :max 個字元',
        'name_regex' => '備份名稱包含無效字元',
        
        'description_string' => '備份描述必須是字串',
        'description_max' => '備份描述不能超過 :max 個字元',
        
        'type_required' => '備份類型為必填項目',
        'type_string' => '備份類型必須是字串',
        'type_in' => '選擇的備份類型無效',
        
        'compression_boolean' => '壓縮必須是 true 或 false',
        'encryption_boolean' => '加密必須是 true 或 false',
        
        'password_required_if' => '啟用加密時需要密碼',
        'password_string' => '密碼必須是字串',
        'password_min' => '密碼至少需要 :min 個字元',
        'password_confirmed' => '密碼確認不符',
        
        'location_required' => '備份位置為必填項目',
        'location_string' => '備份位置必須是字串',
        'location_in' => '選擇的備份位置無效',
        
        'schedule_string' => '備份排程必須是字串',
        'schedule_in' => '選擇的備份排程無效',
    ],

    // 保留政策驗證
    'retention' => [
        'general_days_required' => '一般活動保留天數為必填項目',
        'general_days_integer' => '一般活動保留天數必須是整數',
        'general_days_min' => '一般活動保留天數至少為 :min',
        'general_days_max' => '一般活動保留天數不能超過 :max',
        
        'security_days_required' => '安全事件保留天數為必填項目',
        'security_days_integer' => '安全事件保留天數必須是整數',
        'security_days_min' => '安全事件保留天數至少為 :min',
        'security_days_max' => '安全事件保留天數不能超過 :max',
        
        'system_days_required' => '系統活動保留天數為必填項目',
        'system_days_integer' => '系統活動保留天數必須是整數',
        'system_days_min' => '系統活動保留天數至少為 :min',
        'system_days_max' => '系統活動保留天數不能超過 :max',
        
        'auto_cleanup_boolean' => '自動清理必須是 true 或 false',
        
        'cleanup_schedule_required_if' => '啟用自動清理時需要清理排程',
        'cleanup_schedule_string' => '清理排程必須是字串',
        'cleanup_schedule_in' => '選擇的清理排程無效',
        
        'cleanup_time_date_format' => '清理時間必須是 H:i 格式',
        
        'archive_before_delete_boolean' => '刪除前歸檔必須是 true 或 false',
        
        'archive_location_required_if' => '啟用歸檔時需要歸檔位置',
        'archive_location_string' => '歸檔位置必須是字串',
        
        'compression_level_integer' => '壓縮等級必須是整數',
        'compression_level_between' => '壓縮等級必須在 :min 和 :max 之間',
    ],

    // 通知規則驗證
    'notification_rule' => [
        'name_required' => '通知規則名稱為必填項目',
        'name_string' => '通知規則名稱必須是字串',
        'name_max' => '通知規則名稱不能超過 :max 個字元',
        'name_unique' => '通知規則名稱已被使用',
        
        'description_string' => '通知規則描述必須是字串',
        'description_max' => '通知規則描述不能超過 :max 個字元',
        
        'conditions_required' => '觸發條件為必填項目',
        'conditions_array' => '觸發條件必須是陣列',
        'conditions_min' => '至少需要指定 :min 個條件',
        
        'channels_required' => '通知管道為必填項目',
        'channels_array' => '通知管道必須是陣列',
        'channels_min' => '至少需要選擇 :min 個管道',
        
        'recipients_required' => '收件人為必填項目',
        'recipients_array' => '收件人必須是陣列',
        'recipients_min' => '至少需要指定 :min 個收件人',
        
        'template_required' => '訊息模板為必填項目',
        'template_string' => '訊息模板必須是字串',
        'template_max' => '訊息模板不能超過 :max 個字元',
        
        'is_enabled_boolean' => '啟用狀態必須是 true 或 false',
        
        'rate_limit_integer' => '頻率限制必須是整數',
        'rate_limit_min' => '頻率限制至少為 :min',
        
        'quiet_hours_start_date_format' => '靜音時段開始時間必須是 H:i 格式',
        'quiet_hours_end_date_format' => '靜音時段結束時間必須是 H:i 格式',
    ],

    // API 驗證
    'api' => [
        'api_key_required' => 'API 金鑰為必填項目',
        'api_key_string' => 'API 金鑰必須是字串',
        'api_key_size' => 'API 金鑰必須是 :size 個字元',
        'api_key_exists' => '無效的 API 金鑰',
        
        'endpoint_required' => 'API 端點為必填項目',
        'endpoint_string' => 'API 端點必須是字串',
        'endpoint_in' => '無效的 API 端點',
        
        'method_required' => 'HTTP 方法為必填項目',
        'method_string' => 'HTTP 方法必須是字串',
        'method_in' => 'HTTP 方法必須是以下之一：:values',
        
        'version_string' => 'API 版本必須是字串',
        'version_in' => '不支援的 API 版本',
        
        'limit_integer' => '限制必須是整數',
        'limit_between' => '限制必須在 :min 和 :max 之間',
        
        'offset_integer' => '偏移必須是整數',
        'offset_min' => '偏移至少為 :min',
        
        'fields_array' => '欄位必須是陣列',
        'fields_distinct' => '欄位不能包含重複項目',
    ],

    // 安全驗證
    'security' => [
        'encryption_key_required' => '加密金鑰為必填項目',
        'encryption_key_string' => '加密金鑰必須是字串',
        'encryption_key_size' => '加密金鑰必須是 :size 個字元',
        
        'signature_required' => '數位簽章為必填項目',
        'signature_string' => '數位簽章必須是字串',
        'signature_size' => '數位簽章必須是 :size 個字元',
        
        'mask_type_required' => '遮蔽類型為必填項目',
        'mask_type_string' => '遮蔽類型必須是字串',
        'mask_type_in' => '選擇的遮蔽類型無效',
        
        'field_pattern_required' => '欄位模式為必填項目',
        'field_pattern_string' => '欄位模式必須是字串',
        'field_pattern_regex' => '欄位模式必須是有效的正規表達式',
        
        'custom_mask_required_if' => '遮蔽類型為自訂時需要自訂遮蔽',
        'custom_mask_string' => '自訂遮蔽必須是字串',
        'custom_mask_max' => '自訂遮蔽不能超過 :max 個字元',
    ],

    // 效能驗證
    'performance' => [
        'optimization_type_required' => '優化類型為必填項目',
        'optimization_type_string' => '優化類型必須是字串',
        'optimization_type_in' => '選擇的優化類型無效',
        
        'partition_size_integer' => '分區大小必須是整數',
        'partition_size_min' => '分區大小至少為 :min',
        'partition_size_max' => '分區大小不能超過 :max',
        
        'cache_ttl_integer' => '快取 TTL 必須是整數',
        'cache_ttl_min' => '快取 TTL 至少為 :min 秒',
        
        'compression_level_integer' => '壓縮等級必須是整數',
        'compression_level_between' => '壓縮等級必須在 :min 和 :max 之間',
        
        'index_columns_array' => '索引欄位必須是陣列',
        'index_columns_min' => '索引至少需要指定 :min 個欄位',
    ],

    // 統計驗證
    'statistics' => [
        'time_range_required' => '時間範圍為必填項目',
        'time_range_string' => '時間範圍必須是字串',
        'time_range_in' => '選擇的時間範圍無效',
        
        'chart_type_string' => '圖表類型必須是字串',
        'chart_type_in' => '選擇的圖表類型無效',
        
        'metrics_array' => '指標必須是陣列',
        'metrics_min' => '至少需要選擇 :min 個指標',
        'metrics_distinct' => '指標不能包含重複項目',
        
        'group_by_string' => '分組欄位必須是字串',
        'group_by_in' => '選擇的分組欄位無效',
        
        'aggregation_string' => '聚合函數必須是字串',
        'aggregation_in' => '選擇的聚合函數無效',
    ],

    // 自訂驗證訊息
    'custom' => [
        'activity_type_exists' => '選擇的活動類型不存在',
        'subject_exists' => '指定的操作對象不存在',
        'causer_exists' => '指定的操作者不存在',
        'date_range_valid' => '日期範圍無效或過大',
        'export_permission' => '您沒有權限匯出活動',
        'import_permission' => '您沒有權限匯入活動',
        'monitoring_permission' => '您沒有權限管理監控規則',
        'backup_permission' => '您沒有權限管理備份',
        'retention_permission' => '您沒有權限管理保留政策',
        'api_permission' => '您沒有權限存取 API',
        'security_permission' => '您沒有權限管理安全設定',
    ],

];