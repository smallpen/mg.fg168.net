@props([
    'wire:model' => null,
    'id' => null,
    'placeholder' => '',
    'rows' => 3,
    'maxlength' => null,
    'required' => false,
    'error' => false
])

<textarea {{ $attributes->merge([
              'class' => 'block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm' . ($error ? ' border-red-300' : '')
          ]) }}
          @if($id) id="{{ $id }}" @endif
          @if($placeholder) placeholder="{{ $placeholder }}" @endif
          @if($maxlength) maxlength="{{ $maxlength }}" @endif
          @if($required) required @endif
          rows="{{ $rows }}"
          {{ $attributes->whereStartsWith('wire:') }}></textarea>