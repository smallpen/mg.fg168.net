@extends('admin.layouts.app')

@section('title', '帳號設定')

@section('content')
<div class="space-y-6">
    <!-- 頁面標題 -->
    <div class="bg-white dark:bg-gray-800 shadow rounded-lg">
        <div class="px-4 py-5 sm:p-6">
            <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">帳號設定</h1>
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                管理您的帳號安全性和偏好設定
            </p>
        </div>
    </div>

    <!-- 帳號設定內容 -->
    <div class="bg-white dark:bg-gray-800 shadow rounded-lg">
        <div class="px-4 py-5 sm:p-6">
            <div class="space-y-6">
                <!-- 安全設定 -->
                <div>
                    <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">安全設定</h3>
                    <div class="space-y-4">
                        <div class="flex items-center justify-between p-4 border border-gray-200 dark:border-gray-700 rounded-lg">
                            <div>
                                <h4 class="font-medium text-gray-900 dark:text-gray-100">密碼</h4>
                                <p class="text-sm text-gray-600 dark:text-gray-400">上次更新：{{ auth()->user()->updated_at->diffForHumans() }}</p>
                            </div>
                            <button class="px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700">
                                更改密碼
                            </button>
                        </div>
                        
                        <div class="flex items-center justify-between p-4 border border-gray-200 dark:border-gray-700 rounded-lg">
                            <div>
                                <h4 class="font-medium text-gray-900 dark:text-gray-100">兩步驟驗證</h4>
                                <p class="text-sm text-gray-600 dark:text-gray-400">增強帳號安全性</p>
                            </div>
                            <button class="px-4 py-2 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700">
                                設定
                            </button>
                        </div>
                    </div>
                </div>

                <!-- 偏好設定 -->
                <div>
                    <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">偏好設定</h3>
                    <div class="space-y-4">
                        <div class="flex items-center justify-between p-4 border border-gray-200 dark:border-gray-700 rounded-lg">
                            <div>
                                <h4 class="font-medium text-gray-900 dark:text-gray-100">主題</h4>
                                <p class="text-sm text-gray-600 dark:text-gray-400">選擇您偏好的介面主題</p>
                            </div>
                            <div class="text-sm text-gray-600 dark:text-gray-400">
                                {{ auth()->user()->theme_preference ?? '亮色' }}
                            </div>
                        </div>
                        
                        <div class="flex items-center justify-between p-4 border border-gray-200 dark:border-gray-700 rounded-lg">
                            <div>
                                <h4 class="font-medium text-gray-900 dark:text-gray-100">語言</h4>
                                <p class="text-sm text-gray-600 dark:text-gray-400">選擇介面語言</p>
                            </div>
                            <div class="text-sm text-gray-600 dark:text-gray-400">
                                正體中文
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="mt-8 text-center">
                <p class="text-gray-500 dark:text-gray-400">
                    帳號設定功能正在開發中...
                </p>
            </div>
        </div>
    </div>
</div>
@endsection