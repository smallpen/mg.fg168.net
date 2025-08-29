// Livewire 狀態同步測試腳本
// 專門測試重置篩選後的狀態同步問題

class StateSyncTest {
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

    async testStateSyncAfterReset(pageName, pageUrl) {
        this.currentTest = pageName;
        this.log(`開始測試 ${pageName} 的狀態同步`, 'test');
        
        try {
            // 1. 導航到頁面
            if (window.location.pathname !== pageUrl) {
                this.log(`導航到 ${pageUrl}`);
                window.location.href = pageUrl;
                await this.wait(3000);
            }

            // 2. 展開篩選器（如果有切換按鈕）
            const filterToggle = document.querySelector('button[wire\\\\:click=\"toggleFilters\"]');
            if (filterToggle) {
                this.log('展開篩選器');
                filterToggle.click();
                await this.wait(1000);
            }

            // 3. 設定篩選條件
            await this.setFiltersAndRecord();

            // 4. 記錄設定前的狀態
            const beforeState = this.captureFormState();
            this.log('設定前狀態: ' + JSON.stringify(beforeState));

            // 5. 點擊重置按鈕
            const resetButton = this.findResetButton();
            if (!resetButton) {
                this.log('❌ 找不到重置按鈕', 'error');
                return false;
            }
            
            this.log('點擊重置按鈕');
            resetButton.click();
            
            // 6. 等待 Livewire 處理
            await this.wait(2000);

            // 7. 記錄重置後的狀態
            const afterState = this.captureFormState();
            this.log('重置後狀態: ' + JSON.stringify(afterState));

            // 8. 驗證狀態同步
            const syncResult = this.verifyStateSync(beforeState, afterState);
            
            if (syncResult.success) {
                this.log(`✅ ${pageName} 狀態同步測試通過`, 'success');
                return true;
            } else {
                this.log(`❌ ${pageName} 狀態同步測試失敗: ${syncResult.message}`, 'error');
                return false;
            }

        } catch (error) {
            this.log(`測試過程中發生錯誤: ${error.message}`, 'error');
            return false;
        }
    }

    async setFiltersAndRecord() {
        this.log('設定篩選條件並記錄');
        
        // 設定搜尋框
        const searchInputs = document.querySelectorAll('input[wire\\\\:model\\\\.live=\"search\"]');
        if (searchInputs.length > 0) {
            const searchInput = searchInputs[0];
            searchInput.value = 'test-search';
            searchInput.dispatchEvent(new Event('input', { bubbles: true }));
            this.log('已設定搜尋條件: test-search');
        }

        // 設定第一個下拉篩選器
        const selects = document.querySelectorAll('select[wire\\\\:model\\\\.live*=\"Filter\"]');
        if (selects.length > 0) {
            const select = selects[0];
            if (select.options.length > 1) {
                const newValue = select.options[1].value;
                select.value = newValue;
                select.dispatchEvent(new Event('change', { bubbles: true }));
                this.log(`已設定篩選器: ${newValue}`);
            }
        }

        // 設定日期篩選器（如果存在）
        const dateInputs = document.querySelectorAll('input[type=\"date\"]');
        if (dateInputs.length > 0) {
            const dateInput = dateInputs[0];
            dateInput.value = '2024-01-01';
            dateInput.dispatchEvent(new Event('input', { bubbles: true }));
            this.log('已設定日期篩選器: 2024-01-01');
        }

        await this.wait(1000);
    }

    captureFormState() {
        const state = {
            search: [],
            selects: [],
            dates: [],
            others: []
        };

        // 捕獲搜尋框狀態
        const searchInputs = document.querySelectorAll('input[wire\\\\:model\\\\.live=\"search\"]');
        searchInputs.forEach((input, index) => {
            state.search.push({
                index,
                value: input.value,
                wireModel: input.getAttribute('wire:model.live')
            });
        });

        // 捕獲下拉選單狀態
        const selects = document.querySelectorAll('select[wire\\\\:model\\\\.live*=\"Filter\"]');
        selects.forEach((select, index) => {
            state.selects.push({
                index,
                value: select.value,
                wireModel: select.getAttribute('wire:model.live')
            });
        });

        // 捕獲日期輸入框狀態
        const dateInputs = document.querySelectorAll('input[type=\"date\"]');
        dateInputs.forEach((input, index) => {
            state.dates.push({
                index,
                value: input.value,
                wireModel: input.getAttribute('wire:model.live')
            });
        });

        // 捕獲其他輸入框狀態
        const otherInputs = document.querySelectorAll('input[wire\\\\:model\\\\.live*=\"Filter\"]');
        otherInputs.forEach((input, index) => {
            state.others.push({
                index,
                value: input.value,
                wireModel: input.getAttribute('wire:model.live')
            });
        });

        return state;
    }

    verifyStateSync(beforeState, afterState) {
        const issues = [];

        // 檢查搜尋框是否清空
        afterState.search.forEach((searchState, index) => {
            if (searchState.value !== '') {
                issues.push(`搜尋框 ${index} 未清空: "${searchState.value}"`);
            }
        });

        // 檢查下拉選單是否重置為 'all' 或空值
        afterState.selects.forEach((selectState, index) => {
            if (selectState.value !== 'all' && selectState.value !== '') {
                issues.push(`下拉選單 ${index} 未重置: "${selectState.value}"`);
            }
        });

        // 檢查其他輸入框是否清空
        afterState.others.forEach((otherState, index) => {
            if (otherState.value !== '') {
                issues.push(`輸入框 ${index} 未清空: "${otherState.value}"`);
            }
        });

        if (issues.length === 0) {
            return { success: true, message: '所有表單元素狀態同步正確' };
        } else {
            return { success: false, message: issues.join('; ') };
        }
    }

    findResetButton() {
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

    wait(ms) {
        return new Promise(resolve => setTimeout(resolve, ms));
    }

    async runAllStateSyncTests() {
        this.log('開始執行所有狀態同步測試', 'test');
        
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
            const result = await this.testStateSyncAfterReset(test.name, test.url);
            results.push({ name: test.name, success: result });
            await this.wait(2000); // 測試間隔
        }

        // 輸出測試結果摘要
        this.log('=== 狀態同步測試結果摘要 ===', 'test');
        results.forEach(result => {
            const status = result.success ? '✅ 同步正常' : '❌ 同步失敗';
            this.log(`${result.name}: ${status}`);
        });

        const passedTests = results.filter(r => r.success).length;
        const totalTests = results.length;
        this.log(`總計: ${passedTests}/${totalTests} 個測試通過狀態同步檢查`);

        return results;
    }

    exportResults() {
        const results = {
            timestamp: new Date().toISOString(),
            testType: 'state-sync',
            testResults: this.testResults,
            summary: this.testResults.reduce((acc, result) => {
                acc[result.type] = (acc[result.type] || 0) + 1;
                return acc;
            }, {})
        };

        console.log('狀態同步測試結果:', results);
        return results;
    }
}

// 使用方法：
// const syncTester = new StateSyncTest();
// syncTester.runAllStateSyncTests().then(results => {
//     console.log('所有狀態同步測試完成:', results);
//     syncTester.exportResults();
// });

// 或者測試單個頁面：
// syncTester.testStateSyncAfterReset('使用者管理', '/admin/users');

console.log('Livewire 狀態同步測試腳本已載入');
console.log('使用方法:');
console.log('const syncTester = new StateSyncTest();');
console.log('syncTester.runAllStateSyncTests();');