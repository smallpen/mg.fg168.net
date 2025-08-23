@props([
    'wire:model' => null,
    'id' => null,
    'placeholder' => '',
    'required' => false,
    'error' => false,
    'showToggle' => true
])

<div class="relative">
    <input type="password"
           x-data="{ show: false }"
           :type="show ? 'text' : 'password'"
           {{ $attributes->merge([
               'class' => 'block w-full pr-10 border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm' . ($error ? ' border-red-300' : '')
           ]) }}
           @if($id) id="{{ $id }}" @endif
           @if($placeholder) placeholder="{{ $placeholder }}" @endif
           @if($required) required @endif
           {{ $attributes->whereStartsWith('wire:') }}>
    
    @if($showToggle)
        <div class="absolute inset-y-0 right-0 pr-3 flex items-center">
            <button type="button"
                    x-on:click="show = !show"
                    class="text-gray-400 hover:text-gray-500 focus:outline-none focus:text-gray-500">
                <svg x-show="!show" class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                </svg>
                <svg x-show="show" class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="display: none;">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.878 9.878L3 3m6.878 6.878L21 21"></path>
                </svg>
            </button>
        </div>
    @endif
</div>