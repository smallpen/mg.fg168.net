@extends('layouts.admin')

@section('title', '建立通知')

@section('content')
    <div class="space-y-6">
        <!-- 頁面標題 -->
        <div class="flex justify-between items-center">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                    建立通知
                </h1>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                    建立新的系統通知並發送給指定使用者
                </p>
            </div>
            
            <div class="flex space-x-3">
                <a href="{{ route('admin.notifications.index') }}" 
                   class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 disabled:opacity-25 transition ease-in-out duration-150">
                    取消
                </a>
            </div>
        </div>

        <!-- 通知表單元件 -->
        <livewire:admin.notifications.notification-form />
    </div>
@endsection