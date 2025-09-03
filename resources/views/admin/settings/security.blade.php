@extends('layouts.admin')

@section('title', '安全設定')

@section('content')
<div class="space-y-6">
    
    <!-- 頁面標題和描述 -->
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                安全設定
            </h1>
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                配置系統安全政策、存取控制和安全監控設定
            </p>
        </div>
        
        <div class="flex space-x-3">
            <button type="button" 
                    onclick="showSecurityScan()"
                    class="inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                安全檢測
            </button>
        </div>
    </div>

    <!-- 安全設定表單 -->
    <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg">
        <livewire:admin.settings.security-settings />
    </div>

    <!-- 安全檢測模態對話框 -->
    <div id="securityScanModal" class="fixed inset-0 z-50 overflow-y-auto hidden">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <!-- 背景遮罩 -->
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" onclick="hideSecurityScan()"></div>

            <!-- 對話框內容 -->
            <div class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg px-4 pt-5 pb-4 text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-6xl sm:w-full sm:p-6">
                
                <!-- 標題列 -->
                <div class="flex items-center justify-between mb-6">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white">系統安全檢測</h3>
                    <button onclick="hideSecurityScan()" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>

                <!-- 安全檢測元件 -->
                <div class="max-h-[70vh] overflow-y-auto">
                    <livewire:admin.settings.security-scan />
                </div>

                <!-- 底部按鈕 -->
                <div class="mt-6 flex justify-end">
                    <button onclick="hideSecurityScan()"
                            class="inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500">
                        關閉
                    </button>
                </div>
            </div>
        </div>
    </div>

</div>

<script>
function showSecurityScan() {
    document.getElementById('securityScanModal').classList.remove('hidden');
}

function hideSecurityScan() {
    document.getElementById('securityScanModal').classList.add('hidden');
}
</script>
@endsection