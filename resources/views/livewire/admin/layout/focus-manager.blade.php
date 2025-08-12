<div class="focus-manager" 
     x-data="focusManager()"
     x-init="init()"
     style="display: none;">
    <!-- 這個元件主要透過 JavaScript 運作，不需要可見的 HTML -->
    
    <script>
function focusManager() {
    return {
        currentFocus: null,
        focusHistory: [],
        trapContainer: null,
        focusableElements: 'a[href], button, input, textarea, select, details, [tabindex]:not([tabindex="-1"])',
        
        init() {
            this.setupEventListeners();
        },
        
        setupEventListeners() {
            // 監聽 Livewire 事件
            Livewire.on('set-focus', (data) => {
                this.setFocus(data[0].elementId);
            });
            
            Livewire.on('enable-focus-trap', (data) => {
                this.enableFocusTrap(data[0].containerId);
            });
            
            Livewire.on('disable-focus-trap', () => {
                this.disableFocusTrap();
            });
            
            Livewire.on('focus-first-element', (data) => {
                this.focusFirstElement(data[0].containerId);
            });
            
            Livewire.on('navigate-within-container', (data) => {
                this.navigateWithinContainer(data[0].containerId, data[0].direction);
            });
            
            Livewire.on('close-focused-element', () => {
                this.closeFocusedElement();
            });
            
            Livewire.on('arrow-key-navigation', (data) => {
                this.handleArrowKeyNavigation(data[0]);
            });
            
            Livewire.on('scroll-to-element', (data) => {
                this.scrollToElement(data[0].elementId);
            });
            
            // 監聽全域鍵盤事件
            document.addEventListener('keydown', (event) => {
                this.handleGlobalKeydown(event);
            });
            
            // 監聽焦點變更
            document.addEventListener('focusin', (event) => {
                this.handleFocusIn(event);
            });
            
            document.addEventListener('focusout', (event) => {
                this.handleFocusOut(event);
            });
        },
        
        setFocus(elementId) {
            const element = document.getElementById(elementId);
            if (element) {
                // 確保元素可以接收焦點
                if (element.tabIndex === undefined || element.tabIndex < 0) {
                    element.tabIndex = -1;
                }
                
                element.focus();
                this.currentFocus = elementId;
                
                // 滾動到元素位置
                this.scrollToElement(elementId);
            }
        },
        
        enableFocusTrap(containerId) {
            this.trapContainer = containerId;
            const container = document.getElementById(containerId);
            
            if (container) {
                // 儲存原始的 tabindex 值
                const focusableElements = container.querySelectorAll(this.focusableElements);
                focusableElements.forEach(el => {
                    if (!el.hasAttribute('data-original-tabindex')) {
                        el.setAttribute('data-original-tabindex', el.tabIndex || '0');
                    }
                });
                
                // 設定焦點陷阱
                container.addEventListener('keydown', this.handleTrapKeydown.bind(this));
            }
        },
        
        disableFocusTrap() {
            if (this.trapContainer) {
                const container = document.getElementById(this.trapContainer);
                if (container) {
                    // 恢復原始的 tabindex 值
                    const elements = container.querySelectorAll('[data-original-tabindex]');
                    elements.forEach(el => {
                        const originalTabindex = el.getAttribute('data-original-tabindex');
                        if (originalTabindex === '0') {
                            el.removeAttribute('tabindex');
                        } else {
                            el.tabIndex = parseInt(originalTabindex);
                        }
                        el.removeAttribute('data-original-tabindex');
                    });
                    
                    container.removeEventListener('keydown', this.handleTrapKeydown.bind(this));
                }
                this.trapContainer = null;
            }
        },
        
        focusFirstElement(containerId) {
            const container = document.getElementById(containerId);
            if (container) {
                const firstFocusable = container.querySelector(this.focusableElements);
                if (firstFocusable) {
                    firstFocusable.focus();
                }
            }
        },
        
        navigateWithinContainer(containerId, direction) {
            const container = document.getElementById(containerId);
            if (!container) return;
            
            const focusableElements = Array.from(container.querySelectorAll(this.focusableElements))
                .filter(el => this.isVisible(el) && !el.disabled);
            
            const currentIndex = focusableElements.indexOf(document.activeElement);
            let nextIndex;
            
            if (direction === 'next') {
                nextIndex = currentIndex + 1;
                if (nextIndex >= focusableElements.length) {
                    nextIndex = 0; // 循環到第一個
                }
            } else {
                nextIndex = currentIndex - 1;
                if (nextIndex < 0) {
                    nextIndex = focusableElements.length - 1; // 循環到最後一個
                }
            }
            
            if (focusableElements[nextIndex]) {
                focusableElements[nextIndex].focus();
            }
        },
        
        handleTrapKeydown(event) {
            if (event.key === 'Tab') {
                const container = document.getElementById(this.trapContainer);
                if (!container) return;
                
                const focusableElements = Array.from(container.querySelectorAll(this.focusableElements))
                    .filter(el => this.isVisible(el) && !el.disabled);
                
                const firstElement = focusableElements[0];
                const lastElement = focusableElements[focusableElements.length - 1];
                
                if (event.shiftKey) {
                    if (document.activeElement === firstElement) {
                        event.preventDefault();
                        lastElement.focus();
                    }
                } else {
                    if (document.activeElement === lastElement) {
                        event.preventDefault();
                        firstElement.focus();
                    }
                }
            }
        },
        
        handleGlobalKeydown(event) {
            // 發送鍵盤事件到 Livewire
            Livewire.find('{{ $this->getId() }}').call('handleKeyboardNavigation', {
                key: event.key,
                shiftKey: event.shiftKey,
                ctrlKey: event.ctrlKey,
                altKey: event.altKey,
                target: event.target.id || event.target.className
            });
        },
        
        handleFocusIn(event) {
            // 記錄焦點變更
            if (event.target.id) {
                this.currentFocus = event.target.id;
            }
        },
        
        handleFocusOut(event) {
            // 處理焦點離開事件
        },
        
        closeFocusedElement() {
            // 關閉當前聚焦的元素（如模態框、下拉選單等）
            const activeElement = document.activeElement;
            
            // 尋找可關閉的父元素
            let closableElement = activeElement.closest('[data-closable]');
            if (!closableElement) {
                closableElement = activeElement.closest('.modal, .dropdown, .popover');
            }
            
            if (closableElement) {
                // 觸發關閉事件
                const closeButton = closableElement.querySelector('[data-close], .close-button');
                if (closeButton) {
                    closeButton.click();
                }
            }
        },
        
        handleArrowKeyNavigation(data) {
            const { key, currentFocus } = data;
            const activeElement = document.activeElement;
            
            // 處理選單導航
            if (activeElement.closest('.menu, .dropdown-menu, .nav-menu')) {
                this.handleMenuNavigation(key, activeElement);
            }
            
            // 處理表格導航
            if (activeElement.closest('table')) {
                this.handleTableNavigation(key, activeElement);
            }
        },
        
        handleMenuNavigation(key, activeElement) {
            const menu = activeElement.closest('.menu, .dropdown-menu, .nav-menu');
            const menuItems = Array.from(menu.querySelectorAll('a, button, [role="menuitem"]'))
                .filter(el => this.isVisible(el) && !el.disabled);
            
            const currentIndex = menuItems.indexOf(activeElement);
            let nextIndex;
            
            switch (key) {
                case 'ArrowDown':
                    nextIndex = (currentIndex + 1) % menuItems.length;
                    break;
                case 'ArrowUp':
                    nextIndex = currentIndex - 1;
                    if (nextIndex < 0) nextIndex = menuItems.length - 1;
                    break;
                case 'Home':
                    nextIndex = 0;
                    break;
                case 'End':
                    nextIndex = menuItems.length - 1;
                    break;
                case 'ArrowRight':
                    // 展開子選單
                    const submenu = activeElement.nextElementSibling;
                    if (submenu && submenu.classList.contains('submenu')) {
                        submenu.style.display = 'block';
                        const firstSubmenuItem = submenu.querySelector('a, button, [role="menuitem"]');
                        if (firstSubmenuItem) firstSubmenuItem.focus();
                    }
                    return;
                case 'ArrowLeft':
                    // 收合子選單或回到父選單
                    const parentMenu = activeElement.closest('.submenu');
                    if (parentMenu) {
                        parentMenu.style.display = 'none';
                        const parentItem = parentMenu.previousElementSibling;
                        if (parentItem) parentItem.focus();
                    }
                    return;
                default:
                    return;
            }
            
            if (menuItems[nextIndex]) {
                menuItems[nextIndex].focus();
            }
        },
        
        handleTableNavigation(key, activeElement) {
            const table = activeElement.closest('table');
            const cells = Array.from(table.querySelectorAll('td, th'))
                .filter(el => this.isVisible(el));
            
            const currentIndex = cells.indexOf(activeElement.closest('td, th'));
            const row = activeElement.closest('tr');
            const rowCells = Array.from(row.querySelectorAll('td, th'));
            const cellIndex = rowCells.indexOf(activeElement.closest('td, th'));
            
            let nextCell;
            
            switch (key) {
                case 'ArrowRight':
                    nextCell = cells[currentIndex + 1];
                    break;
                case 'ArrowLeft':
                    nextCell = cells[currentIndex - 1];
                    break;
                case 'ArrowDown':
                    const nextRow = row.nextElementSibling;
                    if (nextRow) {
                        const nextRowCells = Array.from(nextRow.querySelectorAll('td, th'));
                        nextCell = nextRowCells[cellIndex];
                    }
                    break;
                case 'ArrowUp':
                    const prevRow = row.previousElementSibling;
                    if (prevRow) {
                        const prevRowCells = Array.from(prevRow.querySelectorAll('td, th'));
                        nextCell = prevRowCells[cellIndex];
                    }
                    break;
            }
            
            if (nextCell) {
                const focusableInCell = nextCell.querySelector(this.focusableElements);
                if (focusableInCell) {
                    focusableInCell.focus();
                } else {
                    nextCell.tabIndex = -1;
                    nextCell.focus();
                }
            }
        },
        
        scrollToElement(elementId) {
            const element = document.getElementById(elementId);
            if (element) {
                element.scrollIntoView({
                    behavior: 'smooth',
                    block: 'center',
                    inline: 'nearest'
                });
            }
        },
        
        isVisible(element) {
            return element.offsetWidth > 0 && 
                   element.offsetHeight > 0 && 
                   getComputedStyle(element).visibility !== 'hidden' &&
                   getComputedStyle(element).display !== 'none';
        }
    };
}

    }
}

// 初始化焦點管理器
document.addEventListener('DOMContentLoaded', () => {
    // 全域焦點管理器實例
    if (!window.focusManager) {
        window.focusManager = focusManager();
        window.focusManager.init();
    }
});
    </script>
</div>