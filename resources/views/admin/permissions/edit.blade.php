@extends('layouts.admin')

@section('title', __('admin.permissions.edit', ['default' => '編輯權限']) . ': ' . $permission->display_name)
@section('page-title', __('admin.permissions.edit', ['default' => '編輯權限']) . ': ' . $permission->display_name)

@section('content')
<x-admin.layout.admin-layout :breadcrumbs="$breadcrumbs ?? []">
    <div class="space-y-6">
        
        <!-- 頁面標題 -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">
                    {{ __('admin.permissions.edit', ['default' => '編輯權限']) }}
                </h1>
                <p class="text-gray-600 dark:text-gray-400 mt-1">
                    {{ __('admin.permissions.edit_description', ['default' => '修改權限資訊和配置']) }}
                </p>
                
                <!-- 權限基本資訊 -->
                <div class="mt-3 flex items-center space-x-4 text-sm text-gray-500 dark:text-gray-400">
                    <span class="flex items-center">
                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                        </svg>
                        {{ $permission->name }}
                    </span>
                    <span class="flex items-center">
                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14-7H5m14 14H5"/>
                        </svg>
                        {{ $permission->module }}
                    </span>
                    <span class="flex items-center">
                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 100 4m0-4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 100 4m0-4v2m0-6V4"/>
                        </svg>
                        {{ $permission->type }}
                    </span>
                    @if($permission->is_system_permission)
                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200">
                            <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                            </svg>
                            系統權限
                        </span>
                    @endif
                </div>
            </div>
            
            <!-- 操作按鈕 -->
            <div class="flex items-center space-x-3 mt-4 sm:mt-0">
                @can('permissions.view')
                    <a href="{{ route('admin.permissions.show', $permission) }}" 
                       class="inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors duration-200">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                        </svg>
                        {{ __('admin.common.view', ['default' => '檢視']) }}
                    </a>
                @endcan
                
                <a href="{{ route('admin.permissions.index') }}" 
                   class="inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors duration-200">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                    {{ __('admin.common.back', ['default' => '返回']) }}
                </a>
            </div>
        </div>
        
        <!-- 系統權限警告 -->
        @if($permission->is_system_permission)
            <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg p-4">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                        </svg>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-yellow-800 dark:text-yellow-200">
                            系統權限編輯警告
                        </h3>
                        <div class="mt-2 text-sm text-yellow-700 dark:text-yellow-300">
                            <p>
                                此權限為系統核心權限，修改時請特別小心。某些欄位可能受到限制以確保系統穩定性。
                                建議在修改前先備份相關配置。
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        @endif
        
        <!-- 權限編輯表單元件 -->
        <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg">
            <livewire:admin.permissions.permission-form :permission="$permission" />
        </div>
        
        <!-- 權限使用情況 -->
        @if($permission->roles()->exists())
            <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-6">
                <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">
                    權限使用情況
                </h3>
                <div class="space-y-3">
                    <div class="flex items-center justify-between text-sm">
                        <span class="text-gray-600 dark:text-gray-400">使用此權限的角色數量：</span>
                        <span class="font-medium text-gray-900 dark:text-gray-100">{{ $permission->roles()->count() }}</span>
                    </div>
                    <div class="flex items-center justify-between text-sm">
                        <span class="text-gray-600 dark:text-gray-400">間接擁有此權限的使用者數量：</span>
                        <span class="font-medium text-gray-900 dark:text-gray-100">{{ $permission->user_count ?? 0 }}</span>
                    </div>
                </div>
                
                <!-- 使用此權限的角色列表 -->
                <div class="mt-4">
                    <h4 class="text-sm font-medium text-gray-900 dark:text-gray-100 mb-2">使用此權限的角色：</h4>
                    <div class="flex flex-wrap gap-2">
                        @foreach($permission->roles as $role)
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                                {{ $role->display_name }}
                            </span>
                        @endforeach
                    </div>
                </div>
            </div>
        @endif
        
    </div>
</x-admin.layout.admin-layout>

@push('scripts')
<script>
    // 權限編輯頁面的 JavaScript 功能
    document.addEventListener('DOMContentLoaded', function() {
        // 監聽 Livewire 事件
        window.addEventListener('permission-updated', event => {
            // 權限更新成功後的處理
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
        
        // 系統權限編輯確認
        @if($permission->is_system_permission)
            const form = document.querySelector('form');
            if (form) {
                form.addEventListener('submit', function(e) {
                    if (!confirm('您正在編輯系統權限，這可能會影響系統功能。確定要繼續嗎？')) {
                        e.preventDefault();
                        return false;
                    }
                });
            }
        @endif
        
        // 權限名稱變更警告
        const nameInput = document.querySelector('input[name="name"]');
        if (nameInput) {
            const originalName = nameInput.value;
            nameInput.addEventListener('change', function() {
                if (this.value !== originalName) {
                    const warning = document.createElement('div');
                    warning.className = 'mt-2 text-sm text-yellow-600 dark:text-yellow-400';
                    warning.innerHTML = '<svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path></svg>變更權限名稱可能會影響現有的權限檢查邏輯';
                    
                    // 移除舊的警告
                    const existingWarning = this.parentNode.querySelector('.text-yellow-600, .text-yellow-400');
                    if (existingWarning) {
                        existingWarning.remove();
                    }
                    
                    this.parentNode.appendChild(warning);
                }
            });
        }
    });
</script>
@endpush

@push('styles')
<style>
    /* 權限編輯表單的自定義樣式 */
    .permission-edit-container {
        /* 確保編輯容器有適當的間距 */
        padding: 1.5rem;
    }
    
    /* 權限資訊標籤樣式 */
    .permission-info-tags {
        display: flex;
        flex-wrap: wrap;
        gap: 0.5rem;
        margin-top: 0.75rem;
    }
    
    .permission-info-tag {
        display: inline-flex;
        align-items: center;
        padding: 0.25rem 0.75rem;
        background-color: #f3f4f6;
        color: #374151;
        border-radius: 9999px;
        font-size: 0.875rem;
        font-weight: 500;
    }
    
    .dark .permission-info-tag {
        background-color: #4b5563;
        color: #d1d5db;
    }
    
    /* 系統權限特殊樣式 */
    .system-permission-indicator {
        position: relative;
    }
    
    .system-permission-indicator::before {
        content: '';
        position: absolute;
        top: -2px;
        left: -2px;
        right: -2px;
        bottom: -2px;
        background: linear-gradient(45deg, #ef4444, #f59e0b);
        border-radius: 0.5rem;
        z-index: -1;
        opacity: 0.1;
    }
    
    /* 使用情況統計樣式 */
    .usage-stats {
        background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
        border-radius: 0.75rem;
        padding: 1.5rem;
    }
    
    .dark .usage-stats {
        background: linear-gradient(135deg, #1f2937 0%, #111827 100%);
    }
    
    /* 角色標籤樣式 */
    .role-tags {
        display: flex;
        flex-wrap: wrap;
        gap: 0.5rem;
        max-height: 120px;
        overflow-y: auto;
        padding: 0.5rem;
        border: 1px solid #e5e7eb;
        border-radius: 0.5rem;
        background-color: #f9fafb;
    }
    
    .dark .role-tags {
        border-color: #4b5563;
        background-color: #374151;
    }
    
    /* 響應式調整 */
    @media (max-width: 640px) {
        .permission-info-tags {
            flex-direction: column;
            align-items: flex-start;
        }
        
        .permission-info-tag {
            align-self: flex-start;
        }
    }
</style>
@endpush
@endsection