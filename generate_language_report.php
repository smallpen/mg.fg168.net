<?php

require_once 'vendor/autoload.php';

// 建立 Laravel 應用程式實例
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// 建立語言檔案驗證器
$validator = new App\Services\LanguageFileValidator();

echo "📊 生成詳細的多語系報告...\n\n";

try {
    // 生成完整報告
    $report = $validator->generateReport();
    
    // 儲存報告到檔案
    $reportPath = 'multilingual_audit_report.json';
    file_put_contents($reportPath, json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    
    echo "✅ 詳細報告已儲存至: {$reportPath}\n\n";
    
    // 顯示摘要
    echo "📋 報告摘要:\n";
    echo "生成時間: {$report['timestamp']}\n";
    echo "支援語言: " . implode(', ', $report['supported_locales']) . "\n\n";
    
    $summary = $report['completeness']['summary'];
    echo "📊 統計資訊:\n";
    echo "- 檢查的檔案總數: {$summary['total_files_checked']}\n";
    echo "- 缺少的檔案: {$summary['missing_files_count']}\n";
    echo "- 有缺少鍵值的檔案: {$summary['files_with_missing_keys']}\n";
    echo "- 有額外鍵值的檔案: {$summary['files_with_extra_keys']}\n";
    echo "- 有硬編碼文字的檔案: {$summary['hardcoded_files_count']}\n";
    echo "- 硬編碼文字總數: {$summary['total_hardcoded_instances']}\n\n";
    
    // 顯示最嚴重的硬編碼問題
    echo "🔤 硬編碼文字最多的檔案 (前10個):\n";
    $hardcodedCounts = [];
    foreach ($report['hardcoded_texts'] as $filename => $matches) {
        $hardcodedCounts[$filename] = count($matches);
    }
    arsort($hardcodedCounts);
    $topHardcoded = array_slice($hardcodedCounts, 0, 10, true);
    
    foreach ($topHardcoded as $filename => $count) {
        echo "- {$filename}: {$count} 個硬編碼文字\n";
    }
    echo "\n";
    
    // 顯示缺少翻譯鍵最多的檔案
    echo "🔑 缺少翻譯鍵最多的檔案 (前10個):\n";
    $missingKeyCounts = [];
    foreach ($report['missing_keys'] as $filename => $locales) {
        $totalMissing = 0;
        foreach ($locales as $locale => $keys) {
            $totalMissing += count($keys);
        }
        $missingKeyCounts[$filename] = $totalMissing;
    }
    arsort($missingKeyCounts);
    $topMissing = array_slice($missingKeyCounts, 0, 10, true);
    
    foreach ($topMissing as $filename => $count) {
        echo "- {$filename}: {$count} 個缺少的翻譯鍵\n";
    }
    echo "\n";
    
    // 顯示建議
    echo "💡 建議:\n";
    foreach ($report['recommendations'] as $recommendation) {
        echo "- {$recommendation}\n";
    }
    echo "\n";
    
    // 生成修復優先級清單
    echo "🎯 修復優先級建議:\n";
    echo "1. 高優先級 - 登入頁面和核心功能的硬編碼文字\n";
    echo "2. 中優先級 - 管理後台主要頁面的硬編碼文字\n";
    echo "3. 低優先級 - 測試頁面和示例頁面的硬編碼文字\n";
    echo "4. 語言檔案完整性 - 補充缺少的翻譯鍵\n\n";
    
    // 建立修復計劃檔案
    $fixPlan = [
        'high_priority' => [],
        'medium_priority' => [],
        'low_priority' => []
    ];
    
    foreach ($report['hardcoded_texts'] as $filename => $matches) {
        $count = count($matches);
        
        if (strpos($filename, 'auth/login') !== false || 
            strpos($filename, 'dashboard') !== false ||
            strpos($filename, 'layout') !== false) {
            $fixPlan['high_priority'][$filename] = $count;
        } elseif (strpos($filename, 'admin/') !== false && 
                  strpos($filename, 'test') === false &&
                  strpos($filename, 'demo') === false) {
            $fixPlan['medium_priority'][$filename] = $count;
        } else {
            $fixPlan['low_priority'][$filename] = $count;
        }
    }
    
    // 儲存修復計劃
    $fixPlanPath = 'multilingual_fix_plan.json';
    file_put_contents($fixPlanPath, json_encode($fixPlan, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    
    echo "📋 修復計劃已儲存至: {$fixPlanPath}\n";
    echo "✅ 語言檔案完整性檢查完成！\n";
    
} catch (Exception $e) {
    echo "❌ 生成報告時發生錯誤: " . $e->getMessage() . "\n";
    echo "錯誤位置: " . $e->getFile() . ":" . $e->getLine() . "\n";
}