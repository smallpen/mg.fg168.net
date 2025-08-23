@props([
    'settings' => collect(),
    'viewMode' => 'category',
    'categories' => [],
    'showSearch' => true,
    'showFilters' => true,
    'compact' => false
])

<div class="settings-grid-container" x-data="settingsGrid()">
    <!-- 工具列 -->
    @if($showSearch || $showFilters)
        <div class="mb-6">
            <x-admin.settings.search-bar 
                :categories="$categories"
                :types="$settings->pluck('type')->unique()->values()->toArray()"
                :show-advanced="true"
            />
        </div>
    @endif

    <!-- 檢視模式切換 -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <div class="flex items-center gap-4">
            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">檢視模式：</span>
            <div class="btn-group">
                <button 
                    @click="setViewMode('category')"
                    :class="viewMode === 'category' ? 'btn-active' : ''"
                    class="btn btn-sm"
                >
                    <x-heroicon-o-squares-2x2 class="w-4 h-4" />
                    <span class="hidden sm:inline ml-1">分類</span>
                </button>
                <button 
                    @click="setViewMode('grid')"
                    :class="viewMode === 'grid' ? 'btn-active' : ''"
                    class="btn btn-sm"
                >
                    <x-heroicon-o-squares-plus class="w-4 h-4" />
                    <span class="hidden sm:inline ml-1">網格</span>
                </button>
                <button 
                    @click="setViewMode('list')"
                    :class="viewMode === 'list' ? 'btn-active' : ''"
                    class="btn btn-sm"
                >
                    <x-heroicon-o-list-bullet class="w-4 h-4" />
                    <span class="hidden sm:inline ml-1">列表</span>
                </button>
                <button 
                    @click="setViewMode('compact')"
                    :class="viewMode === 'compact' ? 'btn-active' : ''"
                    class="btn btn-sm"
                >
                    <x-heroicon-o-minus class="w-4 h-4" />
                    <span class="hidden sm:inline ml-1">緊湊</span>
                </button>
            </div>
        </div>
        
        <div class="flex items-center gap-2">
            <!-- 批量操作 -->
            <div x-show="selectedSettings.length > 0" class="flex items-center gap-2">
                <span class="text-sm text-gray-600 dark:text-gray-400">
                    已選擇 <span x-text="selectedSettings.length"></span> 項
                </span>
                <select x-model="bulkAction" class="select select-bordered select-sm">
                    <option value="">選擇操作</option>
                    <option value="export">匯出</option>
                    <option value="reset">重設</option>
                    <option value="backup">備份</option>
                </select>
                <button 
                    @click="executeBulkAction()"
                    :disabled="!bulkAction"
                    class="btn btn-primary btn-sm"
                >
                    執行
                </button>
                <button 
                    @click="clearSelection()"
                    class="btn btn-ghost btn-sm"
                >
                    清除
                </button>
            </div>
            
            <!-- 檢視選項 -->
            <div class="dropdown dropdown-end">
                <button tabindex="0" class="btn btn-ghost btn-sm">
                    <x-heroicon-o-adjustments-horizontal class="w-4 h-4" />
                </button>
                <ul tabindex="0" class="dropdown-content z-[1] menu p-2 shadow bg-white dark:bg-gray-800 rounded-box w-52 border border-gray-200 dark:border-gray-700">
                    <li>
                        <label class="label cursor-pointer justify-start gap-2">
                            <input 
                                type="checkbox" 
                                x-model="showCategories"
                                class="checkbox checkbox-sm"
                            />
                            <span class="label-text">顯示分類</span>
                        </label>
                    </li>
                    <li>
                        <label class="label cursor-pointer justify-start gap-2">
                            <input 
                                type="checkbox" 
                                x-model="showDescriptions"
                                class="checkbox checkbox-sm"
                            />
                            <span class="label-text">顯示描述</span>
                        </label>
                    </li>
                    <li>
                        <label class="label cursor-pointer justify-start gap-2">
                            <input 
                                type="checkbox" 
                                x-model="showValues"
                                class="checkbox checkbox-sm"
                            />
                            <span class="label-text">顯示設定值</span>
                        </label>
                    </li>
                    <li>
                        <label class="label cursor-pointer justify-start gap-2">
                            <input 
                                type="checkbox" 
                                x-model="showMetadata"
                                class="checkbox checkbox-sm"
                            />
                            <span class="label-text">顯示中繼資料</span>
                        </label>
                    </li>
                </ul>
            </div>
        </div>
    </div>

    <!-- 設定內容區域 -->
    <div class="settings-content">
        <!-- 分類檢視 -->
        <div x-show="viewMode === 'category'" class="space-y-8">
            @foreach($settings->groupBy('category') as $categoryKey => $categorySettings)
                <div class="category-section">
                    <!-- 分類標頭 -->
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center gap-3">
                            <button 
                                @click="toggleCategory('{{ $categoryKey }}')"
                                class="flex items-center gap-2 text-lg font-semibold text-gray-900 dark:text-white hover:text-blue-600 dark:hover:text-blue-400 transition-colors"
                            >
                                <x-heroicon-o-chevron-right 
                                    class="w-5 h-5 transition-transform"
                                    :class="expandedCategories.includes('{{ $categoryKey }}') ? 'rotate-90' : ''"
                                />
                                @php
                                    $category = $categories[$categoryKey] ?? ['name' => $categoryKey, 'icon' => 'cog'];
                                    $iconComponent = match($category['icon'] ?? 'cog') {
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
                                    class="w-6 h-6 text-blue-600 dark:text-blue-400" 
                                />
                                {{ $category['name'] }}
                            </button>
                            <span class="text-sm text-gray-500 dark:text-gray-400">
                                ({{ $categorySettings->count() }} 項設定)
                            </span>
                        </div>
                        
                        <div class="flex items-center gap-2">
                            @if($categorySettings->where('is_changed', true)->count() > 0)
                                <span class="badge badge-warning badge-sm">
                                    {{ $categorySettings->where('is_changed', true)->count() }} 已變更
                                </span>
                            @endif
                            
                            <div class="dropdown dropdown-end">
                                <button tabindex="0" class="btn btn-ghost btn-xs">
                                    <x-heroicon-o-ellipsis-horizontal class="w-4 h-4" />
                                </button>
                                <ul tabindex="0" class="dropdown-content z-[1] menu p-2 shadow bg-white dark:bg-gray-800 rounded-box w-48 border border-gray-200 dark:border-gray-700">
                                    <li>
                                        <a @click="selectCategorySettings('{{ $categoryKey }}')">
                                            <x-heroicon-o-check-circle class="w-4 h-4" />
                                            選擇此分類
                                        </a>
                                    </li>
                                    <li>
                                        <a @click="exportCategorySettings('{{ $categoryKey }}')">
                                            <x-heroicon-o-arrow-down-tray class="w-4 h-4" />
                                            匯出此分類
                                        </a>
                                    </li>
                                    <li>
                                        <a @click="resetCategorySettings('{{ $categoryKey }}')">
                                            <x-heroicon-o-arrow-path class="w-4 h-4" />
                                            重設此分類
                                        </a>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    
                    <!-- 分類描述 -->
                    @if(isset($category['description']) && $showDescriptions)
                        <p class="text-gray-600 dark:text-gray-400 mb-4 ml-8">
                            {{ $category['description'] }}
                        </p>
                    @endif
                    
                    <!-- 分類設定 -->
                    <div 
                        x-show="expandedCategories.includes('{{ $categoryKey }}')"
                        x-transition:enter="transition ease-out duration-200"
                        x-transition:enter-start="opacity-0 transform -translate-y-2"
                        x-transition:enter-end="opacity-100 transform translate-y-0"
                        class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4 ml-8"
                    >
                        @foreach($categorySettings as $setting)
                            <div class="setting-item" data-setting-key="{{ $setting->key }}">
                                <x-admin.settings.setting-card 
                                    :setting="$setting"
                                    :show-category="false"
                                    :compact="$compact"
                                />
                            </div>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>
        
        <!-- 網格檢視 -->
        <div x-show="viewMode === 'grid'">
            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-4 gap-4">
                @foreach($settings as $setting)
                    <div class="setting-item" data-setting-key="{{ $setting->key }}">
                        <x-admin.settings.setting-card 
                            :setting="$setting"
                            :show-category="$showCategories"
                            :compact="$compact"
                        />
                    </div>
                @endforeach
            </div>
        </div>
        
        <!-- 列表檢視 -->
        <div x-show="viewMode === 'list'">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="table table-zebra w-full">
                        <thead class="bg-gray-50 dark:bg-gray-700">
                            <tr>
                                <th class="w-12">
                                    <input 
                                        type="checkbox" 
                                        @change="toggleSelectAll()"
                                        :checked="isAllSelected()"
                                        :indeterminate="isPartiallySelected()"
                                        class="checkbox checkbox-sm"
                                    />
                                </th>
                                <th class="text-left">設定項目</th>
                                <th class="text-left" x-show="showCategories">分類</th>
                                <th class="text-left">類型</th>
                                <th class="text-left" x-show="showValues">目前值</th>
                                <th class="text-left">狀態</th>
                                <th class="text-left" x-show="showMetadata">最後更新</th>
                                <th class="text-center w-32">操作</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($settings as $setting)
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                                    <td>
                                        <input 
                                            type="checkbox" 
                                            :checked="selectedSettings.includes('{{ $setting->key }}')"
                                            @change="toggleSettingSelection('{{ $setting->key }}')"
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
                                    <td x-show="showCategories">
                                        <span class="badge badge-outline badge-sm">
                                            {{ $categories[$setting->category]['name'] ?? $setting->category }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="text-sm capitalize">{{ $setting->type }}</span>
                                    </td>
                                    <td x-show="showValues">
                                        <div class="max-w-xs truncate font-mono text-sm">
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
                                                <x-heroicon-o-lock-closed class="w-3 h-3 text-gray-400" />
                                            @endif
                                        </div>
                                    </td>
                                    <td x-show="showMetadata">
                                        <div class="text-xs text-gray-500 dark:text-gray-400">
                                            {{ $setting->updated_at?->format('m-d H:i') }}
                                        </div>
                                    </td>
                                    <td>
                                        <div class="flex items-center justify-center gap-1">
                                            @php
                                                $previewSettings = config('system-settings.preview_settings', []);
                                            @endphp
                                            @if(in_array($setting->key, $previewSettings))
                                                <button 
                                                    @click="previewSetting('{{ $setting->key }}')"
                                                    class="btn btn-ghost btn-xs text-blue-600"
                                                    title="預覽"
                                                >
                                                    <x-heroicon-o-eye class="w-3 h-3" />
                                                </button>
                                            @endif
                                            
                                            <button 
                                                @click="editSetting('{{ $setting->key }}')"
                                                class="btn btn-ghost btn-xs"
                                                title="編輯"
                                            >
                                                <x-heroicon-o-pencil class="w-3 h-3" />
                                            </button>
                                            
                                            @if($setting->is_changed)
                                                <button 
                                                    @click="resetSetting('{{ $setting->key }}')"
                                                    class="btn btn-ghost btn-xs text-orange-600"
                                                    title="重設"
                                                >
                                                    <x-heroicon-o-arrow-path class="w-3 h-3" />
                                                </button>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
        <!-- 緊湊檢視 -->
        <div x-show="viewMode === 'compact'">
            <div class="space-y-2">
                @foreach($settings as $setting)
                    <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-3 hover:shadow-sm transition-shadow">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-3 flex-1 min-w-0">
                                <input 
                                    type="checkbox" 
                                    :checked="selectedSettings.includes('{{ $setting->key }}')"
                                    @change="toggleSettingSelection('{{ $setting->key }}')"
                                    class="checkbox checkbox-sm"
                                />
                                
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center gap-2">
                                        <h4 class="font-medium text-gray-900 dark:text-white truncate">
                                            {{ $setting->description ?: $setting->key }}
                                        </h4>
                                        @if($setting->is_changed)
                                            <span class="badge badge-warning badge-xs">變更</span>
                                        @endif
                                    </div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400">
                                        <code>{{ $setting->key }}</code>
                                        <span class="mx-1">•</span>
                                        <span class="capitalize">{{ $setting->type }}</span>
                                        @if($showCategories)
                                            <span class="mx-1">•</span>
                                            <span>{{ $categories[$setting->category]['name'] ?? $setting->category }}</span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            
                            <div class="flex items-center gap-1">
                                @php
                                    $previewSettings = config('system-settings.preview_settings', []);
                                @endphp
                                @if(in_array($setting->key, $previewSettings))
                                    <button 
                                        @click="previewSetting('{{ $setting->key }}')"
                                        class="btn btn-ghost btn-xs text-blue-600"
                                        title="預覽"
                                    >
                                        <x-heroicon-o-eye class="w-3 h-3" />
                                    </button>
                                @endif
                                
                                <button 
                                    @click="editSetting('{{ $setting->key }}')"
                                    class="btn btn-ghost btn-xs"
                                    title="編輯"
                                >
                                    <x-heroicon-o-pencil class="w-3 h-3" />
                                </button>
                                
                                @if($setting->is_changed)
                                    <button 
                                        @click="resetSetting('{{ $setting->key }}')"
                                        class="btn btn-ghost btn-xs text-orange-600"
                                        title="重設"
                                    >
                                        <x-heroicon-o-arrow-path class="w-3 h-3" />
                                    </button>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
    
    <!-- 空狀態 -->
    @if($settings->isEmpty())
        <div class="text-center py-12">
            <x-heroicon-o-cog-6-tooth class="w-16 h-16 mx-auto text-gray-300 dark:text-gray-600 mb-4" />
            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">
                沒有找到設定項目
            </h3>
            <p class="text-gray-600 dark:text-gray-400 mb-4">
                請調整搜尋條件或篩選器來查看設定項目
            </p>
            <button 
                @click="clearAllFilters()"
                class="btn btn-primary"
            >
                清除所有篩選
            </button>
        </div>
    @endif
</div>

@push('scripts')
<script>
function settingsGrid() {
    return {
        viewMode: '{{ $viewMode }}',
        selectedSettings: [],
        expandedCategories: @js(array_keys($categories)),
        bulkAction: '',
        showCategories: true,
        showDescriptions: true,
        showValues: true,
        showMetadata: false,
        
        // 設定檢視模式
        setViewMode(mode) {
            this.viewMode = mode;
            Livewire.dispatch('change-view-mode', { mode: mode });
        },
        
        // 切換分類展開狀態
        toggleCategory(category) {
            const index = this.expandedCategories.indexOf(category);
            if (index > -1) {
                this.expandedCategories.splice(index, 1);
            } else {
                this.expandedCategories.push(category);
            }
        },
        
        // 切換設定選擇
        toggleSettingSelection(key) {
            const index = this.selectedSettings.indexOf(key);
            if (index > -1) {
                this.selectedSettings.splice(index, 1);
            } else {
                this.selectedSettings.push(key);
            }
        },
        
        // 全選/取消全選
        toggleSelectAll() {
            const allKeys = Array.from(document.querySelectorAll('[data-setting-key]')).map(el => el.dataset.settingKey);
            if (this.isAllSelected()) {
                this.selectedSettings = [];
            } else {
                this.selectedSettings = [...allKeys];
            }
        },
        
        // 檢查是否全選
        isAllSelected() {
            const allKeys = Array.from(document.querySelectorAll('[data-setting-key]')).map(el => el.dataset.settingKey);
            return allKeys.length > 0 && allKeys.every(key => this.selectedSettings.includes(key));
        },
        
        // 檢查是否部分選中
        isPartiallySelected() {
            const allKeys = Array.from(document.querySelectorAll('[data-setting-key]')).map(el => el.dataset.settingKey);
            const selectedCount = allKeys.filter(key => this.selectedSettings.includes(key)).length;
            return selectedCount > 0 && selectedCount < allKeys.length;
        },
        
        // 清除選擇
        clearSelection() {
            this.selectedSettings = [];
        },
        
        // 選擇分類設定
        selectCategorySettings(category) {
            const categoryKeys = Array.from(document.querySelectorAll(`[data-setting-key]`))
                .map(el => el.dataset.settingKey)
                .filter(key => {
                    // 這裡需要根據實際情況判斷設定所屬分類
                    return true; // 簡化實作
                });
            this.selectedSettings = [...new Set([...this.selectedSettings, ...categoryKeys])];
        },
        
        // 執行批量操作
        executeBulkAction() {
            if (!this.bulkAction || this.selectedSettings.length === 0) {
                return;
            }
            
            Livewire.dispatch('execute-bulk-action', {
                action: this.bulkAction,
                settings: this.selectedSettings
            });
        },
        
        // 編輯設定
        editSetting(key) {
            Livewire.dispatch('open-setting-form', { settingKey: key });
        },
        
        // 預覽設定
        previewSetting(key) {
            Livewire.dispatch('open-setting-preview', { settingKey: key });
        },
        
        // 重設設定
        resetSetting(key) {
            if (confirm('確定要重設此設定為預設值嗎？')) {
                Livewire.dispatch('reset-setting', { key: key });
            }
        },
        
        // 匯出分類設定
        exportCategorySettings(category) {
            Livewire.dispatch('export-category-settings', { category: category });
        },
        
        // 重設分類設定
        resetCategorySettings(category) {
            if (confirm('確定要重設此分類的所有設定為預設值嗎？')) {
                Livewire.dispatch('reset-category-settings', { category: category });
            }
        },
        
        // 清除所有篩選
        clearAllFilters() {
            Livewire.dispatch('clear-all-filters');
        }
    };
}
</script>
@endpush