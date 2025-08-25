<?php

/**
 * 多語系功能測試演示腳本
 * 
 * 這個腳本演示如何執行基本的多語系功能測試
 * 可以立即執行以驗證多語系功能的基本運作
 */

echo "🚀 多語系功能測試演示\n";
echo "時間: " . date('Y-m-d H:i:s') . "\n";
echo str_repeat("=", 50) . "\n\n";

// 測試結果記錄
$testResults = [];
$totalTests = 0;
$passedTests = 0;

/**
 * 執行測試並記錄結果
 */
function runTest($testName, $testFunction) {
    global $testResults, $totalTests, $passedTests;
    
    $totalTests++;
    echo "🧪 {$testName}... ";
    
    try {
        $result = $testFunction();
        if ($result) {
            $passedTests++;
            echo "✅ 通過\n";
            $testResults[] = ['name' => $testName, 'status' => 'passed'];
        } else {
            echo "❌ 失敗\n";
            $testResults[] = ['name' => $testName, 'status' => 'failed', 'reason' => '測試條件不滿足'];
        }
    } catch (Exception $e) {
        echo "❌ 錯誤: " . $e->getMessage() . "\n";
        $testResults[] = ['name' => $testName, 'status' => 'error', 'reason' => $e->getMessage()];
    }
}

// 1. 檢查語言檔案存在性
runTest('檢查正體中文語言檔案', function() {
    return is_dir('lang/zh_TW') && file_exists('lang/zh_TW/auth.php');
});

runTest('檢查英文語言檔案', function() {
    return is_dir('lang/en') && file_exists('lang/en/auth.php');
});

// 2. 檢查語言檔案內容
runTest('檢查中文 auth.php 語言檔案格式', function() {
    if (!file_exists('lang/zh_TW/auth.php')) return false;
    
    $content = include 'lang/zh_TW/auth.php';
    return is_array($content) && isset($content['failed']);
});

runTest('檢查英文 auth.php 語言檔案格式', function() {
    if (!file_exists('lang/en/auth.php')) return false;
    
    $content = include 'lang/en/auth.php';
    return is_array($content) && isset($content['failed']);
});

// 3. 檢查語言檔案完整性
runTest('比較中英文語言檔案鍵值完整性', function() {
    if (!file_exists('lang/zh_TW/auth.php') || !file_exists('lang/en/auth.php')) {
        return false;
    }
    
    $zhContent = include 'lang/zh_TW/auth.php';
    $enContent = include 'lang/en/auth.php';
    
    $zhKeys = array_keys($zhContent);
    $enKeys = array_keys($enContent);
    
    // 檢查鍵值是否一致
    $missingInEn = array_diff($zhKeys, $enKeys);
    $missingInZh = array_diff($enKeys, $zhKeys);
    
    return empty($missingInEn) && empty($missingInZh);
});

// 4. 檢查主題相關語言檔案
runTest('檢查主題切換語言檔案', function() {
    $zhThemeExists = file_exists('lang/zh_TW/theme.php');
    $enThemeExists = file_exists('lang/en/theme.php');
    
    if (!$zhThemeExists || !$enThemeExists) {
        return false;
    }
    
    $zhTheme = include 'lang/zh_TW/theme.php';
    $enTheme = include 'lang/en/theme.php';
    
    return isset($zhTheme['toggle_dark_mode']) && isset($enTheme['toggle_dark_mode']);
});

// 5. 檢查設定檔案
runTest('檢查應用程式語言設定', function() {
    if (!file_exists('config/app.php')) return false;
    
    $content = file_get_contents('config/app.php');
    // 檢查是否包含 locale 設定
    return strpos($content, "'locale'") !== false;
});

// 6. 檢查中介軟體檔案
runTest('檢查 SetLocale 中介軟體', function() {
    return file_exists('app/Http/Middleware/SetLocale.php');
});

// 7. 檢查語言檔案內容差異
runTest('驗證中英文翻譯內容不同', function() {
    if (!file_exists('lang/zh_TW/auth.php') || !file_exists('lang/en/auth.php')) {
        return false;
    }
    
    $zhContent = include 'lang/zh_TW/auth.php';
    $enContent = include 'lang/en/auth.php';
    
    // 檢查 'failed' 鍵的內容是否不同
    if (!isset($zhContent['failed']) || !isset($enContent['failed'])) {
        return false;
    }
    
    return $zhContent['failed'] !== $enContent['failed'];
});

// 8. 檢查視圖檔案中的翻譯使用
runTest('檢查登入視圖是否使用翻譯函數', function() {
    $loginViewPath = 'resources/views/admin/auth/login.blade.php';
    
    if (!file_exists($loginViewPath)) {
        return false;
    }
    
    $content = file_get_contents($loginViewPath);
    
    // 檢查是否使用了 __() 或 @lang() 翻譯函數
    return strpos($content, '__(') !== false || strpos($content, '@lang(') !== false;
});

// 9. 檢查語言選擇器元件
runTest('檢查語言選擇器元件', function() {
    $componentPaths = [
        'resources/views/components/language-selector.blade.php',
        'resources/views/livewire/language-selector.blade.php',
        'app/Livewire/LanguageSelector.php'
    ];
    
    foreach ($componentPaths as $path) {
        if (file_exists($path)) {
            return true;
        }
    }
    
    return false;
});

// 10. 檢查資料庫遷移檔案
runTest('檢查使用者語言偏好欄位', function() {
    $migrationFiles = glob('database/migrations/*_add_locale_to_users_table.php');
    
    if (empty($migrationFiles)) {
        // 檢查是否在建立使用者表格的遷移中包含了 locale 欄位
        $userMigrations = glob('database/migrations/*_create_users_table.php');
        
        if (!empty($userMigrations)) {
            $content = file_get_contents($userMigrations[0]);
            return strpos($content, 'locale') !== false;
        }
        
        return false;
    }
    
    return true;
});

echo "\n" . str_repeat("=", 50) . "\n";
echo "📊 測試結果摘要\n";
echo str_repeat("=", 50) . "\n";

$passRate = $totalTests > 0 ? round($passedTests / $totalTests * 100, 1) : 0;

echo "總測試數: {$totalTests}\n";
echo "通過測試: {$passedTests}\n";
echo "失敗測試: " . ($totalTests - $passedTests) . "\n";
echo "通過率: {$passRate}%\n\n";

// 顯示詳細結果
echo "📋 詳細測試結果:\n";
foreach ($testResults as $result) {
    $status = $result['status'] === 'passed' ? '✅' : '❌';
    echo "  {$status} {$result['name']}";
    
    if (isset($result['reason'])) {
        echo " - {$result['reason']}";
    }
    
    echo "\n";
}

echo "\n";

// 根據結果給出建議
if ($passRate >= 90) {
    echo "🎉 優秀！多語系基礎設施配置完整。\n";
} elseif ($passRate >= 70) {
    echo "⚠️ 良好，但仍有改進空間。\n";
} else {
    echo "❌ 需要重點關注多語系功能的配置。\n";
}

// 給出具體建議
echo "\n💡 建議事項:\n";

$failedTests = array_filter($testResults, function($test) {
    return $test['status'] !== 'passed';
});

if (empty($failedTests)) {
    echo "  - 所有基礎檢查都通過了，可以進行更深入的功能測試\n";
    echo "  - 建議執行完整的 MCP 整合測試: php run-comprehensive-multilingual-tests.php\n";
} else {
    foreach ($failedTests as $test) {
        switch ($test['name']) {
            case '檢查正體中文語言檔案':
            case '檢查英文語言檔案':
                echo "  - 確保語言檔案目錄和基本檔案存在\n";
                break;
            case '比較中英文語言檔案鍵值完整性':
                echo "  - 檢查並同步中英文語言檔案的翻譯鍵\n";
                break;
            case '檢查主題切換語言檔案':
                echo "  - 建立主題相關的語言檔案\n";
                break;
            case '檢查 SetLocale 中介軟體':
                echo "  - 確保 SetLocale 中介軟體已正確建立\n";
                break;
            case '檢查登入視圖是否使用翻譯函數':
                echo "  - 更新視圖檔案以使用翻譯函數而非硬編碼文字\n";
                break;
            case '檢查語言選擇器元件':
                echo "  - 建立語言選擇器元件\n";
                break;
            case '檢查使用者語言偏好欄位':
                echo "  - 在使用者表格中加入 locale 欄位\n";
                break;
        }
    }
}

echo "\n📚 更多資訊請參考: COMPREHENSIVE_MULTILINGUAL_TESTING_GUIDE.md\n";
echo str_repeat("=", 50) . "\n";