/**
 * 整合效能監控系統
 * 結合效能測試、記憶體監控和響應時間分析
 */

const PerformanceTestSuite = require('./PerformanceTestSuite');
const MemoryMonitor = require('./MemoryMonitor');
const ResponseTimeAnalyzer = require('./ResponseTimeAnalyzer');

class PerformanceMonitor {
    constructor(page) {
        this.page = page;
        this.performanceTestSuite = new PerformanceTestSuite(page);
        this.memoryMonitor = new MemoryMonitor(page);
        this.responseTimeAnalyzer = new ResponseTimeAnalyzer(page);
        this.monitoringSessions = [];
        this.isMonitoring = false;
        this.currentSession = null;
    }

    /**
     * 開始完整效能監控
     * @param {string} testName - 測試名稱
     * @param {Object} config - 監控配置
     */
    async startComprehensiveMonitoring(testName, config = {}) {
        if (this.isMonitoring) {
            console.log('⚠️  效能監控已在執行中');
            return;
        }

        console.log(`🚀 開始完整效能監控: ${testName}`);

        const {
            memoryMonitoring = true,
            responseTimeMonitoring = true,
            memoryInterval = 1000,
            performanceBaseline = true
        } = config;

        this.isMonitoring = true;
        this.currentSession = {
            testName,
            startTime: Date.now(),
            config,
            components: {
                memory: memoryMonitoring,
                responseTime: responseTimeMonitoring,
                performance: true
            },
            results: {}
        };

        try {
            // 1. 建立效能基準（如果需要）
            if (performanceBaseline) {
                console.log('  建立效能基準');
                await this.performanceTestSuite.establishPerformanceBaseline(testName, config);
            }

            // 2. 開始記憶體監控
            if (memoryMonitoring) {
                console.log('  啟動記憶體監控');
                await this.memoryMonitor.startMonitoring(testName, memoryInterval);
            }

            // 3. 開始響應時間監控
            if (responseTimeMonitoring) {
                console.log('  啟動響應時間監控');
                this.responseTimeAnalyzer.startMonitoring(testName);
            }

            console.log('✅ 完整效能監控已啟動');

        } catch (error) {
            console.error(`❌ 效能監控啟動失敗: ${error.message}`);
            this.isMonitoring = false;
            this.currentSession = null;
            throw error;
        }
    }

    /**
     * 停止完整效能監控
     * @returns {Promise<Object>} - 監控結果摘要
     */
    async stopComprehensiveMonitoring() {
        if (!this.isMonitoring || !this.currentSession) {
            console.log('⚠️  效能監控未在執行');
            return null;
        }

        console.log('🛑 停止完整效能監控');

        const endTime = Date.now();
        const duration = endTime - this.currentSession.startTime;

        try {
            // 1. 停止記憶體監控
            if (this.currentSession.components.memory) {
                console.log('  停止記憶體監控');
                this.currentSession.results.memory = await this.memoryMonitor.stopMonitoring();
            }

            // 2. 停止響應時間監控
            if (this.currentSession.components.responseTime) {
                console.log('  停止響應時間監控');
                this.currentSession.results.responseTime = this.responseTimeAnalyzer.stopMonitoring();
            }

            // 3. 完成會話記錄
            this.currentSession.endTime = endTime;
            this.currentSession.duration = duration;
            this.currentSession.success = true;

            // 4. 生成綜合分析
            console.log('  生成綜合分析');
            this.currentSession.analysis = this.generateComprehensiveAnalysis(this.currentSession);

            this.monitoringSessions.push(this.currentSession);

            console.log(`✅ 完整效能監控已停止 (持續時間: ${(duration / 1000).toFixed(2)}秒)`);

            const sessionResult = { ...this.currentSession };
            this.currentSession = null;
            this.isMonitoring = false;

            return sessionResult;

        } catch (error) {
            console.error(`❌ 效能監控停止失敗: ${error.message}`);
            
            this.currentSession.endTime = endTime;
            this.currentSession.duration = duration;
            this.currentSession.success = false;
            this.currentSession.error = error.message;

            this.monitoringSessions.push(this.currentSession);
            
            const sessionResult = { ...this.currentSession };
            this.currentSession = null;
            this.isMonitoring = false;

            return sessionResult;
        }
    }

    /**
     * 執行完整的元件效能測試
     * @param {string} componentName - 元件名稱
     * @param {Object} config - 測試配置
     * @param {number} iterations - 測試迭代次數
     * @returns {Promise<Object>} - 完整效能測試結果
     */
    async executeComprehensivePerformanceTest(componentName, config, iterations = 3) {
        console.log(`🧪 執行 ${componentName} 完整效能測試`);

        const testResult = {
            componentName,
            timestamp: new Date(),
            config,
            iterations,
            phases: {}
        };

        try {
            // 階段 1: 開始監控
            console.log('  階段 1: 開始效能監控');
            await this.startComprehensiveMonitoring(`${componentName}_performance_test`, {
                ...config,
                memoryInterval: 500 // 更頻繁的記憶體監控
            });

            // 階段 2: 執行效能測試
            console.log('  階段 2: 執行效能測試');
            testResult.phases.performanceTest = await this.performanceTestSuite.measureFormResetPerformance(
                componentName,
                config,
                iterations
            );

            // 階段 3: 執行記憶體洩漏分析
            console.log('  階段 3: 執行記憶體洩漏分析');
            testResult.phases.memoryLeakAnalysis = this.memoryMonitor.analyzeMemoryLeaks(10);

            // 階段 4: 執行響應時間趨勢分析
            console.log('  階段 4: 執行響應時間趨勢分析');
            testResult.phases.responseTimeTrends = this.responseTimeAnalyzer.analyzeResponseTimeTrends(20);

            // 階段 5: 停止監控並獲取結果
            console.log('  階段 5: 停止監控');
            testResult.phases.monitoringResults = await this.stopComprehensiveMonitoring();

            // 階段 6: 生成綜合評估
            console.log('  階段 6: 生成綜合評估');
            testResult.phases.comprehensiveEvaluation = this.evaluateOverallPerformance(testResult.phases);

            testResult.success = this.determineTestSuccess(testResult.phases);
            
            console.log(`${testResult.success ? '✅' : '❌'} ${componentName} 完整效能測試${testResult.success ? '成功' : '失敗'}`);

        } catch (error) {
            testResult.success = false;
            testResult.error = error.message;
            console.error(`❌ ${componentName} 完整效能測試失敗: ${error.message}`);

            // 確保監控已停止
            if (this.isMonitoring) {
                await this.stopComprehensiveMonitoring();
            }
        }

        return testResult;
    }

    /**
     * 測量特定操作的完整效能
     * @param {string} operationName - 操作名稱
     * @param {Function} operation - 操作函數
     * @returns {Promise<Object>} - 操作效能結果
     */
    async measureOperationPerformance(operationName, operation) {
        console.log(`⏱️  測量操作完整效能: ${operationName}`);

        // 開始監控
        await this.startComprehensiveMonitoring(`operation_${operationName}`, {
            memoryInterval: 200,
            performanceBaseline: false
        });

        // 拍攝操作前記憶體快照
        await this.memoryMonitor.takeMemorySnapshot('operation_start');

        // 執行操作並測量響應時間
        const operationResult = await this.responseTimeAnalyzer.measureOperation(operationName, operation);

        // 拍攝操作後記憶體快照
        await this.memoryMonitor.takeMemorySnapshot('operation_end');

        // 停止監控
        const monitoringResult = await this.stopComprehensiveMonitoring();

        // 整合結果
        const integratedResult = {
            operationName,
            timestamp: new Date(),
            operationResult,
            monitoringResult,
            performanceMetrics: {
                totalTime: operationResult.totalTime,
                networkRequests: operationResult.networkActivity.totalRequests,
                livewireRequests: operationResult.networkActivity.livewireRequests,
                averageResponseTime: operationResult.networkActivity.averageResponseTime,
                memoryImpact: this.calculateMemoryImpact(monitoringResult),
                performanceScore: this.calculatePerformanceScore(operationResult, monitoringResult)
            },
            recommendations: this.generateOperationRecommendations(operationResult, monitoringResult)
        };

        console.log(`✅ 操作效能測量完成: ${operationName}`);
        console.log(`  總時間: ${integratedResult.performanceMetrics.totalTime}ms`);
        console.log(`  效能評分: ${integratedResult.performanceMetrics.performanceScore}/100`);

        return integratedResult;
    }

    /**
     * 計算記憶體影響
     * @param {Object} monitoringResult - 監控結果
     * @returns {Object} - 記憶體影響分析
     */
    calculateMemoryImpact(monitoringResult) {
        if (!monitoringResult?.results?.memory) {
            return { error: 'No memory monitoring data available' };
        }

        const memoryData = monitoringResult.results.memory;
        
        return {
            memoryIncrease: memoryData.memoryAnalysis?.increase || 0,
            memoryIncreaseMB: memoryData.memoryAnalysis?.increaseMB || '0',
            domNodeIncrease: memoryData.domAnalysis?.increase || 0,
            livewireComponentIncrease: memoryData.livewireAnalysis?.increase || 0,
            impact: this.categorizeMemoryImpact(memoryData.memoryAnalysis?.increase || 0)
        };
    }

    /**
     * 分類記憶體影響
     * @param {number} memoryIncrease - 記憶體增長（位元組）
     * @returns {string} - 影響分類
     */
    categorizeMemoryImpact(memoryIncrease) {
        const increaseMB = memoryIncrease / 1024 / 1024;
        
        if (increaseMB < 5) return 'minimal';
        if (increaseMB < 20) return 'low';
        if (increaseMB < 50) return 'moderate';
        if (increaseMB < 100) return 'high';
        return 'critical';
    }

    /**
     * 計算效能評分
     * @param {Object} operationResult - 操作結果
     * @param {Object} monitoringResult - 監控結果
     * @returns {number} - 效能評分 (0-100)
     */
    calculatePerformanceScore(operationResult, monitoringResult) {
        let score = 100;

        // 基於總時間扣分
        const totalTime = operationResult.totalTime;
        if (totalTime > 3000) score -= 30;
        else if (totalTime > 2000) score -= 20;
        else if (totalTime > 1000) score -= 10;

        // 基於響應時間扣分
        const avgResponseTime = operationResult.networkActivity.averageResponseTime;
        if (avgResponseTime > 2000) score -= 25;
        else if (avgResponseTime > 1000) score -= 15;
        else if (avgResponseTime > 500) score -= 5;

        // 基於記憶體影響扣分
        const memoryImpact = this.calculateMemoryImpact(monitoringResult);
        switch (memoryImpact.impact) {
            case 'critical': score -= 30; break;
            case 'high': score -= 20; break;
            case 'moderate': score -= 10; break;
            case 'low': score -= 5; break;
        }

        // 基於失敗請求扣分
        const failedRequests = operationResult.networkActivity.failedRequests;
        if (failedRequests > 0) {
            score -= failedRequests * 10;
        }

        return Math.max(0, Math.min(100, score));
    }

    /**
     * 生成操作建議
     * @param {Object} operationResult - 操作結果
     * @param {Object} monitoringResult - 監控結果
     * @returns {Array} - 建議陣列
     */
    generateOperationRecommendations(operationResult, monitoringResult) {
        const recommendations = [];

        // 基於總時間的建議
        if (operationResult.totalTime > 2000) {
            recommendations.push({
                type: 'performance',
                title: '操作時間過長',
                description: `操作耗時 ${operationResult.totalTime}ms，建議優化處理邏輯`,
                priority: 'high'
            });
        }

        // 基於網路請求的建議
        if (operationResult.networkActivity.livewireRequests > 3) {
            recommendations.push({
                type: 'network',
                title: 'Livewire 請求過多',
                description: `單次操作產生 ${operationResult.networkActivity.livewireRequests} 個 Livewire 請求，建議優化元件設計`,
                priority: 'medium'
            });
        }

        // 基於記憶體的建議
        const memoryImpact = this.calculateMemoryImpact(monitoringResult);
        if (memoryImpact.impact === 'high' || memoryImpact.impact === 'critical') {
            recommendations.push({
                type: 'memory',
                title: '記憶體使用過多',
                description: `操作導致記憶體增長 ${memoryImpact.memoryIncreaseMB}MB，可能存在記憶體洩漏`,
                priority: 'high'
            });
        }

        return recommendations;
    }

    /**
     * 生成綜合分析
     * @param {Object} session - 監控會話
     * @returns {Object} - 綜合分析結果
     */
    generateComprehensiveAnalysis(session) {
        const analysis = {
            timestamp: new Date(),
            sessionDuration: session.duration,
            overallHealth: 'good', // good, fair, poor
            keyFindings: [],
            performanceMetrics: {},
            recommendations: []
        };

        // 分析記憶體健康狀況
        if (session.results.memory) {
            const memoryData = session.results.memory;
            
            if (memoryData.memoryAnalysis?.increase > 100 * 1024 * 1024) { // 100MB
                analysis.overallHealth = 'poor';
                analysis.keyFindings.push('檢測到嚴重記憶體洩漏');
                analysis.recommendations.push({
                    type: 'memory_leak',
                    description: '建議檢查 Livewire 元件的記憶體管理',
                    priority: 'critical'
                });
            } else if (memoryData.memoryAnalysis?.increase > 50 * 1024 * 1024) { // 50MB
                analysis.overallHealth = analysis.overallHealth === 'good' ? 'fair' : analysis.overallHealth;
                analysis.keyFindings.push('記憶體使用量增長較多');
            }

            analysis.performanceMetrics.memoryIncrease = memoryData.memoryAnalysis?.increaseMB || '0';
        }

        // 分析響應時間健康狀況
        if (session.results.responseTime) {
            const responseData = session.results.responseTime;
            
            if (responseData.responseTimeAnalysis?.livewire?.average > 2000) {
                analysis.overallHealth = 'poor';
                analysis.keyFindings.push('Livewire 響應時間過慢');
                analysis.recommendations.push({
                    type: 'response_time',
                    description: '建議優化 Livewire 元件效能',
                    priority: 'high'
                });
            } else if (responseData.responseTimeAnalysis?.livewire?.average > 1000) {
                analysis.overallHealth = analysis.overallHealth === 'good' ? 'fair' : analysis.overallHealth;
                analysis.keyFindings.push('Livewire 響應時間偏慢');
            }

            analysis.performanceMetrics.averageResponseTime = 
                responseData.responseTimeAnalysis?.livewire?.average?.toFixed(2) || 'N/A';
        }

        // 設定整體健康狀況顏色
        analysis.healthColor = {
            good: '🟢',
            fair: '🟡',
            poor: '🔴'
        }[analysis.overallHealth];

        return analysis;
    }

    /**
     * 評估整體效能
     * @param {Object} phases - 測試階段結果
     * @returns {Object} - 整體效能評估
     */
    evaluateOverallPerformance(phases) {
        const evaluation = {
            timestamp: new Date(),
            overallScore: 0,
            categoryScores: {},
            strengths: [],
            weaknesses: [],
            criticalIssues: []
        };

        let totalScore = 0;
        let categoryCount = 0;

        // 評估效能測試結果
        if (phases.performanceTest?.success) {
            const perfSummary = phases.performanceTest.summary;
            let perfScore = 100;

            if (perfSummary.averages?.resetTime > 2000) perfScore -= 30;
            else if (perfSummary.averages?.resetTime > 1000) perfScore -= 15;

            if (perfSummary.averages?.fillTime > 1000) perfScore -= 20;
            else if (perfSummary.averages?.fillTime > 500) perfScore -= 10;

            evaluation.categoryScores.performance = Math.max(0, perfScore);
            totalScore += evaluation.categoryScores.performance;
            categoryCount++;

            if (perfScore >= 80) {
                evaluation.strengths.push('表單操作效能良好');
            } else if (perfScore < 60) {
                evaluation.weaknesses.push('表單操作效能需要改善');
            }
        }

        // 評估記憶體表現
        if (phases.memoryLeakAnalysis && !phases.memoryLeakAnalysis.error) {
            let memoryScore = 100;
            const memoryAnalysis = phases.memoryLeakAnalysis;

            if (memoryAnalysis.leakDetection?.suspectedLeak) {
                if (memoryAnalysis.leakDetection.severity === 'high') {
                    memoryScore -= 50;
                    evaluation.criticalIssues.push('檢測到嚴重記憶體洩漏');
                } else {
                    memoryScore -= 30;
                    evaluation.weaknesses.push('檢測到可能的記憶體洩漏');
                }
            }

            evaluation.categoryScores.memory = Math.max(0, memoryScore);
            totalScore += evaluation.categoryScores.memory;
            categoryCount++;

            if (memoryScore >= 90) {
                evaluation.strengths.push('記憶體使用效率良好');
            }
        }

        // 評估響應時間表現
        if (phases.responseTimeTrends && !phases.responseTimeTrends.error) {
            let responseScore = 100;
            const responseTrends = phases.responseTimeTrends;

            if (responseTrends.trends?.overall?.degrading) {
                responseScore -= 25;
                evaluation.weaknesses.push('響應時間呈現惡化趨勢');
            }

            if (responseTrends.performance?.slowRequestPercentage > 20) {
                responseScore -= 30;
                evaluation.weaknesses.push('慢請求比例過高');
            }

            evaluation.categoryScores.responseTime = Math.max(0, responseScore);
            totalScore += evaluation.categoryScores.responseTime;
            categoryCount++;

            if (responseScore >= 85) {
                evaluation.strengths.push('響應時間表現優秀');
            }
        }

        // 計算整體評分
        evaluation.overallScore = categoryCount > 0 ? Math.round(totalScore / categoryCount) : 0;

        // 設定整體評級
        if (evaluation.overallScore >= 90) {
            evaluation.grade = 'A';
            evaluation.gradeDescription = '優秀';
        } else if (evaluation.overallScore >= 80) {
            evaluation.grade = 'B';
            evaluation.gradeDescription = '良好';
        } else if (evaluation.overallScore >= 70) {
            evaluation.grade = 'C';
            evaluation.gradeDescription = '普通';
        } else if (evaluation.overallScore >= 60) {
            evaluation.grade = 'D';
            evaluation.gradeDescription = '需要改善';
        } else {
            evaluation.grade = 'F';
            evaluation.gradeDescription = '不及格';
        }

        return evaluation;
    }

    /**
     * 判斷測試成功狀態
     * @param {Object} phases - 測試階段結果
     * @returns {boolean} - 是否成功
     */
    determineTestSuccess(phases) {
        // 基本成功條件
        const performanceSuccess = phases.performanceTest?.success !== false;
        const monitoringSuccess = phases.monitoringResults?.success !== false;
        
        // 嚴重問題檢查
        const hasCriticalMemoryLeak = phases.memoryLeakAnalysis?.leakDetection?.severity === 'high';
        const hasSlowPerformance = phases.performanceTest?.summary?.averages?.resetTime > 3000;
        const hasOverallEvaluation = phases.comprehensiveEvaluation?.overallScore >= 60;

        return performanceSuccess && 
               monitoringSuccess && 
               !hasCriticalMemoryLeak && 
               !hasSlowPerformance && 
               hasOverallEvaluation;
    }

    /**
     * 生成完整效能報告
     * @returns {Object} - 完整效能報告
     */
    generateComprehensivePerformanceReport() {
        const report = {
            timestamp: new Date(),
            totalSessions: this.monitoringSessions.length,
            successfulSessions: this.monitoringSessions.filter(s => s.success).length,
            failedSessions: this.monitoringSessions.filter(s => !s.success).length,
            overallHealthDistribution: {
                good: this.monitoringSessions.filter(s => s.analysis?.overallHealth === 'good').length,
                fair: this.monitoringSessions.filter(s => s.analysis?.overallHealth === 'fair').length,
                poor: this.monitoringSessions.filter(s => s.analysis?.overallHealth === 'poor').length
            },
            componentReports: {
                performance: this.performanceTestSuite.generatePerformanceReport(),
                memory: this.memoryMonitor.generateMemoryReport(),
                responseTime: this.responseTimeAnalyzer.generateResponseTimeReport()
            },
            sessions: this.monitoringSessions,
            recommendations: this.generateOverallRecommendations()
        };

        console.log('\n=== 完整效能監控報告 ===');
        console.log(`總監控會話: ${report.totalSessions}`);
        console.log(`成功會話: ${report.successfulSessions}`);
        console.log(`失敗會話: ${report.failedSessions}`);
        console.log(`健康狀況分佈:`);
        console.log(`  🟢 良好: ${report.overallHealthDistribution.good}`);
        console.log(`  🟡 普通: ${report.overallHealthDistribution.fair}`);
        console.log(`  🔴 不佳: ${report.overallHealthDistribution.poor}`);

        return report;
    }

    /**
     * 生成整體建議
     * @returns {Array} - 建議陣列
     */
    generateOverallRecommendations() {
        const recommendations = [];

        // 基於所有會話的分析生成建議
        const poorHealthSessions = this.monitoringSessions.filter(s => s.analysis?.overallHealth === 'poor');
        
        if (poorHealthSessions.length > 0) {
            recommendations.push({
                title: '效能問題',
                description: `${poorHealthSessions.length} 個會話檢測到效能問題，需要優先處理`,
                priority: 'high',
                affectedSessions: poorHealthSessions.map(s => s.testName)
            });
        }

        const memoryLeakSessions = this.monitoringSessions.filter(s => 
            s.results?.memory?.memoryAnalysis?.increase > 50 * 1024 * 1024
        );

        if (memoryLeakSessions.length > 0) {
            recommendations.push({
                title: '記憶體管理',
                description: '多個會話檢測到記憶體使用量增長，建議檢查記憶體洩漏',
                priority: 'medium',
                affectedSessions: memoryLeakSessions.map(s => s.testName)
            });
        }

        return recommendations;
    }

    /**
     * 清理所有效能監控資料
     */
    clearAllPerformanceData() {
        this.performanceTestSuite.clearPerformanceData();
        this.memoryMonitor.clearMemoryData();
        this.responseTimeAnalyzer.clearResponseTimeData();
        this.monitoringSessions = [];
        
        if (this.isMonitoring) {
            this.stopComprehensiveMonitoring();
        }
        
        console.log('🧹 所有效能監控資料已清理');
    }

    /**
     * 匯出完整效能資料
     * @param {string} format - 匯出格式
     * @returns {Object} - 匯出資料
     */
    exportComprehensivePerformanceData(format = 'json') {
        console.log(`📤 匯出完整效能資料 (格式: ${format})`);

        const exportData = {
            timestamp: new Date(),
            sessions: this.monitoringSessions,
            performanceData: this.performanceTestSuite.performanceMetrics,
            memoryData: this.memoryMonitor.exportMemoryData('json'),
            responseTimeData: this.responseTimeAnalyzer.exportResponseTimeData('json')
        };

        if (format === 'json') {
            return JSON.stringify(exportData, null, 2);
        }

        // 其他格式可以在這裡實作
        return exportData;
    }
}

module.exports = PerformanceMonitor;