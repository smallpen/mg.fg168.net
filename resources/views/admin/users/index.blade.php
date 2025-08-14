@extends('layouts.admin')

@section('title', $pageTitle ?? __('admin.users.title'))

@section('content')
<x-admin.layout.admin-layout :breadcrumbs="$breadcrumbs ?? []">
    <div class="space-y-6">
        <!-- 頁面標題 -->
        <div class="flex justify-between items-center">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                    {{ $pageTitle ?? __('admin.users.title') }}
                </h1>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                    {{ __('admin.users.description') }}
                </p>
            </div>
            
            @can('users.create')
                <a href="{{ route('admin.users.create') }}" 
                   class="inline-flex items-center px-4 py-2 bg-primary-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-primary-700 focus:bg-primary-700 active:bg-primary-900 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 transition ease-in-out duration-150">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                    </svg>
                    {{ __('admin.users.create') }}
                </a>
            @endcan
        </div>

        <!-- 使用者列表 Livewire 元件 -->
        <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg">
            <livewire:admin.users.user-list />
        </div>
    </div>
</x-admin.layout.admin-layout>
@endsection