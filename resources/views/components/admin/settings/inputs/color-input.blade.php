@props([
    'wire:model' => null,
    'id' => null,
    'required' => false,
    'error' => false
])

<div class="flex items-center space-x-3">
    {{-- 顏色選擇器 --}}
    <input type="color"
           {{ $attributes->whereStartsWith('wire:') }}
           class="h-10 w-20 border border-gray-300 rounded cursor-pointer">
    
    {{-- 文字輸入框 --}}
    <input type="text"
           {{ $attributes->merge([
               'class' => 'block flex-1 border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm' . ($error ? ' border-red-300' : '')
           ]) }}
           @if($id) id="{{ $id }}" @endif
           @if($required) required @endif
           placeholder="#000000"
           pattern="^#[0-9A-Fa-f]{6}$"
           {{ $attributes->whereStartsWith('wire:') }}>
</div>