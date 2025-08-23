@props([
    'setting' => null,
    'previewValue' => null,
    'previewType' => 'default'
])

<div 
    id="settings-preview-panel" 
    class="fixed inset-y-0 right-0 w-96 bg-white dark:bg-gray-800 shadow-2xl transform translate-x-full transition-transform duration-300 ease-in-out z-50 hidden"
    x-data="{ 
        isOpen: false,
        currentSetting: null,
        previewValue: null,
        previewType: 'default'
    }"
    x-show="isOpen"
    x-transition:enter="transition ease-out duration-300"
    x-transition:enter-start="transform translate-x-full"
    x-transition:enter-end="transform translate-x-0"
    x-transition:leave="transition ease-in duration-300"
    x-transition:leave-start="transform translate-x-0"
    x-transition:leave-end="transform translate-x-full"
>
    <div class="h-full flex flex-col">
        <!-- 預覽面板標頭 -->
        <div class="flex items-center justify-between p-6 border-b border-gray-200 dark:border-gray-700 bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/20">
            <div class="flex items-center gap-3">
                <div class="p-2 bg-blue-100 dark:bg-blue-900/30 rounded-lg">
                    <x-heroicon-o-eye class="w-5 h-5 text-blue-600 dark:text-blue-400" />
                </div>
                <div>
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                        設定預覽
                    </h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400" x-text="currentSetting?.description || '預覽設定變更效果'">
                        預覽設定變更效果
                    </p>
                </div>
            </div>
            <button 
                @click="closePreview()"
                class="btn btn-ghost btn-sm"
                title="關閉預覽"
            >
                <x-heroicon-o-x-mark class="w-5 h-5" />
            </button>
        </div>
        
        <!-- 設定資訊 -->
        <div class="p-4 bg-gray-50 dark:bg-gray-700/50 border-b border-gray-200 dark:border-gray-700" x-show="currentSetting">
            <div class="space-y-2">
                <div class="flex items-center justify-between">
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">設定鍵值</span>
                    <code class="text-xs bg-gray-200 dark:bg-gray-600 px-2 py-1 rounded" x-text="currentSetting?.key">
                    </code>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">設定類型</span>
                    <span class="text-xs capitalize bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-300 px-2 py-1 rounded" x-text="currentSetting?.type">
                    </span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">預覽模式</span>
                    <span class="text-xs capitalize bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-300 px-2 py-1 rounded" x-text="previewType">
                    </span>
                </div>
            </div>
        </div>
        
        <!-- 預覽內容區域 -->
        <div class="flex-1 overflow-y-auto">
            <!-- 主題預覽 -->
            <div x-show="previewType === 'theme'" class="p-6">
                <h4 class="text-sm font-semibold text-gray-900 dark:text-white mb-4">主題預覽</h4>
                <div class="space-y-4">
                    <!-- 主題色彩預覽 -->
                    <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4">
                        <div class="flex items-center gap-3 mb-3">
                            <div class="w-4 h-4 rounded-full" :style="`background-color: ${previewValue}`"></div>
                            <span class="text-sm font-medium">主要顏色</span>
                        </div>
                        <div class="grid grid-cols-3 gap-2">
                            <div class="h-8 rounded" :style="`background-color: ${previewValue}`"></div>
                            <div class="h-8 rounded opacity-75" :style="`background-color: ${previewValue}`"></div>
                            <div class="h-8 rounded opacity-50" :style="`background-color: ${previewValue}`"></div>
                        </div>
                    </div>
                    
                    <!-- 按鈕預覽 -->
                    <div class="space-y-2">
                        <h5 class="text-xs font-medium text-gray-700 dark:text-gray-300">按鈕樣式</h5>
                        <div class="flex gap-2">
                            <button class="btn btn-sm" :style="`background-color: ${previewValue}; border-color: ${previewValue}; color: white`">
                                主要按鈕
                            </button>
                            <button class="btn btn-outline btn-sm" :style="`border-color: ${previewValue}; color: ${previewValue}`">
                                次要按鈕
                            </button>
                        </div>
                    </div>
                    
                    <!-- 連結預覽 -->
                    <div class="space-y-2">
                        <h5 class="text-xs font-medium text-gray-700 dark:text-gray-300">連結樣式</h5>
                        <div class="space-y-1">
                            <a href="#" class="block text-sm hover:underline" :style="`color: ${previewValue}`">
                                一般連結樣式
                            </a>
                            <a href="#" class="block text-sm font-medium hover:underline" :style="`color: ${previewValue}`">
                                重要連結樣式
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- 外觀預覽 -->
            <div x-show="previewType === 'appearance'" class="p-6">
                <h4 class="text-sm font-semibold text-gray-900 dark:text-white mb-4">外觀預覽</h4>
                <div class="space-y-4">
                    <!-- 模擬頁面預覽 -->
                    <div class="bg-gray-100 dark:bg-gray-700 rounded-lg p-4 border-2 border-dashed border-gray-300 dark:border-gray-600">
                        <div class="text-center text-gray-500 dark:text-gray-400">
                            <x-heroicon-o-photo class="w-12 h-12 mx-auto mb-2" />
                            <p class="text-sm">外觀預覽區域</p>
                            <p class="text-xs">這裡將顯示設定變更後的外觀效果</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- 文字預覽 -->
            <div x-show="previewType === 'text'" class="p-6">
                <h4 class="text-sm font-semibold text-gray-900 dark:text-white mb-4">文字預覽</h4>
                <div class="space-y-4">
                    <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                        <div class="text-sm text-gray-600 dark:text-gray-400 mb-2">預覽值：</div>
                        <div class="font-mono text-gray-900 dark:text-white" x-text="previewValue">
                        </div>
                    </div>
                    
                    <!-- 格式化預覽 -->
                    <div class="space-y-2">
                        <h5 class="text-xs font-medium text-gray-700 dark:text-gray-300">格式化顯示</h5>
                        <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-3">
                            <div class="text-sm" x-html="formatPreviewValue(previewValue)">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- 數值預覽 -->
            <div x-show="previewType === 'number'" class="p-6">
                <h4 class="text-sm font-semibold text-gray-900 dark:text-white mb-4">數值預覽</h4>
                <div class="space-y-4">
                    <!-- 數值顯示 -->
                    <div class="bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/20 rounded-lg p-4">
                        <div class="text-center">
                            <div class="text-3xl font-bold text-blue-600 dark:text-blue-400" x-text="previewValue">
                            </div>
                            <div class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                                設定值
                            </div>
                        </div>
                    </div>
                    
                    <!-- 數值範圍指示 -->
                    <div class="space-y-2" x-show="currentSetting?.validation_rules">
                        <h5 class="text-xs font-medium text-gray-700 dark:text-gray-300">有效範圍</h5>
                        <div class="bg-gray-100 dark:bg-gray-700 rounded-lg p-3">
                            <div class="text-xs text-gray-600 dark:text-gray-400" x-text="currentSetting?.validation_rules">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- 布林值預覽 -->
            <div x-show="previewType === 'boolean'" class="p-6">
                <h4 class="text-sm font-semibold text-gray-900 dark:text-white mb-4">開關預覽</h4>
                <div class="space-y-4">
                    <div class="flex items-center justify-center p-8 bg-gray-50 dark:bg-gray-700 rounded-lg">
                        <div class="text-center">
                            <div class="toggle toggle-lg mb-4" :class="previewValue ? 'toggle-success' : ''">
                                <input type="checkbox" :checked="previewValue" disabled />
                            </div>
                            <div class="text-lg font-semibold" :class="previewValue ? 'text-green-600 dark:text-green-400' : 'text-gray-500 dark:text-gray-400'">
                                <span x-text="previewValue ? '啟用' : '停用'"></span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- 影響說明 -->
                    <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-4">
                        <div class="flex items-start gap-3">
                            <x-heroicon-o-information-circle class="w-5 h-5 text-blue-600 dark:text-blue-400 flex-shrink-0 mt-0.5" />
                            <div class="text-sm text-blue-800 dark:text-blue-300">
                                <div class="font-medium mb-1">設定影響</div>
                                <div x-text="previewValue ? '此功能將被啟用' : '此功能將被停用'"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- 預設預覽 -->
            <div x-show="previewType === 'default'" class="p-6">
                <div class="text-center py-12">
                    <x-heroicon-o-eye-slash class="w-16 h-16 mx-auto text-gray-300 dark:text-gray-600 mb-4" />
                    <h4 class="text-lg font-medium text-gray-900 dark:text-white mb-2">
                        無可用預覽
                    </h4>
                    <p class="text-gray-600 dark:text-gray-400">
                        此設定類型暫不支援即時預覽功能
                    </p>
                </div>
            </div>
        </div>
        
        <!-- 預覽面板操作 -->
        <div class="p-6 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700/50">
            <!-- 預覽控制 -->
            <div class="mb-4">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">預覽控制</span>
                    <button 
                        @click="refreshPreview()"
                        class="btn btn-ghost btn-xs"
                        title="重新整理預覽"
                    >
                        <x-heroicon-o-arrow-path class="w-4 h-4" />
                    </button>
                </div>
                <div class="flex gap-2">
                    <button 
                        @click="togglePreviewMode()"
                        class="btn btn-outline btn-sm flex-1"
                    >
                        <x-heroicon-o-arrows-right-left class="w-4 h-4" />
                        切換模式
                    </button>
                    <button 
                        @click="resetPreview()"
                        class="btn btn-ghost btn-sm flex-1"
                    >
                        <x-heroicon-o-arrow-uturn-left class="w-4 h-4" />
                        重設預覽
                    </button>
                </div>
            </div>
            
            <!-- 主要操作按鈕 -->
            <div class="flex gap-3">
                <button 
                    @click="applyPreview()"
                    class="btn btn-primary flex-1"
                    :disabled="!previewValue"
                >
                    <x-heroicon-o-check class="w-4 h-4" />
                    套用變更
                </button>
                <button 
                    @click="closePreview()"
                    class="btn btn-ghost flex-1"
                >
                    <x-heroicon-o-x-mark class="w-4 h-4" />
                    取消
                </button>
            </div>
            
            <!-- 預覽提示 -->
            <div class="mt-4 p-3 bg-yellow-50 dark:bg-yellow-900/20 rounded-lg">
                <div class="flex items-start gap-2">
                    <x-heroicon-o-exclamation-triangle class="w-4 h-4 text-yellow-600 dark:text-yellow-400 flex-shrink-0 mt-0.5" />
                    <div class="text-xs text-yellow-800 dark:text-yellow-300">
                        <div class="font-medium mb-1">預覽提示</div>
                        <div>預覽僅供參考，實際效果可能因環境而異。套用前請確認變更內容。</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
// 預覽面板控制
function initPreviewPanel() {
    return {
        isOpen: false,
        currentSetting: null,
        previewValue: null,
        previewType: 'default',
        
        // 開啟預覽
        openPreview(setting, value, type = 'default') {
            this.currentSetting = setting;
            this.previewValue = value;
            this.previewType = type;
            this.isOpen = true;
        },
        
        // 關閉預覽
        closePreview() {
            this.isOpen = false;
            setTimeout(() => {
                this.currentSetting = null;
                this.previewValue = null;
                this.previewType = 'default';
            }, 300);
        },
        
        // 套用預覽
        applyPreview() {
            if (this.currentSetting && this.previewValue !== null) {
                Livewire.dispatch('apply-setting-preview', {
                    key: this.currentSetting.key,
                    value: this.previewValue
                });
                this.closePreview();
            }
        },
        
        // 重設預覽
        resetPreview() {
            if (this.currentSetting) {
                this.previewValue = this.currentSetting.original_value || this.currentSetting.default_value;
            }
        },
        
        // 重新整理預覽
        refreshPreview() {
            // 觸發預覽重新整理
            Livewire.dispatch('refresh-setting-preview', {
                key: this.currentSetting?.key,
                value: this.previewValue
            });
        },
        
        // 切換預覽模式
        togglePreviewMode() {
            const modes = ['default', 'theme', 'appearance', 'text', 'number', 'boolean'];
            const currentIndex = modes.indexOf(this.previewType);
            const nextIndex = (currentIndex + 1) % modes.length;
            this.previewType = modes[nextIndex];
        },
        
        // 格式化預覽值
        formatPreviewValue(value) {
            if (typeof value === 'string') {
                // 處理 HTML 標籤
                return value.replace(/</g, '&lt;').replace(/>/g, '&gt;');
            } else if (typeof value === 'object') {
                return JSON.stringify(value, null, 2);
            }
            return String(value);
        }
    };
}

// 監聽 Livewire 預覽事件
document.addEventListener('DOMContentLoaded', function() {
    // 監聽開啟預覽事件
    Livewire.on('show-setting-preview', (data) => {
        const panel = Alpine.$data(document.getElementById('settings-preview-panel'));
        if (panel) {
            panel.openPreview(data.setting, data.value, data.type || 'default');
        }
    });
    
    // 監聽關閉預覽事件
    Livewire.on('close-setting-preview', () => {
        const panel = Alpine.$data(document.getElementById('settings-preview-panel'));
        if (panel) {
            panel.closePreview();
        }
    });
    
    // 監聽預覽更新事件
    Livewire.on('update-setting-preview', (data) => {
        const panel = Alpine.$data(document.getElementById('settings-preview-panel'));
        if (panel) {
            panel.previewValue = data.value;
            panel.previewType = data.type || panel.previewType;
        }
    });
});

// 鍵盤快捷鍵
document.addEventListener('keydown', function(e) {
    // Escape: 關閉預覽面板
    if (e.key === 'Escape') {
        const panel = Alpine.$data(document.getElementById('settings-preview-panel'));
        if (panel && panel.isOpen) {
            panel.closePreview();
        }
    }
    
    // Enter: 套用預覽（當預覽面板開啟時）
    if (e.key === 'Enter' && e.ctrlKey) {
        const panel = Alpine.$data(document.getElementById('settings-preview-panel'));
        if (panel && panel.isOpen) {
            panel.applyPreview();
        }
    }
});
</script>
@endpush