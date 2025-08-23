@props([
    'placeholder' => '搜尋設定項目...',
    'value' => '',
    'categories' => [],
    'types' => [],
    'showAdvanced' => true
])

<div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
    <div class="p-4">
        <!-- 主搜尋列 -->
        <div class="flex flex-col lg:flex-row gap-4">
            <!-- 搜尋輸入框 -->
            <div class="flex-1">
                <div class="relative">
                    <x-heroicon-o-magnifying-glass class="absolute left-3 top-1/2 transform -translate-y-1/2 w-5 h-5 text-gray-400" />
                    <input 
                        type="text" 
                        wire:model.live.debounce.300ms="search"
                        placeholder="{{ $placeholder }}"
                        class="input input-bordered w-full pl-11 pr-12 h-12 text-base focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                        autocomplete="off"
                    />
                    @if($value)
                        <button 
                            wire:click="$set('search', '')"
                            class="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-400 hover:text-gray-600 transition-colors"
                            title="清除搜尋"
                        >
                            <x-heroicon-o-x-mark class="w-5 h-5" />
                        </button>
                    @endif
                </div>
                
                <!-- 搜尋建議 -->
                @if($value && strlen($value) >= 2)
                    <div class="absolute z-10 w-full mt-1 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg shadow-lg max-h-60 overflow-y-auto">
                        <div class="p-2">
                            <div class="text-xs text-gray-500 dark:text-gray-400 px-2 py-1">搜尋建議</div>
                            <!-- 這裡可以顯示搜尋建議 -->
                            <div class="space-y-1">
                                <button class="w-full text-left px-2 py-1 text-sm hover:bg-gray-100 dark:hover:bg-gray-700 rounded">
                                    app.name - 應用程式名稱
                                </button>
                                <button class="w-full text-left px-2 py-1 text-sm hover:bg-gray-100 dark:hover:bg-gray-700 rounded">
                                    security.password_min_length - 密碼最小長度
                                </button>
                            </div>
                        </div>
                    </div>
                @endif
            </div>

            <!-- 快速篩選 -->
            <div class="flex flex-wrap gap-2 lg:w-auto">
                <!-- 分類篩選 -->
                <select wire:model.live="categoryFilter" class="select select-bordered select-sm min-w-32">
                    <option value="all">所有分類</option>
                    @foreach($categories as $key => $category)
                        <option value="{{ $key }}">{{ $category['name'] }}</option>
                    @endforeach
                </select>

                <!-- 狀態篩選 -->
                <select wire:model.live="statusFilter" class="select select-bordered select-sm min-w-28">
                    <option value="all">所有狀態</option>
                    <option value="changed">已變更</option>
                    <option value="default">預設值</option>
                    <option value="system">系統設定</option>
                </select>

                <!-- 類型篩選 -->
                <select wire:model.live="typeFilter" class="select select-bordered select-sm min-w-24">
                    <option value="all">所有類型</option>
                    @foreach($types as $type)
                        <option value="{{ $type }}">{{ ucfirst($type) }}</option>
                    @endforeach
                </select>

                <!-- 進階搜尋按鈕 -->
                @if($showAdvanced)
                    <button 
                        onclick="toggleAdvancedSearch()"
                        class="btn btn-outline btn-sm"
                        title="進階搜尋"
                    >
                        <x-heroicon-o-adjustments-horizontal class="w-4 h-4" />
                    </button>
                @endif

                <!-- 清除篩選 -->
                <button 
                    wire:click="clearFilters"
                    class="btn btn-ghost btn-sm"
                    title="清除所有篩選"
                >
                    <x-heroicon-o-x-mark class="w-4 h-4" />
                </button>
            </div>
        </div>

        <!-- 進階搜尋面板 -->
        <div id="advanced-search-panel" class="hidden mt-4 pt-4 border-t border-gray-200 dark:border-gray-700">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <!-- 設定鍵值搜尋 -->
                <div>
                    <label class="label label-text text-xs">設定鍵值</label>
                    <input 
                        type="text" 
                        wire:model.live.debounce.500ms="advancedSearch.key"
                        placeholder="例如：app.name"
                        class="input input-bordered input-sm w-full"
                    />
                </div>

                <!-- 值搜尋 -->
                <div>
                    <label class="label label-text text-xs">設定值</label>
                    <input 
                        type="text" 
                        wire:model.live.debounce.500ms="advancedSearch.value"
                        placeholder="搜尋設定值"
                        class="input input-bordered input-sm w-full"
                    />
                </div>

                <!-- 更新時間範圍 -->
                <div>
                    <label class="label label-text text-xs">更新時間</label>
                    <div class="flex gap-1">
                        <input 
                            type="date" 
                            wire:model.live="advancedSearch.dateFrom"
                            class="input input-bordered input-sm flex-1"
                        />
                        <input 
                            type="date" 
                            wire:model.live="advancedSearch.dateTo"
                            class="input input-bordered input-sm flex-1"
                        />
                    </div>
                </div>

                <!-- 更新者 -->
                <div>
                    <label class="label label-text text-xs">更新者</label>
                    <input 
                        type="text" 
                        wire:model.live.debounce.500ms="advancedSearch.updatedBy"
                        placeholder="使用者名稱"
                        class="input input-bordered input-sm w-full"
                    />
                </div>

                <!-- 驗證規則 -->
                <div>
                    <label class="label label-text text-xs">驗證規則</label>
                    <input 
                        type="text" 
                        wire:model.live.debounce.500ms="advancedSearch.validation"
                        placeholder="例如：required"
                        class="input input-bordered input-sm w-full"
                    />
                </div>

                <!-- 選項 -->
                <div class="flex flex-col gap-2">
                    <label class="label label-text text-xs">選項</label>
                    <div class="space-y-1">
                        <label class="label cursor-pointer justify-start gap-2 py-1">
                            <input 
                                type="checkbox" 
                                wire:model.live="advancedSearch.changedOnly"
                                class="checkbox checkbox-xs"
                            />
                            <span class="label-text text-xs">僅顯示已變更</span>
                        </label>
                        <label class="label cursor-pointer justify-start gap-2 py-1">
                            <input 
                                type="checkbox" 
                                wire:model.live="advancedSearch.systemOnly"
                                class="checkbox checkbox-xs"
                            />
                            <span class="label-text text-xs">僅顯示系統設定</span>
                        </label>
                        <label class="label cursor-pointer justify-start gap-2 py-1">
                            <input 
                                type="checkbox" 
                                wire:model.live="advancedSearch.encryptedOnly"
                                class="checkbox checkbox-xs"
                            />
                            <span class="label-text text-xs">僅顯示加密設定</span>
                        </label>
                    </div>
                </div>
            </div>

            <!-- 進階搜尋操作 -->
            <div class="flex justify-between items-center mt-4">
                <div class="text-xs text-gray-500 dark:text-gray-400">
                    使用進階搜尋可以更精確地找到所需的設定項目
                </div>
                <div class="flex gap-2">
                    <button 
                        wire:click="clearAdvancedSearch"
                        class="btn btn-ghost btn-xs"
                    >
                        清除進階搜尋
                    </button>
                    <button 
                        wire:click="saveSearchPreset"
                        class="btn btn-outline btn-xs"
                    >
                        儲存搜尋條件
                    </button>
                </div>
            </div>
        </div>

        <!-- 搜尋結果統計 -->
        <div class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                <!-- 結果統計 -->
                <div class="flex items-center gap-4 text-sm text-gray-600 dark:text-gray-400">
                    <span>找到 <strong class="text-gray-900 dark:text-white">{{ $this->filteredCount ?? 0 }}</strong> 個設定</span>
                    @if($this->hasActiveFilters ?? false)
                        <span class="text-blue-600 dark:text-blue-400">
                            <x-heroicon-o-funnel class="w-4 h-4 inline mr-1" />
                            已套用篩選
                        </span>
                    @endif
                </div>

                <!-- 排序選項 -->
                <div class="flex items-center gap-2">
                    <span class="text-sm text-gray-600 dark:text-gray-400">排序：</span>
                    <select wire:model.live="sortBy" class="select select-bordered select-xs">
                        <option value="key">設定鍵值</option>
                        <option value="category">分類</option>
                        <option value="updated_at">更新時間</option>
                        <option value="type">類型</option>
                    </select>
                    <button 
                        wire:click="toggleSortDirection"
                        class="btn btn-ghost btn-xs"
                        title="切換排序方向"
                    >
                        @if($this->sortDirection === 'asc')
                            <x-heroicon-o-bars-arrow-up class="w-4 h-4" />
                        @else
                            <x-heroicon-o-bars-arrow-down class="w-4 h-4" />
                        @endif
                    </button>
                </div>
            </div>
        </div>

        <!-- 搜尋預設 -->
        @if($this->searchPresets ?? false)
            <div class="mt-3">
                <div class="text-xs text-gray-500 dark:text-gray-400 mb-2">常用搜尋：</div>
                <div class="flex flex-wrap gap-1">
                    @foreach($this->searchPresets as $preset)
                        <button 
                            wire:click="loadSearchPreset('{{ $preset['id'] }}')"
                            class="btn btn-ghost btn-xs"
                            title="{{ $preset['description'] }}"
                        >
                            {{ $preset['name'] }}
                        </button>
                    @endforeach
                </div>
            </div>
        @endif
    </div>
</div>

@push('scripts')
<script>
// 切換進階搜尋面板
function toggleAdvancedSearch() {
    const panel = document.getElementById('advanced-search-panel');
    panel.classList.toggle('hidden');
    
    // 更新按鈕狀態
    const button = event.target.closest('button');
    if (panel.classList.contains('hidden')) {
        button.classList.remove('btn-active');
    } else {
        button.classList.add('btn-active');
    }
}

// 鍵盤快捷鍵
document.addEventListener('keydown', function(e) {
    // Ctrl/Cmd + K: 聚焦搜尋框
    if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
        e.preventDefault();
        const searchInput = document.querySelector('input[wire\\:model\\.live\\.debounce\\.300ms="search"]');
        if (searchInput) {
            searchInput.focus();
        }
    }
    
    // Ctrl/Cmd + Shift + F: 開啟進階搜尋
    if ((e.ctrlKey || e.metaKey) && e.shiftKey && e.key === 'F') {
        e.preventDefault();
        toggleAdvancedSearch();
    }
});

// 搜尋建議功能
function initSearchSuggestions() {
    // 這裡可以實作搜尋建議的邏輯
    // 例如從 API 獲取建議或使用本地快取
}

// 初始化搜尋功能
document.addEventListener('DOMContentLoaded', function() {
    initSearchSuggestions();
});
</script>
@endpush