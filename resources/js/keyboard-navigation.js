/**
 * 鍵盤導航增強功能
 * 為角色和權限管理頁面提供完整的鍵盤導航支援
 */

class KeyboardNavigation {
    constructor() {
        this.currentRowIndex = -1;
        this.isNavigating = false;
        this.shortcuts = new Map();
        this.init();
    }

    init() {
        this.setupEventListeners();
        this.registerShortcuts();
        this.showHelpOnFirstVisit();
    }

    setupEventListeners() {
        document.addEventListener('keydown', (e) => this.handleKeyDown(e));
        document.addEventListener('DOMContentLoaded', () => this.initializeNavigation());
        
        // Livewire 頁面更新後重新初始化
        document.addEventListener('livewire:navigated', () => this.initializeNavigation());
    }

    registerShortcuts() {
        // 全域快捷鍵
        this.shortcuts.set('ctrl+/', () => this.showHelp());
        this.shortcuts.set('alt+h', () => this.showHelp());
        this.shortcuts.set('escape', () => this.exitNavigation());
        
        // 導航快捷鍵
        this.shortcuts.set('ctrl+f', () => this.focusSearch());
        this.shortcuts.set('ctrl+r', () => this.resetFilters());
        this.shortcuts.set('ctrl+n', () => this.createNew());
        
        // 表格導航快捷鍵
        this.shortcuts.set('arrowdown', () => this.navigateDown());
        this.shortcuts.set('arrowup', () => this.navigateUp());
        this.shortcuts.set('home', () => this.navigateToFirst());
        this.shortcuts.set('end', () => this.navigateToLast());
        this.shortcuts.set('enter', () => this.activateCurrentRow());
        this.shortcuts.set('delete', () => this.deleteCurrentRow());
        this.shortcuts.set('space', () => this.toggleCurrentRowSelection());
        
        // 批量操作快捷鍵
        this.shortcuts.set('ctrl+a', () => this.selectAll());
        this.shortcuts.set('ctrl+shift+a', () => this.deselectAll());
    }

    handleKeyDown(e) {
        // 忽略在輸入框中的按鍵
        if (this.isInInputField(e.target)) {
            // 但允許某些全域快捷鍵
            if (e.key === 'Escape' || (e.ctrlKey && e.key === '/')) {
                this.handleShortcut(e);
            }
            return;
        }

        this.handleShortcut(e);
    }

    handleShortcut(e) {
        const key = this.getShortcutKey(e);
        const handler = this.shortcuts.get(key);
        
        if (handler) {
            e.preventDefault();
            handler();
        }
    }

    getShortcutKey(e) {
        const parts = [];
        
        if (e.ctrlKey) parts.push('ctrl');
        if (e.altKey) parts.push('alt');
        if (e.shiftKey) parts.push('shift');
        
        parts.push(e.key.toLowerCase());
        
        return parts.join('+');
    }

    isInInputField(element) {
        const inputTypes = ['INPUT', 'TEXTAREA', 'SELECT'];
        return inputTypes.includes(element.tagName) || 
               element.contentEditable === 'true' ||
               element.closest('[contenteditable="true"]');
    }

    initializeNavigation() {
        this.currentRowIndex = -1;
        this.isNavigating = false;
        this.updateNavigationHints();
    }

    getNavigableRows() {
        return document.querySelectorAll('tbody tr:not(.hidden):not([style*="display: none"])');
    }

    navigateDown() {
        const rows = this.getNavigableRows();
        if (rows.length === 0) return;

        this.isNavigating = true;
        this.currentRowIndex = Math.min(this.currentRowIndex + 1, rows.length - 1);
        this.highlightCurrentRow();
        this.announceNavigation();
    }

    navigateUp() {
        const rows = this.getNavigableRows();
        if (rows.length === 0) return;

        this.isNavigating = true;
        this.currentRowIndex = Math.max(this.currentRowIndex - 1, 0);
        this.highlightCurrentRow();
        this.announceNavigation();
    }

    navigateToFirst() {
        const rows = this.getNavigableRows();
        if (rows.length === 0) return;

        this.isNavigating = true;
        this.currentRowIndex = 0;
        this.highlightCurrentRow();
        this.announceNavigation();
    }

    navigateToLast() {
        const rows = this.getNavigableRows();
        if (rows.length === 0) return;

        this.isNavigating = true;
        this.currentRowIndex = rows.length - 1;
        this.highlightCurrentRow();
        this.announceNavigation();
    }

    highlightCurrentRow() {
        const rows = this.getNavigableRows();
        
        // 移除所有高亮
        rows.forEach(row => {
            row.classList.remove('keyboard-navigation-active');
        });

        // 高亮當前行
        if (this.currentRowIndex >= 0 && this.currentRowIndex < rows.length) {
            const currentRow = rows[this.currentRowIndex];
            currentRow.classList.add('keyboard-navigation-active');
            
            // 滾動到可見區域
            currentRow.scrollIntoView({ 
                behavior: 'smooth', 
                block: 'nearest',
                inline: 'nearest'
            });

            // 更新 ARIA 屬性
            currentRow.setAttribute('aria-selected', 'true');
            currentRow.setAttribute('tabindex', '0');
            
            // 移除其他行的 ARIA 屬性
            rows.forEach((row, index) => {
                if (index !== this.currentRowIndex) {
                    row.removeAttribute('aria-selected');
                    row.setAttribute('tabindex', '-1');
                }
            });
        }
    }

    activateCurrentRow() {
        if (!this.isNavigating || this.currentRowIndex < 0) return;

        const rows = this.getNavigableRows();
        const currentRow = rows[this.currentRowIndex];
        
        if (currentRow) {
            // 尋找編輯按鈕
            const editButton = currentRow.querySelector('button[wire\\:click*="edit"], a[href*="/edit"]');
            if (editButton) {
                editButton.click();
                return;
            }

            // 尋找檢視按鈕
            const viewButton = currentRow.querySelector('button[wire\\:click*="view"], a[href*="/show"]');
            if (viewButton) {
                viewButton.click();
                return;
            }

            // 如果沒有找到按鈕，嘗試點擊整行
            currentRow.click();
        }
    }

    deleteCurrentRow() {
        if (!this.isNavigating || this.currentRowIndex < 0) return;

        const rows = this.getNavigableRows();
        const currentRow = rows[this.currentRowIndex];
        
        if (currentRow) {
            const deleteButton = currentRow.querySelector('button[wire\\:click*="delete"]:not([disabled])');
            if (deleteButton) {
                deleteButton.click();
            } else {
                this.showToast('此項目無法刪除或您沒有刪除權限', 'warning');
            }
        }
    }

    toggleCurrentRowSelection() {
        if (!this.isNavigating || this.currentRowIndex < 0) return;

        const rows = this.getNavigableRows();
        const currentRow = rows[this.currentRowIndex];
        
        if (currentRow) {
            const checkbox = currentRow.querySelector('input[type="checkbox"]');
            if (checkbox) {
                checkbox.click();
            }
        }
    }

    selectAll() {
        const selectAllCheckbox = document.querySelector('input[wire\\:model*="selectAll"]');
        if (selectAllCheckbox && !selectAllCheckbox.checked) {
            selectAllCheckbox.click();
            this.showToast('已選擇所有項目', 'success');
        }
    }

    deselectAll() {
        const selectAllCheckbox = document.querySelector('input[wire\\:model*="selectAll"]');
        if (selectAllCheckbox && selectAllCheckbox.checked) {
            selectAllCheckbox.click();
            this.showToast('已取消選擇所有項目', 'info');
        }
    }

    focusSearch() {
        const searchInput = document.querySelector('input[wire\\:model*="search"], input[placeholder*="搜尋"], input[placeholder*="Search"]');
        if (searchInput) {
            searchInput.focus();
            searchInput.select();
        }
    }

    resetFilters() {
        // 尋找重置按鈕
        const resetButton = document.querySelector('button[wire\\:click*="resetFilters"], button[wire\\:click*="reset"]');
        if (resetButton) {
            resetButton.click();
            this.showToast('篩選條件已重置', 'success');
        }
    }

    createNew() {
        // 尋找建立按鈕
        const createButton = document.querySelector('button[wire\\:click*="create"], a[href*="/create"]');
        if (createButton) {
            createButton.click();
        }
    }

    exitNavigation() {
        this.isNavigating = false;
        this.currentRowIndex = -1;
        
        // 移除所有高亮
        const rows = this.getNavigableRows();
        rows.forEach(row => {
            row.classList.remove('keyboard-navigation-active');
            row.removeAttribute('aria-selected');
            row.setAttribute('tabindex', '-1');
        });

        this.showToast('已退出鍵盤導航模式', 'info');
    }

    announceNavigation() {
        const rows = this.getNavigableRows();
        if (this.currentRowIndex >= 0 && this.currentRowIndex < rows.length) {
            const currentRow = rows[this.currentRowIndex];
            const rowText = this.getRowDescription(currentRow);
            
            // 使用 ARIA live region 宣告
            this.announceToScreenReader(`第 ${this.currentRowIndex + 1} 行，共 ${rows.length} 行：${rowText}`);
        }
    }

    getRowDescription(row) {
        // 嘗試從行中提取描述性文字
        const nameCell = row.querySelector('td:nth-child(2), td:first-child');
        if (nameCell) {
            return nameCell.textContent.trim();
        }
        return '項目';
    }

    announceToScreenReader(message) {
        let liveRegion = document.getElementById('keyboard-nav-announcer');
        if (!liveRegion) {
            liveRegion = document.createElement('div');
            liveRegion.id = 'keyboard-nav-announcer';
            liveRegion.setAttribute('aria-live', 'polite');
            liveRegion.setAttribute('aria-atomic', 'true');
            liveRegion.className = 'sr-only';
            document.body.appendChild(liveRegion);
        }
        
        liveRegion.textContent = message;
    }

    showHelp() {
        const helpModal = this.createHelpModal();
        document.body.appendChild(helpModal);
        
        // 聚焦到模態對話框
        setTimeout(() => {
            const closeButton = helpModal.querySelector('button');
            if (closeButton) closeButton.focus();
        }, 100);
    }

    createHelpModal() {
        const modal = document.createElement('div');
        modal.className = 'fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50';
        modal.setAttribute('role', 'dialog');
        modal.setAttribute('aria-labelledby', 'help-modal-title');
        modal.setAttribute('aria-modal', 'true');
        
        modal.innerHTML = `
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-2xl w-full mx-4 max-h-[80vh] overflow-y-auto">
                <div class="p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h2 id="help-modal-title" class="text-xl font-semibold text-gray-900 dark:text-white">
                            鍵盤快捷鍵說明
                        </h2>
                        <button class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300" onclick="this.closest('.fixed').remove()">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                    
                    <div class="space-y-6">
                        <div>
                            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-3">導航快捷鍵</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-3 text-sm">
                                <div class="flex justify-between">
                                    <span class="text-gray-600 dark:text-gray-400">↑ / ↓</span>
                                    <span class="text-gray-900 dark:text-white">上下移動</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600 dark:text-gray-400">Home / End</span>
                                    <span class="text-gray-900 dark:text-white">跳到首行/末行</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600 dark:text-gray-400">Enter</span>
                                    <span class="text-gray-900 dark:text-white">編輯選中項目</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600 dark:text-gray-400">Delete</span>
                                    <span class="text-gray-900 dark:text-white">刪除選中項目</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600 dark:text-gray-400">Space</span>
                                    <span class="text-gray-900 dark:text-white">切換選擇狀態</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600 dark:text-gray-400">Esc</span>
                                    <span class="text-gray-900 dark:text-white">退出導航模式</span>
                                </div>
                            </div>
                        </div>
                        
                        <div>
                            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-3">功能快捷鍵</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-3 text-sm">
                                <div class="flex justify-between">
                                    <span class="text-gray-600 dark:text-gray-400">Ctrl + F</span>
                                    <span class="text-gray-900 dark:text-white">聚焦搜尋框</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600 dark:text-gray-400">Ctrl + R</span>
                                    <span class="text-gray-900 dark:text-white">重置篩選條件</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600 dark:text-gray-400">Ctrl + N</span>
                                    <span class="text-gray-900 dark:text-white">建立新項目</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600 dark:text-gray-400">Ctrl + A</span>
                                    <span class="text-gray-900 dark:text-white">全選</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600 dark:text-gray-400">Ctrl + Shift + A</span>
                                    <span class="text-gray-900 dark:text-white">取消全選</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600 dark:text-gray-400">Ctrl + /</span>
                                    <span class="text-gray-900 dark:text-white">顯示此說明</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mt-6 flex justify-end">
                        <button 
                            class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
                            onclick="this.closest('.fixed').remove()"
                        >
                            關閉
                        </button>
                    </div>
                </div>
            </div>
        `;
        
        // 點擊背景關閉
        modal.addEventListener('click', (e) => {
            if (e.target === modal) {
                modal.remove();
            }
        });
        
        // ESC 鍵關閉
        const handleEscape = (e) => {
            if (e.key === 'Escape') {
                modal.remove();
                document.removeEventListener('keydown', handleEscape);
            }
        };
        document.addEventListener('keydown', handleEscape);
        
        return modal;
    }

    showHelpOnFirstVisit() {
        const hasSeenHelp = localStorage.getItem('keyboard-nav-help-seen');
        if (!hasSeenHelp) {
            setTimeout(() => {
                this.showToast('按 Ctrl + / 查看鍵盤快捷鍵說明', 'info', 8000);
                localStorage.setItem('keyboard-nav-help-seen', 'true');
            }, 2000);
        }
    }

    updateNavigationHints() {
        // 在頁面上添加視覺提示
        let hintElement = document.getElementById('keyboard-nav-hint');
        if (!hintElement) {
            hintElement = document.createElement('div');
            hintElement.id = 'keyboard-nav-hint';
            hintElement.className = 'fixed bottom-4 left-4 bg-gray-800 text-white text-xs px-3 py-2 rounded-lg shadow-lg z-40 opacity-0 transition-opacity duration-300';
            hintElement.innerHTML = '按 Ctrl + / 查看鍵盤快捷鍵';
            document.body.appendChild(hintElement);
        }
        
        // 短暫顯示提示
        setTimeout(() => {
            hintElement.classList.remove('opacity-0');
            setTimeout(() => {
                hintElement.classList.add('opacity-0');
            }, 3000);
        }, 1000);
    }

    showToast(message, type = 'info', duration = 3000) {
        // 創建 toast 通知
        const toast = document.createElement('div');
        toast.className = `fixed top-4 right-4 z-50 max-w-sm w-full bg-white dark:bg-gray-800 shadow-lg rounded-lg border border-gray-200 dark:border-gray-700 transform transition-all duration-300 translate-x-full`;
        
        const typeClasses = {
            success: 'border-l-4 border-l-green-500',
            error: 'border-l-4 border-l-red-500',
            warning: 'border-l-4 border-l-yellow-500',
            info: 'border-l-4 border-l-blue-500'
        };
        
        toast.classList.add(typeClasses[type] || typeClasses.info);
        
        toast.innerHTML = `
            <div class="p-4">
                <div class="text-sm text-gray-900 dark:text-white">${message}</div>
            </div>
        `;
        
        document.body.appendChild(toast);
        
        // 動畫顯示
        setTimeout(() => {
            toast.classList.remove('translate-x-full');
        }, 100);
        
        // 自動隱藏
        setTimeout(() => {
            toast.classList.add('translate-x-full');
            setTimeout(() => {
                toast.remove();
            }, 300);
        }, duration);
    }
}

// 初始化鍵盤導航
document.addEventListener('DOMContentLoaded', () => {
    window.keyboardNavigation = new KeyboardNavigation();
});

// 添加 CSS 樣式
const style = document.createElement('style');
style.textContent = `
    .keyboard-navigation-active {
        background-color: rgba(59, 130, 246, 0.1) !important;
        border: 2px solid rgba(59, 130, 246, 0.5) !important;
        box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.2) !important;
    }
    
    .dark .keyboard-navigation-active {
        background-color: rgba(59, 130, 246, 0.2) !important;
        border-color: rgba(59, 130, 246, 0.6) !important;
    }
    
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
`;
document.head.appendChild(style);

export default KeyboardNavigation;