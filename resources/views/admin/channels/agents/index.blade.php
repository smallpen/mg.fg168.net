@extends('layouts.admin')

@section('title', '代理管理')

@section('content')
    <div class="space-y-6">
        {{-- 頁面標題 --}}
        <div class="flex justify-between items-center">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                    代理管理
                </h1>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                    管理多層級代理網絡，點擊代理名稱可檢視下層代理或直屬玩家
                </p>
            </div>
        </div>

        {{-- 代理列表元件 --}}
        <livewire:admin.channels.agent-list />
    </div>
@endsection