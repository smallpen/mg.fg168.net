@extends('layouts.admin')

@section('title', '維護設定')
@section('page-title', '維護設定')

@section('content')
<div class="space-y-6">
    
    <!-- 頁面標題和描述 -->
    <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-xl font-semibold text-gray-900 dark:text-white">維護設定</h2>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                    配置系統維護相關設定，包含備份、日誌、快取和監控
                </p>
            </div>
            <div class="flex space-x-3">
                <button type="button" 
                        class="inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                    </svg>
                    清除快取
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
    </div>

    <!-- 維護設定表單 -->
    <div class="bg-white dark:bg-gray-800 shadow rounded-lg">
        <livewire:admin.settings.maintenance-settings />
    </div>

</div>
@endsection