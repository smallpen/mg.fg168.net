@extends('layouts.admin')

@section('title', '活動記錄匯出')

@section('content')
    <div class="space-y-6">
        <!-- 頁面標題 -->
        <div class="flex justify-between items-center">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                    匯出紀錄
                </h1>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                    匯出活動記錄為多種格式，支援大量資料批量處理
                </p>
            </div>
        </div>

        <!-- 匯出功能 -->
        <livewire:admin.activities.activity-export />
    </div>
@endsection