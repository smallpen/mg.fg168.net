<?php

/**
 * 權限安全控制測試腳本
 * 
 * 這個腳本演示權限管理的安全控制功能
 */

require_once 'vendor/autoload.php';

use App\Services\PermissionSecurityService;
use App\Rules\PermissionSecurityRule;
use Illuminate\Validation\ValidationException;

echo "=== 權限安全控制測試 ===\n\n";

// 測試 1: 系統核心權限識別
echo "1. 測試系統核心權限識別:\n";
$securityService = new PermissionSecurityService(
    app(\App\Services\PermissionAuditService::class),
    app(\App\Services\PermissionValidationService::class),
    app(\App\Services\InputValidationService::class)
);

$systemPermissions = $securityService->getSystemCorePermissions();
echo "系統核心權限: " . implode(', ', $systemPermissions) . "\n";

echo "admin.access 是系統核心權限: " . ($securityService->isSystemCorePermission('admin.access') ? '是' : '否') . "\n";
echo "users.view 是系統核心權限: " . ($securityService->isSystemCorePermission('users.view') ? '是' : '否') . "\n\n";

// 測試 2: 高風險操作識別
echo "2. 測試高風險操作識別:\n";
$highRiskOps = $securityService->getHighRiskOperations();
echo "高風險操作: " . implode(', ', $highRiskOps) . "\n";

echo "delete 是高風險操作: " . ($securityService->isHighRiskOperation('delete') ? '是' : '否') . "\n";
echo "view 是高風險操作: " . ($securityService->isHighRiskOperation('view') ? '是' : '否') . "\n\n";

// 測試 3: 權限名稱驗證
echo "3. 測試權限名稱驗證:\n";
$rule = new PermissionSecurityRule('create');

// 測試危險的權限名稱
$dangerousNames = [
    'system.delete.all',
    'admin.destroy.everything',
    'auth.bypass.security',
];

foreach ($dangerousNames as $name) {
    $errors = [];
    $rule->validate('name', $name, function ($message) use (&$errors) {
        $errors[] = $message;
    });
    
    echo "權限名稱 '{$name}': " . (empty($errors) ? '通過' : '被拒絕 - ' . implode(', ', $errors)) . "\n";
}

// 測試有效的權限名稱
$validNames = [
    'users.view',
    'reports.export',
    'dashboard.view',
];

foreach ($validNames as $name) {
    $errors = [];
    $rule->validate('name', $name, function ($message) use (&$errors) {
        $errors[] = $message;
    });
    
    echo "權限名稱 '{$name}': " . (empty($errors) ? '通過' : '被拒絕 - ' . implode(', ', $errors)) . "\n";
}

echo "\n";

// 測試 4: 資料清理
echo "4. 測試資料清理功能:\n";

// 模擬髒資料
$dirtyData = [
    'name' => '  TEST.PERMISSION  ',
    'display_name' => '  測試權限<script>alert("xss")</script>  ',
    'description' => '  這是一個測試權限\x00\x1F  ',
    'module' => '  TEST  ',
    'dependencies' => [1, 2, 2, 3, null, ''],
];

echo "原始資料:\n";
print_r($dirtyData);

// 使用反射來測試私有方法（僅用於演示）
$reflection = new ReflectionClass($securityService);
$sanitizeMethod = $reflection->getMethod('performSecuritySanitization');
$sanitizeMethod->setAccessible(true);

try {
    $cleanData = $sanitizeMethod->invoke($securityService, $dirtyData, 'create');
    echo "\n清理後的資料:\n";
    print_r($cleanData);
} catch (Exception $e) {
    echo "資料清理過程中發生錯誤: " . $e->getMessage() . "\n";
}

echo "\n";

// 測試 5: 風險評分
echo "5. 測試使用者風險評分:\n";
$userId = 1;
echo "使用者 {$userId} 的初始風險評分: " . $securityService->getUserRiskScore($userId) . "\n";

// 模擬設定風險評分
cache()->put("user_risk_score_{$userId}", 50, now()->addHours(24));
echo "設定風險評分後: " . $securityService->getUserRiskScore($userId) . "\n";

// 重置風險評分
$securityService->resetUserRiskScore($userId);
echo "重置後的風險評分: " . $securityService->getUserRiskScore($userId) . "\n";

echo "\n=== 測試完成 ===\n";