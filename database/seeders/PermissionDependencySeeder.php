<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\PermissionDependency;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PermissionDependencySeeder extends Seeder
{
    /**
     * 執行資料庫填充
     */
    public function run(): void
    {
        // 清除現有的依賴關係
        PermissionDependency::truncate();

        // 建立邏輯合理的權限依賴關係
        $dependencies = [
            // 使用者管理依賴關係
            'users.view' => ['dashboard.view'],
            'users.create' => ['users.view'],
            'users.edit' => ['users.view'],
            'users.delete' => ['users.edit'],
            'users.assign_roles' => ['users.edit', 'roles.view'],

            // 角色管理依賴關係
            'roles.view' => ['dashboard.view'],
            'roles.create' => ['roles.view'],
            'roles.edit' => ['roles.view'],
            'roles.delete' => ['roles.edit'],
            'roles.manage_permissions' => ['roles.edit', 'permissions.view'],

            // 權限管理依賴關係
            'permissions.view' => ['dashboard.view'],
            'permissions.create' => ['permissions.view'],
            'permissions.edit' => ['permissions.view'],
            'permissions.delete' => ['permissions.edit'],

            // 通知管理依賴關係
            'notifications.view' => ['dashboard.view'],
            'notifications.create' => ['notifications.view'],
            'notifications.edit' => ['notifications.view'],
            'notifications.delete' => ['notifications.edit'],
            'notifications.send' => ['notifications.create'],

            // 活動日誌依賴關係
            'activity_logs.view' => ['dashboard.view'],
            'activity_logs.export' => ['activity_logs.view'],
            'activity_logs.delete' => ['activity_logs.view'],

            // 設定管理依賴關係
            'settings.view' => ['dashboard.view'],
            'settings.edit' => ['settings.view'],
            'settings.backup' => ['settings.edit'],
            'settings.reset' => ['settings.edit'],

            // 系統管理依賴關係
            'system.logs' => ['dashboard.view'],
            'system.maintenance' => ['system.logs'],
            'system.monitor' => ['dashboard.view'],
            'system.security' => ['system.monitor'],

            // 個人資料依賴關係
            'profile.edit' => ['profile.view'],
        ];

        $createdCount = 0;
        $skippedCount = 0;

        foreach ($dependencies as $permissionName => $dependencyNames) {
            $permission = Permission::where('name', $permissionName)->first();
            
            if (!$permission) {
                $this->command->warn("權限不存在: {$permissionName}");
                $skippedCount++;
                continue;
            }

            foreach ($dependencyNames as $dependencyName) {
                $dependency = Permission::where('name', $dependencyName)->first();
                
                if (!$dependency) {
                    $this->command->warn("依賴權限不存在: {$dependencyName}");
                    $skippedCount++;
                    continue;
                }

                // 檢查是否已存在
                $exists = PermissionDependency::where('permission_id', $permission->id)
                                             ->where('depends_on_permission_id', $dependency->id)
                                             ->exists();

                if (!$exists) {
                    PermissionDependency::create([
                        'permission_id' => $permission->id,
                        'depends_on_permission_id' => $dependency->id,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                    
                    $createdCount++;
                    $this->command->info("建立依賴關係: {$permissionName} -> {$dependencyName}");
                } else {
                    $skippedCount++;
                }
            }
        }

        $this->command->info("權限依賴關係建立完成！");
        $this->command->info("建立: {$createdCount} 個依賴關係");
        $this->command->info("跳過: {$skippedCount} 個依賴關係");

        // 驗證依賴關係完整性
        $this->validateDependencies();
    }

    /**
     * 驗證依賴關係完整性
     */
    private function validateDependencies(): void
    {
        $this->command->info("正在驗證依賴關係完整性...");

        // 檢查循環依賴
        $circularDependencies = $this->findCircularDependencies();
        
        if (empty($circularDependencies)) {
            $this->command->info("✅ 沒有發現循環依賴");
        } else {
            $this->command->warn("⚠️  發現 " . count($circularDependencies) . " 個循環依賴");
            foreach ($circularDependencies as $cycle) {
                $this->command->warn("循環: " . implode(' -> ', $cycle));
            }
        }

        // 統計資訊
        $totalDependencies = PermissionDependency::count();
        $permissionsWithDependencies = PermissionDependency::distinct('permission_id')->count();
        $totalPermissions = Permission::count();

        $this->command->info("統計資訊:");
        $this->command->info("- 總依賴關係數: {$totalDependencies}");
        $this->command->info("- 有依賴關係的權限數: {$permissionsWithDependencies}");
        $this->command->info("- 總權限數: {$totalPermissions}");
        $this->command->info("- 依賴關係覆蓋率: " . round(($permissionsWithDependencies / $totalPermissions) * 100, 2) . "%");
    }

    /**
     * 尋找循環依賴
     */
    private function findCircularDependencies(): array
    {
        $dependencies = PermissionDependency::with(['permission', 'dependency'])->get();
        $graph = [];
        
        // 建立依賴圖
        foreach ($dependencies as $dep) {
            $graph[$dep->permission->name][] = $dep->dependency->name;
        }

        $cycles = [];
        $visited = [];
        $recursionStack = [];

        foreach (array_keys($graph) as $node) {
            if (!isset($visited[$node])) {
                $this->detectCycle($node, $graph, $visited, $recursionStack, [], $cycles);
            }
        }

        return $cycles;
    }

    /**
     * 檢測循環依賴
     */
    private function detectCycle($node, $graph, &$visited, &$recursionStack, $path, &$cycles): void
    {
        $visited[$node] = true;
        $recursionStack[$node] = true;
        $path[] = $node;

        if (isset($graph[$node])) {
            foreach ($graph[$node] as $neighbor) {
                if (!isset($visited[$neighbor])) {
                    $this->detectCycle($neighbor, $graph, $visited, $recursionStack, $path, $cycles);
                } elseif (isset($recursionStack[$neighbor]) && $recursionStack[$neighbor]) {
                    // 找到循環
                    $cycleStart = array_search($neighbor, $path);
                    $cycle = array_slice($path, $cycleStart);
                    $cycle[] = $neighbor; // 完成循環
                    $cycles[] = $cycle;
                }
            }
        }

        $recursionStack[$node] = false;
    }
}