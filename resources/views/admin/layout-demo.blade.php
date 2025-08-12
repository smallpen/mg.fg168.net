<!DOCTYPE html>
<html lang="zh-TW" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>管理後台佈局系統演示</title>
    
    <!-- CSS -->
    @vite(['resources/css/app.css', 'resources/css/admin-layout.css'])
    
    <!-- Alpine.js -->
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    
    <!-- Livewire -->
    @livewireStyles
</head>
<body>
    
    <!-- 管理後台佈局元件 -->
    <livewire:admin.layout.admin-layout>
        
        <!-- 演示內容 -->
        <div class="space-y-6">
            
            <!-- 頁面標題 -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
                <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100 mb-4">
                    佈局系統演示
                </h1>
                <p class="text-gray-600 dark:text-gray-400">
                    這是管理後台佈局系統的演示頁面，展示了響應式設計、主題切換和各種互動功能。
                </p>
            </div>
            
            <!-- 功能展示卡片 -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                
                <!-- 響應式設計 -->
                <div class="admin-card p-6">
                    <div class="flex items-center mb-4">
                        <div class="w-10 h-10 bg-blue-100 dark:bg-blue-900 rounded-lg flex items-center justify-center mr-3">
                            <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                            </svg>
                        </div>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">響應式設計</h3>
                    </div>
                    <p class="text-gray-600 dark:text-gray-400 text-sm">
                        支援桌面、平板和手機三種佈局模式，自動適應不同螢幕尺寸。
                    </p>
                    <div class="mt-4 flex space-x-2">
                        <span class="admin-badge admin-badge-primary">桌面版</span>
                        <span class="admin-badge admin-badge-success">平板版</span>
                        <span class="admin-badge admin-badge-warning">手機版</span>
                    </div>
                </div>
                
                <!-- 主題系統 -->
                <div class="admin-card p-6">
                    <div class="flex items-center mb-4">
                        <div class="w-10 h-10 bg-purple-100 dark:bg-purple-900 rounded-lg flex items-center justify-center mr-3">
                            <svg class="w-6 h-6 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zM21 5a2 2 0 00-2-2h-4a2 2 0 00-2 2v12a4 4 0 004 4h4a2 2 0 002-2V5z"></path>
                            </svg>
                        </div>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">主題系統</h3>
                    </div>
                    <p class="text-gray-600 dark:text-gray-400 text-sm">
                        支援亮色和暗色主題，可手動切換或跟隨系統設定。
                    </p>
                    <div class="mt-4">
                        <livewire:admin.layout.theme-toggle />
                    </div>
                </div>
                
                <!-- 導航選單 -->
                <div class="admin-card p-6">
                    <div class="flex items-center mb-4">
                        <div class="w-10 h-10 bg-green-100 dark:bg-green-900 rounded-lg flex items-center justify-center mr-3">
                            <svg class="w-6 h-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                            </svg>
                        </div>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">導航選單</h3>
                    </div>
                    <p class="text-gray-600 dark:text-gray-400 text-sm">
                        多層級選單結構，支援權限控制和收合功能。
                    </p>
                    <div class="mt-4">
                        <button class="admin-btn admin-btn-secondary text-sm" onclick="toggleSidebar()">
                            切換側邊欄
                        </button>
                    </div>
                </div>
                
                <!-- 動畫效果 -->
                <div class="admin-card p-6">
                    <div class="flex items-center mb-4">
                        <div class="w-10 h-10 bg-yellow-100 dark:bg-yellow-900 rounded-lg flex items-center justify-center mr-3">
                            <svg class="w-6 h-6 text-yellow-600 dark:text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                            </svg>
                        </div>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">動畫效果</h3>
                    </div>
                    <p class="text-gray-600 dark:text-gray-400 text-sm">
                        流暢的過渡動畫和載入效果，提升使用者體驗。
                    </p>
                    <div class="mt-4">
                        <div class="loading-pulse w-full h-2 bg-gray-200 dark:bg-gray-700 rounded"></div>
                    </div>
                </div>
                
                <!-- 鍵盤快捷鍵 -->
                <div class="admin-card p-6">
                    <div class="flex items-center mb-4">
                        <div class="w-10 h-10 bg-red-100 dark:bg-red-900 rounded-lg flex items-center justify-center mr-3">
                            <svg class="w-6 h-6 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                            </svg>
                        </div>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">鍵盤快捷鍵</h3>
                    </div>
                    <p class="text-gray-600 dark:text-gray-400 text-sm">
                        支援多種鍵盤快捷鍵，提高操作效率。
                    </p>
                    <div class="mt-4 space-y-1 text-xs text-gray-500 dark:text-gray-400">
                        <div>Ctrl+B: 切換側邊欄</div>
                        <div>Alt+T: 切換主題</div>
                        <div>ESC: 關閉選單</div>
                    </div>
                </div>
                
                <!-- 無障礙功能 -->
                <div class="admin-card p-6">
                    <div class="flex items-center mb-4">
                        <div class="w-10 h-10 bg-indigo-100 dark:bg-indigo-900 rounded-lg flex items-center justify-center mr-3">
                            <svg class="w-6 h-6 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
                            </svg>
                        </div>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">無障礙功能</h3>
                    </div>
                    <p class="text-gray-600 dark:text-gray-400 text-sm">
                        支援鍵盤導航、螢幕閱讀器和高對比模式。
                    </p>
                    <div class="mt-4">
                        <span class="admin-badge admin-badge-success">WCAG 2.1</span>
                    </div>
                </div>
                
            </div>
            
            <!-- 互動演示 -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
                <h2 class="text-xl font-bold text-gray-900 dark:text-gray-100 mb-4">
                    互動演示
                </h2>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    
                    <!-- 按鈕演示 -->
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-3">按鈕樣式</h3>
                        <div class="space-y-3">
                            <div class="flex space-x-3">
                                <button class="admin-btn admin-btn-primary">主要按鈕</button>
                                <button class="admin-btn admin-btn-secondary">次要按鈕</button>
                            </div>
                            <div class="flex space-x-3">
                                <button class="admin-btn admin-btn-primary" disabled>停用按鈕</button>
                                <button class="admin-btn admin-btn-secondary touch-feedback">觸控回饋</button>
                            </div>
                        </div>
                    </div>
                    
                    <!-- 表單演示 -->
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-3">表單元件</h3>
                        <div class="space-y-3">
                            <input type="text" class="admin-input" placeholder="輸入框範例">
                            <div class="flex space-x-3">
                                <input type="text" class="admin-input flex-1" placeholder="搜尋...">
                                <button class="admin-btn admin-btn-primary">搜尋</button>
                            </div>
                        </div>
                    </div>
                    
                </div>
            </div>
            
        </div>
        
    </livewire:admin.layout.admin-layout>
    
    <!-- JavaScript -->
    @vite(['resources/js/app.js', 'resources/js/admin-layout.js'])
    @livewireScripts
    
    <script>
        // 演示功能
        function toggleSidebar() {
            if (window.adminLayout) {
                window.adminLayout.toggleSidebar();
            }
        }
        
        // 顯示通知
        function showNotification(message, type = 'info') {
            const notification = document.createElement('div');
            notification.className = `notification ${type} show`;
            notification.innerHTML = `
                <div class="flex items-center justify-between">
                    <span>${message}</span>
                    <button onclick="this.parentElement.parentElement.remove()" class="ml-2 text-gray-400 hover:text-gray-600">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
            `;
            
            let container = document.querySelector('.notification-container');
            if (!container) {
                container = document.createElement('div');
                container.className = 'notification-container';
                document.body.appendChild(container);
            }
            
            container.appendChild(notification);
            
            setTimeout(() => {
                notification.remove();
            }, 5000);
        }
        
        // 監聽佈局事件
        window.addEventListener('admin:viewport-changed', (e) => {
            const { isMobile, isTablet } = e.detail;
            let deviceType = '桌面版';
            if (isMobile) deviceType = '手機版';
            else if (isTablet) deviceType = '平板版';
            
            showNotification(`已切換到${deviceType}佈局`, 'info');
        });
        
        window.addEventListener('admin:theme-changed', (e) => {
            const { theme } = e.detail;
            const themeName = theme === 'dark' ? '暗色' : '亮色';
            showNotification(`已切換到${themeName}主題`, 'success');
        });
    </script>
    
</body>
</html>