<?php

return [

    /*
    |--------------------------------------------------------------------------
    | 權限管理語言檔案
    |--------------------------------------------------------------------------
    |
    | 以下語言行用於權限管理系統的各種元素和訊息。
    |
    */

    // 標題
    'titles' => [
        'permission_management' => '權限管理',
    ],

    // 基本標籤
    'permission_name' => '權限名稱',
    'display_name' => '顯示名稱',
    'description' => '描述',
    'module' => '模組',
    'type' => '類型',
    'roles_count' => '角色數量',
    'usage_status' => '使用狀態',
    'created_at' => '建立時間',
    'actions_label' => '操作',

    // 搜尋和篩選
    'search_label' => '搜尋權限',
    'search' => [
        'search_placeholder' => '搜尋權限名稱、顯示名稱或描述...',
        'all_usage' => '全部使用狀態',
        'used' => '已使用',
        'unused' => '未使用',
    ],

    'filter_by_module' => '模組篩選',
    'filter_by_type' => '類型篩選',
    'filter_by_usage' => '使用狀態篩選',
    'all_modules' => '全部模組',
    'all_types' => '全部類型',
    'clear_filters' => '清除篩選',

    // 檢視模式
    'view_mode' => '檢視模式',
    'view_modes' => [
        'list' => '列表檢視',
        'grouped' => '分組檢視',
        'tree' => '樹狀檢視',
    ],

    // 操作按鈕
    'create_permission' => '建立權限',
    'create_first_permission' => '建立第一個權限',
    'edit' => '編輯',
    'delete' => '刪除',
    'export' => '匯出',
    'import' => '匯入',
    'more_actions' => '更多操作',
    'view_dependencies' => '檢視依賴關係',
    'view_usage' => '檢視使用情況',

    // 批量操作
    'selected_permissions' => '已選擇 :count 個權限',
    'cancel_selection' => '取消選擇',

    // 狀態和標籤
    'status' => [
        'used' => '已使用',
        'unused' => '未使用',
    ],

    'types' => [
        'view' => '檢視',
        'create' => '建立',
        'edit' => '編輯',
        'delete' => '刪除',
        'manage' => '管理',
        'export' => '匯出',
        'import' => '匯入',
        'test' => '測試',
    ],

    // 統計資訊
    'usage' => [
        'total_permissions' => '總權限數',
        'used_permissions' => '已使用權限',
        'unused_permissions' => '未使用權限',
        'usage_frequency' => '使用頻率',
    ],

    // 空狀態
    'no_permissions_found' => '找不到權限',
    'no_permissions_description' => '請嘗試調整搜尋條件或建立新的權限',

    // 載入狀態
    'loading' => '載入中...',

    // 訊息
    'messages' => [
        'no_permission_create' => '您沒有建立權限的權限',
        'no_permission_edit' => '您沒有編輯權限的權限',
        'no_permission_delete' => '您沒有刪除權限的權限',
        'permission_not_found' => '找不到指定的權限',
        'cannot_delete_permission' => '無法刪除此權限',
    ],

];