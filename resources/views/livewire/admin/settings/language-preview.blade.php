<div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
    {{-- 標頭 --}}
    <div class="p-4 border-b border-gray-200 dark:border-gray-700">
        <div class="flex items-center justify-between">
            <div>
                <h3 class="text-lg font-medium text-gray-900 dark:text-white">
                    <x-heroicon-o-language class="w-5 h-5 inline mr-2 text-blue-600" />
                    {{ __('settings.preview.title') }}
                </h3>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                    {{ __('settings.help.preview_help') }}
                </p>
            </div>
            
            {{-- 預覽狀態 --}}
            @if($previewMode)
                <span class="badge badge-info">
                    <x-heroicon-o-eye class="w-3 h-3 mr-1" />
                    {{ __('settings.preview.preview_mode') }}
                </span>
            @endif
        </div>
    </div>

    {{-- 語言選擇器 --}}
    <div class="p-4">
        <div class="flex flex-col sm:flex-row sm:items-center gap-4">
            <div class="flex-1">
                <label for="preview_locale" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    {{ __('settings.form.filter_category') }}
                </label>
                <select 
                    id="preview_locale"
                    wire:model.live="previewLocale"
                    class="select select-bordered w-full"
                >
                    @foreach($this->localeOptions as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            
            <div class="flex gap-2">
                @if(!$previewMode)
                    <button 
                        wire:click="startPreview"
                        class="btn btn-primary"
                    >
                        <x-heroicon-o-eye class="w-4 h-4" />
                        {{ __('settings.preview.enable') }}
                    </button>
                @else
                    <button 
                        wire:click="applyPreview"
                        class="btn btn-success"
                    >
                        <x-heroicon-o-check class="w-4 h-4" />
                        {{ __('settings.actions.apply') }}
                    </button>
                    <button 
                        wire:click="stopPreview"
                        class="btn btn-ghost"
                    >
                        <x-heroicon-o-x-mark class="w-4 h-4" />
                        {{ __('settings.actions.cancel') }}
                    </button>
                @endif
            </div>
        </div>
    </div>

    {{-- 預覽內容 --}}
    @if($previewMode)
        <div class="p-4 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700/50">
            <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">
                {{ __('settings.preview.live_preview') }}
            </h4>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                {{-- 介面文字預覽 --}}
                <div>
                    <h5 class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-2">
                        {{ __('settings.form.name') }}
                    </h5>
                    <div class="space-y-2 text-sm">
                        <div class="flex justify-between">
                            <span class="text-gray-600 dark:text-gray-400">{{ __('settings.title') }}:</span>
                            <span class="font-medium">{{ $this->sampleTexts['title'] }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600 dark:text-gray-400">{{ __('settings.actions.save') }}:</span>
                            <span class="font-medium">{{ $this->sampleTexts['save'] }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600 dark:text-gray-400">{{ __('settings.actions.cancel') }}:</span>
                            <span class="font-medium">{{ $this->sampleTexts['cancel'] }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600 dark:text-gray-400">{{ __('settings.status.success') }}:</span>
                            <span class="font-medium text-green-600">{{ $this->sampleTexts['success'] }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600 dark:text-gray-400">{{ __('settings.status.error') }}:</span>
                            <span class="font-medium text-red-600">{{ $this->sampleTexts['error'] }}</span>
                        </div>
                    </div>
                </div>

                {{-- 分類名稱預覽 --}}
                <div>
                    <h5 class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-2">
                        {{ __('settings.form.category') }}
                    </h5>
                    <div class="space-y-2 text-sm">
                        @foreach($this->sampleTexts['categories'] as $key => $name)
                            <div class="flex items-center gap-2">
                                <div class="w-2 h-2 rounded-full bg-blue-500"></div>
                                <span class="font-medium">{{ $name }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>

                {{-- 日期時間格式預覽 --}}
                <div>
                    <h5 class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-2">
                        {{ __('settings.settings.app.date_format.name') }}
                    </h5>
                    <div class="space-y-2 text-sm">
                        <div class="flex justify-between">
                            <span class="text-gray-600 dark:text-gray-400">{{ __('settings.form.current_value') }}:</span>
                            <span class="font-mono">{{ $this->dateTimeExamples['date'] }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600 dark:text-gray-400">{{ __('settings.settings.app.time_format.name') }}:</span>
                            <span class="font-mono">{{ $this->dateTimeExamples['time'] }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600 dark:text-gray-400">{{ __('settings.form.description') }}:</span>
                            <span class="font-mono">{{ $this->dateTimeExamples['datetime'] }}</span>
                        </div>
                    </div>
                </div>

                {{-- 驗證訊息預覽 --}}
                <div>
                    <h5 class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-2">
                        {{ __('settings.validation.required') }}
                    </h5>
                    <div class="space-y-2 text-sm">
                        <div class="text-red-600">
                            {{ __('settings.validation.required', ['attribute' => __('settings.form.name')]) }}
                        </div>
                        <div class="text-red-600">
                            {{ __('settings.validation.email', ['attribute' => __('settings.form.value')]) }}
                        </div>
                        <div class="text-red-600">
                            {{ __('settings.validation.min', ['attribute' => __('settings.form.value'), 'min' => '8']) }}
                        </div>
                    </div>
                </div>
            </div>

            {{-- 預覽警告 --}}
            <div class="mt-4 p-3 bg-yellow-50 dark:bg-yellow-900/20 rounded-lg">
                <p class="text-xs text-yellow-700 dark:text-yellow-300">
                    <x-heroicon-o-exclamation-triangle class="w-4 h-4 inline mr-1" />
                    {{ __('settings.preview.preview_warning') }}
                </p>
            </div>
        </div>
    @endif

    {{-- 語言統計 --}}
    <div class="p-4 border-t border-gray-200 dark:border-gray-700">
        <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">
            {{ __('settings.stats.total_settings') }}
        </h4>
        
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            @foreach($this->localeOptions as $locale => $name)
                <div class="text-center p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                    <div class="text-lg font-bold text-gray-900 dark:text-white">
                        {{ $locale === $previewLocale ? '✓' : '○' }}
                    </div>
                    <div class="text-xs text-gray-600 dark:text-gray-400 mt-1">
                        {{ $name }}
                    </div>
                    @if($locale === $originalLocale)
                        <div class="text-xs text-blue-600 dark:text-blue-400 mt-1">
                            {{ __('settings.status.default') }}
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
    </div>
</div>