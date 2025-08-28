/**
 * 測試資料管理器
 * 負責測試資料的準備、清理和管理
 */

class TestDataManager {
    constructor(mysqlVerifier) {
        this.mysqlVerifier = mysqlVerifier;
        this.testDataSets = new Map();
        this.cleanupTasks = [];
    }

    /**
     * 準備基礎測試資料
     * @returns {Promise<Object>} - 準備結果
     */
    async prepareBaseTestData() {
        console.log('🔧 準備基礎測試資料');

        const preparations = [
            {
                name: 'verify_admin_user',
                query: `SELECT id, username, name, email, is_active 
                        FROM users 
                        WHERE username = 'admin' AND is_active = 1`,
                description: '驗證管理員使用者存在且啟用',
                required: true
            },
            {
                name: 'verify_test_roles',
                query: `SELECT name, display_name 
                        FROM roles 
                        WHERE name IN ('super_admin', 'admin', 'user')`,
                description: '驗證基本角色存在',
                required: true
            },
            {
                name: 'verify_permissions',
                query: `SELECT COUNT(*) as permission_count 
                        FROM permissions`,
                description: '驗證權限系統已初始化',
                required: true,
                expectedMinCount: 30
            },
            {
                name: 'verify_admin_permissions',
                query: `SELECT COUNT(DISTINCT p.id) as permission_count
                        FROM users u
                        JOIN user_roles ur ON u.id = ur.user_id
                        JOIN roles r ON ur.role_id = r.id
                        JOIN role_permissions rp ON r.id = rp.role_id
                        JOIN permissions p ON rp.permission_id = p.id
                        WHERE u.username = 'admin'`,
                description: '驗證管理員擁有權限',
                required: true,
                expectedMinCount: 30
            }
        ];

        const results = {};
        for (const prep of preparations) {
            results[prep.name] = await this.mysqlVerifier.executeQuery(prep.query, prep.description);
            results[prep.name].required = prep.required;
            results[prep.name].expectedMinCount = prep.expectedMinCount;
        }

        return {
            timestamp: new Date(),
            preparations: results,
            success: true
        };
    }

    /**
     * 建立測試使用者
     * @param {Object} userData - 使用者資料
     * @returns {Promise<Object>} - 建立結果
     */
    async createTestUser(userData) {
        const {
            username,
            name,
            email,
            password = 'test123456',
            isActive = 1,
            roles = ['user']
        } = userData;

        console.log(`👤 建立測試使用者: ${username}`);

        const queries = [
            {
                name: 'create_user',
                query: `INSERT INTO users (username, name, email, password, is_active, created_at, updated_at)
                        VALUES ('${username}', '${name}', '${email}', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', ${isActive}, NOW(), NOW())`,
                description: `建立使用者 ${username}`
            },
            {
                name: 'get_user_id',
                query: `SELECT id FROM users WHERE username = '${username}'`,
                description: `取得使用者 ${username} 的 ID`
            }
        ];

        // 為每個角色建立關聯查詢
        roles.forEach(roleName => {
            queries.push({
                name: `assign_role_${roleName}`,
                query: `INSERT INTO user_roles (user_id, role_id, created_at, updated_at)
                        SELECT u.id, r.id, NOW(), NOW()
                        FROM users u, roles r
                        WHERE u.username = '${username}' AND r.name = '${roleName}'`,
                description: `為使用者 ${username} 指派角色 ${roleName}`
            });
        });

        const results = {};
        for (const queryInfo of queries) {
            results[queryInfo.name] = await this.mysqlVerifier.executeQuery(queryInfo.query, queryInfo.description);
        }

        // 記錄清理任務
        this.cleanupTasks.push({
            type: 'delete_test_user',
            username,
            query: `DELETE FROM users WHERE username = '${username}'`
        });

        this.testDataSets.set(`user_${username}`, {
            type: 'user',
            username,
            data: userData,
            queries: results,
            createdAt: new Date()
        });

        return {
            username,
            queries: results,
            success: true
        };
    }

    /**
     * 建立測試設定
     * @param {Array} settings - 設定陣列
     * @returns {Promise<Object>} - 建立結果
     */
    async createTestSettings(settings) {
        console.log('⚙️  建立測試設定');

        const queries = [];
        const settingKeys = [];

        settings.forEach(setting => {
            const {
                keyName,
                value,
                type = 'string',
                category = 'test',
                isPublic = 0
            } = setting;

            settingKeys.push(keyName);

            queries.push({
                name: `create_setting_${keyName}`,
                query: `INSERT INTO system_settings (key_name, value, type, category, is_public, created_at, updated_at)
                        VALUES ('${keyName}', '${value}', '${type}', '${category}', ${isPublic}, NOW(), NOW())
                        ON DUPLICATE KEY UPDATE
                        value = '${value}', updated_at = NOW()`,
                description: `建立/更新設定 ${keyName}`
            });
        });

        const results = {};
        for (const queryInfo of queries) {
            results[queryInfo.name] = await this.mysqlVerifier.executeQuery(queryInfo.query, queryInfo.description);
        }

        // 記錄清理任務
        this.cleanupTasks.push({
            type: 'delete_test_settings',
            settingKeys,
            query: `DELETE FROM system_settings WHERE key_name IN ('${settingKeys.join("', '")}')`
        });

        this.testDataSets.set('test_settings', {
            type: 'settings',
            settingKeys,
            data: settings,
            queries: results,
            createdAt: new Date()
        });

        return {
            settingKeys,
            queries: results,
            success: true
        };
    }

    /**
     * 建立測試活動日誌
     * @param {Array} activities - 活動陣列
     * @returns {Promise<Object>} - 建立結果
     */
    async createTestActivityLogs(activities) {
        console.log('📊 建立測試活動日誌');

        const queries = [];

        activities.forEach((activity, index) => {
            const {
                userId = null,
                activityType,
                description,
                createdAt = 'NOW()'
            } = activity;

            const userIdClause = userId ? userId : `(SELECT id FROM users WHERE username = 'admin')`;

            queries.push({
                name: `create_activity_${index}`,
                query: `INSERT INTO activity_logs (user_id, activity_type, description, created_at, updated_at)
                        VALUES (${userIdClause}, '${activityType}', '${description}', ${createdAt}, NOW())`,
                description: `建立活動日誌: ${activityType}`
            });
        });

        const results = {};
        for (const queryInfo of queries) {
            results[queryInfo.name] = await this.mysqlVerifier.executeQuery(queryInfo.query, queryInfo.description);
        }

        // 記錄清理任務
        this.cleanupTasks.push({
            type: 'delete_test_activities',
            query: `DELETE FROM activity_logs WHERE description LIKE '%test%' OR description LIKE '%測試%'`
        });

        this.testDataSets.set('test_activities', {
            type: 'activities',
            data: activities,
            queries: results,
            createdAt: new Date()
        });

        return {
            activityCount: activities.length,
            queries: results,
            success: true
        };
    }

    /**
     * 建立測試權限和角色
     * @param {Object} permissionData - 權限資料
     * @returns {Promise<Object>} - 建立結果
     */
    async createTestPermissionsAndRoles(permissionData) {
        console.log('🔐 建立測試權限和角色');

        const { permissions = [], roles = [] } = permissionData;
        const queries = [];

        // 建立測試權限
        permissions.forEach(permission => {
            const { name, displayName, module = 'test' } = permission;
            queries.push({
                name: `create_permission_${name}`,
                query: `INSERT INTO permissions (name, display_name, module, created_at, updated_at)
                        VALUES ('${name}', '${displayName}', '${module}', NOW(), NOW())`,
                description: `建立權限 ${name}`
            });
        });

        // 建立測試角色
        roles.forEach(role => {
            const { name, displayName, permissionNames = [] } = role;
            
            queries.push({
                name: `create_role_${name}`,
                query: `INSERT INTO roles (name, display_name, created_at, updated_at)
                        VALUES ('${name}', '${displayName}', NOW(), NOW())`,
                description: `建立角色 ${name}`
            });

            // 為角色分配權限
            permissionNames.forEach(permissionName => {
                queries.push({
                    name: `assign_permission_${name}_${permissionName}`,
                    query: `INSERT INTO role_permissions (role_id, permission_id, created_at, updated_at)
                            SELECT r.id, p.id, NOW(), NOW()
                            FROM roles r, permissions p
                            WHERE r.name = '${name}' AND p.name = '${permissionName}'`,
                    description: `為角色 ${name} 分配權限 ${permissionName}`
                });
            });
        });

        const results = {};
        for (const queryInfo of queries) {
            results[queryInfo.name] = await this.mysqlVerifier.executeQuery(queryInfo.query, queryInfo.description);
        }

        // 記錄清理任務
        const permissionNames = permissions.map(p => p.name);
        const roleNames = roles.map(r => r.name);

        if (permissionNames.length > 0) {
            this.cleanupTasks.push({
                type: 'delete_test_permissions',
                permissionNames,
                query: `DELETE FROM permissions WHERE name IN ('${permissionNames.join("', '")}')`
            });
        }

        if (roleNames.length > 0) {
            this.cleanupTasks.push({
                type: 'delete_test_roles',
                roleNames,
                query: `DELETE FROM roles WHERE name IN ('${roleNames.join("', '")}')`
            });
        }

        this.testDataSets.set('test_permissions_roles', {
            type: 'permissions_roles',
            permissions,
            roles,
            queries: results,
            createdAt: new Date()
        });

        return {
            permissionCount: permissions.length,
            roleCount: roles.length,
            queries: results,
            success: true
        };
    }

    /**
     * 執行資料清理
     * @returns {Promise<Object>} - 清理結果
     */
    async cleanupTestData() {
        console.log('🧹 執行測試資料清理');

        const results = {};
        
        // 按相反順序執行清理（避免外鍵約束問題）
        const reversedTasks = [...this.cleanupTasks].reverse();

        for (const [index, task] of reversedTasks.entries()) {
            const taskName = `cleanup_${task.type}_${index}`;
            results[taskName] = await this.mysqlVerifier.executeQuery(
                task.query,
                `清理 ${task.type}`
            );
        }

        // 清理記錄
        this.cleanupTasks = [];
        this.testDataSets.clear();

        return {
            timestamp: new Date(),
            cleanupTasks: reversedTasks.length,
            results,
            success: true
        };
    }

    /**
     * 驗證測試資料完整性
     * @returns {Promise<Object>} - 驗證結果
     */
    async verifyTestDataIntegrity() {
        console.log('🔍 驗證測試資料完整性');

        const verifications = [];

        // 驗證每個測試資料集
        for (const [key, dataSet] of this.testDataSets.entries()) {
            let verificationQuery;
            
            switch (dataSet.type) {
                case 'user':
                    verificationQuery = {
                        query: `SELECT COUNT(*) as count FROM users WHERE username = '${dataSet.username}'`,
                        description: `驗證測試使用者 ${dataSet.username} 存在`,
                        expectedCount: 1
                    };
                    break;
                    
                case 'settings':
                    const settingKeys = dataSet.settingKeys.map(key => `'${key}'`).join(', ');
                    verificationQuery = {
                        query: `SELECT COUNT(*) as count FROM system_settings WHERE key_name IN (${settingKeys})`,
                        description: `驗證測試設定存在`,
                        expectedCount: dataSet.settingKeys.length
                    };
                    break;
                    
                case 'activities':
                    verificationQuery = {
                        query: `SELECT COUNT(*) as count FROM activity_logs WHERE description LIKE '%test%' OR description LIKE '%測試%'`,
                        description: `驗證測試活動日誌存在`,
                        expectedMinCount: 1
                    };
                    break;
                    
                case 'permissions_roles':
                    const permissionNames = dataSet.permissions.map(p => `'${p.name}'`).join(', ');
                    const roleNames = dataSet.roles.map(r => `'${r.name}'`).join(', ');
                    
                    if (permissionNames) {
                        verifications.push({
                            name: `verify_permissions_${key}`,
                            query: `SELECT COUNT(*) as count FROM permissions WHERE name IN (${permissionNames})`,
                            description: `驗證測試權限存在`,
                            expectedCount: dataSet.permissions.length
                        });
                    }
                    
                    if (roleNames) {
                        verificationQuery = {
                            query: `SELECT COUNT(*) as count FROM roles WHERE name IN (${roleNames})`,
                            description: `驗證測試角色存在`,
                            expectedCount: dataSet.roles.length
                        };
                    }
                    break;
            }

            if (verificationQuery) {
                verifications.push({
                    name: `verify_${key}`,
                    ...verificationQuery
                });
            }
        }

        const results = {};
        for (const verification of verifications) {
            results[verification.name] = await this.mysqlVerifier.executeQuery(
                verification.query,
                verification.description
            );
            results[verification.name].expectedCount = verification.expectedCount;
            results[verification.name].expectedMinCount = verification.expectedMinCount;
        }

        return {
            timestamp: new Date(),
            verifications: results,
            testDataSets: this.testDataSets.size,
            success: true
        };
    }

    /**
     * 重置測試環境
     * @returns {Promise<Object>} - 重置結果
     */
    async resetTestEnvironment() {
        console.log('🔄 重置測試環境');

        // 先清理現有測試資料
        const cleanupResult = await this.cleanupTestData();

        // 重新準備基礎測試資料
        const prepareResult = await this.prepareBaseTestData();

        return {
            timestamp: new Date(),
            cleanup: cleanupResult,
            prepare: prepareResult,
            success: cleanupResult.success && prepareResult.success
        };
    }

    /**
     * 生成測試資料報告
     * @returns {Object} - 測試資料報告
     */
    generateTestDataReport() {
        const report = {
            timestamp: new Date(),
            testDataSets: this.testDataSets.size,
            cleanupTasks: this.cleanupTasks.length,
            dataSets: [],
            cleanupQueue: this.cleanupTasks
        };

        for (const [key, dataSet] of this.testDataSets.entries()) {
            report.dataSets.push({
                key,
                type: dataSet.type,
                createdAt: dataSet.createdAt,
                dataSize: Object.keys(dataSet.data).length
            });
        }

        console.log('\n=== 測試資料管理報告 ===');
        console.log(`測試資料集數量: ${report.testDataSets}`);
        console.log(`待清理任務數量: ${report.cleanupTasks}`);
        
        report.dataSets.forEach(dataSet => {
            console.log(`  ${dataSet.key} (${dataSet.type}): ${dataSet.dataSize} 項目`);
        });

        return report;
    }
}

module.exports = TestDataManager;