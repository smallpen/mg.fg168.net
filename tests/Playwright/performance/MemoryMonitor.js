/**
 * 記憶體使用監控器
 * 監控 Livewire 元件的記憶體使用情況
 */

class MemoryMonitor {
    constructor(page) {
        this.page = page;
        this.memorySnapshots = [];
        this.monitoringInterval = null;
        this.isMonitoring = false;
        this.alertThresholds = {
            heapSizeIncrease: 100 * 1024 * 1024, // 100MB
            heapSizeLimit: 500 * 1024 * 1024,    // 500MB
            snapshotInterval: 1000                // 1 秒
        };
    }

    /**
     * 開始記憶體監控
     * @param {string} testName - 測試名稱
     * @param {number} interval - 監控間隔（毫秒）
     */
    async startMonitoring(testName, interval = 1000) {
        if (this.isMonitoring) {
            console.log('⚠️  記憶體監控已在執行中');
            return;
        }

        console.log(`🔍 開始記憶體監控: ${testName}`);
        
        this.isMonitoring = true;
        this.currentTestName = testName;
        this.monitoringStartTime = Date.now();

        // 拍攝初始記憶體快照
        await this.takeMemorySnapshot('monitoring_start');

        // 設定定期監控
        this.monitoringInterval = setInterval(async () => {
            if (this.isMonitoring) {
                await this.takeMemorySnapshot('periodic');
            }
        }, interval);

        console.log(`✅ 記憶體監控已啟動 (間隔: ${interval}ms)`);
    }

    /**
     * 停止記憶體監控
     * @returns {Object} - 監控摘要
     */
    async stopMonitoring() {
        if (!this.isMonitoring) {
            console.log('⚠️  記憶體監控未在執行');
            return null;
        }

        console.log('🛑 停止記憶體監控');

        // 拍攝最終記憶體快照
        await this.takeMemorySnapshot('monitoring_end');

        // 清理監控間隔
        if (this.monitoringInterval) {
            clearInterval(this.monitoringInterval);
            this.monitoringInterval = null;
        }

        this.isMonitoring = false;
        const monitoringEndTime = Date.now();
        const monitoringDuration = monitoringEndTime - this.monitoringStartTime;

        // 生成監控摘要
        const summary = this.generateMonitoringSummary(monitoringDuration);
        
        console.log(`✅ 記憶體監控已停止 (持續時間: ${(monitoringDuration / 1000).toFixed(2)}秒)`);
        
        return summary;
    }

    /**
     * 拍攝記憶體快照
     * @param {string} snapshotType - 快照類型
     * @returns {Promise<Object>} - 記憶體快照
     */
    async takeMemorySnapshot(snapshotType = 'manual') {
        try {
            const snapshot = await this.page.evaluate(() => {
                const memoryInfo = {
                    timestamp: Date.now(),
                    performance: {
                        memory: performance.memory ? {
                            usedJSHeapSize: performance.memory.usedJSHeapSize,
                            totalJSHeapSize: performance.memory.totalJSHeapSize,
                            jsHeapSizeLimit: performance.memory.jsHeapSizeLimit
                        } : null,
                        timing: performance.timing ? {
                            navigationStart: performance.timing.navigationStart,
                            loadEventEnd: performance.timing.loadEventEnd
                        } : null
                    },
                    dom: {
                        nodeCount: document.querySelectorAll('*').length,
                        livewireComponents: document.querySelectorAll('[wire\\:id]').length,
                        eventListeners: document.querySelectorAll('[wire\\:click], [wire\\:model], [wire\\:submit]').length
                    },
                    livewire: window.Livewire ? {
                        componentCount: window.Livewire.all().length,
                        components: window.Livewire.all().map(component => ({
                            id: component.id,
                            name: component.name,
                            fingerprint: component.fingerprint
                        }))
                    } : null
                };

                // 計算記憶體使用量（MB）
                if (memoryInfo.performance.memory) {
                    memoryInfo.performance.memory.usedMB = (memoryInfo.performance.memory.usedJSHeapSize / 1024 / 1024).toFixed(2);
                    memoryInfo.performance.memory.totalMB = (memoryInfo.performance.memory.totalJSHeapSize / 1024 / 1024).toFixed(2);
                    memoryInfo.performance.memory.limitMB = (memoryInfo.performance.memory.jsHeapSizeLimit / 1024 / 1024).toFixed(2);
                }

                return memoryInfo;
            });

            snapshot.snapshotType = snapshotType;
            snapshot.testName = this.currentTestName;
            snapshot.relativeTime = this.isMonitoring ? 
                snapshot.timestamp - this.monitoringStartTime : 0;

            this.memorySnapshots.push(snapshot);

            // 檢查記憶體警告
            this.checkMemoryAlerts(snapshot);

            return snapshot;

        } catch (error) {
            console.error(`❌ 記憶體快照失敗: ${error.message}`);
            return null;
        }
    }

    /**
     * 檢查記憶體警告
     * @param {Object} snapshot - 記憶體快照
     */
    checkMemoryAlerts(snapshot) {
        if (!snapshot.performance.memory) {
            return;
        }

        const { usedJSHeapSize, jsHeapSizeLimit } = snapshot.performance.memory;

        // 檢查記憶體使用是否接近限制
        const usagePercentage = (usedJSHeapSize / jsHeapSizeLimit) * 100;
        if (usagePercentage > 80) {
            console.log(`⚠️  記憶體使用警告: ${usagePercentage.toFixed(2)}% (${(usedJSHeapSize / 1024 / 1024).toFixed(2)}MB)`);
        }

        // 檢查記憶體增長
        if (this.memorySnapshots.length > 1) {
            const previousSnapshot = this.memorySnapshots[this.memorySnapshots.length - 2];
            if (previousSnapshot.performance.memory) {
                const memoryIncrease = usedJSHeapSize - previousSnapshot.performance.memory.usedJSHeapSize;
                
                if (memoryIncrease > this.alertThresholds.heapSizeIncrease) {
                    console.log(`⚠️  記憶體快速增長警告: +${(memoryIncrease / 1024 / 1024).toFixed(2)}MB`);
                }
            }
        }
    }

    /**
     * 分析記憶體洩漏
     * @param {number} windowSize - 分析視窗大小
     * @returns {Object} - 洩漏分析結果
     */
    analyzeMemoryLeaks(windowSize = 10) {
        console.log('🔍 分析記憶體洩漏');

        if (this.memorySnapshots.length < windowSize) {
            return {
                error: `需要至少 ${windowSize} 個記憶體快照進行分析`,
                availableSnapshots: this.memorySnapshots.length
            };
        }

        const recentSnapshots = this.memorySnapshots.slice(-windowSize);
        const analysis = {
            timestamp: new Date(),
            windowSize,
            snapshots: recentSnapshots.length,
            trends: {},
            leakDetection: {},
            recommendations: []
        };

        // 分析記憶體趨勢
        const memoryValues = recentSnapshots
            .filter(s => s.performance.memory)
            .map(s => s.performance.memory.usedJSHeapSize);

        if (memoryValues.length >= 2) {
            const firstValue = memoryValues[0];
            const lastValue = memoryValues[memoryValues.length - 1];
            const totalIncrease = lastValue - firstValue;
            const averageIncrease = totalIncrease / (memoryValues.length - 1);

            analysis.trends.memoryIncrease = {
                total: totalIncrease,
                totalMB: (totalIncrease / 1024 / 1024).toFixed(2),
                average: averageIncrease,
                averageMB: (averageIncrease / 1024 / 1024).toFixed(2)
            };

            // 檢測潛在洩漏
            if (totalIncrease > 50 * 1024 * 1024) { // 50MB
                analysis.leakDetection.suspectedLeak = true;
                analysis.leakDetection.severity = totalIncrease > 100 * 1024 * 1024 ? 'high' : 'medium';
                analysis.recommendations.push({
                    type: 'memory_leak',
                    message: `檢測到可能的記憶體洩漏，記憶體增長 ${analysis.trends.memoryIncrease.totalMB}MB`,
                    priority: analysis.leakDetection.severity
                });
            }
        }

        // 分析 DOM 節點趨勢
        const domNodeValues = recentSnapshots.map(s => s.dom.nodeCount);
        if (domNodeValues.length >= 2) {
            const firstDomCount = domNodeValues[0];
            const lastDomCount = domNodeValues[domNodeValues.length - 1];
            const domIncrease = lastDomCount - firstDomCount;

            analysis.trends.domNodeIncrease = {
                total: domIncrease,
                average: domIncrease / (domNodeValues.length - 1)
            };

            if (domIncrease > 500) {
                analysis.recommendations.push({
                    type: 'dom_bloat',
                    message: `DOM 節點數量增長過多: +${domIncrease} 個節點`,
                    priority: 'medium'
                });
            }
        }

        // 分析 Livewire 元件趨勢
        const livewireSnapshots = recentSnapshots.filter(s => s.livewire);
        if (livewireSnapshots.length >= 2) {
            const firstComponentCount = livewireSnapshots[0].livewire.componentCount;
            const lastComponentCount = livewireSnapshots[livewireSnapshots.length - 1].livewire.componentCount;
            const componentIncrease = lastComponentCount - firstComponentCount;

            analysis.trends.livewireComponentIncrease = {
                total: componentIncrease,
                average: componentIncrease / (livewireSnapshots.length - 1)
            };

            if (componentIncrease > 5) {
                analysis.recommendations.push({
                    type: 'component_leak',
                    message: `Livewire 元件數量異常增長: +${componentIncrease} 個元件`,
                    priority: 'high'
                });
            }
        }

        console.log(`📊 記憶體洩漏分析完成 (${analysis.recommendations.length} 個建議)`);
        return analysis;
    }

    /**
     * 生成監控摘要
     * @param {number} duration - 監控持續時間
     * @returns {Object} - 監控摘要
     */
    generateMonitoringSummary(duration) {
        const summary = {
            testName: this.currentTestName,
            duration,
            durationSeconds: (duration / 1000).toFixed(2),
            totalSnapshots: this.memorySnapshots.length,
            memoryAnalysis: {},
            domAnalysis: {},
            livewireAnalysis: {},
            alerts: []
        };

        const memorySnapshots = this.memorySnapshots.filter(s => s.performance.memory);
        
        if (memorySnapshots.length > 0) {
            const memoryValues = memorySnapshots.map(s => s.performance.memory.usedJSHeapSize);
            
            summary.memoryAnalysis = {
                initial: memoryValues[0],
                final: memoryValues[memoryValues.length - 1],
                peak: Math.max(...memoryValues),
                minimum: Math.min(...memoryValues),
                increase: memoryValues[memoryValues.length - 1] - memoryValues[0],
                initialMB: (memoryValues[0] / 1024 / 1024).toFixed(2),
                finalMB: (memoryValues[memoryValues.length - 1] / 1024 / 1024).toFixed(2),
                peakMB: (Math.max(...memoryValues) / 1024 / 1024).toFixed(2),
                increaseMB: ((memoryValues[memoryValues.length - 1] - memoryValues[0]) / 1024 / 1024).toFixed(2)
            };
        }

        // DOM 分析
        const domValues = this.memorySnapshots.map(s => s.dom.nodeCount);
        if (domValues.length > 0) {
            summary.domAnalysis = {
                initial: domValues[0],
                final: domValues[domValues.length - 1],
                peak: Math.max(...domValues),
                increase: domValues[domValues.length - 1] - domValues[0]
            };
        }

        // Livewire 分析
        const livewireSnapshots = this.memorySnapshots.filter(s => s.livewire);
        if (livewireSnapshots.length > 0) {
            const componentCounts = livewireSnapshots.map(s => s.livewire.componentCount);
            summary.livewireAnalysis = {
                initial: componentCounts[0],
                final: componentCounts[componentCounts.length - 1],
                peak: Math.max(...componentCounts),
                increase: componentCounts[componentCounts.length - 1] - componentCounts[0]
            };
        }

        // 生成警告
        if (summary.memoryAnalysis.increase > 50 * 1024 * 1024) {
            summary.alerts.push({
                type: 'memory_increase',
                message: `記憶體增長過多: ${summary.memoryAnalysis.increaseMB}MB`,
                severity: 'warning'
            });
        }

        if (summary.domAnalysis.increase > 200) {
            summary.alerts.push({
                type: 'dom_increase',
                message: `DOM 節點增長過多: +${summary.domAnalysis.increase} 個節點`,
                severity: 'warning'
            });
        }

        return summary;
    }

    /**
     * 匯出記憶體快照資料
     * @param {string} format - 匯出格式 ('json' | 'csv')
     * @returns {string} - 匯出資料
     */
    exportMemoryData(format = 'json') {
        console.log(`📤 匯出記憶體資料 (格式: ${format})`);

        if (format === 'csv') {
            const headers = [
                'timestamp',
                'relativeTime',
                'snapshotType',
                'usedJSHeapSize',
                'totalJSHeapSize',
                'domNodeCount',
                'livewireComponentCount'
            ];

            const rows = this.memorySnapshots.map(snapshot => [
                snapshot.timestamp,
                snapshot.relativeTime,
                snapshot.snapshotType,
                snapshot.performance.memory?.usedJSHeapSize || '',
                snapshot.performance.memory?.totalJSHeapSize || '',
                snapshot.dom.nodeCount,
                snapshot.livewire?.componentCount || ''
            ]);

            return [headers, ...rows].map(row => row.join(',')).join('\n');
        }

        return JSON.stringify(this.memorySnapshots, null, 2);
    }

    /**
     * 清理記憶體監控資料
     */
    clearMemoryData() {
        this.memorySnapshots = [];
        this.currentTestName = null;
        this.monitoringStartTime = null;
        
        if (this.isMonitoring) {
            this.stopMonitoring();
        }
        
        console.log('🧹 記憶體監控資料已清理');
    }

    /**
     * 設定警告閾值
     * @param {Object} thresholds - 新的閾值
     */
    setAlertThresholds(thresholds) {
        this.alertThresholds = { ...this.alertThresholds, ...thresholds };
        console.log('⚙️  記憶體警告閾值已更新:', this.alertThresholds);
    }

    /**
     * 生成記憶體監控報告
     * @returns {Object} - 記憶體監控報告
     */
    generateMemoryReport() {
        const report = {
            timestamp: new Date(),
            totalSnapshots: this.memorySnapshots.length,
            monitoringSessions: this.getMonitoringSessions(),
            overallAnalysis: this.analyzeMemoryLeaks(Math.min(this.memorySnapshots.length, 20)),
            recommendations: []
        };

        // 基於分析結果生成建議
        if (report.overallAnalysis.leakDetection?.suspectedLeak) {
            report.recommendations.push({
                title: '記憶體洩漏檢測',
                description: '檢測到可能的記憶體洩漏，建議檢查 Livewire 元件的生命週期管理',
                priority: 'high'
            });
        }

        if (report.overallAnalysis.trends?.domNodeIncrease?.total > 300) {
            report.recommendations.push({
                title: 'DOM 節點過多',
                description: 'DOM 節點數量增長過多，可能影響效能',
                priority: 'medium'
            });
        }

        console.log('\n=== 記憶體監控報告 ===');
        console.log(`總快照數: ${report.totalSnapshots}`);
        console.log(`監控會話數: ${report.monitoringSessions.length}`);
        console.log(`建議數量: ${report.recommendations.length}`);

        return report;
    }

    /**
     * 取得監控會話資訊
     * @returns {Array} - 監控會話陣列
     */
    getMonitoringSessions() {
        const sessions = [];
        let currentSession = null;

        this.memorySnapshots.forEach(snapshot => {
            if (snapshot.snapshotType === 'monitoring_start') {
                currentSession = {
                    testName: snapshot.testName,
                    startTime: snapshot.timestamp,
                    snapshots: [snapshot]
                };
            } else if (currentSession) {
                currentSession.snapshots.push(snapshot);
                
                if (snapshot.snapshotType === 'monitoring_end') {
                    currentSession.endTime = snapshot.timestamp;
                    currentSession.duration = currentSession.endTime - currentSession.startTime;
                    sessions.push(currentSession);
                    currentSession = null;
                }
            }
        });

        // 處理未結束的會話
        if (currentSession) {
            currentSession.endTime = Date.now();
            currentSession.duration = currentSession.endTime - currentSession.startTime;
            sessions.push(currentSession);
        }

        return sessions;
    }
}

module.exports = MemoryMonitor;