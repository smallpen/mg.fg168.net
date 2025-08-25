@extends('layouts.admin')

@section('title', __('permissions.titles.create_permission', ['default' => '建立權限']))

@section('content')
    <div class="space-y-6">
        
        <!-- 頁面標題 -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">
                    {{ __('permissions.titles.create_permission', ['default' => '建立權限']) }}
                </h1>
                <p class="text-gray-600 dark:text-gray-400 mt-1">
                    {{ __('permissions.form.create_title', ['default' => '建立新的系統權限，定義功能模組的存取控制']) }}
                </p>
            </div>
            
            <!-- 返回按鈕 -->
            <div class="flex items-center space-x-3 mt-4 sm:mt-0">
                <a href="{{ route('admin.permissions.index') }}" 
                   class="inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors duration-200">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                    {{ __('admin.common.back', ['default' => '返回']) }}
                </a>
            </div>
        </div>
        
        <!-- 權限建立表單元件 -->
        <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg">
            <livewire:admin.permissions.permission-form />
        </div>
        
    </div>
@endsection

@push('scripts')
<script>
    // 權限建立頁面的 JavaScript 功能
    document.addEventListener('DOMContentLoaded', function() {
        // 監聽 Livewire 事件
        window.addEventListener('permission-created', event => {
            // 權限建立成功後的處理
            if (event.detail.redirect) {
                window.location.href = event.detail.redirect;
            }
        });
        
        window.addEventListener('show-toast', event => {
            // 顯示通知訊息
            if (typeof showToast === 'function') {
                showToast(event.detail.message, event.detail.type);
            }
        });
        
        // 表單驗證增強
        const form = document.querySelector('form');
        if (form) {
            form.addEventListener('submit', function(e) {
                // 可以在這裡添加額外的客戶端驗證
                const nameInput = form.querySelector('input[name="name"]');
                if (nameInput && nameInput.value) {
                    // 檢查權限名稱格式
                    const namePattern = /^[a-z_\.]+$/;
                    if (!namePattern.test(nameInput.value)) {
                        e.preventDefault();
                        alert('權限名稱只能包含小寫字母、底線和點號');
                        nameInput.focus();
                        return false;
                    }
                }
            });
        }
    });
</script>
@endpush

@push('styles')
<style>
    /* 權限建立表單的自定義樣式 */
    .permission-form-container {
        /* 確保表單容器有適當的間距 */
        padding: 1.5rem;
    }
    
    /* 表單欄位分組樣式 */
    .form-group {
        margin-bottom: 1.5rem;
    }
    
    .form-group:last-child {
        margin-bottom: 0;
    }
    
    /* 必填欄位標記 */
    .required-field::after {
        content: ' *';
        color: #ef4444;
        font-weight: bold;
    }
    
    /* 表單驗證錯誤樣式增強 */
    .form-error {
        border-color: #ef4444 !important;
        box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.1) !important;
    }
    
    /* 依賴關係選擇器樣式 */
    .dependency-selector {
        max-height: 200px;
        overflow-y: auto;
        border: 1px solid #d1d5db;
        border-radius: 0.5rem;
        padding: 0.5rem;
    }
    
    .dark .dependency-selector {
        border-color: #4b5563;
        background-color: #374151;
    }
    
    /* 模組和類型選擇器樣式 */
    .module-type-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 1rem;
    }
    
    @media (max-width: 768px) {
        .module-type-grid {
            grid-template-columns: 1fr;
        }
    }
    
    /* 表單提交按鈕區域 */
    .form-actions {
        border-top: 1px solid #e5e7eb;
        padding-top: 1.5rem;
        margin-top: 2rem;
        display: flex;
        justify-content: flex-end;
        gap: 0.75rem;
    }
    
    .dark .form-actions {
        border-top-color: #4b5563;
    }
    
    @media (max-width: 640px) {
        .form-actions {
            flex-direction: column;
        }
        
        .form-actions button,
        .form-actions a {
            width: 100%;
            justify-content: center;
        }
    }
</style>
@endpush
</x-admin.layout.admin-layout>