#!/usr/bin/env node

/**
 * WSL Playwright 測試腳本
 * 替代 MCP Playwright server 的簡單解決方案
 */

const { chromium } = require('playwright');

class WebTester {
    constructor() {
        this.browser = null;
        this.page = null;
    }

    async init() {
        console.log('🚀 啟動瀏覽器...');
        this.browser = await chromium.launch({ 
            headless: false,
            args: ['--no-sandbox', '--disable-setuid-sandbox']
        });
        this.page = await this.browser.newPage();
        console.log('✅ 瀏覽器已啟動');
    }

    async navigate(url) {
        console.log(`🔗 導航到: ${url}`);
        await this.page.goto(url);
        console.log('✅ 頁面載入完成');
    }

    async screenshot(name = 'screenshot') {
        const filename = `${name}-${Date.now()}.png`;
        await this.page.screenshot({ path: filename });
        console.log(`📸 截圖已儲存: ${filename}`);
        return filename;
    }

    async fill(selector, value) {
        console.log(`✏️  填寫 ${selector}: ${value}`);
        await this.page.fill(selector, value);
    }

    async click(selector) {
        console.log(`👆 點擊: ${selector}`);
        await this.page.click(selector);
    }

    async getText() {
        const text = await this.page.textContent('body');
        return text;
    }

    async waitForURL(pattern) {
        console.log(`⏳ 等待 URL 變更: ${pattern}`);
        await this.page.waitForURL(pattern);
        console.log('✅ URL 變更完成');
    }

    async close() {
        if (this.browser) {
            await this.browser.close();
            console.log('🔒 瀏覽器已關閉');
        }
    }

    // 測試登入流程
    async testLogin(username = 'admin', password = 'admin123') {
        try {
            console.log('\n🔐 開始登入測試...');
            
            await this.navigate('http://localhost/admin/login');
            await this.screenshot('login-page');
            
            await this.fill('input[name="username"]', username);
            await this.fill('input[name="password"]', password);
            
            await this.click('button[type="submit"]');
            
            // 等待重定向到儀表板
            await this.waitForURL('**/admin/dashboard');
            await this.screenshot('dashboard');
            
            console.log('✅ 登入測試成功！');
            return true;
            
        } catch (error) {
            console.log(`❌ 登入測試失敗: ${error.message}`);
            await this.screenshot('login-error');
            return false;
        }
    }

    // 測試使用者列表頁面
    async testUserList() {
        try {
            console.log('\n👥 測試使用者列表頁面...');
            
            await this.navigate('http://localhost/admin/users');
            await this.screenshot('user-list');
            
            const pageText = await this.getText();
            if (pageText.includes('使用者管理') || pageText.includes('Users')) {
                console.log('✅ 使用者列表頁面載入成功');
                return true;
            } else {
                console.log('❌ 使用者列表頁面內容不正確');
                return false;
            }
            
        } catch (error) {
            console.log(`❌ 使用者列表測試失敗: ${error.message}`);
            await this.screenshot('user-list-error');
            return false;
        }
    }
}

// 主要測試函數
async function runTests() {
    const tester = new WebTester();
    
    try {
        await tester.init();
        
        // 執行測試
        const loginSuccess = await tester.testLogin();
        
        if (loginSuccess) {
            await tester.testUserList();
        }
        
        console.log('\n🎉 測試完成！');
        
    } catch (error) {
        console.log(`❌ 測試過程發生錯誤: ${error.message}`);
    } finally {
        await tester.close();
    }
}

// 如果直接執行此腳本
if (require.main === module) {
    runTests().catch(console.error);
}

module.exports = WebTester;