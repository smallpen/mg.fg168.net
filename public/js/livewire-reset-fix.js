/**
 * Livewire 重置篩選功能修復腳本
 * 
 * 此腳本解決 Livewire 3.0 中重置篩選功能的狀態同步問題
 */

// 重置按鈕控制器
function resetButtonController() {
    return {
        showResetButton: false,
        
        init() {
            console.log('🔧 重置按鈕控制器初始化');
            
            // 監聽重置表單元素事件
            if (window.Livewire) {
                Livewire.on('reset-form-elements', () => {
                    console.log('🔄 收到重置表單元素事件');
                    this.resetFormElements();
                });
                
                // 監聽強制 UI 更新事件
                Livewire.on('force-ui-update', () => {
                    setTimeout(() => {
                        this.showResetButton = false;
                        console.log('🔄 強制隱藏重置按鈕');
                    }, 100);
                });
            }
            
            this.checkFilters();
            
            // 監聽輸入變化
            document.addEventListener('input', () => {
                setTimeout(() => this.checkFilters(), 100);
            });
            
            document.addEventListener('change', () => {
                setTimeout(() => this.checkFilters(), 100);
            });
        },
        
        checkFilters() {
            // 檢查搜尋框
            const searchInputs = document.querySelectorAll('input[wire\\:model\\.live="search"]');
            let hasSearch = false;
            searchInputs.forEach(input => {
                if (input.value.trim() !== '') {
                    hasSearch = true;
                }
            });
            
            // 檢查篩選器下拉選單
            const filterSelects = document.querySelectorAll('select[wire\\:model\\.live*="Filter"]');
            let hasFilter = false;
            filterSelects.forEach(select => {
                if (select.value !== 'all' && select.value !== '') {
                    hasFilter = true;
                }
            });
            
            // 檢查日期輸入
            const dateInputs = document.querySelectorAll('input[type="date"][wire\\:model\\.live]');
            let hasDate = false;
            dateInputs.forEach(input => {
                if (input.value !== '') {
                    hasDate = true;
                }
            });
            
            this.showResetButton = hasSearch || hasFilter || hasDate;
            
            console.log('🔍 檢查篩選狀態:', {
                hasSearch,
                hasFilter,
                hasDate,
                showResetButton: this.showResetButton
            });
        },
        
        resetFormElements() {
            console.log('🔄 開始重置表單元素');
            
            // 重置所有搜尋框
            const searchInputs = document.querySelectorAll('input[wire\\:model\\.live="search"]');
            searchInputs.forEach(input => {
                input.value = '';
                input.dispatchEvent(new Event('input', { bubbles: true }));
                input.blur();
            });
            
            // 重置所有篩選下拉選單
            const filterSelects = document.querySelectorAll('select[wire\\:model\\.live*="Filter"]');
            filterSelects.forEach(select => {
                select.value = 'all';
                select.dispatchEvent(new Event('change', { bubbles: true }));
            });
            
            // 重置日期輸入
            const dateInputs = document.querySelectorAll('input[type="date"][wire\\:model\\.live]');
            dateInputs.forEach(input => {
                input.value = '';
                input.dispatchEvent(new Event('input', { bubbles: true }));
            });
            
            // 重置其他輸入
            const otherInputs = document.querySelectorAll('input[wire\\:model\\.live]:not([wire\\:model\\.live="search"]):not([type="date"])');
            otherInputs.forEach(input => {
                if (input.type === 'checkbox') {
                    input.checked = false;
                } else {
                    input.value = '';
                }
                input.dispatchEvent(new Event('input', { bubbles: true }));
            });
            
            // 更新重置按鈕狀態
            setTimeout(() => {
                this.checkFilters();
                console.log('✅ 表單元素重置完成');
            }, 100);
        }
    }
}

// 分頁功能修復
function paginationController() {
    return {
        init() {
            console.log('🔧 分頁控制器初始化');
            
            // 監聽每頁顯示筆數更新事件
            if (window.Livewire) {
                Livewire.on('per-page-updated', (data) => {
                    console.log('📄 每頁顯示筆數已更新:', data.perPage);
                });
            }
        }
    }
}

// 表單驗證修復
function formValidationController() {
    return {
        init() {
            console.log('🔧 表單驗證控制器初始化');
            
            // 監聽表單提交
            document.addEventListener('submit', (e) => {
                const form = e.target;
                if (form.hasAttribute('wire:submit')) {
                    console.log('📝 Livewire 表單提交');
                    
                    // 添加載入狀態
                    const submitButton = form.querySelector('button[type="submit"]');
                    if (submitButton) {
                        submitButton.disabled = true;
                        submitButton.innerHTML = '<span class="animate-spin">⏳</span> 處理中...';
                        
                        // 5秒後恢復按鈕狀態（防止卡住）
                        setTimeout(() => {
                            submitButton.disabled = false;
                            submitButton.innerHTML = submitButton.getAttribute('data-original-text') || '提交';
                        }, 5000);
                    }
                }
            });
        }
    }
}

// Toast 通知系統
function toastController() {
    return {
        toasts: [],
        
        init() {
            console.log('🔧 Toast 控制器初始化');
            
            if (window.Livewire) {
                Livewire.on('show-toast', (data) => {
                    this.showToast(data.type, data.message, data.timeout || 5000);
                });
            }
        },
        
        showToast(type, message, timeout = 5000) {
            const toast = {
                id: Date.now(),
                type: type,
                message: message,
                timeout: timeout
            };
            
            this.toasts.push(toast);
            
            // 自動移除
            setTimeout(() => {
                this.removeToast(toast.id);
            }, timeout);
        },
        
        removeToast(id) {
            this.toasts = this.toasts.filter(toast => toast.id !== id);
        },
        
        getToastClass(type) {
            const baseClass = 'fixed top-4 right-4 p-4 rounded-md shadow-lg z-50 transition-all duration-300';
            const typeClasses = {
                'success': 'bg-green-500 text-white',
                'error': 'bg-red-500 text-white',
                'warning': 'bg-yellow-500 text-black',
                'info': 'bg-blue-500 text-white'
            };
            
            return `${baseClass} ${typeClasses[type] || typeClasses.info}`;
        }
    }
}

// 權限檢查修復
function permissionController() {
    return {
        init() {
            console.log('🔧 權限控制器初始化');
            
            // 檢查所有需要權限的元素
            const permissionElements = document.querySelectorAll('[data-permission]');
            permissionElements.forEach(element => {
                const requiredPermission = element.getAttribute('data-permission');
                
                // 這裡可以添加權限檢查邏輯
                // 如果沒有權限，隱藏或禁用元素
                if (!this.hasPermission(requiredPermission)) {
                    element.style.display = 'none';
                }
            });
        },
        
        hasPermission(permission) {
            // 這裡應該從後端獲取使用者權限
            // 暫時返回 true，實際應用中需要實作
            return true;
        }
    }
}

// 全域初始化
document.addEventListener('DOMContentLoaded', function() {
    console.log('🚀 Livewire 修復腳本載入完成');
    
    // 等待 Livewire 載入
    if (window.Livewire) {
        initializeControllers();
    } else {
        // 如果 Livewire 還沒載入，等待一下
        setTimeout(() => {
            if (window.Livewire) {
                initializeControllers();
            }
        }, 1000);
    }
});

function initializeControllers() {
    console.log('🔧 初始化所有控制器');
    
    // 這些控制器會在 Alpine.js 中使用
    window.resetButtonController = resetButtonController;
    window.paginationController = paginationController;
    window.formValidationController = formValidationController;
    window.toastController = toastController;
    window.permissionController = permissionController;
    
    console.log('✅ 所有控制器初始化完成');
}

// 匯出給全域使用
window.LivewireResetFix = {
    resetButtonController,
    paginationController,
    formValidationController,
    toastController,
    permissionController
};