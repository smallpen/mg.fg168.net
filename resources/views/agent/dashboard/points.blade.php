@extends('layouts.agent')

@section('title', '點數管理')

@section('content')
    <div class="space-y-6">
        <!-- 頁面標題 -->
        <div class="flex justify-between items-center">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                    點數管理
                </h1>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                    管理您的點數分配，包括分配給下層代理和玩家的點數。
                </p>
            </div>
        </div>

        <!-- 點數管理元件 -->
        <livewire:agent.dashboard.point-management />
    </div>
@endsection