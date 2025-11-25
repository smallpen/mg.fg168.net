@extends('layouts.agent')

@section('title', '組織架構')

@section('content')
    <div class="space-y-6">
        <!-- 頁面標題 -->
        <div class="flex justify-between items-center">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                    組織架構
                </h1>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                    查看以您為根節點的組織架構圖，包含所有下層代理和玩家。
                </p>
            </div>
        </div>

        <!-- 組織架構元件 -->
        <livewire:agent.dashboard.organization-chart />
    </div>
@endsection