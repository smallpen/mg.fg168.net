/**
 * Livewire 表單重置功能測試套件
 * 提供標準化的測試框架和輔助函數
 */

class FormResetTestSuite {
    constructor(page) {
        this.page = page;
        this.screenshots = [];
        this.testResults = [];
        this.baseUrl = 'http://localhost';
    }

    /**
     * Livewire 登入輔助函數
     * @param {string} username - 使用者名稱
     * @param {string} password - 密碼
     * @returns {Promise<boolean>} - 登入是否成功
     */
    async livewireLogin(username = 'admin', password = 'password123') {
        console.log(`🔐 開始 Livewire 登入: ${username}`);
        
        try {
            // 導航到登入頁面
            await this.page.goto(`${this.baseUrl}/admin/login`);
            
            // 等待 Livewire 載入
            await this.page.evaluate(`
                new Promise((resolve) => {
                    const checkReady = () => {
                        if (window.Livewire && 
                            document.getElementById('username') && 
                            document.getElementById('password')) {
                            resolve('ready');
                        } else {
                            setTimeout(checkReady, 100);
                        }
                    };
                    checkReady();
                })
            `);
            
            // 填寫表單
            await this.page.evaluate(`
                const usernameField = document.getElementById('username');
                usernameField.value = '${username}';
                usernameField.dispatchEvent(new Event('input', { bubbles: true }));
                usernameField.blur();
                
                const passwordField = document.getElementById('password');
                passwordField.value = '${password}';
                passwordField.dispatchEvent(new Event('input', { bubbles: true }));
                passwordField.blur();
            `);
            
            // 等待同步
            await this.page.evaluate('new Promise(resolve => setTimeout(resolve, 800))');
            
            // 提交表單
            await this.page.click('button[type="submit"]');
            
            // 等待重定向
            const result = await this.page.evaluate(`
                new Promise((resolve) => {
                    let attempts = 0;
                    const checkLogin = () => {
                        attempts++;
                        if (window.location.href.includes('/admin/dashboard')) {
                            resolve('success');
                        } else if (attempts > 15) {
                            resolve('timeout');
                        } else {
                            setTimeout(checkLogin, 400);
                        }
                    };
                    setTimeout(checkLogin, 1000);
                })
            `);
            
            const success = result === 'success';
            console.log(success ? '✅ 登入成功' : '❌ 登入失敗');
            return success;
            
        } catch (error) {
            console.error('登入錯誤:', error.message);
            return false;
        }
    }

    /**
     * 等待 Livewire 元件載入完成
     * @param {string} componentSelector - 元件選擇器
     * @param {number} timeout - 超時時間（毫秒）
     */
    async waitForLivewireComponent(componentSelector, timeout = 10000) {
        console.log(`⏳ 等待 Livewire 元件載入: ${componentSelector}`);
        
        await this.page.waitForSelector(componentSelector, { timeout });
        
        // 等待 Livewire 完全初始化
        await this.page.evaluate(`
            new Promise((resolve) => {
                const checkLivewire = () => {
                    if (window.Livewire && window.Livewire.all().length > 0) {
                        resolve('ready');
                    } else {
                        setTimeout(checkLivewire, 100);
                    }
                };
                checkLivewire();
            })
        `);
        
        console.log('✅ Livewire 元件載入完成');
    }

    /**
     * 填寫表單欄位並觸發 Livewire 事件
     * @param {Object} fields - 欄位對象 {selector: value}
     * @param {boolean} useDefer - 是否使用 defer 模式
     */
    async fillLivewireForm(fields, useDefer = true) {
        console.log('📝 填寫 Livewire 表單欄位');
        
        for (const [selector, value] of Object.entries(fields)) {
            console.log(`  填寫 ${selector}: ${value}`);
            
            if (useDefer) {
                // 使用 wire:model.defer 的填寫方式
                await this.page.evaluate(`
                    const field = document.querySelector('${selector}');
                    if (field) {
                        field.value = '${value}';
                        field.dispatchEvent(new Event('input', { bubbles: true }));
                        field.blur();
                    }
                `);
            } else {
                // 使用標準填寫方式
                await this.page.fill(selector, value);
            }
        }
        
        // 等待 Livewire 同步
        await this.page.evaluate('new Promise(resolve => setTimeout(resolve, 800))');
        console.log('✅ 表單填寫完成');
    }

    /**
     * 驗證表單欄位值
     * @param {Object} expectedValues - 預期值對象 {selector: expectedValue}
     * @returns {Object} - 驗證結果
     */
    async validateFormFields(expectedValues) {
        console.log('🔍 驗證表單欄位值');
        
        const results = {};
        
        for (const [selector, expectedValue] of Object.entries(expectedValues)) {
            try {
                const actualValue = await this.page.inputValue(selector);
                const isValid = actualValue === expectedValue;
                
                results[selector] = {
                    expected: expectedValue,
                    actual: actualValue,
                    valid: isValid
                };
                
                console.log(`  ${selector}: ${isValid ? '✅' : '❌'} 預期="${expectedValue}" 實際="${actualValue}"`);
            } catch (error) {
                results[selector] = {
                    expected: expectedValue,
                    actual: null,
                    valid: false,
                    error: error.message
                };
                console.log(`  ${selector}: ❌ 錯誤 - ${error.message}`);
            }
        }
        
        return results;
    }

    /**
     * 執行表單重置並驗證
     * @param {string} resetButtonSelector - 重置按鈕選擇器
     * @param {Object} expectedResetValues - 重置後的預期值
     * @param {number} waitTime - 等待時間（毫秒）
     * @returns {Object} - 重置測試結果
     */
    async executeFormReset(resetButtonSelector, expectedResetValues, waitTime = 1500) {
        console.log(`🔄 執行表單重置: ${resetButtonSelector}`);
        
        // 截圖：重置前
        await this.takeScreenshot('before-reset');
        
        // 點擊重置按鈕
        await this.page.click(resetButtonSelector);
        
        // 等待重置完成
        await this.page.evaluate(`new Promise(resolve => setTimeout(resolve, ${waitTime}))`);
        
        // 截圖：重置後
        await this.takeScreenshot('after-reset');
        
        // 驗證重置結果
        const validationResults = await this.validateFormFields(expectedResetValues);
        
        // 檢查是否有成功訊息
        const successMessage = await this.checkSuccessMessage();
        
        const resetResult = {
            resetExecuted: true,
            validationResults,
            successMessage,
            allFieldsReset: Object.values(validationResults).every(result => result.valid)
        };
        
        console.log(`${resetResult.allFieldsReset ? '✅' : '❌'} 表單重置${resetResult.allFieldsReset ? '成功' : '失敗'}`);
        
        return resetResult;
    }

    /**
     * 檢查成功訊息
     * @returns {Object} - 成功訊息資訊
     */
    async checkSuccessMessage() {
        console.log('📢 檢查成功訊息');
        
        const messageSelectors = [
            '.toast',
            '.alert',
            '[data-toast]',
            '.notification',
            '.flash-message',
            '.success-message'
        ];
        
        for (const selector of messageSelectors) {
            try {
                await this.page.waitForSelector(selector, { timeout: 3000 });
                const messageText = await this.page.textContent(selector);
                
                console.log(`✅ 找到成功訊息: ${messageText}`);
                return {
                    found: true,
                    selector,
                    text: messageText
                };
            } catch (error) {
                // 繼續嘗試下一個選擇器
            }
        }
        
        console.log('ℹ️  未找到成功訊息');
        return {
            found: false,
            selector: null,
            text: null
        };
    }

    /**
     * 截圖功能
     * @param {string} name - 截圖名稱
     * @param {Object} options - 截圖選項
     */
    async takeScreenshot(name, options = {}) {
        const timestamp = new Date().toISOString().replace(/[:.]/g, '-');
        const filename = `${name}-${timestamp}.png`;
        
        await this.page.screenshot({
            path: `tests/screenshots/${filename}`,
            fullPage: true,
            ...options
        });
        
        this.screenshots.push({
            name,
            filename,
            timestamp: new Date(),
            url: this.page.url()
        });
        
        console.log(`📸 截圖已儲存: ${filename}`);
    }

    /**
     * 驗證前後端狀態同步
     * @param {string} statusSelector - 狀態顯示選擇器
     * @param {Array} expectedStatusTexts - 預期的狀態文字
     * @returns {Object} - 同步驗證結果
     */
    async validateFrontendBackendSync(statusSelector, expectedStatusTexts) {
        console.log('🔄 驗證前後端狀態同步');
        
        try {
            const statusText = await this.page.textContent(statusSelector);
            
            const syncResults = expectedStatusTexts.map(expectedText => {
                const found = statusText.includes(expectedText);
                console.log(`  ${found ? '✅' : '❌'} 檢查狀態文字: "${expectedText}"`);
                return {
                    expectedText,
                    found
                };
            });
            
            const allSynced = syncResults.every(result => result.found);
            
            return {
                synced: allSynced,
                statusText,
                results: syncResults
            };
        } catch (error) {
            console.log(`❌ 狀態同步驗證失敗: ${error.message}`);
            return {
                synced: false,
                error: error.message
            };
        }
    }

    /**
     * 測試響應式設計
     * @param {Array} viewports - 視窗大小陣列
     * @param {Function} testFunction - 測試函數
     */
    async testResponsiveDesign(viewports, testFunction) {
        console.log('📱 測試響應式設計');
        
        const results = [];
        
        for (const viewport of viewports) {
            console.log(`  測試視窗大小: ${viewport.width}x${viewport.height} (${viewport.name})`);
            
            await this.page.setViewportSize({ 
                width: viewport.width, 
                height: viewport.height 
            });
            
            // 重新載入頁面以觸發響應式佈局
            await this.page.reload();
            
            // 等待頁面載入
            await this.page.waitForLoadState('networkidle');
            
            try {
                const result = await testFunction(viewport);
                results.push({
                    viewport,
                    success: true,
                    result
                });
                console.log(`    ✅ ${viewport.name} 測試通過`);
            } catch (error) {
                results.push({
                    viewport,
                    success: false,
                    error: error.message
                });
                console.log(`    ❌ ${viewport.name} 測試失敗: ${error.message}`);
            }
        }
        
        return results;
    }

    /**
     * 檢查 Livewire 連接狀態
     * @returns {Object} - 連接狀態資訊
     */
    async checkLivewireConnection() {
        console.log('🔌 檢查 Livewire 連接狀態');
        
        const connectionInfo = await this.page.evaluate(`
            ({
                livewireLoaded: typeof window.Livewire !== 'undefined',
                componentsCount: window.Livewire ? window.Livewire.all().length : 0,
                components: window.Livewire ? window.Livewire.all().map(c => ({
                    id: c.id,
                    name: c.name,
                    fingerprint: c.fingerprint
                })) : []
            })
        `);
        
        console.log(`  Livewire 載入: ${connectionInfo.livewireLoaded ? '✅' : '❌'}`);
        console.log(`  元件數量: ${connectionInfo.componentsCount}`);
        
        return connectionInfo;
    }

    /**
     * 監控 AJAX 請求
     * @param {Function} actionFunction - 執行的動作函數
     * @returns {Object} - 請求監控結果
     */
    async monitorAjaxRequests(actionFunction) {
        console.log('📡 監控 AJAX 請求');
        
        const requests = [];
        
        // 監聽網路請求
        this.page.on('request', request => {
            if (request.url().includes('/livewire/')) {
                requests.push({
                    url: request.url(),
                    method: request.method(),
                    timestamp: new Date()
                });
            }
        });
        
        // 執行動作
        await actionFunction();
        
        // 等待請求完成
        await this.page.waitForLoadState('networkidle');
        
        console.log(`  捕獲到 ${requests.length} 個 Livewire 請求`);
        
        return {
            requestCount: requests.length,
            requests
        };
    }

    /**
     * 生成測試報告
     * @returns {Object} - 測試報告
     */
    generateTestReport() {
        return {
            testSuite: 'FormResetTestSuite',
            timestamp: new Date(),
            screenshots: this.screenshots,
            results: this.testResults,
            summary: {
                totalTests: this.testResults.length,
                passedTests: this.testResults.filter(r => r.success).length,
                failedTests: this.testResults.filter(r => !r.success).length
            }
        };
    }

    /**
     * 記錄測試結果
     * @param {string} testName - 測試名稱
     * @param {boolean} success - 是否成功
     * @param {Object} details - 詳細資訊
     */
    recordTestResult(testName, success, details = {}) {
        this.testResults.push({
            testName,
            success,
            timestamp: new Date(),
            details
        });
    }
}

module.exports = FormResetTestSuite;