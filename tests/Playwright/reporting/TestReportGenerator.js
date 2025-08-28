/**
 * 測試報告生成器
 * 生成詳細的測試報告和視覺化展示
 */

const fs = require('fs').promises;
const path = require('path');

class TestReportGenerator {
    constructor() {
        this.reportData = {
            metadata: {
                generatedAt: new Date(),
                version: '1.0.0',
                testSuite: 'Livewire Form Reset Standardization'
            },
            summary: {},
            testResults: [],
            performanceMetrics: {},
            screenshots: [],
            recommendations: []
        };
        this.outputDirectory = 'tests/reports';
    }

    /**
     * 初始化報告生成器
     * @param {Object} config - 配置選項
     */
    async initialize(config = {}) {
        const {
            outputDirectory = 'tests/reports',
            includeScreenshots = true,
            includePerformanceCharts = true
        } = config;

        this.outputDirectory = outputDirectory;
        this.includeScreenshots = includeScreenshots;
        this.includePerformanceCharts = includePerformanceCharts;

        // 確保輸出目錄存在
        await this.ensureDirectoryExists(this.outputDirectory);
        await this.ensureDirectoryExists(path.join(this.outputDirectory, 'assets'));
        await this.ensureDirectoryExists(path.join(this.outputDirectory, 'screenshots'));

        console.log(`📊 測試報告生成器已初始化 (輸出目錄: ${this.outputDirectory})`);
    }

    /**
     * 添加測試結果
     * @param {Object} testResult - 測試結果
     */
    addTestResult(testResult) {
        this.reportData.testResults.push({
            ...testResult,
            addedAt: new Date()
        });

        console.log(`📝 已添加測試結果: ${testResult.testName || testResult.componentName || 'Unknown'}`);
    }

    /**
     * 添加效能指標
     * @param {Object} performanceData - 效能資料
     */
    addPerformanceMetrics(performanceData) {
        this.reportData.performanceMetrics = {
            ...this.reportData.performanceMetrics,
            ...performanceData,
            addedAt: new Date()
        };

        console.log('📈 已添加效能指標');
    }

    /**
     * 添加截圖
     * @param {Array} screenshots - 截圖陣列
     */
    addScreenshots(screenshots) {
        this.reportData.screenshots.push(...screenshots);
        console.log(`📸 已添加 ${screenshots.length} 張截圖`);
    }

    /**
     * 添加建議
     * @param {Array} recommendations - 建議陣列
     */
    addRecommendations(recommendations) {
        this.reportData.recommendations.push(...recommendations);
        console.log(`💡 已添加 ${recommendations.length} 個建議`);
    }

    /**
     * 生成測試摘要
     */
    generateTestSummary() {
        const testResults = this.reportData.testResults;
        
        this.reportData.summary = {
            totalTests: testResults.length,
            passedTests: testResults.filter(r => r.success || r.passed).length,
            failedTests: testResults.filter(r => !r.success && !r.passed).length,
            skippedTests: testResults.filter(r => r.skipped).length,
            testDuration: this.calculateTotalTestDuration(testResults),
            successRate: 0,
            testCategories: this.categorizeTests(testResults),
            criticalIssues: this.identifyCriticalIssues(testResults)
        };

        // 計算成功率
        if (this.reportData.summary.totalTests > 0) {
            this.reportData.summary.successRate = 
                (this.reportData.summary.passedTests / this.reportData.summary.totalTests * 100).toFixed(2);
        }

        console.log('📊 測試摘要已生成');
    }

    /**
     * 計算總測試時間
     * @param {Array} testResults - 測試結果陣列
     * @returns {number} - 總時間（毫秒）
     */
    calculateTotalTestDuration(testResults) {
        return testResults.reduce((total, test) => {
            const duration = test.duration || test.totalTime || 0;
            return total + duration;
        }, 0);
    }

    /**
     * 分類測試
     * @param {Array} testResults - 測試結果陣列
     * @returns {Object} - 測試分類
     */
    categorizeTests(testResults) {
        const categories = {
            frontend: 0,
            backend: 0,
            integration: 0,
            performance: 0,
            visual: 0
        };

        testResults.forEach(test => {
            const testName = (test.testName || test.componentName || '').toLowerCase();
            
            if (testName.includes('frontend') || testName.includes('playwright')) {
                categories.frontend++;
            } else if (testName.includes('backend') || testName.includes('mysql')) {
                categories.backend++;
            } else if (testName.includes('integration') || testName.includes('integrated')) {
                categories.integration++;
            } else if (testName.includes('performance') || testName.includes('memory')) {
                categories.performance++;
            } else if (testName.includes('visual') || testName.includes('screenshot')) {
                categories.visual++;
            }
        });

        return categories;
    }

    /**
     * 識別關鍵問題
     * @param {Array} testResults - 測試結果陣列
     * @returns {Array} - 關鍵問題陣列
     */
    identifyCriticalIssues(testResults) {
        const criticalIssues = [];

        // 檢查失敗率
        const failureRate = (testResults.filter(r => !r.success && !r.passed).length / testResults.length) * 100;
        if (failureRate > 20) {
            criticalIssues.push({
                type: 'high_failure_rate',
                severity: 'critical',
                message: `測試失敗率過高: ${failureRate.toFixed(2)}%`,
                affectedTests: testResults.filter(r => !r.success && !r.passed).length
            });
        }

        // 檢查效能問題
        const performanceIssues = testResults.filter(test => {
            const duration = test.duration || test.totalTime || 0;
            return duration > 5000; // 5秒以上
        });

        if (performanceIssues.length > 0) {
            criticalIssues.push({
                type: 'performance_issues',
                severity: 'warning',
                message: `${performanceIssues.length} 個測試執行時間過長`,
                affectedTests: performanceIssues.length
            });
        }

        // 檢查記憶體洩漏
        const memoryLeaks = testResults.filter(test => {
            return test.phases?.memoryLeakAnalysis?.leakDetection?.suspectedLeak;
        });

        if (memoryLeaks.length > 0) {
            criticalIssues.push({
                type: 'memory_leaks',
                severity: 'critical',
                message: `${memoryLeaks.length} 個測試檢測到記憶體洩漏`,
                affectedTests: memoryLeaks.length
            });
        }

        return criticalIssues;
    }

    /**
     * 生成 HTML 報告
     * @returns {Promise<string>} - HTML 報告路徑
     */
    async generateHTMLReport() {
        console.log('🌐 生成 HTML 報告');

        // 生成測試摘要
        this.generateTestSummary();

        const htmlContent = this.generateHTMLContent();
        const reportPath = path.join(this.outputDirectory, 'test-report.html');

        await fs.writeFile(reportPath, htmlContent, 'utf8');

        // 複製 CSS 和 JavaScript 資源
        await this.copyReportAssets();

        console.log(`✅ HTML 報告已生成: ${reportPath}`);
        return reportPath;
    }

    /**
     * 生成 HTML 內容
     * @returns {string} - HTML 內容
     */
    generateHTMLContent() {
        const { summary, testResults, performanceMetrics, screenshots, recommendations } = this.reportData;

        return `
<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Livewire 表單重置功能測試報告</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .success { @apply text-green-600 bg-green-50 border-green-200; }
        .failure { @apply text-red-600 bg-red-50 border-red-200; }
        .warning { @apply text-yellow-600 bg-yellow-50 border-yellow-200; }
        .info { @apply text-blue-600 bg-blue-50 border-blue-200; }
    </style>
</head>
<body class="bg-gray-50">
    <div class="container mx-auto px-4 py-8">
        <!-- 標題 -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900 mb-2">
                Livewire 表單重置功能測試報告
            </h1>
            <p class="text-gray-600">
                生成時間: ${this.reportData.metadata.generatedAt.toLocaleString('zh-TW')}
            </p>
        </div>

        <!-- 測試摘要 -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
            <div class="bg-white rounded-lg shadow p-6">
                <div class="text-2xl font-bold text-gray-900">${summary.totalTests}</div>
                <div class="text-gray-600">總測試數</div>
            </div>
            <div class="bg-white rounded-lg shadow p-6">
                <div class="text-2xl font-bold text-green-600">${summary.passedTests}</div>
                <div class="text-gray-600">通過測試</div>
            </div>
            <div class="bg-white rounded-lg shadow p-6">
                <div class="text-2xl font-bold text-red-600">${summary.failedTests}</div>
                <div class="text-gray-600">失敗測試</div>
            </div>
            <div class="bg-white rounded-lg shadow p-6">
                <div class="text-2xl font-bold text-blue-600">${summary.successRate}%</div>
                <div class="text-gray-600">成功率</div>
            </div>
        </div>

        <!-- 關鍵問題 -->
        ${this.generateCriticalIssuesHTML(summary.criticalIssues)}

        <!-- 測試分類圖表 -->
        <div class="bg-white rounded-lg shadow p-6 mb-8">
            <h2 class="text-xl font-semibold mb-4">測試分類分佈</h2>
            <canvas id="testCategoriesChart" width="400" height="200"></canvas>
        </div>

        <!-- 效能指標 -->
        ${this.generatePerformanceMetricsHTML(performanceMetrics)}

        <!-- 測試結果詳情 -->
        <div class="bg-white rounded-lg shadow p-6 mb-8">
            <h2 class="text-xl font-semibold mb-4">測試結果詳情</h2>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">測試名稱</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">狀態</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">執行時間</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">詳情</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        ${this.generateTestResultsTableHTML(testResults)}
                    </tbody>
                </table>
            </div>
        </div>

        <!-- 截圖展示 -->
        ${this.generateScreenshotsHTML(screenshots)}

        <!-- 建議 -->
        ${this.generateRecommendationsHTML(recommendations)}
    </div>

    <script>
        // 測試分類圖表
        const ctx = document.getElementById('testCategoriesChart').getContext('2d');
        new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: ['前端測試', '後端測試', '整合測試', '效能測試', '視覺測試'],
                datasets: [{
                    data: [
                        ${summary.testCategories?.frontend || 0},
                        ${summary.testCategories?.backend || 0},
                        ${summary.testCategories?.integration || 0},
                        ${summary.testCategories?.performance || 0},
                        ${summary.testCategories?.visual || 0}
                    ],
                    backgroundColor: [
                        '#3B82F6',
                        '#10B981',
                        '#F59E0B',
                        '#EF4444',
                        '#8B5CF6'
                    ]
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
    </script>
</body>
</html>`;
    }

    /**
     * 生成關鍵問題 HTML
     * @param {Array} criticalIssues - 關鍵問題陣列
     * @returns {string} - HTML 內容
     */
    generateCriticalIssuesHTML(criticalIssues) {
        if (!criticalIssues || criticalIssues.length === 0) {
            return `
            <div class="bg-green-50 border border-green-200 rounded-lg p-4 mb-8">
                <div class="flex items-center">
                    <div class="text-green-600 mr-3">✅</div>
                    <div class="text-green-800 font-medium">沒有發現關鍵問題</div>
                </div>
            </div>`;
        }

        const issuesHTML = criticalIssues.map(issue => {
            const severityClass = issue.severity === 'critical' ? 'failure' : 'warning';
            const icon = issue.severity === 'critical' ? '🚨' : '⚠️';
            
            return `
            <div class="border rounded-lg p-4 mb-4 ${severityClass}">
                <div class="flex items-start">
                    <div class="mr-3">${icon}</div>
                    <div>
                        <div class="font-medium">${issue.message}</div>
                        <div class="text-sm mt-1">影響測試數: ${issue.affectedTests}</div>
                    </div>
                </div>
            </div>`;
        }).join('');

        return `
        <div class="mb-8">
            <h2 class="text-xl font-semibold mb-4">關鍵問題</h2>
            ${issuesHTML}
        </div>`;
    }

    /**
     * 生成效能指標 HTML
     * @param {Object} performanceMetrics - 效能指標
     * @returns {string} - HTML 內容
     */
    generatePerformanceMetricsHTML(performanceMetrics) {
        if (!performanceMetrics || Object.keys(performanceMetrics).length === 0) {
            return '';
        }

        return `
        <div class="bg-white rounded-lg shadow p-6 mb-8">
            <h2 class="text-xl font-semibold mb-4">效能指標</h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                ${performanceMetrics.averageResetTime ? `
                <div class="text-center">
                    <div class="text-2xl font-bold text-blue-600">${performanceMetrics.averageResetTime.toFixed(2)}ms</div>
                    <div class="text-gray-600">平均重置時間</div>
                </div>` : ''}
                ${performanceMetrics.averageMemoryUsage ? `
                <div class="text-center">
                    <div class="text-2xl font-bold text-green-600">${performanceMetrics.averageMemoryUsage.toFixed(2)}MB</div>
                    <div class="text-gray-600">平均記憶體使用</div>
                </div>` : ''}
                ${performanceMetrics.averageResponseTime ? `
                <div class="text-center">
                    <div class="text-2xl font-bold text-purple-600">${performanceMetrics.averageResponseTime.toFixed(2)}ms</div>
                    <div class="text-gray-600">平均響應時間</div>
                </div>` : ''}
            </div>
        </div>`;
    }

    /**
     * 生成測試結果表格 HTML
     * @param {Array} testResults - 測試結果陣列
     * @returns {string} - HTML 內容
     */
    generateTestResultsTableHTML(testResults) {
        return testResults.map(test => {
            const testName = test.testName || test.componentName || 'Unknown Test';
            const success = test.success || test.passed;
            const duration = test.duration || test.totalTime || 0;
            const statusClass = success ? 'text-green-600' : 'text-red-600';
            const statusIcon = success ? '✅' : '❌';
            const statusText = success ? '通過' : '失敗';

            return `
            <tr>
                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                    ${testName}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm ${statusClass}">
                    ${statusIcon} ${statusText}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                    ${duration > 0 ? `${duration.toFixed(0)}ms` : 'N/A'}
                </td>
                <td class="px-6 py-4 text-sm text-gray-500">
                    ${test.error || test.details || '無額外資訊'}
                </td>
            </tr>`;
        }).join('');
    }

    /**
     * 生成截圖展示 HTML
     * @param {Array} screenshots - 截圖陣列
     * @returns {string} - HTML 內容
     */
    generateScreenshotsHTML(screenshots) {
        if (!screenshots || screenshots.length === 0) {
            return '';
        }

        const screenshotsHTML = screenshots.map(screenshot => {
            return `
            <div class="bg-white rounded-lg shadow p-4">
                <h4 class="font-medium mb-2">${screenshot.name || 'Screenshot'}</h4>
                <img src="screenshots/${screenshot.filename}" alt="${screenshot.name}" class="w-full rounded border">
                <p class="text-sm text-gray-600 mt-2">${screenshot.timestamp?.toLocaleString('zh-TW') || ''}</p>
            </div>`;
        }).join('');

        return `
        <div class="bg-white rounded-lg shadow p-6 mb-8">
            <h2 class="text-xl font-semibold mb-4">測試截圖</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                ${screenshotsHTML}
            </div>
        </div>`;
    }

    /**
     * 生成建議 HTML
     * @param {Array} recommendations - 建議陣列
     * @returns {string} - HTML 內容
     */
    generateRecommendationsHTML(recommendations) {
        if (!recommendations || recommendations.length === 0) {
            return '';
        }

        const recommendationsHTML = recommendations.map(rec => {
            const priorityClass = {
                high: 'border-red-200 bg-red-50 text-red-800',
                medium: 'border-yellow-200 bg-yellow-50 text-yellow-800',
                low: 'border-blue-200 bg-blue-50 text-blue-800'
            }[rec.priority] || 'border-gray-200 bg-gray-50 text-gray-800';

            const priorityIcon = {
                high: '🔴',
                medium: '🟡',
                low: '🔵'
            }[rec.priority] || '⚪';

            return `
            <div class="border rounded-lg p-4 mb-4 ${priorityClass}">
                <div class="flex items-start">
                    <div class="mr-3">${priorityIcon}</div>
                    <div>
                        <div class="font-medium">${rec.title}</div>
                        <div class="text-sm mt-1">${rec.description}</div>
                        ${rec.category ? `<div class="text-xs mt-2 opacity-75">分類: ${rec.category}</div>` : ''}
                    </div>
                </div>
            </div>`;
        }).join('');

        return `
        <div class="bg-white rounded-lg shadow p-6 mb-8">
            <h2 class="text-xl font-semibold mb-4">改善建議</h2>
            ${recommendationsHTML}
        </div>`;
    }

    /**
     * 生成 JSON 報告
     * @returns {Promise<string>} - JSON 報告路徑
     */
    async generateJSONReport() {
        console.log('📄 生成 JSON 報告');

        this.generateTestSummary();

        const reportPath = path.join(this.outputDirectory, 'test-report.json');
        await fs.writeFile(reportPath, JSON.stringify(this.reportData, null, 2), 'utf8');

        console.log(`✅ JSON 報告已生成: ${reportPath}`);
        return reportPath;
    }

    /**
     * 生成 CSV 報告
     * @returns {Promise<string>} - CSV 報告路徑
     */
    async generateCSVReport() {
        console.log('📊 生成 CSV 報告');

        const headers = [
            'testName',
            'componentName',
            'success',
            'duration',
            'timestamp',
            'error',
            'category'
        ];

        const rows = this.reportData.testResults.map(test => [
            test.testName || '',
            test.componentName || '',
            test.success || test.passed || false,
            test.duration || test.totalTime || 0,
            test.timestamp?.toISOString() || '',
            test.error || '',
            this.categorizeTest(test)
        ]);

        const csvContent = [headers, ...rows]
            .map(row => row.map(cell => `"${cell}"`).join(','))
            .join('\n');

        const reportPath = path.join(this.outputDirectory, 'test-report.csv');
        await fs.writeFile(reportPath, csvContent, 'utf8');

        console.log(`✅ CSV 報告已生成: ${reportPath}`);
        return reportPath;
    }

    /**
     * 分類單個測試
     * @param {Object} test - 測試對象
     * @returns {string} - 測試分類
     */
    categorizeTest(test) {
        const testName = (test.testName || test.componentName || '').toLowerCase();
        
        if (testName.includes('frontend') || testName.includes('playwright')) return 'frontend';
        if (testName.includes('backend') || testName.includes('mysql')) return 'backend';
        if (testName.includes('integration') || testName.includes('integrated')) return 'integration';
        if (testName.includes('performance') || testName.includes('memory')) return 'performance';
        if (testName.includes('visual') || testName.includes('screenshot')) return 'visual';
        
        return 'other';
    }

    /**
     * 複製報告資源檔案
     */
    async copyReportAssets() {
        // 這裡可以複製 CSS、JavaScript 等資源檔案
        // 目前使用 CDN，所以不需要複製
        console.log('📁 報告資源檔案處理完成');
    }

    /**
     * 確保目錄存在
     * @param {string} dirPath - 目錄路徑
     */
    async ensureDirectoryExists(dirPath) {
        try {
            await fs.access(dirPath);
        } catch (error) {
            await fs.mkdir(dirPath, { recursive: true });
        }
    }

    /**
     * 生成所有格式的報告
     * @returns {Promise<Object>} - 生成的報告路徑
     */
    async generateAllReports() {
        console.log('📋 生成所有格式的測試報告');

        const reportPaths = {
            html: await this.generateHTMLReport(),
            json: await this.generateJSONReport(),
            csv: await this.generateCSVReport()
        };

        console.log('✅ 所有格式的測試報告已生成');
        console.log(`  HTML: ${reportPaths.html}`);
        console.log(`  JSON: ${reportPaths.json}`);
        console.log(`  CSV: ${reportPaths.csv}`);

        return reportPaths;
    }

    /**
     * 清理舊報告
     * @param {number} daysOld - 保留天數
     */
    async cleanupOldReports(daysOld = 7) {
        console.log(`🧹 清理 ${daysOld} 天前的舊報告`);

        try {
            const files = await fs.readdir(this.outputDirectory);
            const cutoffDate = new Date();
            cutoffDate.setDate(cutoffDate.getDate() - daysOld);

            let deletedCount = 0;

            for (const file of files) {
                const filePath = path.join(this.outputDirectory, file);
                const stats = await fs.stat(filePath);

                if (stats.mtime < cutoffDate && (
                    file.endsWith('.html') || 
                    file.endsWith('.json') || 
                    file.endsWith('.csv')
                )) {
                    await fs.unlink(filePath);
                    deletedCount++;
                }
            }

            console.log(`✅ 清理完成，刪除了 ${deletedCount} 個舊報告`);

        } catch (error) {
            console.error(`❌ 清理舊報告失敗: ${error.message}`);
        }
    }

    /**
     * 獲取報告摘要
     * @returns {Object} - 報告摘要
     */
    getReportSummary() {
        this.generateTestSummary();
        
        return {
            metadata: this.reportData.metadata,
            summary: this.reportData.summary,
            totalScreenshots: this.reportData.screenshots.length,
            totalRecommendations: this.reportData.recommendations.length,
            hasPerformanceMetrics: Object.keys(this.reportData.performanceMetrics).length > 0
        };
    }
}

module.exports = TestReportGenerator;