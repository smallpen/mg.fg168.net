@extends('layouts.admin')

@section('title', '角色管理')
@section('page-title', '角色管理')

@section('content')
<div class="space-y-6">
    
    <!-- 頁面標題和操作按鈕 -->
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">角色管理</h1>
            <p class="text-gray-600 dark:text-gray-400 mt-1">管理系統中的所有角色和權限</p>
        </div>
        <div>
            <a href="{{ route('admin.roles.create') }}" class="btn-primary">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                </svg>
                建立角色
            </a>
        </div>
    </div>
    
    <!-- 角色列表 -->
    <div class="card">
        <div class="card-body">
            <p class="text-gray-500 dark:text-gray-400 text-center py-8">
                角色列表功能將在後續任務中實作
            </p>
        </div>
    </div>
    
</div>
@endsection