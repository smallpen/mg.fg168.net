<?php

return [

    /*
    |--------------------------------------------------------------------------
    | 權限成功訊息
    |--------------------------------------------------------------------------
    |
    | 以下語言行用於權限管理的成功訊息和通知。
    |
    */

    // CRUD 成功訊息
    'crud' => [
        'created' => '權限「:name」已成功建立。',
        'updated' => '權限「:name」已成功更新。',
        'deleted' => '權限「:name」已成功刪除。',
        'activated' => '權限「:name」已成功啟用。',
        'deactivated' => '權限「:name」已成功停用。',
        'duplicated' => '權限「:name」已成功複製為「:new_name」。',
        'restored' => '權限「:name」已成功還原。',
    ],

    // 依賴關係管理訊息
    'dependencies' => [
        'added' => '依賴關係「:dependency」已新增至權限「:permission」。',
        'removed' => '依賴關係「:dependency」已從權限「:permission」移除。',
        'synced' => '權限「:permission」的依賴關係已成功同步。',
        'cleared' => '權限「:permission」的所有依賴關係已清除。',
        'auto_resolved' => '權限「:permission」的依賴關係已自動解析。',
    ],

    // 批量操作訊息
    'bulk' => [
        'deleted' => '已成功刪除 :count 個權限。',
        'activated' => '已成功啟用 :count 個權限。',
        'deactivated' => '已成功停用 :count 個權限。',
        'exported' => '已成功匯出 :count 個權限。',
        'updated' => '已成功更新 :count 個權限。',
        'partial_success' => '批量操作完成：成功 :success 個，失敗 :failed 個。',
        'dependencies_assigned' => '已為 :count 個權限指派依賴關係。',
    ],

    // 匯入/匯出訊息
    'import_export' => [
        'exported' => '權限已成功匯出至「:filename」。',
        'imported' => '已成功匯入 :count 個權限。',
        'import_preview' => '匯入預覽已生成：:create 個待建立，:update 個待更新，:conflicts 個衝突。',
        'conflicts_resolved' => '匯入衝突已成功解決。',
        'backup_created' => '匯入前已建立備份：「:filename」。',
        'import_completed' => '匯入成功完成。建立 :created 個，更新 :updated 個，跳過 :skipped 個。',
    ],

    // 模板訊息
    'templates' => [
        'created' => '權限模板「:name」已成功建立。',
        'updated' => '權限模板「:name」已成功更新。',
        'deleted' => '權限模板「:name」已成功刪除。',
        'applied' => '模板「:name」已成功套用。建立了 :count 個權限。',
        'exported' => '模板「:name」已成功匯出。',
        'imported' => '模板「:name」已成功匯入。',
    ],

    // 測試訊息
    'testing' => [
        'test_completed' => '權限測試已成功完成。',
        'batch_test_completed' => '批量權限測試完成：通過 :passed 個，失敗 :failed 個。',
        'test_results_exported' => '測試結果已匯出至「:filename」。',
        'test_data_cleared' => '測試資料已成功清除。',
        'permission_verified' => '已驗證 :subject 的權限「:permission」。',
        'access_granted' => '存取許可：:subject 擁有權限「:permission」。',
        'access_denied' => '存取拒絕：:subject 沒有權限「:permission」。',
    ],

    // 使用情況分析訊息
    'usage' => [
        'analysis_completed' => '權限使用情況分析已成功完成。',
        'statistics_updated' => '權限使用統計已更新。',
        'cache_refreshed' => '權限使用快取已重新整理。',
        'report_generated' => '使用情況分析報告已生成：「:filename」。',
        'unused_permissions_identified' => '已識別 :count 個未使用的權限。',
    ],

    // 審計訊息
    'audit' => [
        'log_created' => '已為權限「:permission」建立審計日誌項目。',
        'logs_exported' => '審計日誌已匯出至「:filename」。',
        'logs_cleared' => '舊審計日誌已清除。移除了 :count 個項目。',
        'log_archived' => '審計日誌已成功歸檔。',
        'retention_applied' => '審計日誌保留政策已套用。',
    ],

    // 快取訊息
    'cache' => [
        'cleared' => '權限快取已成功清除。',
        'refreshed' => '權限快取已成功重新整理。',
        'warmed' => '權限快取已成功預熱。',
        'optimized' => '權限快取已成功最佳化。',
    ],

    // 系統訊息
    'system' => [
        'maintenance_completed' => '權限系統維護已成功完成。',
        'integrity_check_passed' => '權限系統完整性檢查通過。',
        'database_optimized' => '權限資料庫已最佳化。',
        'indexes_rebuilt' => '權限資料庫索引已重建。',
        'cleanup_completed' => '權限系統清理完成。移除了 :count 個孤立記錄。',
    ],

    // 配置訊息
    'config' => [
        'updated' => '權限配置已成功更新。',
        'reset' => '權限配置已重設為預設值。',
        'validated' => '權限配置驗證通過。',
        'backup_created' => '配置備份已建立：「:filename」。',
        'restored' => '權限配置已從備份還原。',
    ],

    // 搜尋和篩選訊息
    'search' => [
        'results_found' => '找到 :count 個符合搜尋條件的權限。',
        'no_results' => '沒有找到符合搜尋條件的權限。',
        'filters_applied' => '搜尋篩選器已成功套用。',
        'filters_cleared' => '搜尋篩選器已清除。',
        'search_saved' => '搜尋條件已儲存為「:name」。',
    ],

    // 通知訊息
    'notifications' => [
        'permission_created' => '新權限「:name」已建立。',
        'permission_modified' => '權限「:name」已修改。',
        'permission_deleted' => '權限「:name」已刪除。',
        'system_permission_warning' => '系統權限「:name」需要注意。',
        'dependency_conflict' => '權限「:name」中偵測到依賴衝突。',
        'usage_threshold_reached' => '權限「:name」已達到使用閾值。',
    ],

    // 電子郵件訊息
    'email' => [
        'permission_created_subject' => '新權限已建立：:name',
        'permission_deleted_subject' => '權限已刪除：:name',
        'bulk_operation_subject' => '批量權限操作已完成',
        'import_completed_subject' => '權限匯入已完成',
        'system_alert_subject' => '權限系統警報',
    ],

    // API 訊息
    'api' => [
        'permission_retrieved' => '權限資料已成功取得。',
        'permissions_listed' => '權限列表已成功取得。',
        'operation_queued' => '權限操作已排入處理佇列。',
        'batch_processed' => '批量權限操作已成功處理。',
        'sync_completed' => '權限同步已成功完成。',
    ],

    // 一般成功訊息
    'general' => [
        'operation_successful' => '操作成功完成。',
        'changes_saved' => '變更已成功儲存。',
        'action_completed' => '動作成功完成。',
        'request_processed' => '請求已成功處理。',
        'task_finished' => '任務已成功完成。',
        'process_completed' => '程序成功完成。',
    ],

];