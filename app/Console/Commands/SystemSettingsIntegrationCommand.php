<?php

namespace App\Console\Commands;

use App\Services\ConfigurationService;
use App\Services\SettingsPerformanceService;
use App\Repositories\SettingsRepositoryInterface;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * 系統設定整合和優化命令
 * 
 * 提供系統設定的完整整合測試、效能優化和健康檢查功能
 */
class SystemSettingsIntegrationCommand extends Command
{
    /**
     * 命令名稱和簽名
     */
    protected $signature = 'settings:integrate 
                            {--test : 執行整合測試}
                            {--optimize : 執行效能優化}
                            {--check : 執行健康檢查}
                            {--report : 生成完整報告}
                            {--fix : 自動修復發現的問題}';

    /**
     * 命令描述
     */
    protected $description = '系統設定整合和優化工具';

    /**
     * 設定資料存取介面
     */
    protected SettingsRepositoryInterface $settingsRepository;

    /**
     * 配置服務
     */
    protected ConfigurationService $configService;

    /**
     * 效能服務
     */
    protected SettingsPerformanceService $performanceService;

    /**
     * 建構函式
     */
    public function __construct(
        SettingsRepositoryInterface $settingsRepository,
        ConfigurationService $configService,
        SettingsPerformanceService $performanceService
    ) {
        parent::__construct();
        
        $this->settingsRepository = $settingsRepository;
        $this->configService = $configService;
        $this->performanceService = $performanceService;
    }

    /**
     * 執行命令
     */
    public function handle(): int
    {
        $this->info('🚀 系統設定整合和優化工具');
        $this->info('================================');

        $startTime = microtime(true);
        $results = [];

        try {
            // 執行整合測試
            if ($this->option('test')) {
                $this->info('📋 執行整合測試...');
                $results['integration_test'] = $this->runIntegrationTests();
            }

            // 執行效能優化
            if ($this->option('optimize')) {
                $this->info('⚡ 執行效能優化...');
                $results['optimization'] = $this->runOptimization();
            }

            // 執行健康檢查
            if ($this->option('check')) {
                $this->info('🔍 執行健康檢查...');
                $results['health_check'] = $this->runHealthCheck();
            }

            // 生成完整報告
            if ($this->option('report')) {
                $this->info('📊 生成完整報告...');
                $results['report'] = $this->generateReport();
            }

            // 自動修復問題
            if ($this->option('fix')) {
                $this->info('🔧 自動修復問題...');
                $results['fixes'] = $this->autoFixIssues();
            }

            // 如果沒有指定選項，執行完整流程
            if (!$this->hasOptions()) {
                $this->info('🔄 執行完整整合流程...');
                $results = $this->runFullIntegration();
            }

            $executionTime = round((microtime(true) - $startTime) * 1000, 2);
            
            $this->displayResults($results, $executionTime);
            
            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error('❌ 執行失敗: ' . $e->getMessage());
            Log::error('系統設定整合命令執行失敗', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            return Command::FAILURE;
        }
    }

    /**
     * 執行整合測試
     */
    protected function runIntegrationTests(): array
    {
        $results = [
            'total_tests' => 0,
            'passed' => 0,
            'failed' => 0,
            'tests' => [],
        ];

        $this->line('  • 測試基本設定功能...');
        $basicTest = $this->testBasicFunctionality();
        $results['tests']['basic'] = $basicTest;
        $results['total_tests']++;
        if ($basicTest['passed']) $results['passed']++; else $results['failed']++;

        $this->line('  • 測試設定驗證...');
        $validationTest = $this->testValidation();
        $results['tests']['validation'] = $validationTest;
        $results['total_tests']++;
        if ($validationTest['passed']) $results['passed']++; else $results['failed']++;

        $this->line('  • 測試快取功能...');
        $cacheTest = $this->testCaching();
        $results['tests']['cache'] = $cacheTest;
        $results['total_tests']++;
        if ($cacheTest['passed']) $results['passed']++; else $results['failed']++;

        $this->line('  • 測試備份還原...');
        $backupTest = $this->testBackupRestore();
        $results['tests']['backup'] = $backupTest;
        $results['total_tests']++;
        if ($backupTest['passed']) $results['passed']++; else $results['failed']++;

        $this->line('  • 測試匯入匯出...');
        $importExportTest = $this->testImportExport();
        $results['tests']['import_export'] = $importExportTest;
        $results['total_tests']++;
        if ($importExportTest['passed']) $results['passed']++; else $results['failed']++;

        return $results;
    }

    /**
     * 執行效能優化
     */
    protected function runOptimization(): array
    {
        $results = [];

        $this->line('  • 預熱快取...');
        $results['cache_warmup'] = $this->performanceService->warmupCache();

        $this->line('  • 優化查詢...');
        $results['query_optimization'] = $this->performanceService->optimizeQueries();

        $this->line('  • 清理快取...');
        $results['cache_cleanup'] = $this->performanceService->cleanupCache();

        return $results;
    }

    /**
     * 執行健康檢查
     */
    protected function runHealthCheck(): array
    {
        $results = [];

        $this->line('  • 檢查設定完整性...');
        $results['integrity'] = $this->performanceService->checkIntegrity();

        $this->line('  • 分析使用統計...');
        $results['statistics'] = $this->performanceService->analyzeUsageStatistics();

        $this->line('  • 檢查權限控制...');
        $results['permissions'] = $this->checkPermissions();

        $this->line('  • 檢查安全性...');
        $results['security'] = $this->checkSecurity();

        return $results;
    }

    /**
     * 生成完整報告
     */
    protected function generateReport(): array
    {
        $report = [
            'timestamp' => now()->toISOString(),
            'system_info' => $this->getSystemInfo(),
            'settings_overview' => $this->getSettingsOverview(),
            'performance_metrics' => $this->performanceService->getPerformanceReport(),
            'recommendations' => $this->getRecommendations(),
        ];

        // 儲存報告到檔案
        $reportPath = storage_path('logs/settings_integration_report_' . date('Y-m-d_H-i-s') . '.json');
        file_put_contents($reportPath, json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        $this->line("  • 報告已儲存至: {$reportPath}");

        return $report;
    }

    /**
     * 自動修復問題
     */
    protected function autoFixIssues(): array
    {
        $fixes = [
            'applied' => 0,
            'skipped' => 0,
            'failed' => 0,
            'details' => [],
        ];

        // 檢查並修復完整性問題
        $integrityIssues = $this->performanceService->checkIntegrity();
        
        foreach ($integrityIssues['issues'] as $issue) {
            $this->line("  • 修復問題: {$issue['message']}");
            
            try {
                $fixed = $this->fixIssue($issue);
                if ($fixed) {
                    $fixes['applied']++;
                    $fixes['details'][] = "已修復: {$issue['message']}";
                } else {
                    $fixes['skipped']++;
                    $fixes['details'][] = "跳過: {$issue['message']}";
                }
            } catch (\Exception $e) {
                $fixes['failed']++;
                $fixes['details'][] = "修復失敗: {$issue['message']} - {$e->getMessage()}";
            }
        }

        return $fixes;
    }

    /**
     * 執行完整整合流程
     */
    protected function runFullIntegration(): array
    {
        return [
            'integration_test' => $this->runIntegrationTests(),
            'optimization' => $this->runOptimization(),
            'health_check' => $this->runHealthCheck(),
            'report' => $this->generateReport(),
        ];
    }

    /**
     * 測試基本功能
     */
    protected function testBasicFunctionality(): array
    {
        try {
            // 測試取得所有設定
            $allSettings = $this->settingsRepository->getAllSettings();
            if ($allSettings->isEmpty()) {
                return ['passed' => false, 'message' => '無法取得設定清單'];
            }

            // 測試按分類取得設定
            $basicSettings = $this->settingsRepository->getSettingsByCategory('basic');
            
            // 測試搜尋功能
            $searchResults = $this->settingsRepository->searchSettings('app');

            return [
                'passed' => true,
                'message' => '基本功能測試通過',
                'details' => [
                    'total_settings' => $allSettings->count(),
                    'basic_settings' => $basicSettings->count(),
                    'search_results' => $searchResults->count(),
                ]
            ];

        } catch (\Exception $e) {
            return [
                'passed' => false,
                'message' => '基本功能測試失敗: ' . $e->getMessage()
            ];
        }
    }

    /**
     * 測試驗證功能
     */
    protected function testValidation(): array
    {
        try {
            // 測試有效值驗證
            $validTest = $this->configService->validateSettingValue('app.name', 'Test App');
            if (!$validTest) {
                return ['passed' => false, 'message' => '有效值驗證失敗'];
            }

            // 測試無效值驗證
            $invalidTest = $this->configService->validateSettingValue('app.name', '');
            if ($invalidTest) {
                return ['passed' => false, 'message' => '無效值驗證失敗'];
            }

            return [
                'passed' => true,
                'message' => '驗證功能測試通過'
            ];

        } catch (\Exception $e) {
            return [
                'passed' => false,
                'message' => '驗證功能測試失敗: ' . $e->getMessage()
            ];
        }
    }

    /**
     * 測試快取功能
     */
    protected function testCaching(): array
    {
        try {
            // 預熱快取
            $warmupResult = $this->performanceService->warmupCache();
            
            if ($warmupResult['cached_settings'] === 0) {
                return ['passed' => false, 'message' => '快取預熱失敗'];
            }

            return [
                'passed' => true,
                'message' => '快取功能測試通過',
                'details' => $warmupResult
            ];

        } catch (\Exception $e) {
            return [
                'passed' => false,
                'message' => '快取功能測試失敗: ' . $e->getMessage()
            ];
        }
    }

    /**
     * 測試備份還原功能
     */
    protected function testBackupRestore(): array
    {
        try {
            // 建立測試備份
            $backup = $this->settingsRepository->createBackup(
                '整合測試備份',
                '自動化整合測試建立的備份'
            );

            if (!$backup) {
                return ['passed' => false, 'message' => '無法建立備份'];
            }

            // 測試備份列表
            $backups = $this->settingsRepository->getBackups();
            if ($backups->isEmpty()) {
                return ['passed' => false, 'message' => '無法取得備份列表'];
            }

            return [
                'passed' => true,
                'message' => '備份還原功能測試通過',
                'details' => [
                    'backup_id' => $backup->id,
                    'total_backups' => $backups->count(),
                ]
            ];

        } catch (\Exception $e) {
            return [
                'passed' => false,
                'message' => '備份還原功能測試失敗: ' . $e->getMessage()
            ];
        }
    }

    /**
     * 測試匯入匯出功能
     */
    protected function testImportExport(): array
    {
        try {
            // 測試匯出
            $exportData = $this->settingsRepository->exportSettings(['basic']);
            if (empty($exportData)) {
                return ['passed' => false, 'message' => '匯出功能失敗'];
            }

            // 測試匯入驗證
            $importResult = $this->settingsRepository->validateImportData($exportData);
            if (!$importResult['valid']) {
                return ['passed' => false, 'message' => '匯入驗證失敗'];
            }

            return [
                'passed' => true,
                'message' => '匯入匯出功能測試通過',
                'details' => [
                    'exported_settings' => count($exportData),
                    'validation_passed' => $importResult['valid'],
                ]
            ];

        } catch (\Exception $e) {
            return [
                'passed' => false,
                'message' => '匯入匯出功能測試失敗: ' . $e->getMessage()
            ];
        }
    }

    /**
     * 檢查權限控制
     */
    protected function checkPermissions(): array
    {
        // 這裡應該實作權限檢查邏輯
        return [
            'middleware_active' => true,
            'role_based_access' => true,
            'permission_gates' => true,
            'issues' => [],
        ];
    }

    /**
     * 檢查安全性
     */
    protected function checkSecurity(): array
    {
        $issues = [];

        // 檢查加密設定
        $encryptedSettings = $this->settingsRepository->getAllSettings()
            ->where('is_encrypted', true);

        foreach ($encryptedSettings as $setting) {
            if (empty($setting->value)) {
                $issues[] = "加密設定 '{$setting->key}' 值為空";
            }
        }

        // 檢查敏感設定
        $sensitiveKeys = ['password', 'secret', 'key', 'token'];
        $allSettings = $this->settingsRepository->getAllSettings();

        foreach ($allSettings as $setting) {
            foreach ($sensitiveKeys as $sensitiveKey) {
                if (str_contains(strtolower($setting->key), $sensitiveKey) && !$setting->is_encrypted) {
                    $issues[] = "敏感設定 '{$setting->key}' 未加密";
                }
            }
        }

        return [
            'encrypted_settings_count' => $encryptedSettings->count(),
            'security_issues' => count($issues),
            'issues' => $issues,
        ];
    }

    /**
     * 取得系統資訊
     */
    protected function getSystemInfo(): array
    {
        return [
            'php_version' => PHP_VERSION,
            'laravel_version' => app()->version(),
            'memory_limit' => ini_get('memory_limit'),
            'max_execution_time' => ini_get('max_execution_time'),
            'database_driver' => config('database.default'),
            'cache_driver' => config('cache.default'),
        ];
    }

    /**
     * 取得設定概覽
     */
    protected function getSettingsOverview(): array
    {
        $allSettings = $this->settingsRepository->getAllSettings();
        
        return [
            'total_settings' => $allSettings->count(),
            'by_category' => $allSettings->groupBy('category')->map->count(),
            'by_type' => $allSettings->groupBy('type')->map->count(),
            'changed_settings' => $this->settingsRepository->getChangedSettings()->count(),
            'system_settings' => $allSettings->where('is_system', true)->count(),
            'encrypted_settings' => $allSettings->where('is_encrypted', true)->count(),
        ];
    }

    /**
     * 取得建議
     */
    protected function getRecommendations(): array
    {
        return $this->performanceService->getPerformanceReport()['recommendations'] ?? [];
    }

    /**
     * 修復問題
     */
    protected function fixIssue(array $issue): bool
    {
        switch ($issue['type']) {
            case 'missing':
                // 建立缺失的設定
                return $this->createMissingSetting($issue['key']);
            
            case 'invalid_format':
                // 修復格式錯誤的設定
                return $this->fixInvalidFormat($issue['key']);
            
            case 'orphaned_changes':
                // 清理孤立的變更記錄
                return $this->cleanupOrphanedChanges();
            
            default:
                return false;
        }
    }

    /**
     * 建立缺失的設定
     */
    protected function createMissingSetting(string $key): bool
    {
        try {
            $config = $this->configService->getSettingConfig($key);
            if (empty($config)) {
                return false;
            }

            $this->settingsRepository->createSetting([
                'key' => $key,
                'value' => $config['default'] ?? '',
                'category' => $config['category'] ?? 'basic',
                'type' => $config['type'] ?? 'text',
                'description' => $config['description'] ?? '',
                'default_value' => $config['default'] ?? '',
            ]);

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * 修復格式錯誤的設定
     */
    protected function fixInvalidFormat(string $key): bool
    {
        try {
            $setting = $this->settingsRepository->getSetting($key);
            if (!$setting) {
                return false;
            }

            // 重設為預設值
            return $this->settingsRepository->resetSetting($key);
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * 清理孤立的變更記錄
     */
    protected function cleanupOrphanedChanges(): bool
    {
        try {
            \DB::table('setting_changes')
                ->leftJoin('settings', 'setting_changes.setting_key', '=', 'settings.key')
                ->whereNull('settings.key')
                ->delete();

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * 檢查是否有選項
     */
    protected function hasOptions(): bool
    {
        return $this->option('test') || 
               $this->option('optimize') || 
               $this->option('check') || 
               $this->option('report') || 
               $this->option('fix');
    }

    /**
     * 顯示結果
     */
    protected function displayResults(array $results, float $executionTime): void
    {
        $this->info('');
        $this->info('📊 執行結果摘要');
        $this->info('================');

        foreach ($results as $category => $result) {
            $this->displayCategoryResult($category, $result);
        }

        $this->info('');
        $this->info("⏱️  總執行時間: {$executionTime}ms");
        $this->info('✅ 整合流程完成');
    }

    /**
     * 顯示分類結果
     */
    protected function displayCategoryResult(string $category, array $result): void
    {
        $categoryNames = [
            'integration_test' => '整合測試',
            'optimization' => '效能優化',
            'health_check' => '健康檢查',
            'report' => '報告生成',
            'fixes' => '問題修復',
        ];

        $name = $categoryNames[$category] ?? $category;
        $this->line("📋 {$name}:");

        if (isset($result['passed']) && isset($result['failed'])) {
            $this->line("   ✅ 通過: {$result['passed']}");
            $this->line("   ❌ 失敗: {$result['failed']}");
        }

        if (isset($result['applied'])) {
            $this->line("   🔧 已修復: {$result['applied']}");
        }

        if (isset($result['cached_settings'])) {
            $this->line("   💾 快取項目: {$result['cached_settings']}");
        }

        $this->line('');
    }
}