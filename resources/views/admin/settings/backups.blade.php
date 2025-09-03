@extends('layouts.admin')

@section('title', '備份管理')

@section('content')
<div class="space-y-6">
    
    <!-- 頁面標題和描述 -->
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                備份管理
            </h1>
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                管理系統設定的備份，包含建立、還原、比較和下載功能
            </p>
        </div>
    </div>

    <!-- 備份管理表單 -->
    <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg">
        <livewire:admin.settings.setting-backup-manager />
    </div>
</div>
@endsection