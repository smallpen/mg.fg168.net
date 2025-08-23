<?php

require_once 'vendor/autoload.php';

use App\Models\Activity;
use App\Models\User;
use App\Services\ActivityIntegrityService;
use App\Services\SensitiveDataFilter;

// 載入 Laravel 應用程式
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== 活動記錄完整性保護系統示範 ===\n\n";

try {
    // 取得服務實例
    $integrityService = app(ActivityIntegrityService::class);
    $sensitiveDataFilter = app(SensitiveDataFilter::class);
    
    echo "1. 測試數位簽章生成\n";
    echo "-------------------\n";
    
    $testData = [
        'type' => 'login',
        'description' => '使用者登入系統',
        'user_id' => 1,
        'ip_address' => '192.168.1.1',
        'created_at' => now()
    ];
    
    $signature = $integrityService->generateSignature($testData);
    echo "生成的數位簽章: {$signature}\n";
    echo "簽章長度: " . strlen($signature) . " 字元\n\n";
    
    echo "2. 測試敏感資料過濾\n";
    echo "-------------------\n";
    
    $sensitiveData = [
        'username' => 'john_doe',
        'password' => 'secret123',
        'email' => 'john@example.com',
        'api_token' => 'abc123def456ghi789',
        'credit_card' => '4111-1111-1111-1111',
        'profile' => [
            'phone' => '0912-345-678',
            'address' => '台北市信義區信義路五段7號',
            'secret_key' => 'very_secret_key_here'
        ]
    ];
    
    echo "原始資料:\n";
    print_r($sensitiveData);
    
    $filteredData = $sensitiveDataFilter->filterProperties($sensitiveData);
    
    echo "\n過濾後的資料:\n";
    print_r($filteredData);
    
    echo "\n3. 測試完整性驗證\n";
    echo "-------------------\n";
    
    // 測試有效簽章
    $validSignature = $integrityService->generateSignature($testData);
    echo "原始資料簽章: {$validSignature}\n";
    
    // 測試篡改檢測
    $tamperedData = $testData;
    $tamperedData['description'] = '篡改後的描述';
    
    $tamperedSignature = $integrityService->generateSignature($tamperedData);
    echo "篡改後資料簽章: {$tamperedSignature}\n";
    
    if ($validSignature !== $tamperedSignature) {
        echo "✓ 成功檢測到資料篡改！\n";
    } else {
        echo "✗ 未能檢測到資料篡改\n";
    }
    
    echo "\n4. 測試敏感欄位識別\n";
    echo "-------------------\n";
    
    $testFields = ['username', 'password', 'email', 'api_token', 'phone', 'address'];
    
    foreach ($testFields as $field) {
        $isSensitive = $sensitiveDataFilter->isSensitiveField($field);
        $status = $isSensitive ? '敏感' : '一般';
        echo "欄位 '{$field}': {$status}\n";
    }
    
    echo "\n5. 測試敏感值檢測\n";
    echo "-------------------\n";
    
    $testValues = [
        'john_doe',
        'john@example.com',
        '4111-1111-1111-1111',
        '0912-345-678',
        '192.168.1.1',
        'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.test'
    ];
    
    foreach ($testValues as $value) {
        $containsSensitive = $sensitiveDataFilter->containsSensitiveData($value);
        $status = $containsSensitive ? '包含敏感資料' : '一般資料';
        echo "值 '{$value}': {$status}\n";
    }
    
    echo "\n=== 示範完成 ===\n";
    
} catch (Exception $e) {
    echo "錯誤: " . $e->getMessage() . "\n";
    echo "堆疊追蹤:\n" . $e->getTraceAsString() . "\n";
}