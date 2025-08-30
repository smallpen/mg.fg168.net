<?php

namespace App\Console\Commands;

use App\Models\Permission;
use App\Models\PermissionDependency;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class TestPermissionDependencies extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:permission-dependencies {--detailed : 顯示詳細測試結果}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '測試權限依賴關係功能是否正常運行';

    private array $testResults = [];
    private int $passedTests = 0;
    private int $failedTests = 0;

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔍 開始權限依賴關係功能測試...');
        $this->newLine();

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
        $this->info('📋 測試基本依賴關係...');

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

        $this->info('✅ 基本依賴關係測試完成');
        $this->newLine();
    }

    /**
     * 測試循環依賴檢測
     */
    private function testCircularDependencyDetection(): void
    {
        $this->info('🔄 測試循環依賴檢測...');

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

        $this->info('✅ 循環依賴檢測測試完成');
        $this->newLine();
    }

    /**
     * 測試依賴鏈解析
     */
    private function testDependencyChainResolution(): void
    {
        $this->info('🔗 測試依賴鏈解析...');

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

        $this->info('✅ 依賴鏈解析測試完成');
        $this->newLine();
    }

    /**
     * 測試批量操作
     */
    private function testBatchOperations(): void
    {
        $this->info('📦 測試批量操作...');

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

        $this->info('✅ 批量操作測試完成');
        $this->newLine();
    }

    /**
     * 測試快取效能
     */
    private function testCachePerformance(): void
    {
        $this->info('⚡ 測試快取效能...');

        $permission = Permission::where('name', 'users.edit')->first();
        if (!$permission) {
            $this->assert(false, "找不到測試權限");
            return;
        }

        // 清除快取以確保測試準確性
        Cache::forget("permission_all_dependencies_{$permission->id}");

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

        if ($this->option('detailed')) {
            $this->line("  📊 第一次查詢: " . round($firstQueryTime * 1000, 2) . "ms");
            $this->line("  📊 第二次查詢: " . round($secondQueryTime * 1000, 2) . "ms");
            $this->line("  📊 效能提升: " . round((($firstQueryTime - $secondQueryTime) / $firstQueryTime) * 100, 2) . "%");
        }

        $this->info('✅ 快取效能測試完成');
        $this->newLine();
    }

    /**
     * 測試資料完整性
     */
    private function testDataIntegrity(): void
    {
        $this->info('🔍 測試資料完整性...');

        // 測試 1: 檢查孤立的依賴關係
        $orphanedCount = DB::table('permission_dependencies as pd')
                          ->leftJoin('permissions as p1', 'pd.permission_id', '=', 'p1.id')
                          ->leftJoin('permissions as p2', 'pd.depends_on_permission_id', '=', 'p2.id')
                          ->where(function($query) {
                              $query->whereNull('p1.id')->orWhereNull('p2.id');
                          })
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
                                   ->whereRaw('p1.module != p2.module')
                                   ->whereNotIn('p1.name', ['users.assign_roles', 'roles.manage_permissions'])
                                   ->count();
        
        // 跨模組依賴應該很少（除了特殊情況）
        $this->assert($illogicalDependencies <= 2, "跨模組依賴應該很少");

        $this->info('✅ 資料完整性測試完成');
        $this->newLine();
    }

    /**
     * 測試模型方法
     */
    private function testModelMethods(): void
    {
        $this->info('🔧 測試模型方法...');

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

        $this->info('✅ 模型方法測試完成');
        $this->newLine();
    }

    /**
     * 測試複雜場景
     */
    private function testComplexScenarios(): void
    {
        $this->info('🎯 測試複雜場景...');

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

        $this->info('✅ 複雜場景測試完成');
        $this->newLine();
    }

    /**
     * 斷言方法
     */
    private function assert(bool $condition, string $message): void
    {
        if ($condition) {
            if ($this->option('detailed')) {
                $this->line("  ✅ {$message}");
            }
            $this->passedTests++;
        } else {
            $this->line("  ❌ {$message}");
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

        $this->info('📊 測試摘要');
        $this->line('==========================================');
        $this->line("總測試數: {$totalTests}");
        $this->line("通過: {$this->passedTests}");
        $this->line("失敗: {$this->failedTests}");
        $this->line("成功率: {$successRate}%");
        $this->line('==========================================');

        if ($this->failedTests > 0) {
            $this->newLine();
            $this->error('❌ 失敗的測試:');
            foreach ($this->testResults as $result) {
                if (!$result['passed']) {
                    $this->line("  - {$result['message']}");
                }
            }
        }

        $this->newLine();
        if ($successRate >= 90) {
            $this->info('🎉 權限依賴關係功能運行良好！');
        } elseif ($successRate >= 70) {
            $this->warn('⚠️  權限依賴關係功能基本正常，但有一些問題需要修復。');
        } else {
            $this->error('🚨 權限依賴關係功能存在嚴重問題，需要立即修復！');
        }
    }
}