@extends('layouts.admin')

@section('title', __('admin.roles.edit'))
@section('page-title', __('admin.roles.edit'))

@push('styles')
<style>
    /* 表單樣式 */
    .form-section {
        transition: all 0.3s ease-in-out;
    }
    
    .form-section:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    }
    
    /* 權限選擇器樣式 */
    .permission-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
        gap: 1rem;
    }
    
    .permission-card {
        transition: all 0.2s ease-in-out;
    }
    
    .permission-card:hover {
        transform: scale(1.02);
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }
    
    /* 載入動畫 */
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
        z-index: 10;
    }
    
    .spinner {
        animation: spin 1s linear infinite;
    }
    
    @keyframes spin {
        from { transform: rotate(0deg); }
        to { transform: rotate(360deg); }
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
                        <a href="{{ route('admin.roles.index') }}" class="ml-1 text-sm font-medium text-gray-700 hover:text-blue-600 md:ml-2 dark:text-gray-400 dark:hover:text-white">
                            {{ __('admin.roles.title') }}
                        </a>
                    </div>
                </li>
                <li>
                    <div class="flex items-center">
                        <svg class="w-6 h-6 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"></path>
                        </svg>
                        <span class="ml-1 text-sm font-medium text-gray-500 md:ml-2 dark:text-gray-400">
                            {{ __('admin.roles.edit') }}
                        </span>
                    </div>
                </li>
            </ol>
        </nav>
        
        {{-- 角色編輯表單 --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm">
            <livewire:admin.roles.role-form :role="$role" />
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // 表單驗證提示
    const inputs = document.querySelectorAll('input[required], textarea[required]');
    inputs.forEach(input => {
        input.addEventListener('invalid', function(e) {
            e.preventDefault();
            const field = e.target.getAttribute('name');
            const message = `請填寫${field === 'name' ? '角色名稱' : field === 'display_name' ? '顯示名稱' : '此欄位'}`;
            
            // 顯示自訂錯誤訊息
            showNotification(message, 'error');
        });
    });
    
    // 通知系統
    function showNotification(message, type = 'success') {
        const notification = document.createElement('div');
        notification.className = `
            fixed top-4 right-4 z-50 max-w-sm w-full bg-white dark:bg-gray-800 shadow-lg rounded-lg pointer-events-auto 
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
        
        document.body.appendChild(notification);
        
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
        Livewire.on('role-updated', (event) => {
            showNotification(event.message, 'success');
            setTimeout(() => {
                window.location.href = event.redirect || '/admin/roles';
            }, 1500);
        });
        
        // 錯誤訊息
        Livewire.on('role-update-failed', (event) => {
            showNotification(event.message, 'error');
        });
    });
    
    // 鍵盤快捷鍵
    document.addEventListener('keydown', function(e) {
        // Ctrl/Cmd + S 儲存
        if ((e.ctrlKey || e.metaKey) && e.key === 's') {
            e.preventDefault();
            const saveButton = document.querySelector('[wire\\:click="save"]');
            if (saveButton) {
                saveButton.click();
            }
        }
        
        // ESC 取消
        if (e.key === 'Escape') {
            const cancelButton = document.querySelector('[wire\\:click="cancel"]');
            if (cancelButton) {
                cancelButton.click();
            }
        }
    });
});
</script>
@endpush