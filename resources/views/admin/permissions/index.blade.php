@extends('layouts.admin')

@section('title', __('admin.permissions.permission_management'))
@section('page-title', __('admin.permissions.permission_management'))

@section('content')
<div class="space-y-6">
    
    <!-- 頁面標題和描述 -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">
                {{ __('admin.permissions.permission_management') }}
            </h1>
            <p class="text-gray-600 dark:text-gray-400 mt-1">
                {{ __('admin.permissions.management_description', ['default' => '管理系統權限的細粒度控制，包含權限定義、分組、依賴關係管理和使用情況監控']) }}
            </p>
        </div>
        
        <!-- 快速操作按鈕 -->
        <div class="flex items-center space-x-3 mt-4 sm:mt-0">
            @can('permissions.view')
                <a href="{{ route('admin.permissions.matrix') }}" 
                   class="inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors duration-200">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7m0 10a2 2 0 002 2h2a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2h2a2 2 0 002-2z"/>
                    </svg>
                    {{ __('admin.permissions.matrix') }}
                </a>
            @endcan
            
            @can('permissions.view')
                <a href="{{ route('admin.permissions.dependencies') }}" 
                   class="inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors duration-200">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"/>
                    </svg>
                    依賴關係圖表
                </a>
            @endcan
            
            @can('permissions.manage')
                <a href="{{ route('admin.permissions.templates') }}" 
                   class="inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors duration-200">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14-7H5m14 14H5"/>
                    </svg>
                    {{ __('admin.permissions.templates', ['default' => '權限模板']) }}
                </a>
            @endcan
        </div>
    </div>
    
    <!-- 權限列表元件 -->
    <livewire:admin.permissions.permission-list />
    
</div>

@push('scripts')
<script>
    // 權限管理頁面的 JavaScript 功能
    document.addEventListener('DOMContentLoaded', function() {
        // 監聽 Livewire 事件
        window.addEventListener('show-toast', event => {
            // 顯示通知訊息
            if (typeof showToast === 'function') {
                showToast(event.detail.message, event.detail.type);
            }
        });
        
        // 監聽權限表單開啟事件
        window.addEventListener('open-permission-form', event => {
            // 可以在這裡添加表單開啟的額外邏輯
            console.log('Opening permission form:', event.detail);
        });
        
        // 監聽權限刪除確認事件
        window.addEventListener('confirm-permission-delete', event => {
            // 可以在這裡添加刪除確認的額外邏輯
            console.log('Confirming permission delete:', event.detail);
        });
        
        // 監聽匯出開始事件
        window.addEventListener('export-permissions-started', event => {
            // 可以在這裡添加匯出開始的額外邏輯
            console.log('Export started:', event.detail);
        });
        
        // 監聽匯入模態開啟事件
        window.addEventListener('open-import-modal', event => {
            // 可以在這裡添加匯入模態開啟的額外邏輯
            console.log('Opening import modal');
        });
        
        // 監聽使用情況分析開啟事件
        window.addEventListener('open-usage-analysis', event => {
            // 可以在這裡添加使用情況分析開啟的額外邏輯
            console.log('Opening usage analysis');
        });
        
        // 監聽未使用權限標記工具開啟事件
        window.addEventListener('open-unused-permission-marker', event => {
            // 可以在這裡添加未使用權限標記工具開啟的額外邏輯
            console.log('Opening unused permission marker');
        });
    });
</script>
@endpush

@push('styles')
<style>
    /* 權限管理頁面的自定義樣式 */
    .permission-list-container {
        /* 確保列表容器有適當的最小高度 */
        min-height: 400px;
    }
    
    /* 權限卡片懸停效果增強 */
    .permission-card:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    }
    
    /* 權限狀態徽章動畫 */
    .permission-badge {
        transition: all 0.2s ease-in-out;
    }
    
    .permission-badge:hover {
        transform: scale(1.05);
    }
    
    /* 樹狀檢視的連接線樣式 */
    .tree-connector {
        position: relative;
    }
    
    .tree-connector::before {
        content: '';
        position: absolute;
        left: -12px;
        top: 50%;
        width: 12px;
        height: 1px;
        background-color: #d1d5db;
    }
    
    /* 深色模式下的連接線 */
    .dark .tree-connector::before {
        background-color: #4b5563;
    }
    
    /* 載入狀態的脈衝動畫 */
    .loading-pulse {
        animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
    }
    
    @keyframes pulse {
        0%, 100% {
            opacity: 1;
        }
        50% {
            opacity: .5;
        }
    }
    
    /* 響應式表格改進 */
    @media (max-width: 768px) {
        .permission-table-mobile {
            display: block;
        }
        
        .permission-table-mobile tbody,
        .permission-table-mobile tr,
        .permission-table-mobile td {
            display: block;
            width: 100%;
        }
        
        .permission-table-mobile tr {
            border: 1px solid #e5e7eb;
            margin-bottom: 10px;
            padding: 10px;
            border-radius: 8px;
        }
        
        .permission-table-mobile td {
            border: none;
            padding: 5px 0;
            text-align: left;
        }
        
        .permission-table-mobile td:before {
            content: attr(data-label) ": ";
            font-weight: bold;
            color: #6b7280;
        }
    }
</style>
@endpush
@endsection