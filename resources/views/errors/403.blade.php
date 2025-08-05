@extends('layouts.admin')

@section('title', '存取被拒絕')

@section('content')
<div class="min-h-screen flex items-center justify-center bg-gray-50 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full space-y-8">
        <div class="text-center">
            <!-- 403 圖示 -->
            <div class="mx-auto h-24 w-24 text-red-500 mb-6">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m0 0v2m0-2h2m-2 0H10m2-5V9m0 0V7m0 2h2m-2 0H10m8-2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
            </div>
            
            <h2 class="mt-6 text-3xl font-extrabold text-gray-900">
                存取被拒絕
            </h2>
            
            <p class="mt-2 text-sm text-gray-600">
                {{ $exception->getMessage() ?: '您沒有權限存取此頁面' }}
            </p>
        </div>

        <div class="mt-8 space-y-4">
            <!-- 返回按鈕 -->
            <div class="flex flex-col space-y-2">
                <a href="{{ route('admin.dashboard') }}" 
                   class="group relative w-full flex justify-center py-2 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    <span class="absolute left-0 inset-y-0 flex items-center pl-3">
                        <svg class="h-5 w-5 text-indigo-500 group-hover:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
                        </svg>
                    </span>
                    返回儀表板
                </a>
                
                <button onclick="history.back()" 
                        class="w-full flex justify-center py-2 px-4 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    返回上一頁
                </button>
            </div>
        </div>

        <!-- 聯絡資訊 -->
        <div class="mt-6 text-center">
            <p class="text-xs text-gray-500">
                如果您認為這是錯誤，請聯絡系統管理員
            </p>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    // 自動返回功能（可選）
    setTimeout(function() {
        const returnButton = document.querySelector('a[href="{{ route('admin.dashboard') }}"]');
        if (returnButton) {
            returnButton.focus();
        }
    }, 100);
</script>
@endpush