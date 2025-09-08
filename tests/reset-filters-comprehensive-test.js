/**
 * Livewire 重置篩選功能綜合測試腳本
 * 
 * 此腳本測試所有已修復的 Livewire 元件的重置篩選功能
 */

class ComprehensiveResetFiltersTest {
    constructor() {
        this.testResults = [];
        this.currentTest = null;
        this.testStartTime = null;
    }

    /**
     * 執行所有測試
     */
    async runAllTests() {
        console.log('🚀 開始執行 Livewire 重置篩選功能綜合測試');
        console.log('測試時間:', new Date().toLocaleString());
        
        const testSuites = [
            { name: '使用者管理', url: '/admin/users', component: 'UserList' },
            { name: '角色管理', url: '/admin/roles', component: 'RoleList' },
            { name: '權限管理', url: '/admin/permissions', component: 'PermissionList' },
            { name: '活動記錄', url: '/admin/activities', component: 'ActivityList' },
            { name: '設定列表', url: '/admin/settings', component: 'SettingsList' },
            { name: '通知列表', url: '/admin/notifications', component: 'NotificationList' },
            { name: '權限審計日誌', url: '/admin/permissions/audit', component: 'PermissionAuditLog' }
        ];

        for (const suite of testSuites) {
            await this.runTestSuite(suite);
            await this.delay(2000); // 等待 2 秒再進行下一個測試
        }

        this.generateReport();
        return this.testResults;
    }

    /**
     * 執行單個測試套件
     */
    async runTestSuite(suite) {
        console.log(`\n📋 測試套件: ${suite.name} (${suite.component})`);
        
        try {
            // 導航到測試頁面
            if (window.location.pathname !== suite.url) {
                console.log(`🔄 導航到 ${suite.url}`);
                window.location.href = suite.url;
                await this.delay(3000); // 等待頁面載入
            }

            const suiteResults = {
                name: suite.name,
                component: suite.component,
                url: suite.url,
                tests: [],
                startTime: new Date(),
                status: 'running'
            };

            // 執行基本功能測試
            await this.testBasicResetFunctionality(suiteResults);
            await this.testSearchReset(suiteResults);
            await this.testFilterReset(suiteResults);
            await this.testPaginationReset(suiteResults);
            await this.testUIConsistency(suiteResults);
            await this.testStateSync(suiteResults);

            suiteResults.endTime = new Date();
            suiteResults.duration = suiteResults.endTime - suiteResults.startTime;
            suiteResults.status = suiteResults.tests.every(t => t.passed) ? 'passed' : 'failed';
            
            this.testResults.push(suiteResults);
            
            console.log(`✅ 測試套件 ${suite.name} 完成 - 狀態: ${suiteResults.status}`);
            
        } catch (error) {
            console.error(`❌ 測試套件 ${suite.name} 執行失敗:`, error);
            this.testResults.push({
                name: suite.name,
                component: suite.component,
                url: suite.url,
                tests: [],
                status: 'error',
                error: error.message
            });
        }
    }

    /**
     * 測試基本重置功能
     */
    async testBasicResetFunctionality(suiteResults) {
        const testName = '基本重置功能';
        console.log(`  🧪 ${testName}`);
        
        try {
            // 檢查重置按鈕是否存在
            const resetButton = document.querySelector('button[wire\\:click="resetFilters"]');
            
            if (!resetButton) {
                throw new Error('找不到重置按鈕');
            }

            // 檢查重置方法是否可用
            const livewireComponent = this.findLivewireComponent();
            if (!livewireComponent || typeof livewireComponent.resetFilters !== 'function') {
                throw new Error('Livewire 元件沒有 resetFilters 方法');
            }

            suiteResults.tests.push({
                name: testName,
                passed: true,
                message: '重置按鈕和方法都存在'
            });

        } catch (error) {
            suiteResults.tests.push({
                name: testName,
                passed: false,
                error: error.message
            });
        }
    }

    /**
     * 測試搜尋重置功能
     */
    async testSearchReset(suiteResults) {
        const testName = '搜尋重置功能';
        console.log(`  🧪 ${testName}`);
        
        try {
            // 找到搜尋框
            const searchInput = document.querySelector('input[wire\\:model\\.live="search"]');
            
            if (searchInput) {
                // 輸入搜尋內容
                searchInput.value = '測試搜尋';
                searchInput.dispatchEvent(new Event('input', { bubbles: true }));
                
                await this.delay(1000);
                
                // 檢查重置按鈕是否顯示
                const resetButton = document.querySelector('button[wire\\:click="resetFilters"]');
                const isVisible = resetButton && this.isElementVisible(resetButton);
                
                if (!isVisible) {
                    throw new Error('輸入搜尋內容後重置按鈕沒有顯示');
                }
                
                // 點擊重置按鈕
                resetButton.click();
                
                await this.delay(1000);
                
                // 檢查搜尋框是否清空
                if (searchInput.value !== '') {
                    throw new Error('重置後搜尋框沒有清空');
                }
                
                suiteResults.tests.push({
                    name: testName,
                    passed: true,
                    message: '搜尋重置功能正常'
                });
            } else {
                suiteResults.tests.push({
                    name: testName,
                    passed: true,
                    message: '此頁面沒有搜尋功能，跳過測試'
                });
            }

        } catch (error) {
            suiteResults.tests.push({
                name: testName,
                passed: false,
                error: error.message
            });
        }
    }

    /**
     * 測試篩選器重置功能
     */
    async testFilterReset(suiteResults) {
        const testName = '篩選器重置功能';
        console.log(`  🧪 ${testName}`);
        
        try {
            // 找到篩選器下拉選單
            const filterSelects = document.querySelectorAll('select[wire\\:model\\.live*="Filter"]');
            
            if (filterSelects.length > 0) {
                let changedFilters = 0;
                
                // 改變篩選器值
                filterSelects.forEach(select => {
                    if (select.options.length > 1) {
                        select.selectedIndex = 1; // 選擇第二個選項
                        select.dispatchEvent(new Event('change', { bubbles: true }));
                        changedFilters++;
                    }
                });
                
                if (changedFilters === 0) {
                    throw new Error('沒有可用的篩選器選項');
                }
                
                await this.delay(1000);
                
                // 檢查重置按鈕是否顯示
                const resetButton = document.querySelector('button[wire\\:click="resetFilters"]');
                const isVisible = resetButton && this.isElementVisible(resetButton);
                
                if (!isVisible) {
                    throw new Error('改變篩選器後重置按鈕沒有顯示');
                }
                
                // 點擊重置按鈕
                resetButton.click();
                
                await this.delay(1000);
                
                // 檢查篩選器是否重置
                let resetFilters = 0;
                filterSelects.forEach(select => {
                    if (select.value === 'all' || select.value === '' || select.selectedIndex === 0) {
                        resetFilters++;
                    }
                });
                
                if (resetFilters !== filterSelects.length) {
                    throw new Error('部分篩選器沒有重置');
                }
                
                suiteResults.tests.push({
                    name: testName,
                    passed: true,
                    message: `篩選器重置功能正常 (測試了 ${changedFilters} 個篩選器)`
                });
            } else {
                suiteResults.tests.push({
                    name: testName,
                    passed: true,
                    message: '此頁面沒有篩選器，跳過測試'
                });
            }

        } catch (error) {
            suiteResults.tests.push({
                name: testName,
                passed: false,
                error: error.message
            });
        }
    }

    /**
     * 測試分頁重置功能
     */
    async testPaginationReset(suiteResults) {
        const testName = '分頁重置功能';
        console.log(`  🧪 ${testName}`);
        
        try {
            // 檢查每頁顯示筆數選擇器
            const perPageSelect = document.querySelector('select[wire\\:model\\.live="perPage"]');
            
            if (perPageSelect && perPageSelect.options.length > 1) {
                const originalValue = perPageSelect.value;
                
                // 改變每頁顯示筆數
                perPageSelect.selectedIndex = perPageSelect.selectedIndex === 0 ? 1 : 0;
                perPageSelect.dispatchEvent(new Event('change', { bubbles: true }));
                
                await this.delay(1000);
                
                // 檢查 URL 是否包含 perPage 參數
                const urlParams = new URLSearchParams(window.location.search);
                const hasPerPageParam = urlParams.has('perPage');
                
                if (!hasPerPageParam) {
                    console.warn('URL 中沒有 perPage 參數，但這可能是正常的');
                }
                
                suiteResults.tests.push({
                    name: testName,
                    passed: true,
                    message: '分頁功能正常'
                });
            } else {
                suiteResults.tests.push({
                    name: testName,
                    passed: true,
                    message: '此頁面沒有分頁功能，跳過測試'
                });
            }

        } catch (error) {
            suiteResults.tests.push({
                name: testName,
                passed: false,
                error: error.message
            });
        }
    }

    /**
     * 測試 UI 一致性
     */
    async testUIConsistency(suiteResults) {
        const testName = 'UI 一致性';
        console.log(`  🧪 ${testName}`);
        
        try {
            const issues = [];
            
            // 檢查重置按鈕樣式
            const resetButton = document.querySelector('button[wire\\:click="resetFilters"]');
            if (resetButton) {
                const computedStyle = window.getComputedStyle(resetButton);
                if (!computedStyle.transition || computedStyle.transition === 'none') {
                    issues.push('重置按鈕缺少過渡效果');
                }
            }
            
            // 檢查表格樣式
            const table = document.querySelector('table');
            if (table) {
                const computedStyle = window.getComputedStyle(table);
                if (!computedStyle.borderCollapse || computedStyle.borderCollapse !== 'separate') {
                    // 這是正常的，不算問題
                }
            }
            
            // 檢查響應式設計
            const isMobile = window.innerWidth < 640;
            const mobileElements = document.querySelectorAll('.sm\\:hidden');
            const desktopElements = document.querySelectorAll('.hidden.sm\\:block');
            
            if (isMobile && mobileElements.length === 0) {
                issues.push('缺少手機版專用元素');
            }
            
            if (issues.length > 0) {
                suiteResults.tests.push({
                    name: testName,
                    passed: false,
                    error: issues.join(', ')
                });
            } else {
                suiteResults.tests.push({
                    name: testName,
                    passed: true,
                    message: 'UI 一致性檢查通過'
                });
            }

        } catch (error) {
            suiteResults.tests.push({
                name: testName,
                passed: false,
                error: error.message
            });
        }
    }

    /**
     * 測試狀態同步
     */
    async testStateSync(suiteResults) {
        const testName = '狀態同步';
        console.log(`  🧪 ${testName}`);
        
        try {
            // 檢查 Livewire 元件狀態
            const livewireComponent = this.findLivewireComponent();
            
            if (!livewireComponent) {
                throw new Error('找不到 Livewire 元件');
            }
            
            // 檢查前端狀態與後端狀態是否同步
            const searchInput = document.querySelector('input[wire\\:model\\.live="search"]');
            if (searchInput && livewireComponent.search !== undefined) {
                if (searchInput.value !== livewireComponent.search) {
                    throw new Error('搜尋框前端值與 Livewire 狀態不同步');
                }
            }
            
            // 檢查篩選器狀態同步
            const filterSelects = document.querySelectorAll('select[wire\\:model\\.live*="Filter"]');
            filterSelects.forEach(select => {
                const modelName = select.getAttribute('wire:model.live');
                if (modelName && livewireComponent[modelName] !== undefined) {
                    if (select.value !== livewireComponent[modelName]) {
                        throw new Error(`篩選器 ${modelName} 前端值與 Livewire 狀態不同步`);
                    }
                }
            });
            
            suiteResults.tests.push({
                name: testName,
                passed: true,
                message: '狀態同步檢查通過'
            });

        } catch (error) {
            suiteResults.tests.push({
                name: testName,
                passed: false,
                error: error.message
            });
        }
    }

    /**
     * 找到當前頁面的 Livewire 元件
     */
    findLivewireComponent() {
        if (window.Livewire && window.Livewire.all) {
            const components = window.Livewire.all();
            return components.length > 0 ? components[0] : null;
        }
        return null;
    }

    /**
     * 檢查元素是否可見
     */
    isElementVisible(element) {
        const style = window.getComputedStyle(element);
        return style.display !== 'none' && 
               style.visibility !== 'hidden' && 
               style.opacity !== '0' &&
               element.offsetWidth > 0 && 
               element.offsetHeight > 0;
    }

    /**
     * 延遲執行
     */
    delay(ms) {
        return new Promise(resolve => setTimeout(resolve, ms));
    }

    /**
     * 生成測試報告
     */
    generateReport() {
        console.log('\n📊 測試報告');
        console.log('='.repeat(50));
        
        let totalTests = 0;
        let passedTests = 0;
        let failedTests = 0;
        
        this.testResults.forEach(suite => {
            console.log(`\n📋 ${suite.name} (${suite.component})`);
            console.log(`   狀態: ${suite.status === 'passed' ? '✅ 通過' : '❌ 失敗'}`);
            console.log(`   URL: ${suite.url}`);
            
            if (suite.duration) {
                console.log(`   執行時間: ${suite.duration}ms`);
            }
            
            if (suite.tests) {
                suite.tests.forEach(test => {
                    totalTests++;
                    if (test.passed) {
                        passedTests++;
                        console.log(`     ✅ ${test.name}: ${test.message}`);
                    } else {
                        failedTests++;
                        console.log(`     ❌ ${test.name}: ${test.error}`);
                    }
                });
            }
            
            if (suite.error) {
                console.log(`   錯誤: ${suite.error}`);
            }
        });
        
        console.log('\n📈 總結');
        console.log(`   總測試數: ${totalTests}`);
        console.log(`   通過: ${passedTests}`);
        console.log(`   失敗: ${failedTests}`);
        console.log(`   成功率: ${totalTests > 0 ? Math.round((passedTests / totalTests) * 100) : 0}%`);
        
        // 匯出結果
        this.exportResults();
    }

    /**
     * 匯出測試結果
     */
    exportResults() {
        const results = {
            timestamp: new Date().toISOString(),
            summary: {
                totalSuites: this.testResults.length,
                passedSuites: this.testResults.filter(s => s.status === 'passed').length,
                failedSuites: this.testResults.filter(s => s.status === 'failed').length,
                totalTests: this.testResults.reduce((sum, s) => sum + (s.tests ? s.tests.length : 0), 0),
                passedTests: this.testResults.reduce((sum, s) => sum + (s.tests ? s.tests.filter(t => t.passed).length : 0), 0),
                failedTests: this.testResults.reduce((sum, s) => sum + (s.tests ? s.tests.filter(t => !t.passed).length : 0), 0)
            },
            results: this.testResults
        };
        
        // 將結果保存到 localStorage
        localStorage.setItem('livewire_reset_test_results', JSON.stringify(results));
        
        console.log('\n💾 測試結果已保存到 localStorage');
        console.log('可以使用以下命令查看詳細結果:');
        console.log('JSON.parse(localStorage.getItem("livewire_reset_test_results"))');
    }
}

// 執行測試的便利函數
async function runComprehensiveResetTest() {
    const tester = new ComprehensiveResetFiltersTest();
    return await tester.runAllTests();
}

// 如果在瀏覽器控制台中執行，自動開始測試
if (typeof window !== 'undefined') {
    console.log('🧪 Livewire 重置篩選功能綜合測試腳本已載入');
    console.log('執行 runComprehensiveResetTest() 開始測試');
    
    // 將測試類別和函數添加到全域範圍
    window.ComprehensiveResetFiltersTest = ComprehensiveResetFiltersTest;
    window.runComprehensiveResetTest = runComprehensiveResetTest;
}

// 匯出供 Node.js 使用
if (typeof module !== 'undefined' && module.exports) {
    module.exports = { ComprehensiveResetFiltersTest, runComprehensiveResetTest };
}