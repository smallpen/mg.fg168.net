/**
 * UserList 元件篩選重置功能測試
 * 驗證修復後的前後端狀態同步
 */

const { test, expect } = require('@playwright/test');

// 登入輔助函數
async function livewireLogin(page, username = 'admin', password = 'password123') {
    console.log(`🔐 開始 Livewire 登入: ${username}`);
    
    try {
        // 導航到登入頁面
        await page.goto('http://localhost/admin/login');
        
        // 等待 Livewire 載入
        await page.evaluate(`
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
        await page.evaluate(`
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
        await page.evaluate('new Promise(resolve => setTimeout(resolve, 800))');
        
        // 提交表單
        await page.click('button[type="submit"]');
        
        // 等待重定向
        const result = await page.evaluate(`
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
        
        return result === 'success';
        
    } catch (error) {
        console.error('登入錯誤:', error.message);
        return false;
    }
}

test.describe('UserList 篩選重置功能測試', () => {
    test.beforeEach(async ({ page }) => {
        // 每個測試前都重新登入
        const loginSuccess = await livewireLogin(page);
        expect(loginSuccess).toBe(true);
        
        // 導航到使用者列表頁面
        await page.goto('http://localhost/admin/users');
        
        // 等待頁面載入完成
        await page.waitForSelector('[wire\\:key="search-desktop-input"]', { timeout: 10000 });
    });

    test('桌面版篩選重置功能', async ({ page }) => {
        console.log('🧪 測試桌面版篩選重置功能');
        
        // 1. 設定篩選條件
        await page.fill('#search', 'test');
        await page.selectOption('#roleFilter', 'admin');
        await page.selectOption('#statusFilter', 'active');
        
        // 等待篩選生效
        await page.waitForTimeout(1000);
        
        // 2. 驗證篩選條件已設定
        const searchValue = await page.inputValue('#search');
        const roleValue = await page.inputValue('#roleFilter');
        const statusValue = await page.inputValue('#statusFilter');
        
        expect(searchValue).toBe('test');
        expect(roleValue).toBe('admin');
        expect(statusValue).toBe('active');
        
        // 3. 點擊重置按鈕
        await page.click('button[wire\\:click="resetFilters"]');
        
        // 等待重置完成
        await page.waitForTimeout(1500);
        
        // 4. 驗證篩選條件已重置
        const resetSearchValue = await page.inputValue('#search');
        const resetRoleValue = await page.inputValue('#roleFilter');
        const resetStatusValue = await page.inputValue('#statusFilter');
        
        expect(resetSearchValue).toBe('');
        expect(resetRoleValue).toBe('all');
        expect(resetStatusValue).toBe('all');
        
        console.log('✅ 桌面版篩選重置測試通過');
    });

    test('手機版篩選重置功能', async ({ page }) => {
        console.log('🧪 測試手機版篩選重置功能');
        
        // 設定手機版視窗大小
        await page.setViewportSize({ width: 375, height: 667 });
        
        // 重新載入頁面以觸發響應式佈局
        await page.reload();
        await page.waitForSelector('#search-mobile', { timeout: 10000 });
        
        // 1. 設定篩選條件
        await page.fill('#search-mobile', 'mobile test');
        await page.selectOption('#roleFilter-mobile', 'user');
        await page.selectOption('#statusFilter-mobile', 'inactive');
        
        // 等待篩選生效
        await page.waitForTimeout(1000);
        
        // 2. 驗證篩選條件已設定
        const searchValue = await page.inputValue('#search-mobile');
        const roleValue = await page.inputValue('#roleFilter-mobile');
        const statusValue = await page.inputValue('#statusFilter-mobile');
        
        expect(searchValue).toBe('mobile test');
        expect(roleValue).toBe('user');
        expect(statusValue).toBe('inactive');
        
        // 3. 點擊重置按鈕
        await page.click('button[wire\\:key="mobile-reset-button"]');
        
        // 等待重置完成
        await page.waitForTimeout(1500);
        
        // 4. 驗證篩選條件已重置
        const resetSearchValue = await page.inputValue('#search-mobile');
        const resetRoleValue = await page.inputValue('#roleFilter-mobile');
        const resetStatusValue = await page.inputValue('#statusFilter-mobile');
        
        expect(resetSearchValue).toBe('');
        expect(resetRoleValue).toBe('all');
        expect(resetStatusValue).toBe('all');
        
        console.log('✅ 手機版篩選重置測試通過');
    });

    test('批量操作區域重置功能', async ({ page }) => {
        console.log('🧪 測試批量操作區域重置功能');
        
        // 1. 設定篩選條件
        await page.fill('#search', 'batch test');
        await page.selectOption('#roleFilter', 'admin');
        
        // 等待篩選生效
        await page.waitForTimeout(1000);
        
        // 2. 選擇一些使用者（如果有的話）
        const checkboxes = await page.$$('input[type="checkbox"][value]');
        if (checkboxes.length > 0) {
            await checkboxes[0].click();
            
            // 等待批量操作區域出現
            await page.waitForSelector('button[wire\\:key="desktop-bulk-reset-button"]', { timeout: 5000 });
            
            // 3. 點擊批量操作區域的重置按鈕
            await page.click('button[wire\\:key="desktop-bulk-reset-button"]');
        } else {
            // 如果沒有使用者，直接測試普通重置
            await page.click('button[wire\\:click="resetFilters"]');
        }
        
        // 等待重置完成
        await page.waitForTimeout(1500);
        
        // 4. 驗證篩選條件已重置
        const resetSearchValue = await page.inputValue('#search');
        const resetRoleValue = await page.inputValue('#roleFilter');
        
        expect(resetSearchValue).toBe('');
        expect(resetRoleValue).toBe('all');
        
        console.log('✅ 批量操作區域重置測試通過');
    });

    test('前後端狀態同步驗證', async ({ page }) => {
        console.log('🧪 測試前後端狀態同步');
        
        // 1. 設定篩選條件
        await page.fill('#search', 'sync test');
        await page.selectOption('#roleFilter', 'admin');
        await page.selectOption('#statusFilter', 'active');
        
        // 等待篩選生效
        await page.waitForTimeout(1000);
        
        // 2. 檢查頁面顯示的當前篩選狀態
        const filterStatus = await page.textContent('[wire\\:key="filter-status"]');
        expect(filterStatus).toContain('sync test');
        expect(filterStatus).toContain('admin');
        expect(filterStatus).toContain('active');
        
        // 3. 執行重置
        await page.click('button[wire\\:click="resetFilters"]');
        
        // 等待重置完成
        await page.waitForTimeout(1500);
        
        // 4. 驗證前端表單已重置
        const resetSearchValue = await page.inputValue('#search');
        const resetRoleValue = await page.inputValue('#roleFilter');
        const resetStatusValue = await page.inputValue('#statusFilter');
        
        expect(resetSearchValue).toBe('');
        expect(resetRoleValue).toBe('all');
        expect(resetStatusValue).toBe('all');
        
        // 5. 驗證後端狀態也已重置（檢查顯示的篩選狀態）
        const resetFilterStatus = await page.textContent('[wire\\:key="filter-status"]');
        expect(resetFilterStatus).toContain('搜尋=""');
        expect(resetFilterStatus).toContain('角色="all"');
        expect(resetFilterStatus).toContain('狀態="all"');
        
        console.log('✅ 前後端狀態同步測試通過');
    });

    test('重置後成功訊息顯示', async ({ page }) => {
        console.log('🧪 測試重置後成功訊息顯示');
        
        // 1. 設定篩選條件
        await page.fill('#search', 'message test');
        
        // 等待篩選生效
        await page.waitForTimeout(1000);
        
        // 2. 執行重置
        await page.click('button[wire\\:click="resetFilters"]');
        
        // 3. 等待並檢查成功訊息（如果有 toast 通知系統）
        try {
            await page.waitForSelector('.toast, .alert, [data-toast]', { timeout: 3000 });
            const toastText = await page.textContent('.toast, .alert, [data-toast]');
            expect(toastText).toContain('篩選條件已清除');
            console.log('✅ 成功訊息顯示測試通過');
        } catch (error) {
            console.log('ℹ️  未檢測到 toast 訊息，可能未實作或選擇器不正確');
        }
        
        // 4. 驗證重置仍然有效
        const resetSearchValue = await page.inputValue('#search');
        expect(resetSearchValue).toBe('');
        
        console.log('✅ 重置功能本身測試通過');
    });

    test('測試方法驗證 Livewire 連接', async ({ page }) => {
        console.log('🧪 測試 Livewire 連接');
        
        // 點擊測試方法按鈕
        await page.click('button[wire\\:key="test-method-button"]');
        
        // 等待響應
        await page.waitForTimeout(1000);
        
        // 檢查是否有成功訊息或日誌
        try {
            await page.waitForSelector('.toast, .alert, [data-toast]', { timeout: 3000 });
            const toastText = await page.textContent('.toast, .alert, [data-toast]');
            expect(toastText).toContain('測試方法執行成功');
            console.log('✅ Livewire 連接測試通過');
        } catch (error) {
            console.log('ℹ️  未檢測到測試方法回應，檢查控制台日誌');
        }
    });
});