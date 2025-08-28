/**
 * 後端日誌分析器
 * 分析 Laravel 日誌和 Livewire 事件日誌
 */

class LogAnalyzer {
    constructor(mysqlVerifier) {
        this.mysqlVerifier = mysqlVerifier;
        this.logPatterns = {
            livewire: {
                componentLoad: /Livewire component loaded: (.+)/,
                methodCall: /Livewire method called: (.+)\.(.+)/,
                propertyUpdate: /Livewire property updated: (.+)\.(.+) = (.+)/,
                eventDispatched: /Livewire event dispatched: (.+)/,
                refresh: /Livewire component refreshed: (.+)/
            },
            laravel: {
                query: /select \* from `(.+)` where/i,
                insert: /insert into `(.+)` \(/i,
                update: /update `(.+)` set/i,
                delete: /delete from `(.+)` where/i,
                error: /ERROR: (.+)/,
                warning: /WARNING: (.+)/
            },
            formReset: {
                resetCalled: /resetFilters\(\) called/,
                resetForm: /resetForm\(\) called/,
                clearFilters: /clearFilters\(\) called/,
                dispatchRefresh: /dispatch\('\$refresh'\) called/,
                customEvent: /dispatch\('(.+)-reset'\) called/
            }
        };
        this.analysisResults = [];
    }

    /**
     * 分析活動日誌中的表單重置事件
     * @param {Object} filters - 篩選條件
     * @returns {Promise<Object>} - 分析結果
     */
    async analyzeFormResetEvents(filters = {}) {
        console.log('📊 分析表單重置事件');

        const {
            dateFrom = null,
            dateTo = null,
            componentName = null,
            userId = null
        } = filters;

        let whereConditions = ["activity_type LIKE '%reset%' OR description LIKE '%reset%' OR description LIKE '%clear%'"];
        
        if (dateFrom) whereConditions.push(`created_at >= '${dateFrom}'`);
        if (dateTo) whereConditions.push(`created_at <= '${dateTo}'`);
        if (userId) whereConditions.push(`user_id = ${userId}`);
        if (componentName) whereConditions.push(`description LIKE '%${componentName}%'`);

        const whereClause = `WHERE ${whereConditions.join(' AND ')}`;

        const queries = [
            {
                name: 'reset_events',
                query: `SELECT al.id, al.activity_type, al.description, al.created_at,
                               u.username, u.name as user_name
                        FROM activity_logs al
                        LEFT JOIN users u ON al.user_id = u.id
                        ${whereClause}
                        ORDER BY al.created_at DESC
                        LIMIT 100`,
                description: '查詢表單重置相關事件'
            },
            {
                name: 'reset_event_types',
                query: `SELECT activity_type, COUNT(*) as count
                        FROM activity_logs
                        ${whereClause}
                        GROUP BY activity_type
                        ORDER BY count DESC`,
                description: '統計重置事件類型'
            },
            {
                name: 'reset_events_by_user',
                query: `SELECT u.username, u.name, COUNT(al.id) as reset_count
                        FROM activity_logs al
                        LEFT JOIN users u ON al.user_id = u.id
                        ${whereClause}
                        GROUP BY u.id, u.username, u.name
                        ORDER BY reset_count DESC`,
                description: '統計各使用者的重置操作次數'
            },
            {
                name: 'reset_events_timeline',
                query: `SELECT DATE(created_at) as date, 
                               HOUR(created_at) as hour,
                               COUNT(*) as count
                        FROM activity_logs
                        ${whereClause}
                        GROUP BY DATE(created_at), HOUR(created_at)
                        ORDER BY date DESC, hour DESC
                        LIMIT 48`,
                description: '重置事件時間線分析'
            }
        ];

        const results = {};
        for (const queryInfo of queries) {
            results[queryInfo.name] = await this.mysqlVerifier.executeQuery(queryInfo.query, queryInfo.description);
        }

        return {
            filters,
            timestamp: new Date(),
            queries: results,
            success: true
        };
    }

    /**
     * 分析 Livewire 元件效能
     * @param {string} componentName - 元件名稱
     * @returns {Promise<Object>} - 效能分析結果
     */
    async analyzeLivewireComponentPerformance(componentName) {
        console.log(`⚡ 分析 Livewire 元件效能: ${componentName}`);

        const queries = [
            {
                name: 'component_load_times',
                query: `SELECT description, created_at,
                               EXTRACT(EPOCH FROM (created_at - LAG(created_at) OVER (ORDER BY created_at))) as load_time_seconds
                        FROM activity_logs
                        WHERE description LIKE '%${componentName}%' 
                          AND (activity_type = 'component_load' OR description LIKE '%loaded%')
                        ORDER BY created_at DESC
                        LIMIT 50`,
                description: `${componentName} 元件載入時間分析`
            },
            {
                name: 'method_call_frequency',
                query: `SELECT 
                               SUBSTRING_INDEX(SUBSTRING_INDEX(description, '.', -1), '(', 1) as method_name,
                               COUNT(*) as call_count,
                               AVG(EXTRACT(EPOCH FROM (created_at - LAG(created_at) OVER (ORDER BY created_at)))) as avg_response_time
                        FROM activity_logs
                        WHERE description LIKE '%${componentName}%' 
                          AND (activity_type = 'method_call' OR description LIKE '%called%')
                        GROUP BY method_name
                        ORDER BY call_count DESC`,
                description: `${componentName} 方法呼叫頻率分析`
            },
            {
                name: 'error_rate',
                query: `SELECT DATE(created_at) as date,
                               COUNT(CASE WHEN activity_type = 'error' OR description LIKE '%error%' THEN 1 END) as error_count,
                               COUNT(*) as total_events,
                               (COUNT(CASE WHEN activity_type = 'error' OR description LIKE '%error%' THEN 1 END) * 100.0 / COUNT(*)) as error_rate
                        FROM activity_logs
                        WHERE description LIKE '%${componentName}%'
                        GROUP BY DATE(created_at)
                        ORDER BY date DESC
                        LIMIT 30`,
                description: `${componentName} 錯誤率分析`
            }
        ];

        const results = {};
        for (const queryInfo of queries) {
            results[queryInfo.name] = await this.mysqlVerifier.executeQuery(queryInfo.query, queryInfo.description);
        }

        return {
            componentName,
            timestamp: new Date(),
            queries: results,
            success: true
        };
    }

    /**
     * 分析資料庫查詢效能
     * @param {Object} filters - 篩選條件
     * @returns {Promise<Object>} - 查詢效能分析結果
     */
    async analyzeDatabaseQueryPerformance(filters = {}) {
        console.log('🗄️  分析資料庫查詢效能');

        const {
            dateFrom = null,
            dateTo = null,
            slowQueryThreshold = 1000 // 毫秒
        } = filters;

        let whereConditions = ["activity_type = 'database_query' OR description LIKE '%query%' OR description LIKE '%SELECT%'"];
        
        if (dateFrom) whereConditions.push(`created_at >= '${dateFrom}'`);
        if (dateTo) whereConditions.push(`created_at <= '${dateTo}'`);

        const whereClause = `WHERE ${whereConditions.join(' AND ')}`;

        const queries = [
            {
                name: 'slow_queries',
                query: `SELECT description, created_at,
                               EXTRACT(EPOCH FROM (created_at - LAG(created_at) OVER (ORDER BY created_at))) * 1000 as execution_time_ms
                        FROM activity_logs
                        ${whereClause}
                        HAVING execution_time_ms > ${slowQueryThreshold}
                        ORDER BY execution_time_ms DESC
                        LIMIT 20`,
                description: `慢查詢分析 (>${slowQueryThreshold}ms)`
            },
            {
                name: 'query_types',
                query: `SELECT 
                               CASE 
                                   WHEN description LIKE '%SELECT%' THEN 'SELECT'
                                   WHEN description LIKE '%INSERT%' THEN 'INSERT'
                                   WHEN description LIKE '%UPDATE%' THEN 'UPDATE'
                                   WHEN description LIKE '%DELETE%' THEN 'DELETE'
                                   ELSE 'OTHER'
                               END as query_type,
                               COUNT(*) as count,
                               AVG(EXTRACT(EPOCH FROM (created_at - LAG(created_at) OVER (ORDER BY created_at))) * 1000) as avg_time_ms
                        FROM activity_logs
                        ${whereClause}
                        GROUP BY query_type
                        ORDER BY count DESC`,
                description: '查詢類型統計'
            },
            {
                name: 'table_access_frequency',
                query: `SELECT 
                               REGEXP_REPLACE(description, '.*FROM `([^`]+)`.*', '\\1') as table_name,
                               COUNT(*) as access_count
                        FROM activity_logs
                        ${whereClause}
                        AND description LIKE '%FROM %'
                        GROUP BY table_name
                        ORDER BY access_count DESC
                        LIMIT 20`,
                description: '資料表存取頻率'
            }
        ];

        const results = {};
        for (const queryInfo of queries) {
            results[queryInfo.name] = await this.mysqlVerifier.executeQuery(queryInfo.query, queryInfo.description);
        }

        return {
            filters,
            timestamp: new Date(),
            queries: results,
            success: true
        };
    }

    /**
     * 分析使用者行為模式
     * @param {string} username - 使用者名稱
     * @returns {Promise<Object>} - 行為分析結果
     */
    async analyzeUserBehaviorPatterns(username) {
        console.log(`👤 分析使用者行為模式: ${username}`);

        const queries = [
            {
                name: 'activity_timeline',
                query: `SELECT activity_type, description, created_at
                        FROM activity_logs al
                        JOIN users u ON al.user_id = u.id
                        WHERE u.username = '${username}'
                        ORDER BY created_at DESC
                        LIMIT 100`,
                description: `${username} 的活動時間線`
            },
            {
                name: 'activity_patterns',
                query: `SELECT activity_type, 
                               COUNT(*) as count,
                               DATE(MIN(created_at)) as first_occurrence,
                               DATE(MAX(created_at)) as last_occurrence
                        FROM activity_logs al
                        JOIN users u ON al.user_id = u.id
                        WHERE u.username = '${username}'
                        GROUP BY activity_type
                        ORDER BY count DESC`,
                description: `${username} 的活動模式統計`
            },
            {
                name: 'hourly_activity',
                query: `SELECT HOUR(created_at) as hour,
                               COUNT(*) as activity_count
                        FROM activity_logs al
                        JOIN users u ON al.user_id = u.id
                        WHERE u.username = '${username}'
                        GROUP BY HOUR(created_at)
                        ORDER BY hour`,
                description: `${username} 的每小時活動分佈`
            },
            {
                name: 'form_reset_behavior',
                query: `SELECT DATE(created_at) as date,
                               COUNT(CASE WHEN description LIKE '%reset%' OR description LIKE '%clear%' THEN 1 END) as reset_count,
                               COUNT(*) as total_actions,
                               (COUNT(CASE WHEN description LIKE '%reset%' OR description LIKE '%clear%' THEN 1 END) * 100.0 / COUNT(*)) as reset_ratio
                        FROM activity_logs al
                        JOIN users u ON al.user_id = u.id
                        WHERE u.username = '${username}'
                        GROUP BY DATE(created_at)
                        ORDER BY date DESC
                        LIMIT 30`,
                description: `${username} 的表單重置行為分析`
            }
        ];

        const results = {};
        for (const queryInfo of queries) {
            results[queryInfo.name] = await this.mysqlVerifier.executeQuery(queryInfo.query, queryInfo.description);
        }

        return {
            username,
            timestamp: new Date(),
            queries: results,
            success: true
        };
    }

    /**
     * 分析系統錯誤和異常
     * @param {Object} filters - 篩選條件
     * @returns {Promise<Object>} - 錯誤分析結果
     */
    async analyzeSystemErrorsAndExceptions(filters = {}) {
        console.log('🚨 分析系統錯誤和異常');

        const {
            dateFrom = null,
            dateTo = null,
            severity = null
        } = filters;

        let whereConditions = ["activity_type IN ('error', 'exception', 'warning') OR description LIKE '%error%' OR description LIKE '%exception%' OR description LIKE '%warning%'"];
        
        if (dateFrom) whereConditions.push(`created_at >= '${dateFrom}'`);
        if (dateTo) whereConditions.push(`created_at <= '${dateTo}'`);
        if (severity) whereConditions.push(`activity_type = '${severity}'`);

        const whereClause = `WHERE ${whereConditions.join(' AND ')}`;

        const queries = [
            {
                name: 'error_summary',
                query: `SELECT activity_type, COUNT(*) as count
                        FROM activity_logs
                        ${whereClause}
                        GROUP BY activity_type
                        ORDER BY count DESC`,
                description: '錯誤類型摘要'
            },
            {
                name: 'recent_errors',
                query: `SELECT al.activity_type, al.description, al.created_at,
                               u.username, u.name as user_name
                        FROM activity_logs al
                        LEFT JOIN users u ON al.user_id = u.id
                        ${whereClause}
                        ORDER BY al.created_at DESC
                        LIMIT 50`,
                description: '最近的錯誤記錄'
            },
            {
                name: 'error_frequency',
                query: `SELECT DATE(created_at) as date,
                               HOUR(created_at) as hour,
                               COUNT(*) as error_count
                        FROM activity_logs
                        ${whereClause}
                        GROUP BY DATE(created_at), HOUR(created_at)
                        ORDER BY date DESC, hour DESC
                        LIMIT 72`,
                description: '錯誤頻率分析'
            },
            {
                name: 'error_by_component',
                query: `SELECT 
                               CASE 
                                   WHEN description LIKE '%UserList%' THEN 'UserList'
                                   WHEN description LIKE '%ActivityExport%' THEN 'ActivityExport'
                                   WHEN description LIKE '%PermissionAuditLog%' THEN 'PermissionAuditLog'
                                   WHEN description LIKE '%SettingsList%' THEN 'SettingsList'
                                   WHEN description LIKE '%NotificationList%' THEN 'NotificationList'
                                   ELSE 'Other'
                               END as component,
                               COUNT(*) as error_count
                        FROM activity_logs
                        ${whereClause}
                        GROUP BY component
                        ORDER BY error_count DESC`,
                description: '各元件錯誤統計'
            }
        ];

        const results = {};
        for (const queryInfo of queries) {
            results[queryInfo.name] = await this.mysqlVerifier.executeQuery(queryInfo.query, queryInfo.description);
        }

        return {
            filters,
            timestamp: new Date(),
            queries: results,
            success: true
        };
    }

    /**
     * 生成日誌分析報告
     * @param {Object} analysisData - 分析資料
     * @returns {Object} - 分析報告
     */
    generateLogAnalysisReport(analysisData) {
        const report = {
            timestamp: new Date(),
            analysisType: 'LogAnalysis',
            summary: {
                totalAnalyses: this.analysisResults.length,
                successfulAnalyses: this.analysisResults.filter(r => r.success).length,
                failedAnalyses: this.analysisResults.filter(r => !r.success).length
            },
            analyses: this.analysisResults,
            recommendations: []
        };

        // 基於分析結果生成建議
        if (analysisData) {
            report.recommendations = this.generateRecommendations(analysisData);
        }

        console.log('\n=== 日誌分析報告 ===');
        console.log(`總分析數: ${report.summary.totalAnalyses}`);
        console.log(`成功分析: ${report.summary.successfulAnalyses}`);
        console.log(`失敗分析: ${report.summary.failedAnalyses}`);

        if (report.recommendations.length > 0) {
            console.log('\n=== 建議 ===');
            report.recommendations.forEach((rec, index) => {
                console.log(`${index + 1}. ${rec.title}: ${rec.description}`);
            });
        }

        return report;
    }

    /**
     * 基於分析結果生成建議
     * @param {Object} analysisData - 分析資料
     * @returns {Array} - 建議陣列
     */
    generateRecommendations(analysisData) {
        const recommendations = [];

        // 這裡可以根據實際的分析結果生成具體建議
        // 例如：如果發現某個元件的重置操作頻率過高，建議優化 UX
        // 如果發現某些查詢過慢，建議優化資料庫索引等

        recommendations.push({
            title: '效能優化',
            description: '建議對頻繁使用的表單重置功能進行效能優化',
            priority: 'medium',
            category: 'performance'
        });

        recommendations.push({
            title: '錯誤監控',
            description: '建議加強對 Livewire 元件錯誤的監控和日誌記錄',
            priority: 'high',
            category: 'monitoring'
        });

        return recommendations;
    }

    /**
     * 記錄分析結果
     * @param {string} analysisType - 分析類型
     * @param {Object} result - 分析結果
     */
    recordAnalysisResult(analysisType, result) {
        this.analysisResults.push({
            type: analysisType,
            timestamp: new Date(),
            result,
            success: result.success || false
        });
    }

    /**
     * 清理分析結果
     */
    clearAnalysisResults() {
        this.analysisResults = [];
        console.log('🧹 分析結果已清理');
    }
}

module.exports = LogAnalyzer;