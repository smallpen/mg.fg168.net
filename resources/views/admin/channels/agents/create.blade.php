@extends('layouts.admin')

@section('title', '建立代理')

@section('content')
    <div class="space-y-6">
        {{-- 頁面標題 --}}
        <div class="flex justify-between items-center">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                    建立代理
                </h1>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                    建立新的代理，可以是第一層代理或下層代理
                </p>
            </div>
            
            <div class="flex space-x-3">
                <a href="{{ route('admin.channels.agents.index') }}" 
                   class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 disabled:opacity-25 transition ease-in-out duration-150">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                    返回列表
                </a>
            </div>
        </div>

        {{-- 代理表單元件 --}}
        <livewire:admin.channels.agent-form :parent-id="$parentId ?? null" />
    </div>
@endsection