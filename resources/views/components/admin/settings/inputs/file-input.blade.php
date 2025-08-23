@props([
    'wire:model' => null,
    'id' => null,
    'accept' => null,
    'required' => false,
    'error' => false,
    'currentFile' => null,
    'showPreview' => false
])

<div class="space-y-3">
    {{-- 目前檔案顯示 --}}
    @if($currentFile)
        <div class="flex items-center space-x-3 p-3 bg-gray-50 rounded-lg">
            <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
            </svg>
            <div>
                <p class="text-sm text-gray-600">目前檔案：</p>
                <a href="{{ $currentFile }}" target="_blank" class="text-blue-600 hover:text-blue-500 text-sm">
                    {{ basename($currentFile) }}
                </a>
            </div>
        </div>
    @endif
    
    {{-- 檔案上傳輸入 --}}
    <input type="file"
           {{ $attributes->merge([
               'class' => 'block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100' . ($error ? ' border-red-300' : '')
           ]) }}
           @if($id) id="{{ $id }}" @endif
           @if($accept) accept="{{ $accept }}" @endif
           @if($required) required @endif
           {{ $attributes->whereStartsWith('wire:') }}>
    
    {{-- 上傳進度或新檔案提示 --}}
    <div wire:loading wire:target="{{ $attributes->whereStartsWith('wire:')->first() }}" class="text-sm text-blue-600">
        <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-blue-600 inline" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
        上傳中...
    </div>
</div>