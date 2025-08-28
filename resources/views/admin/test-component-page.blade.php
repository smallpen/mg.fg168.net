@extends('layouts.admin')

@section('title', 'Livewire 測試元件')

@section('content')
    <div class="space-y-6">
        <!-- 頁面標題 -->
        <div class="flex justify-between items-center">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                    Livewire 測試元件
                </h1>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                    用於測試和偵錯 Livewire 功能的獨立測試元件
                </p>
            </div>
        </div>

        <!-- 測試元件 -->
        <livewire:admin.test-component />
    </div>
@endsection