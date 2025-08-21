@props([
    'wire:model' => null,
    'id' => null,
    'min' => null,
    'max' => null,
    'step' => null,
    'required' => false,
    'error' => false
])

<input type="number"
       {{ $attributes->merge([
           'class' => 'block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm' . ($error ? ' border-red-300' : '')
       ]) }}
       @if($id) id="{{ $id }}" @endif
       @if($min !== null) min="{{ $min }}" @endif
       @if($max !== null) max="{{ $max }}" @endif
       @if($step !== null) step="{{ $step }}" @endif
       @if($required) required @endif
       {{ $attributes->whereStartsWith('wire:') }}>