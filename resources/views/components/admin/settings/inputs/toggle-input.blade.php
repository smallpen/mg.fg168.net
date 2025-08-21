@props([
    'wire:model' => null,
    'id' => null,
    'label' => '啟用此設定',
    'required' => false
])

<div class="flex items-center">
    <input type="checkbox"
           {{ $attributes->merge([
               'class' => 'h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded'
           ]) }}
           @if($id) id="{{ $id }}" @endif
           @if($required) required @endif
           {{ $attributes->whereStartsWith('wire:') }}>
    
    @if($label && $id)
        <label for="{{ $id }}" class="ml-2 block text-sm text-gray-900">
            {{ $label }}
        </label>
    @elseif($label)
        <span class="ml-2 block text-sm text-gray-900">
            {{ $label }}
        </span>
    @endif
</div>