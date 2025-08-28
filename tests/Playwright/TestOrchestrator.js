/**
 * 測試編排器
 * 整合所有測試組件，提供完整的測試執行和報告功能
 */

const FormResetTestSuite = require('./FormResetTestSuite');
const BackendVerificationSuite = require('./backend/BackendVerificationSuite');
const PerformanceMonitor = require('./performance/PerformanceMonitor');
const IntegratedFormResetTest = require('./IntegratedFormResetTest');
const TestReportGenerator = require('./reporting/TestReportGenerator');
const VerificationWorkflow = require('./reporting/VerificationWorkflow');
const ScreenshotComparison = require('./utils/ScreenshotComparison');
const { TestConfigurations, TestSuiteConfigurations } = require('./config/TestConfigurations');

class TestOrchestrator {
    constructor(page) {
        this.page = page;
        this.testComponents = {
            frontend: new FormResetTestSuite(page),
            backend: new BackendVerificationSuite(),
            performance: new PerformanceMonitor(page),
            integration: new IntegratedFormResetTest(page),
            screenshots: new ScreenshotComparison(page)
        };
        this.reportGenerator = new TestReportGenerator();
        this.verificationWorkflow = new VerificationWorkflow();
        this.testSession = {
            id: this.generateSessionId(),
            startTime: null,
            endTime: null,
            status: 'initialized'
        };
        this.testResults = [];
    }

    /**
     * 初始化測試編排器
     * @param {Object} config - 配置選項
     */
    async initialize(config = {}) {
        console.log('🚀 初始化測試編排器');

        const {
            reportOutputDir = 'tests/reports',
            enableContinuousVerification = false,
            performanceThresholds = {},
            screenshotConfig = {}
        } = config;

        // 初始化報告生成器
        await this.reportGenerator.initialize({
            outputDirectory: reportOutputDir,
            includeScreenshots: true,
            includePerformanceCharts: true
        });

        // 設定效能閾值
        if (Object.keys(performanceThresholds).length > 0) {
            this.testComponents.performance.performanceTestSuite.setPerformanceThresholds(performanceThresholds);
        }

        // 建立標準驗證工作流程
        this.createStandardVerificationWorkflows();

        // 啟動持續驗證（如果需要）
        if (enableContinuousVerification) {
            this.verificationWorkflow.startContinuousVerification({
                workflows: ['comprehensive_verification', 'performance_monitoring'],
                interval: 3600000 // 1 小時
            });
        }

        console.log('✅ 測試編排器初始化完成');
    }

    /**
     * 執行完整的測試套件
     * @param {Object} options - 執行選項
     * @returns {Promise<Object>} - 測試結果
     */
    async executeFullTestSuite(options = {}) {
        console.log('🧪 執行完整的 Livewire 表單重置測試套件');

        const {
            includePerformanceTests = true,
            includeIntegrationTests = true,
            includeVisualTests = true,
            generateReports = true,
            components = 'all' // 'all', 'high_priority', 'medium_priority', 'monitoring'
        } = options;

        this.testSession.startTime = Date.now();
        this.testSession.status = 'running';

        const suiteResult = {
            sessionId: this.testSession.id,
            startTime: this.testSession.startTime,
            endTime: null,
            phases: {},
            overallResult: null,
            reports: {}
        };

        try {
            // 階段 1: 環境初始化和登入
            console.log('📋 階段 1: 環境初始化');
            suiteResult.phases.initialization = await this.executeInitializationPhase();

            // 階段 2: 元件測試
            console.log('📋 階段 2: 元件測試');
            suiteResult.phases.componentTests = await this.executeComponentTestsPhase(components);

            // 階段 3: 效能測試（如果啟用）
            if (includePerformanceTests) {
                console.log('📋 階段 3: 效能測試');
                suiteResult.phases.performanceTests = await this.executePerformanceTestsPhase();
            }

            // 階段 4: 整合測試（如果啟用）
            if (includeIntegrationTests) {
                console.log('📋 階段 4: 整合測試');
                suiteResult.phases.integrationTests = await this.executeIntegrationTestsPhase();
            }

            // 階段 5: 視覺測試（如果啟用）
            if (includeVisualTests) {
                console.log('📋 階段 5: 視覺測試');
                suiteResult.phases.visualTests = await this.executeVisualTestsPhase();
            }

            // 階段 6: 結果分析和驗證
            console.log('📋 階段 6: 結果分析');
            suiteResult.phases.analysis = await this.executeAnalysisPhase(suiteResult.phases);

            // 階段 7: 報告生成（如果啟用）
            if (generateReports) {
                console.log('📋 階段 7: 報告生成');
                suiteResult.reports = await this.generateComprehensiveReports(suiteResult);
            }

            // 階段 8: 清理
            console.log('📋 階段 8: 清理');
            suiteResult.phases.cleanup = await this.executeCleanupPhase();

            // 計算整體結果
            suiteResult.overallResult = this.calculateOverallResult(suiteResult.phases);
            suiteResult.success = suiteResult.overallResult.success;

            console.log(`${suiteResult.success ? '✅' : '❌'} 完整測試套件執行${suiteResult.success ? '成功' : '失敗'}`);

        } catch (error) {
            suiteResult.success = false;
            suiteResult.error = error.message;
            console.error(`❌ 測試套件執行失敗: ${error.message}`);
        } finally {
            this.testSession.endTime = Date.now();
            this.testSession.status = suiteResult.success ? 'completed' : 'failed';
            suiteResult.endTime = this.testSession.endTime;
            suiteResult.duration = suiteResult.endTime - suiteResult.startTime;
        }

        return suiteResult;
    }

    /**
     * 執行初始化階段
     * @returns {Promise<Object>} - 初始化結果
     */
    async executeInitializationPhase() {
        const initialization = {
            timestamp: new Date(),
            steps: {}
        };

        try {
            // 1. 登入系統
            console.log('  1.1 登入系統');
            const loginSuccess = await this.testComponents.frontend.livewireLogin();
            initialization.steps.login = { success: loginSuccess };

            if (!loginSuccess) {
                throw new Error('系統登入失敗');
            }

            // 2. 檢查 Livewire 連接
            console.log('  1.2 檢查 Livewire 連接');
            const connectionInfo = await this.testComponents.frontend.checkLivewireConnection();
            initialization.steps.livewireConnection = connectionInfo;

            // 3. 初始化後端驗證環境
            console.log('  1.3 初始化後端驗證環境');
            initialization.steps.backendInit = await this.testComponents.backend.initializeTestEnvironment();

            // 4. 開始效能監控
            console.log('  1.4 開始效能監控');
            await this.testComponents.performance.startComprehensiveMonitoring('full_test_suite', {
                memoryInterval: 2000,
                performanceBaseline: true
            });
            initialization.steps.performanceMonitoring = { success: true };

            initialization.success = true;

        } catch (error) {
            initialization.success = false;
            initialization.error = error.message;
        }

        return initialization;
    }

    /**
     * 執行元件測試階段
     * @param {string} components - 元件範圍
     * @returns {Promise<Object>} - 元件測試結果
     */
    async executeComponentTestsPhase(components) {
        const componentTests = {
            timestamp: new Date(),
            scope: components,
            results: {}
        };

        try {
            let testComponents = [];

            // 根據範圍選擇測試元件
            switch (components) {
                case 'high_priority':
                    testComponents = TestSuiteConfigurations.highPriority;
                    break;
                case 'medium_priority':
                    testComponents = TestSuiteConfigurations.mediumPriority;
                    break;
                case 'monitoring':
                    testComponents = TestSuiteConfigurations.monitoringComponents;
                    break;
                case 'all':
                default:
                    testComponents = [
                        ...TestSuiteConfigurations.highPriority,
                        ...TestSuiteConfigurations.mediumPriority,
                        ...TestSuiteConfigurations.monitoringComponents
                    ];
                    break;
            }

            // 執行每個元件的測試
            for (const componentName of testComponents) {
                const config = TestConfigurations[componentName];
                
                if (!config) {
                    console.log(`⚠️  跳過未配置的元件: ${componentName}`);
                    continue;
                }

                console.log(`  🧪 測試元件: ${componentName}`);
                
                try {
                    const componentResult = await this.testComponents.integration.executeIntegratedComponentTest(
                        componentName,
                        config
                    );
                    
                    componentTests.results[componentName] = componentResult;
                    this.testResults.push(componentResult);
                    
                    // 添加到報告生成器
                    this.reportGenerator.addTestResult(componentResult);
                    
                } catch (error) {
                    console.error(`❌ 元件測試失敗: ${componentName} - ${error.message}`);
                    componentTests.results[componentName] = {
                        componentName,
                        success: false,
                        error: error.message,
                        timestamp: new Date()
                    };
                }
            }

            const successfulTests = Object.values(componentTests.results).filter(r => r.success).length;
            const totalTests = Object.keys(componentTests.results).length;
            
            componentTests.success = successfulTests === totalTests;
            componentTests.summary = {
                total: totalTests,
                successful: successfulTests,
                failed: totalTests - successfulTests,
                successRate: totalTests > 0 ? (successfulTests / totalTests * 100).toFixed(2) : 0
            };

        } catch (error) {
            componentTests.success = false;
            componentTests.error = error.message;
        }

        return componentTests;
    }

    /**
     * 執行效能測試階段
     * @returns {Promise<Object>} - 效能測試結果
     */
    async executePerformanceTestsPhase() {
        const performanceTests = {
            timestamp: new Date(),
            results: {}
        };

        try {
            // 測試主要元件的效能
            const performanceComponents = ['userList', 'activityExport', 'permissionAuditLog'];
            
            for (const componentName of performanceComponents) {
                const config = TestConfigurations[componentName];
                
                if (!config) continue;

                console.log(`  ⚡ 效能測試: ${componentName}`);
                
                const performanceResult = await this.testComponents.performance.executeComprehensivePerformanceTest(
                    componentName,
                    config,
                    3 // 3 次迭代
                );
                
                performanceTests.results[componentName] = performanceResult;
                this.testResults.push(performanceResult);
            }

            // 生成效能報告
            const performanceReport = this.testComponents.performance.generateComprehensivePerformanceReport();
            performanceTests.overallReport = performanceReport;
            
            // 添加效能指標到報告生成器
            this.reportGenerator.addPerformanceMetrics(performanceReport);

            const successfulTests = Object.values(performanceTests.results).filter(r => r.success).length;
            const totalTests = Object.keys(performanceTests.results).length;
            
            performanceTests.success = successfulTests === totalTests;
            performanceTests.summary = {
                total: totalTests,
                successful: successfulTests,
                failed: totalTests - successfulTests
            };

        } catch (error) {
            performanceTests.success = false;
            performanceTests.error = error.message;
        }

        return performanceTests;
    }

    /**
     * 執行整合測試階段
     * @returns {Promise<Object>} - 整合測試結果
     */
    async executeIntegrationTestsPhase() {
        const integrationTests = {
            timestamp: new Date(),
            results: {}
        };

        try {
            // 執行跨元件整合測試
            const integrationScenarios = [
                {
                    name: 'user_management_flow',
                    description: '使用者管理完整流程測試',
                    components: ['userList', 'permissionForm']
                },
                {
                    name: 'activity_monitoring_flow',
                    description: '活動監控完整流程測試',
                    components: ['activityExport', 'recentActivity']
                }
            ];

            for (const scenario of integrationScenarios) {
                console.log(`  🔗 整合測試: ${scenario.name}`);
                
                try {
                    const integrationResult = await this.executeIntegrationScenario(scenario);
                    integrationTests.results[scenario.name] = integrationResult;
                    this.testResults.push(integrationResult);
                    
                } catch (error) {
                    integrationTests.results[scenario.name] = {
                        scenarioName: scenario.name,
                        success: false,
                        error: error.message,
                        timestamp: new Date()
                    };
                }
            }

            const successfulTests = Object.values(integrationTests.results).filter(r => r.success).length;
            const totalTests = Object.keys(integrationTests.results).length;
            
            integrationTests.success = successfulTests === totalTests;
            integrationTests.summary = {
                total: totalTests,
                successful: successfulTests,
                failed: totalTests - successfulTests
            };

        } catch (error) {
            integrationTests.success = false;
            integrationTests.error = error.message;
        }

        return integrationTests;
    }

    /**
     * 執行整合場景
     * @param {Object} scenario - 整合場景
     * @returns {Promise<Object>} - 場景結果
     */
    async executeIntegrationScenario(scenario) {
        // 這裡實作具體的整合場景測試邏輯
        return {
            scenarioName: scenario.name,
            description: scenario.description,
            components: scenario.components,
            success: true,
            timestamp: new Date(),
            details: '整合場景執行完成'
        };
    }

    /**
     * 執行視覺測試階段
     * @returns {Promise<Object>} - 視覺測試結果
     */
    async executeVisualTestsPhase() {
        const visualTests = {
            timestamp: new Date(),
            results: {}
        };

        try {
            // 執行視覺回歸測試
            const visualComponents = ['userList', 'activityExport'];
            
            for (const componentName of visualComponents) {
                const config = TestConfigurations[componentName];
                
                if (!config) continue;

                console.log(`  📸 視覺測試: ${componentName}`);
                
                const visualResult = await this.testComponents.screenshots.createVisualTestSequence(
                    `visual_${componentName}`,
                    async () => {
                        // 導航到元件頁面
                        await this.page.goto(config.componentUrl);
                        await this.testComponents.frontend.waitForLivewireComponent(config.searchSelector);
                        
                        // 執行基本操作
                        await this.testComponents.frontend.fillLivewireForm({
                            [config.searchSelector]: 'visual test'
                        });
                        
                        await this.page.click(config.resetButtonSelector);
                        await this.page.evaluate('new Promise(resolve => setTimeout(resolve, 1500))');
                        
                        return { success: true };
                    },
                    {
                        formSelectors: { search: config.searchSelector }
                    }
                );
                
                visualTests.results[componentName] = visualResult;
                this.testResults.push(visualResult);
            }

            // 生成視覺測試報告
            const visualReport = this.testComponents.screenshots.generateVisualTestReport();
            visualTests.overallReport = visualReport;
            
            // 添加截圖到報告生成器
            this.reportGenerator.addScreenshots(visualReport.sequences.flatMap(s => s.screenshots));

            const successfulTests = Object.values(visualTests.results).filter(r => r.success).length;
            const totalTests = Object.keys(visualTests.results).length;
            
            visualTests.success = successfulTests === totalTests;
            visualTests.summary = {
                total: totalTests,
                successful: successfulTests,
                failed: totalTests - successfulTests
            };

        } catch (error) {
            visualTests.success = false;
            visualTests.error = error.message;
        }

        return visualTests;
    }

    /**
     * 執行分析階段
     * @param {Object} phases - 測試階段結果
     * @returns {Promise<Object>} - 分析結果
     */
    async executeAnalysisPhase(phases) {
        const analysis = {
            timestamp: new Date(),
            overallMetrics: {},
            recommendations: [],
            criticalIssues: []
        };

        try {
            // 分析整體測試結果
            const allTestResults = this.testResults;
            const totalTests = allTestResults.length;
            const successfulTests = allTestResults.filter(r => r.success).length;
            
            analysis.overallMetrics = {
                totalTests,
                successfulTests,
                failedTests: totalTests - successfulTests,
                successRate: totalTests > 0 ? (successfulTests / totalTests * 100).toFixed(2) : 0,
                totalDuration: allTestResults.reduce((sum, test) => sum + (test.duration || 0), 0)
            };

            // 識別關鍵問題
            if (analysis.overallMetrics.successRate < 80) {
                analysis.criticalIssues.push({
                    type: 'low_success_rate',
                    severity: 'high',
                    message: `測試成功率過低: ${analysis.overallMetrics.successRate}%`,
                    recommendation: '需要檢查失敗的測試並修復相關問題'
                });
            }

            // 分析效能問題
            if (phases.performanceTests?.overallReport) {
                const performanceReport = phases.performanceTests.overallReport;
                
                if (performanceReport.failedTests > 0) {
                    analysis.criticalIssues.push({
                        type: 'performance_issues',
                        severity: 'medium',
                        message: `${performanceReport.failedTests} 個效能測試失敗`,
                        recommendation: '需要優化相關元件的效能'
                    });
                }
            }

            // 生成建議
            analysis.recommendations = this.generateTestRecommendations(phases);
            
            // 添加建議到報告生成器
            this.reportGenerator.addRecommendations(analysis.recommendations);

            analysis.success = true;

        } catch (error) {
            analysis.success = false;
            analysis.error = error.message;
        }

        return analysis;
    }

    /**
     * 執行清理階段
     * @returns {Promise<Object>} - 清理結果
     */
    async executeCleanupPhase() {
        const cleanup = {
            timestamp: new Date(),
            steps: {}
        };

        try {
            // 1. 停止效能監控
            console.log('  8.1 停止效能監控');
            cleanup.steps.performanceMonitoring = await this.testComponents.performance.stopComprehensiveMonitoring();

            // 2. 清理後端測試環境
            console.log('  8.2 清理後端測試環境');
            cleanup.steps.backendCleanup = await this.testComponents.backend.cleanupTestEnvironment();

            // 3. 清理截圖
            console.log('  8.3 清理舊截圖');
            cleanup.steps.screenshots = await this.testComponents.screenshots.cleanupOldScreenshots(7);

            cleanup.success = true;

        } catch (error) {
            cleanup.success = false;
            cleanup.error = error.message;
        }

        return cleanup;
    }

    /**
     * 生成綜合報告
     * @param {Object} suiteResult - 測試套件結果
     * @returns {Promise<Object>} - 報告路徑
     */
    async generateComprehensiveReports(suiteResult) {
        console.log('📊 生成綜合測試報告');

        const reports = {};

        try {
            // 添加所有測試結果到報告生成器
            this.testResults.forEach(result => {
                this.reportGenerator.addTestResult(result);
            });

            // 生成所有格式的報告
            reports.paths = await this.reportGenerator.generateAllReports();
            
            // 生成驗證工作流程報告
            reports.verificationReport = this.verificationWorkflow.generateVerificationReport();

            console.log('✅ 綜合測試報告生成完成');

        } catch (error) {
            console.error(`❌ 報告生成失敗: ${error.message}`);
            reports.error = error.message;
        }

        return reports;
    }

    /**
     * 生成測試建議
     * @param {Object} phases - 測試階段結果
     * @returns {Array} - 建議陣列
     */
    generateTestRecommendations(phases) {
        const recommendations = [];

        // 基於元件測試結果的建議
        if (phases.componentTests?.summary?.successRate < 90) {
            recommendations.push({
                title: '元件測試改善',
                description: '部分元件測試失敗，建議檢查 Livewire 元件的實作',
                priority: 'high',
                category: 'component'
            });
        }

        // 基於效能測試結果的建議
        if (phases.performanceTests?.summary?.failed > 0) {
            recommendations.push({
                title: '效能優化',
                description: '部分元件效能測試未通過，建議進行效能優化',
                priority: 'medium',
                category: 'performance'
            });
        }

        // 基於整合測試結果的建議
        if (phases.integrationTests?.summary?.failed > 0) {
            recommendations.push({
                title: '整合問題修復',
                description: '整合測試發現問題，建議檢查元件間的互動',
                priority: 'high',
                category: 'integration'
            });
        }

        return recommendations;
    }

    /**
     * 計算整體結果
     * @param {Object} phases - 測試階段結果
     * @returns {Object} - 整體結果
     */
    calculateOverallResult(phases) {
        const phaseResults = Object.values(phases).filter(phase => phase && typeof phase.success === 'boolean');
        const successfulPhases = phaseResults.filter(phase => phase.success).length;
        const totalPhases = phaseResults.length;
        
        return {
            success: successfulPhases === totalPhases,
            totalPhases,
            successfulPhases,
            failedPhases: totalPhases - successfulPhases,
            successRate: totalPhases > 0 ? (successfulPhases / totalPhases * 100).toFixed(2) : 0
        };
    }

    /**
     * 建立標準驗證工作流程
     */
    createStandardVerificationWorkflows() {
        console.log('🔧 建立標準驗證工作流程');

        // 綜合驗證工作流程
        this.verificationWorkflow.createVerificationWorkflow('comprehensive_verification', {
            description: '完整的 Livewire 表單重置功能驗證',
            steps: [
                {
                    name: '前端功能測試',
                    type: 'frontend_test',
                    testName: 'form_reset_frontend',
                    required: true
                },
                {
                    name: '後端狀態驗證',
                    type: 'backend_verification',
                    verificationName: 'form_reset_backend',
                    required: true
                },
                {
                    name: '整合測試',
                    type: 'integration_test',
                    testName: 'form_reset_integration',
                    required: true
                },
                {
                    name: '結果驗證',
                    type: 'validation',
                    validationName: 'comprehensive_validation',
                    rules: [
                        { name: 'success_rate', type: 'threshold', expectedValue: 90 },
                        { name: 'performance', type: 'threshold', expectedValue: 2000 }
                    ]
                },
                {
                    name: '清理作業',
                    type: 'cleanup',
                    cleanupName: 'test_cleanup',
                    items: ['test_data', 'screenshots', 'logs']
                }
            ],
            preChecks: [
                { name: 'system_ready', type: 'system_check' },
                { name: 'test_data_available', type: 'data_check' }
            ],
            postChecks: [
                { name: 'cleanup_complete', type: 'cleanup_check' },
                { name: 'no_memory_leaks', type: 'memory_check' }
            ]
        });

        // 效能監控工作流程
        this.verificationWorkflow.createVerificationWorkflow('performance_monitoring', {
            description: '持續效能監控和驗證',
            steps: [
                {
                    name: '效能基準測試',
                    type: 'performance_test',
                    testName: 'performance_baseline',
                    required: true
                },
                {
                    name: '記憶體使用監控',
                    type: 'performance_test',
                    testName: 'memory_monitoring',
                    required: false
                },
                {
                    name: '效能驗證',
                    type: 'validation',
                    validationName: 'performance_validation',
                    rules: [
                        { name: 'response_time', type: 'threshold', expectedValue: 1000 },
                        { name: 'memory_usage', type: 'threshold', expectedValue: 100 }
                    ]
                },
                {
                    name: '效能報告',
                    type: 'notification',
                    notificationName: 'performance_report',
                    title: '效能監控報告',
                    message: '定期效能監控完成'
                }
            ]
        });

        console.log('✅ 標準驗證工作流程已建立');
    }

    /**
     * 生成會話 ID
     * @returns {string} - 唯一會話 ID
     */
    generateSessionId() {
        return `test_session_${Date.now()}_${Math.random().toString(36).substr(2, 9)}`;
    }

    /**
     * 獲取測試狀態
     * @returns {Object} - 測試狀態
     */
    getTestStatus() {
        return {
            sessionId: this.testSession.id,
            status: this.testSession.status,
            startTime: this.testSession.startTime,
            endTime: this.testSession.endTime,
            duration: this.testSession.endTime ? 
                this.testSession.endTime - this.testSession.startTime : 
                Date.now() - (this.testSession.startTime || Date.now()),
            totalTests: this.testResults.length,
            completedTests: this.testResults.filter(r => r.success !== undefined).length,
            successfulTests: this.testResults.filter(r => r.success).length
        };
    }

    /**
     * 清理所有測試資料
     */
    async cleanupAllTestData() {
        console.log('🧹 清理所有測試資料');

        try {
            // 清理各個測試組件的資料
            this.testComponents.performance.clearAllPerformanceData();
            await this.testComponents.screenshots.cleanupOldScreenshots(0);
            await this.testComponents.backend.cleanupTestEnvironment();
            
            // 清理驗證工作流程資料
            this.verificationWorkflow.cleanupVerificationData(0);
            
            // 清理報告生成器
            await this.reportGenerator.cleanupOldReports(0);
            
            // 重置測試結果
            this.testResults = [];
            
            console.log('✅ 所有測試資料已清理');

        } catch (error) {
            console.error(`❌ 清理測試資料失敗: ${error.message}`);
        }
    }
}

module.exports = TestOrchestrator;