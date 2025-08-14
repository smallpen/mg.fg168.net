/**
 * 錯誤處理 JavaScript 模組
 * 
 * 提供前端錯誤處理和使用者通知功能
 */

class ErrorHandler {
    constructor() {
        this.retryAttempts = new Map();
        this.maxRetries = 3;
        this.retryDelay = 1000;
        this.init();
    }

    /**
     * 初始化錯誤處理器
     */
    init() {
        this.setupLivewireErrorListeners();
        this.setupGlobalErrorHandler();
        this.setupNetworkErrorHandler();
    }

    /**
     * 設定 Livewire 錯誤監聽器
     */
    setupLivewireErrorListeners() {
        // 監聽各種錯誤事件
        document.addEventListener('livewire:init', () => {
            // 權限錯誤
            Livewire.on('show-permission-error', (data) => {
                this.showPermissionError(data);
            });

            // 驗證錯誤
            Livewire.on('show-validation-errors', (data) => {
                this.showValidationErrors(data);
            });

            // 網路錯誤
            Livewire.on('show-network-error', (data) => {
                this.showNetworkError(data);
            });

            // 資料庫錯誤
            Livewire.on('show-database-error', (data) => {
                this.showDatabaseError(data);
            });

            // 操作錯誤
            Livewire.on('show-operation-error', (data) => {
                this.showOperationError(data);
            });

            // 系統錯誤
            Livewire.on('show-system-error', (data) => {
                this.showSystemError(data);
            });

            // 重試操作
            document.addEventListener('retry-operation', (event) => {
                this.handleRetryOperation(event);
            });
        });
    }

    /**
     * 設定全域錯誤處理器
     */
    setupGlobalErrorHandler() {
        // 捕獲未處理的 JavaScript 錯誤
        window.addEventListener('error', (event) => {
            this.logClientError({
                message: event.message,
                filename: event.filename,
                lineno: event.lineno,
                colno: event.colno,
                error: event.error?.stack,
                type: 'javascript_error'
            });
        });

        // 捕獲未處理的 Promise 拒絕
        window.addEventListener('unhandledrejection', (event) => {
            this.logClientError({
                message: event.reason?.message || 'Unhandled Promise Rejection',
                error: event.reason?.stack,
                type: 'promise_rejection'
            });
        });
    }

    /**
     * 設定網路錯誤處理器
     */
    setupNetworkErrorHandler() {
        // 監聽網路狀態變化
        window.addEventListener('online', () => {
            this.showToast('網路連線已恢復', 'success');
            this.clearNetworkErrorNotifications();
        });

        window.addEventListener('offline', () => {
            this.showToast('網路連線中斷，請檢查網路設定', 'error', 0);
        });
    }

    /**
     * 顯示權限錯誤
     */
    showPermissionError(data) {
        this.showModal({
            type: 'error',
            title: data.title || '權限不足',
            message: data.message,
            icon: data.icon || 'shield-exclamation',
            actions: data.actions || [
                { label: '返回', action: 'go_back' }
            ],
            dismissible: true
        });
    }

    /**
     * 顯示驗證錯誤
     */
    showValidationErrors(data) {
        const errorContainer = document.getElementById('validation-errors');
        if (errorContainer) {
            this.renderValidationErrors(errorContainer, data);
        } else {
            this.showToast(data.message || '資料驗證失敗', 'error');
        }
    }

    /**
     * 顯示網路錯誤
     */
    showNetworkError(data) {
        this.showModal({
            type: 'error',
            title: data.title || '網路連線異常',
            message: data.message,
            icon: data.icon || 'wifi-slash',
            actions: [
                { label: '重試', action: 'retry', delay: data.retry_delay || 3000 },
                { label: '重新整理頁面', action: 'refresh' },
                ...(data.actions || [])
            ],
            retry: true,
            retryDelay: data.retry_delay || 3000
        });
    }

    /**
     * 顯示資料庫錯誤
     */
    showDatabaseError(data) {
        this.showModal({
            type: 'error',
            title: data.title || '資料處理錯誤',
            message: data.message,
            icon: data.icon || 'database',
            actions: data.actions || [
                { label: '重試', action: 'retry' },
                { label: '返回', action: 'go_back' }
            ],
            retry: data.retry || false
        });
    }

    /**
     * 顯示操作錯誤
     */
    showOperationError(data) {
        this.showToast(data.message || '操作失敗', 'error');
    }

    /**
     * 顯示系統錯誤
     */
    showSystemError(data) {
        this.showModal({
            type: 'error',
            title: data.title || '系統錯誤',
            message: data.message || '系統發生未預期的錯誤，請稍後再試',
            icon: data.icon || 'exclamation-triangle',
            actions: [
                { label: '重新整理', action: 'refresh' },
                { label: '返回首頁', action: 'redirect', url: '/admin/dashboard' },
                { label: '聯繫支援', action: 'contact_support' }
            ]
        });
    }

    /**
     * 顯示模態對話框
     */
    showModal(options) {
        const modal = this.createModal(options);
        document.body.appendChild(modal);
        
        // 顯示模態框
        setTimeout(() => {
            modal.classList.remove('opacity-0');
            modal.classList.add('opacity-100');
        }, 10);
    }

    /**
     * 建立模態對話框元素
     */
    createModal(options) {
        const modal = document.createElement('div');
        modal.className = 'fixed inset-0 z-50 overflow-y-auto opacity-0 transition-opacity duration-300';
        modal.innerHTML = `
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" onclick="this.parentElement.parentElement.remove()"></div>
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>
                <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                    <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <div class="sm:flex sm:items-start">
                            <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-red-100 sm:mx-0 sm:h-10 sm:w-10">
                                ${this.getIconSvg(options.icon || 'exclamation-triangle')}
                            </div>
                            <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                                <h3 class="text-lg leading-6 font-medium text-gray-900">${options.title}</h3>
                                <div class="mt-2">
                                    <p class="text-sm text-gray-500">${options.message}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                        ${this.createActionButtons(options.actions || [])}
                    </div>
                </div>
            </div>
        `;

        // 綁定事件
        this.bindModalEvents(modal, options);
        
        return modal;
    }

    /**
     * 建立操作按鈕
     */
    createActionButtons(actions) {
        return actions.map(action => {
            const buttonClass = action.action === 'retry' 
                ? 'bg-red-600 hover:bg-red-700 text-white'
                : 'bg-white hover:bg-gray-50 text-gray-900 border border-gray-300';
            
            return `
                <button type="button" 
                        data-action="${action.action}" 
                        data-url="${action.url || ''}"
                        data-delay="${action.delay || 0}"
                        class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 text-base font-medium focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 sm:ml-3 sm:w-auto sm:text-sm ${buttonClass}">
                    ${action.label}
                </button>
            `;
        }).join('');
    }

    /**
     * 綁定模態框事件
     */
    bindModalEvents(modal, options) {
        const buttons = modal.querySelectorAll('button[data-action]');
        buttons.forEach(button => {
            button.addEventListener('click', (e) => {
                const action = e.target.dataset.action;
                const url = e.target.dataset.url;
                const delay = parseInt(e.target.dataset.delay) || 0;
                
                this.handleAction(action, { url, delay });
                modal.remove();
            });
        });
    }

    /**
     * 處理操作
     */
    handleAction(action, options = {}) {
        switch (action) {
            case 'retry':
                if (options.delay > 0) {
                    setTimeout(() => {
                        this.dispatchRetryEvent();
                    }, options.delay);
                } else {
                    this.dispatchRetryEvent();
                }
                break;
            case 'redirect':
                if (options.url) {
                    window.location.href = options.url;
                }
                break;
            case 'go_back':
                window.history.back();
                break;
            case 'refresh':
                window.location.reload();
                break;
            case 'contact_support':
                this.openSupportContact();
                break;
            default:
                document.dispatchEvent(new CustomEvent(action));
        }
    }

    /**
     * 發送重試事件
     */
    dispatchRetryEvent() {
        document.dispatchEvent(new CustomEvent('retry-operation'));
        Livewire.dispatch('retry-last-operation');
    }

    /**
     * 處理重試操作
     */
    handleRetryOperation(event) {
        const operation = event.detail?.operation;
        if (operation) {
            const attempts = this.retryAttempts.get(operation) || 0;
            if (attempts < this.maxRetries) {
                this.retryAttempts.set(operation, attempts + 1);
                this.showToast(`重試中... (${attempts + 1}/${this.maxRetries})`, 'info');
            } else {
                this.showToast('重試次數已達上限', 'error');
            }
        }
    }

    /**
     * 顯示 Toast 通知
     */
    showToast(message, type = 'info', duration = 5000) {
        const toast = this.createToast(message, type);
        document.body.appendChild(toast);
        
        // 顯示動畫
        setTimeout(() => {
            toast.classList.remove('translate-x-full');
        }, 10);
        
        // 自動隱藏
        if (duration > 0) {
            setTimeout(() => {
                this.hideToast(toast);
            }, duration);
        }
    }

    /**
     * 建立 Toast 元素
     */
    createToast(message, type) {
        const toast = document.createElement('div');
        const typeClasses = {
            success: 'bg-green-500',
            error: 'bg-red-500',
            warning: 'bg-yellow-500',
            info: 'bg-blue-500'
        };
        
        toast.className = `fixed top-4 right-4 z-50 max-w-sm w-full ${typeClasses[type]} text-white px-6 py-4 rounded-lg shadow-lg transform translate-x-full transition-transform duration-300`;
        toast.innerHTML = `
            <div class="flex items-center justify-between">
                <span>${message}</span>
                <button onclick="this.parentElement.parentElement.remove()" class="ml-4 text-white hover:text-gray-200">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
        `;
        
        return toast;
    }

    /**
     * 隱藏 Toast
     */
    hideToast(toast) {
        toast.classList.add('translate-x-full');
        setTimeout(() => {
            toast.remove();
        }, 300);
    }

    /**
     * 取得圖示 SVG
     */
    getIconSvg(iconName) {
        const icons = {
            'exclamation-triangle': '<svg class="h-6 w-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"/></svg>',
            'shield-exclamation': '<svg class="h-6 w-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.618 5.984A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016zM12 9v2m0 4h.01"/></svg>',
            'wifi-slash': '<svg class="h-6 w-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 5.636l-12.728 12.728m0 0L12 12m6.364 6.364L12 12m-6.364-6.364L12 12"/></svg>',
            'database': '<svg class="h-6 w-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4"/></svg>'
        };
        
        return icons[iconName] || icons['exclamation-triangle'];
    }

    /**
     * 記錄客戶端錯誤
     */
    logClientError(errorData) {
        // 發送錯誤到後端日誌系統
        fetch('/admin/api/log-client-error', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
            },
            body: JSON.stringify({
                ...errorData,
                url: window.location.href,
                userAgent: navigator.userAgent,
                timestamp: new Date().toISOString()
            })
        }).catch(() => {
            // 如果無法發送到後端，至少在控制台記錄
            console.error('Client Error:', errorData);
        });
    }

    /**
     * 清除網路錯誤通知
     */
    clearNetworkErrorNotifications() {
        const networkErrors = document.querySelectorAll('[data-error-type="network"]');
        networkErrors.forEach(error => error.remove());
    }

    /**
     * 開啟支援聯繫
     */
    openSupportContact() {
        // 這裡可以實作支援聯繫功能
        this.showToast('請聯繫系統管理員尋求協助', 'info');
    }

    /**
     * 渲染驗證錯誤
     */
    renderValidationErrors(container, data) {
        container.innerHTML = `
            <div class="bg-red-50 border border-red-200 rounded-lg p-4">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                        </svg>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-red-800">${data.title}</h3>
                        <div class="mt-2 text-sm text-red-700">
                            <ul class="list-disc list-inside space-y-1">
                                ${Object.entries(data.errors).map(([field, error]) => 
                                    `<li>${error.field}: ${error.first_message}</li>`
                                ).join('')}
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        `;
    }
}

// 初始化錯誤處理器
document.addEventListener('DOMContentLoaded', () => {
    window.errorHandler = new ErrorHandler();
});

export default ErrorHandler;