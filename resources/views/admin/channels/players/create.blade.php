@extends('layouts.admin')

@section('title', '建立玩家')

@section('content')
    <div class="space-y-6">
        {{-- 頁面標題 --}}
        <div class="flex justify-between items-center">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                    建立玩家
                </h1>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                    建立新的玩家帳號，設定隸屬代理和初始點數
                </p>
            </div>
            
            <div class="flex space-x-3">
                <a 
                    href="{{ route('admin.channels.players.index') }}"
                    class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-600"
                >
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                    返回列表
                </a>
            </div>
        </div>

        {{-- 玩家表單元件 --}}
        <livewire:admin.channels.player-form />
    </div>
@endsection