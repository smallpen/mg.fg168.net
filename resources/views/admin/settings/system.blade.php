@extends('layouts.admin')

@section('title', '系統設定管理')

@section('content')
<div class="space-y-6">
    <!-- 頁面標題 -->
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                系統設定管理
            </h1>
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                集中管理應用程式的各項系統設定和配置參數
            </p>
        </div>
        
        @can('settings.backup')
            <div class="flex space-x-3">
                <a href="{{ route('admin.settings.history') }}" 
                   class="inline-flex items-center px-4 py-2.5 bg-white dark:bg-gray-700 border-2 border-gray-300 dark:border-gray-500 rounded-lg shadow-md text-sm font-semibold text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-600 hover:border-gray-400 dark:hover:border-gray-400 focus:outline-none focus:ring-3 focus:ring-blue-500 focus:ring-opacity-50 transform hover:scale-105 transition-all duration-200">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    變更歷史
                </a>
                
                <a href="{{ route('admin.settings.backups') }}" 
                   class="inline-flex items-center px-4 py-2.5 bg-gradient-to-r from-green-600 to-green-700 border border-transparent rounded-lg shadow-lg text-sm font-semibold text-white hover:from-green-700 hover:to-green-800 focus:outline-none focus:ring-3 focus:ring-green-500 focus:ring-opacity-50 transform hover:scale-105 transition-all duration-200">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h1.586a1 1 0 01.707.293l1.414 1.414a1 1 0 00.707.293H15a2 2 0 012 2v0M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/>
                    </svg>
                    備份管理
                </a>
            </div>
        @endcan
    </div>

    <!-- 主要內容 -->
    <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg">
        <!-- 這裡將由 Livewire 元件渲染 -->
        <livewire:admin.settings.settings-list />
    </div>
</div>
@endsection