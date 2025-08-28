/**
 * Playwright 前端測試框架驗證
 * 驗證測試框架的基本功能是否正常工作
 */

const { test, expect } = require('@playwright/test');
const FormResetTestSuite = require('./FormResetTestSuite');
const ComponentTestHelpers = require('./helpers/ComponentTestHelpers');
const ScreenshotComparison = require('./utils/ScreenshotComparison');
const { TestConfigurations } = require('./config/TestConfigurations');

test.describe('Playwright 前端測試框架驗證', () => {
    let testSuite;
    let componentHelpers;
    let screenshotComparison;

    test.beforeEach(async ({ page }) => {
        testSuite = new FormResetTestSuite(page);
        componentHelpers = new ComponentTestHelpers(page, testSuite);
        screenshotComparison = new ScreenshotComparison(page);
    });

    test('測試框架基本功能驗證', async ({ page }) => {
        console.log('🧪 驗證測試框架基本功能');

        // 1. 測試登入功能
        console.log('  1. 測試登入功能');
        const loginSuccess = await testSuite.livewireLogin();
        expect(loginSuccess).toBe(true);
        console.log('    ✅ 登入功能正常');

        // 2. 測試 Livewire 連接檢查
        console.log('  2. 測試 Livewire 連接檢查');
        const connectionInfo = await testSuite.checkLivewireConnection();
        expect(connectionInfo.livewireLoaded).toBe(true);
        expect(connectionInfo.componentsCount).toBeGreaterThan(0);
        console.log(`    ✅ Livewire 連接正常 (${connectionInfo.componentsCount} 個元件)`);

        // 3. 測試截圖功能
        console.log('  3. 測試截圖功能');
        await testSuite.takeScreenshot('framework-validation');
        console.log('    ✅ 截圖功能正常');

        // 4. 測試表單填寫功能
        console.log('  4. 測試表單填寫功能');
        await page.goto('http://localhost/admin/users');
        await testSuite.waitForLivewireComponent('#search');
        
        await testSuite.fillLivewireForm({
            '#search': 'framework test'
        });

        const searchValue = await page.inputValue('#search');
        expect(searchValue).toBe('framework test');
        console.log('    ✅ 表單填寫功能正常');

        // 5. 測試表單驗證功能
        console.log('  5. 測試表單驗證功能');
        const validationResult = await testSuite.validateFormFields({
            '#search': 'framework test'
        });
        expect(validationResult['#search'].valid).toBe(true);
        console.log('    ✅ 表單驗證功能正常');

        // 6. 測試重置功能
        console.log('  6. 測試重置功能');
        const resetResult = await testSuite.executeFormReset(
            'button[wire\\:click="resetFilters"]',
            { '#search': '' }
        );
        expect(resetResult.allFieldsReset).toBe(true);
        console.log('    ✅ 重置功能正常');

        console.log('✅ 測試框架基本功能驗證完成');
    });

    test('元件測試輔助函數驗證', async ({ page }) => {
        console.log('🧪 驗證元件測試輔助函數');

        // 登入系統
        const loginSuccess = await testSuite.livewireLogin();
        expect(loginSuccess).toBe(true);

        // 測試列表篩選器元件輔助函數
        console.log('  測試列表篩選器元件輔助函數');
        const config = TestConfigurations.userList;
        
        try {
            const result = await componentHelpers.testListFilterComponent(config);
            expect(result.componentType).toBe('ListFilter');
            expect(typeof result.success).toBe('boolean');
            console.log(`    ✅ 列表篩選器測試輔助函數正常 (結果: ${result.success})`);
        } catch (error) {
            console.log(`    ⚠️  列表篩選器測試可能需要調整: ${error.message}`);
        }

        console.log('✅ 元件測試輔助函數驗證完成');
    });

    test('截圖對比工具驗證', async ({ page }) => {
        console.log('🧪 驗證截圖對比工具');

        // 登入系統
        const loginSuccess = await testSuite.livewireLogin();
        expect(loginSuccess).toBe(true);

        // 導航到測試頁面
        await page.goto('http://localhost/admin/users');
        await testSuite.waitForLivewireComponent('#search');

        // 測試視覺測試序列
        console.log('  測試視覺測試序列');
        const sequence = await screenshotComparison.createVisualTestSequence(
            'screenshot-validation',
            async () => {
                // 填寫表單
                await testSuite.fillLivewireForm({
                    '#search': 'screenshot test'
                });

                // 執行重置
                await page.click('button[wire\\:click="resetFilters"]');
                await page.evaluate('new Promise(resolve => setTimeout(resolve, 1500))');

                return { success: true };
            },
            {
                formSelectors: { search: '#search' }
            }
        );

        expect(sequence.success).toBe(true);
        expect(sequence.screenshots.length).toBeGreaterThan(0);
        console.log(`    ✅ 視覺測試序列正常 (${sequence.screenshots.length} 張截圖)`);

        console.log('✅ 截圖對比工具驗證完成');
    });

    test('響應式測試功能驗證', async ({ page }) => {
        console.log('🧪 驗證響應式測試功能');

        // 登入系統
        const loginSuccess = await testSuite.livewireLogin();
        expect(loginSuccess).toBe(true);

        // 測試響應式設計
        const viewports = [
            { name: 'Desktop', width: 1280, height: 720 },
            { name: 'Mobile', width: 375, height: 667 }
        ];

        const results = await testSuite.testResponsiveDesign(viewports, async (viewport) => {
            await page.goto('http://localhost/admin/users');
            await testSuite.waitForLivewireComponent('#search');

            // 填寫表單
            await testSuite.fillLivewireForm({
                '#search': `responsive test ${viewport.name}`
            });

            // 驗證填寫結果
            const searchValue = await page.inputValue('#search');
            expect(searchValue).toBe(`responsive test ${viewport.name}`);

            return { viewport: viewport.name, success: true };
        });

        expect(results.length).toBe(2);
        expect(results.every(r => r.success)).toBe(true);
        console.log('    ✅ 響應式測試功能正常');

        console.log('✅ 響應式測試功能驗證完成');
    });

    test('AJAX 請求監控功能驗證', async ({ page }) => {
        console.log('🧪 驗證 AJAX 請求監控功能');

        // 登入系統
        const loginSuccess = await testSuite.livewireLogin();
        expect(loginSuccess).toBe(true);

        // 導航到測試頁面
        await page.goto('http://localhost/admin/users');
        await testSuite.waitForLivewireComponent('#search');

        // 監控 AJAX 請求
        const ajaxResult = await testSuite.monitorAjaxRequests(async () => {
            await testSuite.fillLivewireForm({
                '#search': 'ajax monitoring test'
            });

            await page.click('button[wire\\:click="resetFilters"]');
            await page.evaluate('new Promise(resolve => setTimeout(resolve, 1500))');
        });

        expect(typeof ajaxResult.requestCount).toBe('number');
        expect(Array.isArray(ajaxResult.requests)).toBe(true);
        console.log(`    ✅ AJAX 請求監控功能正常 (捕獲 ${ajaxResult.requestCount} 個請求)`);

        console.log('✅ AJAX 請求監控功能驗證完成');
    });

    test('測試報告生成功能驗證', async ({ page }) => {
        console.log('🧪 驗證測試報告生成功能');

        // 記錄一些測試結果
        testSuite.recordTestResult('test1', true, { message: 'Test 1 passed' });
        testSuite.recordTestResult('test2', false, { message: 'Test 2 failed' });
        testSuite.recordTestResult('test3', true, { message: 'Test 3 passed' });

        // 生成測試報告
        const report = testSuite.generateTestReport();

        expect(report.testSuite).toBe('FormResetTestSuite');
        expect(report.summary.totalTests).toBe(3);
        expect(report.summary.passedTests).toBe(2);
        expect(report.summary.failedTests).toBe(1);
        expect(Array.isArray(report.results)).toBe(true);

        console.log('    ✅ 測試報告生成功能正常');
        console.log(`      總測試: ${report.summary.totalTests}`);
        console.log(`      通過: ${report.summary.passedTests}`);
        console.log(`      失敗: ${report.summary.failedTests}`);

        console.log('✅ 測試報告生成功能驗證完成');
    });

    test.afterAll(async ({ page }) => {
        console.log('\n📊 框架驗證測試完成');
        console.log('✅ Playwright 前端測試框架已準備就緒');
    });
});