/**
 * 完整的 Livewire 表單重置功能測試
 * 展示整個測試系統的完整功能
 */

const { test, expect } = require('@playwright/test');
const TestOrchestrator = require('./TestOrchestrator');

test.describe('完整的 Livewire 表單重置功能測試套件', () => {
    let testOrchestrator;

    test.beforeAll(async ({ browser }) => {
        console.log('🚀 初始化完整測試套件');
    });

    test.beforeEach(async ({ page }) => {
        testOrchestrator = new TestOrchestrator(page);
        
        // 初始化測試編排器
        await testOrchestrator.initialize({
            reportOutputDir: 'tests/reports',
            enableContinuousVerification: false,
            performanceThresholds: {
                resetTime: 2000,
                fillTime: 1000,
                totalTime: 3000,
                memoryIncrease: 50,
                ajaxResponseTime: 1000
            },
            screenshotConfig: {
                quality: 90,
                fullPage: true
            }
        });
    });

    test('執行完整的高優先級元件測試套件', async ({ page }) => {
        console.log('🧪 執行高優先級元件測試套件');

        const testResult = await testOrchestrator.executeFullTestSuite({
            includePerformanceTests: true,
            includeIntegrationTests: true,
            includeVisualTests: true,
            generateReports: true,
            components: 'high_priority'
        });

        // 驗證測試結果
        expect(testResult.success).toBe(true);
        expect(testResult.phases.initialization.success).toBe(true);
        expect(testResult.phases.componentTests.success).toBe(true);
        
        // 驗證測試摘要
        const componentSummary = testResult.phases.componentTests.summary;
        expect(componentSummary.total).toBeGreaterThan(0);
        expect(parseFloat(componentSummary.successRate)).toBeGreaterThanOrEqual(80);

        // 驗證報告生成
        expect(testResult.reports.paths).toBeDefined();
        expect(testResult.reports.paths.html).toBeDefined();
        expect(testResult.reports.paths.json).toBeDefined();
        expect(testResult.reports.paths.csv).toBeDefined();

        console.log(`✅ 高優先級元件測試完成 (成功率: ${componentSummary.successRate}%)`);
    });

    test('執行完整的中優先級元件測試套件', async ({ page }) => {
        console.log('🧪 執行中優先級元件測試套件');

        const testResult = await testOrchestrator.executeFullTestSuite({
            includePerformanceTests: false,
            includeIntegrationTests: true,
            includeVisualTests: false,
            generateReports: true,
            components: 'medium_priority'
        });

        // 驗證測試結果
        expect(testResult.success).toBe(true);
        expect(testResult.phases.componentTests.success).toBe(true);

        const componentSummary = testResult.phases.componentTests.summary;
        expect(componentSummary.total).toBeGreaterThan(0);
        expect(parseFloat(componentSummary.successRate)).toBeGreaterThanOrEqual(70);

        console.log(`✅ 中優先級元件測試完成 (成功率: ${componentSummary.successRate}%)`);
    });

    test('執行監控元件測試套件', async ({ page }) => {
        console.log('🧪 執行監控元件測試套件');

        const testResult = await testOrchestrator.executeFullTestSuite({
            includePerformanceTests: true,
            includeIntegrationTests: false,
            includeVisualTests: false,
            generateReports: true,
            components: 'monitoring'
        });

        // 驗證測試結果
        expect(testResult.success).toBe(true);
        expect(testResult.phases.componentTests.success).toBe(true);

        // 驗證效能測試結果
        if (testResult.phases.performanceTests) {
            expect(testResult.phases.performanceTests.success).toBe(true);
            
            const performanceSummary = testResult.phases.performanceTests.summary;
            expect(performanceSummary.total).toBeGreaterThan(0);
        }

        console.log('✅ 監控元件測試完成');
    });

    test('執行完整的測試套件（所有元件）', async ({ page }) => {
        console.log('🧪 執行完整的測試套件（所有元件）');

        const testResult = await testOrchestrator.executeFullTestSuite({
            includePerformanceTests: true,
            includeIntegrationTests: true,
            includeVisualTests: true,
            generateReports: true,
            components: 'all'
        });

        // 驗證整體測試結果
        expect(testResult.success).toBe(true);
        expect(testResult.overallResult.success).toBe(true);
        expect(parseFloat(testResult.overallResult.successRate)).toBeGreaterThanOrEqual(80);

        // 驗證各個階段
        expect(testResult.phases.initialization.success).toBe(true);
        expect(testResult.phases.componentTests.success).toBe(true);
        expect(testResult.phases.analysis.success).toBe(true);
        expect(testResult.phases.cleanup.success).toBe(true);

        // 驗證測試統計
        const componentSummary = testResult.phases.componentTests.summary;
        expect(componentSummary.total).toBeGreaterThan(5); // 至少測試 5 個元件
        expect(componentSummary.successful).toBeGreaterThan(0);

        // 驗證效能測試（如果執行）
        if (testResult.phases.performanceTests) {
            expect(testResult.phases.performanceTests.overallReport).toBeDefined();
        }

        // 驗證整合測試（如果執行）
        if (testResult.phases.integrationTests) {
            expect(testResult.phases.integrationTests.summary.total).toBeGreaterThan(0);
        }

        // 驗證視覺測試（如果執行）
        if (testResult.phases.visualTests) {
            expect(testResult.phases.visualTests.overallReport).toBeDefined();
        }

        // 驗證分析結果
        expect(testResult.phases.analysis.overallMetrics).toBeDefined();
        expect(testResult.phases.analysis.overallMetrics.totalTests).toBeGreaterThan(0);

        // 驗證報告生成
        expect(testResult.reports.paths).toBeDefined();
        expect(testResult.reports.verificationReport).toBeDefined();

        console.log('✅ 完整測試套件執行成功');
        console.log(`📊 測試統計:`);
        console.log(`  總測試數: ${testResult.phases.analysis.overallMetrics.totalTests}`);
        console.log(`  成功測試: ${testResult.phases.analysis.overallMetrics.successfulTests}`);
        console.log(`  失敗測試: ${testResult.phases.analysis.overallMetrics.failedTests}`);
        console.log(`  成功率: ${testResult.phases.analysis.overallMetrics.successRate}%`);
        console.log(`  總執行時間: ${(testResult.duration / 1000).toFixed(2)}秒`);
    });

    test('驗證測試系統的錯誤處理能力', async ({ page }) => {
        console.log('🧪 測試系統錯誤處理能力');

        // 測試無效元件配置的處理
        const testResult = await testOrchestrator.executeFullTestSuite({
            includePerformanceTests: false,
            includeIntegrationTests: false,
            includeVisualTests: false,
            generateReports: true,
            components: 'invalid_component_type'
        });

        // 即使有無效配置，系統也應該能夠處理
        expect(testResult).toBeDefined();
        expect(testResult.phases.initialization.success).toBe(true);
        expect(testResult.phases.cleanup.success).toBe(true);

        console.log('✅ 錯誤處理測試完成');
    });

    test('驗證測試狀態監控功能', async ({ page }) => {
        console.log('🧪 測試狀態監控功能');

        // 開始測試並檢查狀態
        const testPromise = testOrchestrator.executeFullTestSuite({
            includePerformanceTests: false,
            includeIntegrationTests: false,
            includeVisualTests: false,
            generateReports: false,
            components: 'high_priority'
        });

        // 檢查測試狀態
        await new Promise(resolve => setTimeout(resolve, 2000)); // 等待 2 秒
        
        const status = testOrchestrator.getTestStatus();
        expect(status.sessionId).toBeDefined();
        expect(status.status).toBe('running');
        expect(status.startTime).toBeDefined();
        expect(status.duration).toBeGreaterThan(0);

        // 等待測試完成
        const testResult = await testPromise;
        
        const finalStatus = testOrchestrator.getTestStatus();
        expect(finalStatus.status).toBe('completed');
        expect(finalStatus.endTime).toBeDefined();
        expect(finalStatus.totalTests).toBeGreaterThan(0);

        console.log('✅ 狀態監控測試完成');
    });

    test('驗證驗證工作流程功能', async ({ page }) => {
        console.log('🧪 測試驗證工作流程功能');

        // 執行驗證工作流程
        const workflowResult = await testOrchestrator.verificationWorkflow.executeVerificationWorkflow(
            'comprehensive_verification',
            {
                testMode: true,
                skipCleanup: false
            }
        );

        // 驗證工作流程結果
        expect(workflowResult).toBeDefined();
        expect(workflowResult.status).toBe('completed');
        expect(workflowResult.stepResults.length).toBeGreaterThan(0);
        expect(workflowResult.overallResult).toBeDefined();

        // 檢查工作流程狀態
        const workflowStatus = testOrchestrator.verificationWorkflow.getWorkflowStatus('comprehensive_verification');
        expect(workflowStatus.name).toBe('comprehensive_verification');
        expect(workflowStatus.totalExecutions).toBeGreaterThan(0);

        console.log('✅ 驗證工作流程測試完成');
    });

    test.afterEach(async ({ page }) => {
        // 清理測試資料
        if (testOrchestrator) {
            await testOrchestrator.cleanupAllTestData();
        }
    });

    test.afterAll(async ({ browser }) => {
        console.log('🏁 完整測試套件執行完成');
        console.log('📊 所有測試已完成，請查看生成的報告以獲取詳細結果');
    });
});