/**
 * 效能測試套件
 * 測量表單重置操作的效能指標
 */

class PerformanceTestSuite {
    constructor(page) {
        this.page = page;
        this.performanceMetrics = [];
        this.baselineMetrics = new Map();
        this.thresholds = {
            resetTime: 2000,        // 重置操作最大時間（毫秒）
            fillTime: 1000,         // 表單填寫最大時間（毫秒）
            totalTime: 3000,        // 總操作時間最大值（毫秒）
            memoryIncrease: 50,     // 記憶體增長最大值（MB）
            domNodes: 1000,         // DOM 節點數量最大值
            ajaxResponseTime: 1000, // AJAX 響應時間最大值（毫秒）
            pageLoadTime: 5000      // 頁面載入時間最大值（毫秒）
        };
    }

    /**
     * 測量表單重置效能
     * @param {string} componentName - 元件名稱
     * @param {Object} config - 測試配置
     * @param {number} iterations - 迭代次數
     * @returns {Promise<Object>} - 效能測試結果
     */
    async measureFormResetPerformance(componentName, config, iterations = 5) {
        console.log(`⚡ 測量 ${componentName} 表單重置效能 (${iterations} 次迭代)`);

        const performanceTest = {
            componentName,
            timestamp: new Date(),
            iterations,
            config,
            results: [],
            summary: {}
        };

        try {
            // 建立基準效能指標
            await this.establishPerformanceBaseline(componentName, config);

            for (let i = 0; i < iterations; i++) {
                console.log(`  執行第 ${i + 1}/${iterations} 次效能測試`);
                
                const iterationResult = await this.executePerformanceIteration(
                    componentName, 
                    config, 
                    i + 1
                );
                
                performanceTest.results.push(iterationResult);
                
                // 在迭代之間稍作等待，避免快取影響
                await this.page.evaluate('new Promise(resolve => setTimeout(resolve, 1000))');
            }

            // 計算效能摘要
            performanceTest.summary = this.calculatePerformanceSummary(performanceTest.results);
            
            // 與基準比較
            performanceTest.baselineComparison = this.compareWithBaseline(
                componentName, 
                performanceTest.summary
            );

            performanceTest.success = this.evaluatePerformanceSuccess(performanceTest.summary);
            
            console.log(`${performanceTest.success ? '✅' : '❌'} ${componentName} 效能測試${performanceTest.success ? '通過' : '失敗'}`);

        } catch (error) {
            performanceTest.success = false;
            performanceTest.error = error.message;
            console.error(`❌ ${componentName} 效能測試失敗: ${error.message}`);
        }

        this.performanceMetrics.push(performanceTest);
        return performanceTest;
    }

    /**
     * 建立效能基準
     * @param {string} componentName - 元件名稱
     * @param {Object} config - 配置
     */
    async establishPerformanceBaseline(componentName, config) {
        console.log(`📊 建立 ${componentName} 效能基準`);

        // 導航到頁面
        await this.page.goto(config.componentUrl);
        
        // 等待頁面完全載入
        await this.page.waitForLoadState('networkidle');
        
        // 測量基準指標
        const baseline = await this.page.evaluate(() => {
            return {
                domNodeCount: document.querySelectorAll('*').length,
                memoryUsage: performance.memory ? {
                    usedJSHeapSize: performance.memory.usedJSHeapSize,
                    totalJSHeapSize: performance.memory.totalJSHeapSize,
                    jsHeapSizeLimit: performance.memory.jsHeapSizeLimit
                } : null,
                timing: performance.timing ? {
                    navigationStart: performance.timing.navigationStart,
                    loadEventEnd: performance.timing.loadEventEnd,
                    domContentLoadedEventEnd: performance.timing.domContentLoadedEventEnd
                } : null
            };
        });

        this.baselineMetrics.set(componentName, baseline);
        console.log(`  基準 DOM 節點數: ${baseline.domNodeCount}`);
        
        if (baseline.memoryUsage) {
            console.log(`  基準記憶體使用: ${(baseline.memoryUsage.usedJSHeapSize / 1024 / 1024).toFixed(2)} MB`);
        }
    }

    /**
     * 執行單次效能迭代
     * @param {string} componentName - 元件名稱
     * @param {Object} config - 配置
     * @param {number} iteration - 迭代次數
     * @returns {Promise<Object>} - 迭代結果
     */
    async executePerformanceIteration(componentName, config, iteration) {
        const iterationResult = {
            iteration,
            timestamp: new Date(),
            metrics: {}
        };

        try {
            // 重新載入頁面確保乾淨狀態
            const navigationStart = Date.now();
            await this.page.goto(config.componentUrl);
            await this.page.waitForLoadState('networkidle');
            const navigationEnd = Date.now();
            
            iterationResult.metrics.pageLoadTime = navigationEnd - navigationStart;

            // 等待 Livewire 元件載入
            const componentSelector = config.searchSelector || Object.values(config.formSelectors || {})[0];
            await this.page.waitForSelector(componentSelector, { timeout: 10000 });

            // 測量表單填寫效能
            const fillStartTime = Date.now();
            await this.fillFormWithPerformanceTracking(config);
            const fillEndTime = Date.now();
            
            iterationResult.metrics.fillTime = fillEndTime - fillStartTime;

            // 測量重置操作效能
            const resetMetrics = await this.measureResetOperation(config);
            iterationResult.metrics = { ...iterationResult.metrics, ...resetMetrics };

            // 測量記憶體使用
            const memoryMetrics = await this.measureMemoryUsage();
            iterationResult.metrics.memory = memoryMetrics;

            // 測量 DOM 複雜度
            const domMetrics = await this.measureDOMComplexity();
            iterationResult.metrics.dom = domMetrics;

            iterationResult.success = true;

        } catch (error) {
            iterationResult.success = false;
            iterationResult.error = error.message;
        }

        return iterationResult;
    }

    /**
     * 填寫表單並追蹤效能
     * @param {Object} config - 配置
     */
    async fillFormWithPerformanceTracking(config) {
        const testValues = this.generatePerformanceTestValues(config);

        // 使用 performance.mark 標記開始
        await this.page.evaluate('performance.mark("form-fill-start")');

        for (const [selector, value] of Object.entries(testValues)) {
            const fieldStartTime = Date.now();
            
            await this.page.evaluate(`
                const field = document.querySelector('${selector}');
                if (field) {
                    field.value = '${value}';
                    field.dispatchEvent(new Event('input', { bubbles: true }));
                    field.blur();
                }
            `);
            
            const fieldEndTime = Date.now();
            console.log(`    欄位 ${selector} 填寫時間: ${fieldEndTime - fieldStartTime}ms`);
        }

        // 等待 Livewire 同步
        await this.page.evaluate('new Promise(resolve => setTimeout(resolve, 800))');

        // 標記結束
        await this.page.evaluate('performance.mark("form-fill-end")');
    }

    /**
     * 測量重置操作效能
     * @param {Object} config - 配置
     * @returns {Promise<Object>} - 重置效能指標
     */
    async measureResetOperation(config) {
        const resetMetrics = {};

        // 開始測量
        await this.page.evaluate('performance.mark("reset-start")');
        
        const resetStartTime = Date.now();
        
        // 監控網路請求
        const networkRequests = [];
        this.page.on('request', request => {
            if (request.url().includes('/livewire/')) {
                networkRequests.push({
                    url: request.url(),
                    method: request.method(),
                    timestamp: Date.now()
                });
            }
        });

        // 執行重置
        await this.page.click(config.resetButtonSelector);
        
        // 等待重置完成
        await this.page.evaluate('new Promise(resolve => setTimeout(resolve, 1500))');
        
        const resetEndTime = Date.now();
        
        // 結束測量
        await this.page.evaluate('performance.mark("reset-end")');

        resetMetrics.resetTime = resetEndTime - resetStartTime;
        resetMetrics.networkRequests = networkRequests.length;
        
        // 計算網路請求響應時間
        if (networkRequests.length > 0) {
            const avgResponseTime = networkRequests.reduce((sum, req) => {
                return sum + (Date.now() - req.timestamp);
            }, 0) / networkRequests.length;
            
            resetMetrics.avgAjaxResponseTime = avgResponseTime;
        }

        // 獲取 Performance API 測量結果
        const performanceEntries = await this.page.evaluate(() => {
            const entries = performance.getEntriesByType('mark');
            const measures = [];
            
            try {
                performance.measure('form-fill-duration', 'form-fill-start', 'form-fill-end');
                performance.measure('reset-duration', 'reset-start', 'reset-end');
                
                const measureEntries = performance.getEntriesByType('measure');
                measureEntries.forEach(entry => {
                    measures.push({
                        name: entry.name,
                        duration: entry.duration
                    });
                });
            } catch (e) {
                // Performance API 可能不支援某些測量
            }
            
            return measures;
        });

        resetMetrics.performanceEntries = performanceEntries;

        return resetMetrics;
    }

    /**
     * 測量記憶體使用
     * @returns {Promise<Object>} - 記憶體指標
     */
    async measureMemoryUsage() {
        const memoryMetrics = await this.page.evaluate(() => {
            if (performance.memory) {
                return {
                    usedJSHeapSize: performance.memory.usedJSHeapSize,
                    totalJSHeapSize: performance.memory.totalJSHeapSize,
                    jsHeapSizeLimit: performance.memory.jsHeapSizeLimit,
                    usedMB: (performance.memory.usedJSHeapSize / 1024 / 1024).toFixed(2),
                    totalMB: (performance.memory.totalJSHeapSize / 1024 / 1024).toFixed(2)
                };
            }
            return null;
        });

        return memoryMetrics;
    }

    /**
     * 測量 DOM 複雜度
     * @returns {Promise<Object>} - DOM 指標
     */
    async measureDOMComplexity() {
        const domMetrics = await this.page.evaluate(() => {
            return {
                totalNodes: document.querySelectorAll('*').length,
                livewireComponents: document.querySelectorAll('[wire\\:id]').length,
                formElements: document.querySelectorAll('input, select, textarea').length,
                eventListeners: document.querySelectorAll('[wire\\:click], [wire\\:model], [wire\\:submit]').length,
                depth: this.calculateDOMDepth(document.body)
            };
        });

        return domMetrics;
    }

    /**
     * 生成效能測試值
     * @param {Object} config - 配置
     * @returns {Object} - 測試值
     */
    generatePerformanceTestValues(config) {
        const testValues = {};

        if (config.searchSelector) {
            testValues[config.searchSelector] = 'performance test search';
        }

        if (config.filterSelectors) {
            Object.entries(config.filterSelectors).forEach(([key, selector]) => {
                testValues[selector] = `perf_test_${key}`;
            });
        }

        if (config.formSelectors) {
            Object.entries(config.formSelectors).forEach(([key, selector]) => {
                testValues[selector] = `performance_test_${key}`;
            });
        }

        return testValues;
    }

    /**
     * 計算效能摘要
     * @param {Array} results - 迭代結果
     * @returns {Object} - 效能摘要
     */
    calculatePerformanceSummary(results) {
        const validResults = results.filter(r => r.success);
        
        if (validResults.length === 0) {
            return { error: 'No valid results to summarize' };
        }

        const summary = {
            iterations: validResults.length,
            averages: {},
            minimums: {},
            maximums: {},
            standardDeviations: {}
        };

        // 計算各項指標的統計值
        const metrics = ['pageLoadTime', 'fillTime', 'resetTime', 'networkRequests', 'avgAjaxResponseTime'];
        
        metrics.forEach(metric => {
            const values = validResults
                .map(r => r.metrics[metric])
                .filter(v => v !== undefined && v !== null);
            
            if (values.length > 0) {
                summary.averages[metric] = values.reduce((sum, val) => sum + val, 0) / values.length;
                summary.minimums[metric] = Math.min(...values);
                summary.maximums[metric] = Math.max(...values);
                
                // 計算標準差
                const mean = summary.averages[metric];
                const variance = values.reduce((sum, val) => sum + Math.pow(val - mean, 2), 0) / values.length;
                summary.standardDeviations[metric] = Math.sqrt(variance);
            }
        });

        // 記憶體指標摘要
        const memoryValues = validResults
            .map(r => r.metrics.memory?.usedJSHeapSize)
            .filter(v => v !== undefined && v !== null);
        
        if (memoryValues.length > 0) {
            summary.averages.memoryUsage = memoryValues.reduce((sum, val) => sum + val, 0) / memoryValues.length;
            summary.minimums.memoryUsage = Math.min(...memoryValues);
            summary.maximums.memoryUsage = Math.max(...memoryValues);
        }

        // DOM 指標摘要
        const domNodeValues = validResults
            .map(r => r.metrics.dom?.totalNodes)
            .filter(v => v !== undefined && v !== null);
        
        if (domNodeValues.length > 0) {
            summary.averages.domNodes = domNodeValues.reduce((sum, val) => sum + val, 0) / domNodeValues.length;
            summary.minimums.domNodes = Math.min(...domNodeValues);
            summary.maximums.domNodes = Math.max(...domNodeValues);
        }

        return summary;
    }

    /**
     * 與基準比較
     * @param {string} componentName - 元件名稱
     * @param {Object} summary - 效能摘要
     * @returns {Object} - 基準比較結果
     */
    compareWithBaseline(componentName, summary) {
        const baseline = this.baselineMetrics.get(componentName);
        
        if (!baseline) {
            return { error: 'No baseline available for comparison' };
        }

        const comparison = {
            componentName,
            timestamp: new Date(),
            improvements: [],
            regressions: [],
            stable: []
        };

        // 比較記憶體使用
        if (baseline.memoryUsage && summary.averages.memoryUsage) {
            const memoryDiff = summary.averages.memoryUsage - baseline.memoryUsage.usedJSHeapSize;
            const memoryDiffMB = memoryDiff / 1024 / 1024;
            
            if (Math.abs(memoryDiffMB) < 5) {
                comparison.stable.push({
                    metric: 'memoryUsage',
                    baseline: (baseline.memoryUsage.usedJSHeapSize / 1024 / 1024).toFixed(2),
                    current: (summary.averages.memoryUsage / 1024 / 1024).toFixed(2),
                    difference: memoryDiffMB.toFixed(2)
                });
            } else if (memoryDiffMB > 0) {
                comparison.regressions.push({
                    metric: 'memoryUsage',
                    baseline: (baseline.memoryUsage.usedJSHeapSize / 1024 / 1024).toFixed(2),
                    current: (summary.averages.memoryUsage / 1024 / 1024).toFixed(2),
                    increase: memoryDiffMB.toFixed(2)
                });
            } else {
                comparison.improvements.push({
                    metric: 'memoryUsage',
                    baseline: (baseline.memoryUsage.usedJSHeapSize / 1024 / 1024).toFixed(2),
                    current: (summary.averages.memoryUsage / 1024 / 1024).toFixed(2),
                    decrease: Math.abs(memoryDiffMB).toFixed(2)
                });
            }
        }

        // 比較 DOM 節點數
        if (baseline.domNodeCount && summary.averages.domNodes) {
            const domDiff = summary.averages.domNodes - baseline.domNodeCount;
            
            if (Math.abs(domDiff) < 50) {
                comparison.stable.push({
                    metric: 'domNodes',
                    baseline: baseline.domNodeCount,
                    current: Math.round(summary.averages.domNodes),
                    difference: Math.round(domDiff)
                });
            } else if (domDiff > 0) {
                comparison.regressions.push({
                    metric: 'domNodes',
                    baseline: baseline.domNodeCount,
                    current: Math.round(summary.averages.domNodes),
                    increase: Math.round(domDiff)
                });
            } else {
                comparison.improvements.push({
                    metric: 'domNodes',
                    baseline: baseline.domNodeCount,
                    current: Math.round(summary.averages.domNodes),
                    decrease: Math.round(Math.abs(domDiff))
                });
            }
        }

        return comparison;
    }

    /**
     * 評估效能成功狀態
     * @param {Object} summary - 效能摘要
     * @returns {boolean} - 是否通過效能測試
     */
    evaluatePerformanceSuccess(summary) {
        const failures = [];

        // 檢查各項閾值
        if (summary.averages.resetTime > this.thresholds.resetTime) {
            failures.push(`重置時間超過閾值: ${summary.averages.resetTime.toFixed(2)}ms > ${this.thresholds.resetTime}ms`);
        }

        if (summary.averages.fillTime > this.thresholds.fillTime) {
            failures.push(`填寫時間超過閾值: ${summary.averages.fillTime.toFixed(2)}ms > ${this.thresholds.fillTime}ms`);
        }

        if (summary.averages.pageLoadTime > this.thresholds.pageLoadTime) {
            failures.push(`頁面載入時間超過閾值: ${summary.averages.pageLoadTime.toFixed(2)}ms > ${this.thresholds.pageLoadTime}ms`);
        }

        if (summary.averages.domNodes > this.thresholds.domNodes) {
            failures.push(`DOM 節點數超過閾值: ${Math.round(summary.averages.domNodes)} > ${this.thresholds.domNodes}`);
        }

        if (summary.averages.avgAjaxResponseTime > this.thresholds.ajaxResponseTime) {
            failures.push(`AJAX 響應時間超過閾值: ${summary.averages.avgAjaxResponseTime.toFixed(2)}ms > ${this.thresholds.ajaxResponseTime}ms`);
        }

        if (failures.length > 0) {
            console.log('❌ 效能測試失敗原因:');
            failures.forEach(failure => console.log(`  - ${failure}`));
            return false;
        }

        return true;
    }

    /**
     * 生成效能測試報告
     * @returns {Object} - 效能報告
     */
    generatePerformanceReport() {
        const report = {
            timestamp: new Date(),
            totalTests: this.performanceMetrics.length,
            successfulTests: this.performanceMetrics.filter(t => t.success).length,
            failedTests: this.performanceMetrics.filter(t => !t.success).length,
            thresholds: this.thresholds,
            testResults: this.performanceMetrics,
            overallSummary: {}
        };

        // 計算整體效能摘要
        const allResults = this.performanceMetrics
            .filter(t => t.success)
            .flatMap(t => t.results)
            .filter(r => r.success);

        if (allResults.length > 0) {
            report.overallSummary = this.calculatePerformanceSummary(allResults);
        }

        console.log('\n=== 效能測試報告 ===');
        console.log(`總測試數: ${report.totalTests}`);
        console.log(`成功測試: ${report.successfulTests}`);
        console.log(`失敗測試: ${report.failedTests}`);
        
        if (report.overallSummary.averages) {
            console.log('\n=== 整體效能指標 ===');
            console.log(`平均重置時間: ${(report.overallSummary.averages.resetTime || 0).toFixed(2)}ms`);
            console.log(`平均填寫時間: ${(report.overallSummary.averages.fillTime || 0).toFixed(2)}ms`);
            console.log(`平均頁面載入時間: ${(report.overallSummary.averages.pageLoadTime || 0).toFixed(2)}ms`);
            
            if (report.overallSummary.averages.memoryUsage) {
                console.log(`平均記憶體使用: ${(report.overallSummary.averages.memoryUsage / 1024 / 1024).toFixed(2)}MB`);
            }
        }

        return report;
    }

    /**
     * 設定效能閾值
     * @param {Object} newThresholds - 新的閾值
     */
    setPerformanceThresholds(newThresholds) {
        this.thresholds = { ...this.thresholds, ...newThresholds };
        console.log('⚙️  效能閾值已更新:', this.thresholds);
    }

    /**
     * 清理效能測試資料
     */
    clearPerformanceData() {
        this.performanceMetrics = [];
        this.baselineMetrics.clear();
        console.log('🧹 效能測試資料已清理');
    }
}

module.exports = PerformanceTestSuite;