@props([
    'wire:model' => null,
    'id' => null,
    'placeholder' => '',
    'required' => false,
    'error' => false
])

<input type="email"
       {{ $attributes->merge([
           'class' => 'block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm' . ($error ? ' border-red-300' : '')
       ]) }}
       @if($id) id="{{ $id }}" @endif
       @if($placeholder) placeholder="{{ $placeholder }}" @endif
       @if($required) required @endif
       {{ $attributes->whereStartsWith('wire:') }}>