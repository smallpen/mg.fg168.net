/**
 * Service Worker 管理器
 * 
 * 負責註冊、更新和管理 Service Worker
 */

class ServiceWorkerManager {
    constructor() {
        this.registration = null;
        this.isOnline = navigator.onLine;
        this.updateAvailable = false;
        
        this.init();
    }

    /**
     * 初始化 Service Worker 管理器
     */
    async init() {
        if ('serviceWorker' in navigator) {
            try {
                await this.registerServiceWorker();
                this.setupEventListeners();
                this.checkForUpdates();
            } catch (error) {
                console.error('Service Worker 初始化失敗:', error);
            }
        } else {
            console.warn('此瀏覽器不支援 Service Worker');
        }
    }

    /**
     * 註冊 Service Worker
     */
    async registerServiceWorker() {
        try {
            this.registration = await navigator.serviceWorker.register('/sw.js', {
                scope: '/admin/'
            });
            
            console.log('Service Worker 註冊成功:', this.registration.scope);
            
            // 監聽 Service Worker 狀態變更
            this.registration.addEventListener('updatefound', () => {
                this.handleUpdateFound();
            });
            
            return this.registration;
        } catch (error) {
            console.error('Service Worker 註冊失敗:', error);
            throw error;
        }
    }

    /**
     * 設定事件監聽器
     */
    setupEventListeners() {
        // 監聽網路狀態變更
        window.addEventListener('online', () => {
            this.isOnline = true;
            this.showNetworkStatus('online');
            this.syncWhenOnline();
        });

        window.addEventListener('offline', () => {
            this.isOnline = false;
            this.showNetworkStatus('offline');
        });

        // 監聽 Service Worker 訊息
        navigator.serviceWorker.addEventListener('message', (event) => {
            this.handleServiceWorkerMessage(event);
        });

        // 監聽頁面可見性變更
        document.addEventListener('visibilitychange', () => {
            if (!document.hidden) {
                this.checkForUpdates();
            }
        });
    }

    /**
     * 處理 Service Worker 更新
     */
    handleUpdateFound() {
        const newWorker = this.registration.installing;
        
        newWorker.addEventListener('statechange', () => {
            if (newWorker.state === 'installed') {
                if (navigator.serviceWorker.controller) {
                    // 有新版本可用
                    this.updateAvailable = true;
                    this.showUpdateNotification();
                } else {
                    // 首次安裝
                    console.log('Service Worker 首次安裝完成');
                }
            }
        });
    }

    /**
     * 顯示更新通知
     */
    showUpdateNotification() {
        // 使用 Alpine.js 或其他方式顯示更新通知
        const notification = document.createElement('div');
        notification.className = 'fixed top-4 right-4 bg-blue-500 text-white p-4 rounded-lg shadow-lg z-50';
        notification.innerHTML = `
            <div class="flex items-center space-x-3">
                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                </svg>
                <div>
                    <p class="font-medium">有新版本可用</p>
                    <p class="text-sm opacity-90">點擊重新載入以更新</p>
                </div>
                <button onclick="window.swManager.applyUpdate()" class="bg-white text-blue-500 px-3 py-1 rounded text-sm font-medium hover:bg-gray-100">
                    更新
                </button>
                <button onclick="this.parentElement.parentElement.remove()" class="text-white hover:text-gray-200">
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                    </svg>
                </button>
            </div>
        `;
        
        document.body.appendChild(notification);
        
        // 5 秒後自動隱藏
        setTimeout(() => {
            if (notification.parentElement) {
                notification.remove();
            }
        }, 5000);
    }

    /**
     * 應用更新
     */
    async applyUpdate() {
        if (this.registration && this.registration.waiting) {
            // 告訴等待中的 Service Worker 跳過等待
            this.registration.waiting.postMessage({ type: 'SKIP_WAITING' });
            
            // 重新載入頁面
            window.location.reload();
        }
    }

    /**
     * 檢查更新
     */
    async checkForUpdates() {
        if (this.registration) {
            try {
                await this.registration.update();
            } catch (error) {
                console.error('檢查更新失敗:', error);
            }
        }
    }

    /**
     * 顯示網路狀態
     */
    showNetworkStatus(status) {
        const statusElement = document.getElementById('network-status');
        
        if (statusElement) {
            statusElement.className = status === 'online' 
                ? 'network-status online' 
                : 'network-status offline';
            statusElement.textContent = status === 'online' ? '已連線' : '離線模式';
        }

        // 顯示臨時通知
        const notification = document.createElement('div');
        notification.className = `fixed bottom-4 left-4 p-3 rounded-lg text-white z-50 ${
            status === 'online' ? 'bg-green-500' : 'bg-red-500'
        }`;
        notification.textContent = status === 'online' ? '網路連線已恢復' : '網路連線中斷，進入離線模式';
        
        document.body.appendChild(notification);
        
        setTimeout(() => {
            notification.remove();
        }, 3000);
    }

    /**
     * 網路恢復時同步資料
     */
    async syncWhenOnline() {
        if (this.isOnline && this.registration) {
            try {
                // 觸發背景同步
                if ('sync' in window.ServiceWorkerRegistration.prototype) {
                    await this.registration.sync.register('background-sync');
                }
                
                // 重新載入關鍵資料
                if (window.Livewire) {
                    window.Livewire.emit('network-restored');
                }
            } catch (error) {
                console.error('同步失敗:', error);
            }
        }
    }

    /**
     * 處理 Service Worker 訊息
     */
    handleServiceWorkerMessage(event) {
        const { data } = event;
        
        switch (data.type) {
            case 'CACHE_UPDATED':
                console.log('快取已更新:', data.url);
                break;
                
            case 'OFFLINE_FALLBACK':
                this.showOfflineFallback();
                break;
                
            default:
                console.log('收到 Service Worker 訊息:', data);
        }
    }

    /**
     * 顯示離線備用內容
     */
    showOfflineFallback() {
        const fallback = document.createElement('div');
        fallback.className = 'fixed inset-0 bg-gray-100 flex items-center justify-center z-50';
        fallback.innerHTML = `
            <div class="text-center">
                <svg class="w-16 h-16 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192L5.636 18.364M12 2.25a9.75 9.75 0 100 19.5 9.75 9.75 0 000-19.5z"></path>
                </svg>
                <h2 class="text-xl font-semibold text-gray-700 mb-2">網路連線中斷</h2>
                <p class="text-gray-500 mb-4">請檢查您的網路連線並重試</p>
                <button onclick="window.location.reload()" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">
                    重新載入
                </button>
            </div>
        `;
        
        document.body.appendChild(fallback);
    }

    /**
     * 取得快取統計
     */
    async getCacheStats() {
        if (this.registration && this.registration.active) {
            return new Promise((resolve) => {
                const messageChannel = new MessageChannel();
                messageChannel.port1.onmessage = (event) => {
                    resolve(event.data);
                };
                
                this.registration.active.postMessage(
                    { type: 'CACHE_STATS' },
                    [messageChannel.port2]
                );
            });
        }
        
        return null;
    }

    /**
     * 清除快取
     */
    async clearCache() {
        if (this.registration && this.registration.active) {
            return new Promise((resolve) => {
                const messageChannel = new MessageChannel();
                messageChannel.port1.onmessage = (event) => {
                    resolve(event.data);
                };
                
                this.registration.active.postMessage(
                    { type: 'CLEAR_CACHE' },
                    [messageChannel.port2]
                );
            });
        }
        
        return null;
    }

    /**
     * 取得網路狀態
     */
    getNetworkStatus() {
        return {
            online: this.isOnline,
            effectiveType: navigator.connection?.effectiveType || 'unknown',
            downlink: navigator.connection?.downlink || 0,
            rtt: navigator.connection?.rtt || 0
        };
    }

    /**
     * 解除註冊 Service Worker
     */
    async unregister() {
        if (this.registration) {
            const result = await this.registration.unregister();
            console.log('Service Worker 解除註冊:', result);
            return result;
        }
        
        return false;
    }
}

// 全域實例
window.ServiceWorkerManager = ServiceWorkerManager;

// 自動初始化
document.addEventListener('DOMContentLoaded', () => {
    window.swManager = new ServiceWorkerManager();
});

export default ServiceWorkerManager;