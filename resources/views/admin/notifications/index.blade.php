@extends('layouts.admin')

@section('title', '通知管理')

@section('content')
    <div class="space-y-6">
        <!-- 頁面標題 -->
        <div class="flex justify-between items-center">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                    通知管理
                </h1>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                    管理系統通知，包括建立、編輯、發送和刪除通知
                </p>
            </div>
            
            @can('notifications.create')
                <div class="flex space-x-3">
                    <a href="{{ route('admin.notifications.create') }}" 
                       class="inline-flex items-center px-4 py-2 bg-primary-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-primary-700 focus:bg-primary-700 active:bg-primary-900 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 transition ease-in-out duration-150">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        建立通知
                    </a>
                </div>
            @endcan
        </div>

        <!-- 通知列表元件 -->
        <livewire:admin.notifications.notification-list />
    </div>
@endsection