<div class="space-y-6">
    {{-- 頁面標頭 --}}
    {{-- 移除頁面級標題，遵循 UI 設計標準 --}}
    <div class="flex justify-end">
        <div class="flex items-center space-x-3">
        
        <div class="flex flex-wrap items-center gap-2">
            {{-- 統計資訊 --}}
            <div class="flex items-center gap-4 text-sm text-gray-600 dark:text-gray-400">
                <span>總計: {{ $this->stats['total'] }}</span>
                <span>已變更: {{ $this->stats['changed'] }}</span>
                <span>分類: {{ $this->stats['categories'] }}</span>
                @if($this->stats['filtered'] !== $this->stats['total'])
                    <span class="text-blue-600 dark:text-blue-400">篩選: {{ $this->stats['filtered'] }}</span>
                @endif
            </div>
            
            {{-- 操作按鈕 --}}
            <button 
                wire:click="exportSettings"
                class="btn btn-outline btn-sm"
                title="匯出設定"
            >
                <x-heroicon-o-arrow-down-tray class="w-4 h-4" />
                匯出
            </button>
            
            <button 
                wire:click="openImportDialog"
                class="btn btn-outline btn-sm"
                title="匯入設定"
            >
                <x-heroicon-o-arrow-up-tray class="w-4 h-4" />
                匯入
            </button>
            
            <button 
                wire:click="createBackup"
                class="btn btn-primary btn-sm"
                title="建立備份"
            >
                <x-heroicon-o-archive-box class="w-4 h-4" />
                建立備份
            </button>
        </div>
    </div>

    {{-- 搜尋和篩選工具列 --}}
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-4">
        <div class="flex flex-col lg:flex-row gap-4">
            {{-- 搜尋框 --}}
            <div class="flex-1">
                <div class="relative">
                    <x-heroicon-o-magnifying-glass class="absolute left-3 top-1/2 transform -translate-y-1/2 w-4 h-4 text-gray-400" />
                    <input 
                        type="text" 
                        wire:model.defer="search"
                        wire:key="settings-search-input"
                        placeholder="搜尋設定項目..."
                        class="input input-bordered w-full pl-10"
                    />
                    @if($search)
                        <button 
                            wire:click="$set('search', '')"
                            class="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-400 hover:text-gray-600"
                        >
                            <x-heroicon-o-x-mark class="w-4 h-4" />
                        </button>
                    @endif
                </div>
            </div>

            {{-- 篩選器 --}}
            <div class="flex flex-wrap gap-2">
                {{-- 分類篩選 --}}
                <select wire:model.defer="categoryFilter" wire:key="category-filter-select" class="select select-bordered select-sm">
                    <option value="all">所有分類</option>
                    @foreach($this->categories as $key => $category)
                        <option value="{{ $key }}">{{ $category['name'] }}</option>
                    @endforeach
                </select>

                {{-- 類型篩選 --}}
                <select wire:model.defer="typeFilter" wire:key="type-filter-select" class="select select-bordered select-sm">
                    <option value="all">所有類型</option>
                    @foreach($this->availableTypes as $type)
                        <option value="{{ $type }}">{{ ucfirst($type) }}</option>
                    @endforeach
                </select>

                {{-- 變更狀態篩選 --}}
                <select wire:model.defer="changedFilter" wire:key="changed-filter-select" class="select select-bordered select-sm">
                    <option value="all">所有狀態</option>
                    <option value="changed">已變更</option>
                    <option value="unchanged">未變更</option>
                </select>

                {{-- 檢視模式 --}}
                <select wire:model.defer="viewMode" wire:key="view-mode-select" class="select select-bordered select-sm">
                    <option value="category">分類檢視</option>
                    <option value="list">列表檢視</option>
                    <option value="tree">樹狀檢視</option>
                </select>

                {{-- 清除篩選 --}}
                @if($search || $categoryFilter !== 'all' || $typeFilter !== 'all' || $changedFilter !== 'all')
                    <button 
                        wire:click="clearFilters"
                        class="btn btn-ghost btn-sm"
                        title="清除篩選"
                    >
                        <x-heroicon-o-x-mark class="w-4 h-4" />
                    </button>
                @endif
            </div>
        </div>

        {{-- 批量操作工具列 --}}
        @if(count($selectedSettings) > 0)
            <div class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-4">
                        <span class="text-sm text-gray-600 dark:text-gray-400">
                            已選擇 {{ count($selectedSettings) }} 個項目
                        </span>
                        
                        <select wire:model="bulkAction" class="select select-bordered select-sm">
                            <option value="">選擇操作</option>
                            <option value="reset">重設為預設值</option>
                            <option value="export">匯出選中項目</option>
                        </select>
                        
                        <button 
                            wire:click="executeBulkAction"
                            class="btn btn-primary btn-sm"
                            :disabled="!bulkAction"
                        >
                            執行
                        </button>
                    </div>
                    
                    <button 
                        wire:click="clearSelection"
                        class="btn btn-ghost btn-sm"
                    >
                        取消選擇
                    </button>
                </div>
            </div>
        @endif
    </div>

    {{-- 設定列表 --}}
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
        @if($viewMode === 'category')
            {{-- 分類檢視 --}}
            <div class="divide-y divide-gray-200 dark:divide-gray-700">
                {{-- 分類操作工具列 --}}
                <div class="p-4 bg-gray-50 dark:bg-gray-700/50">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <input 
                                type="checkbox" 
                                wire:click="toggleSelectAll"
                                :checked="$wire.isAllSelected()"
                                :indeterminate="$wire.isPartiallySelected()"
                                class="checkbox checkbox-sm"
                            />
                            <span class="text-sm text-gray-600 dark:text-gray-400">全選</span>
                        </div>
                        
                        <div class="flex items-center gap-2">
                            <button 
                                wire:click="expandAllCategories"
                                class="btn btn-ghost btn-xs"
                            >
                                展開全部
                            </button>
                            <button 
                                wire:click="collapseAllCategories"
                                class="btn btn-ghost btn-xs"
                            >
                                收合全部
                            </button>
                        </div>
                    </div>
                </div>

                {{-- 分類列表 --}}
                @forelse($this->settingsByCategory as $category => $categorySettings)
                    <div class="category-section">
                        {{-- 分類標頭 --}}
                        <div 
                            wire:click="toggleCategory('{{ $category }}')"
                            class="flex items-center justify-between p-4 cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors"
                        >
                            <div class="flex items-center gap-3">
                                <div class="flex items-center gap-2">
                                    @if($this->isCategoryExpanded($category))
                                        <x-heroicon-o-chevron-down class="w-4 h-4 text-gray-400" />
                                    @else
                                        <x-heroicon-o-chevron-right class="w-4 h-4 text-gray-400" />
                                    @endif
                                    
                                    @php
                                        $iconName = $this->getCategoryIcon($category);
                                        $iconComponent = match($iconName) {
                                            'cog' => 'heroicon-o-cog-6-tooth',
                                            'shield-check' => 'heroicon-o-shield-check',
                                            'bell' => 'heroicon-o-bell',
                                            'palette' => 'heroicon-o-paint-brush',
                                            'link' => 'heroicon-o-link',
                                            'wrench' => 'heroicon-o-wrench-screwdriver',
                                            default => 'heroicon-o-cog-6-tooth'
                                        };
                                    @endphp
                                    <x-dynamic-component 
                                        :component="$iconComponent" 
                                        class="w-5 h-5 text-blue-600 dark:text-blue-400" 
                                    />
                                </div>
                                
                                <div>
                                    <h3 class="font-medium text-gray-900 dark:text-white">
                                        {{ $this->getCategoryName($category) }}
                                    </h3>
                                    <p class="text-sm text-gray-600 dark:text-gray-400">
                                        {{ $this->getCategoryDescription($category) }}
                                    </p>
                                </div>
                            </div>
                            
                            <div class="flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400">
                                <span>{{ $categorySettings->count() }} 項設定</span>
                                @if($categorySettings->where('is_changed', true)->count() > 0)
                                    <span class="badge badge-warning badge-sm">
                                        {{ $categorySettings->where('is_changed', true)->count() }} 已變更
                                    </span>
                                @endif
                            </div>
                        </div>

                        {{-- 分類設定列表 --}}
                        @if($this->isCategoryExpanded($category))
                            <div class="bg-gray-50 dark:bg-gray-700/25">
                                @foreach($categorySettings as $setting)
                                    <div class="flex items-center justify-between p-4 border-t border-gray-200 dark:border-gray-600 hover:bg-white dark:hover:bg-gray-700/50 transition-colors">
                                        <div class="flex items-center gap-3 flex-1">
                                            <input 
                                                type="checkbox" 
                                                wire:click="toggleSettingSelection('{{ $setting->key }}')"
                                                :checked="$wire.isSettingSelected('{{ $setting->key }}')"
                                                class="checkbox checkbox-sm"
                                            />
                                            
                                            <div class="flex-1">
                                                <div class="flex items-center gap-2">
                                                    <h4 class="font-medium text-gray-900 dark:text-white">
                                                        {{ $setting->description ?: $setting->key }}
                                                    </h4>
                                                    
                                                    @if($setting->is_changed)
                                                        <span class="badge badge-warning badge-xs">已變更</span>
                                                    @endif
                                                    
                                                    @if($setting->is_system)
                                                        <span class="badge badge-info badge-xs">系統</span>
                                                    @endif
                                                    
                                                    @if($setting->is_encrypted)
                                                        <x-heroicon-o-lock-closed class="w-3 h-3 text-gray-400" title="加密設定" />
                                                    @endif
                                                </div>
                                                
                                                <div class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                                                    <span class="font-mono">{{ $setting->key }}</span>
                                                    <span class="mx-2">•</span>
                                                    <span class="capitalize">{{ $setting->type }}</span>
                                                </div>
                                                
                                                <div class="mt-1 text-sm">
                                                    <span class="text-gray-500 dark:text-gray-400">目前值：</span>
                                                    <span class="font-mono text-gray-900 dark:text-white">
                                                        @if($setting->type === 'password')
                                                            ••••••••
                                                        @elseif($setting->type === 'boolean')
                                                            {{ $setting->value ? '是' : '否' }}
                                                        @elseif(is_array($setting->value))
                                                            {{ json_encode($setting->value, JSON_UNESCAPED_UNICODE) }}
                                                        @else
                                                            {{ Str::limit($setting->value, 50) }}
                                                        @endif
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="flex items-center gap-2">
                                            {{-- 預覽按鈕（僅支援預覽的設定顯示） --}}
                                            @php
                                                $previewSettings = config('system-settings.preview_settings', []);
                                            @endphp
                                            @if(in_array($setting->key, $previewSettings))
                                                <button 
                                                    wire:click="$dispatch('setting-preview-start', { key: '{{ $setting->key }}', value: @js($setting->value) })"
                                                    class="btn btn-ghost btn-sm text-blue-600 hover:text-blue-700"
                                                    title="預覽設定"
                                                >
                                                    <x-heroicon-o-eye class="w-4 h-4" />
                                                </button>
                                            @endif

                                            <button 
                                                wire:click="editSetting('{{ $setting->key }}')"
                                                class="btn btn-ghost btn-sm"
                                                title="編輯設定"
                                            >
                                                <x-heroicon-o-pencil class="w-4 h-4" />
                                            </button>
                                            
                                            @if($setting->is_changed)
                                                <button 
                                                    wire:click="resetSetting('{{ $setting->key }}')"
                                                    class="btn btn-ghost btn-sm text-orange-600 hover:text-orange-700"
                                                    title="重設為預設值"
                                                    onclick="return confirm('確定要重設此設定為預設值嗎？')"
                                                >
                                                    <x-heroicon-o-arrow-path class="w-4 h-4" />
                                                </button>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                @empty
                    <div class="p-8 text-center text-gray-500 dark:text-gray-400">
                        <x-heroicon-o-cog class="w-12 h-12 mx-auto mb-4 text-gray-300 dark:text-gray-600" />
                        <p>沒有找到符合條件的設定項目</p>
                        @if($search || $categoryFilter !== 'all' || $typeFilter !== 'all' || $changedFilter !== 'all')
                            <button 
                                wire:click="clearFilters"
                                class="mt-2 btn btn-ghost btn-sm"
                            >
                                清除篩選條件
                            </button>
                        @endif
                    </div>
                @endforelse
            </div>
        @elseif($viewMode === 'list')
            {{-- 列表檢視 --}}
            <div class="overflow-x-auto">
                <table class="table table-zebra w-full">
                    <thead>
                        <tr>
                            <th class="w-12">
                                <input 
                                    type="checkbox" 
                                    wire:click="toggleSelectAll"
                                    :checked="$wire.isAllSelected()"
                                    :indeterminate="$wire.isPartiallySelected()"
                                    class="checkbox checkbox-sm"
                                />
                            </th>
                            <th>設定項目</th>
                            <th>分類</th>
                            <th>類型</th>
                            <th>目前值</th>
                            <th>狀態</th>
                            <th class="w-32">操作</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($this->settings as $setting)
                            <tr>
                                <td>
                                    <input 
                                        type="checkbox" 
                                        wire:click="toggleSettingSelection('{{ $setting->key }}')"
                                        :checked="$wire.isSettingSelected('{{ $setting->key }}')"
                                        class="checkbox checkbox-sm"
                                    />
                                </td>
                                <td>
                                    <div>
                                        <div class="font-medium text-gray-900 dark:text-white">
                                            {{ $setting->description ?: $setting->key }}
                                        </div>
                                        <div class="text-sm text-gray-500 dark:text-gray-400 font-mono">
                                            {{ $setting->key }}
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge badge-outline">
                                        {{ $this->getCategoryName($setting->category) }}
                                    </span>
                                </td>
                                <td>
                                    <span class="capitalize text-sm">{{ $setting->type }}</span>
                                </td>
                                <td>
                                    <div class="font-mono text-sm max-w-xs truncate">
                                        @if($setting->type === 'password')
                                            ••••••••
                                        @elseif($setting->type === 'boolean')
                                            {{ $setting->value ? '是' : '否' }}
                                        @elseif(is_array($setting->value))
                                            {{ json_encode($setting->value, JSON_UNESCAPED_UNICODE) }}
                                        @else
                                            {{ Str::limit($setting->value, 30) }}
                                        @endif
                                    </div>
                                </td>
                                <td>
                                    <div class="flex items-center gap-1">
                                        @if($setting->is_changed)
                                            <span class="badge badge-warning badge-xs">已變更</span>
                                        @endif
                                        @if($setting->is_system)
                                            <span class="badge badge-info badge-xs">系統</span>
                                        @endif
                                        @if($setting->is_encrypted)
                                            <x-heroicon-o-lock-closed class="w-3 h-3 text-gray-400" title="加密" />
                                        @endif
                                    </div>
                                </td>
                                <td>
                                    <div class="flex items-center gap-1">
                                        {{-- 預覽按鈕 --}}
                                        @php
                                            $previewSettings = config('system-settings.preview_settings', []);
                                        @endphp
                                        @if(in_array($setting->key, $previewSettings))
                                            <button 
                                                wire:click="$dispatch('setting-preview-start', { key: '{{ $setting->key }}', value: @js($setting->value) })"
                                                class="btn btn-ghost btn-xs text-blue-600"
                                                title="預覽"
                                            >
                                                <x-heroicon-o-eye class="w-3 h-3" />
                                            </button>
                                        @endif

                                        <button 
                                            wire:click="editSetting('{{ $setting->key }}')"
                                            class="btn btn-ghost btn-xs"
                                            title="編輯"
                                        >
                                            <x-heroicon-o-pencil class="w-3 h-3" />
                                        </button>
                                        
                                        @if($setting->is_changed)
                                            <button 
                                                wire:click="resetSetting('{{ $setting->key }}')"
                                                class="btn btn-ghost btn-xs text-orange-600"
                                                title="重設"
                                                onclick="return confirm('確定要重設此設定為預設值嗎？')"
                                            >
                                                <x-heroicon-o-arrow-path class="w-3 h-3" />
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-8 text-gray-500 dark:text-gray-400">
                                    沒有找到符合條件的設定項目
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        @else
            {{-- 樹狀檢視 --}}
            <div class="p-4">
                <div class="text-center text-gray-500 dark:text-gray-400 py-8">
                    <x-heroicon-o-squares-2x2 class="w-12 h-12 mx-auto mb-4 text-gray-300 dark:text-gray-600" />
                    <p>樹狀檢視功能開發中...</p>
                </div>
            </div>
        @endif
    </div>

    {{-- 批量操作確認對話框 --}}
    @if($showBulkConfirm)
        <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-md w-full mx-4">
                <div class="p-6">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">
                        確認批量操作
                    </h3>
                    
                    <p class="text-gray-600 dark:text-gray-400 mb-6">
                        您即將對 {{ count($selectedSettings) }} 個設定項目執行
                        <strong>
                            @if($bulkAction === 'reset')
                                重設為預設值
                            @elseif($bulkAction === 'export')
                                匯出
                            @endif
                        </strong>
                        操作，此操作無法復原。
                    </p>
                    
                    <div class="flex justify-end gap-3">
                        <button 
                            wire:click="cancelBulkAction"
                            class="btn btn-ghost"
                        >
                            取消
                        </button>
                        <button 
                            wire:click="confirmBulkAction"
                            class="btn btn-primary"
                        >
                            確認執行
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- 設定預覽元件 --}}
    <livewire:admin.settings.setting-preview />

    {{-- 設定表單元件 --}}
    <livewire:admin.settings.setting-form />

    {{-- 設定匯入匯出元件 --}}
    <livewire:admin.settings.setting-import-export />
</div>

@script
<script>
    // 監聽設定列表重置事件
    $wire.on('settings-list-reset', () => {
        console.log('🔄 收到 settings-list-reset 事件，手動更新前端...');
        
        // 重置所有表單元素
        const formElements = [
            // 搜尋輸入框
            'input[wire\\:key="settings-search-input"]',
            // 下拉選單
            'select[wire\\:key="category-filter-select"]',
            'select[wire\\:key="type-filter-select"]',
            'select[wire\\:key="changed-filter-select"]',
            'select[wire\\:key="view-mode-select"]'
        ];
        
        formElements.forEach(selector => {
            const elements = document.querySelectorAll(selector);
            elements.forEach(element => {
                if (element.tagName === 'SELECT') {
                    // 重置下拉選單為第一個選項（通常是 'all'）
                    element.selectedIndex = 0;
                    element.dispatchEvent(new Event('change', { bubbles: true }));
                } else if (element.type === 'text') {
                    // 清空文字輸入框
                    element.value = '';
                    element.dispatchEvent(new Event('input', { bubbles: true }));
                }
                
                // 觸發 blur 事件確保同步
                element.blur();
            });
        });
        
        // 延遲刷新以確保同步
        setTimeout(() => {
            console.log('🔄 SettingsList 延遲刷新執行');
            $wire.$refresh();
        }, 500);
    });

    // 為表單元素添加手動觸發事件
    document.addEventListener('DOMContentLoaded', function() {
        // 為所有 select 元素添加 change 事件監聽
        const selects = document.querySelectorAll('select[wire\\:model\\.defer]');
        selects.forEach(select => {
            select.addEventListener('change', function() {
                this.blur();
                setTimeout(() => $wire.$refresh(), 100);
            });
        });
        
        // 為搜尋輸入框添加事件監聽
        const searchInput = document.querySelector('input[wire\\:key="settings-search-input"]');
        if (searchInput) {
            searchInput.addEventListener('keyup', function(e) {
                if (e.key === 'Enter') {
                    this.blur();
                    $wire.$refresh();
                }
            });
            searchInput.addEventListener('blur', function() {
                setTimeout(() => $wire.$refresh(), 100);
            });
        }
    });
</script>
@endscript
