@props([
    'category' => '',
    'settings' => [],
    'values' => [],
    'errors' => [],
    'dependencyWarnings' => [],
    'autoSave' => false
])

<div class="space-y-6">
    @foreach($settings as $settingKey => $config)
        @php
            $fieldName = str_replace('.', '_', $settingKey);
            $currentValue = $values[$settingKey] ?? $config['default'] ?? null;
            $fieldErrors = $errors[$settingKey] ?? [];
            $fieldDependencies = $dependencyWarnings[$settingKey] ?? [];
        @endphp
        
        <x-admin.settings.form-field
            :label="$config['description'] ?? $settingKey"
            :name="$fieldName"
            :type="$config['type'] ?? 'text'"
            :required="str_contains($config['validation'] ?? '', 'required')"
            :error="$fieldErrors"
            :help="$config['help'] ?? ''"
            :value="$currentValue"
            :options="$config"
            :dependencyWarnings="$fieldDependencies"
            :autoSave="$autoSave"
            wire:model.live="values.{{ $settingKey }}" />
    @endforeach
    
    {{-- 如果沒有設定項目 --}}
    @if(empty($settings))
        <div class="text-center py-8">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"></path>
            </svg>
            <h3 class="mt-2 text-sm font-medium text-gray-900">此分類暫無設定項目</h3>
            <p class="mt-1 text-sm text-gray-500">設定項目將在系統更新後顯示</p>
        </div>
    @endif
</div>