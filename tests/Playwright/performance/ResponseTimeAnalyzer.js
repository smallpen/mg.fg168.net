/**
 * 響應時間分析器
 * 分析 Livewire 操作的響應時間和網路效能
 */

class ResponseTimeAnalyzer {
    constructor(page) {
        this.page = page;
        this.responseTimeData = [];
        this.networkRequests = [];
        this.isMonitoring = false;
        this.thresholds = {
            fastResponse: 200,      // 快速響應閾值（毫秒）
            acceptableResponse: 1000, // 可接受響應閾值（毫秒）
            slowResponse: 3000,     // 慢響應閾值（毫秒）
            networkTimeout: 10000   // 網路超時閾值（毫秒）
        };
    }

    /**
     * 開始響應時間監控
     * @param {string} testName - 測試名稱
     */
    startMonitoring(testName) {
        if (this.isMonitoring) {
            console.log('⚠️  響應時間監控已在執行中');
            return;
        }

        console.log(`⏱️  開始響應時間監控: ${testName}`);
        
        this.isMonitoring = true;
        this.currentTestName = testName;
        this.monitoringStartTime = Date.now();

        // 監聽網路請求
        this.page.on('request', this.handleRequest.bind(this));
        this.page.on('response', this.handleResponse.bind(this));
        this.page.on('requestfailed', this.handleRequestFailed.bind(this));

        console.log('✅ 響應時間監控已啟動');
    }

    /**
     * 停止響應時間監控
     * @returns {Object} - 監控摘要
     */
    stopMonitoring() {
        if (!this.isMonitoring) {
            console.log('⚠️  響應時間監控未在執行');
            return null;
        }

        console.log('🛑 停止響應時間監控');

        // 移除事件監聽器
        this.page.off('request', this.handleRequest.bind(this));
        this.page.off('response', this.handleResponse.bind(this));
        this.page.off('requestfailed', this.handleRequestFailed.bind(this));

        this.isMonitoring = false;
        const monitoringEndTime = Date.now();
        const monitoringDuration = monitoringEndTime - this.monitoringStartTime;

        // 生成監控摘要
        const summary = this.generateResponseTimeSummary(monitoringDuration);
        
        console.log(`✅ 響應時間監控已停止 (持續時間: ${(monitoringDuration / 1000).toFixed(2)}秒)`);
        
        return summary;
    }

    /**
     * 處理請求事件
     * @param {Object} request - 請求對象
     */
    handleRequest(request) {
        if (!this.isMonitoring) return;

        const requestData = {
            id: this.generateRequestId(),
            url: request.url(),
            method: request.method(),
            resourceType: request.resourceType(),
            startTime: Date.now(),
            testName: this.currentTestName,
            isLivewire: request.url().includes('/livewire/'),
            headers: request.headers(),
            postData: request.postData()
        };

        this.networkRequests.push(requestData);

        if (requestData.isLivewire) {
            console.log(`📤 Livewire 請求: ${requestData.method} ${requestData.url}`);
        }
    }

    /**
     * 處理響應事件
     * @param {Object} response - 響應對象
     */
    async handleResponse(response) {
        if (!this.isMonitoring) return;

        const request = this.networkRequests.find(req => 
            req.url === response.url() && !req.responseTime
        );

        if (request) {
            const endTime = Date.now();
            const responseTime = endTime - request.startTime;

            request.endTime = endTime;
            request.responseTime = responseTime;
            request.status = response.status();
            request.statusText = response.statusText();
            request.responseHeaders = response.headers();

            // 嘗試獲取響應大小
            try {
                const responseBody = await response.text();
                request.responseSize = responseBody.length;
                
                // 如果是 Livewire 響應，嘗試解析內容
                if (request.isLivewire && responseBody) {
                    try {
                        const livewireResponse = JSON.parse(responseBody);
                        request.livewireData = {
                            effects: livewireResponse.effects || {},
                            serverMemo: livewireResponse.serverMemo || {},
                            components: Object.keys(livewireResponse.components || {}).length
                        };
                    } catch (e) {
                        // 無法解析 JSON，忽略
                    }
                }
            } catch (error) {
                // 無法獲取響應內容，忽略
            }

            // 記錄響應時間資料
            this.recordResponseTime(request);

            if (request.isLivewire) {
                const category = this.categorizeResponseTime(responseTime);
                console.log(`📥 Livewire 響應: ${responseTime}ms (${category}) - ${response.status()}`);
            }
        }
    }

    /**
     * 處理請求失敗事件
     * @param {Object} request - 失敗的請求
     */
    handleRequestFailed(request) {
        if (!this.isMonitoring) return;

        const requestData = this.networkRequests.find(req => 
            req.url === request.url() && !req.responseTime
        );

        if (requestData) {
            requestData.failed = true;
            requestData.failureText = request.failure().errorText;
            requestData.endTime = Date.now();
            requestData.responseTime = requestData.endTime - requestData.startTime;

            console.log(`❌ 請求失敗: ${request.url()} - ${requestData.failureText}`);
        }
    }

    /**
     * 記錄響應時間資料
     * @param {Object} requestData - 請求資料
     */
    recordResponseTime(requestData) {
        const responseTimeRecord = {
            timestamp: requestData.endTime,
            testName: requestData.testName,
            url: requestData.url,
            method: requestData.method,
            responseTime: requestData.responseTime,
            status: requestData.status,
            isLivewire: requestData.isLivewire,
            category: this.categorizeResponseTime(requestData.responseTime),
            resourceType: requestData.resourceType,
            responseSize: requestData.responseSize || 0,
            failed: requestData.failed || false
        };

        this.responseTimeData.push(responseTimeRecord);
    }

    /**
     * 分類響應時間
     * @param {number} responseTime - 響應時間（毫秒）
     * @returns {string} - 響應時間分類
     */
    categorizeResponseTime(responseTime) {
        if (responseTime <= this.thresholds.fastResponse) {
            return 'fast';
        } else if (responseTime <= this.thresholds.acceptableResponse) {
            return 'acceptable';
        } else if (responseTime <= this.thresholds.slowResponse) {
            return 'slow';
        } else {
            return 'very_slow';
        }
    }

    /**
     * 測量特定操作的響應時間
     * @param {string} operationName - 操作名稱
     * @param {Function} operation - 要測量的操作函數
     * @returns {Promise<Object>} - 操作結果和響應時間
     */
    async measureOperation(operationName, operation) {
        console.log(`⏱️  測量操作響應時間: ${operationName}`);

        const startTime = Date.now();
        const initialRequestCount = this.networkRequests.length;

        // 開始監控（如果尚未開始）
        const wasMonitoring = this.isMonitoring;
        if (!wasMonitoring) {
            this.startMonitoring(`operation_${operationName}`);
        }

        let operationResult;
        let operationError;

        try {
            // 執行操作
            operationResult = await operation();
        } catch (error) {
            operationError = error;
        }

        const endTime = Date.now();
        const totalTime = endTime - startTime;

        // 分析此操作期間的網路請求
        const operationRequests = this.networkRequests.slice(initialRequestCount);
        const livewireRequests = operationRequests.filter(req => req.isLivewire);

        const result = {
            operationName,
            startTime,
            endTime,
            totalTime,
            success: !operationError,
            error: operationError?.message,
            result: operationResult,
            networkActivity: {
                totalRequests: operationRequests.length,
                livewireRequests: livewireRequests.length,
                completedRequests: operationRequests.filter(req => req.responseTime).length,
                failedRequests: operationRequests.filter(req => req.failed).length,
                averageResponseTime: this.calculateAverageResponseTime(operationRequests),
                slowestRequest: this.findSlowestRequest(operationRequests),
                fastestRequest: this.findFastestRequest(operationRequests)
            }
        };

        // 停止監控（如果是我們啟動的）
        if (!wasMonitoring) {
            this.stopMonitoring();
        }

        console.log(`${result.success ? '✅' : '❌'} 操作完成: ${operationName} (${totalTime}ms)`);
        console.log(`  網路請求: ${result.networkActivity.totalRequests} 個 (${result.networkActivity.livewireRequests} 個 Livewire)`);
        
        if (result.networkActivity.averageResponseTime) {
            console.log(`  平均響應時間: ${result.networkActivity.averageResponseTime.toFixed(2)}ms`);
        }

        return result;
    }

    /**
     * 計算平均響應時間
     * @param {Array} requests - 請求陣列
     * @returns {number} - 平均響應時間
     */
    calculateAverageResponseTime(requests) {
        const completedRequests = requests.filter(req => req.responseTime && !req.failed);
        
        if (completedRequests.length === 0) {
            return null;
        }

        const totalTime = completedRequests.reduce((sum, req) => sum + req.responseTime, 0);
        return totalTime / completedRequests.length;
    }

    /**
     * 找到最慢的請求
     * @param {Array} requests - 請求陣列
     * @returns {Object} - 最慢的請求
     */
    findSlowestRequest(requests) {
        const completedRequests = requests.filter(req => req.responseTime && !req.failed);
        
        if (completedRequests.length === 0) {
            return null;
        }

        return completedRequests.reduce((slowest, req) => 
            req.responseTime > (slowest?.responseTime || 0) ? req : slowest
        );
    }

    /**
     * 找到最快的請求
     * @param {Array} requests - 請求陣列
     * @returns {Object} - 最快的請求
     */
    findFastestRequest(requests) {
        const completedRequests = requests.filter(req => req.responseTime && !req.failed);
        
        if (completedRequests.length === 0) {
            return null;
        }

        return completedRequests.reduce((fastest, req) => 
            req.responseTime < (fastest?.responseTime || Infinity) ? req : fastest
        );
    }

    /**
     * 分析響應時間趨勢
     * @param {number} windowSize - 分析視窗大小
     * @returns {Object} - 趨勢分析結果
     */
    analyzeResponseTimeTrends(windowSize = 20) {
        console.log('📈 分析響應時間趨勢');

        const recentData = this.responseTimeData.slice(-windowSize);
        
        if (recentData.length < 5) {
            return {
                error: '資料不足，無法進行趨勢分析',
                availableData: recentData.length
            };
        }

        const livewireData = recentData.filter(data => data.isLivewire);
        const analysis = {
            timestamp: new Date(),
            windowSize: recentData.length,
            livewireRequests: livewireData.length,
            trends: {},
            performance: {},
            alerts: []
        };

        // 分析整體趨勢
        if (recentData.length >= 2) {
            const responseTimes = recentData.map(data => data.responseTime);
            const firstHalf = responseTimes.slice(0, Math.floor(responseTimes.length / 2));
            const secondHalf = responseTimes.slice(Math.floor(responseTimes.length / 2));

            const firstHalfAvg = firstHalf.reduce((sum, time) => sum + time, 0) / firstHalf.length;
            const secondHalfAvg = secondHalf.reduce((sum, time) => sum + time, 0) / secondHalf.length;

            analysis.trends.overall = {
                improving: secondHalfAvg < firstHalfAvg,
                degrading: secondHalfAvg > firstHalfAvg,
                change: secondHalfAvg - firstHalfAvg,
                changePercentage: ((secondHalfAvg - firstHalfAvg) / firstHalfAvg * 100).toFixed(2)
            };
        }

        // 分析 Livewire 特定趨勢
        if (livewireData.length >= 2) {
            const livewireResponseTimes = livewireData.map(data => data.responseTime);
            const avgResponseTime = livewireResponseTimes.reduce((sum, time) => sum + time, 0) / livewireResponseTimes.length;
            const maxResponseTime = Math.max(...livewireResponseTimes);
            const minResponseTime = Math.min(...livewireResponseTimes);

            analysis.trends.livewire = {
                average: avgResponseTime,
                maximum: maxResponseTime,
                minimum: minResponseTime,
                range: maxResponseTime - minResponseTime
            };

            // 效能分類統計
            const categories = {
                fast: livewireData.filter(data => data.category === 'fast').length,
                acceptable: livewireData.filter(data => data.category === 'acceptable').length,
                slow: livewireData.filter(data => data.category === 'slow').length,
                very_slow: livewireData.filter(data => data.category === 'very_slow').length
            };

            analysis.performance.categories = categories;
            analysis.performance.slowRequestPercentage = 
                ((categories.slow + categories.very_slow) / livewireData.length * 100).toFixed(2);
        }

        // 生成警告
        if (analysis.trends.overall?.degrading && Math.abs(analysis.trends.overall.change) > 500) {
            analysis.alerts.push({
                type: 'performance_degradation',
                message: `響應時間惡化 ${analysis.trends.overall.changePercentage}%`,
                severity: 'warning'
            });
        }

        if (analysis.performance.slowRequestPercentage > 20) {
            analysis.alerts.push({
                type: 'slow_requests',
                message: `${analysis.performance.slowRequestPercentage}% 的請求響應緩慢`,
                severity: 'warning'
            });
        }

        console.log(`📊 響應時間趨勢分析完成 (${analysis.alerts.length} 個警告)`);
        return analysis;
    }

    /**
     * 生成響應時間摘要
     * @param {number} duration - 監控持續時間
     * @returns {Object} - 響應時間摘要
     */
    generateResponseTimeSummary(duration) {
        const summary = {
            testName: this.currentTestName,
            duration,
            durationSeconds: (duration / 1000).toFixed(2),
            totalRequests: this.networkRequests.length,
            completedRequests: this.networkRequests.filter(req => req.responseTime).length,
            failedRequests: this.networkRequests.filter(req => req.failed).length,
            livewireRequests: this.networkRequests.filter(req => req.isLivewire).length,
            responseTimeAnalysis: {},
            performanceBreakdown: {}
        };

        const completedRequests = this.networkRequests.filter(req => req.responseTime && !req.failed);
        const livewireRequests = completedRequests.filter(req => req.isLivewire);

        if (completedRequests.length > 0) {
            const responseTimes = completedRequests.map(req => req.responseTime);
            
            summary.responseTimeAnalysis = {
                average: responseTimes.reduce((sum, time) => sum + time, 0) / responseTimes.length,
                median: this.calculateMedian(responseTimes),
                minimum: Math.min(...responseTimes),
                maximum: Math.max(...responseTimes),
                standardDeviation: this.calculateStandardDeviation(responseTimes)
            };
        }

        if (livewireRequests.length > 0) {
            const livewireResponseTimes = livewireRequests.map(req => req.responseTime);
            
            summary.responseTimeAnalysis.livewire = {
                average: livewireResponseTimes.reduce((sum, time) => sum + time, 0) / livewireResponseTimes.length,
                median: this.calculateMedian(livewireResponseTimes),
                minimum: Math.min(...livewireResponseTimes),
                maximum: Math.max(...livewireResponseTimes)
            };

            // 效能分類統計
            summary.performanceBreakdown = {
                fast: livewireRequests.filter(req => this.categorizeResponseTime(req.responseTime) === 'fast').length,
                acceptable: livewireRequests.filter(req => this.categorizeResponseTime(req.responseTime) === 'acceptable').length,
                slow: livewireRequests.filter(req => this.categorizeResponseTime(req.responseTime) === 'slow').length,
                very_slow: livewireRequests.filter(req => this.categorizeResponseTime(req.responseTime) === 'very_slow').length
            };
        }

        return summary;
    }

    /**
     * 計算中位數
     * @param {Array} values - 數值陣列
     * @returns {number} - 中位數
     */
    calculateMedian(values) {
        const sorted = [...values].sort((a, b) => a - b);
        const mid = Math.floor(sorted.length / 2);
        
        return sorted.length % 2 !== 0 
            ? sorted[mid] 
            : (sorted[mid - 1] + sorted[mid]) / 2;
    }

    /**
     * 計算標準差
     * @param {Array} values - 數值陣列
     * @returns {number} - 標準差
     */
    calculateStandardDeviation(values) {
        const mean = values.reduce((sum, val) => sum + val, 0) / values.length;
        const variance = values.reduce((sum, val) => sum + Math.pow(val - mean, 2), 0) / values.length;
        return Math.sqrt(variance);
    }

    /**
     * 生成請求 ID
     * @returns {string} - 唯一請求 ID
     */
    generateRequestId() {
        return `req_${Date.now()}_${Math.random().toString(36).substr(2, 9)}`;
    }

    /**
     * 匯出響應時間資料
     * @param {string} format - 匯出格式 ('json' | 'csv')
     * @returns {string} - 匯出資料
     */
    exportResponseTimeData(format = 'json') {
        console.log(`📤 匯出響應時間資料 (格式: ${format})`);

        if (format === 'csv') {
            const headers = [
                'timestamp',
                'testName',
                'url',
                'method',
                'responseTime',
                'status',
                'isLivewire',
                'category',
                'resourceType',
                'responseSize',
                'failed'
            ];

            const rows = this.responseTimeData.map(data => [
                data.timestamp,
                data.testName,
                data.url,
                data.method,
                data.responseTime,
                data.status,
                data.isLivewire,
                data.category,
                data.resourceType,
                data.responseSize,
                data.failed
            ]);

            return [headers, ...rows].map(row => row.join(',')).join('\n');
        }

        return JSON.stringify(this.responseTimeData, null, 2);
    }

    /**
     * 清理響應時間資料
     */
    clearResponseTimeData() {
        this.responseTimeData = [];
        this.networkRequests = [];
        this.currentTestName = null;
        this.monitoringStartTime = null;
        
        if (this.isMonitoring) {
            this.stopMonitoring();
        }
        
        console.log('🧹 響應時間資料已清理');
    }

    /**
     * 設定響應時間閾值
     * @param {Object} thresholds - 新的閾值
     */
    setResponseTimeThresholds(thresholds) {
        this.thresholds = { ...this.thresholds, ...thresholds };
        console.log('⚙️  響應時間閾值已更新:', this.thresholds);
    }

    /**
     * 生成響應時間分析報告
     * @returns {Object} - 響應時間分析報告
     */
    generateResponseTimeReport() {
        const report = {
            timestamp: new Date(),
            totalDataPoints: this.responseTimeData.length,
            totalRequests: this.networkRequests.length,
            thresholds: this.thresholds,
            overallAnalysis: this.analyzeResponseTimeTrends(Math.min(this.responseTimeData.length, 50)),
            recommendations: []
        };

        // 基於分析結果生成建議
        if (report.overallAnalysis.trends?.overall?.degrading) {
            report.recommendations.push({
                title: '效能惡化',
                description: '響應時間呈現惡化趨勢，建議檢查伺服器效能和網路狀況',
                priority: 'high'
            });
        }

        if (report.overallAnalysis.performance?.slowRequestPercentage > 15) {
            report.recommendations.push({
                title: '慢請求過多',
                description: `${report.overallAnalysis.performance.slowRequestPercentage}% 的請求響應緩慢，建議優化 Livewire 元件`,
                priority: 'medium'
            });
        }

        console.log('\n=== 響應時間分析報告 ===');
        console.log(`總資料點數: ${report.totalDataPoints}`);
        console.log(`總請求數: ${report.totalRequests}`);
        console.log(`建議數量: ${report.recommendations.length}`);

        return report;
    }
}

module.exports = ResponseTimeAnalyzer;