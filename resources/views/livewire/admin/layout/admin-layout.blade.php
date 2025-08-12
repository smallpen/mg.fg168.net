<!-- 主佈局容器 - 使用 CSS Grid 和 Flexbox 混合佈局 -->
<div class="admin-layout-container {{ $this->getLayoutClasses()['container'] }} {{ app(\App\Services\AccessibilityService::class)->getAccessibilityClasses() }}" 
     x-data="adminLayout" 
     x-init="init()"
     data-theme="{{ $currentTheme }}"
     :class="{ 
         'sidebar-collapsed': $wire.sidebarCollapsed,
         'sidebar-mobile-open': $wire.sidebarMobile,
         'is-mobile': isMobile,
         'is-tablet': isTablet,
         'is-desktop': isDesktop,
         'touch-gestures-enabled': $wire.touchGesturesEnabled,
         'layout-transitioning': isTransitioning,
         'animations-enabled': !$wire.reducedMotion
     }"
     @swipe-gesture="handleSwipeGesture($event.detail)"
     @touch-feedback="handleTouchFeedback($event.detail)"
     @keydown="handleAccessibilityKeydown($event)"
     @resize.window="handleResize()"
     role="application"
     aria-label="管理後台應用程式"
     style="--sidebar-width: {{ $this->getSidebarWidth() }}px; --topbar-height: {{ $this->getTopbarHeight() }}px;"
     wire:loading.class="layout-loading">
    
    <!-- 佈局網格容器 -->
    <div class="layout-grid-container" 
         :class="{
             'grid-desktop': isDesktop,
             'grid-tablet': isTablet,
             'grid-mobile': isMobile,
             'sidebar-collapsed': $wire.sidebarCollapsed
         }">
    
        <!-- 遮罩層（僅在行動裝置模式下顯示） -->
        <div class="layout-overlay {{ $this->getLayoutClasses()['overlay'] }}" 
             wire:click="toggleMobileSidebar"
             x-show="$wire.sidebarMobile && isMobile"
             x-transition:enter="overlay-enter"
             x-transition:enter-start="overlay-enter-start"
             x-transition:enter-end="overlay-enter-end"
             x-transition:leave="overlay-leave"
             x-transition:leave-start="overlay-leave-start"
             x-transition:leave-end="overlay-leave-end"
             @click="$wire.call('toggleMobileSidebar')"
             role="button"
             aria-label="關閉側邊選單"
             tabindex="0"
             @keydown.enter="$wire.call('toggleMobileSidebar')"
             @keydown.space.prevent="$wire.call('toggleMobileSidebar')">
        </div>
    
    <!-- 觸控滑動指示器 -->
    <div class="touch-swipe-indicator" 
         x-show="showSwipeIndicator"
         x-transition:enter="transition-transform ease-out duration-150"
         x-transition:enter-start="transform -translate-x-full"
         x-transition:enter-end="transform translate-x-0"
         x-transition:leave="transition-transform ease-in duration-150"
         x-transition:leave-start="transform translate-x-0"
         x-transition:leave-end="transform -translate-x-full">
    </div>
    
        <!-- 側邊導航選單區域 -->
        <aside id="navigation" 
               class="layout-sidebar {{ $this->getLayoutClasses()['sidebar'] }}"
               role="navigation"
               aria-label="主要導航選單"
               x-ref="sidebar"
               :class="{
                   'sidebar-collapsed': $wire.sidebarCollapsed,
                   'sidebar-expanded': !$wire.sidebarCollapsed,
                   'sidebar-mobile-open': $wire.sidebarMobile && isMobile,
                   'sidebar-transitioning': isTransitioning
               }"
               x-transition:enter="sidebar-enter"
               x-transition:enter-start="sidebar-enter-start"
               x-transition:enter-end="sidebar-enter-end"
               x-transition:leave="sidebar-leave"
               x-transition:leave-start="sidebar-leave-start"
               x-transition:leave-end="sidebar-leave-end"
               @touchstart="handleSidebarTouchStart($event)"
               @touchmove="handleSidebarTouchMove($event)"
               @touchend="handleSidebarTouchEnd($event)"
               wire:loading.class="sidebar-loading">
            
            <!-- 平板版側邊欄切換按鈕 -->
            <div class="sidebar-toggle-button tablet-sidebar-toggle show-tablet"
                 x-show="isTablet"
                 @click="$wire.call('toggleSidebar')"
                 :class="{ 'rotate-180': !$wire.sidebarCollapsed }"
                 role="button"
                 aria-label="切換側邊選單"
                 tabindex="0"
                 @keydown.enter="$wire.call('toggleSidebar')"
                 @keydown.space.prevent="$wire.call('toggleSidebar')">
                <svg class="w-4 h-4 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                </svg>
            </div>
            
            <!-- 側邊欄內容 -->
            <div class="sidebar-content-wrapper">
                <livewire:admin.layout.sidebar />
            </div>
            
            <!-- 側邊欄調整大小控制器（桌面版） -->
            <div class="sidebar-resize-handle show-desktop"
                 x-show="isDesktop && !$wire.sidebarCollapsed"
                 @mousedown="startSidebarResize($event)"
                 role="separator"
                 aria-label="調整側邊選單寬度"
                 tabindex="0">
                <div class="resize-handle-indicator"></div>
            </div>
        </aside>
    
        <!-- 主內容區域 -->
        <div class="layout-main {{ $this->getLayoutClasses()['main'] }}"
             :class="{
                 'main-with-sidebar': !$wire.sidebarCollapsed || (isTablet && !$wire.sidebarCollapsed),
                 'main-without-sidebar': $wire.sidebarCollapsed && !isTablet,
                 'main-mobile': isMobile,
                 'main-transitioning': isTransitioning
             }">
            
            <!-- 頂部導航列 -->
            <header id="banner" 
                    class="layout-topbar relative z-30"
                    role="banner"
                    aria-label="頁面標題區域"
                    :class="{
                        'topbar-with-sidebar': !$wire.sidebarCollapsed || (isTablet && !$wire.sidebarCollapsed),
                        'topbar-without-sidebar': $wire.sidebarCollapsed && !isTablet,
                        'topbar-mobile': isMobile
                    }"
                    wire:loading.class="topbar-loading">
                <livewire:admin.layout.top-nav-bar />
            </header>
            
            <!-- 頁面載入進度指示器 -->
            <div class="page-loading-container">
                <livewire:admin.layout.page-loading-indicator />
            </div>
            
            <!-- 主要內容容器 -->
            <main id="main-content" 
                  class="layout-content flex-1 responsive-spacing touch-gesture-area"
                  role="main"
                  aria-label="主要內容區域"
                  tabindex="-1"
                  :class="{
                      'content-with-sidebar': !$wire.sidebarCollapsed || (isTablet && !$wire.sidebarCollapsed),
                      'content-without-sidebar': $wire.sidebarCollapsed && !isTablet,
                      'content-mobile': isMobile,
                      'content-loading': $wire.isLoading
                  }"
                  @touchstart="handleMainTouchStart($event)"
                  @touchmove="handleMainTouchMove($event)"
                  @touchend="handleMainTouchEnd($event)"
                  wire:loading.class="content-loading">
            
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
            @if(isset($slot))
                {{ $slot }}
            @else
                <div class="text-center py-8">
                    <p class="text-gray-500 dark:text-gray-400">頁面內容將在此處顯示</p>
                </div>
            @endif
            
            </main>
            
        </div>
        
    </div> <!-- 結束 layout-grid-container -->
    
    <!-- 無障礙功能元件 -->
    <livewire:admin.layout.skip-links />
    <livewire:admin.layout.focus-manager />
    <livewire:admin.layout.screen-reader-support />
    <livewire:admin.layout.accessibility-settings />
    
    <!-- 全域載入狀態管理元件 -->
    <livewire:admin.layout.loading-overlay />
    <livewire:admin.layout.operation-feedback />
    <livewire:admin.layout.network-status />
    <livewire:admin.layout.skeleton-loader />
    
    <!-- 效能監控元件 -->
    @if($this->performanceSettings['performance_monitoring'])
        <livewire:admin.performance.performance-monitor />
    @endif
    
</div>

@push('scripts')
<script>
    // Alpine.js 佈局管理元件
    document.addEventListener('alpine:init', () => {
        Alpine.data('adminLayout', () => ({
            isMobile: false,
            isTablet: false,
            isDesktop: true,
            showSwipeIndicator: false,
            
            // 觸控手勢相關
            touchStartX: 0,
            touchStartY: 0,
            touchCurrentX: 0,
            touchCurrentY: 0,
            isTouching: false,
            swipeThreshold: 50,
            
            init() {
                // 初始化響應式檢測
                this.checkViewport();
                
                // 監聽視窗大小變化
                window.addEventListener('resize', this.handleResize.bind(this));
                
                // 監聽鍵盤快捷鍵
                document.addEventListener('keydown', this.handleKeydown.bind(this));
                
                // 初始化觸控手勢
                this.initTouchGestures();
                
                // 初始化佈局狀態
                this.initLayoutState();
                
                // 監聽主題變更
                this.$watch('$wire.currentTheme', (theme) => {
                    document.documentElement.setAttribute('data-theme', theme);
                    localStorage.setItem('theme', theme);
                    this.handleThemeChange(theme);
                });
                
                // 監聽響應式佈局變更
                this.$watch('isMobile', (isMobile) => {
                    document.body.classList.toggle('layout-mobile', isMobile);
                    this.handleViewportChange();
                });
                
                this.$watch('isTablet', (isTablet) => {
                    document.body.classList.toggle('layout-tablet', isTablet);
                    this.handleViewportChange();
                });
                
                this.$watch('isDesktop', (isDesktop) => {
                    document.body.classList.toggle('layout-desktop', isDesktop);
                    this.handleViewportChange();
                });
                
                // 監聽側邊欄狀態變更
                this.$watch('$wire.sidebarCollapsed', (collapsed) => {
                    this.handleSidebarToggle(collapsed);
                });
                
                this.$watch('$wire.sidebarMobile', (open) => {
                    this.handleMobileSidebarToggle(open);
                });
            },
            
            checkViewport() {
                const width = window.innerWidth;
                const newIsMobile = width < 768;
                const newIsTablet = width >= 768 && width < 1024;
                const newIsDesktop = width >= 1024;
                
                if (this.isMobile !== newIsMobile || this.isTablet !== newIsTablet || this.isDesktop !== newIsDesktop) {
                    this.isMobile = newIsMobile;
                    this.isTablet = newIsTablet;
                    this.isDesktop = newIsDesktop;
                    
                    // 通知 Livewire 元件
                    this.$wire.call('handleViewportChange', {
                        isMobile: this.isMobile,
                        isTablet: this.isTablet,
                        isDesktop: this.isDesktop,
                        width: width
                    });
                    
                    // 觸發自訂事件
                    this.$dispatch('viewport-changed', {
                        isMobile: this.isMobile,
                        isTablet: this.isTablet,
                        isDesktop: this.isDesktop,
                        width: width
                    });
                }
            },
            
            handleResize() {
                // 使用防抖來避免過度觸發
                clearTimeout(this.resizeTimeout);
                this.resizeTimeout = setTimeout(() => {
                    this.checkViewport();
                }, 150);
            },
            
            handleKeydown(e) {
                // Ctrl/Cmd + B 切換側邊欄
                if ((e.ctrlKey || e.metaKey) && e.key === 'b') {
                    e.preventDefault();
                    if (this.isMobile) {
                        this.$wire.call('toggleMobileSidebar');
                    } else {
                        this.$wire.call('toggleSidebar');
                    }
                }
                
                // ESC 關閉行動版側邊欄
                if (e.key === 'Escape' && this.isMobile) {
                    this.$wire.set('sidebarMobile', false);
                }
                
                // Alt + T 切換主題
                if (e.altKey && e.key === 't') {
                    e.preventDefault();
                    const currentTheme = this.$wire.get('currentTheme');
                    const newTheme = currentTheme === 'light' ? 'dark' : 'light';
                    this.$wire.call('setTheme', newTheme);
                }
            },
            
            // 處理無障礙鍵盤事件
            handleAccessibilityKeydown(e) {
                // 委託給焦點管理器處理
                if (window.focusManager) {
                    window.focusManager.handleGlobalKeydown(e);
                }
            },
            
            // 初始化觸控手勢
            initTouchGestures() {
                // 只在觸控裝置上啟用
                if (!('ontouchstart' in window)) {
                    return;
                }
                
                // 綁定觸控事件到主容器
                const container = this.$el;
                
                container.addEventListener('touchstart', this.handleTouchStart.bind(this), { passive: false });
                container.addEventListener('touchmove', this.handleTouchMove.bind(this), { passive: false });
                container.addEventListener('touchend', this.handleTouchEnd.bind(this), { passive: false });
            },
            
            // 處理觸控開始
            handleTouchStart(e) {
                if (!this.$wire.get('touchGesturesEnabled')) {
                    return;
                }
                
                const touch = e.touches[0];
                this.touchStartX = touch.clientX;
                this.touchStartY = touch.clientY;
                this.touchCurrentX = touch.clientX;
                this.touchCurrentY = touch.clientY;
                this.isTouching = true;
            },
            
            // 處理觸控移動
            handleTouchMove(e) {
                if (!this.isTouching || !this.$wire.get('touchGesturesEnabled')) {
                    return;
                }
                
                const touch = e.touches[0];
                this.touchCurrentX = touch.clientX;
                this.touchCurrentY = touch.clientY;
                
                const deltaX = this.touchCurrentX - this.touchStartX;
                const deltaY = Math.abs(this.touchCurrentY - this.touchStartY);
                
                // 顯示滑動指示器
                if (this.isMobile && Math.abs(deltaX) > 20 && deltaY < 100) {
                    this.showSwipeIndicator = true;
                    
                    // 防止頁面滾動
                    if (Math.abs(deltaX) > Math.abs(deltaY)) {
                        e.preventDefault();
                    }
                }
            },
            
            // 處理觸控結束
            handleTouchEnd(e) {
                if (!this.isTouching || !this.$wire.get('touchGesturesEnabled')) {
                    return;
                }
                
                const deltaX = this.touchCurrentX - this.touchStartX;
                const deltaY = Math.abs(this.touchCurrentY - this.touchStartY);
                const distance = Math.abs(deltaX);
                
                this.isTouching = false;
                this.showSwipeIndicator = false;
                
                // 檢查是否為有效的水平滑動
                if (distance > this.swipeThreshold && deltaY < 100) {
                    const direction = deltaX > 0 ? 'right' : 'left';
                    const velocity = distance / (Date.now() - this.touchStartTime || 1);
                    
                    // 觸發滑動手勢事件
                    this.$wire.call('handleSwipeGesture', {
                        direction: direction,
                        distance: distance,
                        velocity: velocity,
                        startX: this.touchStartX,
                        endX: this.touchCurrentX
                    });
                }
            },
            
            // 處理側邊欄觸控開始
            handleSidebarTouchStart(e) {
                // 記錄觸控開始時間
                this.touchStartTime = Date.now();
            },
            
            // 處理側邊欄觸控移動
            handleSidebarTouchMove(e) {
                // 在側邊欄內滑動時不觸發全域手勢
                e.stopPropagation();
            },
            
            // 處理側邊欄觸控結束
            handleSidebarTouchEnd(e) {
                // 在側邊欄內結束觸控時不觸發全域手勢
                e.stopPropagation();
            },
            
            // 處理主內容區域觸控開始
            handleMainTouchStart(e) {
                this.touchStartTime = Date.now();
            },
            
            // 處理主內容區域觸控移動
            handleMainTouchMove(e) {
                // 允許主內容區域的滑動手勢
            },
            
            // 處理主內容區域觸控結束
            handleMainTouchEnd(e) {
                // 允許主內容區域的滑動手勢
            },
            
            // 處理滑動手勢事件
            handleSwipeGesture(detail) {
                // 由 Livewire 元件處理
            },
            
            // 處理觸控回饋事件
            handleTouchFeedback(detail) {
                // 添加觸控回饋效果
                const element = detail.target;
                if (element && element.classList.contains('touch-feedback')) {
                    this.addRippleEffect(element, detail.x, detail.y);
                }
            },
            
            // 初始化佈局狀態
            initLayoutState() {
                // 設定初始 CSS 變數
                this.updateLayoutVariables();
                
                // 初始化過渡狀態
                this.isTransitioning = false;
                
                // 設定佈局類別
                this.updateLayoutClasses();
            },
            
            // 更新佈局 CSS 變數
            updateLayoutVariables() {
                const root = document.documentElement;
                
                if (this.isMobile) {
                    root.style.setProperty('--current-sidebar-width', '280px');
                } else if (this.isTablet) {
                    root.style.setProperty('--current-sidebar-width', this.$wire.get('sidebarCollapsed') ? '64px' : '240px');
                } else {
                    root.style.setProperty('--current-sidebar-width', this.$wire.get('sidebarCollapsed') ? '64px' : '280px');
                }
                
                root.style.setProperty('--current-topbar-height', '64px');
            },
            
            // 更新佈局類別
            updateLayoutClasses() {
                const container = this.$el;
                
                // 清除舊類別
                container.classList.remove('layout-mobile', 'layout-tablet', 'layout-desktop');
                
                // 添加新類別
                if (this.isMobile) {
                    container.classList.add('layout-mobile');
                } else if (this.isTablet) {
                    container.classList.add('layout-tablet');
                } else {
                    container.classList.add('layout-desktop');
                }
            },
            
            // 處理視窗變更
            handleViewportChange() {
                this.updateLayoutVariables();
                this.updateLayoutClasses();
                
                // 觸發自訂事件
                this.$dispatch('layout-viewport-changed', {
                    isMobile: this.isMobile,
                    isTablet: this.isTablet,
                    isDesktop: this.isDesktop,
                    width: window.innerWidth
                });
            },
            
            // 處理主題變更
            handleThemeChange(theme) {
                // 添加過渡效果
                document.body.classList.add('theme-transitioning');
                
                setTimeout(() => {
                    document.body.classList.remove('theme-transitioning');
                }, 300);
                
                // 觸發自訂事件
                this.$dispatch('layout-theme-changed', { theme });
            },
            
            // 處理側邊欄切換
            handleSidebarToggle(collapsed) {
                this.isTransitioning = true;
                this.updateLayoutVariables();
                
                setTimeout(() => {
                    this.isTransitioning = false;
                }, 300);
                
                // 觸發自訂事件
                this.$dispatch('layout-sidebar-toggled', { collapsed });
            },
            
            // 處理手機版側邊欄切換
            handleMobileSidebarToggle(open) {
                if (this.isMobile) {
                    document.body.style.overflow = open ? 'hidden' : '';
                }
                
                // 觸發自訂事件
                this.$dispatch('layout-mobile-sidebar-toggled', { open });
            },
            
            // 開始側邊欄調整大小
            startSidebarResize(e) {
                if (!this.isDesktop) return;
                
                e.preventDefault();
                
                const startX = e.clientX;
                const startWidth = this.$refs.sidebar.offsetWidth;
                const minWidth = 200;
                const maxWidth = 400;
                
                const handleMouseMove = (e) => {
                    const deltaX = e.clientX - startX;
                    const newWidth = Math.min(Math.max(startWidth + deltaX, minWidth), maxWidth);
                    
                    this.$refs.sidebar.style.width = newWidth + 'px';
                    document.documentElement.style.setProperty('--current-sidebar-width', newWidth + 'px');
                };
                
                const handleMouseUp = () => {
                    document.removeEventListener('mousemove', handleMouseMove);
                    document.removeEventListener('mouseup', handleMouseUp);
                    document.body.style.cursor = '';
                    document.body.style.userSelect = '';
                    
                    // 儲存新寬度
                    const newWidth = this.$refs.sidebar.offsetWidth;
                    this.$wire.call('updateSidebarWidth', newWidth);
                };
                
                document.addEventListener('mousemove', handleMouseMove);
                document.addEventListener('mouseup', handleMouseUp);
                document.body.style.cursor = 'col-resize';
                document.body.style.userSelect = 'none';
            },
            
            // 添加漣漪效果
            addRippleEffect(element, x, y) {
                const rect = element.getBoundingClientRect();
                const size = Math.max(rect.width, rect.height);
                const rippleX = x - rect.left - size / 2;
                const rippleY = y - rect.top - size / 2;
                
                const ripple = document.createElement('div');
                ripple.style.cssText = `
                    position: absolute;
                    border-radius: 50%;
                    background: rgba(59, 130, 246, 0.3);
                    transform: scale(0);
                    animation: ripple 0.6s linear;
                    left: ${rippleX}px;
                    top: ${rippleY}px;
                    width: ${size}px;
                    height: ${size}px;
                    pointer-events: none;
                    z-index: 1000;
                `;
                
                element.appendChild(ripple);
                
                setTimeout(() => {
                    if (ripple.parentNode) {
                        ripple.parentNode.removeChild(ripple);
                    }
                }, 600);
            }
        }));
    });
    
    // 頁面載入時應用儲存的主題
    (function() {
        const savedTheme = localStorage.getItem('theme') || 'light';
        document.documentElement.setAttribute('data-theme', savedTheme);
    })();
</script>
@endpush

@push('styles')
<style>
    /* ===== 主佈局容器樣式 ===== */
    .admin-layout-container {
        display: flex;
        flex-direction: column;
        min-height: 100vh;
        background-color: var(--bg-secondary);
        transition: all var(--transition-normal) var(--ease-in-out);
        position: relative;
        overflow: hidden;
    }
    
    /* CSS Grid 佈局容器 */
    .layout-grid-container {
        display: grid;
        min-height: 100vh;
        transition: grid-template-columns var(--transition-normal) var(--ease-in-out);
        position: relative;
    }
    
    /* 桌面版網格佈局 (≥1024px) */
    @media (min-width: 1024px) {
        .layout-grid-container.grid-desktop {
            grid-template-columns: var(--sidebar-width-desktop) 1fr;
        }
        
        .layout-grid-container.grid-desktop.sidebar-collapsed {
            grid-template-columns: var(--sidebar-width-collapsed) 1fr;
        }
    }
    
    /* 平板版網格佈局 (768px-1023px) */
    @media (min-width: 768px) and (max-width: 1023px) {
        .layout-grid-container.grid-tablet {
            grid-template-columns: var(--sidebar-width-collapsed) 1fr;
        }
        
        .layout-grid-container.grid-tablet:not(.sidebar-collapsed) {
            grid-template-columns: var(--sidebar-width-tablet) 1fr;
        }
    }
    
    /* 手機版網格佈局 (<768px) */
    @media (max-width: 767px) {
        .layout-grid-container.grid-mobile {
            grid-template-columns: 1fr;
            grid-template-rows: auto 1fr;
        }
    }
    
    /* ===== 側邊欄樣式增強 ===== */
    .layout-sidebar {
        position: relative;
        background-color: var(--bg-primary);
        border-right: 1px solid var(--border-primary);
        box-shadow: var(--shadow-lg);
        transition: all var(--transition-normal) var(--ease-in-out);
        z-index: 40;
        display: flex;
        flex-direction: column;
        overflow: hidden;
    }
    
    /* 桌面版側邊欄 */
    @media (min-width: 1024px) {
        .layout-sidebar {
            width: var(--sidebar-width-desktop);
        }
        
        .layout-sidebar.sidebar-collapsed {
            width: var(--sidebar-width-collapsed);
        }
    }
    
    /* 平板版側邊欄 */
    @media (min-width: 768px) and (max-width: 1023px) {
        .layout-sidebar {
            width: var(--sidebar-width-collapsed);
        }
        
        .layout-sidebar:not(.sidebar-collapsed) {
            width: var(--sidebar-width-tablet);
        }
    }
    
    /* 手機版側邊欄 */
    @media (max-width: 767px) {
        .layout-sidebar {
            position: fixed;
            top: 0;
            left: 0;
            bottom: 0;
            width: var(--sidebar-width-mobile);
            transform: translateX(-100%);
            z-index: 50;
        }
        
        .layout-sidebar.sidebar-mobile-open {
            transform: translateX(0);
        }
    }
    
    /* 側邊欄內容包裝器 */
    .sidebar-content-wrapper {
        flex: 1;
        overflow-y: auto;
        overflow-x: hidden;
        scrollbar-width: thin;
        scrollbar-color: var(--border-secondary) transparent;
    }
    
    .sidebar-content-wrapper::-webkit-scrollbar {
        width: 6px;
    }
    
    .sidebar-content-wrapper::-webkit-scrollbar-track {
        background: transparent;
    }
    
    .sidebar-content-wrapper::-webkit-scrollbar-thumb {
        background-color: var(--border-secondary);
        border-radius: 3px;
    }
    
    /* 側邊欄切換按鈕 */
    .sidebar-toggle-button {
        position: absolute;
        top: 50%;
        right: -12px;
        transform: translateY(-50%);
        width: 24px;
        height: 48px;
        background-color: var(--bg-primary);
        border: 1px solid var(--border-primary);
        border-left: none;
        border-radius: 0 0.5rem 0.5rem 0;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: all var(--transition-fast) var(--ease-in-out);
        z-index: 1;
    }
    
    .sidebar-toggle-button:hover {
        background-color: var(--bg-secondary);
        transform: translateY(-50%) scale(1.05);
    }
    
    /* 側邊欄調整大小控制器 */
    .sidebar-resize-handle {
        position: absolute;
        top: 0;
        right: 0;
        bottom: 0;
        width: 4px;
        cursor: col-resize;
        background: transparent;
        transition: background-color var(--transition-fast) var(--ease-in-out);
    }
    
    .sidebar-resize-handle:hover {
        background-color: var(--color-primary);
    }
    
    .resize-handle-indicator {
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        width: 2px;
        height: 20px;
        background-color: var(--border-secondary);
        border-radius: 1px;
        opacity: 0;
        transition: opacity var(--transition-fast) var(--ease-in-out);
    }
    
    .sidebar-resize-handle:hover .resize-handle-indicator {
        opacity: 1;
    }
    
    /* ===== 主內容區域樣式 ===== */
    .layout-main {
        display: flex;
        flex-direction: column;
        min-height: 100vh;
        background-color: var(--bg-secondary);
        transition: all var(--transition-normal) var(--ease-in-out);
        position: relative;
    }
    
    /* 頂部導航列 */
    .layout-topbar {
        height: var(--topbar-height);
        background-color: var(--bg-primary);
        border-bottom: 1px solid var(--border-primary);
        box-shadow: var(--shadow-sm);
        position: sticky;
        top: 0;
        z-index: 30;
        transition: all var(--transition-normal) var(--ease-in-out);
    }
    
    /* 主要內容區域 */
    .layout-content {
        flex: 1;
        padding: 1.5rem;
        overflow-y: auto;
        transition: all var(--transition-normal) var(--ease-in-out);
        position: relative;
    }
    
    @media (max-width: 767px) {
        .layout-content {
            padding: 1rem;
        }
    }
    
    /* ===== 遮罩層樣式 ===== */
    .layout-overlay {
        position: fixed;
        inset: 0;
        background-color: rgba(0, 0, 0, 0.5);
        z-index: 45;
        backdrop-filter: blur(2px);
        transition: all var(--transition-normal) var(--ease-in-out);
    }
    
    /* ===== 載入狀態樣式 ===== */
    .layout-loading {
        position: relative;
    }
    
    .layout-loading::after {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 2px;
        background: linear-gradient(90deg, transparent, var(--color-primary), transparent);
        animation: loading-bar 2s ease-in-out infinite;
        z-index: 1000;
    }
    
    @keyframes loading-bar {
        0% { transform: translateX(-100%); }
        50% { transform: translateX(0); }
        100% { transform: translateX(100%); }
    }
    
    .sidebar-loading {
        opacity: 0.7;
        pointer-events: none;
    }
    
    .topbar-loading {
        opacity: 0.9;
    }
    
    .content-loading {
        opacity: 0.8;
        pointer-events: none;
    }
    
    /* ===== 動畫和過渡效果 ===== */
    .layout-transition {
        transition: all var(--transition-normal) var(--ease-in-out);
    }
    
    /* 響應式佈局類別 */
    .layout-mobile .sidebar {
        transform: translateX(-100%);
    }
    
    .layout-mobile.sidebar-mobile-open .sidebar {
        transform: translateX(0);
    }
    
    .layout-tablet .sidebar {
        width: 4rem;
    }
    
    .layout-tablet:not(.sidebar-collapsed) .sidebar {
        width: 16rem;
    }
    
    .layout-desktop .sidebar {
        width: 4rem;
    }
    
    .layout-desktop:not(.sidebar-collapsed) .sidebar {
        width: 18rem;
    }
    
    /* 主題過渡動畫 */
    * {
        transition: background-color 0.3s ease, 
                    color 0.3s ease, 
                    border-color 0.3s ease,
                    box-shadow 0.3s ease;
    }
    
    /* 載入動畫 */
    .loading-skeleton {
        background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
        background-size: 200% 100%;
        animation: loading 1.5s infinite;
    }
    
    @keyframes loading {
        0% { background-position: 200% 0; }
        100% { background-position: -200% 0; }
    }
    
    /* 暗色主題下的載入動畫 */
    [data-theme="dark"] .loading-skeleton {
        background: linear-gradient(90deg, #374151 25%, #4B5563 50%, #374151 75%);
        background-size: 200% 100%;
    }
    
    /* 漣漪效果動畫 */
    @keyframes ripple {
        to {
            transform: scale(4);
            opacity: 0;
        }
    }
    
    /* 觸控回饋效果 */
    .touch-feedback {
        position: relative;
        overflow: hidden;
    }
    
    /* 滑動指示器動畫 */
    @keyframes slideIndicator {
        0% {
            opacity: 0;
            transform: translateX(-100%);
        }
        50% {
            opacity: 1;
        }
        100% {
            opacity: 0;
            transform: translateX(100%);
        }
    }
    
    .touch-swipe-indicator {
        animation: slideIndicator 2s ease-in-out infinite;
    }
    
    /* 無障礙功能樣式 */
    
    /* 高對比模式 */
    .high-contrast {
        --color-primary: #0066CC;
        --color-secondary: #333333;
        --bg-primary: #FFFFFF;
        --bg-secondary: #F5F5F5;
        --text-primary: #000000;
        --text-secondary: #333333;
        --border-primary: #000000;
        filter: contrast(150%);
    }
    
    [data-theme="dark"].high-contrast {
        --color-primary: #66B3FF;
        --bg-primary: #000000;
        --bg-secondary: #1A1A1A;
        --text-primary: #FFFFFF;
        --text-secondary: #CCCCCC;
        --border-primary: #FFFFFF;
        filter: contrast(150%);
    }
    
    /* 大字體模式 */
    .large-text {
        font-size: 1.125em;
    }
    
    .large-text h1 { font-size: 2.5em; }
    .large-text h2 { font-size: 2em; }
    .large-text h3 { font-size: 1.75em; }
    .large-text h4 { font-size: 1.5em; }
    .large-text h5 { font-size: 1.25em; }
    .large-text h6 { font-size: 1.125em; }
    
    .large-text .text-xs { font-size: 0.875rem; }
    .large-text .text-sm { font-size: 1rem; }
    .large-text .text-base { font-size: 1.125rem; }
    .large-text .text-lg { font-size: 1.25rem; }
    .large-text .text-xl { font-size: 1.375rem; }
    
    /* 減少動畫模式 */
    .reduced-motion,
    .reduced-motion * {
        animation-duration: 0.01ms !important;
        animation-iteration-count: 1 !important;
        transition-duration: 0.01ms !important;
        scroll-behavior: auto !important;
    }
    
    /* 增強焦點指示器 */
    .enhanced-focus *:focus {
        outline: 3px solid #3B82F6 !important;
        outline-offset: 2px !important;
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.3) !important;
    }
    
    .enhanced-focus *:focus-visible {
        outline: 3px solid #3B82F6 !important;
        outline-offset: 2px !important;
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.3) !important;
    }
    
    /* 暗色主題下的焦點指示器 */
    [data-theme="dark"].enhanced-focus *:focus {
        outline-color: #60A5FA !important;
        box-shadow: 0 0 0 3px rgba(96, 165, 250, 0.3) !important;
    }
    
    /* 跳轉目標高亮 */
    .skip-target-highlight {
        outline: 3px solid #3B82F6 !important;
        outline-offset: 2px;
        animation: pulse 1s ease-in-out 2;
    }
    
    @keyframes pulse {
        0%, 100% { opacity: 1; }
        50% { opacity: 0.7; }
    }
    
    /* 螢幕閱讀器專用樣式 */
    .sr-only {
        position: absolute;
        width: 1px;
        height: 1px;
        padding: 0;
        margin: -1px;
        overflow: hidden;
        clip: rect(0, 0, 0, 0);
        white-space: nowrap;
        border: 0;
    }
    
    /* 當元素獲得焦點時顯示（用於跳轉連結等） */
    .sr-only:focus {
        position: static;
        width: auto;
        height: auto;
        padding: inherit;
        margin: inherit;
        overflow: visible;
        clip: auto;
        white-space: normal;
    }
    
    /* 鍵盤導航增強 */
    .keyboard-navigation-enabled {
        /* 確保所有互動元素都可以透過鍵盤存取 */
    }
    
    .keyboard-navigation-enabled button:not([tabindex="-1"]),
    .keyboard-navigation-enabled a:not([tabindex="-1"]),
    .keyboard-navigation-enabled input:not([tabindex="-1"]),
    .keyboard-navigation-enabled select:not([tabindex="-1"]),
    .keyboard-navigation-enabled textarea:not([tabindex="-1"]),
    .keyboard-navigation-enabled [tabindex]:not([tabindex="-1"]) {
        position: relative;
    }
    
    /* 確保焦點指示器在所有元素上都可見 */
    .keyboard-navigation-enabled *:focus {
        z-index: 1000;
        position: relative;
    }
    
    /* 無障礙按鈕樣式 */
    .accessibility-button {
        position: fixed;
        top: 10px;
        right: 10px;
        z-index: 9999;
        background: #3B82F6;
        color: white;
        border: none;
        border-radius: 50%;
        width: 48px;
        height: 48px;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        transition: all 0.3s ease;
    }
    
    .accessibility-button:hover {
        background: #2563EB;
        transform: scale(1.1);
    }
    
    .accessibility-button:focus {
        outline: 3px solid #60A5FA;
        outline-offset: 2px;
    }
    
    /* 響應式無障礙調整 */
    @media (max-width: 768px) {
        .large-text {
            font-size: 1.25em;
        }
        
        .enhanced-focus *:focus {
            outline-width: 4px !important;
        }
        
        .accessibility-button {
            width: 56px;
            height: 56px;
        }
    }
    
    /* 列印樣式的無障礙調整 */
    @media print {
        .sr-only {
            position: static !important;
            width: auto !important;
            height: auto !important;
            clip: auto !important;
            overflow: visible !important;
        }
        
        .skip-links,
        .accessibility-settings,
        .focus-manager {
            display: none !important;
        }
    }
    
    /* ===== 佈局動畫增強 ===== */
    
    /* 遮罩層動畫 */
    .overlay-enter {
        transition: opacity var(--transition-normal) ease-linear;
    }
    
    .overlay-enter-start {
        opacity: 0;
    }
    
    .overlay-enter-end {
        opacity: 1;
    }
    
    .overlay-leave {
        transition: opacity var(--transition-normal) ease-linear;
    }
    
    .overlay-leave-start {
        opacity: 1;
    }
    
    .overlay-leave-end {
        opacity: 0;
    }
    
    /* 側邊欄動畫 */
    .sidebar-enter {
        transition: transform var(--transition-normal) var(--ease-out);
    }
    
    .sidebar-enter-start {
        transform: translateX(-100%);
    }
    
    .sidebar-enter-end {
        transform: translateX(0);
    }
    
    .sidebar-leave {
        transition: transform var(--transition-normal) var(--ease-in);
    }
    
    .sidebar-leave-start {
        transform: translateX(0);
    }
    
    .sidebar-leave-end {
        transform: translateX(-100%);
    }
    
    /* 佈局過渡狀態 */
    .layout-transitioning {
        pointer-events: none;
    }
    
    .layout-transitioning * {
        transition-duration: var(--transition-normal) !important;
    }
    
    .sidebar-transitioning {
        overflow: hidden;
    }
    
    .main-transitioning {
        overflow: hidden;
    }
    
    /* 佈局狀態類別 */
    .animations-enabled .layout-sidebar,
    .animations-enabled .layout-main,
    .animations-enabled .layout-topbar,
    .animations-enabled .layout-content {
        transition: all var(--transition-normal) var(--ease-in-out);
    }
    
    .animations-enabled .sidebar-toggle-button {
        transition: all var(--transition-fast) var(--ease-in-out);
    }
    
    /* 響應式佈局狀態 */
    .is-mobile .layout-sidebar {
        position: fixed;
        z-index: 50;
    }
    
    .is-tablet .layout-sidebar {
        position: relative;
        z-index: 40;
    }
    
    .is-desktop .layout-sidebar {
        position: relative;
        z-index: 40;
    }
    
    /* 佈局過渡動畫 */
    .layout-transition-enter {
        opacity: 0;
        transform: translateY(-10px);
    }
    
    .layout-transition-enter-active {
        opacity: 1;
        transform: translateY(0);
        transition: opacity var(--transition-normal) var(--ease-out),
                    transform var(--transition-normal) var(--ease-out);
    }
    
    .layout-transition-leave {
        opacity: 1;
        transform: translateY(0);
    }
    
    .layout-transition-leave-active {
        opacity: 0;
        transform: translateY(-10px);
        transition: opacity var(--transition-fast) var(--ease-in),
                    transform var(--transition-fast) var(--ease-in);
    }
    
    /* 效能優化 */
    .layout-sidebar,
    .layout-main,
    .layout-overlay {
        will-change: transform;
        backface-visibility: hidden;
        perspective: 1000px;
    }
    
    /* 觸控優化 */
    .sidebar-toggle-button,
    .sidebar-resize-handle {
        -webkit-tap-highlight-color: transparent;
        touch-action: manipulation;
    }
    
    /* 減少動畫偏好支援 */
    @media (prefers-reduced-motion: reduce) {
        .admin-layout-container,
        .layout-grid-container,
        .layout-sidebar,
        .layout-main,
        .layout-topbar,
        .layout-content,
        .sidebar-toggle-button,
        .sidebar-resize-handle {
            transition: none !important;
            animation: none !important;
        }
    }
</style>
@endpush