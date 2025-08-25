<?php

require_once __DIR__ . '/vendor/autoload.php';

use App\Models\Activity;
use App\Models\User;
use App\Services\ActivitySecurityService;
use App\Services\ActivityIntegrityService;
use App\Services\SensitiveDataFilter;

// 載入 Laravel 應用程式
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== 活動記錄安全控制功能測試 ===\n\n";

try {
    // 取得服務實例
    $securityService = app(ActivitySecurityService::class);
    $integrityService = app(ActivityIntegrityService::class);
    $sensitiveDataFilter = app(SensitiveDataFilter::class);
    
    echo "1. 測試敏感資料過濾\n";
    echo str_repeat('-', 40) . "\n";
    
    $sensitiveData = [
        'username' => 'testuser',
        'password' => 'secret123',
        'email' => 'test@example.com',
        'api_key' => 'sk_abc123def456ghi789',
        'credit_card' => '4111-1111-1111-1111',
        'phone' => '0912-345-678',
        'normal_field' => 'normal_value'
    ];
    
    echo "原始資料:\n";
    print_r($sensitiveData);
    
    $filteredData = $sensitiveDataFilter->filterProperties($sensitiveData);
    
    echo "\n過濾後的資料:\n";
    print_r($filteredData);
    
    echo "\n2. 測試敏感欄位識別\n";
    echo str_repeat('-', 40) . "\n";
    
    $testFields = ['password', 'username', 'api_key', 'normal_field', 'secret_token'];
    
    foreach ($testFields as $field) {
        $isSensitive = $sensitiveDataFilter->isSensitiveField($field);
        $status = $isSensitive ? '敏感' : '一般';
        echo "欄位 '{$field}': {$status}\n";
    }
    
    echo "\n3. 測試敏感資料模式檢測\n";
    echo str_repeat('-', 40) . "\n";
    
    $testValues = [
        'test@example.com',
        '4111-1111-1111-1111',
        '192.168.1.1',
        'normal text',
        'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9'
    ];
    
    foreach ($testValues as $value) {
        $containsSensitive = $sensitiveDataFilter->containsSensitiveData($value);
        $status = $containsSensitive ? '包含敏感資料' : '一般資料';
        echo "值 '{$value}': {$status}\n";
    }
    
    echo "\n4. 測試數位簽章生成和驗證\n";
    echo str_repeat('-', 40) . "\n";
    
    $activityData = [
        'type' => 'user_login',
        'description' => 'User logged in successfully',
        'user_id' => 1,
        'ip_address' => '192.168.1.100',
        'created_at' => now()
    ];
    
    // 生成簽章
    $signature = $integrityService->generateSignature($activityData);
    echo "生成的簽章: {$signature}\n";
    
    // 建立活動記錄並驗證
    $activity = new Activity(array_merge($activityData, ['signature' => $signature]));
    $isValid = $integrityService->verifyActivity($activity);
    echo "簽章驗證結果: " . ($isValid ? '有效' : '無效') . "\n";
    
    // 測試篡改檢測
    $activity->type = 'admin_action'; // 篡改資料
    $isValidAfterTampering = $integrityService->verifyActivity($activity);
    echo "篡改後驗證結果: " . ($isValidAfterTampering ? '有效' : '無效') . "\n";
    
    echo "\n5. 測試存取權限檢查\n";
    echo str_repeat('-', 40) . "\n";
    
    // 建立測試使用者（模擬）
    $adminUser = new User([
        'id' => 1,
        'username' => 'admin',
        'name' => '管理員'
    ]);
    
    $regularUser = new User([
        'id' => 2,
        'username' => 'user',
        'name' => '一般使用者'
    ]);
    
    // 模擬權限檢查方法
    $adminUser->hasPermission = function($permission) {
        return in_array($permission, ['activity_logs.view', 'activity_logs.export', 'security.view', 'security.audit']);
    };
    
    $regularUser->hasPermission = function($permission) {
        return in_array($permission, ['activity_logs.view']);
    };
    
    $testActivity = new Activity([
        'id' => 1,
        'type' => 'user_login',
        'user_id' => 2,
        'risk_level' => 3
    ]);
    
    // 測試管理員存取權限
    echo "管理員檢視一般活動記錄: ";
    try {
        $result = $securityService->checkAccessPermission($adminUser, 'view', $testActivity);
        echo $result['allowed'] ? '允許' : '拒絕';
        if (!$result['allowed']) {
            echo " (原因: {$result['reason']})";
        }
        echo "\n";
    } catch (Exception $e) {
        echo "錯誤: " . $e->getMessage() . "\n";
    }
    
    echo "\n6. 測試資料加密和解密\n";
    echo str_repeat('-', 40) . "\n";
    
    // 啟用加密
    config(['activity-security.encryption.enabled' => true]);
    
    $sensitiveActivityData = [
        'description' => 'User password changed',
        'properties' => [
            'old_password' => 'oldpass123',
            'new_password' => 'newpass456'
        ]
    ];
    
    echo "原始資料:\n";
    print_r($sensitiveActivityData);
    
    // 加密
    $encryptedData = $securityService->encryptSensitiveData($sensitiveActivityData);
    echo "\n加密後的資料:\n";
    echo "Description encrypted: " . (isset($encryptedData['description_encrypted']) ? 'Yes' : 'No') . "\n";
    echo "Properties encrypted: " . (isset($encryptedData['properties_encrypted']) ? 'Yes' : 'No') . "\n";
    
    // 解密
    $decryptedData = $securityService->decryptSensitiveData($encryptedData);
    echo "\n解密後的資料:\n";
    print_r($decryptedData);
    
    echo "\n=== 測試完成 ===\n";
    echo "所有安全控制功能已成功測試！\n";
    
} catch (Exception $e) {
    echo "錯誤: " . $e->getMessage() . "\n";
    echo "堆疊追蹤:\n" . $e->getTraceAsString() . "\n";
}