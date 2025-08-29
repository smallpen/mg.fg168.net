// 重置篩選功能測試腳本 - 更新版本
// 使用方法：在瀏覽器控制台中執行此腳本

class ResetFiltersTest {
    constructor() {
        this.testResults = [];
        this.currentTest = '';
    }

    log(message, type = 'info') {
        const timestamp = new Date().toLocaleTimeString();
        const logMessage = `[${timestamp}] ${type.toUpperCase()}: ${message}`;
        console.log(logMessage);
        
        this.testResults.push({
            timestamp,
            type,
            message,
            test: this.currentTest
        });
    }

    async testResetFunction(pageName, pageUrl) {
        this.currentTest = pageName;
        this.log(`開始測試 ${pageName} 的重置功能`, 'test');
        
        try {
            // 1. 導航到頁面
            if (window.location.pathname !== pageUrl) {
                this.log(`導航到 ${pageUrl}`);
                window.location.href = pageUrl;
                await this.wait(2000);
            }

            // 2. 展開篩選器（如果有切換按鈕）
            const filterToggle = document.querySelector('button[wire\\\\:click=\"toggleFilters\"]');
            if (filterToggle) {
                this.log('展開篩選器');
                filterToggle.click();
                await this.wait(1000);
            }

            // 3. 設定篩選條件
            await this.setFilters();

            // 4. 檢查重置按鈕是否出現
            const resetButton = this.findResetButton();
            if (!resetButton) {
                this.log('❌ 找不到重置按鈕', 'error');
                return false;
            }
            this.log('✅ 重置按鈕已出現');

            // 5. 點擊重置按鈕
            this.log('點擊重置按鈕');
            resetButton.click();
            await this.wait(2000);

            // 6. 驗證重置結果
            const resetSuccess = await this.verifyReset();
            if (resetSuccess) {
                this.log(`✅ ${pageName} 重置功能測試通過`, 'success');
                return true;
            } else {
                this.log(`❌ ${pageName} 重置功能測試失敗`, 'error');
                return false;
            }

        } catch (error) {
            this.log(`測試過程中發生錯誤: ${error.message}`, 'error');
            return false;
        }
    }

    async setFilters() {
        this.log('設定篩選條件');
        
        // 設定搜尋框
        const searchInputs = document.querySelectorAll('input[wire\\\\:model\\\\.live=\"search\"]');
        if (searchInputs.length > 0) {
            const searchInput = searchInputs[0];
            searchInput.value = 'test';
            searchInput.dispatchEvent(new Event('input', { bubbles: true }));
            this.log('已設定搜尋條件: test');
        }

        // 設定第一個下拉篩選器
        const selects = document.querySelectorAll('select[wire\\\\:model\\\\.live*=\"Filter\"]');
        if (selects.length > 0) {
            const select = selects[0];
            if (select.options.length > 1) {
                select.value = select.options[1].value;
                select.dispatchEvent(new Event('change', { bubbles: true }));
                this.log(`已設定篩選器: ${select.value}`);
            }
        }

        // 設定日期篩選器（如果存在）
        const dateInputs = document.querySelectorAll('input[type=\"date\"]');
        if (dateInputs.length > 0) {
            const dateInput = dateInputs[0];
            dateInput.value = '2024-01-01';
            dateInput.dispatchEvent(new Event('input', { bubbles: true }));
            this.log('已設定日期篩選器');
        }

        await this.wait(1000);
    }

    findResetButton() {
        // 嘗試多種選擇器來找到重置按鈕
        const selectors = [
            'button[wire\\\\:click=\"resetFilters\"]',
            'button[wire\\\\:click=\"clearFilters\"]',
            '.hidden.sm\\\\:flex button[wire\\\\:click=\"resetFilters\"]',
            '.block.sm\\\\:hidden button[wire\\\\:click=\"resetFilters\"]',
            'button[title=\"重置篩選\"]',
            'button[title=\"清除篩選\"]'
        ];

        for (const selector of selectors) {
            const button = document.querySelector(selector);
            if (button && getComputedStyle(button).display !== 'none') {
                return button;
            }
        }
        return null;
    }

    async verifyReset() {
        this.log('驗證重置結果');
        
        // 檢查搜尋框是否清空
        const searchInputs = document.querySelectorAll('input[wire\\\\:model\\\\.live=\"search\"]');
        for (const input of searchInputs) {
            if (input.value !== '') {
                this.log(`❌ 搜尋框未清空: ${input.value}`, 'error');
                return false;
            }
        }
        this.log('✅ 搜尋框已清空');

        // 檢查下拉選單是否重置為 'all' 或空值
        const selects = document.querySelectorAll('select[wire\\\\:model\\\\.live*=\"Filter\"]');
        for (const select of selects) {
            if (select.value !== 'all' && select.value !== '') {
                this.log(`❌ 下拉選單未重置: ${select.value}`, 'error');
                return false;
            }
        }
        this.log('✅ 下拉選單已重置');

        // 檢查重置按鈕是否隱藏
        await this.wait(500); // 等待 UI 更新
        const resetButton = this.findResetButton();
        if (resetButton && getComputedStyle(resetButton).display !== 'none') {
            this.log('❌ 重置按鈕未隱藏', 'error');
            return false;
        }
        this.log('✅ 重置按鈕已隱藏');

        return true;
    }

    wait(ms) {
        return new Promise(resolve => setTimeout(resolve, ms));
    }

    async runAllTests() {
        this.log('開始執行所有重置功能測試', 'test');
        
        const tests = [
            { name: '權限管理', url: '/admin/permissions' },
            { name: '使用者管理', url: '/admin/users' },
            { name: '角色管理', url: '/admin/roles' },
            { name: '權限審計日誌', url: '/admin/permissions/audit-log' },
            { name: '活動記錄', url: '/admin/activities' },
            { name: '設定列表', url: '/admin/settings' },
            { name: '通知列表', url: '/admin/activities/notifications' }
        ];

        const results = [];
        for (const test of tests) {
            const result = await this.testResetFunction(test.name, test.url);
            results.push({ name: test.name, success: result });
            await this.wait(1000);
        }

        // 輸出測試結果摘要
        this.log('=== 測試結果摘要 ===', 'test');
        results.forEach(result => {
            const status = result.success ? '✅ 通過' : '❌ 失敗';
            this.log(`${result.name}: ${status}`);
        });

        const passedTests = results.filter(r => r.success).length;
        const totalTests = results.length;
        this.log(`總計: ${passedTests}/${totalTests} 個測試通過`);

        return results;
    }

    exportResults() {
        const results = {
            timestamp: new Date().toISOString(),
            testResults: this.testResults,
            summary: this.testResults.reduce((acc, result) => {
                acc[result.type] = (acc[result.type] || 0) + 1;
                return acc;
            }, {})
        };

        console.log('測試結果:', results);
        return results;
    }
}

// 使用方法：
// const tester = new ResetFiltersTest();
// tester.runAllTests().then(results => {
//     console.log('所有測試完成:', results);
//     tester.exportResults();
// });

// 或者測試單個頁面：
// tester.testResetFunction('權限管理', '/admin/permissions');

console.log('重置篩選功能測試腳本已載入（更新版本）');
console.log('使用方法:');
console.log('const tester = new ResetFiltersTest();');
console.log('tester.runAllTests();');