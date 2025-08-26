@extends('layouts.admin')

@section('title', '活動統計分析')

@section('content')
    <div class="space-y-6">
        <!-- 頁面標題 -->
        <div class="flex justify-between items-center">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                    統計分析
                </h1>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                    檢視系統活動的統計資訊和趨勢分析
                </p>
            </div>
        </div>

        <!-- 統計分析內容 -->
        <livewire:admin.activities.activity-stats />
    </div>
@endsection