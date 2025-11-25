@extends('layouts.admin')

@section('title', '點數管理 - ' . $agent->name)

@section('content')
    <div class="space-y-6">
        {{-- 頁面標題 --}}
        <div class="flex justify-between items-center">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                    點數管理
                </h1>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                    管理代理 {{ $agent->name }} ({{ $agent->account }}) 的點數分配和回收
                </p>
            </div>
            
            <div class="flex space-x-3">
                <a href="{{ route('admin.channels.agents.show', $agent) }}" 
                   class="inline-flex items-center px-4 py-2 bg-gray-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:bg-gray-700 active:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition ease-in-out duration-150">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                    </svg>
                    返回代理詳情
                </a>
            </div>
        </div>

        {{-- 點數管理元件 --}}
        <livewire:admin.channels.point-management :agent="$agent" />
    </div>
@endsection