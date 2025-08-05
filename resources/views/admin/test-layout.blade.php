@extends('layouts.admin')

@section('title', '佈局測試')

@section('content')
<div class="space-y-6">
    
    <!-- 測試卡片 -->
    <div class="card">
        <div class="card-header">
            <h2 class="text-xl font-semibold text-gray-900 dark:text-gray-100">
                佈局測試頁面
            </h2>
        </div>
        <div class="card-body">
            <p class="text-gray-600 dark:text-gray-400 mb-4">
                這是一個測試頁面，用來驗證新的 Livewire 佈局系統是否正常運作。
            </p>
            
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <div class="p-4 bg-primary-50 dark:bg-primary-900/20 rounded-lg">
                    <h3 class="font-medium text-primary-900 dark:text-primary-100 mb-2">響應式設計</h3>
                    <p class="text-sm text-primary-700 dark:text-primary-300">
                        佈局會根據螢幕大小自動調整，在行動裝置上側邊欄會變成覆蓋模式。
                    </p>
                </div>
                
                <div class="p-4 bg-success-50 dark:bg-success-900/20 rounded-lg">
                    <h3 class="font-medium text-success-900 dark:text-success-100 mb-2">動態側邊欄</h3>
                    <p class="text-sm text-success-700 dark:text-success-300">
                        點擊頂部的選單按鈕可以切換側邊欄的顯示/隱藏狀態。
                    </p>
                </div>
                
                <div class="p-4 bg-warning-50 dark:bg-warning-900/20 rounded-lg">
                    <h3 class="font-medium text-warning-900 dark:text-warning-100 mb-2">權限控制</h3>
                    <p class="text-sm text-warning-700 dark:text-warning-300">
                        側邊欄選單會根據使用者權限動態顯示可存取的功能。
                    </p>
                </div>
            </div>
        </div>
    </div>
    
    <!-- 測試按鈕 -->
    <div class="card">
        <div class="card-body">
            <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">
                互動測試
            </h3>
            
            <div class="flex flex-wrap gap-4">
                <button onclick="alert('測試按鈕點擊成功！')" 
                        class="btn-primary">
                    測試按鈕
                </button>
                
                <button onclick="window.copyToClipboard('測試文字已複製')" 
                        class="btn-secondary">
                    複製測試
                </button>
                
                <button onclick="window.location.reload()" 
                        class="btn-outline">
                    重新載入頁面
                </button>
            </div>
        </div>
    </div>
    
    <!-- 響應式測試 -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="card">
            <div class="card-header">
                <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                    桌面版功能
                </h3>
            </div>
            <div class="card-body">
                <ul class="space-y-2 text-sm text-gray-600 dark:text-gray-400">
                    <li class="flex items-center">
                        <svg class="w-4 h-4 text-green-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                        </svg>
                        側邊欄推擠內容
                    </li>
                    <li class="flex items-center">
                        <svg class="w-4 h-4 text-green-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                        </svg>
                        完整導航選單
                    </li>
                    <li class="flex items-center">
                        <svg class="w-4 h-4 text-green-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                        </svg>
                        鍵盤快捷鍵支援
                    </li>
                </ul>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                    行動版功能
                </h3>
            </div>
            <div class="card-body">
                <ul class="space-y-2 text-sm text-gray-600 dark:text-gray-400">
                    <li class="flex items-center">
                        <svg class="w-4 h-4 text-green-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                        </svg>
                        側邊欄覆蓋模式
                    </li>
                    <li class="flex items-center">
                        <svg class="w-4 h-4 text-green-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                        </svg>
                        觸控友善介面
                    </li>
                    <li class="flex items-center">
                        <svg class="w-4 h-4 text-green-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                        </svg>
                        遮罩層背景
                    </li>
                </ul>
            </div>
        </div>
    </div>
    
</div>

@push('scripts')
<script>
    // 測試頁面專用的 JavaScript
    console.log('佈局測試頁面已載入');
    
    // 顯示當前視窗大小資訊
    function showWindowInfo() {
        const width = window.innerWidth;
        const height = window.innerHeight;
        const isMobile = width < 1024;
        
        console.log(`視窗大小: ${width}x${height}, 行動模式: ${isMobile}`);
    }
    
    // 初始化和監聽視窗大小變化
    showWindowInfo();
    window.addEventListener('resize', showWindowInfo);
</script>
@endpush