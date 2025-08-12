/**
 * 鍵盤快捷鍵輔助函數
 * 提供快捷鍵相關的 JavaScript 功能
 */

class KeyboardShortcutHelper {
    constructor() {
        this.pressedKeys = new Set();
        this.keySequence = [];
        this.sequenceTimeout = null;
        this.listeners = new Map();
        this.enabled = true;
        
        this.init();
    }
    
    /**
     * 初始化
     */
    init() {
        // 綁定全域鍵盤事件
        document.addEventListener('keydown', this.handleKeyDown.bind(this));
        document.addEventListener('keyup', this.handleKeyUp.bind(this));
        
        // 防止頁面離開時的快捷鍵觸發
        window.addEventListener('beforeunload', () => {
            this.enabled = false;
        });
        
        // 監聽 Livewire 事件
        document.addEventListener('livewire:init', () => {
            this.setupLivewireListeners();
        });
    }
    
    /**
     * 設定 Livewire 事件監聽器
     */
    setupLivewireListeners() {
        // 監聽快捷鍵執行事件
        Livewire.on('shortcut-executed', (event) => {
            this.handleShortcutExecuted(event);
        });
        
        // 監聽導航事件
        Livewire.on('navigate-external', (event) => {
            window.open(event.url, '_blank');
        });
        
        // 監聽複製到剪貼簿事件
        Livewire.on('copy-to-clipboard', (event) => {
            this.copyToClipboard(event.text, event.message);
        });
        
        // 監聽下載檔案事件
        Livewire.on('download-file', (event) => {
            this.downloadFile(event.filename, event.content, event.type);
        });
        
        // 監聽確認登出事件
        Livewire.on('confirm-logout', () => {
            this.confirmLogout();
        });
    }
    
    /**
     * 處理鍵盤按下事件
     */
    handleKeyDown(event) {
        if (!this.enabled) return;
        
        // 記錄按下的按鍵
        this.pressedKeys.add(event.code);
        
        // 檢查是否在輸入框中
        if (this.isInInputField(event.target)) {
            return;
        }
        
        // 建立快捷鍵字串
        const shortcutKey = this.buildShortcutKey(event);
        
        if (shortcutKey) {
            // 觸發自訂監聽器
            this.triggerListeners(shortcutKey, event);
            
            // 記錄到序列中
            this.addToSequence(shortcutKey);
        }
    }
    
    /**
     * 處理鍵盤釋放事件
     */
    handleKeyUp(event) {
        this.pressedKeys.delete(event.code);
    }
    
    /**
     * 建立快捷鍵字串
     */
    buildShortcutKey(event) {
        const modifiers = [];
        const key = event.key.toLowerCase();
        
        // 檢查修飾鍵
        if (event.ctrlKey) modifiers.push('ctrl');
        if (event.altKey) modifiers.push('alt');
        if (event.shiftKey) modifiers.push('shift');
        if (event.metaKey) modifiers.push('meta');
        
        // 處理特殊鍵
        const specialKeys = {
            'Escape': 'escape',
            'Enter': 'enter',
            'Tab': 'tab',
            ' ': 'space',
            'ArrowUp': 'up',
            'ArrowDown': 'down',
            'ArrowLeft': 'left',
            'ArrowRight': 'right',
        };
        
        const finalKey = specialKeys[event.key] || key;
        
        // 忽略單獨的修飾鍵
        if (['control', 'alt', 'shift', 'meta'].includes(finalKey)) {
            return null;
        }
        
        // 建立快捷鍵字串
        if (modifiers.length === 0 && !['escape', 'enter', 'tab', 'space'].includes(finalKey)) {
            return null;
        }
        
        return [...modifiers, finalKey].join('+');
    }
    
    /**
     * 檢查是否在輸入框中
     */
    isInInputField(target) {
        const tagName = target.tagName.toLowerCase();
        const type = target.type ? target.type.toLowerCase() : '';
        
        // 檢查是否為輸入元素
        if (['input', 'textarea', 'select'].includes(tagName)) {
            return true;
        }
        
        // 檢查是否為可編輯元素
        if (target.contentEditable === 'true') {
            return true;
        }
        
        // 檢查特定的輸入類型
        const inputTypes = ['text', 'password', 'email', 'search', 'url', 'tel', 'number'];
        if (tagName === 'input' && inputTypes.includes(type)) {
            return true;
        }
        
        return false;
    }
    
    /**
     * 新增快捷鍵監聽器
     */
    addListener(shortcutKey, callback) {
        if (!this.listeners.has(shortcutKey)) {
            this.listeners.set(shortcutKey, []);
        }
        this.listeners.get(shortcutKey).push(callback);
    }
    
    /**
     * 移除快捷鍵監聽器
     */
    removeListener(shortcutKey, callback) {
        if (this.listeners.has(shortcutKey)) {
            const callbacks = this.listeners.get(shortcutKey);
            const index = callbacks.indexOf(callback);
            if (index > -1) {
                callbacks.splice(index, 1);
            }
            if (callbacks.length === 0) {
                this.listeners.delete(shortcutKey);
            }
        }
    }
    
    /**
     * 觸發監聽器
     */
    triggerListeners(shortcutKey, event) {
        if (this.listeners.has(shortcutKey)) {
            this.listeners.get(shortcutKey).forEach(callback => {
                try {
                    callback(event, shortcutKey);
                } catch (error) {
                    console.error('快捷鍵監聽器執行錯誤:', error);
                }
            });
        }
    }
    
    /**
     * 新增到按鍵序列
     */
    addToSequence(shortcutKey) {
        this.keySequence.push({
            key: shortcutKey,
            timestamp: Date.now()
        });
        
        // 限制序列長度
        if (this.keySequence.length > 10) {
            this.keySequence.shift();
        }
        
        // 清除序列超時
        if (this.sequenceTimeout) {
            clearTimeout(this.sequenceTimeout);
        }
        
        // 設定新的序列超時
        this.sequenceTimeout = setTimeout(() => {
            this.keySequence = [];
        }, 2000);
    }
    
    /**
     * 處理快捷鍵執行事件
     */
    handleShortcutExecuted(event) {
        // 顯示快捷鍵執行提示
        this.showShortcutFeedback(event.key, event.shortcut);
        
        // 記錄到控制台（開發模式）
        if (window.APP_DEBUG) {
            console.log('快捷鍵執行:', event.key, event.shortcut);
        }
    }
    
    /**
     * 顯示快捷鍵執行回饋
     */
    showShortcutFeedback(key, shortcut) {
        // 建立回饋元素
        const feedback = document.createElement('div');
        feedback.className = 'fixed top-4 right-4 z-50 bg-black bg-opacity-75 text-white px-3 py-2 rounded-lg text-sm font-medium';
        feedback.innerHTML = `
            <div class="flex items-center">
                <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M18 3a1 1 0 00-1.447-.894L8.763 6H5a3 3 0 000 6h.28l1.771 5.316A1 1 0 008 18h1a1 1 0 001-1v-4.382l6.553 3.894A1 1 0 0018 16V3z" clip-rule="evenodd"></path>
                </svg>
                <span>${this.formatShortcutKey(key)}</span>
            </div>
        `;
        
        document.body.appendChild(feedback);
        
        // 動畫效果
        feedback.style.opacity = '0';
        feedback.style.transform = 'translateY(-10px)';
        
        requestAnimationFrame(() => {
            feedback.style.transition = 'all 0.3s ease';
            feedback.style.opacity = '1';
            feedback.style.transform = 'translateY(0)';
        });
        
        // 自動移除
        setTimeout(() => {
            feedback.style.opacity = '0';
            feedback.style.transform = 'translateY(-10px)';
            setTimeout(() => {
                if (feedback.parentNode) {
                    feedback.parentNode.removeChild(feedback);
                }
            }, 300);
        }, 2000);
    }
    
    /**
     * 格式化快捷鍵顯示
     */
    formatShortcutKey(key) {
        return key.split('+').map(part => {
            switch (part.toLowerCase()) {
                case 'ctrl': return 'Ctrl';
                case 'alt': return 'Alt';
                case 'shift': return 'Shift';
                case 'meta': return 'Cmd';
                case 'escape': return 'Esc';
                case 'enter': return 'Enter';
                case 'space': return 'Space';
                case 'tab': return 'Tab';
                default: return part.toUpperCase();
            }
        }).join(' + ');
    }
    
    /**
     * 複製到剪貼簿
     */
    async copyToClipboard(text, message) {
        try {
            await navigator.clipboard.writeText(text);
            if (message) {
                this.showToast('success', message);
            }
        } catch (error) {
            console.error('複製失敗:', error);
            this.showToast('error', '複製失敗');
        }
    }
    
    /**
     * 下載檔案
     */
    downloadFile(filename, content, type = 'text/plain') {
        const blob = new Blob([content], { type });
        const url = URL.createObjectURL(blob);
        
        const a = document.createElement('a');
        a.href = url;
        a.download = filename;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        
        URL.revokeObjectURL(url);
    }
    
    /**
     * 確認登出
     */
    confirmLogout() {
        if (confirm('確定要登出嗎？')) {
            // 觸發登出
            window.location.href = '/admin/logout';
        }
    }
    
    /**
     * 顯示提示訊息
     */
    showToast(type, message) {
        // 這裡可以整合現有的提示系統
        // 暫時使用簡單的 alert
        if (type === 'error') {
            alert('錯誤: ' + message);
        } else {
            console.log(type + ': ' + message);
        }
    }
    
    /**
     * 啟用快捷鍵
     */
    enable() {
        this.enabled = true;
    }
    
    /**
     * 停用快捷鍵
     */
    disable() {
        this.enabled = false;
    }
    
    /**
     * 切換快捷鍵狀態
     */
    toggle() {
        this.enabled = !this.enabled;
        return this.enabled;
    }
    
    /**
     * 獲取當前按下的按鍵
     */
    getPressedKeys() {
        return Array.from(this.pressedKeys);
    }
    
    /**
     * 獲取按鍵序列
     */
    getKeySequence() {
        return [...this.keySequence];
    }
    
    /**
     * 清除按鍵序列
     */
    clearKeySequence() {
        this.keySequence = [];
        if (this.sequenceTimeout) {
            clearTimeout(this.sequenceTimeout);
            this.sequenceTimeout = null;
        }
    }
}

// 建立全域實例
window.KeyboardShortcutHelper = new KeyboardShortcutHelper();

// 匯出供其他模組使用
export default KeyboardShortcutHelper;