@extends('layouts.admin')

@section('title', $pageTitle ?? __('admin.users.view_user', ['name' => $user->name]))

@section('content')
<x-admin.layout.admin-layout :breadcrumbs="$breadcrumbs ?? []">
    <div class="space-y-6">
        <!-- 頁面標題 -->
        <div class="flex justify-between items-center">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                    {{ $pageTitle ?? __('admin.users.view_user', ['name' => $user->name]) }}
                </h1>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                    {{ __('admin.users.view_description') }}
                </p>
            </div>
            
            <div class="flex space-x-3">
                @can('users.edit')
                    <a href="{{ route('admin.users.edit', $user) }}" 
                       class="inline-flex items-center px-4 py-2 bg-primary-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-primary-700 focus:bg-primary-700 active:bg-primary-900 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 transition ease-in-out duration-150">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                        </svg>
                        {{ __('admin.users.edit') }}
                    </a>
                @endcan
                
                <a href="{{ route('admin.users.index') }}" 
                   class="inline-flex items-center px-4 py-2 bg-gray-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:bg-gray-700 active:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition ease-in-out duration-150">
                    {{ __('admin.common.back') }}
                </a>
            </div>
        </div>

        <!-- 使用者詳細資訊 -->
        <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg">
            <div class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            {{ __('admin.users.name') }}
                        </label>
                        <p class="mt-1 text-sm text-gray-900 dark:text-white">{{ $user->name }}</p>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            {{ __('admin.users.username') }}
                        </label>
                        <p class="mt-1 text-sm text-gray-900 dark:text-white">{{ $user->username }}</p>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            {{ __('admin.users.email') }}
                        </label>
                        <p class="mt-1 text-sm text-gray-900 dark:text-white">{{ $user->email }}</p>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            {{ __('admin.users.status') }}
                        </label>
                        <p class="mt-1">
                            @if($user->is_active)
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300">
                                    {{ __('admin.users.active') }}
                                </span>
                            @else
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300">
                                    {{ __('admin.users.inactive') }}
                                </span>
                            @endif
                        </p>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            {{ __('admin.users.role') }}
                        </label>
                        <div class="mt-1">
                            @if($user->roles->count() > 0)
                                <div class="flex flex-wrap gap-2">
                                    @foreach($user->roles as $role)
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300">
                                            {{ $role->display_name ?? $role->name }}
                                        </span>
                                    @endforeach
                                </div>
                            @else
                                <p class="text-sm text-gray-500 dark:text-gray-400">
                                    {{ __('admin.users.no_roles_assigned') }}
                                </p>
                            @endif
                        </div>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            {{ __('admin.users.created_at') }}
                        </label>
                        <p class="mt-1 text-sm text-gray-900 dark:text-white">{{ $user->created_at->format('Y-m-d H:i:s') }}</p>
                    </div>
                    
                    @if($user->last_login_at)
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            {{ __('admin.users.last_login_at') }}
                        </label>
                        <p class="mt-1 text-sm text-gray-900 dark:text-white">{{ $user->last_login_at->format('Y-m-d H:i:s') }}</p>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-admin.layout.admin-layout>
@endsection