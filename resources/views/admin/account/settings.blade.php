@extends('layouts.admin')

@section('title', '帳號設定')

@section('content')
<div class="space-y-6">
    <!-- 頁面標題 -->
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                帳號設定
            </h1>
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                管理您的帳號安全性和隱私設定
            </p>
        </div>
        
        <div class="flex space-x-3">
            <a href="{{ route('admin.profile') }}" 
               class="inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                </svg>
                個人資料
            </a>
        </div>
    </div>

    <!-- 分頁導航 -->
    <div class="border-b border-gray-200 dark:border-gray-700">
        <nav class="-mb-px flex space-x-8" x-data="{ activeTab: 'password' }">
            <button @click="activeTab = 'password'" 
                    :class="activeTab === 'password' ? 'border-primary-500 text-primary-600 dark:text-primary-400' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300'"
                    class="whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm">
                密碼安全
            </button>
            <button @click="activeTab = 'security'" 
                    :class="activeTab === 'security' ? 'border-primary-500 text-primary-600 dark:text-primary-400' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300'"
                    class="whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm">
                安全設定
            </button>
        </nav>
    </div>

    <!-- 分頁內容 -->
    <div x-data="{ activeTab: 'password' }">
        <!-- 密碼安全分頁 -->
        <div x-show="activeTab === 'password'" x-transition>
            <livewire:admin.profile.password-form />
        </div>

        <!-- 安全設定分頁 -->
        <div x-show="activeTab === 'security'" x-transition>
            <livewire:admin.profile.security-settings />
        </div>
    </div>
</div>
@endsection