<?php

namespace App\Console\Commands;

use App\Services\PermissionUsageAnalysisService;
use App\Services\AuditLogService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * 權限使用情況分析命令
 * 
 * 定期分析權限使用情況並生成報告
 */
class AnalyzePermissionUsage extends Command
{
    /**
     * 命令名稱和簽名
     *
     * @var string
     */
    protected $signature = 'permissions:analyze-usage 
                           {--days=90 : 未使用天數閾值}
                           {--exclude-system : 排除系統權限}
                           {--auto-mark : 自動標記未使用權限}
                           {--report : 生成詳細報告}
                           {--clear-cache : 清除分析快取}';

    /**
     * 命令描述
     *
     * @var string
     */
    protected $description = '分析權限使用情況，標記未使用權限並生成報告';

    protected PermissionUsageAnalysisService $usageAnalysisService;
    protected AuditLogService $auditService;

    /**
     * 建構函式
     */
    public function __construct(
        PermissionUsageAnalysisService $usageAnalysisService,
        AuditLogService $auditService
    ) {
        parent::__construct();
        $this->usageAnalysisService = $usageAnalysisService;
        $this->auditService = $auditService;
    }

    /**
     * 執行命令
     */
    public function handle(): int
    {
        $this->info('開始分析權限使用情況...');
        
        try {
            // 清除快取（如果指定）
            if ($this->option('clear-cache')) {
                $this->clearCache();
            }
            
            // 取得選項
            $daysThreshold = (int) $this->option('days');
            $excludeSystem = $this->option('exclude-system');
            $autoMark = $this->option('auto-mark');
            $generateReport = $this->option('report');
            
            // 執行分析
            $this->performAnalysis($daysThreshold, $excludeSystem, $autoMark, $generateReport);
            
            $this->info('權限使用情況分析完成！');
            
            // 記錄命令執行
            $this->auditService->logDataAccess('permissions', 'usage_analysis_completed', [
                'days_threshold' => $daysThreshold,
                'exclude_system' => $excludeSystem,
                'auto_mark' => $autoMark,
                'generate_report' => $generateReport,
            ]);
            
            return Command::SUCCESS;
            
        } catch (\Exception $e) {
            $this->error('分析過程中發生錯誤: ' . $e->getMessage());
            Log::error('Permission usage analysis failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            return Command::FAILURE;
        }
    }

    /**
     * 執行權限使用分析
     */
    private function performAnalysis(int $daysThreshold, bool $excludeSystem, bool $autoMark, bool $generateReport): void
    {
        // 取得整體統計
        $overallStats = $this->usageAnalysisService->getUsageStats();
        $this->displayOverallStats($overallStats);
        
        // 取得未使用權限
        $unusedPermissions = $this->usageAnalysisService->getUnusedPermissions();
        $this->displayUnusedPermissions($unusedPermissions, $excludeSystem);
        
        // 自動標記未使用權限
        if ($autoMark) {
            $this->markUnusedPermissions($daysThreshold, $excludeSystem);
        }
        
        // 生成詳細報告
        if ($generateReport) {
            $this->generateDetailedReport();
        }
        
        // 顯示模組統計
        $this->displayModuleStats();
    }

    /**
     * 顯示整體統計
     */
    private function displayOverallStats(array $stats): void
    {
        $this->info("\n=== 整體權限統計 ===");
        $this->line("總權限數: {$stats['total_permissions']}");
        $this->line("已使用權限: {$stats['used_permissions']} ({$stats['usage_percentage']}%)");
        $this->line("未使用權限: {$stats['unused_permissions']} ({$stats['unused_percentage']}%)");
        $this->line("系統權限: {$stats['system_permissions']}");
        $this->line("自訂權限: {$stats['custom_permissions']}");
        $this->line("總角色數: {$stats['total_roles']}");
        $this->line("總使用者數: {$stats['total_users']}");
        $this->line("平均權限/角色: {$stats['average_permissions_per_role']}");
        $this->line("平均權限/使用者: {$stats['average_permissions_per_user']}");
    }

    /**
     * 顯示未使用權限
     */
    private function displayUnusedPermissions($unusedPermissions, bool $excludeSystem): void
    {
        $filtered = $unusedPermissions->filter(function ($permission) use ($excludeSystem) {
            return !$excludeSystem || !$permission['is_system_permission'];
        });
        
        $this->info("\n=== 未使用權限 ===");
        $this->line("未使用權限總數: {$unusedPermissions->count()}");
        
        if ($excludeSystem) {
            $this->line("排除系統權限後: {$filtered->count()}");
        }
        
        if ($filtered->isNotEmpty()) {
            $this->table(
                ['模組', '權限名稱', '顯示名稱', '類型', '創建時間', '系統權限'],
                $filtered->take(20)->map(function ($permission) {
                    return [
                        $permission['module'],
                        $permission['name'],
                        $permission['display_name'],
                        $permission['type'],
                        \Carbon\Carbon::parse($permission['created_at'])->format('Y-m-d'),
                        $permission['is_system_permission'] ? '是' : '否',
                    ];
                })->toArray()
            );
            
            if ($filtered->count() > 20) {
                $this->line("... 還有 " . ($filtered->count() - 20) . " 個未使用權限");
            }
        }
    }

    /**
     * 標記未使用權限
     */
    private function markUnusedPermissions(int $daysThreshold, bool $excludeSystem): void
    {
        $this->info("\n=== 標記未使用權限 ===");
        
        $options = [
            'days_threshold' => $daysThreshold,
            'exclude_system' => $excludeSystem,
        ];
        
        $result = $this->usageAnalysisService->markUnusedPermissions($options);
        
        $this->line("找到未使用權限: {$result['total_unused']}");
        $this->line("符合標記條件: {$result['marked_unused']}");
        
        if ($excludeSystem && $result['excluded_system'] > 0) {
            $this->line("排除系統權限: {$result['excluded_system']}");
        }
        
        if ($result['marked_unused'] > 0) {
            $this->info("已成功標記 {$result['marked_unused']} 個未使用權限");
        } else {
            $this->comment("沒有權限需要標記");
        }
    }

    /**
     * 生成詳細報告
     */
    private function generateDetailedReport(): void
    {
        $this->info("\n=== 生成詳細報告 ===");
        
        // 取得模組統計
        $moduleStats = $this->usageAnalysisService->getModuleUsageStats();
        
        // 取得使用熱力圖資料
        $heatmapData = $this->usageAnalysisService->getUsageHeatmapData();
        
        // 生成報告檔案
        $reportData = [
            'generated_at' => now()->toISOString(),
            'overall_stats' => $this->usageAnalysisService->getUsageStats(),
            'module_stats' => $moduleStats,
            'unused_permissions' => $this->usageAnalysisService->getUnusedPermissions()->toArray(),
            'heatmap_data' => $heatmapData,
        ];
        
        $reportPath = storage_path('app/reports/permission-usage-' . now()->format('Y-m-d-H-i-s') . '.json');
        
        // 確保目錄存在
        $reportDir = dirname($reportPath);
        if (!is_dir($reportDir)) {
            mkdir($reportDir, 0755, true);
        }
        
        file_put_contents($reportPath, json_encode($reportData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        
        $this->info("詳細報告已生成: {$reportPath}");
    }

    /**
     * 顯示模組統計
     */
    private function displayModuleStats(): void
    {
        $moduleStats = $this->usageAnalysisService->getModuleUsageStats();
        
        $this->info("\n=== 模組使用統計 ===");
        $this->table(
            ['模組', '總權限', '已使用', '未使用', '使用率', '使用者數'],
            collect($moduleStats)->map(function ($stat) {
                return [
                    $stat['module'],
                    $stat['total_permissions'],
                    $stat['used_permissions'],
                    $stat['unused_permissions'],
                    $stat['usage_percentage'] . '%',
                    $stat['total_users'],
                ];
            })->toArray()
        );
    }

    /**
     * 清除分析快取
     */
    private function clearCache(): void
    {
        $this->info('清除權限使用分析快取...');
        $this->usageAnalysisService->clearUsageCache();
        $this->info('快取已清除');
    }
}