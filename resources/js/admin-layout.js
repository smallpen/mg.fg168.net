/**
 * 管理後台佈局系統 JavaScript
 * 
 * 負責處理佈局的互動功能，包括：
 * - 響應式設計檢測
 * - 鍵盤快捷鍵處理
 * - 動畫和過渡效果
 * - 主題切換邏輯
 * - 觸控手勢支援
 */

class AdminLayoutManager {
    constructor() {
        this.breakpoints = {
            mobile: 768,
            tablet: 1024
        };
        
        this.currentViewport = {
            isMobile: false,
            isTablet: false,
            width: window.innerWidth
        };
        
        this.resizeTimeout = null;
        this.touchStartX = 0;
        this.touchStartY = 0;
        this.isSwipeGesture = false;
        
        this.init();
    }
    
    /**
     * 初始化佈局管理器
     */
    init() {
        this.checkViewport();
        this.bindEvents();
        this.initTheme();
        this.initKeyboardShortcuts();
        this.initTouchGestures();
        this.initAccessibility();
    }
    
    /**
     * 檢查當前視窗大小並更新狀態
     */
    checkViewport() {
        const width = window.innerWidth;
        const newIsMobile = width < this.breakpoints.mobile;
        const newIsTablet = width >= this.breakpoints.mobile && width < this.breakpoints.tablet;
        
        if (this.currentViewport.isMobile !== newIsMobile || 
            this.currentViewport.isTablet !== newIsTablet) {
            
            this.currentViewport = {
                isMobile: newIsMobile,
                isTablet: newIsTablet,
                width: width
            };
            
            this.onViewportChange();
        }
    }
    
    /**
     * 綁定事件監聽器
     */
    bindEvents() {
        // 視窗大小變更
        window.addEventListener('resize', this.handleResize.bind(this));
        
        // 點擊外部關閉選單
        document.addEventListener('click', this.handleOutsideClick.bind(this));
        
        // 主題變更監聽
        if (window.matchMedia) {
            window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', 
                this.handleSystemThemeChange.bind(this));
        }
        
        // 頁面可見性變更
        document.addEventListener('visibilitychange', this.handleVisibilityChange.bind(this));
    }
    
    /**
     * 處理視窗大小變更
     */
    handleResize() {
        clearTimeout(this.resizeTimeout);
        this.resizeTimeout = setTimeout(() => {
            this.checkViewport();
        }, 150);
    }
    
    /**
     * 處理視窗大小變更回調
     */
    onViewportChange() {
        // 通知 Livewire 元件
        if (window.Livewire) {
            window.Livewire.dispatch('viewport-changed', this.currentViewport);
        }
        
        // 更新 CSS 類別
        this.updateLayoutClasses();
        
        // 觸發自定義事件
        window.dispatchEvent(new CustomEvent('admin:viewport-changed', {
            detail: this.currentViewport
        }));
    }
    
    /**
     * 更新佈局 CSS 類別
     */
    updateLayoutClasses() {
        const body = document.body;
        
        // 移除舊的類別
        body.classList.remove('layout-mobile', 'layout-tablet', 'layout-desktop');
        
        // 新增新的類別
        if (this.currentViewport.isMobile) {
            body.classList.add('layout-mobile');
        } else if (this.currentViewport.isTablet) {
            body.classList.add('layout-tablet');
        } else {
            body.classList.add('layout-desktop');
        }
    }
    
    /**
     * 處理點擊外部事件
     */
    handleOutsideClick(event) {
        // 關閉開啟的下拉選單
        const dropdowns = document.querySelectorAll('[data-dropdown-open="true"]');
        dropdowns.forEach(dropdown => {
            if (!dropdown.contains(event.target)) {
                dropdown.setAttribute('data-dropdown-open', 'false');
            }
        });
    }
    
    /**
     * 初始化主題系統
     */
    initTheme() {
        const savedTheme = localStorage.getItem('theme');
        const systemTheme = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
        const theme = savedTheme || systemTheme;
        
        this.applyTheme(theme);
    }
    
    /**
     * 應用主題
     */
    applyTheme(theme) {
        document.documentElement.setAttribute('data-theme', theme);
        
        if (theme === 'dark') {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }
        
        localStorage.setItem('theme', theme);
        
        // 觸發主題變更事件
        window.dispatchEvent(new CustomEvent('admin:theme-changed', {
            detail: { theme }
        }));
    }
    
    /**
     * 處理系統主題變更
     */
    handleSystemThemeChange(e) {
        const savedTheme = localStorage.getItem('theme');
        
        // 只有在使用者沒有手動設定主題時才自動切換
        if (!savedTheme || savedTheme === 'auto') {
            const systemTheme = e.matches ? 'dark' : 'light';
            this.applyTheme(systemTheme);
            
            if (window.Livewire) {
                window.Livewire.dispatch('system-theme-changed', { theme: systemTheme });
            }
        }
    }
    
    /**
     * 初始化鍵盤快捷鍵
     */
    initKeyboardShortcuts() {
        document.addEventListener('keydown', this.handleKeydown.bind(this));
    }
    
    /**
     * 處理鍵盤快捷鍵
     */
    handleKeydown(e) {
        // Ctrl/Cmd + B: 切換側邊欄
        if ((e.ctrlKey || e.metaKey) && e.key === 'b') {
            e.preventDefault();
            this.toggleSidebar();
        }
        
        // Alt + T: 切換主題
        if (e.altKey && e.key === 't') {
            e.preventDefault();
            this.toggleTheme();
        }
        
        // ESC: 關閉選單和對話框
        if (e.key === 'Escape') {
            this.closeAllMenus();
            this.closeMobileSidebar();
        }
        
        // Ctrl/Cmd + K: 開啟搜尋
        if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
            e.preventDefault();
            this.openGlobalSearch();
        }
        
        // F1: 開啟說明
        if (e.key === 'F1') {
            e.preventDefault();
            this.openHelp();
        }
    }
    
    /**
     * 切換側邊欄
     */
    toggleSidebar() {
        if (window.Livewire) {
            if (this.currentViewport.isMobile) {
                window.Livewire.dispatch('toggle-mobile-sidebar');
            } else {
                window.Livewire.dispatch('toggle-sidebar');
            }
        }
    }
    
    /**
     * 切換主題
     */
    toggleTheme() {
        const currentTheme = document.documentElement.getAttribute('data-theme') || 'light';
        const newTheme = currentTheme === 'light' ? 'dark' : 'light';
        
        this.applyTheme(newTheme);
        
        if (window.Livewire) {
            window.Livewire.dispatch('theme-changed', { theme: newTheme });
        }
    }
    
    /**
     * 關閉所有選單
     */
    closeAllMenus() {
        const menus = document.querySelectorAll('[data-menu-open="true"]');
        menus.forEach(menu => {
            menu.setAttribute('data-menu-open', 'false');
        });
        
        if (window.Livewire) {
            window.Livewire.dispatch('close-all-menus');
        }
    }
    
    /**
     * 關閉行動版側邊欄
     */
    closeMobileSidebar() {
        if (this.currentViewport.isMobile && window.Livewire) {
            window.Livewire.dispatch('close-mobile-sidebar');
        }
    }
    
    /**
     * 開啟全域搜尋
     */
    openGlobalSearch() {
        const searchInput = document.querySelector('[data-global-search]');
        if (searchInput) {
            searchInput.focus();
        }
        
        if (window.Livewire) {
            window.Livewire.dispatch('open-global-search');
        }
    }
    
    /**
     * 開啟說明
     */
    openHelp() {
        if (window.Livewire) {
            window.Livewire.dispatch('open-help');
        }
    }
    
    /**
     * 初始化觸控手勢
     */
    initTouchGestures() {
        if (!('ontouchstart' in window)) {
            return; // 不支援觸控
        }
        
        document.addEventListener('touchstart', this.handleTouchStart.bind(this), { passive: true });
        document.addEventListener('touchmove', this.handleTouchMove.bind(this), { passive: false });
        document.addEventListener('touchend', this.handleTouchEnd.bind(this), { passive: true });
    }
    
    /**
     * 處理觸控開始
     */
    handleTouchStart(e) {
        this.touchStartX = e.touches[0].clientX;
        this.touchStartY = e.touches[0].clientY;
        this.isSwipeGesture = false;
    }
    
    /**
     * 處理觸控移動
     */
    handleTouchMove(e) {
        if (!this.currentViewport.isMobile) {
            return;
        }
        
        const touchX = e.touches[0].clientX;
        const touchY = e.touches[0].clientY;
        const deltaX = touchX - this.touchStartX;
        const deltaY = touchY - this.touchStartY;
        
        // 檢查是否為水平滑動手勢
        if (Math.abs(deltaX) > Math.abs(deltaY) && Math.abs(deltaX) > 30) {
            this.isSwipeGesture = true;
            
            // 從左邊緣向右滑動：開啟側邊欄
            if (this.touchStartX < 20 && deltaX > 50) {
                e.preventDefault();
                this.openMobileSidebar();
            }
            
            // 向左滑動：關閉側邊欄
            if (deltaX < -50) {
                e.preventDefault();
                this.closeMobileSidebar();
            }
        }
    }
    
    /**
     * 處理觸控結束
     */
    handleTouchEnd(e) {
        this.isSwipeGesture = false;
    }
    
    /**
     * 開啟行動版側邊欄
     */
    openMobileSidebar() {
        if (window.Livewire) {
            window.Livewire.dispatch('open-mobile-sidebar');
        }
    }
    
    /**
     * 初始化無障礙功能
     */
    initAccessibility() {
        // 跳轉到主內容的連結
        this.createSkipLink();
        
        // 焦點管理
        this.initFocusManagement();
        
        // ARIA 標籤更新
        this.updateAriaLabels();
    }
    
    /**
     * 建立跳轉連結
     */
    createSkipLink() {
        const skipLink = document.createElement('a');
        skipLink.href = '#main-content';
        skipLink.textContent = '跳轉到主要內容';
        skipLink.className = 'sr-only focus:not-sr-only focus:absolute focus:top-4 focus:left-4 focus:z-50 focus:px-4 focus:py-2 focus:bg-primary-600 focus:text-white focus:rounded-md';
        
        document.body.insertBefore(skipLink, document.body.firstChild);
    }
    
    /**
     * 初始化焦點管理
     */
    initFocusManagement() {
        // 監聽焦點變化
        document.addEventListener('focusin', this.handleFocusIn.bind(this));
        document.addEventListener('focusout', this.handleFocusOut.bind(this));
    }
    
    /**
     * 處理焦點進入
     */
    handleFocusIn(e) {
        // 為當前焦點元素新增視覺指示
        e.target.classList.add('focus-visible');
    }
    
    /**
     * 處理焦點離開
     */
    handleFocusOut(e) {
        // 移除焦點視覺指示
        e.target.classList.remove('focus-visible');
    }
    
    /**
     * 更新 ARIA 標籤
     */
    updateAriaLabels() {
        // 更新側邊欄的 ARIA 狀態
        const sidebar = document.querySelector('[data-sidebar]');
        if (sidebar) {
            sidebar.setAttribute('aria-label', '主要導航選單');
            sidebar.setAttribute('role', 'navigation');
        }
        
        // 更新主內容區域的 ARIA 標籤
        const mainContent = document.querySelector('[data-main-content]');
        if (mainContent) {
            mainContent.setAttribute('aria-label', '主要內容');
            mainContent.setAttribute('role', 'main');
            mainContent.id = 'main-content';
        }
    }
    
    /**
     * 處理頁面可見性變更
     */
    handleVisibilityChange() {
        if (document.hidden) {
            // 頁面隱藏時暫停動畫
            document.body.classList.add('animations-paused');
        } else {
            // 頁面顯示時恢復動畫
            document.body.classList.remove('animations-paused');
        }
    }
    
    /**
     * 取得當前視窗狀態
     */
    getCurrentViewport() {
        return { ...this.currentViewport };
    }
    
    /**
     * 銷毀管理器
     */
    destroy() {
        window.removeEventListener('resize', this.handleResize);
        document.removeEventListener('click', this.handleOutsideClick);
        document.removeEventListener('keydown', this.handleKeydown);
        document.removeEventListener('touchstart', this.handleTouchStart);
        document.removeEventListener('touchmove', this.handleTouchMove);
        document.removeEventListener('touchend', this.handleTouchEnd);
        document.removeEventListener('focusin', this.handleFocusIn);
        document.removeEventListener('focusout', this.handleFocusOut);
        document.removeEventListener('visibilitychange', this.handleVisibilityChange);
        
        if (this.resizeTimeout) {
            clearTimeout(this.resizeTimeout);
        }
    }
}

// 全域實例
window.AdminLayoutManager = AdminLayoutManager;

// 當 DOM 載入完成時初始化
document.addEventListener('DOMContentLoaded', () => {
    window.adminLayout = new AdminLayoutManager();
});

// Alpine.js 整合
document.addEventListener('alpine:init', () => {
    Alpine.data('adminLayout', () => ({
        isMobile: false,
        isTablet: false,
        sidebarCollapsed: false,
        sidebarMobile: false,
        
        init() {
            // 從全域管理器取得狀態
            if (window.adminLayout) {
                const viewport = window.adminLayout.getCurrentViewport();
                this.isMobile = viewport.isMobile;
                this.isTablet = viewport.isTablet;
            }
            
            // 監聽視窗變更事件
            window.addEventListener('admin:viewport-changed', (e) => {
                this.isMobile = e.detail.isMobile;
                this.isTablet = e.detail.isTablet;
            });
            
            // 監聽主題變更事件
            window.addEventListener('admin:theme-changed', (e) => {
                this.$dispatch('theme-updated', e.detail);
            });
        },
        
        toggleSidebar() {
            if (this.isMobile) {
                this.sidebarMobile = !this.sidebarMobile;
                this.$wire.set('sidebarMobile', this.sidebarMobile);
            } else {
                this.sidebarCollapsed = !this.sidebarCollapsed;
                this.$wire.set('sidebarCollapsed', this.sidebarCollapsed);
            }
        },
        
        closeMobileSidebar() {
            if (this.isMobile) {
                this.sidebarMobile = false;
                this.$wire.set('sidebarMobile', false);
            }
        }
    }));
});

// 匯出供其他模組使用
export { AdminLayoutManager };