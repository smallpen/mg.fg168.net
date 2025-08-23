@props([
    'title' => '系統設定',
    'description' => '管理系統配置和參數',
    'showNavigation' => true,
    'showSearch' => true
])

<div class="min-h-screen bg-gray-50 dark:bg-gray-900" x-data="mobileSettingsLayout()">
    <!-- 行動版頂部導航 -->
    <div class="lg:hidden bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 sticky top-0 z-40">
        <div class="flex items-center justify-between p-4">
            <div class="flex items-center gap-3">
                <button 
                    @click="toggleSidebar()"
                    class="btn btn-ghost btn-sm"
                >
                    <x-heroicon-o-bars-3 class="w-5 h-5" />
                </button>
                <div>
                    <h1 class="text-lg font-semibold text-gray-900 dark:text-white">{{ $title }}</h1>
                    <p class="text-xs text-gray-600 dark:text-gray-400">{{ $description }}</p>
                </div>
            </div>
            
            <div class="flex items-center gap-2">
                <!-- 搜尋按鈕 -->
                @if($showSearch)
                    <button 
                        @click="toggleSearch()"
                        class="btn btn-ghost btn-sm"
                    >
                        <x-heroicon-o-magnifying-glass class="w-5 h-5" />
                    </button>
                @endif
                
                <!-- 更多選項 -->
                <div class="dropdown dropdown-end">
                    <button tabindex="0" class="btn btn-ghost btn-sm">
                        <x-heroicon-o-ellipsis-vertical class="w-5 h-5" />
                    </button>
                    <ul tabindex="0" class="dropdown-content z-[1] menu p-2 shadow bg-white dark:bg-gray-800 rounded-box w-52 border border-gray-200 dark:border-gray-700">
                        <li>
                            <a @click="openQuickActions()">
                                <x-heroicon-o-bolt class="w-4 h-4" />
                                快速操作
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('admin.settings.backups') }}">
                                <x-heroicon-o-archive-box class="w-4 h-4" />
                                備份管理
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('admin.settings.history') }}">
                                <x-heroicon-o-clock class="w-4 h-4" />
                                變更歷史
                            </a>
                        </li>
                        <li>
                            <a @click="showHelp()">
                                <x-heroicon-o-question-mark-circle class="w-4 h-4" />
                                使用說明
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
        
        <!-- 行動版搜尋列 -->
        <div 
            x-show="showMobileSearch"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 transform -translate-y-2"
            x-transition:enter-end="opacity-100 transform translate-y-0"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100 transform translate-y-0"
            x-transition:leave-end="opacity-0 transform -translate-y-2"
            class="px-4 pb-4"
        >
            <div class="relative">
                <x-heroicon-o-magnifying-glass class="absolute left-3 top-1/2 transform -translate-y-1/2 w-4 h-4 text-gray-400" />
                <input 
                    type="text" 
                    placeholder="搜尋設定..."
                    class="input input-bordered w-full pl-10 pr-10"
                    x-model="searchQuery"
                    @input="performSearch()"
                />
                <button 
                    x-show="searchQuery"
                    @click="clearSearch()"
                    class="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-400"
                >
                    <x-heroicon-o-x-mark class="w-4 h-4" />
                </button>
            </div>
        </div>
    </div>

    <!-- 側邊欄遮罩 -->
    <div 
        x-show="showSidebar"
        @click="closeSidebar()"
        class="fixed inset-0 bg-black bg-opacity-50 z-40 lg:hidden"
        x-transition:enter="transition-opacity ease-linear duration-300"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition-opacity ease-linear duration-300"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
    ></div>

    <!-- 側邊欄 -->
    <div 
        x-show="showSidebar"
        class="fixed inset-y-0 left-0 w-80 bg-white dark:bg-gray-800 shadow-xl z-50 lg:hidden overflow-y-auto"
        x-transition:enter="transition ease-in-out duration-300 transform"
        x-transition:enter-start="-translate-x-full"
        x-transition:enter-end="translate-x-0"
        x-transition:leave="transition ease-in-out duration-300 transform"
        x-transition:leave-start="translate-x-0"
        x-transition:leave-end="-translate-x-full"
    >
        <!-- 側邊欄標頭 -->
        <div class="flex items-center justify-between p-4 border-b border-gray-200 dark:border-gray-700">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">設定分類</h2>
            <button 
                @click="closeSidebar()"
                class="btn btn-ghost btn-sm"
            >
                <x-heroicon-o-x-mark class="w-5 h-5" />
            </button>
        </div>
        
        <!-- 側邊欄內容 -->
        <div class="p-4">
            @if($showNavigation)
                {{ $navigation ?? '' }}
            @endif
        </div>
    </div>

    <!-- 主要內容區域 -->
    <div class="lg:flex">
        <!-- 桌面版側邊欄 -->
        @if($showNavigation)
            <div class="hidden lg:block lg:w-80 lg:flex-shrink-0">
                <div class="h-full bg-white dark:bg-gray-800 border-r border-gray-200 dark:border-gray-700 overflow-y-auto">
                    <div class="p-6">
                        {{ $navigation ?? '' }}
                    </div>
                </div>
            </div>
        @endif
        
        <!-- 主要內容 -->
        <div class="flex-1 min-w-0">
            <div class="p-4 lg:p-8">
                {{ $slot }}
            </div>
        </div>
    </div>

    <!-- 快速操作面板 -->
    <div 
        x-show="showQuickActions"
        class="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-end lg:items-center lg:justify-center"
        @click="closeQuickActions()"
    >
        <div 
            @click.stop
            class="bg-white dark:bg-gray-800 w-full lg:w-96 lg:rounded-lg shadow-xl"
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 transform translate-y-full lg:translate-y-0 lg:scale-95"
            x-transition:enter-end="opacity-100 transform translate-y-0 lg:scale-100"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100 transform translate-y-0 lg:scale-100"
            x-transition:leave-end="opacity-0 transform translate-y-full lg:translate-y-0 lg:scale-95"
        >
            <!-- 快速操作標頭 -->
            <div class="flex items-center justify-between p-4 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">快速操作</h3>
                <button 
                    @click="closeQuickActions()"
                    class="btn btn-ghost btn-sm"
                >
                    <x-heroicon-o-x-mark class="w-5 h-5" />
                </button>
            </div>
            
            <!-- 快速操作內容 -->
            <div class="p-4 space-y-3">
                <button 
                    @click="exportAllSettings()"
                    class="w-full flex items-center gap-3 p-3 text-left hover:bg-gray-50 dark:hover:bg-gray-700 rounded-lg transition-colors"
                >
                    <div class="p-2 bg-blue-100 dark:bg-blue-900/30 rounded-lg">
                        <x-heroicon-o-arrow-down-tray class="w-5 h-5 text-blue-600 dark:text-blue-400" />
                    </div>
                    <div>
                        <div class="font-medium text-gray-900 dark:text-white">匯出所有設定</div>
                        <div class="text-sm text-gray-600 dark:text-gray-400">下載完整的設定檔案</div>
                    </div>
                </button>
                
                <button 
                    @click="importSettings()"
                    class="w-full flex items-center gap-3 p-3 text-left hover:bg-gray-50 dark:hover:bg-gray-700 rounded-lg transition-colors"
                >
                    <div class="p-2 bg-green-100 dark:bg-green-900/30 rounded-lg">
                        <x-heroicon-o-arrow-up-tray class="w-5 h-5 text-green-600 dark:text-green-400" />
                    </div>
                    <div>
                        <div class="font-medium text-gray-900 dark:text-white">匯入設定</div>
                        <div class="text-sm text-gray-600 dark:text-gray-400">從檔案匯入設定</div>
                    </div>
                </button>
                
                <button 
                    @click="createBackup()"
                    class="w-full flex items-center gap-3 p-3 text-left hover:bg-gray-50 dark:hover:bg-gray-700 rounded-lg transition-colors"
                >
                    <div class="p-2 bg-purple-100 dark:bg-purple-900/30 rounded-lg">
                        <x-heroicon-o-archive-box class="w-5 h-5 text-purple-600 dark:text-purple-400" />
                    </div>
                    <div>
                        <div class="font-medium text-gray-900 dark:text-white">建立備份</div>
                        <div class="text-sm text-gray-600 dark:text-gray-400">備份目前的設定</div>
                    </div>
                </button>
                
                <button 
                    @click="resetAllSettings()"
                    class="w-full flex items-center gap-3 p-3 text-left hover:bg-gray-50 dark:hover:bg-gray-700 rounded-lg transition-colors"
                >
                    <div class="p-2 bg-orange-100 dark:bg-orange-900/30 rounded-lg">
                        <x-heroicon-o-arrow-path class="w-5 h-5 text-orange-600 dark:text-orange-400" />
                    </div>
                    <div>
                        <div class="font-medium text-gray-900 dark:text-white">重設所有設定</div>
                        <div class="text-sm text-gray-600 dark:text-gray-400">恢復為預設值</div>
                    </div>
                </button>
            </div>
        </div>
    </div>

    <!-- 浮動操作按鈕 (行動版) -->
    <div class="fixed bottom-6 right-6 lg:hidden z-30">
        <div class="flex flex-col gap-3">
            <!-- 主要操作按鈕 -->
            <button 
                @click="toggleFab()"
                class="btn btn-circle btn-primary btn-lg shadow-lg"
                :class="showFabMenu ? 'rotate-45' : ''"
            >
                <x-heroicon-o-plus class="w-6 h-6" />
            </button>
            
            <!-- 子操作按鈕 -->
            <div 
                x-show="showFabMenu"
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0 transform scale-95"
                x-transition:enter-end="opacity-100 transform scale-100"
                x-transition:leave="transition ease-in duration-150"
                x-transition:leave-start="opacity-100 transform scale-100"
                x-transition:leave-end="opacity-0 transform scale-95"
                class="flex flex-col gap-2"
            >
                <button 
                    @click="scrollToTop()"
                    class="btn btn-circle btn-ghost bg-white dark:bg-gray-800 shadow-lg"
                    title="回到頂部"
                >
                    <x-heroicon-o-arrow-up class="w-5 h-5" />
                </button>
                
                <button 
                    @click="openQuickActions()"
                    class="btn btn-circle btn-ghost bg-white dark:bg-gray-800 shadow-lg"
                    title="快速操作"
                >
                    <x-heroicon-o-bolt class="w-5 h-5" />
                </button>
                
                <button 
                    @click="toggleSearch()"
                    class="btn btn-circle btn-ghost bg-white dark:bg-gray-800 shadow-lg"
                    title="搜尋"
                >
                    <x-heroicon-o-magnifying-glass class="w-5 h-5" />
                </button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function mobileSettingsLayout() {
    return {
        showSidebar: false,
        showMobileSearch: false,
        showQuickActions: false,
        showFabMenu: false,
        searchQuery: '',
        
        // 切換側邊欄
        toggleSidebar() {
            this.showSidebar = !this.showSidebar;
        },
        
        // 關閉側邊欄
        closeSidebar() {
            this.showSidebar = false;
        },
        
        // 切換搜尋
        toggleSearch() {
            this.showMobileSearch = !this.showMobileSearch;
            if (this.showMobileSearch) {
                this.$nextTick(() => {
                    const input = this.$el.querySelector('input[x-model="searchQuery"]');
                    if (input) input.focus();
                });
            }
        },
        
        // 執行搜尋
        performSearch() {
            // 觸發 Livewire 搜尋
            Livewire.dispatch('search-settings', { query: this.searchQuery });
        },
        
        // 清除搜尋
        clearSearch() {
            this.searchQuery = '';
            this.performSearch();
        },
        
        // 開啟快速操作
        openQuickActions() {
            this.showQuickActions = true;
            this.showFabMenu = false;
        },
        
        // 關閉快速操作
        closeQuickActions() {
            this.showQuickActions = false;
        },
        
        // 切換浮動按鈕選單
        toggleFab() {
            this.showFabMenu = !this.showFabMenu;
        },
        
        // 回到頂部
        scrollToTop() {
            window.scrollTo({ top: 0, behavior: 'smooth' });
            this.showFabMenu = false;
        },
        
        // 匯出所有設定
        exportAllSettings() {
            Livewire.dispatch('export-all-settings');
            this.closeQuickActions();
        },
        
        // 匯入設定
        importSettings() {
            Livewire.dispatch('open-import-dialog');
            this.closeQuickActions();
        },
        
        // 建立備份
        createBackup() {
            Livewire.dispatch('create-backup');
            this.closeQuickActions();
        },
        
        // 重設所有設定
        resetAllSettings() {
            if (confirm('確定要重設所有設定為預設值嗎？此操作無法復原。')) {
                Livewire.dispatch('reset-all-settings');
            }
            this.closeQuickActions();
        },
        
        // 顯示說明
        showHelp() {
            // 觸發說明對話框
            document.getElementById('help-modal')?.classList.add('modal-open');
        }
    };
}

// 處理視窗大小變更
window.addEventListener('resize', function() {
    if (window.innerWidth >= 1024) {
        // 桌面版時關閉行動版元素
        const layout = Alpine.$data(document.querySelector('[x-data*="mobileSettingsLayout"]'));
        if (layout) {
            layout.showSidebar = false;
            layout.showMobileSearch = false;
            layout.showFabMenu = false;
        }
    }
});

// 處理返回鍵
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        const layout = Alpine.$data(document.querySelector('[x-data*="mobileSettingsLayout"]'));
        if (layout) {
            if (layout.showQuickActions) {
                layout.closeQuickActions();
            } else if (layout.showSidebar) {
                layout.closeSidebar();
            } else if (layout.showMobileSearch) {
                layout.toggleSearch();
            }
        }
    }
});
</script>
@endpush