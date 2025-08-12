@extends('layouts.admin')

@section('title', '載入狀態管理示例')

@section('content')
<div class="space-y-6">
    <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
        <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">載入狀態管理系統示例</h2>
        
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <!-- 載入覆蓋層示例 -->
            <div class="space-y-4">
                <h3 class="text-md font-medium text-gray-700 dark:text-gray-300">載入覆蓋層</h3>
                <div class="space-y-2">
                    <button type="button" 
                            class="w-full px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600 transition-colors"
                            onclick="showSpinnerLoading()">
                        顯示 Spinner 載入
                    </button>
                    <button type="button" 
                            class="w-full px-4 py-2 bg-green-500 text-white rounded hover:bg-green-600 transition-colors"
                            onclick="showProgressLoading()">
                        顯示進度條載入
                    </button>
                    <button type="button" 
                            class="w-full px-4 py-2 bg-red-500 text-white rounded hover:bg-red-600 transition-colors"
                            onclick="hideLoading()">
                        隱藏載入
                    </button>
                </div>
            </div>
            
            <!-- 操作回饋示例 -->
            <div class="space-y-4">
                <h3 class="text-md font-medium text-gray-700 dark:text-gray-300">操作回饋</h3>
                <div class="space-y-2">
                    <button type="button" 
                            class="w-full px-4 py-2 bg-green-500 text-white rounded hover:bg-green-600 transition-colors"
                            onclick="showSuccessFeedback()">
                        成功訊息
                    </button>
                    <button type="button" 
                            class="w-full px-4 py-2 bg-red-500 text-white rounded hover:bg-red-600 transition-colors"
                            onclick="showErrorFeedback()">
                        錯誤訊息
                    </button>
                    <button type="button" 
                            class="w-full px-4 py-2 bg-yellow-500 text-white rounded hover:bg-yellow-600 transition-colors"
                            onclick="showWarningFeedback()">
                        警告訊息
                    </button>
                    <button type="button" 
                            class="w-full px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600 transition-colors"
                            onclick="showLoadingFeedback()">
                        載入訊息
                    </button>
                </div>
            </div>
            
            <!-- 骨架屏示例 -->
            <div class="space-y-4">
                <h3 class="text-md font-medium text-gray-700 dark:text-gray-300">骨架屏載入</h3>
                <div class="space-y-2">
                    <button type="button" 
                            class="w-full px-4 py-2 bg-purple-500 text-white rounded hover:bg-purple-600 transition-colors"
                            onclick="showDashboardSkeleton()">
                        儀表板骨架屏
                    </button>
                    <button type="button" 
                            class="w-full px-4 py-2 bg-indigo-500 text-white rounded hover:bg-indigo-600 transition-colors"
                            onclick="showTableSkeleton()">
                        表格骨架屏
                    </button>
                    <button type="button" 
                            class="w-full px-4 py-2 bg-pink-500 text-white rounded hover:bg-pink-600 transition-colors"
                            onclick="showFormSkeleton()">
                        表單骨架屏
                    </button>
                    <button type="button" 
                            class="w-full px-4 py-2 bg-gray-500 text-white rounded hover:bg-gray-600 transition-colors"
                            onclick="hideSkeleton()">
                        隱藏骨架屏
                    </button>
                </div>
            </div>
        </div>
        
        <!-- 網路狀態示例 -->
        <div class="mt-8 space-y-4">
            <h3 class="text-md font-medium text-gray-700 dark:text-gray-300">網路狀態</h3>
            <div class="flex space-x-4">
                <button type="button" 
                        class="px-4 py-2 bg-green-500 text-white rounded hover:bg-green-600 transition-colors"
                        onclick="simulateOnline()">
                    模擬上線
                </button>
                <button type="button" 
                        class="px-4 py-2 bg-red-500 text-white rounded hover:bg-red-600 transition-colors"
                        onclick="simulateOffline()">
                    模擬離線
                </button>
                <button type="button" 
                        class="px-4 py-2 bg-yellow-500 text-white rounded hover:bg-yellow-600 transition-colors"
                        onclick="toggleOfflineMode()">
                    切換離線模式
                </button>
            </div>
        </div>
        
        <!-- 頁面載入示例 -->
        <div class="mt-8 space-y-4">
            <h3 class="text-md font-medium text-gray-700 dark:text-gray-300">頁面載入指示器</h3>
            <div class="flex space-x-4">
                <button type="button" 
                        class="px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600 transition-colors"
                        onclick="startPageLoading()">
                    開始頁面載入
                </button>
                <button type="button" 
                        class="px-4 py-2 bg-green-500 text-white rounded hover:bg-green-600 transition-colors"
                        onclick="finishPageLoading()">
                    完成頁面載入
                </button>
            </div>
        </div>
    </div>
</div>

<script>
// 載入覆蓋層示例
function showSpinnerLoading() {
    Livewire.dispatch('start-loading', { text: '正在處理您的請求...', type: 'spinner' });
}

function showProgressLoading() {
    Livewire.dispatch('start-loading', { text: '載入中...', type: 'progress' });
    
    // 模擬進度更新
    let progress = 0;
    const interval = setInterval(() => {
        progress += Math.random() * 20;
        if (progress >= 100) {
            progress = 100;
            clearInterval(interval);
        }
        Livewire.dispatch('update-progress', { progress: Math.floor(progress), text: `載入中... ${Math.floor(progress)}%` });
    }, 500);
}

function hideLoading() {
    Livewire.dispatch('stop-loading');
}

// 操作回饋示例
function showSuccessFeedback() {
    Livewire.dispatch('operation-success', { message: '操作成功完成！' });
}

function showErrorFeedback() {
    Livewire.dispatch('operation-error', { message: '操作失敗，請稍後重試。' });
}

function showWarningFeedback() {
    Livewire.dispatch('operation-warning', { message: '請注意：此操作可能需要一些時間。' });
}

function showLoadingFeedback() {
    const feedbackId = 'loading_' + Date.now();
    Livewire.dispatch('operation-started', { message: '正在處理中...', id: feedbackId });
    
    // 模擬進度更新
    let progress = 0;
    const interval = setInterval(() => {
        progress += Math.random() * 15;
        if (progress >= 100) {
            progress = 100;
            clearInterval(interval);
        }
        Livewire.dispatch('update-feedback-progress', { 
            id: feedbackId, 
            progress: Math.floor(progress), 
            message: `處理中... ${Math.floor(progress)}%` 
        });
    }, 300);
}

// 骨架屏示例
function showDashboardSkeleton() {
    Livewire.dispatch('show-skeleton', { type: 'dashboard', animation: 'shimmer' });
    
    // 3 秒後自動隱藏
    setTimeout(() => {
        Livewire.dispatch('hide-skeleton');
    }, 3000);
}

function showTableSkeleton() {
    Livewire.dispatch('show-skeleton', { type: 'table', animation: 'wave' });
    
    // 模擬載入進度
    let progress = 0;
    const interval = setInterval(() => {
        progress += Math.random() * 25;
        if (progress >= 100) {
            progress = 100;
            clearInterval(interval);
        }
        Livewire.dispatch('update-skeleton-progress', { progress: Math.floor(progress) });
    }, 400);
}

function showFormSkeleton() {
    Livewire.dispatch('show-skeleton', { type: 'form', animation: 'pulse' });
    
    // 2 秒後自動隱藏
    setTimeout(() => {
        Livewire.dispatch('hide-skeleton');
    }, 2000);
}

function hideSkeleton() {
    Livewire.dispatch('hide-skeleton');
}

// 網路狀態示例
function simulateOnline() {
    Livewire.dispatch('network-status-update', { 
        isOnline: true, 
        details: { latency: 50, type: 'wifi' } 
    });
}

function simulateOffline() {
    Livewire.dispatch('network-status-update', { isOnline: false });
}

function toggleOfflineMode() {
    // 這個需要直接調用元件方法
    // 在實際應用中，可以通過 Livewire 元件來處理
    console.log('切換離線模式');
}

// 頁面載入示例
function startPageLoading() {
    Livewire.dispatch('start-page-loading', {
        steps: {
            'initializing': '初始化頁面...',
            'loading_data': '載入資料...',
            'rendering': '渲染介面...',
            'finalizing': '完成載入...'
        },
        estimatedDuration: 4000
    });
}

function finishPageLoading() {
    Livewire.dispatch('finish-page-loading');
}

// 頁面載入時的示例
document.addEventListener('DOMContentLoaded', function() {
    // 顯示歡迎訊息
    setTimeout(() => {
        Livewire.dispatch('show-feedback', {
            message: '歡迎使用載入狀態管理系統示例！',
            type: 'info',
            duration: 5000
        });
    }, 1000);
});
</script>
@endsection