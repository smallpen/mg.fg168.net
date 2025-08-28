/**
 * Livewire 表單重置功能測試執行器
 * 整合所有測試套件和輔助函數
 */

const { test, expect } = require('@playwright/test');
const FormResetTestSuite = require('./FormResetTestSuite');
const ComponentTestHelpers = require('./helpers/ComponentTestHelpers');
const { TestConfigurations, TestSuiteConfigurations } = require('./config/TestConfigurations');

class FormResetTestRunner {
    constructor() {
        this.testResults = [];
        this.screenshots = [];
        this.performanceMetrics = [];
    }

    /**
     * 執行完整的表單重置測試套件
     */
    static createTestSuite() {
        test.describe('Livewire 表單重置功能完整測試套件', () => {
            let testSuite;
            let componentHelpers;

            test.beforeEach(async ({ page }) => {
                testSuite = new FormResetTestSuite(page);
                componentHelpers = new ComponentTestHelpers(page, testSuite);

                // 登入系統
                const loginSuccess = await testSuite.livewireLogin();
                expect(loginSuccess).toBe(true);

                // 檢查 Livewire 連接
                const connectionInfo = await testSuite.checkLivewireConnection();
                expect(connectionInfo.livewireLoaded).toBe(true);
            });

            // 高優先級元件測試
            test.describe('高優先級元件測試', () => {
                TestSuiteConfigurations.highPriority.forEach(componentName => {
                    test(`${componentName} 篩選重置功能測試`, async ({ page }) => {
                        const config = TestConfigurations[componentName];
                        if (!config) {
                            test.skip(`配置不存在: ${componentName}`);
                            return;
                        }

                        console.log(`🧪 執行 ${componentName} 測試`);

                        const result = await componentHelpers.testListFilterComponent(config);
                        
                        testSuite.recordTestResult(
                            `${componentName}_reset_test`,
                            result.success,
                            result
                        );

                        expect(result.success).toBe(true);
                        console.log(`✅ ${componentName} 測試完成`);
                    });
                });
            });

            // 中優先級元件測試
            test.describe('中優先級元件測試', () => {
                TestSuiteConfigurations.mediumPriority.forEach(componentName => {
                    test(`${componentName} 表單重置功能測試`, async ({ page }) => {
                        const config = TestConfigurations[componentName];
                        if (!config) {
                            test.skip(`配置不存在: ${componentName}`);
                            return;
                        }

                        console.log(`🧪 執行 ${componentName} 測試`);

                        let result;
                        if (config.openModalSelector) {
                            // 模態表單測試
                            result = await componentHelpers.testModalFormComponent(config);
                        } else {
                            // 一般設定表單測試
                            result = await componentHelpers.testSettingsFormComponent(config);
                        }
                        
                        testSuite.recordTestResult(
                            `${componentName}_reset_test`,
                            result.success,
                            result
                        );

                        expect(result.success).toBe(true);
                        console.log(`✅ ${componentName} 測試完成`);
                    });
                });
            });

            // 監控元件測試
            test.describe('監控元件測試', () => {
                TestSuiteConfigurations.monitoringComponents.forEach(componentName => {
                    test(`${componentName} 控制項重置功能測試`, async ({ page }) => {
                        const config = TestConfigurations[componentName];
                        if (!config) {
                            test.skip(`配置不存在: ${componentName}`);
                            return;
                        }

                        console.log(`🧪 執行 ${componentName} 測試`);

                        const result = await componentHelpers.testMonitoringControlComponent(config);
                        
                        testSuite.recordTestResult(
                            `${componentName}_reset_test`,
                            result.success,
                            result
                        );

                        expect(result.success).toBe(true);
                        console.log(`✅ ${componentName} 測試完成`);
                    });
                });
            });

            // 響應式設計測試
            test.describe('響應式設計測試', () => {
                test('UserList 響應式重置功能測試', async ({ page }) => {
                    const config = TestConfigurations.userList;
                    
                    console.log('🧪 執行響應式重置測試');

                    const responsiveConfig = {
                        componentUrl: config.componentUrl,
                        desktopSelectors: {
                            search: config.searchSelector,
                            role: config.filterSelectors.role,
                            status: config.filterSelectors.status
                        },
                        mobileSelectors: config.mobileSelectors,
                        desktopResetSelector: config.resetButtonSelector,
                        mobileResetSelector: config.mobileResetSelector
                    };

                    const result = await componentHelpers.testResponsiveReset(responsiveConfig);
                    
                    testSuite.recordTestResult(
                        'responsive_reset_test',
                        result.success,
                        result
                    );

                    expect(result.success).toBe(true);
                    console.log('✅ 響應式重置測試完成');
                });
            });

            // 批量操作測試
            test.describe('批量操作測試', () => {
                test('UserList 批量操作重置功能測試', async ({ page }) => {
                    const config = TestConfigurations.userList;
                    
                    console.log('🧪 執行批量操作測試');

                    const bulkConfig = {
                        componentUrl: config.componentUrl,
                        searchSelector: config.searchSelector,
                        bulkResetSelector: config.bulkResetSelector
                    };

                    const result = await componentHelpers.testBulkOperations(bulkConfig);
                    
                    testSuite.recordTestResult(
                        'bulk_operations_test',
                        result.success,
                        result
                    );

                    expect(result.success).toBe(true);
                    console.log('✅ 批量操作測試完成');
                });
            });

            // 前後端狀態同步測試
            test.describe('前後端狀態同步測試', () => {
                test('UserList 前後端狀態同步驗證', async ({ page }) => {
                    const config = TestConfigurations.userList;
                    
                    console.log('🧪 執行前後端狀態同步測試');

                    // 導航到頁面
                    await page.goto(config.componentUrl);
                    await testSuite.waitForLivewireComponent(config.searchSelector);

                    // 設定篩選條件
                    await testSuite.fillLivewireForm({
                        [config.searchSelector]: 'sync test',
                        [config.filterSelectors.role]: 'admin',
                        [config.filterSelectors.status]: 'active'
                    });

                    // 驗證前端狀態
                    const preResetValidation = await testSuite.validateFormFields({
                        [config.searchSelector]: 'sync test',
                        [config.filterSelectors.role]: 'admin',
                        [config.filterSelectors.status]: 'active'
                    });

                    // 執行重置
                    const resetResult = await testSuite.executeFormReset(
                        config.resetButtonSelector,
                        config.expectedResetValues
                    );

                    // 驗證後端狀態同步
                    const syncResult = await testSuite.validateFrontendBackendSync(
                        config.statusSelector,
                        ['搜尋=""', '角色="all"', '狀態="all"']
                    );

                    const overallSuccess = resetResult.allFieldsReset && syncResult.synced;
                    
                    testSuite.recordTestResult(
                        'frontend_backend_sync_test',
                        overallSuccess,
                        { preResetValidation, resetResult, syncResult }
                    );

                    expect(overallSuccess).toBe(true);
                    console.log('✅ 前後端狀態同步測試完成');
                });
            });

            // AJAX 請求監控測試
            test.describe('AJAX 請求監控測試', () => {
                test('重置操作 AJAX 請求監控', async ({ page }) => {
                    const config = TestConfigurations.userList;
                    
                    console.log('🧪 執行 AJAX 請求監控測試');

                    // 導航到頁面
                    await page.goto(config.componentUrl);
                    await testSuite.waitForLivewireComponent(config.searchSelector);

                    // 監控 AJAX 請求
                    const ajaxResult = await testSuite.monitorAjaxRequests(async () => {
                        // 設定篩選條件
                        await testSuite.fillLivewireForm({
                            [config.searchSelector]: 'ajax test'
                        });

                        // 執行重置
                        await page.click(config.resetButtonSelector);
                        await page.evaluate('new Promise(resolve => setTimeout(resolve, 1500))');
                    });

                    testSuite.recordTestResult(
                        'ajax_monitoring_test',
                        ajaxResult.requestCount > 0,
                        ajaxResult
                    );

                    expect(ajaxResult.requestCount).toBeGreaterThan(0);
                    console.log(`✅ AJAX 請求監控測試完成 (捕獲 ${ajaxResult.requestCount} 個請求)`);
                });
            });

            // 效能測試
            test.describe('效能測試', () => {
                test('表單重置效能測試', async ({ page }) => {
                    const config = TestConfigurations.userList;
                    
                    console.log('🧪 執行效能測試');

                    const performanceConfig = {
                        componentUrl: config.componentUrl,
                        formSelectors: {
                            search: config.searchSelector,
                            role: config.filterSelectors.role,
                            status: config.filterSelectors.status
                        },
                        resetButtonSelector: config.resetButtonSelector,
                        iterations: TestSuiteConfigurations.performanceTest.iterations
                    };

                    const result = await componentHelpers.testResetPerformance(performanceConfig);
                    
                    testSuite.recordTestResult(
                        'performance_test',
                        result.success,
                        result
                    );

                    // 驗證效能指標
                    expect(result.averages.resetTime).toBeLessThan(
                        TestSuiteConfigurations.performanceTest.maxResetTime
                    );
                    expect(result.averages.fillTime).toBeLessThan(
                        TestSuiteConfigurations.performanceTest.maxFillTime
                    );
                    expect(result.averages.totalTime).toBeLessThan(
                        TestSuiteConfigurations.performanceTest.maxTotalTime
                    );

                    console.log('✅ 效能測試完成');
                });
            });

            // 測試報告生成
            test.afterAll(async ({ page }) => {
                console.log('📊 生成測試報告');
                
                const report = testSuite.generateTestReport();
                
                // 輸出測試摘要
                console.log('\n=== 測試摘要 ===');
                console.log(`總測試數: ${report.summary.totalTests}`);
                console.log(`通過測試: ${report.summary.passedTests}`);
                console.log(`失敗測試: ${report.summary.failedTests}`);
                console.log(`成功率: ${((report.summary.passedTests / report.summary.totalTests) * 100).toFixed(2)}%`);
                
                // 輸出截圖資訊
                if (report.screenshots.length > 0) {
                    console.log(`\n=== 截圖記錄 ===`);
                    report.screenshots.forEach(screenshot => {
                        console.log(`${screenshot.name}: ${screenshot.filename}`);
                    });
                }
                
                // 輸出失敗的測試
                const failedTests = report.results.filter(r => !r.success);
                if (failedTests.length > 0) {
                    console.log(`\n=== 失敗測試詳情 ===`);
                    failedTests.forEach(test => {
                        console.log(`❌ ${test.testName}: ${JSON.stringify(test.details, null, 2)}`);
                    });
                }

                console.log('\n✅ 測試報告生成完成');
            });
        });
    }
}

// 建立並匯出測試套件
FormResetTestRunner.createTestSuite();

module.exports = FormResetTestRunner;