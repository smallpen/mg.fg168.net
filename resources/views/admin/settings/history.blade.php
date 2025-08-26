@extends('layouts.admin')

@section('title', '設定變更歷史')

@section('content')
<div class="space-y-6">
    
    <!-- 頁面標題和描述 -->
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                設定變更歷史
            </h1>
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                檢視系統設定的變更記錄，追蹤配置變更和問題排查
            </p>
        </div>
        
        <div class="flex space-x-3">
            <button type="button" 
                    class="inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4-4m0 0L8 8m4-4v12"></path>
                </svg>
                匯出歷史
            </button>
            <button type="button" 
                    class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                </svg>
                重新整理
            </button>
        </div>
    </div>

    <!-- 變更歷史表單 -->
    <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg">
        <livewire:admin.settings.setting-change-history />
    </div>
</div>

</div>
@endsection