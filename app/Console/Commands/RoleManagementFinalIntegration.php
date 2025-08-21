<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\RoleManagementIntegrationService;
use App\Services\RolePerformanceOptimizationService;
use App\Services\RoleSecurityAuditService;
use App\Services\RoleHierarchyValidationService;
use App\Services\RoleManagementUXTestService;
use Illuminate\Support\Facades\Log;

/**
 * 角色管理最終整合和優化命令
 * 
 * 執行完整的角色管理系統整合測試、效能優化和驗證
 */
class RoleManagementFinalIntegration extends Command
{
    /**
     * 命令名稱和簽名
     *
     * @var string
     */
    protected $signature = 'role-management:final-integration 
                            {--skip-ux : 跳過使用者體驗測試}
                            {--skip-performance : 跳過效能測試}
                            {--skip-security : 跳過安全性稽核}
                            {--skip-validation : 跳過層級驗證}
                            {--optimize : 執行系統優化}
                            {--report-only : 僅生成報告，不執行優化}';

    /**
     * 命令描述
     *
     * @var string
     */
    protected $description = '執行角色管理系統的最終整合測試和優化';

    private RoleManagementIntegrationService $integrationService;
    private RolePerformanceOptimizationService $performanceService;
    private RoleSecurityAuditService $securityService;
    private RoleHierarchyValidationService $validationService;
    private RoleManagementUXTestService $uxTestService;

    /**
     * 建立新的命令實例
     */
    public function __construct(
        RoleManagementIntegrationService $integrationService,
        RolePerformanceOptimizationService $performanceService,
        RoleSecurityAuditService $securityService,
        RoleHierarchyValidationService $validationService,
        RoleManagementUXTestService $uxTestService
    ) {
        parent::__construct();
        
        $this->integrationService = $integrationService;
        $this->performanceService = $performanceService;
        $this->securityService = $securityService;
        $this->validationService = $validationService;
        $this->uxTestService = $uxTestService;
    }

    /**
     * 執行命令
     *
     * @return int
     */
    public function handle(): int
    {
        $this->info('🚀 開始執行角色管理系統最終整合測試和優化');
        $this->newLine();

        $startTime = microtime(true);
        $results = [];
        $overallSuccess = true;

        try {
            // 1. 系統初始化和完整性檢查
            $this->info('📋 步驟 1: 系統初始化和完整性檢查');
            $initResult = $this->performSystemInitialization();
            $results['initialization'] = $initResult;
            
            if (!$initResult['success']) {
                $this->error('❌ 系統初始化失敗');
                $overallSuccess = false;
            } else {
                $this->info('✅ 系統初始化成功');
            }
            $this->newLine();

            // 2. 效能測試和優化
            if (!$this->option('skip-performance')) {
                $this->info('⚡ 步驟 2: 效能測試和優化');
                $performanceResult = $this->performPerformanceTesting();
                $results['performance'] = $performanceResult;
                
                if (!$performanceResult['success']) {
                    $this->warn('⚠️  效能測試部分失敗');
                } else {
                    $this->info('✅ 效能測試完成');
                }
                $this->newLine();
            }

            // 3. 安全性稽核
            if (!$this->option('skip-security')) {
                $this->info('🔒 步驟 3: 安全性稽核');
                $securityResult = $this->performSecurityAudit();
                $results['security'] = $securityResult;
                
                if (!$securityResult['success']) {
                    $this->error('❌ 安全性稽核失敗');
                    $overallSuccess = false;
                } else {
                    $this->info('✅ 安全性稽核完成');
                }
                $this->newLine();
            }

            // 4. 角色層級和權限繼承驗證
            if (!$this->option('skip-validation')) {
                $this->info('🔍 步驟 4: 角色層級和權限繼承驗證');
                $validationResult = $this->performHierarchyValidation();
                $results['validation'] = $validationResult;
                
                if (!$validationResult['success']) {
                    $this->error('❌ 層級驗證失敗');
                    $overallSuccess = false;
                } else {
                    $this->info('✅ 層級驗證完成');
                }
                $this->newLine();
            }

            // 5. 使用者體驗測試
            if (!$this->option('skip-ux')) {
                $this->info('👥 步驟 5: 使用者體驗測試');
                $uxResult = $this->performUXTesting();
                $results['ux_testing'] = $uxResult;
                
                if (!$uxResult['success']) {
                    $this->warn('⚠️  使用者體驗測試部分失敗');
                } else {
                    $this->info('✅ 使用者體驗測試完成');
                }
                $this->newLine();
            }

            // 6. 系統優化
            if ($this->option('optimize') && !$this->option('report-only')) {
                $this->info('🔧 步驟 6: 系統優化');
                $optimizationResult = $this->performSystemOptimization();
                $results['optimization'] = $optimizationResult;
                
                if (!$optimizationResult['success']) {
                    $this->warn('⚠️  系統優化部分失敗');
                } else {
                    $this->info('✅ 系統優化完成');
                }
                $this->newLine();
            }

            // 7. 最終健康檢查
            $this->info('🏥 步驟 7: 最終健康檢查');
            $healthResult = $this->performFinalHealthCheck();
            $results['health_check'] = [
                'success' => $healthResult['overall_status'] === 'healthy',
                'health_data' => $healthResult
            ];
            
            if ($healthResult['overall_status'] !== 'healthy') {
                $this->warn('⚠️  系統健康檢查發現問題');
            } else {
                $this->info('✅ 系統健康檢查通過');
            }
            $this->newLine();

            // 8. 生成綜合報告
            $this->info('📊 步驟 8: 生成綜合報告');
            $this->generateComprehensiveReport($results, microtime(true) - $startTime);

            if ($overallSuccess) {
                $this->info('🎉 角色管理系統最終整合測試和優化完成！');
                return Command::SUCCESS;
            } else {
                $this->error('❌ 部分測試失敗，請檢查報告詳情');
                return Command::FAILURE;
            }

        } catch (\Exception $e) {
            $this->error('💥 執行過程中發生錯誤: ' . $e->getMessage());
            Log::error('角色管理最終整合命令執行失敗', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return Command::FAILURE;
        }
    }

    /**
     * 執行系統初始化
     * 
     * @return array
     */
    private function performSystemInitialization(): array
    {
        $this->line('  • 檢查系統完整性...');
        $integrityResult = $this->integrationService->performSystemIntegrityCheck();
        
        if (!$integrityResult['success']) {
            foreach ($integrityResult['errors'] as $error) {
                $this->error("    ❌ {$error}");
            }
        }

        if (!empty($integrityResult['warnings'])) {
            foreach ($integrityResult['warnings'] as $warning) {
                $this->warn("    ⚠️  {$warning}");
            }
        }

        $this->line('  • 初始化系統...');
        $initResult = $this->integrationService->initializeSystem();

        if ($initResult['success']) {
            $stats = $initResult['stats'];
            $this->line("    📊 系統統計:");
            $this->line("       - 總角色數: {$stats['roles']['total']}");
            $this->line("       - 啟用角色數: {$stats['roles']['active']}");
            $this->line("       - 總權限數: {$stats['permissions']['total']}");
            $this->line("       - 總使用者數: {$stats['users']['total']}");
        }

        return $initResult;
    }

    /**
     * 執行效能測試
     * 
     * @return array
     */
    private function performPerformanceTesting(): array
    {
        $this->line('  • 執行效能測試套件...');
        $performanceResult = $this->performanceService->runPerformanceTestSuite();

        if ($performanceResult['success']) {
            $report = $performanceResult['results']['performance_report'];
            $this->line("    📈 效能報告:");
            $this->line("       - 整體狀態: {$report['summary']['overall_status']}");
            
            if (!empty($report['recommendations'])) {
                $this->line("    💡 建議:");
                foreach ($report['recommendations'] as $recommendation) {
                    $this->line("       - {$recommendation}");
                }
            }
        }

        return $performanceResult;
    }

    /**
     * 執行安全性稽核
     * 
     * @return array
     */
    private function performSecurityAudit(): array
    {
        $this->line('  • 執行安全性稽核...');
        $securityResult = $this->securityService->performSecurityAudit();

        if ($securityResult['success']) {
            $report = $securityResult['security_report'];
            $this->line("    🔒 安全性報告:");
            $this->line("       - 整體狀態: {$report['overall_status']}");
            $this->line("       - 安全性評分: {$report['security_score']}/100");
            
            $summary = $report['issue_summary'];
            if ($summary['total_issues'] > 0) {
                $this->line("    ⚠️  發現問題:");
                $this->line("       - 高嚴重性: {$summary['high_severity']}");
                $this->line("       - 中嚴重性: {$summary['medium_severity']}");
                $this->line("       - 低嚴重性: {$summary['low_severity']}");
            }

            if (!empty($report['recommendations'])) {
                $this->line("    💡 安全建議:");
                foreach ($report['recommendations'] as $recommendation) {
                    $this->line("       - {$recommendation}");
                }
            }
        }

        return $securityResult;
    }

    /**
     * 執行層級驗證
     * 
     * @return array
     */
    private function performHierarchyValidation(): array
    {
        $this->line('  • 執行角色層級驗證...');
        $validationResult = $this->validationService->performCompleteValidation();

        if ($validationResult['success']) {
            $report = $validationResult['validation_report'];
            $this->line("    🔍 驗證報告:");
            $this->line("       - 整體狀態: {$report['overall_status']}");
            $this->line("       - 驗證評分: {$report['validation_score']}/100");
            
            $summary = $report['summary'];
            if ($summary['total_errors'] > 0 || $summary['total_warnings'] > 0) {
                $this->line("    ⚠️  發現問題:");
                $this->line("       - 錯誤: {$summary['total_errors']}");
                $this->line("       - 警告: {$summary['total_warnings']}");
            }

            if (!empty($report['recommendations'])) {
                $this->line("    💡 驗證建議:");
                foreach ($report['recommendations'] as $recommendation) {
                    $this->line("       - {$recommendation}");
                }
            }
        }

        return $validationResult;
    }

    /**
     * 執行使用者體驗測試
     * 
     * @return array
     */
    private function performUXTesting(): array
    {
        $this->line('  • 執行使用者體驗測試...');
        
        try {
            $uxResult = $this->uxTestService->runCompleteUXTestSuite();

            if ($uxResult['success']) {
                $report = $uxResult['test_report'];
                $this->line("    👥 UX 測試報告:");
                $this->line("       - 整體狀態: {$report['overall_status']}");
                $this->line("       - UX 評分: {$report['ux_score']}/100");
                
                $summary = $report['test_summary'];
                $this->line("       - 成功測試: {$summary['successful_tests']}/{$summary['total_tests']}");
                $this->line("       - 成功率: {$summary['success_rate']}%");

                if (!empty($report['recommendations'])) {
                    $this->line("    💡 UX 建議:");
                    foreach ($report['recommendations'] as $recommendation) {
                        $this->line("       - {$recommendation}");
                    }
                }
            }

            return $uxResult;

        } catch (\Exception $e) {
            $this->warn("    ⚠️  UX 測試執行失敗: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * 執行系統優化
     * 
     * @return array
     */
    private function performSystemOptimization(): array
    {
        $this->line('  • 執行系統優化...');
        
        $integrationOptimization = $this->integrationService->performOptimization();
        $performanceOptimization = $this->performanceService->performOptimizations();

        $combinedResult = [
            'success' => $integrationOptimization['success'] && $performanceOptimization['success'],
            'integration_optimization' => $integrationOptimization,
            'performance_optimization' => $performanceOptimization
        ];

        if ($combinedResult['success']) {
            $this->line("    🔧 優化完成:");
            
            if (isset($integrationOptimization['results'])) {
                foreach ($integrationOptimization['results'] as $key => $result) {
                    $this->line("       - {$key}: {$result}");
                }
            }

            if (isset($performanceOptimization['optimizations'])) {
                foreach ($performanceOptimization['optimizations'] as $key => $optimization) {
                    $this->line("       - {$key}: {$optimization}");
                }
            }
        }

        return $combinedResult;
    }

    /**
     * 執行最終健康檢查
     * 
     * @return array
     */
    private function performFinalHealthCheck(): array
    {
        $this->line('  • 執行最終健康檢查...');
        $healthResult = $this->integrationService->performHealthCheck();

        $this->line("    🏥 健康檢查結果:");
        $this->line("       - 整體狀態: {$healthResult['overall_status']}");

        foreach ($healthResult['checks'] as $checkName => $check) {
            $status = $check['status'] === 'healthy' ? '✅' : '❌';
            $this->line("       - {$checkName}: {$status} {$check['message']}");
        }

        return $healthResult;
    }

    /**
     * 生成綜合報告
     * 
     * @param array $results
     * @param float $totalTime
     * @return void
     */
    private function generateComprehensiveReport(array $results, float $totalTime): void
    {
        $this->newLine();
        $this->info('📊 綜合報告');
        $this->line('═══════════════════════════════════════════════════════════');

        // 執行摘要
        $this->line('📋 執行摘要:');
        $this->line("   • 總執行時間: " . round($totalTime, 2) . " 秒");
        $this->line("   • 執行時間: " . now()->format('Y-m-d H:i:s'));
        $this->line("   • 測試模組數: " . count($results));

        // 各模組狀態
        $this->newLine();
        $this->line('🔍 各模組狀態:');
        foreach ($results as $module => $result) {
            $status = $result['success'] ? '✅ 成功' : '❌ 失敗';
            $this->line("   • " . ucfirst($module) . ": {$status}");
        }

        // 關鍵指標
        $this->newLine();
        $this->line('📈 關鍵指標:');
        
        if (isset($results['initialization']['stats'])) {
            $stats = $results['initialization']['stats'];
            $this->line("   • 系統角色數: {$stats['roles']['total']}");
            $this->line("   • 啟用角色數: {$stats['roles']['active']}");
            $this->line("   • 系統權限數: {$stats['permissions']['total']}");
        }

        if (isset($results['security']['security_report']['security_score'])) {
            $securityScore = $results['security']['security_report']['security_score'];
            $this->line("   • 安全性評分: {$securityScore}/100");
        }

        if (isset($results['validation']['validation_report']['validation_score'])) {
            $validationScore = $results['validation']['validation_report']['validation_score'];
            $this->line("   • 驗證評分: {$validationScore}/100");
        }

        if (isset($results['ux_testing']['test_report']['ux_score'])) {
            $uxScore = $results['ux_testing']['test_report']['ux_score'];
            $this->line("   • UX 評分: {$uxScore}/100");
        }

        // 建議事項
        $this->newLine();
        $this->line('💡 綜合建議:');
        
        $allRecommendations = [];
        foreach ($results as $result) {
            if (isset($result['security_report']['recommendations'])) {
                $allRecommendations = array_merge($allRecommendations, $result['security_report']['recommendations']);
            }
            if (isset($result['validation_report']['recommendations'])) {
                $allRecommendations = array_merge($allRecommendations, $result['validation_report']['recommendations']);
            }
            if (isset($result['test_report']['recommendations'])) {
                $allRecommendations = array_merge($allRecommendations, $result['test_report']['recommendations']);
            }
        }

        $uniqueRecommendations = array_unique($allRecommendations);
        if (!empty($uniqueRecommendations)) {
            foreach (array_slice($uniqueRecommendations, 0, 5) as $recommendation) {
                $this->line("   • {$recommendation}");
            }
        } else {
            $this->line("   • 系統運作良好，建議定期執行整合測試");
        }

        $this->line('═══════════════════════════════════════════════════════════');
        
        // 記錄到日誌
        Log::info('角色管理最終整合報告', [
            'execution_time' => $totalTime,
            'results' => $results,
            'recommendations' => $uniqueRecommendations ?? []
        ]);
    }
}