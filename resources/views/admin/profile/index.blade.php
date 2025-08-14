@extends('layouts.admin')

@section('title', '個人資料')

@section('content')
<div class="space-y-6">
    <!-- 頁面標題 -->
    <div class="bg-white dark:bg-gray-800 shadow rounded-lg">
        <div class="px-4 py-5 sm:p-6">
            <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">個人資料</h1>
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                管理您的個人資料和帳號設定
            </p>
        </div>
    </div>

    <!-- 個人資料內容 -->
    <div class="bg-white dark:bg-gray-800 shadow rounded-lg">
        <div class="px-4 py-5 sm:p-6">
            <div class="text-center">
                <div class="w-24 h-24 mx-auto bg-primary-500 rounded-full flex items-center justify-center mb-4">
                    <span class="text-2xl font-medium text-white">
                        {{ auth()->user() ? mb_substr(auth()->user()->name ?? auth()->user()->username ?? 'U', 0, 1) : 'U' }}
                    </span>
                </div>
                <h2 class="text-xl font-medium text-gray-900 dark:text-gray-100">
                    {{ auth()->user()->name ?? auth()->user()->username ?? '使用者' }}
                </h2>
                <p class="text-gray-600 dark:text-gray-400">
                    {{ auth()->user()->email ?? '' }}
                </p>
                <p class="text-sm text-primary-600 dark:text-primary-400 mt-1">
                    {{ auth()->user()->roles->pluck('display_name')->join(', ') ?: '一般使用者' }}
                </p>
            </div>
            
            <div class="mt-8 text-center">
                <p class="text-gray-500 dark:text-gray-400">
                    個人資料管理功能正在開發中...
                </p>
            </div>
        </div>
    </div>
</div>
@endsection