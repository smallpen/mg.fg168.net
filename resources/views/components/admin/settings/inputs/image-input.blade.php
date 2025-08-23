@props([
    'wire:model' => null,
    'id' => null,
    'required' => false,
    'error' => false,
    'currentImage' => null,
    'maxSize' => '2MB'
])

<div class="space-y-3">
    {{-- 目前圖片預覽 --}}
    @if($currentImage)
        <div class="flex items-center space-x-3 p-3 bg-gray-50 rounded-lg">
            <img src="{{ $currentImage }}" alt="目前圖片" class="h-16 w-16 object-cover rounded-lg border">
            <div>
                <p class="text-sm text-gray-600">目前圖片：</p>
                <a href="{{ $currentImage }}" target="_blank" class="text-blue-600 hover:text-blue-500 text-sm">
                    {{ basename($currentImage) }}
                </a>
            </div>
        </div>
    @endif
    
    {{-- 圖片上傳輸入 --}}
    <input type="file"
           {{ $attributes->merge([
               'class' => 'block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100' . ($error ? ' border-red-300' : '')
           ]) }}
           @if($id) id="{{ $id }}" @endif
           accept="image/*"
           @if($required) required @endif
           {{ $attributes->whereStartsWith('wire:') }}>
    
    {{-- 上傳提示 --}}
    <p class="text-xs text-gray-500">
        支援 PNG、JPG、JPEG、SVG 格式，最大 {{ $maxSize }}
    </p>
    
    {{-- 上傳進度 --}}
    <div wire:loading wire:target="{{ $attributes->whereStartsWith('wire:')->first() }}" class="text-sm text-blue-600">
        <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-blue-600 inline" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
        上傳中...
    </div>
    
    {{-- 新圖片預覽 --}}
    <div wire:loading.remove wire:target="{{ $attributes->whereStartsWith('wire:')->first() }}">
        <div x-data="{ imagePreview: null }" x-init="
            $watch('$wire.{{ str_replace('wire:', '', $attributes->whereStartsWith('wire:')->first()) }}', value => {
                if (value) {
                    const reader = new FileReader();
                    reader.onload = e => imagePreview = e.target.result;
                    reader.readAsDataURL(value);
                } else {
                    imagePreview = null;
                }
            })
        ">
            <div x-show="imagePreview" class="mt-3">
                <p class="text-sm text-green-600 mb-2">新圖片預覽：</p>
                <img :src="imagePreview" alt="新圖片預覽" class="h-32 w-32 object-cover rounded-lg border">
            </div>
        </div>
    </div>
</div>