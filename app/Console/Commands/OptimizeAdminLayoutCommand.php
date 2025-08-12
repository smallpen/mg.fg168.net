<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\AdminLayoutIntegrationService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Artisan;

/**
 * 管理後台佈局優化指令
 * 
 * 執行完整的佈局系統優化和驗證
 */
class OptimizeAdminLayoutCommand extends Command
{
    /**
     * 指令名稱和簽名
     */
    protected $signature = 'admin:optimize-layout 
                           {--test : 執行整合測試}
                           {--cache : 重建快取}
                           {--assets : 重建前端資源}
                           {--report : 生成詳細報告}';

    /**
     * 指令描述
     */
    protected $description = '優化管理後台佈局系統並執行整合驗證';

    protected AdminLayoutIntegrationService $integrationService;

    /**
     * 建構函式
     */
    public function __construct(AdminLayoutIntegrationService $integrationService)
    {
        parent::__construct();
        $this->integrationService = $integrationService;
    }

    /**
     * 執行指令
     */
    public function handle(): int
    {
        $this->info('🚀 開始優化管理後台佈局系統...');
        $this->newLine();

        $startTime = microtime(true);
        $results = [];

        try {
            // 1. 清除快取（如果指定）
            if ($this->option('cache')) {
                $this->optimizeCache();
            }

            // 2. 重建前端資源（如果指定）
            if ($this->option('assets')) {
                $this->buildAssets();
            }

            // 3. 執行整合測試（如果指定）
            if ($this->option('test')) {
                $this->runIntegrationTests();
            }

            // 4. 執行完整整合檢查
            $this->info('📋 執行系統整合檢查...');
            $results = $this->integrationService->performFullIntegration();

            // 5. 顯示結果
            $this->displayResults($results);

            // 6. 生成報告（如果指定）
            if ($this->option('report')) {
                $this->generateReport($results, microtime(true) - $startTime);
            }

            // 7. 最終狀態
            if ($results['overall_status'] === 'passed') {
                $this->info('✅ 管理後台佈局系統優化完成！');
                return Command::SUCCESS;
            } else {
                $this->error('❌ 系統整合檢查發現問題，請查看上方詳細資訊');
                return Command::FAILURE;
            }

        } catch (\Exception $e) {
            $this->error('💥 優化過程中發生錯誤: ' . $e->getMessage());
            $this->error('堆疊追蹤: ' . $e->getTraceAsString());
            return Command::FAILURE;
        }
    }

    /**
     * 優化快取系統
     */
    protected function optimizeCache(): void
    {
        $this->info('🗄️  優化快取系統...');
        
        $this->line('清除應用程式快取...');
        Cache::flush();
        $this->info('✅ 應用程式快取已清除');

        $this->line('清除設定快取...');
        Artisan::call('config:clear');
        $this->info('✅ 設定快取已清除');

        $this->line('清除路由快取...');
        Artisan::call('route:clear');
        $this->info('✅ 路由快取已清除');

        $this->line('清除視圖快取...');
        Artisan::call('view:clear');
        $this->info('✅ 視圖快取已清除');

        $this->line('重建設定快取...');
        Artisan::call('config:cache');
        $this->info('✅ 設定快取已重建');

        $this->line('重建路由快取...');
        Artisan::call('route:cache');
        $this->info('✅ 路由快取已重建');

        $this->newLine();
    }

    /**
     * 建置前端資源
     */
    protected function buildAssets(): void
    {
        $this->info('🎨 建置前端資源...');
        
        $this->line('安裝 NPM 依賴...');
        $output = shell_exec('npm install 2>&1');
        if (strpos($output, 'error') === false) {
            $this->info('✅ NPM 依賴安裝完成');
        } else {
            $this->error('❌ NPM 依賴安裝失敗');
        }

        $this->line('建置生產版本資源...');
        $output = shell_exec('npm run build 2>&1');
        if (strpos($output, 'error') === false) {
            $this->info('✅ 前端資源建置完成');
        } else {
            $this->error('❌ 前端資源建置失敗');
        }

        $this->newLine();
    }

    /**
     * 執行整合測試
     */
    protected function runIntegrationTests(): void
    {
        $this->info('🧪 執行整合測試...');
        
        $this->line('執行佈局整合測試...');
        $exitCode = Artisan::call('test', [
            '--filter' => 'AdminLayoutIntegrationTest',
            '--stop-on-failure' => true
        ]);
        
        if ($exitCode === 0) {
            $this->info('✅ 整合測試通過');
        } else {
            $this->error('❌ 整合測試失敗');
        }

        $this->newLine();
    }

    /**
     * 顯示整合結果
     */
    protected function displayResults(array $results): void
    {
        $this->info('📊 系統整合檢查結果:');
        $this->newLine();

        // 整體狀態
        $overallStatus = $results['overall_status'];
        $statusIcon = $overallStatus === 'passed' ? '✅' : '❌';
        $this->line("整體狀態: {$statusIcon} " . strtoupper($overallStatus));
        $this->newLine();

        // 各子系統狀態
        $subsystems = [
            'component_integration' => '元件整合',
            'performance_optimization' => '效能優化',
            'permission_control' => '權限控制',
            'responsive_design' => '響應式設計',
            'accessibility_features' => '無障礙功能',
            'user_experience' => '使用者體驗'
        ];

        $table = [];
        foreach ($subsystems as $key => $name) {
            if (isset($results[$key])) {
                $result = $results[$key];
                $status = $result['status'] ?? 'unknown';
                $statusIcon = $status === 'passed' ? '✅' : ($status === 'failed' ? '❌' : '⚠️');
                
                $details = '';
                if (isset($result['performance_score'])) {
                    $details = "分數: {$result['performance_score']}";
                } elseif (isset($result['working_components'], $result['total_components'])) {
                    $details = "運作中: {$result['working_components']}/{$result['total_components']}";
                } elseif (isset($result['passed_tests'], $result['total_tests'])) {
                    $details = "通過: {$result['passed_tests']}/{$result['total_tests']}";
                }

                $table[] = [$name, $statusIcon . ' ' . strtoupper($status), $details];
            }
        }

        $this->table(['子系統', '狀態', '詳細資訊'], $table);
        $this->newLine();

        // 顯示問題詳情
        $this->displayIssues($results);
    }

    /**
     * 顯示問題詳情
     */
    protected function displayIssues(array $results): void
    {
        $hasIssues = false;

        foreach ($results as $subsystemKey => $subsystem) {
            if (!is_array($subsystem) || !isset($subsystem['status'])) {
                continue;
            }

            if ($subsystem['status'] !== 'passed') {
                if (!$hasIssues) {
                    $this->error('🚨 發現的問題:');
                    $hasIssues = true;
                }

                $this->newLine();
                $this->line("📍 {$subsystemKey}:");

                // 顯示元件問題
                if (isset($subsystem['components'])) {
                    foreach ($subsystem['components'] as $componentName => $componentResult) {
                        if ($componentResult['status'] === 'failed') {
                            $this->line("  ❌ {$componentName}: {$componentResult['error']}");
                        }
                    }
                }

                // 顯示未受保護的路由
                if (isset($subsystem['unprotected_routes'])) {
                    foreach ($subsystem['unprotected_routes'] as $route) {
                        $this->line("  🔓 {$route['route']}: {$route['issue']}");
                    }
                }

                // 顯示權限問題
                if (isset($subsystem['menu_permissions']['issues'])) {
                    foreach ($subsystem['menu_permissions']['issues'] as $issue) {
                        $this->line("  🔐 {$issue['menu_item']}: {$issue['issue']}");
                    }
                }

                // 顯示一般錯誤
                if (isset($subsystem['error'])) {
                    $this->line("  ❌ 錯誤: {$subsystem['error']}");
                }
            }
        }

        if (!$hasIssues) {
            $this->info('🎉 沒有發現問題！');
        }
    }

    /**
     * 生成詳細報告
     */
    protected function generateReport(array $results, float $executionTime): void
    {
        $this->info('📄 生成詳細報告...');

        $reportPath = storage_path('logs/admin-layout-integration-report-' . date('Y-m-d-H-i-s') . '.json');
        
        $report = [
            'timestamp' => now()->toISOString(),
            'execution_time' => round($executionTime, 2),
            'php_version' => PHP_VERSION,
            'laravel_version' => app()->version(),
            'results' => $results,
            'system_info' => [
                'memory_usage' => memory_get_peak_usage(true),
                'memory_limit' => ini_get('memory_limit'),
                'time_limit' => ini_get('max_execution_time')
            ]
        ];

        file_put_contents($reportPath, json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        
        $this->info("報告已儲存至: {$reportPath}");
        $this->newLine();

        // 顯示摘要統計
        $this->displaySummaryStats($results, $executionTime);
    }

    /**
     * 顯示摘要統計
     */
    protected function displaySummaryStats(array $results, float $executionTime): void
    {
        $this->info('📈 執行摘要:');
        
        $stats = [
            ['項目', '數值'],
            ['執行時間', round($executionTime, 2) . ' 秒'],
            ['記憶體使用', $this->formatBytes(memory_get_peak_usage(true))],
            ['整體狀態', $results['overall_status']],
        ];

        // 計算各子系統通過率
        $subsystemCount = 0;
        $passedCount = 0;
        
        foreach ($results as $key => $result) {
            if ($key !== 'overall_status' && is_array($result) && isset($result['status'])) {
                $subsystemCount++;
                if ($result['status'] === 'passed') {
                    $passedCount++;
                }
            }
        }

        if ($subsystemCount > 0) {
            $passRate = round(($passedCount / $subsystemCount) * 100, 1);
            $stats[] = ['子系統通過率', "{$passedCount}/{$subsystemCount} ({$passRate}%)"];
        }

        $this->table([], $stats);
    }

    /**
     * 格式化位元組大小
     */
    protected function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        
        $bytes /= (1 << (10 * $pow));
        
        return round($bytes, 2) . ' ' . $units[$pow];
    }
}