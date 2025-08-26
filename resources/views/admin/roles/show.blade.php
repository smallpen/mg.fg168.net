@extends('layouts.admin')

@section('title', __('admin.roles.show.title'))

@push('styles')
<style>
    /* 角色詳情頁面樣式 */
    .role-detail-card {
        transition: all 0.3s ease-in-out;
    }
    
    .role-detail-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 16px rgba(0, 0, 0, 0.1);
    }
    
    .permission-badge {
        transition: all 0.2s ease-in-out;
    }
    
    .permission-badge:hover {
        transform: scale(1.05);
    }
    
    .stats-number {
        font-size: 2rem;
        font-weight: 700;
        color: #3b82f6;
    }
    
    .stats-label {
        font-size: 0.875rem;
        color: #6b7280;
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }
    
    .user-avatar {
        width: 32px;
        height: 32px;
        border-radius: 50%;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-weight: 600;
        font-size: 0.875rem;
    }
    
    .permission-module {
        border-left: 4px solid #e5e7eb;
        padding-left: 1rem;
        margin-bottom: 1.5rem;
    }
    
    .permission-module.has-permissions {
        border-left-color: #10b981;
    }
    
    .permission-item {
        display: flex;
        align-items: center;
        padding: 0.5rem 0;
        border-bottom: 1px solid #f3f4f6;
    }
    
    .permission-item:last-child {
        border-bottom: none;
    }
    
    .permission-icon {
        width: 16px;
        height: 16px;
        margin-right: 0.5rem;
        color: #10b981;
    }
</style>
@endpush

@section('content')
<div class="min-h-screen bg-gray-50 dark:bg-gray-900">
    <div class="container mx-auto px-4 py-6">
        {{-- 麵包屑導航 --}}
        <nav class="flex mb-6" aria-label="Breadcrumb">
            <ol class="inline-flex items-center space-x-1 md:space-x-3">
                <li class="inline-flex items-center">
                    <a href="{{ route('admin.dashboard') }}" class="inline-flex items-center text-sm font-medium text-gray-700 hover:text-blue-600 dark:text-gray-400 dark:hover:text-white">
                        <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z"></path>
                        </svg>
                        {{ __('admin.dashboard.title') }}
                    </a>
                </li>
                <li>
                    <div class="flex items-center">
                        <svg class="w-6 h-6 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"></path>
                        </svg>
                        <a href="{{ route('admin.roles.index') }}" class="ml-1 text-sm font-medium text-gray-700 hover:text-blue-600 md:ml-2 dark:text-gray-400 dark:hover:text-white">
                            {{ __('admin.roles.title') }}
                        </a>
                    </div>
                </li>
                <li>
                    <div class="flex items-center">
                        <svg class="w-6 h-6 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"></path>
                        </svg>
                        <span class="ml-1 text-sm font-medium text-gray-500 md:ml-2 dark:text-gray-400">
                            {{ $role->display_name }}
                        </span>
                    </div>
                </li>
            </ol>
        </nav>

        {{-- 頁面標題和操作按鈕 --}}
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-6">
            <div class="flex items-center mb-4 sm:mb-0">
                <div class="flex-shrink-0">
                    <div class="w-12 h-12 bg-blue-100 dark:bg-blue-900/20 rounded-lg flex items-center justify-center">
                        <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
                        </svg>
                    </div>
                </div>
                <div class="ml-4">
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ $role->display_name }}</h1>
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        {{ __('admin.roles.show.subtitle') }} • {{ $role->name }}
                        @if($role->is_system_role)
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900/20 dark:text-red-400 ml-2">
                                {{ __('admin.roles.system_role') }}
                            </span>
                        @endif
                    </p>
                </div>
            </div>
            
            <div class="flex space-x-3">
                @can('roles.edit')
                    <a href="{{ route('admin.roles.edit', $role) }}" 
                       class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                        </svg>
                        {{ __('admin.common.edit') }}
                    </a>
                @endcan
                
                @can('roles.create')
                    <form action="{{ route('admin.roles.duplicate', $role) }}" method="POST" class="inline">
                        @csrf
                        <button type="submit" 
                                class="inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 text-sm font-medium rounded-md text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                            </svg>
                            {{ __('admin.common.duplicate') }}
                        </button>
                    </form>
                @endcan
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            {{-- 左側：角色資訊和統計 --}}
            <div class="lg:col-span-1 space-y-6">
                {{-- 角色基本資訊 --}}
                <div class="role-detail-card bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">
                        {{ __('admin.roles.show.basic_info') }}
                    </h3>
                    
                    <dl class="space-y-4">
                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('admin.roles.name') }}</dt>
                            <dd class="mt-1 text-sm text-gray-900 dark:text-white font-mono">{{ $role->name }}</dd>
                        </div>
                        
                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('admin.roles.display_name') }}</dt>
                            <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $role->display_name }}</dd>
                        </div>
                        
                        @if($role->description)
                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('admin.roles.description') }}</dt>
                            <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $role->description }}</dd>
                        </div>
                        @endif
                        
                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('admin.common.created_at') }}</dt>
                            <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $role->created_at->format('Y-m-d H:i:s') }}</dd>
                        </div>
                        
                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('admin.common.updated_at') }}</dt>
                            <dd class="mt-1 text-sm text-gray-900 dark:text-white">{{ $role->updated_at->format('Y-m-d H:i:s') }}</dd>
                        </div>
                    </dl>
                </div>

                {{-- 統計資訊 --}}
                <div class="role-detail-card bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">
                        {{ __('admin.roles.show.statistics') }}
                    </h3>
                    
                    <div class="grid grid-cols-2 gap-4">
                        <div class="text-center">
                            <div class="stats-number">{{ $role->users->count() }}</div>
                            <div class="stats-label">{{ __('admin.roles.users_count') }}</div>
                        </div>
                        
                        <div class="text-center">
                            <div class="stats-number">{{ $role->permissions->count() }}</div>
                            <div class="stats-label">{{ __('admin.roles.permissions_count') }}</div>
                        </div>
                    </div>
                </div>

                {{-- 擁有此角色的使用者 --}}
                @if($role->users->count() > 0)
                <div class="role-detail-card bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">
                        {{ __('admin.roles.show.users_with_role') }}
                    </h3>
                    
                    <div class="space-y-3">
                        @foreach($role->users->take(10) as $user)
                        <div class="flex items-center">
                            <div class="user-avatar">
                                {{ strtoupper(substr($user->name, 0, 1)) }}
                            </div>
                            <div class="ml-3">
                                <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $user->name }}</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">{{ $user->email }}</p>
                            </div>
                        </div>
                        @endforeach
                        
                        @if($role->users->count() > 10)
                        <div class="text-center pt-2">
                            <span class="text-sm text-gray-500 dark:text-gray-400">
                                {{ __('admin.common.and_more', ['count' => $role->users->count() - 10]) }}
                            </span>
                        </div>
                        @endif
                    </div>
                </div>
                @endif
            </div>

            {{-- 右側：權限列表 --}}
            <div class="lg:col-span-2">
                <div class="role-detail-card bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6">
                    <div class="flex items-center justify-between mb-6">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white">
                            {{ __('admin.roles.show.permissions') }}
                        </h3>
                        
                        @can('roles.edit')
                        <a href="{{ route('admin.roles.permissions', $role) }}" 
                           class="inline-flex items-center px-3 py-1.5 border border-transparent text-xs font-medium rounded text-blue-700 bg-blue-100 hover:bg-blue-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 dark:bg-blue-900/20 dark:text-blue-400 dark:hover:bg-blue-900/30">
                            <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                            </svg>
                            {{ __('admin.roles.edit_permissions') }}
                        </a>
                        @endcan
                    </div>
                    
                    @if($role->permissions->count() > 0)
                        @php
                            $groupedPermissions = $role->permissions->groupBy('module');
                        @endphp
                        
                        <div class="space-y-6">
                            @foreach($groupedPermissions as $module => $permissions)
                            <div class="permission-module has-permissions">
                                <h4 class="text-base font-medium text-gray-900 dark:text-white mb-3">
                                    {{ __("permissions.modules.{$module}") }}
                                    <span class="text-sm text-gray-500 dark:text-gray-400 font-normal">
                                        ({{ $permissions->count() }})
                                    </span>
                                </h4>
                                
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
                                    @foreach($permissions as $permission)
                                    <div class="permission-item">
                                        <svg class="permission-icon" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                        </svg>
                                        <div>
                                            <p class="text-sm font-medium text-gray-900 dark:text-white">
                                                {{ $permission->display_name }}
                                            </p>
                                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                                {{ $permission->name }}
                                            </p>
                                        </div>
                                    </div>
                                    @endforeach
                                </div>
                            </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-8">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                            </svg>
                            <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">{{ __('admin.roles.no_permissions') }}</h3>
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('admin.roles.no_permissions_description') }}</p>
                            
                            @can('roles.edit')
                            <div class="mt-6">
                                <a href="{{ route('admin.roles.permissions', $role) }}" 
                                   class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                    </svg>
                                    {{ __('admin.roles.assign_permissions') }}
                                </a>
                            </div>
                            @endcan
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // 權限項目動畫
    const permissionItems = document.querySelectorAll('.permission-item');
    
    const observer = new IntersectionObserver((entries) => {
        entries.forEach((entry, index) => {
            if (entry.isIntersecting) {
                setTimeout(() => {
                    entry.target.style.opacity = '1';
                    entry.target.style.transform = 'translateY(0)';
                }, index * 50);
            }
        });
    });
    
    permissionItems.forEach(item => {
        item.style.opacity = '0';
        item.style.transform = 'translateY(10px)';
        item.style.transition = 'opacity 0.3s ease, transform 0.3s ease';
        observer.observe(item);
    });
});
</script>
@endpush