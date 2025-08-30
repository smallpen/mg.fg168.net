@extends('layouts.admin')

@section('title', $pageTitle ?? __('admin.roles.create'))

@section('content')
    <div class="space-y-6">
        <!-- 頁面標題 -->
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                {{ __('admin.roles.create') }}
            </h1>
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                {{ __('admin.roles.create_description') }}
            </p>
        </div>

        <!-- 建立角色表單 -->
        <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg">
            <livewire:admin.roles.role-form />
        </div>
    </div>
@endsection