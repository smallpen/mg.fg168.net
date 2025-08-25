<?php

require_once 'vendor/autoload.php';

// 建立 Laravel 應用程式實例
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// 建立語言檔案驗證器
$validator = new App\Services\LanguageFileValidator();

echo "🔍 開始驗證語言檔案...\n\n";

try {
    // 執行基本驗證
    $result = $validator->validateCompleteness();
    $summary = $result['summary'];
    
    echo "📊 驗證摘要:\n";
    echo "檢查的檔案總數: {$summary['total_files_checked']}\n";
    echo "缺少的檔案: {$summary['missing_files_count']}\n";
    echo "有缺少鍵值的檔案: {$summary['files_with_missing_keys']}\n";
    echo "有額外鍵值的檔案: {$summary['files_with_extra_keys']}\n";
    echo "有硬編碼文字的檔案: {$summary['hardcoded_files_count']}\n";
    echo "硬編碼文字總數: {$summary['total_hardcoded_instances']}\n\n";
    
    // 檢查硬編碼文字
    echo "🔍 檢查硬編碼文字...\n";
    $hardcodedTexts = $validator->detectHardcodedText();
    
    if (!empty($hardcodedTexts)) {
        echo "⚠️  發現硬編碼文字:\n";
        foreach ($hardcodedTexts as $filename => $matches) {
            echo "📄 {$filename}\n";
            foreach (array_slice($matches, 0, 3) as $match) { // 只顯示前3個
                echo "   第 {$match['line']} 行: " . implode(', ', $match['chinese_text']) . "\n";
            }
            if (count($matches) > 3) {
                echo "   ... 還有 " . (count($matches) - 3) . " 個\n";
            }
            echo "\n";
        }
    } else {
        echo "✅ 未發現硬編碼文字\n\n";
    }
    
    // 檢查缺少的翻譯鍵
    echo "🔍 檢查缺少的翻譯鍵...\n";
    $missingKeys = $validator->findMissingKeys();
    
    if (!empty($missingKeys)) {
        echo "⚠️  發現缺少的翻譯鍵:\n";
        foreach ($missingKeys as $filename => $locales) {
            echo "📄 {$filename}\n";
            foreach ($locales as $locale => $keys) {
                echo "   {$locale} 缺少 " . count($keys) . " 個鍵\n";
                foreach (array_slice($keys, 0, 5) as $key) { // 只顯示前5個
                    echo "     - {$key}\n";
                }
                if (count($keys) > 5) {
                    echo "     ... 還有 " . (count($keys) - 5) . " 個\n";
                }
            }
            echo "\n";
        }
    } else {
        echo "✅ 所有語言檔案的翻譯鍵都完整\n\n";
    }
    
    echo "✅ 語言檔案驗證完成！\n";
    
} catch (Exception $e) {
    echo "❌ 驗證過程中發生錯誤: " . $e->getMessage() . "\n";
    echo "錯誤位置: " . $e->getFile() . ":" . $e->getLine() . "\n";
}