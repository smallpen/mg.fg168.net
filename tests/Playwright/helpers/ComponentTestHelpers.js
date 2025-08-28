/**
 * 元件測試輔助函數
 * 針對不同類型的 Livewire 元件提供專用的測試方法
 */

class ComponentTestHelpers {
    constructor(page, testSuite) {
        this.page = page;
        this.testSuite = testSuite;
    }

    /**
     * 列表篩選器元件測試
     * @param {Object} config - 測試配置
     */
    async testListFilterComponent(config) {
        const {
            componentUrl,
            searchSelector = '#search',
            filterSelectors = {},
            resetButtonSelector = 'button[wire\\:click="resetFilters"]',
            expectedResetValues = {}
        } = config;

        console.log(`🧪 測試列表篩選器元件: ${componentUrl}`);

        // 導航到元件頁面
        await this.page.goto(componentUrl);
        await this.testSuite.waitForLivewireComponent(searchSelector);

        // 設定篩選條件
        const testValues = {
            [searchSelector]: 'test search',
            ...Object.fromEntries(
                Object.entries(filterSelectors).map(([key, selector]) => [selector, 'test_value'])
            )
        };

        await this.testSuite.fillLivewireForm(testValues);

        // 驗證篩選條件已設定
        const preResetValidation = await this.testSuite.validateFormFields(testValues);
        
        // 執行重置
        const resetResult = await this.testSuite.executeFormReset(
            resetButtonSelector,
            {
                [searchSelector]: '',
                ...expectedResetValues
            }
        );

        return {
            componentType: 'ListFilter',
            preResetValidation,
            resetResult,
            success: resetResult.allFieldsReset
        };
    }

    /**
     * 模態表單元件測試
     * @param {Object} config - 測試配置
     */
    async testModalFormComponent(config) {
        const {
            componentUrl,
            openModalSelector,
            formSelectors = {},
            resetButtonSelector,
            closeModalSelector,
            expectedResetValues = {}
        } = config;

        console.log(`🧪 測試模態表單元件: ${componentUrl}`);

        // 導航到元件頁面
        await this.page.goto(componentUrl);
        
        // 開啟模態
        await this.page.click(openModalSelector);
        await this.page.waitForSelector(Object.values(formSelectors)[0], { timeout: 5000 });

        // 填寫表單
        const testValues = Object.fromEntries(
            Object.entries(formSelectors).map(([key, selector]) => [selector, `test_${key}`])
        );

        await this.testSuite.fillLivewireForm(testValues);

        // 執行重置
        const resetResult = await this.testSuite.executeFormReset(
            resetButtonSelector,
            expectedResetValues
        );

        // 關閉模態（如果有關閉按鈕）
        if (closeModalSelector) {
            await this.page.click(closeModalSelector);
        }

        return {
            componentType: 'ModalForm',
            resetResult,
            success: resetResult.allFieldsReset
        };
    }

    /**
     * 設定表單元件測試
     * @param {Object} config - 測試配置
     */
    async testSettingsFormComponent(config) {
        const {
            componentUrl,
            formSelectors = {},
            resetButtonSelector,
            saveButtonSelector,
            expectedResetValues = {}
        } = config;

        console.log(`🧪 測試設定表單元件: ${componentUrl}`);

        // 導航到元件頁面
        await this.page.goto(componentUrl);
        await this.testSuite.waitForLivewireComponent(Object.values(formSelectors)[0]);

        // 記錄原始值
        const originalValues = {};
        for (const [key, selector] of Object.entries(formSelectors)) {
            try {
                originalValues[selector] = await this.page.inputValue(selector);
            } catch (error) {
                originalValues[selector] = '';
            }
        }

        // 修改設定值
        const testValues = Object.fromEntries(
            Object.entries(formSelectors).map(([key, selector]) => [selector, `modified_${key}`])
        );

        await this.testSuite.fillLivewireForm(testValues);

        // 執行重置
        const resetResult = await this.testSuite.executeFormReset(
            resetButtonSelector,
            expectedResetValues.length > 0 ? expectedResetValues : originalValues
        );

        return {
            componentType: 'SettingsForm',
            originalValues,
            resetResult,
            success: resetResult.allFieldsReset
        };
    }

    /**
     * 監控控制項元件測試
     * @param {Object} config - 測試配置
     */
    async testMonitoringControlComponent(config) {
        const {
            componentUrl,
            controlSelectors = {},
            resetButtonSelector,
            expectedResetValues = {}
        } = config;

        console.log(`🧪 測試監控控制項元件: ${componentUrl}`);

        // 導航到元件頁面
        await this.page.goto(componentUrl);
        await this.testSuite.waitForLivewireComponent(Object.values(controlSelectors)[0]);

        // 修改控制項設定
        const testValues = {};
        for (const [key, selector] of Object.entries(controlSelectors)) {
            if (selector.includes('select')) {
                // 下拉選單
                testValues[selector] = 'test_option';
            } else if (selector.includes('checkbox')) {
                // 核取方塊
                await this.page.check(selector);
            } else {
                // 輸入欄位
                testValues[selector] = `test_${key}`;
            }
        }

        if (Object.keys(testValues).length > 0) {
            await this.testSuite.fillLivewireForm(testValues);
        }

        // 執行重置
        const resetResult = await this.testSuite.executeFormReset(
            resetButtonSelector,
            expectedResetValues
        );

        return {
            componentType: 'MonitoringControl',
            resetResult,
            success: resetResult.allFieldsReset
        };
    }

    /**
     * 批量操作測試
     * @param {Object} config - 測試配置
     */
    async testBulkOperations(config) {
        const {
            componentUrl,
            itemCheckboxSelector = 'input[type="checkbox"][value]',
            bulkResetSelector = 'button[wire\\:key*="bulk-reset"]',
            searchSelector = '#search'
        } = config;

        console.log(`🧪 測試批量操作: ${componentUrl}`);

        // 導航到元件頁面
        await this.page.goto(componentUrl);
        await this.testSuite.waitForLivewireComponent(searchSelector);

        // 設定篩選條件
        await this.testSuite.fillLivewireForm({
            [searchSelector]: 'bulk test'
        });

        // 選擇項目
        const checkboxes = await this.page.$$(itemCheckboxSelector);
        if (checkboxes.length > 0) {
            await checkboxes[0].click();
            
            // 等待批量操作區域出現
            try {
                await this.page.waitForSelector(bulkResetSelector, { timeout: 5000 });
                
                // 執行批量重置
                const resetResult = await this.testSuite.executeFormReset(
                    bulkResetSelector,
                    { [searchSelector]: '' }
                );

                return {
                    componentType: 'BulkOperations',
                    itemsSelected: 1,
                    resetResult,
                    success: resetResult.allFieldsReset
                };
            } catch (error) {
                console.log('ℹ️  批量操作區域未出現，使用一般重置');
                
                const resetResult = await this.testSuite.executeFormReset(
                    'button[wire\\:click="resetFilters"]',
                    { [searchSelector]: '' }
                );

                return {
                    componentType: 'BulkOperations',
                    itemsSelected: 0,
                    resetResult,
                    success: resetResult.allFieldsReset
                };
            }
        }

        return {
            componentType: 'BulkOperations',
            itemsSelected: 0,
            success: false,
            error: 'No items available for selection'
        };
    }

    /**
     * 響應式設計測試
     * @param {Object} config - 測試配置
     */
    async testResponsiveReset(config) {
        const {
            componentUrl,
            desktopSelectors = {},
            mobileSelectors = {},
            desktopResetSelector,
            mobileResetSelector
        } = config;

        console.log(`🧪 測試響應式重置功能: ${componentUrl}`);

        const viewports = [
            { name: 'Desktop', width: 1280, height: 720 },
            { name: 'Tablet', width: 768, height: 1024 },
            { name: 'Mobile', width: 375, height: 667 }
        ];

        const results = await this.testSuite.testResponsiveDesign(viewports, async (viewport) => {
            await this.page.goto(componentUrl);
            
            if (viewport.width >= 768) {
                // 桌面/平板版測試
                await this.testSuite.waitForLivewireComponent(Object.values(desktopSelectors)[0]);
                
                const testValues = Object.fromEntries(
                    Object.entries(desktopSelectors).map(([key, selector]) => [selector, `${viewport.name}_${key}`])
                );

                await this.testSuite.fillLivewireForm(testValues);
                
                return await this.testSuite.executeFormReset(
                    desktopResetSelector,
                    Object.fromEntries(
                        Object.keys(desktopSelectors).map(key => [desktopSelectors[key], ''])
                    )
                );
            } else {
                // 手機版測試
                await this.testSuite.waitForLivewireComponent(Object.values(mobileSelectors)[0]);
                
                const testValues = Object.fromEntries(
                    Object.entries(mobileSelectors).map(([key, selector]) => [selector, `${viewport.name}_${key}`])
                );

                await this.testSuite.fillLivewireForm(testValues);
                
                return await this.testSuite.executeFormReset(
                    mobileResetSelector,
                    Object.fromEntries(
                        Object.keys(mobileSelectors).map(key => [mobileSelectors[key], ''])
                    )
                );
            }
        });

        return {
            componentType: 'ResponsiveReset',
            results,
            success: results.every(r => r.success)
        };
    }

    /**
     * 效能測試
     * @param {Object} config - 測試配置
     */
    async testResetPerformance(config) {
        const {
            componentUrl,
            formSelectors = {},
            resetButtonSelector,
            iterations = 5
        } = config;

        console.log(`🧪 測試重置效能: ${componentUrl} (${iterations} 次迭代)`);

        const performanceResults = [];

        for (let i = 0; i < iterations; i++) {
            console.log(`  執行第 ${i + 1}/${iterations} 次測試`);
            
            await this.page.goto(componentUrl);
            await this.testSuite.waitForLivewireComponent(Object.values(formSelectors)[0]);

            // 填寫表單
            const testValues = Object.fromEntries(
                Object.entries(formSelectors).map(([key, selector]) => [selector, `perf_test_${i}_${key}`])
            );

            const fillStartTime = Date.now();
            await this.testSuite.fillLivewireForm(testValues);
            const fillEndTime = Date.now();

            // 執行重置並測量時間
            const resetStartTime = Date.now();
            await this.page.click(resetButtonSelector);
            await this.page.evaluate('new Promise(resolve => setTimeout(resolve, 1500))');
            const resetEndTime = Date.now();

            performanceResults.push({
                iteration: i + 1,
                fillTime: fillEndTime - fillStartTime,
                resetTime: resetEndTime - resetStartTime,
                totalTime: resetEndTime - fillStartTime
            });
        }

        const avgFillTime = performanceResults.reduce((sum, r) => sum + r.fillTime, 0) / iterations;
        const avgResetTime = performanceResults.reduce((sum, r) => sum + r.resetTime, 0) / iterations;
        const avgTotalTime = performanceResults.reduce((sum, r) => sum + r.totalTime, 0) / iterations;

        console.log(`  平均填寫時間: ${avgFillTime.toFixed(2)}ms`);
        console.log(`  平均重置時間: ${avgResetTime.toFixed(2)}ms`);
        console.log(`  平均總時間: ${avgTotalTime.toFixed(2)}ms`);

        return {
            componentType: 'PerformanceTest',
            iterations,
            results: performanceResults,
            averages: {
                fillTime: avgFillTime,
                resetTime: avgResetTime,
                totalTime: avgTotalTime
            },
            success: avgResetTime < 2000 // 重置時間應小於 2 秒
        };
    }
}

module.exports = ComponentTestHelpers;