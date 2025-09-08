@extends('layouts.admin')

@section('title', '維護設定')

@section('content')
<div class="space-y-6">
    
    <!-- 頁面標題和描述 -->
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                維護設定
            </h1>
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                配置系統備份、日誌、快取和維護模式設定
            </p>
        </div>
    </div>

    <!-- 維護設定表單 -->
    <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg">
        <livewire:admin.settings.maintenance-settings />
    </div>

</div>
@endsection