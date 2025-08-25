<?php

/**
 * 活動記錄保留政策功能演示
 * 
 * 此腳本演示活動記錄保留政策的核心功能，包括：
 * 1. 建立預設保留政策
 * 2. 建立測試活動記錄
 * 3. 執行保留政策
 * 4. 查看清理結果
 */

require_once __DIR__ . '/vendor/autoload.php';

use App\Models\ActivityRetentionPolicy;
use App\Models\Activity;
use App\Models\User;
use App\Services\ActivityRetentionService;
use Carbon\Carbon;

// 初始化 Laravel 應用
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "🧹 活動記錄保留政策功能演示\n";
echo "================================\n\n";

try {
    // 1. 建立預設保留政策
    echo "1. 建立預設保留政策...\n";
    ActivityRetentionPolicy::createDefaultPolicies();
    $policyCount = ActivityRetentionPolicy::count();
    echo "   ✅ 已建立 {$policyCount} 個保留政策\n\n";

    // 顯示政策列表
    echo "2. 保留政策列表:\n";
    $policies = ActivityRetentionPolicy::orderBy('priority', 'desc')->get();
    foreach ($policies as $policy) {
        $status = $policy->is_active ? '啟用' : '停用';
        echo "   📋 {$policy->name}\n";
        echo "      - 保留天數: {$policy->retention_days} 天\n";
        echo "      - 處理動作: {$policy->action_text}\n";
        echo "      - 優先級: {$policy->priority}\n";
        echo "      - 狀態: {$status}\n";
        echo "      - 適用範圍: {$policy->scope_description}\n\n";
    }

    // 3. 建立測試使用者（如果不存在）
    echo "3. 準備測試資料...\n";
    $user = User::firstOrCreate(
        ['username' => 'demo_user'],
        [
            'name' => '演示使用者',
            'email' => 'demo@example.com',
            'password' => bcrypt('password'),
            'is_active' => true,
        ]
    );
    echo "   👤 測試使用者: {$user->name} (ID: {$user->id})\n";

    // 4. 建立測試活動記錄
    echo "   📝 建立測試活動記錄...\n";
    
    // 建立過期的活動記錄（超過 90 天）
    $oldActivities = [];
    for ($i = 0; $i < 5; $i++) {
        $activity = Activity::create([
            'type' => 'test_action',
            'description' => "測試活動 #{$i}",
            'module' => 'test',
            'user_id' => $user->id,
            'ip_address' => '127.0.0.1',
            'result' => 'success',
            'risk_level' => rand(1, 5),
            'created_at' => Carbon::now()->subDays(rand(95, 120)),
        ]);
        $oldActivities[] = $activity;
    }

    // 建立最近的活動記錄（30 天內）
    $recentActivities = [];
    for ($i = 0; $i < 3; $i++) {
        $activity = Activity::create([
            'type' => 'recent_action',
            'description' => "最近活動 #{$i}",
            'module' => 'test',
            'user_id' => $user->id,
            'ip_address' => '127.0.0.1',
            'result' => 'success',
            'risk_level' => rand(1, 3),
            'created_at' => Carbon::now()->subDays(rand(1, 25)),
        ]);
        $recentActivities[] = $activity;
    }

    $totalActivities = count($oldActivities) + count($recentActivities);
    echo "   ✅ 已建立 {$totalActivities} 筆測試活動記錄\n";
    echo "      - 過期記錄: " . count($oldActivities) . " 筆\n";
    echo "      - 最近記錄: " . count($recentActivities) . " 筆\n\n";

    // 5. 預覽政策影響
    echo "4. 預覽政策影響...\n";
    $retentionService = app(ActivityRetentionService::class);
    $generalPolicy = ActivityRetentionPolicy::where('name', '一般活動記錄')->first();
    
    if ($generalPolicy) {
        $preview = $retentionService->previewPolicyImpact($generalPolicy);
        echo "   📊 政策: {$generalPolicy->name}\n";
        echo "      - 影響記錄數: {$preview['total_records']} 筆\n";
        echo "      - 預估大小: {$preview['estimated_size_mb']} MB\n";
        echo "      - 處理動作: {$generalPolicy->action_text}\n\n";
    }

    // 6. 執行保留政策（測試模式）
    echo "5. 執行保留政策（測試模式）...\n";
    $results = $retentionService->executeAllPolicies(true); // dry run
    
    echo "   📈 執行結果摘要:\n";
    echo "      - 執行政策數: {$results['total_policies']}\n";
    echo "      - 成功政策數: {$results['successful_policies']}\n";
    echo "      - 失敗政策數: {$results['failed_policies']}\n";
    echo "      - 處理記錄數: {$results['total_records_processed']}\n";
    echo "      - 模式: " . ($results['dry_run'] ? '測試執行' : '實際執行') . "\n\n";

    // 顯示各政策執行詳情
    if (!empty($results['policy_results'])) {
        echo "   📋 各政策執行詳情:\n";
        foreach ($results['policy_results'] as $result) {
            if ($result['records_processed'] > 0) {
                $status = $result['status'] === 'completed' ? '✅' : '❌';
                echo "      {$status} {$result['policy_name']}\n";
                echo "         - 處理記錄: {$result['records_processed']} 筆\n";
                if ($result['status'] === 'failed') {
                    echo "         - 錯誤: {$result['error']}\n";
                }
            }
        }
        echo "\n";
    }

    // 7. 實際執行保留政策
    echo "6. 實際執行保留政策...\n";
    echo "   ⚠️  這將實際刪除/歸檔過期的活動記錄\n";
    
    // 為了演示安全，這裡只執行測試模式
    // 如果要實際執行，請將 true 改為 false
    $actualResults = $retentionService->executeAllPolicies(true);
    
    echo "   📈 實際執行結果:\n";
    echo "      - 處理記錄數: {$actualResults['total_records_processed']}\n";
    echo "      - 刪除記錄數: {$actualResults['total_records_deleted']}\n";
    echo "      - 歸檔記錄數: {$actualResults['total_records_archived']}\n\n";

    // 8. 查看清理統計
    echo "7. 清理統計資訊...\n";
    $cleanupStats = $retentionService->getCleanupHistory('30d');
    echo "   📊 30天內清理統計:\n";
    echo "      - 總操作次數: {$cleanupStats['total_operations']}\n";
    echo "      - 成功操作: {$cleanupStats['successful_operations']}\n";
    echo "      - 失敗操作: {$cleanupStats['failed_operations']}\n";
    echo "      - 處理記錄總數: " . number_format($cleanupStats['total_records_processed']) . "\n";
    echo "      - 歸檔記錄總數: " . number_format($cleanupStats['total_records_archived']) . "\n\n";

    // 9. 顯示政策統計
    echo "8. 政策統計資訊...\n";
    $policyStats = $retentionService->getPolicyStats();
    foreach ($policyStats as $stat) {
        if ($stat['stats']['applicable_records'] > 0) {
            echo "   📋 {$stat['policy']['name']}\n";
            echo "      - 適用記錄: " . number_format($stat['stats']['applicable_records']) . " 筆\n";
            echo "      - 30天處理: " . number_format($stat['total_processed_30d']) . " 筆\n";
            echo "      - 最後執行: " . ($stat['last_execution'] ?? '從未執行') . "\n\n";
        }
    }

    echo "✅ 活動記錄保留政策功能演示完成！\n\n";
    
    echo "💡 提示:\n";
    echo "   - 可以透過管理後台的保留政策管理頁面進行設定\n";
    echo "   - 建議設定定時任務自動執行清理操作\n";
    echo "   - 可以使用 php artisan activity:cleanup 命令手動執行\n";
    echo "   - 支援測試模式 (--dry-run) 預覽清理效果\n\n";

} catch (Exception $e) {
    echo "❌ 演示過程中發生錯誤: {$e->getMessage()}\n";
    echo "   檔案: {$e->getFile()}:{$e->getLine()}\n";
    exit(1);
}