/**
 * 手勢動畫處理器
 * Gesture Animation Handler
 */

class GestureHandler {
    constructor() {
        this.touchStartX = 0;
        this.touchStartY = 0;
        this.touchStartTime = 0;
        this.longPressTimer = null;
        this.gestureTrails = [];
        this.multiTouchPoints = new Map();
        
        this.init();
    }

    /**
     * 初始化手勢處理器
     */
    init() {
        if ('ontouchstart' in window) {
            this.bindTouchEvents();
        }
        
        this.bindMouseEvents();
        this.bindDragEvents();
        this.setupGestureTrails();
        
        console.log('手勢動畫處理器已初始化');
    }

    /**
     * 綁定觸控事件
     */
    bindTouchEvents() {
        // 單點觸控事件
        document.addEventListener('touchstart', this.handleTouchStart.bind(this), { passive: false });
        document.addEventListener('touchmove', this.handleTouchMove.bind(this), { passive: false });
        document.addEventListener('touchend', this.handleTouchEnd.bind(this), { passive: false });
        document.addEventListener('touchcancel', this.handleTouchCancel.bind(this), { passive: false });

        // 多點觸控事件
        document.addEventListener('gesturestart', this.handleGestureStart.bind(this), { passive: false });
        document.addEventListener('gesturechange', this.handleGestureChange.bind(this), { passive: false });
        document.addEventListener('gestureend', this.handleGestureEnd.bind(this), { passive: false });
    }

    /**
     * 綁定滑鼠事件（用於桌面測試）
     */
    bindMouseEvents() {
        document.addEventListener('mousedown', this.handleMouseDown.bind(this));
        document.addEventListener('mousemove', this.handleMouseMove.bind(this));
        document.addEventListener('mouseup', this.handleMouseUp.bind(this));
    }

    /**
     * 處理觸控開始
     */
    handleTouchStart(event) {
        const touch = event.touches[0];
        this.touchStartX = touch.clientX;
        this.touchStartY = touch.clientY;
        this.touchStartTime = Date.now();

        // 觸控回饋
        this.addTouchFeedback(event.target, touch.clientX, touch.clientY);

        // 長按檢測
        this.startLongPressDetection(event.target, touch);

        // 多點觸控處理
        if (event.touches.length > 1) {
            this.handleMultiTouch(event);
        }

        // 手勢軌跡
        this.addGestureTrail(touch.clientX, touch.clientY);
    }

    /**
     * 處理觸控移動
     */
    handleTouchMove(event) {
        const touch = event.touches[0];
        
        // 取消長按
        this.cancelLongPress();

        // 滑動檢測
        this.detectSwipeGesture(touch);

        // 拖拽處理
        this.handleDragMove(event.target, touch);

        // 手勢軌跡
        this.addGestureTrail(touch.clientX, touch.clientY);

        // 多點觸控處理
        if (event.touches.length > 1) {
            this.handleMultiTouchMove(event);
        }
    }

    /**
     * 處理觸控結束
     */
    handleTouchEnd(event) {
        const touch = event.changedTouches[0];
        const touchEndTime = Date.now();
        const touchDuration = touchEndTime - this.touchStartTime;

        // 取消長按
        this.cancelLongPress();

        // 滑動手勢檢測
        this.processSwipeGesture(touch, touchDuration);

        // 點擊檢測
        this.processTapGesture(event.target, touch, touchDuration);

        // 清理多點觸控
        this.cleanupMultiTouch(event);

        // 拖拽結束
        this.handleDragEnd(event.target);
    }

    /**
     * 處理觸控取消
     */
    handleTouchCancel(event) {
        this.cancelLongPress();
        this.cleanupMultiTouch(event);
        this.handleDragEnd(event.target);
    }

    /**
     * 添加觸控回饋
     */
    addTouchFeedback(element, x, y) {
        const touchElement = element.closest('.touch-feedback');
        if (!touchElement) return;

        touchElement.classList.add('touching');
        
        setTimeout(() => {
            touchElement.classList.remove('touching');
        }, 300);
    }

    /**
     * 開始長按檢測
     */
    startLongPressDetection(element, touch) {
        const longPressElement = element.closest('.long-press');
        if (!longPressElement) return;

        longPressElement.classList.add('pressing');

        this.longPressTimer = setTimeout(() => {
            this.triggerLongPress(longPressElement, touch);
        }, 800);
    }

    /**
     * 取消長按
     */
    cancelLongPress() {
        if (this.longPressTimer) {
            clearTimeout(this.longPressTimer);
            this.longPressTimer = null;
        }

        document.querySelectorAll('.long-press.pressing').forEach(element => {
            element.classList.remove('pressing');
        });
    }

    /**
     * 觸發長按事件
     */
    triggerLongPress(element, touch) {
        element.classList.remove('pressing');
        
        // 添加長按回饋動畫
        const feedback = document.createElement('div');
        feedback.className = 'long-press-feedback';
        element.appendChild(feedback);

        setTimeout(() => {
            feedback.remove();
        }, 800);

        // 觸發自訂事件
        element.dispatchEvent(new CustomEvent('longpress', {
            detail: { x: touch.clientX, y: touch.clientY }
        }));
    }

    /**
     * 檢測滑動手勢
     */
    detectSwipeGesture(touch) {
        const deltaX = touch.clientX - this.touchStartX;
        const deltaY = touch.clientY - this.touchStartY;
        const distance = Math.sqrt(deltaX * deltaX + deltaY * deltaY);

        if (distance > 10) {
            // 顯示滑動指示器
            this.showSwipeIndicator(deltaX, deltaY);
        }
    }

    /**
     * 處理滑動手勢
     */
    processSwipeGesture(touch, duration) {
        const deltaX = touch.clientX - this.touchStartX;
        const deltaY = touch.clientY - this.touchStartY;
        const distance = Math.sqrt(deltaX * deltaX + deltaY * deltaY);

        if (distance > 50 && duration < 300) {
            let direction;
            
            if (Math.abs(deltaX) > Math.abs(deltaY)) {
                direction = deltaX > 0 ? 'right' : 'left';
            } else {
                direction = deltaY > 0 ? 'down' : 'up';
            }

            this.triggerSwipeAnimation(direction, distance);
        }
    }

    /**
     * 觸發滑動動畫
     */
    triggerSwipeAnimation(direction, distance) {
        const swipeElement = document.elementFromPoint(this.touchStartX, this.touchStartY);
        const swipeContainer = swipeElement?.closest('.swipe-container');
        
        if (swipeContainer) {
            swipeContainer.classList.add(`swipe-${direction}`);
            
            setTimeout(() => {
                swipeContainer.classList.remove(`swipe-${direction}`);
            }, 300);

            // 觸發自訂事件
            swipeContainer.dispatchEvent(new CustomEvent('swipe', {
                detail: { direction, distance }
            }));
        }

        // 處理側邊欄滑動
        this.handleSidebarSwipe(direction, distance);
    }

    /**
     * 處理側邊欄滑動
     */
    handleSidebarSwipe(direction, distance) {
        if (distance < 100) return;

        const sidebar = document.querySelector('.sidebar');
        if (!sidebar) return;

        if (direction === 'right' && sidebar.classList.contains('collapsed')) {
            // 向右滑動開啟側邊欄
            this.toggleSidebar(true);
        } else if (direction === 'left' && !sidebar.classList.contains('collapsed')) {
            // 向左滑動關閉側邊欄
            this.toggleSidebar(false);
        }
    }

    /**
     * 切換側邊欄
     */
    toggleSidebar(open) {
        const sidebar = document.querySelector('.sidebar');
        const toggleBtn = document.querySelector('[data-sidebar-toggle]');
        
        if (sidebar && toggleBtn) {
            if (open) {
                sidebar.classList.remove('collapsed');
            } else {
                sidebar.classList.add('collapsed');
            }

            // 觸發切換事件
            toggleBtn.dispatchEvent(new CustomEvent('click'));
        }
    }

    /**
     * 顯示滑動指示器
     */
    showSwipeIndicator(deltaX, deltaY) {
        const swipeNav = document.querySelector('.swipe-navigation');
        if (!swipeNav) return;

        const leftIndicator = swipeNav.querySelector('.swipe-nav-indicator.left');
        const rightIndicator = swipeNav.querySelector('.swipe-nav-indicator.right');

        if (Math.abs(deltaX) > Math.abs(deltaY)) {
            if (deltaX > 20 && rightIndicator) {
                rightIndicator.classList.add('active');
                leftIndicator?.classList.remove('active');
            } else if (deltaX < -20 && leftIndicator) {
                leftIndicator.classList.add('active');
                rightIndicator?.classList.remove('active');
            }
        }
    }

    /**
     * 處理點擊手勢
     */
    processTapGesture(element, touch, duration) {
        const deltaX = Math.abs(touch.clientX - this.touchStartX);
        const deltaY = Math.abs(touch.clientY - this.touchStartY);

        // 檢查是否為點擊（移動距離小且時間短）
        if (deltaX < 10 && deltaY < 10 && duration < 300) {
            this.triggerTapAnimation(element, touch);
        }
    }

    /**
     * 觸發點擊動畫
     */
    triggerTapAnimation(element, touch) {
        const rippleElement = element.closest('.btn-ripple, .touch-feedback');
        if (!rippleElement) return;

        const rect = rippleElement.getBoundingClientRect();
        const size = Math.max(rect.width, rect.height);
        const x = touch.clientX - rect.left - size / 2;
        const y = touch.clientY - rect.top - size / 2;

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
            z-index: 1000;
        `;

        rippleElement.appendChild(ripple);

        setTimeout(() => {
            ripple.remove();
        }, 600);
    }

    /**
     * 處理多點觸控
     */
    handleMultiTouch(event) {
        for (let i = 0; i < event.touches.length; i++) {
            const touch = event.touches[i];
            this.addMultiTouchPoint(touch.identifier, touch.clientX, touch.clientY);
        }
    }

    /**
     * 處理多點觸控移動
     */
    handleMultiTouchMove(event) {
        if (event.touches.length === 2) {
            this.handlePinchGesture(event.touches[0], event.touches[1]);
        }
    }

    /**
     * 添加多點觸控點
     */
    addMultiTouchPoint(id, x, y) {
        this.multiTouchPoints.set(id, { x, y });
        
        const feedback = document.createElement('div');
        feedback.className = 'multi-touch-feedback active';
        feedback.style.left = x + 'px';
        feedback.style.top = y + 'px';
        feedback.dataset.touchId = id;
        
        document.body.appendChild(feedback);
    }

    /**
     * 處理縮放手勢
     */
    handlePinchGesture(touch1, touch2) {
        const pinchElement = document.elementFromPoint(
            (touch1.clientX + touch2.clientX) / 2,
            (touch1.clientY + touch2.clientY) / 2
        )?.closest('.pinch-zoom');

        if (!pinchElement) return;

        const distance = Math.sqrt(
            Math.pow(touch2.clientX - touch1.clientX, 2) +
            Math.pow(touch2.clientY - touch1.clientY, 2)
        );

        if (!pinchElement.dataset.initialDistance) {
            pinchElement.dataset.initialDistance = distance;
            pinchElement.classList.add('zooming');
        } else {
            const initialDistance = parseFloat(pinchElement.dataset.initialDistance);
            const scale = distance / initialDistance;
            
            pinchElement.style.transform = `scale(${Math.max(0.5, Math.min(3, scale))})`;
        }
    }

    /**
     * 清理多點觸控
     */
    cleanupMultiTouch(event) {
        // 移除結束的觸控點
        for (let i = 0; i < event.changedTouches.length; i++) {
            const touch = event.changedTouches[i];
            this.multiTouchPoints.delete(touch.identifier);
            
            const feedback = document.querySelector(`[data-touch-id="${touch.identifier}"]`);
            if (feedback) {
                feedback.remove();
            }
        }

        // 清理縮放狀態
        document.querySelectorAll('.pinch-zoom.zooming').forEach(element => {
            element.classList.remove('zooming');
            delete element.dataset.initialDistance;
        });
    }

    /**
     * 設定手勢軌跡
     */
    setupGestureTrails() {
        // 清理舊的軌跡
        setInterval(() => {
            this.gestureTrails = this.gestureTrails.filter(trail => {
                if (Date.now() - trail.time > 500) {
                    trail.element.remove();
                    return false;
                }
                return true;
            });
        }, 100);
    }

    /**
     * 添加手勢軌跡
     */
    addGestureTrail(x, y) {
        if (this.gestureTrails.length > 10) return; // 限制軌跡數量

        const trail = document.createElement('div');
        trail.className = 'gesture-trail';
        trail.style.left = (x - 10) + 'px';
        trail.style.top = (y - 10) + 'px';
        
        document.body.appendChild(trail);

        this.gestureTrails.push({
            element: trail,
            time: Date.now()
        });
    }

    /**
     * 綁定拖拽事件
     */
    bindDragEvents() {
        document.addEventListener('dragstart', this.handleDragStart.bind(this));
        document.addEventListener('dragover', this.handleDragOver.bind(this));
        document.addEventListener('drop', this.handleDrop.bind(this));
        document.addEventListener('dragend', this.handleDragEnd.bind(this));
    }

    /**
     * 處理拖拽開始
     */
    handleDragStart(event) {
        const draggable = event.target.closest('.draggable');
        if (!draggable) return;

        draggable.classList.add('dragging');
        
        // 創建拖拽預覽
        this.createDragPreview(draggable, event);
    }

    /**
     * 創建拖拽預覽
     */
    createDragPreview(element, event) {
        const preview = element.cloneNode(true);
        preview.className = 'drag-preview';
        preview.style.left = event.clientX + 'px';
        preview.style.top = event.clientY + 'px';
        
        document.body.appendChild(preview);
        
        element.dataset.dragPreview = 'true';
    }

    /**
     * 處理拖拽移動
     */
    handleDragMove(element, touch) {
        const draggable = element.closest('.draggable');
        if (!draggable || !draggable.classList.contains('dragging')) return;

        const preview = document.querySelector('.drag-preview');
        if (preview) {
            preview.style.left = (touch.clientX - 20) + 'px';
            preview.style.top = (touch.clientY - 20) + 'px';
        }
    }

    /**
     * 處理拖拽懸停
     */
    handleDragOver(event) {
        event.preventDefault();
        
        const dropZone = event.target.closest('.drop-zone');
        if (dropZone) {
            dropZone.classList.add('drag-over');
        }
    }

    /**
     * 處理放置
     */
    handleDrop(event) {
        event.preventDefault();
        
        const dropZone = event.target.closest('.drop-zone');
        if (dropZone) {
            dropZone.classList.remove('drag-over');
            dropZone.classList.add('drop-success');
            
            setTimeout(() => {
                dropZone.classList.remove('drop-success');
            }, 500);

            // 觸發放置事件
            dropZone.dispatchEvent(new CustomEvent('drop-success', {
                detail: { x: event.clientX, y: event.clientY }
            }));
        }
    }

    /**
     * 處理拖拽結束
     */
    handleDragEnd(element) {
        const draggable = element?.closest('.draggable');
        if (draggable) {
            draggable.classList.remove('dragging');
        }

        // 清理拖拽預覽
        const preview = document.querySelector('.drag-preview');
        if (preview) {
            preview.remove();
        }

        // 清理拖拽狀態
        document.querySelectorAll('.drop-zone.drag-over').forEach(zone => {
            zone.classList.remove('drag-over');
        });
    }

    /**
     * 處理滑鼠事件（桌面測試用）
     */
    handleMouseDown(event) {
        if (event.target.closest('.touch-feedback, .long-press, .draggable')) {
            this.handleTouchStart({
                touches: [{ clientX: event.clientX, clientY: event.clientY }],
                target: event.target
            });
        }
    }

    handleMouseMove(event) {
        if (event.buttons === 1) {
            this.handleTouchMove({
                touches: [{ clientX: event.clientX, clientY: event.clientY }],
                target: event.target
            });
        }
    }

    handleMouseUp(event) {
        this.handleTouchEnd({
            changedTouches: [{ clientX: event.clientX, clientY: event.clientY }],
            target: event.target,
            touches: []
        });
    }

    /**
     * 處理手勢開始（iOS Safari）
     */
    handleGestureStart(event) {
        event.preventDefault();
        const element = event.target.closest('.pinch-zoom, .rotatable');
        if (element) {
            element.classList.add('gesturing');
        }
    }

    /**
     * 處理手勢變化（iOS Safari）
     */
    handleGestureChange(event) {
        event.preventDefault();
        const element = event.target.closest('.pinch-zoom');
        if (element) {
            element.style.transform = `scale(${event.scale}) rotate(${event.rotation}deg)`;
        }
    }

    /**
     * 處理手勢結束（iOS Safari）
     */
    handleGestureEnd(event) {
        event.preventDefault();
        const element = event.target.closest('.pinch-zoom, .rotatable');
        if (element) {
            element.classList.remove('gesturing');
        }
    }

    /**
     * 公用方法：啟用手勢提示
     */
    static showGestureHint(type, element) {
        const hint = document.createElement('div');
        hint.className = `gesture-hint gesture-hint-${type}`;
        
        if (type === 'swipe') {
            hint.innerHTML = '滑動';
        } else if (type === 'tap') {
            hint.innerHTML = '';
        } else if (type === 'long-press') {
            hint.innerHTML = '長按';
        }
        
        element.appendChild(hint);
        
        setTimeout(() => {
            hint.remove();
        }, 3000);
    }

    /**
     * 公用方法：禁用手勢
     */
    static disableGestures() {
        document.body.style.touchAction = 'none';
        document.body.classList.add('gestures-disabled');
    }

    /**
     * 公用方法：啟用手勢
     */
    static enableGestures() {
        document.body.style.touchAction = '';
        document.body.classList.remove('gestures-disabled');
    }
}

// 初始化手勢處理器
document.addEventListener('DOMContentLoaded', () => {
    if (!window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
        window.gestureHandler = new GestureHandler();
    }
});

// 導出類別
export default GestureHandler;