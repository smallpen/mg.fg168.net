@extends('layouts.admin')

@section('title', '系統設定管理')

@push('styles')
<style>
    /* 自訂樣式 */
    .setting-card {
        transition: all 0.2s ease-in-out;
    }
    
    .setting-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
    }
    
    .category-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    }
    
    .search-highlight {
        background-color: #fef3c7;
        padding: 0 2px;
        border-radius: 2px;
    }
    
    .dark .search-highlight {
        background-color: #92400e;
        color: #fbbf24;
    }
    
    .setting-preview-panel {
        backdrop-filter: blur(10px);
        background: rgba(255, 255, 255, 0.95);
    }
    
    .dark .setting-preview-panel {
        background: rgba(31, 41, 55, 0.95);
    }
    
    .category-icon {
        transition: transform 0.2s ease-in-out;
    }
    
    .category-expanded .category-icon {
        transform: rotate(90deg);
    }
    
    @media (max-width: 768px) {
        .mobile-stack {
            flex-direction: column;
        }
        
        .mobile-full {
            width: 100%;
        }
    }
</style>
@endpush

@section('content')
<x-admin.settings.mobile-layout 
    title="系統設定管理" 
    description="集中管理應用程式的各項系統設定和配置參數"
    :show-navigation="true"
    :show-search="true"
>
    <x-slot name="navigation">
        <x-admin.settings.navigation 
            :current-category="request('category', 'all')"
            :categories="config('system-settings.categories', [])"
            :stats="[]"
        />
    </x-slot>

<div class="min-h-screen bg-gray-50 dark:bg-gray-900 hidden lg:block">
    <!-- 頁面標頭區域 -->
    <div class="bg-white dark:bg-gray-800 shadow-sm border-b border-gray-200 dark:border-gray-700">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="py-6">
                <!-- 標題和描述 -->
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div>
                        <h1 class="text-3xl font-bold text-gray-900 dark:text-white">
                            <x-heroicon-o-cog-6-tooth class="inline-block w-8 h-8 mr-3 text-blue-600 dark:text-blue-400" />
                            系統設定管理
                        </h1>
                        <p class="mt-2 text-lg text-gray-600 dark:text-gray-400">
                            集中管理應用程式的各項系統設定和配置參數
                        </p>
                    </div>
                    
                    <!-- 快速操作按鈕 -->
                    <div class="flex flex-wrap items-center gap-3">
                        <div class="flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400 bg-gray-100 dark:bg-gray-700 px-3 py-2 rounded-lg">
                            <x-heroicon-o-clock class="w-4 h-4" />
                            <span>最後更新：{{ now()->format('Y-m-d H:i') }}</span>
                        </div>
                        
                        <a href="{{ route('admin.settings.history') }}" 
                           class="btn btn-outline btn-sm">
                            <x-heroicon-o-clock class="w-4 h-4" />
                            變更歷史
                        </a>
                        
                        <a href="{{ route('admin.settings.backups') }}" 
                           class="btn btn-outline btn-sm">
                            <x-heroicon-o-archive-box class="w-4 h-4" />
                            備份管理
                        </a>
                        
                        <button onclick="showHelpModal()" 
                                class="btn btn-primary btn-sm">
                            <x-heroicon-o-question-mark-circle class="w-4 h-4" />
                            使用說明
                        </button>
                    </div>
                </div>
                
                <!-- 統計資訊卡片 -->
                <div class="mt-6 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <div class="bg-gradient-to-r from-blue-500 to-blue-600 rounded-lg p-4 text-white">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-blue-100 text-sm">總設定數</p>
                                <p class="text-2xl font-bold" id="total-settings">-</p>
                            </div>
                            <x-heroicon-o-cog-6-tooth class="w-8 h-8 text-blue-200" />
                        </div>
                    </div>
                    
                    <div class="bg-gradient-to-r from-green-500 to-green-600 rounded-lg p-4 text-white">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-green-100 text-sm">已變更設定</p>
                                <p class="text-2xl font-bold" id="changed-settings">-</p>
                            </div>
                            <x-heroicon-o-pencil class="w-8 h-8 text-green-200" />
                        </div>
                    </div>
                    
                    <div class="bg-gradient-to-r from-purple-500 to-purple-600 rounded-lg p-4 text-white">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-purple-100 text-sm">設定分類</p>
                                <p class="text-2xl font-bold" id="categories-count">-</p>
                            </div>
                            <x-heroicon-o-squares-2x2 class="w-8 h-8 text-purple-200" />
                        </div>
                    </div>
                    
                    <div class="bg-gradient-to-r from-orange-500 to-orange-600 rounded-lg p-4 text-white">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-orange-100 text-sm">最近備份</p>
                                <p class="text-sm font-medium" id="last-backup">-</p>
                            </div>
                            <x-heroicon-o-shield-check class="w-8 h-8 text-orange-200" />
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 主要內容區域 -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- 搜尋和篩選工具列 -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 mb-8">
            <div class="p-6">
                <div class="flex flex-col lg:flex-row gap-6">
                    <!-- 搜尋區域 -->
                    <div class="flex-1">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            搜尋設定項目
                        </label>
                        <div class="relative">
                            <x-heroicon-o-magnifying-glass class="absolute left-4 top-1/2 transform -translate-y-1/2 w-5 h-5 text-gray-400" />
                            <input type="text" 
                                   id="search-input"
                                   placeholder="輸入設定名稱、描述或關鍵字..."
                                   class="input input-bordered w-full pl-12 pr-12 h-12 text-base"
                                   autocomplete="off">
                            <button id="clear-search" 
                                    class="absolute right-4 top-1/2 transform -translate-y-1/2 text-gray-400 hover:text-gray-600 hidden">
                                <x-heroicon-o-x-mark class="w-5 h-5" />
                            </button>
                        </div>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                            支援模糊搜尋，可搜尋設定名稱、描述、分類等
                        </p>
                    </div>
                    
                    <!-- 篩選區域 -->
                    <div class="lg:w-80">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            篩選條件
                        </label>
                        <div class="grid grid-cols-2 gap-3">
                            <select id="category-filter" class="select select-bordered">
                                <option value="all">所有分類</option>
                                <option value="basic">基本設定</option>
                                <option value="security">安全設定</option>
                                <option value="notification">通知設定</option>
                                <option value="appearance">外觀設定</option>
                                <option value="integration">整合設定</option>
                                <option value="maintenance">維護設定</option>
                            </select>
                            
                            <select id="status-filter" class="select select-bordered">
                                <option value="all">所有狀態</option>
                                <option value="changed">已變更</option>
                                <option value="default">預設值</option>
                                <option value="system">系統設定</option>
                            </select>
                        </div>
                        
                        <div class="mt-3 flex gap-2">
                            <button id="clear-filters" class="btn btn-ghost btn-sm flex-1">
                                <x-heroicon-o-x-mark class="w-4 h-4" />
                                清除篩選
                            </button>
                            <button id="advanced-search" class="btn btn-outline btn-sm flex-1">
                                <x-heroicon-o-adjustments-horizontal class="w-4 h-4" />
                                進階搜尋
                            </button>
                        </div>
                    </div>
                </div>
                
                <!-- 檢視模式切換 -->
                <div class="mt-6 pt-6 border-t border-gray-200 dark:border-gray-700">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                        <div class="flex items-center gap-4">
                            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">檢視模式：</span>
                            <div class="btn-group">
                                <button id="view-category" class="btn btn-sm btn-active">
                                    <x-heroicon-o-squares-2x2 class="w-4 h-4" />
                                    分類檢視
                                </button>
                                <button id="view-list" class="btn btn-sm">
                                    <x-heroicon-o-list-bullet class="w-4 h-4" />
                                    列表檢視
                                </button>
                                <button id="view-grid" class="btn btn-sm">
                                    <x-heroicon-o-squares-plus class="w-4 h-4" />
                                    網格檢視
                                </button>
                            </div>
                        </div>
                        
                        <div class="flex items-center gap-2">
                            <span class="text-sm text-gray-500 dark:text-gray-400">
                                顯示 <span id="filtered-count">0</span> / <span id="total-count">0</span> 項設定
                            </span>
                            <button id="expand-all" class="btn btn-ghost btn-sm">
                                展開全部
                            </button>
                            <button id="collapse-all" class="btn btn-ghost btn-sm">
                                收合全部
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 設定內容區域 -->
        <div id="settings-container">
            <!-- 這裡將由 Livewire 元件渲染 -->
            <livewire:admin.settings.settings-list />
        </div>
    </div>

    <!-- 設定預覽面板 -->
    <div id="preview-panel" class="fixed inset-y-0 right-0 w-96 bg-white dark:bg-gray-800 shadow-2xl transform translate-x-full transition-transform duration-300 ease-in-out z-50 hidden">
        <div class="h-full flex flex-col">
            <!-- 預覽面板標頭 -->
            <div class="flex items-center justify-between p-6 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                    <x-heroicon-o-eye class="inline-block w-5 h-5 mr-2" />
                    設定預覽
                </h3>
                <button id="close-preview" class="btn btn-ghost btn-sm">
                    <x-heroicon-o-x-mark class="w-5 h-5" />
                </button>
            </div>
            
            <!-- 預覽內容 -->
            <div class="flex-1 overflow-y-auto p-6">
                <div id="preview-content">
                    <!-- 預覽內容將在這裡動態載入 -->
                </div>
            </div>
            
            <!-- 預覽面板操作 -->
            <div class="p-6 border-t border-gray-200 dark:border-gray-700">
                <div class="flex gap-3">
                    <button id="apply-preview" class="btn btn-primary flex-1">
                        <x-heroicon-o-check class="w-4 h-4" />
                        套用變更
                    </button>
                    <button id="cancel-preview" class="btn btn-ghost flex-1">
                        <x-heroicon-o-x-mark class="w-4 h-4" />
                        取消
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- 使用說明模態框 -->
    <div id="help-modal" class="modal">
        <div class="modal-box max-w-4xl">
            <h3 class="font-bold text-lg mb-4">
                <x-heroicon-o-question-mark-circle class="inline-block w-6 h-6 mr-2 text-blue-600" />
                系統設定管理使用說明
            </h3>
            
            <div class="space-y-6">
                <!-- 功能概述 -->
                <div>
                    <h4 class="font-semibold text-gray-900 dark:text-white mb-2">功能概述</h4>
                    <p class="text-gray-600 dark:text-gray-400">
                        系統設定管理提供集中化的配置管理功能，支援搜尋、篩選、分類檢視、即時預覽和批量操作。
                    </p>
                </div>
                
                <!-- 主要功能 -->
                <div>
                    <h4 class="font-semibold text-gray-900 dark:text-white mb-3">主要功能</h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="bg-gray-50 dark:bg-gray-700 p-4 rounded-lg">
                            <h5 class="font-medium text-gray-900 dark:text-white mb-2">
                                <x-heroicon-o-magnifying-glass class="inline-block w-4 h-4 mr-1" />
                                智慧搜尋
                            </h5>
                            <p class="text-sm text-gray-600 dark:text-gray-400">
                                支援模糊搜尋設定名稱、描述、分類等，快速定位所需設定項目。
                            </p>
                        </div>
                        
                        <div class="bg-gray-50 dark:bg-gray-700 p-4 rounded-lg">
                            <h5 class="font-medium text-gray-900 dark:text-white mb-2">
                                <x-heroicon-o-funnel class="inline-block w-4 h-4 mr-1" />
                                多維篩選
                            </h5>
                            <p class="text-sm text-gray-600 dark:text-gray-400">
                                按分類、狀態、類型等多個維度篩選設定，提高管理效率。
                            </p>
                        </div>
                        
                        <div class="bg-gray-50 dark:bg-gray-700 p-4 rounded-lg">
                            <h5 class="font-medium text-gray-900 dark:text-white mb-2">
                                <x-heroicon-o-eye class="inline-block w-4 h-4 mr-1" />
                                即時預覽
                            </h5>
                            <p class="text-sm text-gray-600 dark:text-gray-400">
                                支援主題、外觀等設定的即時預覽，變更前先查看效果。
                            </p>
                        </div>
                        
                        <div class="bg-gray-50 dark:bg-gray-700 p-4 rounded-lg">
                            <h5 class="font-medium text-gray-900 dark:text-white mb-2">
                                <x-heroicon-o-shield-check class="inline-block w-4 h-4 mr-1" />
                                安全控制
                            </h5>
                            <p class="text-sm text-gray-600 dark:text-gray-400">
                                敏感設定加密儲存，變更記錄完整追蹤，確保系統安全。
                            </p>
                        </div>
                    </div>
                </div>
                
                <!-- 操作指南 -->
                <div>
                    <h4 class="font-semibold text-gray-900 dark:text-white mb-3">操作指南</h4>
                    <div class="space-y-3">
                        <div class="flex items-start gap-3">
                            <span class="flex-shrink-0 w-6 h-6 bg-blue-100 dark:bg-blue-900 text-blue-600 dark:text-blue-400 rounded-full flex items-center justify-center text-sm font-medium">1</span>
                            <div>
                                <p class="font-medium text-gray-900 dark:text-white">搜尋和篩選</p>
                                <p class="text-sm text-gray-600 dark:text-gray-400">使用搜尋框快速找到設定，或使用篩選器按分類和狀態篩選。</p>
                            </div>
                        </div>
                        
                        <div class="flex items-start gap-3">
                            <span class="flex-shrink-0 w-6 h-6 bg-blue-100 dark:bg-blue-900 text-blue-600 dark:text-blue-400 rounded-full flex items-center justify-center text-sm font-medium">2</span>
                            <div>
                                <p class="font-medium text-gray-900 dark:text-white">編輯設定</p>
                                <p class="text-sm text-gray-600 dark:text-gray-400">點擊設定項目的編輯按鈕，在彈出的表單中修改設定值。</p>
                            </div>
                        </div>
                        
                        <div class="flex items-start gap-3">
                            <span class="flex-shrink-0 w-6 h-6 bg-blue-100 dark:bg-blue-900 text-blue-600 dark:text-blue-400 rounded-full flex items-center justify-center text-sm font-medium">3</span>
                            <div>
                                <p class="font-medium text-gray-900 dark:text-white">預覽變更</p>
                                <p class="text-sm text-gray-600 dark:text-gray-400">對於支援預覽的設定，可以先預覽效果再決定是否套用。</p>
                            </div>
                        </div>
                        
                        <div class="flex items-start gap-3">
                            <span class="flex-shrink-0 w-6 h-6 bg-blue-100 dark:bg-blue-900 text-blue-600 dark:text-blue-400 rounded-full flex items-center justify-center text-sm font-medium">4</span>
                            <div>
                                <p class="font-medium text-gray-900 dark:text-white">備份和還原</p>
                                <p class="text-sm text-gray-600 dark:text-gray-400">重要變更前建議先建立備份，必要時可以快速還原。</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="modal-action">
                <button class="btn" onclick="closeHelpModal()">關閉</button>
            </div>
        </div>
    </div>

    <!-- 進階搜尋模態框 -->
    <div id="advanced-search-modal" class="modal">
        <div class="modal-box">
            <h3 class="font-bold text-lg mb-4">進階搜尋</h3>
            
            <div class="space-y-4">
                <div>
                    <label class="label">
                        <span class="label-text">設定鍵值</span>
                    </label>
                    <input type="text" id="advanced-key" placeholder="例如：app.name" class="input input-bordered w-full" />
                </div>
                
                <div>
                    <label class="label">
                        <span class="label-text">設定類型</span>
                    </label>
                    <select id="advanced-type" class="select select-bordered w-full">
                        <option value="">所有類型</option>
                        <option value="text">文字</option>
                        <option value="number">數字</option>
                        <option value="boolean">布林值</option>
                        <option value="email">電子郵件</option>
                        <option value="url">網址</option>
                        <option value="password">密碼</option>
                        <option value="color">顏色</option>
                        <option value="file">檔案</option>
                    </select>
                </div>
                
                <div>
                    <label class="label">
                        <span class="label-text">變更時間</span>
                    </label>
                    <div class="grid grid-cols-2 gap-2">
                        <input type="date" id="advanced-date-from" class="input input-bordered" />
                        <input type="date" id="advanced-date-to" class="input input-bordered" />
                    </div>
                </div>
                
                <div class="form-control">
                    <label class="label cursor-pointer">
                        <span class="label-text">僅顯示已變更的設定</span>
                        <input type="checkbox" id="advanced-changed-only" class="checkbox" />
                    </label>
                </div>
                
                <div class="form-control">
                    <label class="label cursor-pointer">
                        <span class="label-text">包含系統設定</span>
                        <input type="checkbox" id="advanced-include-system" class="checkbox" checked />
                    </label>
                </div>
            </div>
            
            <div class="modal-action">
                <button class="btn btn-ghost" onclick="closeAdvancedSearch()">取消</button>
                <button class="btn btn-primary" onclick="applyAdvancedSearch()">套用搜尋</button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
// 全域變數
let currentViewMode = 'category';
let searchTimeout = null;

// 頁面載入完成後初始化
document.addEventListener('DOMContentLoaded', function() {
    initializeSettingsManagement();
});

// 初始化設定管理功能
function initializeSettingsManagement() {
    // 初始化搜尋功能
    initializeSearch();
    
    // 初始化篩選功能
    initializeFilters();
    
    // 初始化檢視模式切換
    initializeViewModes();
    
    // 初始化預覽面板
    initializePreviewPanel();
    
    // 載入統計資訊
    loadStatistics();
    
    // 設定鍵盤快捷鍵
    setupKeyboardShortcuts();
}

// 初始化搜尋功能
function initializeSearch() {
    const searchInput = document.getElementById('search-input');
    const clearButton = document.getElementById('clear-search');
    
    searchInput.addEventListener('input', function() {
        const query = this.value.trim();
        
        // 顯示/隱藏清除按鈕
        if (query) {
            clearButton.classList.remove('hidden');
        } else {
            clearButton.classList.add('hidden');
        }
        
        // 防抖搜尋
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            performSearch(query);
        }, 300);
    });
    
    clearButton.addEventListener('click', function() {
        searchInput.value = '';
        clearButton.classList.add('hidden');
        performSearch('');
    });
}

// 執行搜尋
function performSearch(query) {
    // 觸發 Livewire 搜尋
    Livewire.dispatch('search-settings', { query: query });
    
    // 更新 URL 參數（可選）
    const url = new URL(window.location);
    if (query) {
        url.searchParams.set('search', query);
    } else {
        url.searchParams.delete('search');
    }
    window.history.replaceState({}, '', url);
}

// 初始化篩選功能
function initializeFilters() {
    const categoryFilter = document.getElementById('category-filter');
    const statusFilter = document.getElementById('status-filter');
    const clearFilters = document.getElementById('clear-filters');
    
    categoryFilter.addEventListener('change', function() {
        applyFilters();
    });
    
    statusFilter.addEventListener('change', function() {
        applyFilters();
    });
    
    clearFilters.addEventListener('click', function() {
        categoryFilter.value = 'all';
        statusFilter.value = 'all';
        document.getElementById('search-input').value = '';
        document.getElementById('clear-search').classList.add('hidden');
        applyFilters();
        performSearch('');
    });
}

// 套用篩選
function applyFilters() {
    const category = document.getElementById('category-filter').value;
    const status = document.getElementById('status-filter').value;
    
    // 觸發 Livewire 篩選
    Livewire.dispatch('filter-settings', { 
        category: category, 
        status: status 
    });
}

// 初始化檢視模式
function initializeViewModes() {
    const viewButtons = document.querySelectorAll('[id^="view-"]');
    
    viewButtons.forEach(button => {
        button.addEventListener('click', function() {
            const mode = this.id.replace('view-', '');
            switchViewMode(mode);
        });
    });
    
    // 展開/收合按鈕
    document.getElementById('expand-all').addEventListener('click', function() {
        Livewire.dispatch('expand-all-categories');
    });
    
    document.getElementById('collapse-all').addEventListener('click', function() {
        Livewire.dispatch('collapse-all-categories');
    });
}

// 切換檢視模式
function switchViewMode(mode) {
    currentViewMode = mode;
    
    // 更新按鈕狀態
    document.querySelectorAll('[id^="view-"]').forEach(btn => {
        btn.classList.remove('btn-active');
    });
    document.getElementById(`view-${mode}`).classList.add('btn-active');
    
    // 觸發 Livewire 檢視模式變更
    Livewire.dispatch('change-view-mode', { mode: mode });
}

// 初始化預覽面板
function initializePreviewPanel() {
    const previewPanel = document.getElementById('preview-panel');
    const closeButton = document.getElementById('close-preview');
    const applyButton = document.getElementById('apply-preview');
    const cancelButton = document.getElementById('cancel-preview');
    
    closeButton.addEventListener('click', closePreviewPanel);
    cancelButton.addEventListener('click', closePreviewPanel);
    
    applyButton.addEventListener('click', function() {
        // 套用預覽變更
        Livewire.dispatch('apply-preview-changes');
        closePreviewPanel();
    });
    
    // 監聽 Livewire 預覽事件
    Livewire.on('show-setting-preview', (data) => {
        showPreviewPanel(data);
    });
}

// 顯示預覽面板
function showPreviewPanel(data) {
    const panel = document.getElementById('preview-panel');
    const content = document.getElementById('preview-content');
    
    // 載入預覽內容
    content.innerHTML = generatePreviewContent(data);
    
    // 顯示面板
    panel.classList.remove('hidden');
    setTimeout(() => {
        panel.classList.remove('translate-x-full');
    }, 10);
}

// 關閉預覽面板
function closePreviewPanel() {
    const panel = document.getElementById('preview-panel');
    panel.classList.add('translate-x-full');
    setTimeout(() => {
        panel.classList.add('hidden');
    }, 300);
}

// 生成預覽內容
function generatePreviewContent(data) {
    // 根據設定類型生成不同的預覽內容
    switch (data.type) {
        case 'theme':
            return generateThemePreview(data);
        case 'color':
            return generateColorPreview(data);
        case 'layout':
            return generateLayoutPreview(data);
        default:
            return generateDefaultPreview(data);
    }
}

// 載入統計資訊
function loadStatistics() {
    // 這裡可以通過 AJAX 載入統計資訊
    // 或者通過 Livewire 事件獲取
    Livewire.dispatch('load-statistics');
}

// 設定鍵盤快捷鍵
function setupKeyboardShortcuts() {
    document.addEventListener('keydown', function(e) {
        // Ctrl/Cmd + K: 聚焦搜尋框
        if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
            e.preventDefault();
            document.getElementById('search-input').focus();
        }
        
        // Escape: 關閉預覽面板
        if (e.key === 'Escape') {
            closePreviewPanel();
            closeHelpModal();
            closeAdvancedSearch();
        }
        
        // Ctrl/Cmd + H: 顯示說明
        if ((e.ctrlKey || e.metaKey) && e.key === 'h') {
            e.preventDefault();
            showHelpModal();
        }
    });
}

// 顯示使用說明
function showHelpModal() {
    document.getElementById('help-modal').classList.add('modal-open');
}

// 關閉使用說明
function closeHelpModal() {
    document.getElementById('help-modal').classList.remove('modal-open');
}

// 顯示進階搜尋
function showAdvancedSearch() {
    document.getElementById('advanced-search-modal').classList.add('modal-open');
}

// 關閉進階搜尋
function closeAdvancedSearch() {
    document.getElementById('advanced-search-modal').classList.remove('modal-open');
}

// 套用進階搜尋
function applyAdvancedSearch() {
    const key = document.getElementById('advanced-key').value;
    const type = document.getElementById('advanced-type').value;
    const dateFrom = document.getElementById('advanced-date-from').value;
    const dateTo = document.getElementById('advanced-date-to').value;
    const changedOnly = document.getElementById('advanced-changed-only').checked;
    const includeSystem = document.getElementById('advanced-include-system').checked;
    
    // 觸發 Livewire 進階搜尋
    Livewire.dispatch('advanced-search', {
        key: key,
        type: type,
        dateFrom: dateFrom,
        dateTo: dateTo,
        changedOnly: changedOnly,
        includeSystem: includeSystem
    });
    
    closeAdvancedSearch();
}

// 進階搜尋按鈕事件
document.getElementById('advanced-search').addEventListener('click', showAdvancedSearch);

// 監聽 Livewire 統計更新事件
Livewire.on('statistics-updated', (data) => {
    document.getElementById('total-settings').textContent = data.total || '-';
    document.getElementById('changed-settings').textContent = data.changed || '-';
    document.getElementById('categories-count').textContent = data.categories || '-';
    document.getElementById('last-backup').textContent = data.lastBackup || '-';
    document.getElementById('filtered-count').textContent = data.filtered || '0';
    document.getElementById('total-count').textContent = data.total || '0';
});

// 響應式設計支援
function handleResponsiveLayout() {
    const isMobile = window.innerWidth < 768;
    const previewPanel = document.getElementById('preview-panel');
    
    if (isMobile) {
        previewPanel.classList.remove('w-96');
        previewPanel.classList.add('w-full');
    } else {
        previewPanel.classList.remove('w-full');
        previewPanel.classList.add('w-96');
    }
}

// 監聽視窗大小變更
window.addEventListener('resize', handleResponsiveLayout);

// 初始化響應式佈局
handleResponsiveLayout();
</script>
@endpush
</x-admin.settings.mobile-layout>
@endsection