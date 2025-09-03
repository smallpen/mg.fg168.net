@extends('layouts.admin')

@section('title', '活動記錄')

@section('content')
    <div class="space-y-6">
        <!-- 頁面標題 -->
        <div class="flex justify-between items-center">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                    活動記錄
                </h1>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                    監控系統使用情況和安全狀態
                </p>
            </div>
        </div>

        <!-- 活動記錄列表 -->
        <livewire:admin.activities.activity-list />
        
        <!-- 活動詳情元件 -->
        <livewire:admin.activities.activity-detail />
    </div>
@endsection

@push('scripts')
<script>
    // 即時更新和自動重新整理
    let autoRefreshInterval = null;
    
    // 處理檔案下載
    document.addEventListener('livewire:init', () => {
        Livewire.on('download-file', (event) => {
            console.log('🔥 收到 download-file 事件:', event);
            
            // Livewire 3.0 將事件資料包裝在陣列中
            const eventData = Array.isArray(event) ? event[0] : event;
            
            console.log('📦 處理事件資料:', eventData);
            
            // 支援兩種格式：新格式 (url + filename) 和舊格式 (filePath)
            if (eventData.url) {
                // 新格式：使用 URL 直接下載
                console.log('🔗 使用 URL 下載:', eventData.url);
                const link = document.createElement('a');
                link.href = eventData.url;
                link.download = eventData.filename || '';
                link.target = '_blank'; // 在新視窗開啟，確保下載
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
                
                // 顯示下載提示
                console.log('✅ 下載已觸發:', eventData.filename);
            } else if (eventData.filePath) {
                // 舊格式：使用 storage 路徑
                console.log('📁 使用 filePath 下載:', eventData.filePath);
                const link = document.createElement('a');
                link.href = `/storage/${eventData.filePath}`;
                link.download = eventData.filePath.split('/').pop();
                link.target = '_blank';
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
            } else {
                console.error('❌ 無效的下載事件資料:', eventData);
            }
        });

        // 處理活動詳情對話框
        Livewire.on('open-activity-detail', (event) => {
            // 活動詳情對話框會自動開啟，這裡不需要額外處理
            console.log('開啟活動詳情 ID: ' + event.activityId);
        });

        // 處理通知
        Livewire.on('notify', (event) => {
            const notification = event[0] || event;
            
            // 建立通知元素
            const notificationEl = document.createElement('div');
            notificationEl.className = `fixed top-4 right-4 z-50 max-w-sm w-full bg-white dark:bg-gray-800 shadow-lg rounded-lg pointer-events-auto ring-1 ring-black ring-opacity-5 overflow-hidden transform transition-all duration-300 ease-in-out translate-x-full`;
            
            // 設定通知內容
            const bgColor = {
                'success': 'bg-green-50 dark:bg-green-900/20',
                'error': 'bg-red-50 dark:bg-red-900/20',
                'warning': 'bg-yellow-50 dark:bg-yellow-900/20',
                'info': 'bg-blue-50 dark:bg-blue-900/20'
            }[notification.type] || 'bg-gray-50 dark:bg-gray-900/20';
            
            const textColor = {
                'success': 'text-green-800 dark:text-green-200',
                'error': 'text-red-800 dark:text-red-200',
                'warning': 'text-yellow-800 dark:text-yellow-200',
                'info': 'text-blue-800 dark:text-blue-200'
            }[notification.type] || 'text-gray-800 dark:text-gray-200';
            
            const iconSvg = {
                'success': '<svg class="h-5 w-5 text-green-400" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>',
                'error': '<svg class="h-5 w-5 text-red-400" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path></svg>',
                'warning': '<svg class="h-5 w-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path></svg>',
                'info': '<svg class="h-5 w-5 text-blue-400" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path></svg>'
            }[notification.type] || '<svg class="h-5 w-5 text-gray-400" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path></svg>';
            
            notificationEl.innerHTML = `
                <div class="p-4">
                    <div class="flex items-start">
                        <div class="flex-shrink-0">
                            ${iconSvg}
                        </div>
                        <div class="ml-3 w-0 flex-1">
                            <p class="text-sm font-medium ${textColor}">
                                ${notification.message}
                            </p>
                        </div>
                        <div class="ml-4 flex-shrink-0 flex">
                            <button class="inline-flex text-gray-400 hover:text-gray-500 focus:outline-none" onclick="this.closest('.fixed').remove()">
                                <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>
            `;
            
            // 添加到頁面
            document.body.appendChild(notificationEl);
            
            // 動畫進入
            setTimeout(() => {
                notificationEl.classList.remove('translate-x-full');
            }, 100);
            
            // 自動移除
            const timeout = notification.timeout || 5000;
            setTimeout(() => {
                notificationEl.classList.add('translate-x-full');
                setTimeout(() => {
                    if (notificationEl.parentNode) {
                        notificationEl.parentNode.removeChild(notificationEl);
                    }
                }, 300);
            }, timeout);
        });

        // 監聽即時監控狀態變化
        Livewire.on('real-time-mode-changed', (event) => {
            const isEnabled = event.enabled;
            
            if (isEnabled) {
                // 開啟自動重新整理（每30秒）
                autoRefreshInterval = setInterval(() => {
                    Livewire.dispatch('refresh-activities');
                }, 30000);
                
                // 顯示即時監控指示器
                showRealTimeIndicator();
            } else {
                // 關閉自動重新整理
                if (autoRefreshInterval) {
                    clearInterval(autoRefreshInterval);
                    autoRefreshInterval = null;
                }
                
                // 隱藏即時監控指示器
                hideRealTimeIndicator();
            }
        });

        // 無限滾動支援
        let isLoadingMore = false;
        
        function handleInfiniteScroll() {
            if (isLoadingMore) return;
            
            const scrollPosition = window.innerHeight + window.scrollY;
            const documentHeight = document.documentElement.offsetHeight;
            
            // 當滾動到距離底部200px時觸發載入
            if (scrollPosition >= documentHeight - 200) {
                isLoadingMore = true;
                Livewire.dispatch('load-more-activities');
                
                setTimeout(() => {
                    isLoadingMore = false;
                }, 1000);
            }
        }
        
        // 監聽滾動事件（節流處理）
        let scrollTimeout;
        window.addEventListener('scroll', () => {
            if (scrollTimeout) {
                clearTimeout(scrollTimeout);
            }
            scrollTimeout = setTimeout(handleInfiniteScroll, 100);
        });

        // 鍵盤快捷鍵支援
        document.addEventListener('keydown', (e) => {
            // Ctrl/Cmd + R: 重新整理
            if ((e.ctrlKey || e.metaKey) && e.key === 'r') {
                e.preventDefault();
                Livewire.dispatch('refresh-activities');
            }
            
            // Ctrl/Cmd + F: 聚焦搜尋框
            if ((e.ctrlKey || e.metaKey) && e.key === 'f') {
                e.preventDefault();
                const searchInput = document.querySelector('input[wire\\:model\\.live\\.debounce\\.300ms="search"]');
                if (searchInput) {
                    searchInput.focus();
                }
            }
            
            // Escape: 清除篩選
            if (e.key === 'Escape') {
                Livewire.dispatch('clear-all-filters');
            }
        });
    });

    // 顯示即時監控指示器
    function showRealTimeIndicator() {
        const indicator = document.createElement('div');
        indicator.id = 'real-time-indicator';
        indicator.className = 'fixed top-4 left-4 z-50 flex items-center px-3 py-2 bg-green-500 text-white text-sm rounded-lg shadow-lg';
        indicator.innerHTML = `
            <div class="w-2 h-2 bg-white rounded-full mr-2 animate-pulse"></div>
            <span>即時監控中</span>
        `;
        document.body.appendChild(indicator);
    }

    // 隱藏即時監控指示器
    function hideRealTimeIndicator() {
        const indicator = document.getElementById('real-time-indicator');
        if (indicator) {
            indicator.remove();
        }
    }

    // 頁面可見性變化處理
    document.addEventListener('visibilitychange', () => {
        if (document.hidden) {
            // 頁面隱藏時暫停自動重新整理
            if (autoRefreshInterval) {
                clearInterval(autoRefreshInterval);
            }
        } else {
            // 頁面顯示時恢復自動重新整理（如果即時監控開啟）
            const realTimeButton = document.querySelector('[wire\\:click="toggleRealTime"]');
            if (realTimeButton && realTimeButton.textContent.includes('監控中')) {
                autoRefreshInterval = setInterval(() => {
                    Livewire.dispatch('refresh-activities');
                }, 30000);
            }
        }
    });

    // 確認檢視詳情對話框
    function confirmViewDetail(activityId) {
        // 建立自定義確認對話框
        const modal = document.createElement('div');
        modal.className = 'fixed inset-0 z-50 overflow-y-auto';
        modal.innerHTML = `
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"></div>
                <div class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                    <div class="bg-white dark:bg-gray-800 px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <div class="sm:flex sm:items-start">
                            <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-blue-100 dark:bg-blue-900/20 sm:mx-0 sm:h-10 sm:w-10">
                                <svg class="h-6 w-6 text-blue-600 dark:text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                </svg>
                            </div>
                            <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                                <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white">檢視活動詳情</h3>
                                <div class="mt-2">
                                    <p class="text-sm text-gray-500 dark:text-gray-400">確定要檢視此活動記錄的詳細資訊嗎？</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gray-50 dark:bg-gray-700 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                        <button type="button" class="confirm-btn w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:ml-3 sm:w-auto sm:text-sm">
                            確定檢視
                        </button>
                        <button type="button" class="cancel-btn mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 dark:border-gray-600 shadow-sm px-4 py-2 bg-white dark:bg-gray-800 text-base font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                            取消
                        </button>
                    </div>
                </div>
            </div>
        `;
        
        document.body.appendChild(modal);
        
        // 綁定事件
        const confirmBtn = modal.querySelector('.confirm-btn');
        const cancelBtn = modal.querySelector('.cancel-btn');
        const backdrop = modal.querySelector('.fixed.inset-0');
        
        const closeModal = () => {
            document.body.removeChild(modal);
        };
        
        confirmBtn.addEventListener('click', () => {
            closeModal();
            // 使用事件分發而不是直接方法調用
            Livewire.dispatchTo('admin.activities.activity-detail', 'viewDetail', { activityId: activityId });
        });
        
        cancelBtn.addEventListener('click', closeModal);
        backdrop.addEventListener('click', closeModal);
        
        // ESC 鍵關閉
        const handleEscape = (e) => {
            if (e.key === 'Escape') {
                closeModal();
                document.removeEventListener('keydown', handleEscape);
            }
        };
        document.addEventListener('keydown', handleEscape);
    }
</script>
@endpush