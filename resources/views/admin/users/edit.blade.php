@extends('layouts.admin')

@section('title', $pageTitle ?? __('admin.users.edit_user', ['name' => $user->name]))

@section('content')
<x-admin.layout.admin-layout :breadcrumbs="$breadcrumbs ?? []">
    <div class="space-y-6">
        <!-- 頁面標題 -->
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                {{ $pageTitle ?? __('admin.users.edit_user', ['name' => $user->name]) }}
            </h1>
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                {{ __('admin.users.edit_description') }}
            </p>
        </div>

        <!-- 編輯使用者表單 -->
        <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg">
            <livewire:admin.users.user-form :user="$user" />
        </div>
    </div>
</x-admin.layout.admin-layout>
@endsection