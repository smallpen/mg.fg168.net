@extends('layouts.admin')

@section('title', '安全設定')

@section('content')
<div class="space-y-6">
    
    <!-- 頁面標題和描述 -->
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                安全設定
            </h1>
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                配置系統安全政策、存取控制和安全監控設定
            </p>
        </div>
        
        <div class="flex space-x-3">
            <button type="button" 
                    class="inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                安全檢測
            </button>
            <button type="button" 
                    class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                </svg>
                儲存設定
            </button>
        </div>
    </div>

    <!-- 安全設定表單 -->
    <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg">
        <livewire:admin.settings.security-settings />
    </div>

</div>
@endsection