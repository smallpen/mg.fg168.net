@props([
    'id' => '',
    'required' => false,
    'error' => false,
    'placeholder' => '',
    'maxlength' => null
])

<input 
    type="text"
    id="{{ $id }}"
    {{ $attributes->merge([
        'class' => 'block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm' . ($error ? ' border-red-300 text-red-900 placeholder-red-300 focus:border-red-500 focus:ring-red-500' : '')
    ]) }}
    @if($required) required @endif
    @if($placeholder) placeholder="{{ $placeholder }}" @endif
    @if($maxlength) maxlength="{{ $maxlength }}" @endif
/>