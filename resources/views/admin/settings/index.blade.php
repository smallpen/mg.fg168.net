@extends('layouts.admin')

@section('title', '系統設定')
@section('page-title', '系統設定')

@section('content')
<div class="space-y-6">
    
    <!-- 頁面標題 -->
    <div>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">系統設定</h1>
        <p class="text-gray-600 dark:text-gray-400 mt-1">管理系統的各項設定和配置</p>
    </div>
    
    <!-- 設定選項 -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        
        <!-- 基本設定 -->
        <div class="card hover:shadow-lg transition-shadow">
            <div class="card-body">
                <div class="flex items-center mb-4">
                    <div class="p-3 bg-blue-100 dark:bg-blue-900/30 rounded-lg">
                        <x-heroicon-o-cog-6-tooth class="w-6 h-6 text-blue-600 dark:text-blue-400" />
                    </div>
                    <div class="ml-4">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">基本設定</h3>
                        <p class="text-sm text-gray-600 dark:text-gray-400">應用程式基本資訊和行為設定</p>
                    </div>
                </div>
                <div class="space-y-2 text-sm text-gray-600 dark:text-gray-400 mb-4">
                    <div class="flex items-center">
                        <x-heroicon-o-check class="w-4 h-4 text-green-500 mr-2" />
                        應用程式名稱和描述
                    </div>
                    <div class="flex items-center">
                        <x-heroicon-o-check class="w-4 h-4 text-green-500 mr-2" />
                        時區和語言設定
                    </div>
                    <div class="flex items-center">
                        <x-heroicon-o-check class="w-4 h-4 text-green-500 mr-2" />
                        日期時間格式
                    </div>
                </div>
                <a href="{{ route('admin.settings.basic') }}" class="btn btn-primary btn-sm w-full">
                    管理基本設定
                </a>
            </div>
        </div>

        <!-- 系統設定 -->
        <div class="card hover:shadow-lg transition-shadow">
            <div class="card-body">
                <div class="flex items-center mb-4">
                    <div class="p-3 bg-green-100 dark:bg-green-900/30 rounded-lg">
                        <x-heroicon-o-adjustments-horizontal class="w-6 h-6 text-green-600 dark:text-green-400" />
                    </div>
                    <div class="ml-4">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">系統設定</h3>
                        <p class="text-sm text-gray-600 dark:text-gray-400">完整的系統設定管理</p>
                    </div>
                </div>
                <div class="space-y-2 text-sm text-gray-600 dark:text-gray-400 mb-4">
                    <div class="flex items-center">
                        <x-heroicon-o-check class="w-4 h-4 text-green-500 mr-2" />
                        所有設定分類管理
                    </div>
                    <div class="flex items-center">
                        <x-heroicon-o-check class="w-4 h-4 text-green-500 mr-2" />
                        搜尋和篩選功能
                    </div>
                    <div class="flex items-center">
                        <x-heroicon-o-check class="w-4 h-4 text-green-500 mr-2" />
                        批量操作和匯入匯出
                    </div>
                </div>
                <a href="{{ route('admin.settings.system') }}" class="btn btn-primary btn-sm w-full">
                    管理系統設定
                </a>
            </div>
        </div>

        <!-- 設定備份 -->
        <div class="card hover:shadow-lg transition-shadow">
            <div class="card-body">
                <div class="flex items-center mb-4">
                    <div class="p-3 bg-purple-100 dark:bg-purple-900/30 rounded-lg">
                        <x-heroicon-o-archive-box class="w-6 h-6 text-purple-600 dark:text-purple-400" />
                    </div>
                    <div class="ml-4">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">設定備份</h3>
                        <p class="text-sm text-gray-600 dark:text-gray-400">備份和還原系統設定</p>
                    </div>
                </div>
                <div class="space-y-2 text-sm text-gray-600 dark:text-gray-400 mb-4">
                    <div class="flex items-center">
                        <x-heroicon-o-check class="w-4 h-4 text-green-500 mr-2" />
                        建立設定備份
                    </div>
                    <div class="flex items-center">
                        <x-heroicon-o-check class="w-4 h-4 text-green-500 mr-2" />
                        還原歷史備份
                    </div>
                    <div class="flex items-center">
                        <x-heroicon-o-check class="w-4 h-4 text-green-500 mr-2" />
                        備份比較和差異
                    </div>
                </div>
                <a href="{{ route('admin.settings.backups') }}" class="btn btn-primary btn-sm w-full">
                    管理設定備份
                </a>
            </div>
        </div>

        <!-- 變更歷史 -->
        <div class="card hover:shadow-lg transition-shadow">
            <div class="card-body">
                <div class="flex items-center mb-4">
                    <div class="p-3 bg-orange-100 dark:bg-orange-900/30 rounded-lg">
                        <x-heroicon-o-clock class="w-6 h-6 text-orange-600 dark:text-orange-400" />
                    </div>
                    <div class="ml-4">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">變更歷史</h3>
                        <p class="text-sm text-gray-600 dark:text-gray-400">追蹤設定變更記錄</p>
                    </div>
                </div>
                <div class="space-y-2 text-sm text-gray-600 dark:text-gray-400 mb-4">
                    <div class="flex items-center">
                        <x-heroicon-o-check class="w-4 h-4 text-green-500 mr-2" />
                        設定變更記錄
                    </div>
                    <div class="flex items-center">
                        <x-heroicon-o-check class="w-4 h-4 text-green-500 mr-2" />
                        操作者和時間追蹤
                    </div>
                    <div class="flex items-center">
                        <x-heroicon-o-check class="w-4 h-4 text-green-500 mr-2" />
                        一鍵回復功能
                    </div>
                </div>
                <a href="{{ route('admin.settings.history') }}" class="btn btn-primary btn-sm w-full">
                    查看變更歷史
                </a>
            </div>
        </div>

        <!-- 安全設定 -->
        <div class="card hover:shadow-lg transition-shadow opacity-60">
            <div class="card-body">
                <div class="flex items-center mb-4">
                    <div class="p-3 bg-red-100 dark:bg-red-900/30 rounded-lg">
                        <x-heroicon-o-shield-check class="w-6 h-6 text-red-600 dark:text-red-400" />
                    </div>
                    <div class="ml-4">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">安全設定</h3>
                        <p class="text-sm text-gray-600 dark:text-gray-400">系統安全政策和認證設定</p>
                    </div>
                </div>
                <div class="space-y-2 text-sm text-gray-600 dark:text-gray-400 mb-4">
                    <div class="flex items-center">
                        <x-heroicon-o-clock class="w-4 h-4 text-gray-400 mr-2" />
                        密碼政策設定
                    </div>
                    <div class="flex items-center">
                        <x-heroicon-o-clock class="w-4 h-4 text-gray-400 mr-2" />
                        登入安全設定
                    </div>
                    <div class="flex items-center">
                        <x-heroicon-o-clock class="w-4 h-4 text-gray-400 mr-2" />
                        雙因子認證
                    </div>
                </div>
                <button class="btn btn-ghost btn-sm w-full" disabled>
                    即將推出
                </button>
            </div>
        </div>

        <!-- 外觀設定 -->
        <div class="card hover:shadow-lg transition-shadow opacity-60">
            <div class="card-body">
                <div class="flex items-center mb-4">
                    <div class="p-3 bg-pink-100 dark:bg-pink-900/30 rounded-lg">
                        <x-heroicon-o-paint-brush class="w-6 h-6 text-pink-600 dark:text-pink-400" />
                    </div>
                    <div class="ml-4">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">外觀設定</h3>
                        <p class="text-sm text-gray-600 dark:text-gray-400">主題、顏色和使用者介面設定</p>
                    </div>
                </div>
                <div class="space-y-2 text-sm text-gray-600 dark:text-gray-400 mb-4">
                    <div class="flex items-center">
                        <x-heroicon-o-clock class="w-4 h-4 text-gray-400 mr-2" />
                        主題和顏色設定
                    </div>
                    <div class="flex items-center">
                        <x-heroicon-o-clock class="w-4 h-4 text-gray-400 mr-2" />
                        標誌和圖片上傳
                    </div>
                    <div class="flex items-center">
                        <x-heroicon-o-clock class="w-4 h-4 text-gray-400 mr-2" />
                        即時預覽功能
                    </div>
                </div>
                <button class="btn btn-ghost btn-sm w-full" disabled>
                    即將推出
                </button>
            </div>
        </div>
        
    </div>
    
</div>
@endsection