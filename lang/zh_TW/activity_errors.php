<?php

return [

    /*
    |--------------------------------------------------------------------------
    | 活動記錄錯誤訊息
    |--------------------------------------------------------------------------
    |
    | 以下語言行用於活動記錄系統的錯誤訊息和驗證失敗。
    |
    */

    // 一般錯誤
    'general' => [
        'system_error' => '處理活動記錄時發生系統錯誤',
        'database_connection' => '資料庫連線失敗',
        'permission_denied' => '您沒有權限存取活動記錄',
        'feature_disabled' => '活動記錄功能目前已停用',
        'maintenance_mode' => '活動記錄系統正在維護中',
        'rate_limit_exceeded' => '請求過於頻繁，請稍後再試',
        'invalid_request' => '無效的請求格式',
        'service_unavailable' => '活動記錄服務暫時無法使用',
    ],

    // 活動檢索錯誤
    'retrieval' => [
        'activity_not_found' => '找不到活動記錄',
        'invalid_activity_id' => '無效的活動 ID 格式',
        'access_denied' => '拒絕存取此活動記錄',
        'data_corrupted' => '活動資料似乎已損壞',
        'integrity_check_failed' => '活動完整性驗證失敗',
        'decryption_failed' => '解密活動資料失敗',
        'query_timeout' => '檢索活動時查詢逾時',
        'too_many_results' => '查詢回傳結果過多，請縮小搜尋範圍',
    ],

    // 篩選和搜尋錯誤
    'filtering' => [
        'invalid_date_format' => '無效的日期格式，請使用 YYYY-MM-DD',
        'invalid_date_range' => '無效的日期範圍，結束日期必須晚於開始日期',
        'date_range_too_large' => '日期範圍過大，最大允許範圍為 :days 天',
        'invalid_user_filter' => '無效的使用者篩選值',
        'invalid_type_filter' => '無效的活動類型篩選',
        'invalid_result_filter' => '無效的結果篩選值',
        'invalid_risk_filter' => '無效的風險等級篩選',
        'invalid_ip_format' => '無效的 IP 位址格式',
        'search_query_too_short' => '搜尋查詢至少需要 :min 個字元',
        'search_query_too_long' => '搜尋查詢不能超過 :max 個字元',
        'invalid_regex_pattern' => '無效的正規表達式模式',
        'filter_combination_invalid' => '無效的篩選組合',
    ],

    // 匯出錯誤
    'export' => [
        'export_failed' => '匯出操作失敗',
        'invalid_format' => '指定的匯出格式無效',
        'no_data_to_export' => '沒有可匯出的資料',
        'file_creation_failed' => '建立匯出檔案失敗',
        'file_write_failed' => '寫入匯出資料失敗',
        'compression_failed' => '壓縮匯出檔案失敗',
        'encryption_failed' => '加密匯出檔案失敗',
        'file_size_exceeded' => '匯出檔案大小超過最大限制 :size',
        'disk_space_insufficient' => '磁碟空間不足，無法匯出',
        'export_timeout' => '匯出操作逾時',
        'invalid_columns' => '匯出的欄位選擇無效',
        'email_delivery_failed' => '發送匯出通知電子郵件失敗',
    ],

    // 匯入錯誤
    'import' => [
        'import_failed' => '匯入操作失敗',
        'file_not_found' => '找不到匯入檔案',
        'file_format_invalid' => '無效的匯入檔案格式',
        'file_corrupted' => '匯入檔案似乎已損壞',
        'file_too_large' => '匯入檔案大小超過最大限制 :size',
        'decryption_failed' => '解密匯入檔案失敗',
        'decompression_failed' => '解壓縮匯入檔案失敗',
        'data_validation_failed' => '匯入資料驗證失敗',
        'duplicate_activities' => '匯入資料中發現重複活動',
        'invalid_activity_structure' => '無效的活動資料結構',
        'import_timeout' => '匯入操作逾時',
        'partial_import_failure' => '匯入部分失敗，:success 個成功，:failed 個失敗',
    ],

    // 監控錯誤
    'monitoring' => [
        'monitoring_disabled' => '即時監控已停用',
        'rule_creation_failed' => '建立監控規則失敗',
        'rule_update_failed' => '更新監控規則失敗',
        'rule_deletion_failed' => '刪除監控規則失敗',
        'invalid_rule_conditions' => '無效的監控規則條件',
        'invalid_rule_actions' => '無效的監控規則動作',
        'rule_execution_failed' => '監控規則執行失敗',
        'alert_creation_failed' => '建立安全警報失敗',
        'alert_notification_failed' => '發送警報通知失敗',
        'monitoring_service_error' => '監控服務錯誤',
        'rule_limit_exceeded' => '超過監控規則數量上限',
        'invalid_threshold_value' => '監控規則的閾值無效',
    ],

    // 統計錯誤
    'statistics' => [
        'stats_calculation_failed' => '統計計算失敗',
        'invalid_time_range' => '統計的時間範圍無效',
        'insufficient_data' => '統計分析的資料不足',
        'chart_generation_failed' => '產生統計圖表失敗',
        'data_aggregation_error' => '資料聚合時發生錯誤',
        'memory_limit_exceeded' => '統計計算時超過記憶體限制',
        'stats_cache_error' => '統計快取錯誤',
        'invalid_chart_type' => '指定的圖表類型無效',
    ],

    // 備份和還原錯誤
    'backup' => [
        'backup_creation_failed' => '建立備份失敗',
        'backup_not_found' => '找不到備份檔案',
        'backup_corrupted' => '備份檔案已損壞',
        'backup_decryption_failed' => '解密備份檔案失敗',
        'backup_decompression_failed' => '解壓縮備份檔案失敗',
        'restore_failed' => '還原操作失敗',
        'restore_validation_failed' => '還原資料驗證失敗',
        'backup_verification_failed' => '備份完整性驗證失敗',
        'backup_storage_full' => '備份儲存空間已滿',
        'backup_permission_denied' => '備份操作權限不足',
        'backup_schedule_conflict' => '偵測到備份排程衝突',
        'invalid_backup_format' => '無效的備份檔案格式',
    ],

    // 保留政策錯誤
    'retention' => [
        'cleanup_failed' => '清理操作失敗',
        'invalid_retention_period' => '指定的保留期間無效',
        'cleanup_in_progress' => '另一個清理操作正在進行中',
        'archive_creation_failed' => '清理前建立歸檔失敗',
        'cleanup_permission_denied' => '清理操作權限不足',
        'retention_policy_conflict' => '保留政策配置衝突',
        'cleanup_validation_failed' => '清理驗證失敗',
        'archive_storage_full' => '歸檔儲存空間已滿',
    ],

    // API 錯誤
    'api' => [
        'invalid_api_key' => '無效的 API 金鑰',
        'api_key_expired' => 'API 金鑰已過期',
        'api_key_revoked' => 'API 金鑰已被撤銷',
        'rate_limit_exceeded' => '超過 API 頻率限制',
        'invalid_endpoint' => '無效的 API 端點',
        'method_not_allowed' => '不允許的 HTTP 方法',
        'invalid_parameters' => '無效的 API 參數',
        'authentication_required' => '需要 API 身份驗證',
        'insufficient_permissions' => 'API 權限不足',
        'api_version_unsupported' => '不支援的 API 版本',
        'request_payload_too_large' => '請求負載過大',
        'invalid_content_type' => '無效的內容類型',
    ],

    // 通知錯誤
    'notification' => [
        'notification_failed' => '通知發送失敗',
        'invalid_notification_channel' => '無效的通知管道',
        'notification_rule_failed' => '通知規則執行失敗',
        'email_service_unavailable' => '電子郵件服務無法使用',
        'sms_service_unavailable' => '簡訊服務無法使用',
        'webhook_delivery_failed' => 'Webhook 發送失敗',
        'notification_template_invalid' => '無效的通知模板',
        'recipient_list_empty' => '通知收件人列表為空',
        'notification_rate_limited' => '超過通知頻率限制',
        'notification_queue_full' => '通知佇列已滿',
    ],

    // 安全錯誤
    'security' => [
        'encryption_key_missing' => '缺少加密金鑰',
        'encryption_key_invalid' => '無效的加密金鑰',
        'decryption_key_mismatch' => '解密金鑰不符',
        'signature_verification_failed' => '數位簽章驗證失敗',
        'integrity_violation' => '偵測到資料完整性違規',
        'unauthorized_access_attempt' => '偵測到未授權存取嘗試',
        'suspicious_activity_detected' => '偵測到可疑活動模式',
        'security_policy_violation' => '違反安全政策',
        'access_token_invalid' => '無效的存取權杖',
        'session_expired' => '會話已過期',
        'ip_address_blocked' => 'IP 位址已被封鎖',
        'user_account_locked' => '使用者帳號已被鎖定',
    ],

    // 效能錯誤
    'performance' => [
        'query_timeout' => '資料庫查詢逾時',
        'memory_limit_exceeded' => '超過記憶體限制',
        'cpu_limit_exceeded' => '超過 CPU 使用限制',
        'disk_space_full' => '磁碟空間已滿',
        'connection_pool_exhausted' => '資料庫連線池已耗盡',
        'cache_service_unavailable' => '快取服務無法使用',
        'index_corruption_detected' => '偵測到資料庫索引損壞',
        'partition_limit_exceeded' => '超過資料庫分區限制',
        'optimization_failed' => '效能優化失敗',
        'resource_contention' => '偵測到資源競爭',
    ],

    // 驗證錯誤
    'validation' => [
        'required_field_missing' => '缺少必填欄位：:field',
        'invalid_field_format' => '欄位格式無效：:field',
        'field_too_long' => '欄位 :field 超過最大長度 :max 個字元',
        'field_too_short' => '欄位 :field 至少需要 :min 個字元',
        'invalid_email_format' => '無效的電子郵件地址格式',
        'invalid_url_format' => '無效的網址格式',
        'invalid_json_format' => '無效的 JSON 格式',
        'invalid_timestamp_format' => '無效的時間戳記格式',
        'value_out_of_range' => '欄位 :field 的值超出有效範圍',
        'invalid_enum_value' => '欄位 :field 的值無效，允許的值：:values',
        'duplicate_value' => '偵測到欄位 :field 的重複值',
        'foreign_key_constraint' => '欄位 :field 的外鍵約束違規',
    ],

    // 配置錯誤
    'configuration' => [
        'config_file_missing' => '配置檔案遺失',
        'config_file_corrupted' => '配置檔案已損壞',
        'invalid_config_format' => '無效的配置檔案格式',
        'required_config_missing' => '缺少必要配置：:config',
        'config_validation_failed' => '配置驗證失敗',
        'config_update_failed' => '更新配置失敗',
        'config_backup_failed' => '備份配置失敗',
        'config_restore_failed' => '還原配置失敗',
        'environment_mismatch' => '環境配置不符',
        'dependency_missing' => '缺少必要依賴：:dependency',
    ],

    // 檔案系統錯誤
    'filesystem' => [
        'file_not_found' => '找不到檔案：:file',
        'file_permission_denied' => '檔案權限不足：:file',
        'file_already_exists' => '檔案已存在：:file',
        'directory_not_found' => '找不到目錄：:directory',
        'directory_permission_denied' => '目錄權限不足：:directory',
        'disk_space_insufficient' => '磁碟空間不足',
        'file_size_exceeded' => '檔案大小超過最大限制',
        'file_type_not_allowed' => '不允許的檔案類型：:type',
        'file_upload_failed' => '檔案上傳失敗',
        'file_download_failed' => '檔案下載失敗',
        'file_corruption_detected' => '偵測到檔案損壞',
        'temporary_file_cleanup_failed' => '清理暫存檔案失敗',
    ],

    // 網路錯誤
    'network' => [
        'connection_timeout' => '網路連線逾時',
        'connection_refused' => '網路連線被拒絕',
        'host_unreachable' => '主機無法到達',
        'dns_resolution_failed' => 'DNS 解析失敗',
        'ssl_certificate_invalid' => '無效的 SSL 憑證',
        'ssl_handshake_failed' => 'SSL 握手失敗',
        'proxy_authentication_failed' => '代理伺服器身份驗證失敗',
        'network_unreachable' => '網路無法到達',
        'bandwidth_limit_exceeded' => '超過頻寬限制',
        'request_too_large' => '請求大小過大',
    ],

    // 佇列和工作錯誤
    'queue' => [
        'job_failed' => '背景工作失敗',
        'queue_connection_failed' => '佇列連線失敗',
        'job_timeout' => '工作執行逾時',
        'job_retry_limit_exceeded' => '超過工作重試限制',
        'queue_full' => '工作佇列已滿',
        'invalid_job_payload' => '無效的工作負載',
        'job_serialization_failed' => '工作序列化失敗',
        'job_deserialization_failed' => '工作反序列化失敗',
        'worker_not_available' => '沒有可用的佇列工作者',
        'job_priority_invalid' => '無效的工作優先級',
    ],

];