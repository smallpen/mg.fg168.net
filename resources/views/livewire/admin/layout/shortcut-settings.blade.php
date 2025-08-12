<div>
    <!-- 對話框遮罩 -->
    <div 
        x-show="$wire.isOpen"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="fixed inset-0 bg-black bg-opacity-50 z-50"
        @click="$wire.close()"
        style="display: none;"
    ></div>

    <!-- 對話框內容 -->
    <div 
        x-show="$wire.isOpen"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0 transform scale-95"
        x-transition:enter-end="opacity-100 transform scale-100"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100 transform scale-100"
        x-transition:leave-end="opacity-0 transform scale-95"
        class="fixed inset-0 z-50 overflow-y-auto"
        style="display: none;"
    >
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
            <div class="inline-block w-full max-w-6xl p-6 my-8 overflow-hidden text-left align-middle transition-all transform bg-white shadow-xl rounded-lg">
                <!-- 對話框標題 -->
                <div class="flex items-center justify-between mb-6">
                    <div class="flex items-center">
                        <div class="flex items-center justify-center w-10 h-10 bg-purple-100 rounded-lg mr-3">
                            <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 100 4m0-4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 100 4m0-4v2m0-6V4"></path>
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-lg font-medium text-gray-900">快捷鍵設定</h3>
                            <p class="text-sm text-gray-500">自訂您的鍵盤快捷鍵配置</p>
                        </div>
                    </div>
                    <button 
                        @click="$wire.close()"
                        class="text-gray-400 hover:text-gray-600 transition-colors"
                    >
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <!-- 左側：快捷鍵列表 -->
                    <div class="lg:col-span-2">
                        <div class="flex items-center justify-between mb-4">
                            <h4 class="text-md font-medium text-gray-900">目前快捷鍵</h4>
                            <div class="flex space-x-2">
                                <button 
                                    wire:click="createShortcut"
                                    class="px-3 py-1 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 transition-colors"
                                >
                                    新增快捷鍵
                                </button>
                                <button 
                                    wire:click="resetToDefaults"
                                    wire:confirm="確定要重置為預設設定嗎？這將刪除所有自訂快捷鍵。"
                                    class="px-3 py-1 text-sm font-medium text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 transition-colors"
                                >
                                    重置預設
                                </button>
                            </div>
                        </div>

                        <div class="space-y-2 max-h-96 overflow-y-auto">
                            @forelse($this->shortcuts as $key => $shortcut)
                                <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors">
                                    <div class="flex-1">
                                        <div class="flex items-center mb-1">
                                            <!-- 快捷鍵組合 -->
                                            <div class="flex items-center space-x-1 mr-3">
                                                @foreach(explode('+', $key) as $keyPart)
                                                    <kbd class="px-2 py-1 text-xs font-mono bg-white border border-gray-300 rounded shadow-sm">
                                                        {{ strtoupper(trim($keyPart)) }}
                                                    </kbd>
                                                    @if(!$loop->last)
                                                        <span class="text-gray-400">+</span>
                                                    @endif
                                                @endforeach
                                            </div>
                                            
                                            <!-- 分類標籤 -->
                                            <span class="px-2 py-1 text-xs bg-{{ $shortcut['category'] === 'navigation' ? 'blue' : ($shortcut['category'] === 'function' ? 'green' : ($shortcut['category'] === 'system' ? 'red' : 'gray')) }}-100 text-{{ $shortcut['category'] === 'navigation' ? 'blue' : ($shortcut['category'] === 'function' ? 'green' : ($shortcut['category'] === 'system' ? 'red' : 'gray')) }}-800 rounded">
                                                {{ $this->categories[$shortcut['category']] ?? '自訂' }}
                                            </span>
                                        </div>
                                        <p class="text-sm text-gray-600">{{ $shortcut['description'] ?? '無說明' }}</p>
                                        @if($shortcut['action'] === 'navigate' && $shortcut['target'])
                                            <p class="text-xs text-gray-500 mt-1">目標：{{ $shortcut['target'] }}</p>
                                        @endif
                                    </div>
                                    
                                    <div class="flex items-center space-x-2 ml-4">
                                        <!-- 啟用/停用切換 -->
                                        <label class="inline-flex items-center">
                                            <input 
                                                type="checkbox" 
                                                {{ ($shortcut['enabled'] ?? true) ? 'checked' : '' }}
                                                wire:change="updateShortcut('{{ $key }}', {{ json_encode(array_merge($shortcut, ['enabled' => !($shortcut['enabled'] ?? true)])) }})"
                                                class="form-checkbox h-4 w-4 text-blue-600 transition duration-150 ease-in-out"
                                            >
                                        </label>
                                        
                                        <!-- 編輯按鈕 -->
                                        <button 
                                            wire:click="editShortcut('{{ $key }}')"
                                            class="p-1 text-gray-400 hover:text-blue-600 transition-colors"
                                            title="編輯"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                            </svg>
                                        </button>
                                        
                                        <!-- 刪除按鈕 -->
                                        <button 
                                            wire:click="deleteShortcut('{{ $key }}')"
                                            wire:confirm="確定要刪除此快捷鍵嗎？"
                                            class="p-1 text-gray-400 hover:text-red-600 transition-colors"
                                            title="刪除"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                            @empty
                                <div class="text-center py-8">
                                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9l3 3-3 3m5 0h3M5 20h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                    </svg>
                                    <h3 class="mt-2 text-sm font-medium text-gray-900">沒有快捷鍵</h3>
                                    <p class="mt-1 text-sm text-gray-500">點擊「新增快捷鍵」來建立您的第一個快捷鍵</p>
                                </div>
                            @endforelse
                        </div>
                    </div>

                    <!-- 右側：編輯表單 -->
                    <div class="lg:col-span-1">
                        <div class="bg-gray-50 rounded-lg p-4">
                            <h4 class="text-md font-medium text-gray-900 mb-4">
                                {{ $editingKey ? '編輯快捷鍵' : '新增快捷鍵' }}
                            </h4>

                            <form wire:submit="save" class="space-y-4">
                                <!-- 快捷鍵輸入 -->
                                <div>
                                    <label for="shortcutKey" class="block text-sm font-medium text-gray-700 mb-1">
                                        快捷鍵組合 <span class="text-red-500">*</span>
                                    </label>
                                    <input 
                                        type="text" 
                                        id="shortcutKey"
                                        wire:model.blur="shortcutKey"
                                        placeholder="例如：ctrl+shift+n"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('shortcutKey') border-red-500 @enderror"
                                    >
                                    @error('shortcutKey')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                    <p class="mt-1 text-xs text-gray-500">
                                        使用 + 連接按鍵，例如：ctrl+shift+k
                                    </p>
                                </div>

                                <!-- 說明 -->
                                <div>
                                    <label for="description" class="block text-sm font-medium text-gray-700 mb-1">
                                        說明 <span class="text-red-500">*</span>
                                    </label>
                                    <input 
                                        type="text" 
                                        id="description"
                                        wire:model="description"
                                        placeholder="快捷鍵的功能說明"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('description') border-red-500 @enderror"
                                    >
                                    @error('description')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                <!-- 動作類型 -->
                                <div>
                                    <label for="action" class="block text-sm font-medium text-gray-700 mb-1">
                                        動作類型 <span class="text-red-500">*</span>
                                    </label>
                                    <select 
                                        id="action"
                                        wire:model="action"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('action') border-red-500 @enderror"
                                    >
                                        <option value="">請選擇動作類型</option>
                                        @foreach($this->availableActions as $value => $label)
                                            <option value="{{ $value }}">{{ $label }}</option>
                                        @endforeach
                                    </select>
                                    @error('action')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                <!-- 目標（當動作為導航時顯示） -->
                                @if($action === 'navigate' || $action === 'custom')
                                    <div>
                                        <label for="target" class="block text-sm font-medium text-gray-700 mb-1">
                                            {{ $action === 'navigate' ? '目標網址' : '自訂參數' }}
                                        </label>
                                        <input 
                                            type="text" 
                                            id="target"
                                            wire:model="target"
                                            placeholder="{{ $action === 'navigate' ? '/admin/users' : '自訂參數值' }}"
                                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('target') border-red-500 @enderror"
                                        >
                                        @error('target')
                                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                        @enderror
                                    </div>
                                @endif

                                <!-- 分類 -->
                                <div>
                                    <label for="category" class="block text-sm font-medium text-gray-700 mb-1">
                                        分類
                                    </label>
                                    <select 
                                        id="category"
                                        wire:model="category"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                    >
                                        @foreach($this->categories as $value => $label)
                                            <option value="{{ $value }}">{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <!-- 啟用狀態 -->
                                <div class="flex items-center">
                                    <input 
                                        type="checkbox" 
                                        id="enabled"
                                        wire:model="enabled"
                                        class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                                    >
                                    <label for="enabled" class="ml-2 block text-sm text-gray-700">
                                        啟用此快捷鍵
                                    </label>
                                </div>

                                <!-- 按鈕 -->
                                <div class="flex space-x-3 pt-4">
                                    <button 
                                        type="submit"
                                        class="flex-1 px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors"
                                    >
                                        {{ $editingKey ? '更新' : '新增' }}
                                    </button>
                                    <button 
                                        type="button"
                                        wire:click="resetForm"
                                        class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors"
                                    >
                                        清除
                                    </button>
                                </div>
                            </form>
                        </div>

                        <!-- 匯入/匯出 -->
                        <div class="mt-6 bg-gray-50 rounded-lg p-4">
                            <h4 class="text-md font-medium text-gray-900 mb-4">匯入/匯出</h4>
                            <div class="space-y-3">
                                <button 
                                    wire:click="exportSettings"
                                    class="w-full px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors"
                                >
                                    匯出設定
                                </button>
                                <div>
                                    <input 
                                        type="file" 
                                        accept=".json"
                                        class="hidden"
                                        id="import-file"
                                        @change="handleFileImport($event)"
                                    >
                                    <button 
                                        onclick="document.getElementById('import-file').click()"
                                        class="w-full px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors"
                                    >
                                        匯入設定
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 對話框底部 -->
                <div class="flex items-center justify-between pt-6 border-t border-gray-200 mt-6">
                    <div class="text-sm text-gray-500">
                        <p>提示：修改後的快捷鍵將立即生效</p>
                    </div>
                    
                    <div class="flex space-x-3">
                        <button 
                            @click="$wire.dispatch('show-shortcut-help')"
                            class="px-4 py-2 text-sm font-medium text-blue-600 bg-blue-50 border border-blue-200 rounded-lg hover:bg-blue-100 transition-colors"
                        >
                            查看說明
                        </button>
                        <button 
                            @click="$wire.close()"
                            class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors"
                        >
                            關閉
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function handleFileImport(event) {
    const file = event.target.files[0];
    if (!file) return;
    
    const reader = new FileReader();
    reader.onload = function(e) {
        try {
            const shortcuts = JSON.parse(e.target.result);
            @this.importSettings(shortcuts, false);
        } catch (error) {
            @this.dispatch('show-toast', {
                type: 'error',
                message: '檔案格式錯誤，請選擇有效的 JSON 檔案'
            });
        }
    };
    reader.readAsText(file);
}
</script>