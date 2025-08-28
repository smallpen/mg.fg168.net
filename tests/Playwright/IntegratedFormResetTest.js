/**
 * 整合式表單重置測試
 * 結合 Playwright 前端測試和 MySQL 後端驗證
 */

const { test, expect } = require('@playwright/test');
const FormResetTestSuite = require('./FormResetTestSuite');
const BackendVerificationSuite = require('./backend/BackendVerificationSuite');
const ScreenshotComparison = require('./utils/ScreenshotComparison');
const { TestConfigurations } = require('./config/TestConfigurations');

class IntegratedFormResetTest {
    constructor(page) {
        this.page = page;
        this.frontendSuite = new FormResetTestSuite(page);
        this.backendSuite = new BackendVerificationSuite();
        this.screenshotComparison = new ScreenshotComparison(page);
        this.testResults = [];
    }

    /**
     * 執行整合式元件測試
     * @param {string} componentName - 元件名稱
     * @param {Object} config - 測試配置
     * @returns {Promise<Object>} - 測試結果
     */
    async executeIntegratedComponentTest(componentName, config) {
        console.log(`🧪 執行 ${componentName} 整合式測試`);

        const testResult = {
            componentName,
            timestamp: new Date(),
            config,
            phases: {}
        };

        try {
            // 階段 1: 初始化
            console.log('  階段 1: 初始化測試環境');
            testResult.phases.initialization = await this.initializeIntegratedTest(componentName, config);

            // 階段 2: 前端操作前的後端狀態驗證
            console.log('  階段 2: 前端操作前的後端狀態驗證');
            testResult.phases.preOperationBackend = await this.backendSuite.verifyPreResetState(
                componentName,
                config.testData || {}
            );

            // 階段 3: 前端操作執行
            console.log('  階段 3: 執行前端操作');
            testResult.phases.frontendOperation = await this.executeFrontendOperation(componentName, config);

            // 階段 4: 前端操作後的後端狀態驗證
            console.log('  階段 4: 前端操作後的後端狀態驗證');
            testResult.phases.postOperationBackend = await this.backendSuite.verifyPostResetState(
                componentName,
                config.expectedState || {}
            );

            // 階段 5: 前後端狀態比較
            console.log('  階段 5: 前後端狀態比較');
            testResult.phases.stateComparison = await this.compareIntegratedStates(testResult.phases);

            // 階段 6: 生成整合測試報告
            console.log('  階段 6: 生成整合測試報告');
            testResult.phases.report = this.generateIntegratedTestReport(testResult);

            testResult.success = this.evaluateOverallSuccess(testResult.phases);
            console.log(`${testResult.success ? '✅' : '❌'} ${componentName} 整合式測試${testResult.success ? '成功' : '失敗'}`);

        } catch (error) {
            testResult.success = false;
            testResult.error = error.message;
            console.error(`❌ ${componentName} 整合式測試失敗: ${error.message}`);
        }

        this.testResults.push(testResult);
        return testResult;
    }

    /**
     * 初始化整合測試
     * @param {string} componentName - 元件名稱
     * @param {Object} config - 配置
     * @returns {Promise<Object>} - 初始化結果
     */
    async initializeIntegratedTest(componentName, config) {
        const initialization = {
            timestamp: new Date(),
            steps: {}
        };

        try {
            // 1. 登入系統
            console.log('    1.1 登入系統');
            const loginSuccess = await this.frontendSuite.livewireLogin();
            initialization.steps.login = { success: loginSuccess };

            if (!loginSuccess) {
                throw new Error('登入失敗');
            }

            // 2. 檢查 Livewire 連接
            console.log('    1.2 檢查 Livewire 連接');
            const connectionInfo = await this.frontendSuite.checkLivewireConnection();
            initialization.steps.livewireConnection = connectionInfo;

            // 3. 初始化後端測試環境
            console.log('    1.3 初始化後端測試環境');
            initialization.steps.backendInit = await this.backendSuite.initializeTestEnvironment();

            // 4. 導航到元件頁面
            console.log('    1.4 導航到元件頁面');
            await this.page.goto(config.componentUrl);
            await this.frontendSuite.waitForLivewireComponent(
                config.searchSelector || Object.values(config.formSelectors || {})[0]
            );
            initialization.steps.navigation = { success: true, url: config.componentUrl };

            // 5. 拍攝初始截圖
            console.log('    1.5 拍攝初始截圖');
            await this.screenshotComparison.captureBaseline(componentName);
            initialization.steps.screenshot = { success: true };

            initialization.success = true;

        } catch (error) {
            initialization.success = false;
            initialization.error = error.message;
        }

        return initialization;
    }

    /**
     * 執行前端操作
     * @param {string} componentName - 元件名稱
     * @param {Object} config - 配置
     * @returns {Promise<Object>} - 前端操作結果
     */
    async executeFrontendOperation(componentName, config) {
        const operation = {
            componentName,
            timestamp: new Date(),
            steps: {}
        };

        try {
            // 1. 建立視覺測試序列
            const visualSequence = await this.screenshotComparison.createVisualTestSequence(
                `${componentName}_reset_operation`,
                async () => {
                    // 2. 填寫表單
                    console.log('    3.1 填寫表單');
                    const testValues = this.generateTestValues(config);
                    await this.frontendSuite.fillLivewireForm(testValues);
                    operation.steps.fillForm = { success: true, values: testValues };

                    // 3. 驗證填寫結果
                    console.log('    3.2 驗證填寫結果');
                    const preResetValidation = await this.frontendSuite.validateFormFields(testValues);
                    operation.steps.preResetValidation = preResetValidation;

                    // 4. 監控 AJAX 請求並執行重置
                    console.log('    3.3 執行重置操作');
                    const ajaxMonitoring = await this.frontendSuite.monitorAjaxRequests(async () => {
                        const resetResult = await this.frontendSuite.executeFormReset(
                            config.resetButtonSelector,
                            config.expectedResetValues || {}
                        );
                        operation.steps.resetExecution = resetResult;
                        return resetResult;
                    });
                    operation.steps.ajaxMonitoring = ajaxMonitoring;

                    // 5. 驗證重置結果
                    console.log('    3.4 驗證重置結果');
                    const postResetValidation = await this.frontendSuite.validateFormFields(
                        config.expectedResetValues || {}
                    );
                    operation.steps.postResetValidation = postResetValidation;

                    return {
                        success: operation.steps.resetExecution?.allFieldsReset || false
                    };
                },
                {
                    formSelectors: config.formSelectors || {},
                    componentSelector: config.componentSelector
                }
            );

            operation.steps.visualSequence = visualSequence;
            operation.success = visualSequence.success && (operation.steps.resetExecution?.allFieldsReset || false);

        } catch (error) {
            operation.success = false;
            operation.error = error.message;
        }

        return operation;
    }

    /**
     * 生成測試值
     * @param {Object} config - 配置
     * @returns {Object} - 測試值
     */
    generateTestValues(config) {
        const testValues = {};

        if (config.searchSelector) {
            testValues[config.searchSelector] = 'integrated test search';
        }

        if (config.filterSelectors) {
            Object.entries(config.filterSelectors).forEach(([key, selector]) => {
                if (selector.includes('select')) {
                    testValues[selector] = 'test_option';
                } else {
                    testValues[selector] = `integrated_test_${key}`;
                }
            });
        }

        if (config.formSelectors) {
            Object.entries(config.formSelectors).forEach(([key, selector]) => {
                testValues[selector] = `integrated_test_${key}`;
            });
        }

        return testValues;
    }

    /**
     * 比較整合狀態
     * @param {Object} phases - 測試階段結果
     * @returns {Object} - 比較結果
     */
    async compareIntegratedStates(phases) {
        console.log('    5.1 比較前後端狀態');

        const comparison = {
            timestamp: new Date(),
            frontend: {},
            backend: {},
            integration: {}
        };

        try {
            // 前端狀態比較
            if (phases.frontendOperation?.steps?.preResetValidation && 
                phases.frontendOperation?.steps?.postResetValidation) {
                
                comparison.frontend = {
                    preReset: phases.frontendOperation.steps.preResetValidation,
                    postReset: phases.frontendOperation.steps.postResetValidation,
                    resetSuccessful: phases.frontendOperation.steps.resetExecution?.allFieldsReset || false
                };
            }

            // 後端狀態比較
            if (phases.preOperationBackend && phases.postOperationBackend) {
                comparison.backend = this.backendSuite.compareResetStates(
                    phases.preOperationBackend,
                    phases.postOperationBackend
                );
            }

            // 整合驗證
            comparison.integration = {
                frontendBackendSync: this.validateFrontendBackendSync(comparison.frontend, comparison.backend),
                ajaxRequestsDetected: phases.frontendOperation?.steps?.ajaxMonitoring?.requestCount > 0,
                visualChangesDetected: phases.frontendOperation?.steps?.visualSequence?.screenshots?.length > 0,
                dataIntegrityMaintained: comparison.backend?.summary?.dataIntegrityMaintained || false
            };

            comparison.success = 
                comparison.frontend?.resetSuccessful &&
                comparison.backend?.success &&
                comparison.integration?.frontendBackendSync &&
                comparison.integration?.dataIntegrityMaintained;

        } catch (error) {
            comparison.success = false;
            comparison.error = error.message;
        }

        return comparison;
    }

    /**
     * 驗證前後端同步
     * @param {Object} frontendState - 前端狀態
     * @param {Object} backendState - 後端狀態
     * @returns {boolean} - 是否同步
     */
    validateFrontendBackendSync(frontendState, backendState) {
        // 這裡實作前後端狀態同步的驗證邏輯
        // 例如：檢查前端重置是否對應到後端的重置事件記錄
        
        const frontendResetSuccessful = frontendState?.resetSuccessful || false;
        const backendResetEventsDetected = backendState?.summary?.resetEventsDetected || false;
        
        return frontendResetSuccessful && backendResetEventsDetected;
    }

    /**
     * 評估整體成功狀態
     * @param {Object} phases - 測試階段
     * @returns {boolean} - 是否成功
     */
    evaluateOverallSuccess(phases) {
        return (
            phases.initialization?.success &&
            phases.preOperationBackend?.success &&
            phases.frontendOperation?.success &&
            phases.postOperationBackend?.success &&
            phases.stateComparison?.success
        );
    }

    /**
     * 生成整合測試報告
     * @param {Object} testResult - 測試結果
     * @returns {Object} - 測試報告
     */
    generateIntegratedTestReport(testResult) {
        const report = {
            componentName: testResult.componentName,
            timestamp: new Date(),
            overallSuccess: testResult.success,
            summary: {
                phasesCompleted: Object.keys(testResult.phases).length,
                successfulPhases: Object.values(testResult.phases).filter(p => p.success).length,
                failedPhases: Object.values(testResult.phases).filter(p => !p.success).length
            },
            details: testResult.phases,
            recommendations: []
        };

        // 生成建議
        if (!report.overallSuccess) {
            if (!testResult.phases.initialization?.success) {
                report.recommendations.push({
                    title: '初始化失敗',
                    description: '檢查測試環境設定和系統狀態',
                    priority: 'high'
                });
            }

            if (!testResult.phases.frontendOperation?.success) {
                report.recommendations.push({
                    title: '前端操作失敗',
                    description: '檢查 Livewire 元件實作和前端邏輯',
                    priority: 'high'
                });
            }

            if (!testResult.phases.stateComparison?.success) {
                report.recommendations.push({
                    title: '狀態同步問題',
                    description: '檢查前後端狀態同步機制',
                    priority: 'medium'
                });
            }
        }

        console.log(`📊 ${testResult.componentName} 整合測試報告生成完成`);
        return report;
    }

    /**
     * 生成最終整合報告
     * @returns {Object} - 最終報告
     */
    generateFinalIntegratedReport() {
        const report = {
            timestamp: new Date(),
            totalTests: this.testResults.length,
            successfulTests: this.testResults.filter(r => r.success).length,
            failedTests: this.testResults.filter(r => !r.success).length,
            successRate: 0,
            testResults: this.testResults,
            summary: {
                frontendTestSuite: this.frontendSuite.generateTestReport(),
                backendVerificationSuite: this.backendSuite.generateFinalVerificationReport(),
                visualTestReport: this.screenshotComparison.generateVisualTestReport()
            }
        };

        report.successRate = report.totalTests > 0 
            ? (report.successfulTests / report.totalTests * 100).toFixed(2)
            : 0;

        console.log('\n=== 整合式表單重置測試最終報告 ===');
        console.log(`總測試數: ${report.totalTests}`);
        console.log(`成功測試: ${report.successfulTests}`);
        console.log(`失敗測試: ${report.failedTests}`);
        console.log(`成功率: ${report.successRate}%`);

        return report;
    }

    /**
     * 清理整合測試環境
     * @returns {Promise<Object>} - 清理結果
     */
    async cleanupIntegratedTestEnvironment() {
        console.log('🧹 清理整合測試環境');

        const cleanup = {
            timestamp: new Date(),
            steps: {}
        };

        try {
            // 1. 清理後端測試環境
            console.log('  1. 清理後端測試環境');
            cleanup.steps.backend = await this.backendSuite.cleanupTestEnvironment();

            // 2. 清理截圖
            console.log('  2. 清理舊截圖');
            cleanup.steps.screenshots = await this.screenshotComparison.cleanupOldScreenshots(7);

            // 3. 生成 MCP 執行腳本
            console.log('  3. 生成 MCP 執行腳本');
            cleanup.steps.mcpScript = this.backendSuite.generateMCPExecutionScript();

            cleanup.success = true;

        } catch (error) {
            cleanup.success = false;
            cleanup.error = error.message;
        }

        return cleanup;
    }
}

// 建立整合測試套件
test.describe('整合式表單重置測試套件', () => {
    let integratedTest;

    test.beforeEach(async ({ page }) => {
        integratedTest = new IntegratedFormResetTest(page);
    });

    // 高優先級元件整合測試
    test('UserList 元件整合測試', async ({ page }) => {
        const config = TestConfigurations.userList;
        const result = await integratedTest.executeIntegratedComponentTest('UserList', config);
        expect(result.success).toBe(true);
    });

    test('ActivityExport 元件整合測試', async ({ page }) => {
        const config = TestConfigurations.activityExport;
        const result = await integratedTest.executeIntegratedComponentTest('ActivityExport', config);
        expect(result.success).toBe(true);
    });

    test('PermissionAuditLog 元件整合測試', async ({ page }) => {
        const config = TestConfigurations.permissionAuditLog;
        const result = await integratedTest.executeIntegratedComponentTest('PermissionAuditLog', config);
        expect(result.success).toBe(true);
    });

    // 測試清理
    test.afterAll(async ({ page }) => {
        console.log('📊 生成最終整合報告');
        const finalReport = integratedTest.generateFinalIntegratedReport();
        
        console.log('🧹 執行清理作業');
        await integratedTest.cleanupIntegratedTestEnvironment();
        
        console.log('✅ 整合式表單重置測試套件執行完成');
    });
});

module.exports = IntegratedFormResetTest;