<div 
    x-data="keyboardShortcutManager"
    x-init="init()"
    class="keyboard-shortcut-manager"
    wire:ignore
>
    <!-- 全域鍵盤事件監聽器 -->
    <div 
        @keydown.window="handleKeyDown($event)"
        @keyup.window="handleKeyUp($event)"
        class="sr-only"
    ></div>

    <!-- 快捷鍵狀態指示器 -->
    @if($enabled)
        <div class="fixed bottom-4 right-4 z-50 opacity-75 hover:opacity-100 transition-opacity">
            <div class="bg-gray-800 text-white text-xs px-2 py-1 rounded shadow-lg">
                <span class="inline-flex items-center">
                    <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M18 3a1 1 0 00-1.447-.894L8.763 6H5a3 3 0 000 6h.28l1.771 5.316A1 1 0 008 18h1a1 1 0 001-1v-4.382l6.553 3.894A1 1 0 0018 16V3z" clip-rule="evenodd"></path>
                    </svg>
                    快捷鍵已啟用
                </span>
            </div>
        </div>
    @endif

    <script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('keyboardShortcutManager', () => ({
            enabled: @js($enabled),
            pressedKeys: new Set(),
            keySequence: [],
            sequenceTimeout: null,
            
            init() {
                // 監聽 Livewire 事件
                this.$wire.on('shortcut-manager-enabled', () => {
                    this.enabled = true;
                });
                
                this.$wire.on('shortcut-manager-disabled', () => {
                    this.enabled = false;
                });
                
                this.$wire.on('test-shortcut', (event) => {
                    this.simulateShortcut(event.key);
                });
            },
            
            handleKeyDown(event) {
                if (!this.enabled) return;
                
                // 防止在輸入框中觸發快捷鍵
                if (this.isInInputField(event.target)) {
                    return;
                }
                
                // 記錄按下的按鍵
                this.pressedKeys.add(event.code);
                
                // 建立快捷鍵事件物件
                const keyEvent = {
                    key: event.key,
                    code: event.code,
                    ctrlKey: event.ctrlKey,
                    altKey: event.altKey,
                    shiftKey: event.shiftKey,
                    metaKey: event.metaKey,
                    target: {
                        tagName: event.target.tagName,
                        type: event.target.type,
                        contentEditable: event.target.contentEditable === 'true'
                    }
                };
                
                // 傳送到 Livewire 處理
                this.$wire.handleKeyDown(keyEvent);
                
                // 清除序列超時
                if (this.sequenceTimeout) {
                    clearTimeout(this.sequenceTimeout);
                }
                
                // 設定新的序列超時
                this.sequenceTimeout = setTimeout(() => {
                    this.keySequence = [];
                }, 1000);
            },
            
            handleKeyUp(event) {
                // 移除釋放的按鍵
                this.pressedKeys.delete(event.code);
            },
            
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
            },
            
            simulateShortcut(shortcutKey) {
                // 模擬快捷鍵執行（用於測試）
                console.log('模擬執行快捷鍵:', shortcutKey);
                this.$wire.executeShortcut(shortcutKey);
            },
            
            getCurrentPressedKeys() {
                return Array.from(this.pressedKeys);
            },
            
            clearPressedKeys() {
                this.pressedKeys.clear();
            }
        }));
    });
    </script>

    <style>
    .keyboard-shortcut-manager {
        /* 確保元件不影響頁面佈局 */
        position: relative;
        z-index: 1;
    }

    /* 快捷鍵狀態指示器樣式 */
    .keyboard-shortcut-manager .fixed {
        pointer-events: none;
    }

    .keyboard-shortcut-manager .fixed:hover {
        pointer-events: auto;
    }

    /* 快捷鍵組合顯示樣式 */
    .shortcut-key {
        @apply inline-flex items-center px-2 py-1 text-xs font-mono bg-gray-100 border border-gray-300 rounded shadow-sm;
    }

    .shortcut-key.dark {
        @apply bg-gray-800 border-gray-600 text-gray-200;
    }

    .shortcut-key + .shortcut-key {
        @apply ml-1;
    }

    /* 快捷鍵分隔符 */
    .shortcut-separator {
        @apply mx-1 text-gray-400;
    }
    </style>
</div>