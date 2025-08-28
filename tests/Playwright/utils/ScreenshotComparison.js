/**
 * 截圖對比工具
 * 用於視覺回歸測試和狀態驗證
 */

const fs = require('fs').promises;
const path = require('path');

class ScreenshotComparison {
    constructor(page, baseDir = 'tests/screenshots') {
        this.page = page;
        this.baseDir = baseDir;
        this.comparisonResults = [];
    }

    /**
     * 確保截圖目錄存在
     */
    async ensureDirectoryExists(dirPath) {
        try {
            await fs.access(dirPath);
        } catch (error) {
            await fs.mkdir(dirPath, { recursive: true });
        }
    }

    /**
     * 生成截圖檔名
     * @param {string} testName - 測試名稱
     * @param {string} state - 狀態 (before/after/baseline)
     * @param {Object} viewport - 視窗大小
     */
    generateScreenshotName(testName, state, viewport = null) {
        const timestamp = new Date().toISOString().replace(/[:.]/g, '-');
        const viewportSuffix = viewport ? `_${viewport.width}x${viewport.height}` : '';
        return `${testName}_${state}${viewportSuffix}_${timestamp}.png`;
    }

    /**
     * 拍攝基準截圖
     * @param {string} testName - 測試名稱
     * @param {Object} options - 截圖選項
     */
    async captureBaseline(testName, options = {}) {
        const screenshotDir = path.join(this.baseDir, 'baselines');
        await this.ensureDirectoryExists(screenshotDir);

        const viewport = await this.page.viewportSize();
        const filename = this.generateScreenshotName(testName, 'baseline', viewport);
        const filepath = path.join(screenshotDir, filename);

        await this.page.screenshot({
            path: filepath,
            fullPage: true,
            ...options
        });

        console.log(`📸 基準截圖已儲存: ${filename}`);
        
        return {
            testName,
            type: 'baseline',
            filename,
            filepath,
            viewport,
            timestamp: new Date()
        };
    }

    /**
     * 拍攝狀態截圖（重置前/後）
     * @param {string} testName - 測試名稱
     * @param {string} state - 狀態 (before/after)
     * @param {Object} options - 截圖選項
     */
    async captureState(testName, state, options = {}) {
        const screenshotDir = path.join(this.baseDir, 'states');
        await this.ensureDirectoryExists(screenshotDir);

        const viewport = await this.page.viewportSize();
        const filename = this.generateScreenshotName(testName, state, viewport);
        const filepath = path.join(screenshotDir, filename);

        await this.page.screenshot({
            path: filepath,
            fullPage: true,
            ...options
        });

        console.log(`📸 狀態截圖已儲存: ${filename} (${state})`);
        
        return {
            testName,
            type: 'state',
            state,
            filename,
            filepath,
            viewport,
            timestamp: new Date()
        };
    }

    /**
     * 拍攝元件截圖
     * @param {string} testName - 測試名稱
     * @param {string} selector - 元件選擇器
     * @param {string} state - 狀態
     * @param {Object} options - 截圖選項
     */
    async captureComponent(testName, selector, state, options = {}) {
        const screenshotDir = path.join(this.baseDir, 'components');
        await this.ensureDirectoryExists(screenshotDir);

        const viewport = await this.page.viewportSize();
        const filename = this.generateScreenshotName(`${testName}_component`, state, viewport);
        const filepath = path.join(screenshotDir, filename);

        // 等待元件載入
        await this.page.waitForSelector(selector, { timeout: 5000 });

        // 截圖特定元件
        const element = await this.page.$(selector);
        if (element) {
            await element.screenshot({
                path: filepath,
                ...options
            });

            console.log(`📸 元件截圖已儲存: ${filename} (${state})`);
            
            return {
                testName,
                type: 'component',
                state,
                selector,
                filename,
                filepath,
                viewport,
                timestamp: new Date()
            };
        } else {
            throw new Error(`元件未找到: ${selector}`);
        }
    }

    /**
     * 拍攝表單欄位截圖
     * @param {string} testName - 測試名稱
     * @param {Object} fieldSelectors - 欄位選擇器對象
     * @param {string} state - 狀態
     */
    async captureFormFields(testName, fieldSelectors, state) {
        const screenshots = [];

        for (const [fieldName, selector] of Object.entries(fieldSelectors)) {
            try {
                const screenshot = await this.captureComponent(
                    `${testName}_${fieldName}`,
                    selector,
                    state
                );
                screenshots.push(screenshot);
            } catch (error) {
                console.log(`⚠️  無法截圖欄位 ${fieldName}: ${error.message}`);
            }
        }

        return screenshots;
    }

    /**
     * 建立視覺測試序列
     * @param {string} testName - 測試名稱
     * @param {Function} testFunction - 測試函數
     * @param {Object} options - 選項
     */
    async createVisualTestSequence(testName, testFunction, options = {}) {
        const {
            captureBaseline = false,
            componentSelector = null,
            formSelectors = {}
        } = options;

        const sequence = {
            testName,
            screenshots: [],
            startTime: new Date()
        };

        try {
            // 拍攝基準截圖（如果需要）
            if (captureBaseline) {
                const baseline = await this.captureBaseline(testName);
                sequence.screenshots.push(baseline);
            }

            // 拍攝初始狀態
            const beforeScreenshot = await this.captureState(testName, 'before');
            sequence.screenshots.push(beforeScreenshot);

            // 拍攝表單欄位初始狀態
            if (Object.keys(formSelectors).length > 0) {
                const fieldScreenshots = await this.captureFormFields(testName, formSelectors, 'before');
                sequence.screenshots.push(...fieldScreenshots);
            }

            // 拍攝元件初始狀態
            if (componentSelector) {
                const componentBefore = await this.captureComponent(testName, componentSelector, 'before');
                sequence.screenshots.push(componentBefore);
            }

            // 執行測試函數
            const testResult = await testFunction();

            // 拍攝最終狀態
            const afterScreenshot = await this.captureState(testName, 'after');
            sequence.screenshots.push(afterScreenshot);

            // 拍攝表單欄位最終狀態
            if (Object.keys(formSelectors).length > 0) {
                const fieldScreenshots = await this.captureFormFields(testName, formSelectors, 'after');
                sequence.screenshots.push(...fieldScreenshots);
            }

            // 拍攝元件最終狀態
            if (componentSelector) {
                const componentAfter = await this.captureComponent(testName, componentSelector, 'after');
                sequence.screenshots.push(componentAfter);
            }

            sequence.endTime = new Date();
            sequence.duration = sequence.endTime - sequence.startTime;
            sequence.testResult = testResult;
            sequence.success = true;

            console.log(`✅ 視覺測試序列完成: ${testName} (${sequence.screenshots.length} 張截圖)`);

        } catch (error) {
            sequence.endTime = new Date();
            sequence.duration = sequence.endTime - sequence.startTime;
            sequence.error = error.message;
            sequence.success = false;

            console.log(`❌ 視覺測試序列失敗: ${testName} - ${error.message}`);
        }

        this.comparisonResults.push(sequence);
        return sequence;
    }

    /**
     * 建立響應式截圖序列
     * @param {string} testName - 測試名稱
     * @param {Array} viewports - 視窗大小陣列
     * @param {Function} testFunction - 測試函數
     */
    async createResponsiveScreenshotSequence(testName, viewports, testFunction) {
        const responsiveSequence = {
            testName,
            viewports: [],
            startTime: new Date()
        };

        for (const viewport of viewports) {
            console.log(`📱 拍攝響應式截圖: ${viewport.name} (${viewport.width}x${viewport.height})`);

            // 設定視窗大小
            await this.page.setViewportSize({
                width: viewport.width,
                height: viewport.height
            });

            // 重新載入頁面
            await this.page.reload();
            await this.page.waitForLoadState('networkidle');

            const viewportSequence = await this.createVisualTestSequence(
                `${testName}_${viewport.name}`,
                testFunction,
                { captureBaseline: false }
            );

            responsiveSequence.viewports.push({
                viewport,
                sequence: viewportSequence
            });
        }

        responsiveSequence.endTime = new Date();
        responsiveSequence.duration = responsiveSequence.endTime - responsiveSequence.startTime;

        console.log(`✅ 響應式截圖序列完成: ${testName}`);
        return responsiveSequence;
    }

    /**
     * 生成視覺測試報告
     */
    generateVisualTestReport() {
        const report = {
            testSuite: 'ScreenshotComparison',
            timestamp: new Date(),
            totalSequences: this.comparisonResults.length,
            successfulSequences: this.comparisonResults.filter(s => s.success).length,
            failedSequences: this.comparisonResults.filter(s => !s.success).length,
            totalScreenshots: this.comparisonResults.reduce((sum, s) => sum + s.screenshots.length, 0),
            sequences: this.comparisonResults
        };

        console.log('\n=== 視覺測試報告 ===');
        console.log(`總測試序列: ${report.totalSequences}`);
        console.log(`成功序列: ${report.successfulSequences}`);
        console.log(`失敗序列: ${report.failedSequences}`);
        console.log(`總截圖數: ${report.totalScreenshots}`);

        return report;
    }

    /**
     * 清理舊截圖
     * @param {number} daysOld - 保留天數
     */
    async cleanupOldScreenshots(daysOld = 7) {
        const cutoffDate = new Date();
        cutoffDate.setDate(cutoffDate.getDate() - daysOld);

        const directories = ['baselines', 'states', 'components'];
        let deletedCount = 0;

        for (const dir of directories) {
            const dirPath = path.join(this.baseDir, dir);
            
            try {
                const files = await fs.readdir(dirPath);
                
                for (const file of files) {
                    const filePath = path.join(dirPath, file);
                    const stats = await fs.stat(filePath);
                    
                    if (stats.mtime < cutoffDate) {
                        await fs.unlink(filePath);
                        deletedCount++;
                    }
                }
            } catch (error) {
                console.log(`⚠️  清理目錄失敗 ${dir}: ${error.message}`);
            }
        }

        console.log(`🧹 清理完成，刪除了 ${deletedCount} 個舊截圖`);
        return deletedCount;
    }
}

module.exports = ScreenshotComparison;