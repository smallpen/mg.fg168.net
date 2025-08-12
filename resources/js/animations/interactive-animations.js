/**
 * 互動動畫系統 JavaScript 控制器
 * Interactive Animation System Controller
 */

class InteractiveAnimations {
    constructor() {
        this.init();
        this.bindEvents();
        this.setupGestureSupport();
    }

    /**
     * 初始化動畫系統
     */
    init() {
        // 檢查是否支援動畫
        this.supportsAnimations = this.checkAnimationSupport();
        
        // 檢查使用者偏好
        this.respectsReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        
        // 設定效能模式
        this.performanceMode = this.detectPerformanceMode();
        
        // 初始化頁面載入動畫
        this.initPageTransitions();
        
        console.log('互動動畫系統已初始化');
    }

    /**
     * 檢查動畫支援
     */
    checkAnimationSupport() {
        const element = document.createElement('div');
        const animationSupport = 'animation' in element.style;
        const transitionSupport = 'transition' in element.style;
        return animationSupport && transitionSupport;
    }

    /**
     * 檢測效能模式
     */
    detectPerformanceMode() {
        // 基於裝置和連線速度判斷
        const connection = navigator.connection || navigator.mozConnection || navigator.webkitConnection;
        const isSlowConnection = connection && (connection.effectiveType === 'slow-2g' || connection.effectiveType === '2g');
        const isLowEndDevice = navigator.hardwareConcurrency && navigator.hardwareConcurrency <= 2;
        
        return isSlowConnection || isLowEndDevice;
    }

    /**
     * 綁定事件監聽器
     */
    bindEvents() {
        // 頁面切換動畫
        this.bindPageTransitions();
        
        // 選單動畫
        this.bindMenuAnimations();
        
        // 按鈕動畫
        this.bindButtonAnimations();
        
        // 載入動畫
        this.bindLoadingAnimations();
        
        // 狀態變更動畫
        this.bindStateAnimations();
    }

    /**
     * 初始化頁面轉換動畫
     */
    initPageTransitions() {
        // 頁面載入時的淡入動畫
        document.addEventListener('DOMContentLoaded', () => {
            const mainContent = document.querySelector('.main-content');
            if (mainContent && !this.respectsReducedMotion) {
                mainContent.classList.add('page-transition-enter');
            }
        });
    }

    /**
     * 綁定頁面轉換動畫
     */
    bindPageTransitions() {
        // 監聽 Livewire 頁面更新
        document.addEventListener('livewire:navigating', (event) => {
            this.handlePageLeave();
        });

        document.addEventListener('livewire:navigated', (event) => {
            this.handlePageEnter();
        });

        // 監聽一般連結點擊
        document.addEventListener('click', (event) => {
            const link = event.target.closest('a[href]');
            if (link && !link.hasAttribute('wire:navigate')) {
                const href = link.getAttribute('href');
                if (href && !href.startsWith('#') && !href.startsWith('javascript:')) {
                    this.handleLinkTransition(link, href);
                }
            }
        });
    }

    /**
     * 處理頁面離開動畫
     */
    handlePageLeave() {
        const mainContent = document.querySelector('.main-content');
        if (mainContent && !this.respectsReducedMotion) {
            mainContent.classList.add('page-transition-leave');
        }
    }

    /**
     * 處理頁面進入動畫
     */
    handlePageEnter() {
        const mainContent = document.querySelector('.main-content');
        if (mainContent && !this.respectsReducedMotion) {
            mainContent.classList.remove('page-transition-leave');
            mainContent.classList.add('page-transition-enter');
            
            // 清理動畫類別
            setTimeout(() => {
                mainContent.classList.remove('page-transition-enter');
            }, 300);
        }
    }

    /**
     * 處理連結轉換動畫
     */
    handleLinkTransition(link, href) {
        if (this.respectsReducedMotion) return;

        event.preventDefault();
        
        const mainContent = document.querySelector('.main-content');
        if (mainContent) {
            mainContent.classList.add('page-transition-leave');
            
            setTimeout(() => {
                window.location.href = href;
            }, 150);
        } else {
            window.location.href = href;
        }
    }

    /**
     * 綁定選單動畫
     */
    bindMenuAnimations() {
        // 側邊欄切換動畫
        document.addEventListener('click', (event) => {
            const toggleBtn = event.target.closest('[data-sidebar-toggle]');
            if (toggleBtn) {
                this.handleSidebarToggle();
            }
        });

        // 選單項目展開/收合動畫
        document.addEventListener('click', (event) => {
            const menuItem = event.target.closest('[data-menu-toggle]');
            if (menuItem) {
                this.handleMenuToggle(menuItem);
            }
        });

        // 選單項目懸停效果
        this.bindMenuHoverEffects();
    }

    /**
     * 處理側邊欄切換動畫
     */
    handleSidebarToggle() {
        const sidebar = document.querySelector('.sidebar');
        const mainContent = document.querySelector('.main-content');
        
        if (!sidebar || this.respectsReducedMotion) return;

        const isCollapsed = sidebar.classList.contains('collapsed');
        
        if (isCollapsed) {
            sidebar.classList.remove('collapsed');
            sidebar.classList.add('sidebar-expand');
        } else {
            sidebar.classList.add('collapsed');
            sidebar.classList.add('sidebar-collapse');
        }

        // 清理動畫類別
        setTimeout(() => {
            sidebar.classList.remove('sidebar-expand', 'sidebar-collapse');
        }, 300);
    }

    /**
     * 處理選單項目切換動畫
     */
    handleMenuToggle(menuItem) {
        const submenu = menuItem.nextElementSibling;
        const icon = menuItem.querySelector('.menu-icon');
        
        if (!submenu || this.respectsReducedMotion) return;

        const isExpanded = submenu.classList.contains('expanded');
        
        if (isExpanded) {
            submenu.classList.remove('expanded');
            menuItem.classList.remove('expanded');
        } else {
            // 關閉其他展開的選單
            document.querySelectorAll('.submenu.expanded').forEach(otherSubmenu => {
                if (otherSubmenu !== submenu) {
                    otherSubmenu.classList.remove('expanded');
                    const otherMenuItem = otherSubmenu.previousElementSibling;
                    if (otherMenuItem) {
                        otherMenuItem.classList.remove('expanded');
                    }
                }
            });
            
            submenu.classList.add('expanded');
            menuItem.classList.add('expanded');
        }
    }

    /**
     * 綁定選單懸停效果
     */
    bindMenuHoverEffects() {
        const menuItems = document.querySelectorAll('.menu-item');
        
        menuItems.forEach(item => {
            item.addEventListener('mouseenter', () => {
                if (!this.respectsReducedMotion && window.innerWidth >= 1024) {
                    item.style.transform = 'translateX(4px)';
                }
            });
            
            item.addEventListener('mouseleave', () => {
                if (!this.respectsReducedMotion && window.innerWidth >= 1024) {
                    item.style.transform = '';
                }
            });
        });
    }

    /**
     * 綁定按鈕動畫
     */
    bindButtonAnimations() {
        // 波紋效果
        this.bindRippleEffect();
        
        // 按鈕狀態動畫
        this.bindButtonStates();
        
        // 載入按鈕動畫
        this.bindLoadingButtons();
    }

    /**
     * 綁定波紋效果
     */
    bindRippleEffect() {
        document.addEventListener('click', (event) => {
            const button = event.target.closest('.btn-ripple');
            if (!button || this.respectsReducedMotion) return;

            const rect = button.getBoundingClientRect();
            const size = Math.max(rect.width, rect.height);
            const x = event.clientX - rect.left - size / 2;
            const y = event.clientY - rect.top - size / 2;

            const ripple = document.createElement('span');
            ripple.style.cssText = `
                position: absolute;
                width: ${size}px;
                height: ${size}px;
                left: ${x}px;
                top: ${y}px;
                background: rgba(255, 255, 255, 0.3);
                border-radius: 50%;
                transform: scale(0);
                animation: ripple 0.6s linear;
                pointer-events: none;
            `;

            button.appendChild(ripple);

            setTimeout(() => {
                ripple.remove();
            }, 600);
        });
    }

    /**
     * 綁定按鈕狀態動畫
     */
    bindButtonStates() {
        // 成功狀態動畫
        document.addEventListener('button-success', (event) => {
            const button = event.target;
            if (button && !this.respectsReducedMotion) {
                button.classList.add('btn-success-animation');
                setTimeout(() => {
                    button.classList.remove('btn-success-animation');
                }, 500);
            }
        });

        // 錯誤狀態動畫
        document.addEventListener('button-error', (event) => {
            const button = event.target;
            if (button && !this.respectsReducedMotion) {
                button.classList.add('btn-error-animation');
                setTimeout(() => {
                    button.classList.remove('btn-error-animation');
                }, 300);
            }
        });
    }

    /**
     * 綁定載入按鈕動畫
     */
    bindLoadingButtons() {
        // 監聽 Livewire 載入狀態
        document.addEventListener('livewire:loading', (event) => {
            const button = event.target.querySelector('button[type="submit"]');
            if (button) {
                this.setButtonLoading(button, true);
            }
        });

        document.addEventListener('livewire:loaded', (event) => {
            const button = event.target.querySelector('button[type="submit"]');
            if (button) {
                this.setButtonLoading(button, false);
            }
        });
    }

    /**
     * 設定按鈕載入狀態
     */
    setButtonLoading(button, loading) {
        if (loading) {
            button.classList.add('btn-loading');
            button.disabled = true;
        } else {
            button.classList.remove('btn-loading');
            button.disabled = false;
        }
    }

    /**
     * 綁定載入動畫
     */
    bindLoadingAnimations() {
        // 全域載入覆蓋層
        this.bindGlobalLoading();
        
        // 區域載入動畫
        this.bindSectionLoading();
    }

    /**
     * 綁定全域載入動畫
     */
    bindGlobalLoading() {
        let loadingOverlay = null;

        // 顯示載入覆蓋層
        window.showGlobalLoading = (message = '載入中...') => {
            if (this.respectsReducedMotion) return;

            if (!loadingOverlay) {
                loadingOverlay = this.createLoadingOverlay(message);
                document.body.appendChild(loadingOverlay);
            }

            setTimeout(() => {
                loadingOverlay.classList.add('active');
            }, 10);
        };

        // 隱藏載入覆蓋層
        window.hideGlobalLoading = () => {
            if (loadingOverlay) {
                loadingOverlay.classList.remove('active');
                setTimeout(() => {
                    if (loadingOverlay && loadingOverlay.parentNode) {
                        loadingOverlay.parentNode.removeChild(loadingOverlay);
                        loadingOverlay = null;
                    }
                }, 150);
            }
        };
    }

    /**
     * 建立載入覆蓋層
     */
    createLoadingOverlay(message) {
        const overlay = document.createElement('div');
        overlay.className = 'loading-overlay';
        overlay.innerHTML = `
            <div class="text-center">
                <div class="loading-spinner mb-4"></div>
                <div class="text-gray-600 dark:text-gray-400">${message}</div>
                <div class="loading-progress">
                    <div class="loading-progress-bar"></div>
                </div>
            </div>
        `;
        return overlay;
    }

    /**
     * 綁定區域載入動畫
     */
    bindSectionLoading() {
        // 監聽 Livewire 區域載入
        document.addEventListener('livewire:loading', (event) => {
            const target = event.target;
            if (target && !this.respectsReducedMotion) {
                this.showSectionLoading(target);
            }
        });

        document.addEventListener('livewire:loaded', (event) => {
            const target = event.target;
            if (target) {
                this.hideSectionLoading(target);
            }
        });
    }

    /**
     * 顯示區域載入動畫
     */
    showSectionLoading(element) {
        const loadingIndicator = document.createElement('div');
        loadingIndicator.className = 'section-loading';
        loadingIndicator.innerHTML = `
            <div class="loading-dots">
                <span></span>
                <span></span>
                <span></span>
            </div>
        `;
        
        element.style.position = 'relative';
        element.appendChild(loadingIndicator);
    }

    /**
     * 隱藏區域載入動畫
     */
    hideSectionLoading(element) {
        const loadingIndicator = element.querySelector('.section-loading');
        if (loadingIndicator) {
            loadingIndicator.remove();
        }
    }

    /**
     * 綁定狀態動畫
     */
    bindStateAnimations() {
        // 監聽自訂狀態事件
        document.addEventListener('state-success', (event) => {
            this.animateStateChange(event.target, 'success');
        });

        document.addEventListener('state-error', (event) => {
            this.animateStateChange(event.target, 'error');
        });

        document.addEventListener('state-warning', (event) => {
            this.animateStateChange(event.target, 'warning');
        });
    }

    /**
     * 執行狀態變更動畫
     */
    animateStateChange(element, state) {
        if (!element || this.respectsReducedMotion) return;

        element.classList.add(`state-${state}`);
        
        setTimeout(() => {
            element.classList.remove(`state-${state}`);
        }, state === 'success' ? 500 : 300);
    }

    /**
     * 設定手勢支援
     */
    setupGestureSupport() {
        if ('ontouchstart' in window) {
            this.bindTouchGestures();
        }
        
        this.bindDragAndDrop();
        this.bindLongPress();
    }

    /**
     * 綁定觸控手勢
     */
    bindTouchGestures() {
        let touchStartX = 0;
        let touchStartY = 0;
        let touchStartTime = 0;

        document.addEventListener('touchstart', (event) => {
            touchStartX = event.touches[0].clientX;
            touchStartY = event.touches[0].clientY;
            touchStartTime = Date.now();
        });

        document.addEventListener('touchend', (event) => {
            const touchEndX = event.changedTouches[0].clientX;
            const touchEndY = event.changedTouches[0].clientY;
            const touchEndTime = Date.now();
            
            const deltaX = touchEndX - touchStartX;
            const deltaY = touchEndY - touchStartY;
            const deltaTime = touchEndTime - touchStartTime;
            
            // 檢測滑動手勢
            if (Math.abs(deltaX) > 50 && deltaTime < 300) {
                const direction = deltaX > 0 ? 'right' : 'left';
                this.handleSwipeGesture(event.target, direction, deltaX);
            } else if (Math.abs(deltaY) > 50 && deltaTime < 300) {
                const direction = deltaY > 0 ? 'down' : 'up';
                this.handleSwipeGesture(event.target, direction, deltaY);
            }
        });
    }

    /**
     * 處理滑動手勢
     */
    handleSwipeGesture(element, direction, delta) {
        if (this.respectsReducedMotion) return;

        // 側邊欄滑動手勢
        if (element.closest('.sidebar') || element.closest('.main-content')) {
            if (direction === 'right' && Math.abs(delta) > 100) {
                // 向右滑動開啟側邊欄
                const sidebar = document.querySelector('.sidebar');
                if (sidebar && sidebar.classList.contains('collapsed')) {
                    this.handleSidebarToggle();
                }
            } else if (direction === 'left' && Math.abs(delta) > 100) {
                // 向左滑動關閉側邊欄
                const sidebar = document.querySelector('.sidebar');
                if (sidebar && !sidebar.classList.contains('collapsed')) {
                    this.handleSidebarToggle();
                }
            }
        }

        // 添加滑動動畫效果
        element.classList.add(`swipe-${direction}`);
        setTimeout(() => {
            element.classList.remove(`swipe-${direction}`);
        }, 300);
    }

    /**
     * 綁定拖拽功能
     */
    bindDragAndDrop() {
        const draggables = document.querySelectorAll('.draggable');
        
        draggables.forEach(draggable => {
            draggable.addEventListener('dragstart', (event) => {
                if (!this.respectsReducedMotion) {
                    draggable.classList.add('drag-ghost');
                }
            });
            
            draggable.addEventListener('dragend', (event) => {
                draggable.classList.remove('drag-ghost');
            });
        });

        // 拖拽區域
        const dropZones = document.querySelectorAll('.drop-zone');
        
        dropZones.forEach(zone => {
            zone.addEventListener('dragover', (event) => {
                event.preventDefault();
                if (!this.respectsReducedMotion) {
                    zone.classList.add('drag-over');
                }
            });
            
            zone.addEventListener('dragleave', (event) => {
                zone.classList.remove('drag-over');
            });
            
            zone.addEventListener('drop', (event) => {
                event.preventDefault();
                zone.classList.remove('drag-over');
            });
        });
    }

    /**
     * 綁定長按功能
     */
    bindLongPress() {
        const longPressElements = document.querySelectorAll('.long-press');
        
        longPressElements.forEach(element => {
            let pressTimer = null;
            
            const startPress = () => {
                if (!this.respectsReducedMotion) {
                    element.classList.add('pressing');
                }
                
                pressTimer = setTimeout(() => {
                    element.dispatchEvent(new CustomEvent('longpress'));
                    element.classList.remove('pressing');
                }, 800);
            };
            
            const endPress = () => {
                if (pressTimer) {
                    clearTimeout(pressTimer);
                    pressTimer = null;
                }
                element.classList.remove('pressing');
            };
            
            element.addEventListener('mousedown', startPress);
            element.addEventListener('mouseup', endPress);
            element.addEventListener('mouseleave', endPress);
            element.addEventListener('touchstart', startPress);
            element.addEventListener('touchend', endPress);
        });
    }

    /**
     * 公用方法：觸發動畫
     */
    static triggerAnimation(element, animationClass, duration = 300) {
        if (!element) return;
        
        element.classList.add(animationClass);
        
        setTimeout(() => {
            element.classList.remove(animationClass);
        }, duration);
    }

    /**
     * 公用方法：設定效能模式
     */
    static setPerformanceMode(enabled) {
        const body = document.body;
        
        if (enabled) {
            body.classList.add('performance-mode');
        } else {
            body.classList.remove('performance-mode');
        }
    }

    /**
     * 公用方法：設定除錯模式
     */
    static setDebugMode(enabled) {
        const body = document.body;
        
        if (enabled) {
            body.classList.add('debug-animations');
        } else {
            body.classList.remove('debug-animations');
        }
    }
}

// 初始化動畫系統
document.addEventListener('DOMContentLoaded', () => {
    window.interactiveAnimations = new InteractiveAnimations();
});

// 導出類別供其他模組使用
export default InteractiveAnimations;