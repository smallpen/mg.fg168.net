@extends('layouts.admin')

@section('title', __('permissions.titles.permission_matrix', ['default' => '權限矩陣']))
@section('page-title', __('permissions.titles.permission_matrix', ['default' => '權限矩陣']))

@push('styles')
<style>
    /* 權限矩陣專用樣式 */
    .permission-matrix-container {
        min-height: calc(100vh - 200px);
    }
    
    /* 矩陣表格樣式 */
    .matrix-table {
        border-collapse: separate;
        border-spacing: 0;
    }
    
    .matrix-table th,
    .matrix-table td {
        border: 1px solid #e5e7eb;
        border-right: 0;
        border-bottom: 0;
    }
    
    .matrix-table th:last-child,
    .matrix-table td:last-child {
        border-right: 1px solid #e5e7eb;
    }
    
    .matrix-table tr:last-child th,
    .matrix-table tr:last-child td {
        border-bottom: 1px solid #e5e7eb;
    }
    
    /* 深色模式下的邊框 */
    .dark .matrix-table th,
    .dark .matrix-table td {
        border-color: #374151;
    }
    
    .dark .matrix-table th:last-child,
    .dark .matrix-table td:last-child {
        border-right-color: #374151;
    }
    
    .dark .matrix-table tr:last-child th,
    .dark .matrix-table tr:last-child td {
        border-bottom-color: #374151;
    }
    
    /* 固定列樣式 */
    .sticky-column {
        position: sticky;
        left: 0;
        z-index: 10;
        background: inherit;
    }
    
    /* 權限切換按鈕動畫 */
    .permission-toggle {
        transition: all 0.2s ease-in-out;
    }
    
    .permission-toggle:hover {
        transform: scale(1.1);
    }
    
    /* 變更指示器動畫 */
    .change-indicator {
        animation: pulse 2s infinite;
    }
    
    @keyframes pulse {
        0%, 100% { opacity: 1; }
        50% { opacity: 0.5; }
    }
    
    /* 模組標題行樣式 */
    .module-header {
        background: linear-gradient(135deg, #f3f4f6 0%, #e5e7eb 100%);
    }
    
    .dark .module-header {
        background: linear-gradient(135deg, #374151 0%, #4b5563 100%);
    }
    
    /* 載入狀態 */
    .loading-overlay {
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(255, 255, 255, 0.8);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 50;
    }
    
    .dark .loading-overlay {
        background: rgba(17, 24, 39, 0.8);
    }
    
    /* 響應式優化 */
    @media (max-width: 1024px) {
        .matrix-table {
            font-size: 0.875rem;
        }
        
        .matrix-table th,
        .matrix-table td {
            padding: 0.5rem 0.25rem;
        }
    }
    
    @media (max-width: 768px) {
        .matrix-table {
            font-size: 0.75rem;
        }
        
        .matrix-table th,
        .matrix-table td {
            padding: 0.25rem;
        }
    }
</style>
@endpush

@section('content')
<div class="permission-matrix-container bg-gray-50 dark:bg-gray-900">
    {{-- 載入指示器 --}}
    <div wire:loading.delay class="fixed top-0 left-0 right-0 z-50">
        <div class="bg-blue-600 h-1">
            <div class="bg-blue-400 h-full animate-pulse"></div>
        </div>
    </div>
    
    {{-- 主要內容區域 --}}
    <div class="container mx-auto px-4 py-6">
        {{-- 麵包屑導航 --}}
        <nav class="flex mb-6" aria-label="Breadcrumb">
            <ol class="inline-flex items-center space-x-1 md:space-x-3">
                <li class="inline-flex items-center">
                    <a href="{{ route('admin.dashboard') }}" class="inline-flex items-center text-sm font-medium text-gray-700 hover:text-blue-600 dark:text-gray-400 dark:hover:text-white">
                        <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z"></path>
                        </svg>
                        {{ __('admin.dashboard.title') }}
                    </a>
                </li>
                <li>
                    <div class="flex items-center">
                        <svg class="w-6 h-6 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"></path>
                        </svg>
                        <a href="{{ route('admin.permissions.index') }}" class="ml-1 text-sm font-medium text-gray-700 hover:text-blue-600 md:ml-2 dark:text-gray-400 dark:hover:text-white">
                            {{ __('permissions.titles.permission_management') }}
                        </a>
                    </div>
                </li>
                <li>
                    <div class="flex items-center">
                        <svg class="w-6 h-6 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"></path>
                        </svg>
                        <span class="ml-1 text-sm font-medium text-gray-500 md:ml-2 dark:text-gray-400">
                            {{ __('permissions.titles.permission_matrix', ['default' => '權限矩陣']) }}
                        </span>
                    </div>
                </li>
            </ol>
        </nav>
        
        {{-- 權限矩陣 Livewire 元件 --}}
        <div class="relative">
            <div wire:loading.delay class="loading-overlay">
                <div class="flex items-center space-x-2">
                    <svg class="animate-spin h-5 w-5 text-blue-600" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <span class="text-sm text-gray-600 dark:text-gray-400">{{ __('admin.common.loading') }}</span>
                </div>
            </div>
            
            <livewire:admin.roles.permission-matrix />
        </div>
    </div>
    
    {{-- 全域通知區域 --}}
    <div id="notification-area" class="fixed top-4 right-4 z-50 space-y-2">
        {{-- 通知將通過 JavaScript 動態插入 --}}
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // 通知系統
    const notificationArea = document.getElementById('notification-area');
    
    function showNotification(message, type = 'success') {
        const notification = document.createElement('div');
        notification.className = `
            max-w-sm w-full bg-white dark:bg-gray-800 shadow-lg rounded-lg pointer-events-auto 
            ring-1 ring-black ring-opacity-5 overflow-hidden transform transition-all duration-300 
            ${type === 'success' ? 'border-l-4 border-green-400' : 
              type === 'error' ? 'border-l-4 border-red-400' : 
              type === 'warning' ? 'border-l-4 border-yellow-400' : 
              'border-l-4 border-blue-400'}
        `;
        
        const iconSvg = {
            success: `<svg class="h-6 w-6 text-green-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                      </svg>`,
            error: `<svg class="h-6 w-6 text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z" />
                    </svg>`,
            warning: `<svg class="h-6 w-6 text-yellow-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z" />
                      </svg>`,
            info: `<svg class="h-6 w-6 text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                     <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                   </svg>`
        };
        
        notification.innerHTML = `
            <div class="p-4">
                <div class="flex items-start">
                    <div class="flex-shrink-0">
                        ${iconSvg[type] || iconSvg.info}
                    </div>
                    <div class="ml-3 w-0 flex-1 pt-0.5">
                        <p class="text-sm font-medium text-gray-900 dark:text-white">
                            ${message}
                        </p>
                    </div>
                    <div class="ml-4 flex-shrink-0 flex">
                        <button class="bg-white dark:bg-gray-800 rounded-md inline-flex text-gray-400 hover:text-gray-500 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500" onclick="this.parentElement.parentElement.parentElement.parentElement.remove()">
                            <span class="sr-only">關閉</span>
                            <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
        `;
        
        notificationArea.appendChild(notification);
        
        // 自動移除通知
        setTimeout(() => {
            notification.style.transform = 'translateX(100%)';
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.parentNode.removeChild(notification);
                }
            }, 300);
        }, 5000);
    }
    
    // Livewire 事件監聽
    document.addEventListener('livewire:init', () => {
        // 成功訊息
        Livewire.on('success', (event) => {
            showNotification(event.message, 'success');
        });
        
        // 錯誤訊息
        Livewire.on('error', (event) => {
            showNotification(event.message, 'error');
        });
        
        // 警告訊息
        Livewire.on('warning', (event) => {
            showNotification(event.message, 'warning');
        });
        
        // 資訊訊息
        Livewire.on('info', (event) => {
            showNotification(event.message, 'info');
        });
        
        // 權限變更相關事件
        Livewire.on('permission-toggled', (event) => {
            console.log('權限已切換:', event);
        });
        
        Livewire.on('permissions-applied', (event) => {
            showNotification('權限變更已成功應用', 'success');
        });
        
        Livewire.on('changes-cancelled', (event) => {
            showNotification('已取消所有權限變更', 'info');
        });
        
        Livewire.on('module-assigned', (event) => {
            console.log('模組權限已指派:', event);
        });
        
        Livewire.on('module-revoked', (event) => {
            console.log('模組權限已移除:', event);
        });
    });
    
    // 鍵盤快捷鍵
    document.addEventListener('keydown', function(e) {
        // Ctrl/Cmd + S 應用變更
        if ((e.ctrlKey || e.metaKey) && e.key === 's') {
            e.preventDefault();
            const applyButton = document.querySelector('[wire\\:click="applyChanges"]');
            if (applyButton && !applyButton.disabled) {
                applyButton.click();
            }
        }
        
        // Ctrl/Cmd + Z 取消變更
        if ((e.ctrlKey || e.metaKey) && e.key === 'z') {
            e.preventDefault();
            const cancelButton = document.querySelector('[wire\\:click="cancelChanges"]');
            if (cancelButton) {
                cancelButton.click();
            }
        }
        
        // Ctrl/Cmd + K 開啟搜尋
        if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
            e.preventDefault();
            const searchInput = document.querySelector('input[wire\\:model\\.debounce\\.300ms="search"]');
            if (searchInput) {
                searchInput.focus();
            }
        }
    });
    
    // 表格響應式處理
    function handleResponsiveMatrix() {
        const matrixContainer = document.querySelector('.matrix-table');
        if (matrixContainer) {
            if (window.innerWidth < 1024) {
                matrixContainer.classList.add('text-sm');
            } else {
                matrixContainer.classList.remove('text-sm');
            }
        }
    }
    
    window.addEventListener('resize', handleResponsiveMatrix);
    handleResponsiveMatrix();
    
    // 滾動位置記憶
    let scrollPosition = 0;
    
    document.addEventListener('livewire:navigating', () => {
        scrollPosition = window.scrollY;
    });
    
    document.addEventListener('livewire:navigated', () => {
        window.scrollTo(0, scrollPosition);
    });
});

// 工具提示功能
function initTooltips() {
    const tooltipElements = document.querySelectorAll('[title]');
    tooltipElements.forEach(element => {
        element.addEventListener('mouseenter', function(e) {
            const tooltip = document.createElement('div');
            tooltip.className = 'absolute z-50 px-2 py-1 text-xs text-white bg-gray-900 rounded shadow-lg pointer-events-none';
            tooltip.textContent = this.title;
            tooltip.style.top = (e.pageY - 30) + 'px';
            tooltip.style.left = e.pageX + 'px';
            document.body.appendChild(tooltip);
            
            this.addEventListener('mouseleave', function() {
                if (tooltip.parentNode) {
                    tooltip.parentNode.removeChild(tooltip);
                }
            }, { once: true });
        });
    });
}

// 初始化工具提示
document.addEventListener('livewire:navigated', initTooltips);
initTooltips();
</script>
@endpush