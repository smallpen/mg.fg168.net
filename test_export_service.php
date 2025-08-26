<?php

require_once 'vendor/autoload.php';

// 載入 Laravel 應用程式
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Services\ActivityExportService;

try {
    echo "測試 ActivityExportService...\n";
    
    $exportService = app(ActivityExportService::class);
    echo "服務實例化成功\n";
    
    // 測試匯出配置
    $config = [
        'format' => 'csv',
        'filters' => [
            'date_from' => '2025-08-26',
            'date_to' => '2025-08-26',
        ],
        'options' => [
            'include_user_details' => true,
            'include_properties' => true,
            'include_related_data' => false,
            'batch_size' => 1000,
        ],
        'user_id' => 2, // admin 使用者 ID
    ];
    
    echo "開始測試匯出...\n";
    $result = $exportService->exportDirect($config);
    
    echo "匯出成功！\n";
    echo "檔案名稱: " . $result['filename'] . "\n";
    echo "檔案路徑: " . $result['file_path'] . "\n";
    echo "記錄數量: " . $result['record_count'] . "\n";
    echo "檔案大小: " . $result['file_size'] . " bytes\n";
    
} catch (Exception $e) {
    echo "錯誤: " . $e->getMessage() . "\n";
    echo "檔案: " . $e->getFile() . "\n";
    echo "行號: " . $e->getLine() . "\n";
    echo "堆疊追蹤:\n" . $e->getTraceAsString() . "\n";
}