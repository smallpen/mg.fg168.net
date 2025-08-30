<?php

/**
 * 權限依賴關係功能測試腳本
 * 
 * 此腳本用於驗證權限依賴關係圖表的各項功能是否正常運行
 */

require_once __DIR__ . '/../vendor/autoload.php';

use App\Models\Permission;
use App\Models\PermissionDependency;
use Illuminate\Support\Facades\DB;

class PermissionDependencyTester
{
    private array $testResults = [];
    private int $passedTests = 0;
    private int $failedTests = 0;

    public function runAllTests(): void
    {
        echo "🔍 開始權限依賴關係功能測試...\n\n";

        $this->testBasicDependencyRelations();
        $this->testCircularDependencyDetection();
        $this->testDependencyChainResolution();
        $this->testBatchOperations();
        $this->testCachePerformance();
        $this->testDataIntegrity();
        $this->testModelMethods();
        $this->testComplexScenarios();

        $this->printSummary();
    }

    /**
     * 測試基本依賴關係
     */
    private function testBasicDependencyRelations(): void
    {
        echo "📋 測試基本依賴關係...\n";

        // 測試 1: 檢查依賴關係是否正確建立
        $usersEdit = Permission::where('name', 'users.edit')->first();
        $usersView = Permission::where('name', 'users.view')->first();
        
        if ($usersEdit && $usersView) {
            $dependsOnUsersView = $usersEdit->dependsOn($usersView);
            $this->assert($dependsOnUsersView, "users.edit 應該依賴 users.view");
        } else {
            $this->assert(false, "找不到測試權限");
        }

        // 測試 2: 檢查反向依賴關係
        if ($usersView) {
            $dependents = $usersView->dependents;
            $hasUsersEdit = $dependents->contains('name', 'users.edit');
            $this->assert($hasUsersEdit, "users.view 應該被 users.edit 依賴");
        }

        // 測試 3: 檢查多層依賴
        $usersDelete = Permission::where('name', 'users.delete')->first();
        if ($usersDelete && $usersEdit) {
            $dependsOnUsersEdit = $usersDelete->dependsOn($usersEdit);
            $this->assert($dependsOnUsersEdit, "users.delete 應該依賴 users.edit");
        }

        echo "✅ 基本依賴關係測試完成\n\n";
    }

    /**
     * 測試循環依賴檢測
     */
    private function testCircularDependencyDetection(): void
    {
        echo "🔄 測試循環依賴檢測...\n";

        // 測試 1: 檢查現有資料是否有循環依賴
        $integrityResult = PermissionDependency::validateIntegrity();
        $this->assert($integrityResult['is_valid'], "現有依賴關係應該沒有循環依賴");

        // 測試 2: 測試循環依賴檢測功能
        $permission1 = Permission::where('name', 'users.view')->first();
        $permission2 = Permission::where('name', 'users.edit')->first();
        
        if ($permission1 && $permission2) {
            // users.edit 已經依賴 users.view，如果讓 users.view 依賴 users.edit 會形成循環
            $wouldCreateCycle = !PermissionDependency::validateNoCycle($permission1->id, $permission2->id);
            $this->assert($wouldCreateCycle, "應該檢測到循環依賴");
        }

        // 測試 3: 測試自我依賴檢測
        if ($permission1) {
            $selfDependency = !PermissionDependency::validateNoCycle($permission1->id, $permission1->id);
            $this->assert($selfDependency, "應該檢測到自我依賴");
        }

        echo "✅ 循環依賴檢測測試完成\n\n";
    }

    /**
     * 測試依賴鏈解析
     */
    private function testDependencyChainResolution(): void
    {
        echo "🔗 測試依賴鏈解析...\n";

        // 測試 1: 檢查直接依賴
        $usersDelete = Permission::where('name', 'users.delete')->first();
        if ($usersDelete) {
            $directDependencies = $usersDelete->dependencies;
            $this->assert($directDependencies->count() > 0, "users.delete 應該有直接依賴");
        }

        // 測試 2: 檢查完整依賴鏈
        if ($usersDelete) {
            $allDependencies = $usersDelete->getAllDependencies();
            $this->assert($allDependencies->count() >= 2, "users.delete 應該有多個間接依賴");
            
            // 應該包含 dashboard.view（透過 users.view）
            $hasDashboardView = $allDependencies->contains('name', 'dashboard.view');
            $this->assert($hasDashboardView, "users.delete 的完整依賴鏈應該包含 dashboard.view");
        }

        // 測試 3: 檢查被依賴鏈
        $dashboardView = Permission::where('name', 'dashboard.view')->first();
        if ($dashboardView) {
            $allDependents = $dashboardView->getAllDependents();
            $this->assert($allDependents->count() > 5, "dashboard.view 應該被多個權限依賴");
        }

        echo "✅ 依賴鏈解析測試完成\n\n";
    }

    /**
     * 測試批量操作
     */
    private function testBatchOperations(): void
    {
        echo "📦 測試批量操作...\n";

        // 測試 1: 批量載入依賴關係
        $permissionIds = Permission::limit(5)->pluck('id')->toArray();
        $batchDependencies = Permission::batchLoadDependencies($permissionIds);
        
        $this->assert(is_array($batchDependencies), "批量載入依賴關係應該返回陣列");
        $this->assert(count($batchDependencies) === count($permissionIds), "批量結果數量應該匹配");

        // 測試 2: 批量載入被依賴關係
        $batchDependents = Permission::batchLoadDependents($permissionIds);
        $this->assert(is_array($batchDependents), "批量載入被依賴關係應該返回陣列");

        // 測試 3: 批量檢查使用狀態
        $batchUsageStatus = Permission::batchCheckUsageStatus($permissionIds);
        $this->assert(is_array($batchUsageStatus), "批量檢查使用狀態應該返回陣列");

        echo "✅ 批量操作測試完成\n\n";
    }

    /**
     * 測試快取效能
     */
    private function testCachePerformance(): void
    {
        echo "⚡ 測試快取效能...\n";

        $permission = Permission::where('name', 'users.edit')->first();
        if (!$permission) {
            $this->assert(false, "找不到測試權限");
            return;
        }

        // 測試 1: 第一次查詢（建立快取）
        $startTime = microtime(true);
        $dependencies1 = $permission->getAllDependencies();
        $firstQueryTime = microtime(true) - $startTime;

        // 測試 2: 第二次查詢（使用快取）
        $startTime = microtime(true);
        $dependencies2 = $permission->getAllDependencies();
        $secondQueryTime = microtime(true) - $startTime;

        // 快取應該讓第二次查詢更快
        $this->assert($secondQueryTime < $firstQueryTime, "快取應該提升查詢效能");
        $this->assert($dependencies1->count() === $dependencies2->count(), "快取結果應該一致");

        echo "  📊 第一次查詢: " . round($firstQueryTime * 1000, 2) . "ms\n";
        echo "  📊 第二次查詢: " . round($secondQueryTime * 1000, 2) . "ms\n";
        echo "  📊 效能提升: " . round((($firstQueryTime - $secondQueryTime) / $firstQueryTime) * 100, 2) . "%\n";

        echo "✅ 快取效能測試完成\n\n";
    }

    /**
     * 測試資料完整性
     */
    private function testDataIntegrity(): void
    {
        echo "🔍 測試資料完整性...\n";

        // 測試 1: 檢查孤立的依賴關係
        $orphanedCount = DB::table('permission_dependencies as pd')
                          ->leftJoin('permissions as p1', 'pd.permission_id', '=', 'p1.id')
                          ->leftJoin('permissions as p2', 'pd.depends_on_permission_id', '=', 'p2.id')
                          ->whereNull('p1.id')
                          ->orWhereNull('p2.id')
                          ->count();
        
        $this->assert($orphanedCount === 0, "不應該有孤立的依賴關係");

        // 測試 2: 檢查重複的依賴關係
        $duplicateCount = DB::table('permission_dependencies')
                           ->select('permission_id', 'depends_on_permission_id')
                           ->groupBy('permission_id', 'depends_on_permission_id')
                           ->havingRaw('COUNT(*) > 1')
                           ->count();
        
        $this->assert($duplicateCount === 0, "不應該有重複的依賴關係");

        // 測試 3: 檢查依賴關係的邏輯性
        $illogicalDependencies = DB::table('permission_dependencies as pd')
                                   ->join('permissions as p1', 'pd.permission_id', '=', 'p1.id')
                                   ->join('permissions as p2', 'pd.depends_on_permission_id', '=', 'p2.id')
                                   ->where('p1.module', '!=', 'dashboard')
                                   ->where('p2.module', '!=', 'dashboard')
                                   ->where('p1.module', '!=', p2.module)
                                   ->whereNotIn('p1.name', ['users.assign_roles', 'roles.manage_permissions'])
                                   ->count();
        
        // 跨模組依賴應該很少（除了特殊情況）
        $this->assert($illogicalDependencies <= 2, "跨模組依賴應該很少");

        echo "✅ 資料完整性測試完成\n\n";
    }

    /**
     * 測試模型方法
     */
    private function testModelMethods(): void
    {
        echo "🔧 測試模型方法...\n";

        $permission = Permission::where('name', 'users.edit')->first();
        if (!$permission) {
            $this->assert(false, "找不到測試權限");
            return;
        }

        // 測試 1: hasDependencies 方法
        $hasDependencies = $permission->hasDependencies();
        $this->assert($hasDependencies, "users.edit 應該有依賴");

        // 測試 2: hasDependents 方法
        $hasDependents = $permission->hasDependents();
        $this->assert($hasDependents, "users.edit 應該被其他權限依賴");

        // 測試 3: getDirectDependencies 靜態方法
        $directDeps = PermissionDependency::getDirectDependencies($permission->id);
        $this->assert(is_array($directDeps), "getDirectDependencies 應該返回陣列");
        $this->assert(count($directDeps) > 0, "users.edit 應該有直接依賴");

        // 測試 4: getDirectDependents 靜態方法
        $directDependents = PermissionDependency::getDirectDependents($permission->id);
        $this->assert(is_array($directDependents), "getDirectDependents 應該返回陣列");

        echo "✅ 模型方法測試完成\n\n";
    }

    /**
     * 測試複雜場景
     */
    private function testComplexScenarios(): void
    {
        echo "🎯 測試複雜場景...\n";

        // 測試 1: 深層依賴鏈
        $usersDelete = Permission::where('name', 'users.delete')->first();
        if ($usersDelete) {
            $fullTree = PermissionDependency::getFullDependencyTree($usersDelete->id);
            $this->assert(is_array($fullTree), "依賴樹應該是陣列");
            $this->assert(count($fullTree) > 0, "users.delete 應該有依賴樹");
        }

        // 測試 2: 權限統計摘要
        $statsSummary = Permission::getStatsSummary();
        $this->assert(isset($statsSummary['total_permissions']), "統計摘要應該包含總權限數");
        $this->assert(isset($statsSummary['modules']), "統計摘要應該包含模組統計");
        $this->assert($statsSummary['total_permissions'] > 30, "總權限數應該大於 30");

        // 測試 3: 優化搜尋功能
        $searchResults = Permission::searchOptimized('users', ['limit' => 10]);
        $this->assert($searchResults->count() > 0, "搜尋 'users' 應該有結果");
        $this->assert($searchResults->count() <= 10, "搜尋結果應該受限制");

        // 測試 4: 依賴路徑查找
        $dashboardView = Permission::where('name', 'dashboard.view')->first();
        if ($usersDelete && $dashboardView) {
            $hasPath = PermissionDependency::hasDependencyPath($usersDelete->id, $dashboardView->id);
            $this->assert($hasPath, "users.delete 應該有到 dashboard.view 的依賴路徑");
            
            $path = PermissionDependency::getDependencyPath($usersDelete->id, $dashboardView->id);
            $this->assert(is_array($path), "依賴路徑應該是陣列");
            $this->assert(count($path) > 2, "依賴路徑應該包含多個節點");
        }

        echo "✅ 複雜場景測試完成\n\n";
    }

    /**
     * 斷言方法
     */
    private function assert(bool $condition, string $message): void
    {
        if ($condition) {
            echo "  ✅ {$message}\n";
            $this->passedTests++;
        } else {
            echo "  ❌ {$message}\n";
            $this->failedTests++;
        }
        
        $this->testResults[] = [
            'passed' => $condition,
            'message' => $message
        ];
    }

    /**
     * 列印測試摘要
     */
    private function printSummary(): void
    {
        $totalTests = $this->passedTests + $this->failedTests;
        $successRate = $totalTests > 0 ? round(($this->passedTests / $totalTests) * 100, 2) : 0;

        echo "📊 測試摘要\n";
        echo "==========================================\n";
        echo "總測試數: {$totalTests}\n";
        echo "通過: {$this->passedTests}\n";
        echo "失敗: {$this->failedTests}\n";
        echo "成功率: {$successRate}%\n";
        echo "==========================================\n";

        if ($this->failedTests > 0) {
            echo "\n❌ 失敗的測試:\n";
            foreach ($this->testResults as $result) {
                if (!$result['passed']) {
                    echo "  - {$result['message']}\n";
                }
            }
        }

        if ($successRate >= 90) {
            echo "\n🎉 權限依賴關係功能運行良好！\n";
        } elseif ($successRate >= 70) {
            echo "\n⚠️  權限依賴關係功能基本正常，但有一些問題需要修復。\n";
        } else {
            echo "\n🚨 權限依賴關係功能存在嚴重問題，需要立即修復！\n";
        }
    }
}

// 執行測試
if (php_sapi_name() === 'cli') {
    $tester = new PermissionDependencyTester();
    $tester->runAllTests();
}