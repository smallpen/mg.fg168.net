@props([
    'wire:model' => null,
    'id' => null,
    'options' => [],
    'required' => false,
    'error' => false,
    'placeholder' => '請選擇...'
])

<select {{ $attributes->merge([
            'class' => 'block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm' . ($error ? ' border-red-300' : '')
        ]) }}
        @if($id) id="{{ $id }}" @endif
        @if($required) required @endif
        {{ $attributes->whereStartsWith('wire:') }}>
    
    @if(!$required && $placeholder)
        <option value="">{{ $placeholder }}</option>
    @endif
    
    @foreach($options as $value => $label)
        <option value="{{ $value }}">{{ $label }}</option>
    @endforeach
</select>