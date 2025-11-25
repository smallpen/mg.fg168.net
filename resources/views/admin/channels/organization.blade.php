@extends('layouts.admin')

@section('title', '組織架構圖表')

@section('content')
    <div class="space-y-6">
        <!-- 頁面標題 -->
        <div class="flex justify-between items-center">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                    組織架構圖表
                </h1>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                    視覺化顯示代理網絡結構、層級關係和點數分佈
                </p>
            </div>
            
            <!-- 右側操作按鈕 -->
            <div class="flex space-x-3">
                <a 
                    href="{{ route('admin.channels.agents.index') }}"
                    class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 disabled:opacity-25 transition ease-in-out duration-150 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-600"
                >
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                    </svg>
                    代理管理
                </a>
                
                @can('channels.agents.create')
                    <a 
                        href="{{ route('admin.channels.agents.create') }}"
                        class="inline-flex items-center px-4 py-2 bg-primary-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-primary-700 focus:bg-primary-700 active:bg-primary-900 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 transition ease-in-out duration-150"
                    >
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                        </svg>
                        建立代理
                    </a>
                @endcan
            </div>
        </div>

        <!-- Livewire 組織架構圖表元件 -->
        <livewire:admin.channels.organization-chart :rootAgent="$rootAgent" />
    </div>
@endsection

@push('styles')
<style>
    /* 組織圖表自定義樣式 */
    .organization-chart-container {
        position: relative;
        overflow: hidden;
    }
    
    .organization-chart svg {
        font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
    }
    
    .organization-chart .node {
        transition: all 0.3s ease;
    }
    
    .organization-chart .node:hover {
        transform: scale(1.05);
    }
    
    .organization-chart .link {
        transition: all 0.3s ease;
    }
    
    .organization-chart .reset-button:hover rect {
        fill: #1F2937;
    }
    
    /* 側邊欄動畫 */
    #agent-details-sidebar {
        transition: transform 0.3s ease-in-out;
    }
    
    #sidebar-overlay {
        transition: opacity 0.3s ease-in-out;
    }
    
    /* 響應式調整 */
    @media (max-width: 768px) {
        #agent-details-sidebar {
            width: 100%;
        }
    }
    
    /* 深色模式調整 */
    @media (prefers-color-scheme: dark) {
        .organization-chart svg {
            color: #F9FAFB;
        }
    }
</style>
@endpush