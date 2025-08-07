<div class="min-h-screen bg-gray-50 dark:bg-gray-900" x-data="{ 
    isMobile: window.innerWidth < 1024,
    init() {
        // 監聽視窗大小變化
        window.addEventListener('resize', () => {
            const newIsMobile = window.innerWidth < 1024;
            if (this.isMobile !== newIsMobile) {
                this.isMobile = newIsMobile;
                @this.call('setMobileMode', newIsMobile);
            }
        });
        
        // 初始化時設定行動裝置模式
        @this.call('setMobileMode', this.isMobile);
    }
}">
    
    <!-- 遮罩層（僅在行動裝置模式下顯示） -->
    <div class="{{ $this->getLayoutClasses()['overlay'] }}" 
         wire:click="closeSidebar"
         x-show="$wire.sidebarOpen && isMobile"
         x-transition:enter="transition-opacity ease-linear duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition-opacity ease-linear duration-300"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0">
    </div>
    
    <!-- 側邊導航選單 -->
    <aside class="{{ $this->getLayoutClasses()['sidebar'] }}">
        @livewire('admin.layout.sidebar')
    </aside>
    
    <!-- 主內容區域 -->
    <div class="{{ $this->getLayoutClasses()['main'] }}">
        
        <!-- 頂部導航列 -->
        <header class="bg-white dark:bg-gray-800 shadow-sm border-b border-gray-200 dark:border-gray-700 relative z-30">
            @livewire('admin.layout.top-bar')
        </header>
        
        <!-- 主要內容 -->
        <main class="flex-1 p-4 sm:p-6">
            
            <!-- 通知訊息區域 -->
            <div class="mb-6">
                @if (session('success'))
                    <div class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 text-green-800 dark:text-green-200 px-4 py-3 rounded-md mb-4" 
                         x-data="{ show: true }" 
                         x-show="show"
                         x-transition:enter="transition ease-out duration-300"
                         x-transition:enter-start="opacity-0 transform scale-90"
                         x-transition:enter-end="opacity-100 transform scale-100"
                         x-transition:leave="transition ease-in duration-300"
                         x-transition:leave-start="opacity-100 transform scale-100"
                         x-transition:leave-end="opacity-0 transform scale-90"
                         x-init="setTimeout(() => show = false, 5000)">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center">
                                <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                </svg>
                                <span>{{ session('success') }}</span>
                            </div>
                            <button @click="show = false" class="text-green-600 dark:text-green-400 hover:text-green-800 dark:hover:text-green-200">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            </button>
                        </div>
                    </div>
                @endif
                
                @if (session('error'))
                    <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 text-red-800 dark:text-red-200 px-4 py-3 rounded-md mb-4" 
                         x-data="{ show: true }" 
                         x-show="show"
                         x-transition:enter="transition ease-out duration-300"
                         x-transition:enter-start="opacity-0 transform scale-90"
                         x-transition:enter-end="opacity-100 transform scale-100"
                         x-transition:leave="transition ease-in duration-300"
                         x-transition:leave-start="opacity-100 transform scale-100"
                         x-transition:leave-end="opacity-0 transform scale-90"
                         x-init="setTimeout(() => show = false, 5000)">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center">
                                <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                                </svg>
                                <span>{{ session('error') }}</span>
                            </div>
                            <button @click="show = false" class="text-red-600 dark:text-red-400 hover:text-red-800 dark:hover:text-red-200">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            </button>
                        </div>
                    </div>
                @endif
                
                @if (session('warning'))
                    <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 text-yellow-800 dark:text-yellow-200 px-4 py-3 rounded-md mb-4" 
                         x-data="{ show: true }" 
                         x-show="show"
                         x-transition:enter="transition ease-out duration-300"
                         x-transition:enter-start="opacity-0 transform scale-90"
                         x-transition:enter-end="opacity-100 transform scale-100"
                         x-transition:leave="transition ease-in duration-300"
                         x-transition:leave-start="opacity-100 transform scale-100"
                         x-transition:leave-end="opacity-0 transform scale-90"
                         x-init="setTimeout(() => show = false, 5000)">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center">
                                <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                                </svg>
                                <span>{{ session('warning') }}</span>
                            </div>
                            <button @click="show = false" class="text-yellow-600 dark:text-yellow-400 hover:text-yellow-800 dark:hover:text-yellow-200">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            </button>
                        </div>
                    </div>
                @endif
            </div>
            
            <!-- 頁面內容插槽 -->
            {{ $slot }}
            
        </main>
        
    </div>
    
</div>

@push('scripts')
<script>
    // 處理鍵盤快捷鍵
    document.addEventListener('keydown', function(e) {
        // Ctrl/Cmd + B 切換側邊欄
        if ((e.ctrlKey || e.metaKey) && e.key === 'b') {
            e.preventDefault();
            @this.call('toggleSidebar');
        }
        
        // ESC 關閉側邊欄（僅在行動裝置模式）
        if (e.key === 'Escape' && window.innerWidth < 1024) {
            @this.call('closeSidebar');
        }
    });
</script>
@endpush