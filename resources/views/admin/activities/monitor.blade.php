@extends('layouts.admin')

@section('title', '即時活動監控')

@section('content')
<div class="space-y-6">
    {{-- 頁面標題 --}}
    <div class="md:flex md:items-center md:justify-between">
        <div class="min-w-0 flex-1">
            <h2 class="text-2xl font-bold leading-7 text-gray-900 sm:truncate sm:text-3xl sm:tracking-tight">
                即時活動監控
            </h2>
            <p class="mt-1 text-sm text-gray-500">
                監控系統活動、檢測安全威脅並管理警報通知
            </p>
        </div>
        
        <div class="mt-4 flex md:ml-4 md:mt-0">
            <a href="{{ route('admin.activities.index') }}" 
               class="inline-flex items-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50">
                <svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                </svg>
                活動記錄
            </a>
            
            <a href="{{ route('admin.activities.stats') }}" 
               class="ml-3 inline-flex items-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50">
                <svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                </svg>
                統計分析
            </a>
        </div>
    </div>

    {{-- 活動監控元件 --}}
    <livewire:admin.activities.activity-monitor />
</div>
@endsection

@push('scripts')
<script>
    // 頁面載入時請求通知權限
    document.addEventListener('DOMContentLoaded', function() {
        if ('Notification' in window && Notification.permission === 'default') {
            Notification.requestPermission().then(function(permission) {
                if (permission === 'granted') {
                    console.log('通知權限已授予');
                } else {
                    console.log('通知權限被拒絕');
                }
            });
        }
    });
</script>
@endpush