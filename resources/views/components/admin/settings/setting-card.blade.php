@props([
    'setting',
    'showCategory' => false,
    'compact' => false
])

<div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 hover:shadow-md transition-all duration-200 setting-card">
    <div class="p-4 {{ $compact ? 'sm:p-3' : 'sm:p-6' }}">
        <!-- 設定標頭 -->
        <div class="flex items-start justify-between mb-3">
            <div class="flex-1 min-w-0">
                <div class="flex items-center gap-2 mb-1">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white truncate">
                        {{ $setting->description ?: $setting->key }}
                    </h3>
                    
                    <!-- 狀態標籤 -->
                    <div class="flex items-center gap-1">
                        @if($setting->is_changed)
                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-orange-100 dark:bg-orange-900/30 text-orange-800 dark:text-orange-300">
                                <x-heroicon-o-pencil class="w-3 h-3 mr-1" />
                                已變更
                            </span>
                        @endif
                        
                        @if($setting->is_system)
                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-300">
                                <x-heroicon-o-cog-6-tooth class="w-3 h-3 mr-1" />
                                系統
                            </span>
                        @endif
                        
                        @if($setting->is_encrypted)
                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-purple-100 dark:bg-purple-900/30 text-purple-800 dark:text-purple-300">
                                <x-heroicon-o-lock-closed class="w-3 h-3 mr-1" />
                                加密
                            </span>
                        @endif
                    </div>
                </div>
                
                <!-- 設定鍵值和分類 -->
                <div class="flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400">
                    <code class="px-2 py-1 bg-gray-100 dark:bg-gray-700 rounded text-xs font-mono">
                        {{ $setting->key }}
                    </code>
                    
                    @if($showCategory)
                        <span class="text-gray-300 dark:text-gray-600">•</span>
                        <span class="capitalize">{{ $setting->category }}</span>
                    @endif
                    
                    <span class="text-gray-300 dark:text-gray-600">•</span>
                    <span class="capitalize">{{ $setting->type }}</span>
                </div>
            </div>
            
            <!-- 操作按鈕 -->
            <div class="flex items-center gap-1 ml-4">
                <!-- 預覽按鈕 -->
                @php
                    $previewSettings = config('system-settings.preview_settings', []);
                @endphp
                @if(in_array($setting->key, $previewSettings))
                    <button 
                        onclick="Livewire.dispatch('setting-preview-start', { key: '{{ $setting->key }}', value: @js($setting->value) })"
                        class="p-2 text-blue-600 hover:text-blue-700 hover:bg-blue-50 dark:hover:bg-blue-900/20 rounded-lg transition-colors"
                        title="預覽設定"
                    >
                        <x-heroicon-o-eye class="w-4 h-4" />
                    </button>
                @endif

                <!-- 編輯按鈕 -->
                <button 
                    onclick="Livewire.dispatch('open-setting-form', { settingKey: '{{ $setting->key }}' })"
                    class="p-2 text-gray-600 hover:text-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700 rounded-lg transition-colors"
                    title="編輯設定"
                >
                    <x-heroicon-o-pencil class="w-4 h-4" />
                </button>
                
                <!-- 重設按鈕 -->
                @if($setting->is_changed)
                    <button 
                        onclick="if(confirm('確定要重設此設定為預設值嗎？')) { Livewire.dispatch('reset-setting', { key: '{{ $setting->key }}' }) }"
                        class="p-2 text-orange-600 hover:text-orange-700 hover:bg-orange-50 dark:hover:bg-orange-900/20 rounded-lg transition-colors"
                        title="重設為預設值"
                    >
                        <x-heroicon-o-arrow-path class="w-4 h-4" />
                    </button>
                @endif
                
                <!-- 更多選項 -->
                <div class="dropdown dropdown-end">
                    <button tabindex="0" class="p-2 text-gray-400 hover:text-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700 rounded-lg transition-colors">
                        <x-heroicon-o-ellipsis-vertical class="w-4 h-4" />
                    </button>
                    <ul tabindex="0" class="dropdown-content z-[1] menu p-2 shadow bg-white dark:bg-gray-800 rounded-box w-52 border border-gray-200 dark:border-gray-700">
                        <li>
                            <a onclick="copySettingKey('{{ $setting->key }}')" class="text-sm">
                                <x-heroicon-o-clipboard class="w-4 h-4" />
                                複製設定鍵值
                            </a>
                        </li>
                        <li>
                            <a onclick="Livewire.dispatch('show-setting-history', { key: '{{ $setting->key }}' })" class="text-sm">
                                <x-heroicon-o-clock class="w-4 h-4" />
                                查看變更歷史
                            </a>
                        </li>
                        @if($setting->is_changed)
                            <li>
                                <a onclick="Livewire.dispatch('compare-setting-value', { key: '{{ $setting->key }}' })" class="text-sm">
                                    <x-heroicon-o-arrows-right-left class="w-4 h-4" />
                                    比較變更
                                </a>
                            </li>
                        @endif
                        <li>
                            <a onclick="Livewire.dispatch('export-single-setting', { key: '{{ $setting->key }}' })" class="text-sm">
                                <x-heroicon-o-arrow-down-tray class="w-4 h-4" />
                                匯出此設定
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- 設定值顯示 -->
        <div class="mb-4">
            <div class="flex items-center justify-between mb-2">
                <label class="text-sm font-medium text-gray-700 dark:text-gray-300">
                    目前值
                </label>
                @if($setting->default_value !== null)
                    <button 
                        onclick="showDefaultValue('{{ $setting->key }}')"
                        class="text-xs text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300"
                        title="查看預設值"
                    >
                        查看預設值
                    </button>
                @endif
            </div>
            
            <div class="setting-value-display">
                @if($setting->type === 'password')
                    <div class="flex items-center gap-2">
                        <span class="font-mono text-gray-600 dark:text-gray-400">••••••••</span>
                        <button 
                            onclick="togglePasswordVisibility('{{ $setting->key }}')"
                            class="text-xs text-blue-600 hover:text-blue-700"
                        >
                            顯示
                        </button>
                    </div>
                @elseif($setting->type === 'boolean')
                    <div class="flex items-center gap-2">
                        <div class="toggle toggle-sm {{ $setting->value ? 'toggle-success' : '' }}" disabled>
                            <input type="checkbox" {{ $setting->value ? 'checked' : '' }} disabled />
                        </div>
                        <span class="text-sm text-gray-600 dark:text-gray-400">
                            {{ $setting->value ? '啟用' : '停用' }}
                        </span>
                    </div>
                @elseif($setting->type === 'color')
                    <div class="flex items-center gap-3">
                        <div 
                            class="w-8 h-8 rounded-lg border-2 border-gray-200 dark:border-gray-600 shadow-sm"
                            style="background-color: {{ $setting->value }}"
                        ></div>
                        <span class="font-mono text-sm text-gray-900 dark:text-white">
                            {{ $setting->value }}
                        </span>
                    </div>
                @elseif($setting->type === 'file' && $setting->value)
                    <div class="flex items-center gap-2">
                        <x-heroicon-o-document class="w-5 h-5 text-gray-400" />
                        <span class="text-sm text-gray-900 dark:text-white truncate">
                            {{ basename($setting->value) }}
                        </span>
                        <a href="{{ $setting->value }}" target="_blank" class="text-xs text-blue-600 hover:text-blue-700">
                            查看
                        </a>
                    </div>
                @elseif(is_array($setting->value))
                    <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-3">
                        <pre class="text-xs text-gray-800 dark:text-gray-200 whitespace-pre-wrap">{{ json_encode($setting->value, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                    </div>
                @else
                    <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-3">
                        <span class="font-mono text-sm text-gray-900 dark:text-white break-all">
                            {{ Str::limit($setting->value, 100) }}
                        </span>
                        @if(strlen($setting->value) > 100)
                            <button 
                                onclick="showFullValue('{{ $setting->key }}')"
                                class="ml-2 text-xs text-blue-600 hover:text-blue-700"
                            >
                                顯示完整內容
                            </button>
                        @endif
                    </div>
                @endif
            </div>
        </div>

        <!-- 設定描述和說明 -->
        @if($setting->help_text || $setting->validation_rules)
            <div class="border-t border-gray-200 dark:border-gray-700 pt-3">
                @if($setting->help_text)
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-2">
                        <x-heroicon-o-information-circle class="w-4 h-4 inline mr-1" />
                        {{ $setting->help_text }}
                    </p>
                @endif
                
                @if($setting->validation_rules)
                    <div class="text-xs text-gray-500 dark:text-gray-400">
                        <strong>驗證規則：</strong>
                        <code class="ml-1 px-1 py-0.5 bg-gray-100 dark:bg-gray-700 rounded">
                            {{ $setting->validation_rules }}
                        </code>
                    </div>
                @endif
            </div>
        @endif

        <!-- 最後更新資訊 -->
        @if($setting->updated_at)
            <div class="mt-3 pt-3 border-t border-gray-200 dark:border-gray-700">
                <div class="flex items-center justify-between text-xs text-gray-500 dark:text-gray-400">
                    <span>
                        最後更新：{{ $setting->updated_at->format('Y-m-d H:i:s') }}
                    </span>
                    @if($setting->updated_by)
                        <span>
                            更新者：{{ $setting->updatedBy->name ?? $setting->updated_by }}
                        </span>
                    @endif
                </div>
            </div>
        @endif
    </div>
</div>

@push('scripts')
<script>
// 複製設定鍵值到剪貼簿
function copySettingKey(key) {
    navigator.clipboard.writeText(key).then(function() {
        // 顯示成功提示
        showToast('設定鍵值已複製到剪貼簿', 'success');
    }).catch(function(err) {
        console.error('複製失敗:', err);
        showToast('複製失敗', 'error');
    });
}

// 切換密碼顯示/隱藏
function togglePasswordVisibility(key) {
    // 這裡可以實作密碼顯示切換邏輯
    Livewire.dispatch('toggle-password-visibility', { key: key });
}

// 顯示預設值
function showDefaultValue(key) {
    Livewire.dispatch('show-default-value', { key: key });
}

// 顯示完整值
function showFullValue(key) {
    Livewire.dispatch('show-full-value', { key: key });
}

// 顯示提示訊息
function showToast(message, type = 'info') {
    // 這裡可以整合現有的提示系統
    console.log(`${type}: ${message}`);
}
</script>
@endpush