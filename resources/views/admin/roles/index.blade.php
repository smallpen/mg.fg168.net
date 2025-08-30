@extends('layouts.admin')

@section('title', __('admin.roles.title'))

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
    <div class="space-y-6">
        {{-- 角色列表 Livewire 元件 --}}
        <livewire:admin.roles.role-list />
    </div>

@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // 通知系統
    const notificationArea = document.getElementById('notification-area');
    
    function showNotification(message, type = 'success') {
        // 如果通知區域不存在，則建立一個
        let targetArea = notificationArea;
        if (!targetArea) {
            targetArea = document.createElement('div');
            targetArea.id = 'notification-area';
            targetArea.className = 'fixed top-4 right-4 z-50 space-y-2';
            document.body.appendChild(targetArea);
        }
        
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
        
        targetArea.appendChild(notification);
        
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
        // 如果對話框元素不存在，使用瀏覽器原生確認對話框
        if (!confirmationModal || !modalMessage || !confirmButton || !cancelButton) {
            if (confirm(message)) {
                confirmAction();
            }
            return;
        }
        
        modalMessage.textContent = message;
        currentConfirmAction = confirmAction;
        confirmationModal.classList.remove('hidden');
        document.body.classList.add('overflow-hidden');
    }
    
    function hideConfirmDialog() {
        if (confirmationModal) {
            confirmationModal.classList.add('hidden');
            document.body.classList.remove('overflow-hidden');
        }
        currentConfirmAction = null;
    }
    
    // 只有在元素存在時才添加事件監聽器
    if (confirmButton) {
        confirmButton.addEventListener('click', function() {
            if (currentConfirmAction) {
                currentConfirmAction();
            }
            hideConfirmDialog();
        });
    }
    
    if (cancelButton) {
        cancelButton.addEventListener('click', hideConfirmDialog);
    }
    
    // 點擊背景關閉對話框
    if (confirmationModal) {
        confirmationModal.addEventListener('click', function(e) {
            if (e.target === confirmationModal) {
                hideConfirmDialog();
            }
        });
    }
    
    // ESC 鍵關閉對話框
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && confirmationModal && !confirmationModal.classList.contains('hidden')) {
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
        const searchInput = document.querySelector('input[wire\\:model\\.live="search"]');
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