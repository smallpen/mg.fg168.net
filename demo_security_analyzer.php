<?php

require_once 'vendor/autoload.php';

use App\Models\Activity;
use App\Models\User;
use App\Services\SecurityAnalyzer;
use Carbon\Carbon;

// 載入 Laravel 應用程式
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== 安全分析器功能演示 ===\n\n";

try {
    $analyzer = new SecurityAnalyzer();
    
    // 1. 演示安全事件類型
    echo "1. 支援的安全事件類型：\n";
    foreach (SecurityAnalyzer::SECURITY_EVENT_TYPES as $type => $description) {
        echo "   - {$type}: {$description}\n";
    }
    echo "\n";
    
    // 2. 演示風險等級
    echo "2. 風險等級定義：\n";
    foreach (SecurityAnalyzer::RISK_LEVELS as $level => $score) {
        echo "   - {$level}: {$score}\n";
    }
    echo "\n";
    
    // 3. 建立測試活動並分析
    echo "3. 建立測試活動並進行安全分析：\n";
    
    // 模擬登入失敗活動
    $testUser = User::first();
    if ($testUser) {
        $loginFailureActivity = Activity::create([
            'type' => 'login',
            'description' => '登入失敗嘗試',
            'result' => 'failed',
            'user_id' => $testUser->id,
            'ip_address' => '10.0.0.1',
            'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)',
            'properties' => ['username' => $testUser->username],
            'created_at' => Carbon::now()
        ]);
        
        echo "   分析登入失敗活動...\n";
        $analysis = $analyzer->analyzeActivity($loginFailureActivity);
        
        echo "   風險評分: {$analysis['risk_score']}\n";
        echo "   風險等級: {$analysis['risk_level']}\n";
        echo "   檢測到的安全事件: " . count($analysis['security_events']) . " 個\n";
        
        if (!empty($analysis['security_events'])) {
            foreach ($analysis['security_events'] as $event) {
                echo "     - {$event['description']} (嚴重程度: {$event['severity']})\n";
            }
        }
        echo "\n";
        
        // 4. 演示權限提升檢測
        echo "4. 權限提升操作分析：\n";
        $privilegeActivity = Activity::create([
            'type' => 'roles.assign',
            'description' => '指派管理員角色給使用者',
            'result' => 'success',
            'user_id' => $testUser->id,
            'ip_address' => '192.168.1.100',
            'properties' => ['target_user' => 'john', 'role' => 'admin'],
            'created_at' => Carbon::now()
        ]);
        
        $privilegeAnalysis = $analyzer->analyzeActivity($privilegeActivity);
        echo "   風險評分: {$privilegeAnalysis['risk_score']}\n";
        echo "   風險等級: {$privilegeAnalysis['risk_level']}\n";
        echo "   安全事件: " . count($privilegeAnalysis['security_events']) . " 個\n";
        echo "\n";
        
        // 5. 演示批量操作檢測
        echo "5. 批量操作分析：\n";
        $bulkActivity = Activity::create([
            'type' => 'users.create',
            'description' => '批量建立使用者帳號',
            'result' => 'success',
            'user_id' => $testUser->id,
            'ip_address' => '192.168.1.100',
            'properties' => ['batch_size' => 25, 'operation' => 'bulk_create'],
            'created_at' => Carbon::now()
        ]);
        
        $bulkAnalysis = $analyzer->analyzeActivity($bulkActivity);
        echo "   風險評分: {$bulkAnalysis['risk_score']}\n";
        echo "   風險等級: {$bulkAnalysis['risk_level']}\n";
        echo "   安全事件: " . count($bulkAnalysis['security_events']) . " 個\n";
        echo "\n";
        
        // 6. 演示深夜操作風險評分
        echo "6. 深夜操作風險分析：\n";
        $nightActivity = Activity::create([
            'type' => 'system.settings',
            'description' => '修改系統設定',
            'result' => 'success',
            'user_id' => $testUser->id,
            'ip_address' => '192.168.1.100',
            'created_at' => Carbon::now()->setHour(2) // 凌晨 2 點
        ]);
        
        $nightAnalysis = $analyzer->analyzeActivity($nightActivity);
        echo "   風險評分: {$nightAnalysis['risk_score']}\n";
        echo "   風險等級: {$nightAnalysis['risk_level']}\n";
        echo "   建議: " . implode(', ', $nightAnalysis['recommendations']) . "\n";
        echo "\n";
        
        // 7. 演示安全警報生成
        echo "7. 安全警報生成：\n";
        if (!empty($analysis['security_events'])) {
            $alert = $analyzer->generateSecurityAlert($loginFailureActivity, $analysis['security_events']);
            if ($alert) {
                echo "   ✓ 成功生成安全警報\n";
                echo "   警報類型: {$alert->type}\n";
                echo "   嚴重程度: {$alert->severity}\n";
                echo "   標題: {$alert->title}\n";
            }
        } else {
            echo "   此活動未觸發安全警報\n";
        }
        echo "\n";
        
    } else {
        echo "   找不到測試使用者，請先執行 database seeder\n";
    }
    
    echo "8. 安全分析器功能總結：\n";
    echo "   ✓ 自動檢測多種安全事件類型\n";
    echo "   ✓ 計算活動風險評分 (0-100)\n";
    echo "   ✓ 識別異常活動模式\n";
    echo "   ✓ 生成安全警報\n";
    echo "   ✓ 監控失敗登入嘗試\n";
    echo "   ✓ 檢測可疑 IP 位址\n";
    echo "   ✓ 分析使用者行為模式\n";
    echo "   ✓ 生成安全報告和建議\n";
    echo "\n";
    
    echo "=== 演示完成 ===\n";
    
} catch (Exception $e) {
    echo "錯誤: " . $e->getMessage() . "\n";
    echo "請確保已正確設定資料庫並執行 migration 和 seeder\n";
}