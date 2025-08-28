/**
 * 後端驗證測試套件
 * 整合 MySQL 狀態驗證、測試資料管理和日誌分析
 */

const MySQLStateVerifier = require('./MySQLStateVerifier');
const TestDataManager = require('./TestDataManager');
const LogAnalyzer = require('./LogAnalyzer');

class BackendVerificationSuite {
    constructor() {
        this.mysqlVerifier = new MySQLStateVerifier();
        this.testDataManager = new TestDataManager(this.mysqlVerifier);
        this.logAnalyzer = new LogAnalyzer(this.mysqlVerifier);
        this.verificationResults = [];
        this.testSession = {
            id: this.generateSessionId(),
            startTime: new Date(),
            endTime: null,
            status: 'running'
        };
    }

    /**
     * 生成測試會話 ID
     * @returns {string} - 會話 ID
     */
    generateSessionId() {
        return `backend_test_${Date.now()}_${Math.random().toString(36).substr(2, 9)}`;
    }

    /**
     * 初始化測試環境
     * @returns {Promise<Object>} - 初始化結果
     */
    async initializeTestEnvironment() {
        console.log('🚀 初始化後端測試環境');

        const results = {
            sessionId: this.testSession.id,
            timestamp: new Date(),
            steps: {}
        };

        try {
            // 1. 準備基礎測試資料
            console.log('  1. 準備基礎測試資料');
            results.steps.prepareBaseData = await this.testDataManager.prepareBaseTestData();

            // 2. 驗證資料完整性
            console.log('  2. 驗證資料完整性');
            results.steps.verifyIntegrity = await this.mysqlVerifier.verifyDataIntegrity();

            // 3. 檢查權限系統
            console.log('  3. 檢查權限系統');
            results.steps.verifyPermissions = await this.mysqlVerifier.verifyPermissionSystemState();

            // 4. 建立初始狀態快照
            console.log('  4. 建立初始狀態快照');
            results.steps.initialSnapshot = await this.mysqlVerifier.createStateSnapshot('initial_state', {
                includeUsers: true,
                includeSettings: true,
                includeActivityLogs: true,
                includePermissions: true
            });

            results.success = true;
            console.log('✅ 後端測試環境初始化完成');

        } catch (error) {
            results.success = false;
            results.error = error.message;
            console.error(`❌ 後端測試環境初始化失敗: ${error.message}`);
        }

        this.verificationResults.push({
            type: 'initialization',
            timestamp: new Date(),
            result: results
        });

        return results;
    }

    /**
     * 執行表單重置前的狀態驗證
     * @param {string} componentName - 元件名稱
     * @param {Object} testData - 測試資料
     * @returns {Promise<Object>} - 驗證結果
     */
    async verifyPreResetState(componentName, testData = {}) {
        console.log(`🔍 驗證 ${componentName} 重置前狀態`);

        const results = {
            componentName,
            timestamp: new Date(),
            testData,
            verifications: {}
        };

        try {
            // 1. 建立測試資料（如果需要）
            if (testData.users && testData.users.length > 0) {
                console.log('  建立測試使用者');
                for (const userData of testData.users) {
                    results.verifications[`create_user_${userData.username}`] = 
                        await this.testDataManager.createTestUser(userData);
                }
            }

            if (testData.settings && testData.settings.length > 0) {
                console.log('  建立測試設定');
                results.verifications.create_settings = 
                    await this.testDataManager.createTestSettings(testData.settings);
            }

            if (testData.activities && testData.activities.length > 0) {
                console.log('  建立測試活動日誌');
                results.verifications.create_activities = 
                    await this.testDataManager.createTestActivityLogs(testData.activities);
            }

            // 2. 驗證當前狀態
            console.log('  驗證當前系統狀態');
            results.verifications.currentState = await this.mysqlVerifier.createStateSnapshot(
                `pre_reset_${componentName}`,
                {
                    includeUsers: true,
                    includeSettings: componentName.includes('Settings'),
                    includeActivityLogs: true,
                    includePermissions: componentName.includes('Permission')
                }
            );

            // 3. 分析相關日誌
            console.log('  分析相關日誌');
            results.verifications.logAnalysis = await this.logAnalyzer.analyzeLivewireComponentPerformance(componentName);

            results.success = true;
            console.log(`✅ ${componentName} 重置前狀態驗證完成`);

        } catch (error) {
            results.success = false;
            results.error = error.message;
            console.error(`❌ ${componentName} 重置前狀態驗證失敗: ${error.message}`);
        }

        this.verificationResults.push({
            type: 'pre_reset_verification',
            timestamp: new Date(),
            result: results
        });

        return results;
    }

    /**
     * 執行表單重置後的狀態驗證
     * @param {string} componentName - 元件名稱
     * @param {Object} expectedState - 預期狀態
     * @returns {Promise<Object>} - 驗證結果
     */
    async verifyPostResetState(componentName, expectedState = {}) {
        console.log(`🔍 驗證 ${componentName} 重置後狀態`);

        const results = {
            componentName,
            timestamp: new Date(),
            expectedState,
            verifications: {}
        };

        try {
            // 1. 建立重置後狀態快照
            console.log('  建立重置後狀態快照');
            results.verifications.postResetSnapshot = await this.mysqlVerifier.createStateSnapshot(
                `post_reset_${componentName}`,
                {
                    includeUsers: true,
                    includeSettings: componentName.includes('Settings'),
                    includeActivityLogs: true,
                    includePermissions: componentName.includes('Permission')
                }
            );

            // 2. 分析重置事件
            console.log('  分析重置事件');
            results.verifications.resetEvents = await this.logAnalyzer.analyzeFormResetEvents({
                componentName,
                dateFrom: new Date(Date.now() - 5 * 60 * 1000).toISOString() // 最近 5 分鐘
            });

            // 3. 驗證特定狀態（根據元件類型）
            if (componentName.includes('User')) {
                console.log('  驗證使用者相關狀態');
                results.verifications.userState = await this.mysqlVerifier.verifyUserState('admin');
            }

            if (componentName.includes('Settings')) {
                console.log('  驗證設定相關狀態');
                results.verifications.settingsState = await this.mysqlVerifier.verifySystemSettingsState();
            }

            if (componentName.includes('Activity')) {
                console.log('  驗證活動日誌狀態');
                results.verifications.activityState = await this.mysqlVerifier.verifyActivityLogState({
                    limit: 20
                });
            }

            // 4. 檢查資料完整性
            console.log('  檢查資料完整性');
            results.verifications.integrityCheck = await this.mysqlVerifier.verifyDataIntegrity();

            results.success = true;
            console.log(`✅ ${componentName} 重置後狀態驗證完成`);

        } catch (error) {
            results.success = false;
            results.error = error.message;
            console.error(`❌ ${componentName} 重置後狀態驗證失敗: ${error.message}`);
        }

        this.verificationResults.push({
            type: 'post_reset_verification',
            timestamp: new Date(),
            result: results
        });

        return results;
    }

    /**
     * 比較重置前後的狀態
     * @param {Object} preResetResult - 重置前結果
     * @param {Object} postResetResult - 重置後結果
     * @returns {Object} - 比較結果
     */
    compareResetStates(preResetResult, postResetResult) {
        console.log(`🔄 比較 ${preResetResult.componentName} 重置前後狀態`);

        const comparison = {
            componentName: preResetResult.componentName,
            timestamp: new Date(),
            preResetResult,
            postResetResult,
            differences: [],
            summary: {
                stateChanged: false,
                dataIntegrityMaintained: true,
                resetEventsDetected: false,
                unexpectedChanges: []
            }
        };

        try {
            // 比較狀態快照
            if (preResetResult.verifications.currentState && postResetResult.verifications.postResetSnapshot) {
                const stateComparison = this.mysqlVerifier.compareStateSnapshots(
                    preResetResult.verifications.currentState,
                    postResetResult.verifications.postResetSnapshot
                );
                comparison.stateComparison = stateComparison;
            }

            // 檢查是否有重置事件
            if (postResetResult.verifications.resetEvents) {
                const resetEventQueries = postResetResult.verifications.resetEvents.queries;
                if (resetEventQueries.reset_events && resetEventQueries.reset_events.success) {
                    comparison.summary.resetEventsDetected = true;
                }
            }

            // 檢查資料完整性
            const preIntegrity = preResetResult.verifications.currentState;
            const postIntegrity = postResetResult.verifications.integrityCheck;
            
            if (preIntegrity && postIntegrity) {
                comparison.summary.dataIntegrityMaintained = 
                    preIntegrity.success && postIntegrity.success;
            }

            comparison.success = true;
            console.log(`✅ ${preResetResult.componentName} 狀態比較完成`);

        } catch (error) {
            comparison.success = false;
            comparison.error = error.message;
            console.error(`❌ ${preResetResult.componentName} 狀態比較失敗: ${error.message}`);
        }

        this.verificationResults.push({
            type: 'state_comparison',
            timestamp: new Date(),
            result: comparison
        });

        return comparison;
    }

    /**
     * 執行完整的元件重置驗證流程
     * @param {string} componentName - 元件名稱
     * @param {Object} testConfig - 測試配置
     * @returns {Promise<Object>} - 完整驗證結果
     */
    async executeFullResetVerification(componentName, testConfig = {}) {
        console.log(`🧪 執行 ${componentName} 完整重置驗證流程`);

        const fullVerification = {
            componentName,
            sessionId: this.testSession.id,
            timestamp: new Date(),
            config: testConfig,
            steps: {}
        };

        try {
            // 1. 重置前驗證
            console.log('  步驟 1: 重置前驗證');
            fullVerification.steps.preReset = await this.verifyPreResetState(
                componentName, 
                testConfig.testData || {}
            );

            // 2. 等待前端執行重置操作
            console.log('  步驟 2: 等待前端重置操作完成');
            // 這裡應該與前端測試協調，等待重置操作完成
            await this.waitForResetOperation(testConfig.waitTime || 3000);

            // 3. 重置後驗證
            console.log('  步驟 3: 重置後驗證');
            fullVerification.steps.postReset = await this.verifyPostResetState(
                componentName,
                testConfig.expectedState || {}
            );

            // 4. 狀態比較
            console.log('  步驟 4: 狀態比較');
            fullVerification.steps.comparison = this.compareResetStates(
                fullVerification.steps.preReset,
                fullVerification.steps.postReset
            );

            // 5. 生成驗證報告
            console.log('  步驟 5: 生成驗證報告');
            fullVerification.steps.report = this.generateComponentVerificationReport(fullVerification);

            fullVerification.success = 
                fullVerification.steps.preReset.success &&
                fullVerification.steps.postReset.success &&
                fullVerification.steps.comparison.success;

            console.log(`${fullVerification.success ? '✅' : '❌'} ${componentName} 完整重置驗證${fullVerification.success ? '成功' : '失敗'}`);

        } catch (error) {
            fullVerification.success = false;
            fullVerification.error = error.message;
            console.error(`❌ ${componentName} 完整重置驗證失敗: ${error.message}`);
        }

        this.verificationResults.push({
            type: 'full_reset_verification',
            timestamp: new Date(),
            result: fullVerification
        });

        return fullVerification;
    }

    /**
     * 等待重置操作完成
     * @param {number} waitTime - 等待時間（毫秒）
     */
    async waitForResetOperation(waitTime) {
        console.log(`⏳ 等待重置操作完成 (${waitTime}ms)`);
        return new Promise(resolve => setTimeout(resolve, waitTime));
    }

    /**
     * 生成元件驗證報告
     * @param {Object} verificationData - 驗證資料
     * @returns {Object} - 驗證報告
     */
    generateComponentVerificationReport(verificationData) {
        const report = {
            componentName: verificationData.componentName,
            sessionId: verificationData.sessionId,
            timestamp: new Date(),
            summary: {
                overallSuccess: verificationData.success,
                preResetSuccess: verificationData.steps.preReset?.success || false,
                postResetSuccess: verificationData.steps.postReset?.success || false,
                comparisonSuccess: verificationData.steps.comparison?.success || false,
                resetEventsDetected: verificationData.steps.comparison?.summary?.resetEventsDetected || false,
                dataIntegrityMaintained: verificationData.steps.comparison?.summary?.dataIntegrityMaintained || false
            },
            details: verificationData.steps,
            recommendations: []
        };

        // 生成建議
        if (!report.summary.overallSuccess) {
            report.recommendations.push({
                title: '驗證失敗',
                description: '需要檢查元件重置功能的實作',
                priority: 'high'
            });
        }

        if (!report.summary.resetEventsDetected) {
            report.recommendations.push({
                title: '重置事件未檢測到',
                description: '可能需要加強重置操作的日誌記錄',
                priority: 'medium'
            });
        }

        if (!report.summary.dataIntegrityMaintained) {
            report.recommendations.push({
                title: '資料完整性問題',
                description: '重置操作可能影響了資料完整性，需要檢查',
                priority: 'high'
            });
        }

        console.log(`📊 ${verificationData.componentName} 驗證報告生成完成`);
        return report;
    }

    /**
     * 清理測試環境
     * @returns {Promise<Object>} - 清理結果
     */
    async cleanupTestEnvironment() {
        console.log('🧹 清理後端測試環境');

        const results = {
            sessionId: this.testSession.id,
            timestamp: new Date(),
            steps: {}
        };

        try {
            // 1. 清理測試資料
            console.log('  1. 清理測試資料');
            results.steps.cleanupTestData = await this.testDataManager.cleanupTestData();

            // 2. 清理分析結果
            console.log('  2. 清理分析結果');
            this.logAnalyzer.clearAnalysisResults();
            this.mysqlVerifier.clearQueryHistory();

            // 3. 生成最終報告
            console.log('  3. 生成最終報告');
            results.steps.finalReport = this.generateFinalVerificationReport();

            results.success = true;
            console.log('✅ 後端測試環境清理完成');

        } catch (error) {
            results.success = false;
            results.error = error.message;
            console.error(`❌ 後端測試環境清理失敗: ${error.message}`);
        }

        // 結束測試會話
        this.testSession.endTime = new Date();
        this.testSession.status = 'completed';

        return results;
    }

    /**
     * 生成最終驗證報告
     * @returns {Object} - 最終報告
     */
    generateFinalVerificationReport() {
        const report = {
            sessionId: this.testSession.id,
            startTime: this.testSession.startTime,
            endTime: new Date(),
            duration: new Date() - this.testSession.startTime,
            totalVerifications: this.verificationResults.length,
            verificationTypes: {},
            summary: {
                successfulVerifications: 0,
                failedVerifications: 0,
                successRate: 0
            },
            verificationResults: this.verificationResults
        };

        // 統計驗證類型
        this.verificationResults.forEach(verification => {
            const type = verification.type;
            if (!report.verificationTypes[type]) {
                report.verificationTypes[type] = 0;
            }
            report.verificationTypes[type]++;

            if (verification.result.success) {
                report.summary.successfulVerifications++;
            } else {
                report.summary.failedVerifications++;
            }
        });

        report.summary.successRate = report.totalVerifications > 0 
            ? (report.summary.successfulVerifications / report.totalVerifications * 100).toFixed(2)
            : 0;

        console.log('\n=== 後端驗證最終報告 ===');
        console.log(`會話 ID: ${report.sessionId}`);
        console.log(`執行時間: ${Math.round(report.duration / 1000)} 秒`);
        console.log(`總驗證數: ${report.totalVerifications}`);
        console.log(`成功驗證: ${report.summary.successfulVerifications}`);
        console.log(`失敗驗證: ${report.summary.failedVerifications}`);
        console.log(`成功率: ${report.summary.successRate}%`);

        console.log('\n=== 驗證類型統計 ===');
        Object.entries(report.verificationTypes).forEach(([type, count]) => {
            console.log(`${type}: ${count}`);
        });

        return report;
    }

    /**
     * 生成 MCP 執行腳本
     * @returns {string} - MCP 腳本
     */
    generateMCPExecutionScript() {
        const script = this.mysqlVerifier.generateMCPQueryScript();
        
        console.log('📝 MCP 執行腳本已生成');
        console.log('請將此腳本在 MCP 環境中執行以獲得實際的資料庫查詢結果');
        
        return script;
    }
}

module.exports = BackendVerificationSuite;