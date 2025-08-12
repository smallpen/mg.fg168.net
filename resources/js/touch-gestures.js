/**
 * 觸控手勢支援系統
 * 提供滑動手勢、觸控回饋和手勢識別功能
 */

class TouchGestureManager {
    constructor() {
        this.startX = 0;
        this.startY = 0;
        this.currentX = 0;
        this.currentY = 0;
        this.isDragging = false;
        this.threshold = 50; // 最小滑動距離
        this.velocityThreshold = 0.3; // 最小滑動速度
        this.maxVerticalDistance = 100; // 最大垂直偏移
        
        this.callbacks = {
            swipeLeft: [],
            swipeRight: [],
            swipeUp: [],
            swipeDown: [],
            tap: [],
            longPress: []
        };
        
        this.longPressTimer = null;
        this.longPressDelay = 500;
        
        this.init();
    }
    
    /**
     * 初始化觸控事件監聽器
     */
    init() {
        // 檢查是否為觸控裝置
        if (!this.isTouchDevice()) {
            return;
        }
        
        // 綁定觸控事件
        document.addEventListener('touchstart', this.handleTouchStart.bind(this), { passive: false });
        document.addEventListener('touchmove', this.handleTouchMove.bind(this), { passive: false });
        document.addEventListener('touchend', this.handleTouchEnd.bind(this), { passive: false });
        document.addEventListener('touchcancel', this.handleTouchCancel.bind(this), { passive: false });
        
        // 綁定滑鼠事件（用於桌面測試）
        document.addEventListener('mousedown', this.handleMouseDown.bind(this));
        document.addEventListener('mousemove', this.handleMouseMove.bind(this));
        document.addEventListener('mouseup', this.handleMouseUp.bind(this));
        
        // 防止預設的觸控行為
        document.addEventListener('touchmove', (e) => {
            if (this.isDragging) {
                e.preventDefault();
            }
        }, { passive: false });
    }
    
    /**
     * 檢查是否為觸控裝置
     */
    isTouchDevice() {
        return 'ontouchstart' in window || navigator.maxTouchPoints > 0;
    }
    
    /**
     * 處理觸控開始事件
     */
    handleTouchStart(e) {
        const touch = e.touches[0];
        this.startTouch(touch.clientX, touch.clientY);
    }
    
    /**
     * 處理觸控移動事件
     */
    handleTouchMove(e) {
        if (!this.isDragging) return;
        
        const touch = e.touches[0];
        this.moveTouch(touch.clientX, touch.clientY);
        
        // 顯示滑動指示器
        this.showSwipeIndicator();
    }
    
    /**
     * 處理觸控結束事件
     */
    handleTouchEnd(e) {
        this.endTouch();
    }
    
    /**
     * 處理觸控取消事件
     */
    handleTouchCancel(e) {
        this.cancelTouch();
    }
    
    /**
     * 處理滑鼠按下事件
     */
    handleMouseDown(e) {
        this.startTouch(e.clientX, e.clientY);
    }
    
    /**
     * 處理滑鼠移動事件
     */
    handleMouseMove(e) {
        if (!this.isDragging) return;
        this.moveTouch(e.clientX, e.clientY);
    }
    
    /**
     * 處理滑鼠釋放事件
     */
    handleMouseUp(e) {
        this.endTouch();
    }
    
    /**
     * 開始觸控
     */
    startTouch(x, y) {
        this.startX = x;
        this.startY = y;
        this.currentX = x;
        this.currentY = y;
        this.isDragging = true;
        this.startTime = Date.now();
        
        // 開始長按計時器
        this.longPressTimer = setTimeout(() => {
            if (this.isDragging) {
                this.triggerCallback('longPress', {
                    x: this.currentX,
                    y: this.currentY
                });
            }
        }, this.longPressDelay);
    }
    
    /**
     * 移動觸控
     */
    moveTouch(x, y) {
        this.currentX = x;
        this.currentY = y;
        
        // 如果移動距離超過閾值，取消長按
        const distance = this.getDistance();
        if (distance > 10 && this.longPressTimer) {
            clearTimeout(this.longPressTimer);
            this.longPressTimer = null;
        }
    }
    
    /**
     * 結束觸控
     */
    endTouch() {
        if (!this.isDragging) return;
        
        // 清除長按計時器
        if (this.longPressTimer) {
            clearTimeout(this.longPressTimer);
            this.longPressTimer = null;
        }
        
        const distance = this.getDistance();
        const deltaX = this.currentX - this.startX;
        const deltaY = this.currentY - this.startY;
        const deltaTime = Date.now() - this.startTime;
        const velocity = distance / deltaTime;
        
        // 隱藏滑動指示器
        this.hideSwipeIndicator();
        
        // 判斷手勢類型
        if (distance < 10 && deltaTime < 300) {
            // 點擊
            this.triggerCallback('tap', {
                x: this.currentX,
                y: this.currentY
            });
        } else if (distance > this.threshold && velocity > this.velocityThreshold) {
            // 滑動手勢
            const absX = Math.abs(deltaX);
            const absY = Math.abs(deltaY);
            
            if (absX > absY && absY < this.maxVerticalDistance) {
                // 水平滑動
                if (deltaX > 0) {
                    this.triggerCallback('swipeRight', {
                        distance: absX,
                        velocity: velocity,
                        startX: this.startX,
                        endX: this.currentX
                    });
                } else {
                    this.triggerCallback('swipeLeft', {
                        distance: absX,
                        velocity: velocity,
                        startX: this.startX,
                        endX: this.currentX
                    });
                }
            } else if (absY > absX) {
                // 垂直滑動
                if (deltaY > 0) {
                    this.triggerCallback('swipeDown', {
                        distance: absY,
                        velocity: velocity,
                        startY: this.startY,
                        endY: this.currentY
                    });
                } else {
                    this.triggerCallback('swipeUp', {
                        distance: absY,
                        velocity: velocity,
                        startY: this.startY,
                        endY: this.currentY
                    });
                }
            }
        }
        
        this.resetTouch();
    }
    
    /**
     * 取消觸控
     */
    cancelTouch() {
        if (this.longPressTimer) {
            clearTimeout(this.longPressTimer);
            this.longPressTimer = null;
        }
        
        this.hideSwipeIndicator();
        this.resetTouch();
    }
    
    /**
     * 重置觸控狀態
     */
    resetTouch() {
        this.isDragging = false;
        this.startX = 0;
        this.startY = 0;
        this.currentX = 0;
        this.currentY = 0;
    }
    
    /**
     * 計算移動距離
     */
    getDistance() {
        const deltaX = this.currentX - this.startX;
        const deltaY = this.currentY - this.startY;
        return Math.sqrt(deltaX * deltaX + deltaY * deltaY);
    }
    
    /**
     * 顯示滑動指示器
     */
    showSwipeIndicator() {
        const deltaX = this.currentX - this.startX;
        
        // 只在水平滑動時顯示指示器
        if (Math.abs(deltaX) > 20) {
            let indicator = document.querySelector('.touch-swipe-indicator');
            
            if (!indicator) {
                indicator = document.createElement('div');
                indicator.className = 'touch-swipe-indicator';
                document.body.appendChild(indicator);
            }
            
            indicator.classList.add('active');
            
            // 根據滑動方向調整指示器位置
            if (deltaX > 0) {
                indicator.style.left = '0';
                indicator.style.right = 'auto';
            } else {
                indicator.style.left = 'auto';
                indicator.style.right = '0';
                indicator.style.borderRadius = '4px 0 0 4px';
            }
        }
    }
    
    /**
     * 隱藏滑動指示器
     */
    hideSwipeIndicator() {
        const indicator = document.querySelector('.touch-swipe-indicator');
        if (indicator) {
            indicator.classList.remove('active');
            setTimeout(() => {
                if (indicator.parentNode) {
                    indicator.parentNode.removeChild(indicator);
                }
            }, 300);
        }
    }
    
    /**
     * 註冊手勢回調函數
     */
    on(gesture, callback) {
        if (this.callbacks[gesture]) {
            this.callbacks[gesture].push(callback);
        }
    }
    
    /**
     * 移除手勢回調函數
     */
    off(gesture, callback) {
        if (this.callbacks[gesture]) {
            const index = this.callbacks[gesture].indexOf(callback);
            if (index > -1) {
                this.callbacks[gesture].splice(index, 1);
            }
        }
    }
    
    /**
     * 觸發回調函數
     */
    triggerCallback(gesture, data) {
        if (this.callbacks[gesture]) {
            this.callbacks[gesture].forEach(callback => {
                try {
                    callback(data);
                } catch (error) {
                    console.error('觸控手勢回調錯誤:', error);
                }
            });
        }
    }
    
    /**
     * 設定手勢參數
     */
    setOptions(options) {
        if (options.threshold !== undefined) {
            this.threshold = options.threshold;
        }
        if (options.velocityThreshold !== undefined) {
            this.velocityThreshold = options.velocityThreshold;
        }
        if (options.maxVerticalDistance !== undefined) {
            this.maxVerticalDistance = options.maxVerticalDistance;
        }
        if (options.longPressDelay !== undefined) {
            this.longPressDelay = options.longPressDelay;
        }
    }
}

/**
 * 觸控回饋效果管理器
 */
class TouchFeedbackManager {
    constructor() {
        this.init();
    }
    
    init() {
        // 為所有具有 touch-feedback 類別的元素添加觸控回饋
        document.addEventListener('touchstart', this.handleTouchStart.bind(this));
        document.addEventListener('mousedown', this.handleMouseDown.bind(this));
    }
    
    handleTouchStart(e) {
        this.addRippleEffect(e.target, e.touches[0]);
    }
    
    handleMouseDown(e) {
        this.addRippleEffect(e.target, e);
    }
    
    addRippleEffect(element, pointer) {
        // 檢查元素是否有 touch-feedback 類別
        if (!element.classList.contains('touch-feedback')) {
            // 檢查父元素
            let parent = element.parentElement;
            while (parent && !parent.classList.contains('touch-feedback')) {
                parent = parent.parentElement;
            }
            if (!parent) return;
            element = parent;
        }
        
        // 創建漣漪效果
        const rect = element.getBoundingClientRect();
        const size = Math.max(rect.width, rect.height);
        const x = pointer.clientX - rect.left - size / 2;
        const y = pointer.clientY - rect.top - size / 2;
        
        const ripple = document.createElement('div');
        ripple.style.cssText = `
            position: absolute;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.3);
            transform: scale(0);
            animation: ripple 0.6s linear;
            left: ${x}px;
            top: ${y}px;
            width: ${size}px;
            height: ${size}px;
            pointer-events: none;
            z-index: 1000;
        `;
        
        element.appendChild(ripple);
        
        // 移除漣漪效果
        setTimeout(() => {
            if (ripple.parentNode) {
                ripple.parentNode.removeChild(ripple);
            }
        }, 600);
    }
}

/**
 * 響應式佈局管理器
 */
class ResponsiveLayoutManager {
    constructor() {
        this.breakpoints = {
            mobile: 768,
            tablet: 1024,
            desktop: 1280
        };
        
        this.currentBreakpoint = this.getCurrentBreakpoint();
        this.callbacks = {
            mobile: [],
            tablet: [],
            desktop: [],
            change: []
        };
        
        this.init();
    }
    
    init() {
        // 監聽視窗大小變化
        window.addEventListener('resize', this.handleResize.bind(this));
        
        // 初始化佈局
        this.updateLayout();
    }
    
    getCurrentBreakpoint() {
        const width = window.innerWidth;
        
        if (width < this.breakpoints.mobile) {
            return 'mobile';
        } else if (width < this.breakpoints.tablet) {
            return 'tablet';
        } else {
            return 'desktop';
        }
    }
    
    handleResize() {
        // 使用防抖來避免過度觸發
        clearTimeout(this.resizeTimeout);
        this.resizeTimeout = setTimeout(() => {
            const newBreakpoint = this.getCurrentBreakpoint();
            
            if (newBreakpoint !== this.currentBreakpoint) {
                const oldBreakpoint = this.currentBreakpoint;
                this.currentBreakpoint = newBreakpoint;
                
                this.updateLayout();
                this.triggerCallback('change', {
                    from: oldBreakpoint,
                    to: newBreakpoint,
                    width: window.innerWidth
                });
            }
        }, 150);
    }
    
    updateLayout() {
        // 更新 body 類別
        document.body.classList.remove('layout-mobile', 'layout-tablet', 'layout-desktop');
        document.body.classList.add(`layout-${this.currentBreakpoint}`);
        
        // 觸發對應的回調
        this.triggerCallback(this.currentBreakpoint, {
            breakpoint: this.currentBreakpoint,
            width: window.innerWidth
        });
    }
    
    on(event, callback) {
        if (this.callbacks[event]) {
            this.callbacks[event].push(callback);
        }
    }
    
    off(event, callback) {
        if (this.callbacks[event]) {
            const index = this.callbacks[event].indexOf(callback);
            if (index > -1) {
                this.callbacks[event].splice(index, 1);
            }
        }
    }
    
    triggerCallback(event, data) {
        if (this.callbacks[event]) {
            this.callbacks[event].forEach(callback => {
                try {
                    callback(data);
                } catch (error) {
                    console.error('響應式佈局回調錯誤:', error);
                }
            });
        }
    }
    
    isMobile() {
        return this.currentBreakpoint === 'mobile';
    }
    
    isTablet() {
        return this.currentBreakpoint === 'tablet';
    }
    
    isDesktop() {
        return this.currentBreakpoint === 'desktop';
    }
}

// 創建全域實例
window.touchGestureManager = new TouchGestureManager();
window.touchFeedbackManager = new TouchFeedbackManager();
window.responsiveLayoutManager = new ResponsiveLayoutManager();

// 添加 CSS 動畫
const style = document.createElement('style');
style.textContent = `
    @keyframes ripple {
        to {
            transform: scale(4);
            opacity: 0;
        }
    }
`;
document.head.appendChild(style);

// 導出類別供其他模組使用
export { TouchGestureManager, TouchFeedbackManager, ResponsiveLayoutManager };