@extends('layouts.agent')

@section('title', '代理儀表板')

@section('content')
    <div class="space-y-6">
        <!-- 頁面標題 -->
        <div class="flex justify-between items-center">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                    代理儀表板
                </h1>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                    歡迎回來，{{ $agent->name }}！管理您的代理業務和下層網絡。
                </p>
            </div>
        </div>

        <!-- 儀表板內容 -->
        <livewire:agent.dashboard.agent-dashboard />
    </div>
@endsection