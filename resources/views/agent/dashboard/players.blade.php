@extends('layouts.agent')

@section('title', '玩家管理')

@section('content')
    <div class="space-y-6">
        <!-- 頁面標題 -->
        <div class="flex justify-between items-center">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                    玩家管理
                </h1>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                    管理隸屬於您的玩家，包括建立、編輯和點數分配。
                </p>
            </div>
            
            <div class="flex space-x-3">
                <a href="{{ route('agent.dashboard.create-player') }}" 
                   class="inline-flex items-center px-4 py-2 bg-primary-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-primary-700 focus:bg-primary-700 active:bg-primary-900 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 transition ease-in-out duration-150">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"></path>
                    </svg>
                    建立玩家
                </a>
            </div>
        </div>

        <!-- 玩家管理元件 -->
        <livewire:agent.dashboard.player-management />
    </div>
@endsection