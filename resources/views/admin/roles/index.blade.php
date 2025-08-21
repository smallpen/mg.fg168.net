@extends('layouts.admin')

@section('title', __('admin.roles.title'))
@section('page-title', __('admin.roles.title'))

@push('styles')
<style>
    /* 載入狀態動畫 */
    .loading-spinner {
        animation: spin 1s linear infinite;
    }
    
    @keyframes spin {
        from { transform: rotate(0deg); }
        to { transform: rotate(360deg); }
    }
    
    /* 響應式表格優化 */
    @media (max-width: 768px) {
        .table-responsive {
            font-size: 0.875rem;
        }
        
        .table-responsive th,
        .table-responsive td {
            padding: 0.5rem 0.25rem;
        }
    }
    
    /* 批量操作區域動畫 */
    .bulk-actions-enter {
        animation: slideDown 0.3s ease-out;
    }
    
    @keyframes slideDown {
        from {
            opacity: 0;
            transform: translateY(-10px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    
    /* 篩選器展開動畫 */
    .filters-expand {
        animation: expandDown 0.3s ease-out;
    }
    
    @keyframes expandDown {
        from {
            opacity: 0;
            max-height: 0;
        }
        to {
            opacity: 1;
            max-height: 200px;
        }
    }
    
    /* 表格行懸停效果 */
    .table-row-hover {
        transition: all 0.2s ease-in-out;
    }
    
    .table-row-hover:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    }
    
    /* 操作按鈕群組 */
    .action-buttons {
        display: flex;
        gap: 0.25rem;
        justify-content: flex-end;
        align-items: center;
    }
    
    .action-buttons button {
        transition: all 0.2s ease-in-out;
    }
    
    .action-buttons button:hover {
        transform: scale(1.1);
    }
    
    /* 統計卡片動畫 */
    .stats-card {
        transition: all 0.3s ease-in-out;
    }
    
    .stats-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 16px rgba(0, 0, 0, 0.1);
    }
    
    /* 搜尋框焦點效果 */
    .search-input:focus {
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        border-color: #3b82f6;
    }
    
    /* 空狀態插圖 */
    .empty-state {
        animation: fadeIn 0.5s ease-in-out;
    }
    
    @keyframes fadeIn {
        from { opacity: 0; }
        to { opacity: 1; }
    }
</style>
@endpush

@section('content')
<div class="min-h-screen bg-gray-50 dark:bg-gray-900">
    {{-- 頁面載入指示器 --}}
    <div wire:loading.delay class="fixed top-0 left-0 right-0 z-50">
        <div class="bg-blue-600 h-1">
            <div class="bg-blue-400 h-full loading-spinner origin-left transform scale-x-0 animate-pulse"></div>
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
                        <span class="ml-1 text-sm font-medium text-gray-500 md:ml-2 dark:text-gray-400">
                            {{ __('admin.roles.title') }}
                        </span>
                    </div>
                </li>
            </ol>
        </nav>
        
        {{-- 角色列表 Livewire 元件 --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm">
            <livewire:admin.roles.role-list />
        </div>
    </div>
    
    {{-- 全域通知區域 --}}
    <div id="notification-area" class="fixed top-4 right-4 z-50 space-y-2">
        {{-- 通知將通過 JavaScript 動態插入 --}}
    </div>
    
    {{-- 確認對話框 --}}
    <div id="confirmation-modal" class="fixed inset-0 z-50 hidden overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
            <div class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                <div class="bg-white dark:bg-gray-800 px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <div class="sm:flex sm:items-start">
                        <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-red-100 dark:bg-red-900/20 sm:mx-0 sm:h-10 sm:w-10">
                            <svg class="h-6 w-6 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z" />
                            </svg>
                        </div>
                        <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                            <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white" id="modal-title">
                                {{ __('admin.roles.confirm.title') }}
                            </h3>
                            <div class="mt-2">
                                <p class="text-sm text-gray-500 dark:text-gray-400" id="modal-message">
                                    {{ __('admin.roles.confirm.message') }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="bg-gray-50 dark:bg-gray-700 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                    <button type="button" id="confirm-button" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-red-600 text-base font-medium text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 sm:ml-3 sm:w-auto sm:text-sm">
                        {{ __('admin.common.confirm') }}
                    </button>
                    <button type="button" id="cancel-button" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 dark:border-gray-600 shadow-sm px-4 py-2 bg-white dark:bg-gray-800 text-base font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                        {{ __('admin.common.cancel') }}
                    </button>
                </div>
            </div>
        </div>
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
            ${type === 'success' ? 'border-l-4 border-green-400' : 'border-l-4 border-red-400'}
        `;
        
        notification.innerHTML = `
            <div class="p-4">
                <div class="flex items-start">
                    <div class="flex-shrink-0">
                        ${type === 'success' ? `
                            <svg class="h-6 w-6 text-green-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        ` : `
                            <svg class="h-6 w-6 text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z" />
                            </svg>
                        `}
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
    
    // 確認對話框
    const confirmationModal = document.getElementById('confirmation-modal');
    const confirmButton = document.getElementById('confirm-button');
    const cancelButton = document.getElementById('cancel-button');
    const modalMessage = document.getElementById('modal-message');
    
    let currentConfirmAction = null;
    
    function showConfirmDialog(message, confirmAction) {
        modalMessage.textContent = message;
        currentConfirmAction = confirmAction;
        confirmationModal.classList.remove('hidden');
        document.body.classList.add('overflow-hidden');
    }
    
    function hideConfirmDialog() {
        confirmationModal.classList.add('hidden');
        document.body.classList.remove('overflow-hidden');
        currentConfirmAction = null;
    }
    
    confirmButton.addEventListener('click', function() {
        if (currentConfirmAction) {
            currentConfirmAction();
        }
        hideConfirmDialog();
    });
    
    cancelButton.addEventListener('click', hideConfirmDialog);
    
    // 點擊背景關閉對話框
    confirmationModal.addEventListener('click', function(e) {
        if (e.target === confirmationModal) {
            hideConfirmDialog();
        }
    });
    
    // ESC 鍵關閉對話框
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && !confirmationModal.classList.contains('hidden')) {
            hideConfirmDialog();
        }
    });
    
    // Livewire 事件監聽
    document.addEventListener('livewire:init', () => {
        // 成功訊息
        Livewire.on('role-deleted', (event) => {
            showNotification(event.message, 'success');
        });
        
        Livewire.on('role-duplicated', (event) => {
            showNotification(event.message, 'success');
        });
        
        Livewire.on('role-bulk-updated', (event) => {
            showNotification(event.message, 'success');
        });
        
        Livewire.on('role-bulk-deleted', (event) => {
            showNotification(event.message, 'success');
        });
        
        Livewire.on('role-status-changed', (event) => {
            showNotification(event.message, 'success');
        });
        
        // 確認對話框
        Livewire.on('confirm-delete', (event) => {
            showConfirmDialog(
                '{{ __("admin.roles.confirm.delete") }}',
                () => Livewire.dispatch('confirmed-delete', { roleId: event.roleId })
            );
        });
        
        Livewire.on('confirm-bulk-delete', (event) => {
            showConfirmDialog(
                '{{ __("admin.roles.confirm.bulk_delete") }}',
                () => Livewire.dispatch('confirmed-bulk-delete')
            );
        });
    });
    
    // 響應式表格處理
    function handleResponsiveTable() {
        const tables = document.querySelectorAll('.table-responsive');
        tables.forEach(table => {
            if (window.innerWidth < 768) {
                table.classList.add('text-sm');
            } else {
                table.classList.remove('text-sm');
            }
        });
    }
    
    window.addEventListener('resize', handleResponsiveTable);
    handleResponsiveTable();
    
    // 載入狀態處理
    document.addEventListener('livewire:navigating', () => {
        document.body.classList.add('loading');
    });
    
    document.addEventListener('livewire:navigated', () => {
        document.body.classList.remove('loading');
    });
    
    // 表格行動畫效果
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.style.animationDelay = `${entry.target.dataset.index * 50}ms`;
                entry.target.classList.add('animate-fadeInUp');
            }
        });
    });
    
    // 觀察表格行
    document.querySelectorAll('tbody tr').forEach((row, index) => {
        row.dataset.index = index;
        observer.observe(row);
    });
});

// 工具函數
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

// 鍵盤快捷鍵
document.addEventListener('keydown', function(e) {
    // Ctrl/Cmd + K 開啟搜尋
    if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
        e.preventDefault();
        const searchInput = document.querySelector('input[wire\\:model\\.live\\.debounce\\.300ms="search"]');
        if (searchInput) {
            searchInput.focus();
        }
    }
    
    // Ctrl/Cmd + N 建立新角色
    if ((e.ctrlKey || e.metaKey) && e.key === 'n') {
        e.preventDefault();
        const createButton = document.querySelector('[wire\\:click="createRole"]');
        if (createButton) {
            createButton.click();
        }
    }
});
</script>
@endpush