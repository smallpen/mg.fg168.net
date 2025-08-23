@props([
    'wire:model' => null,
    'id' => null,
    'options' => [],
    'required' => false,
    'error' => false,
    'placeholder' => '請選擇...',
    'searchable' => true
])

<div x-data="{
    open: false,
    search: '',
    selected: @entangle($attributes->whereStartsWith('wire:')->first()).defer,
    
    get filteredOptions() {
        if (!this.search) return {{ json_encode($options) }};
        return Object.fromEntries(
            Object.entries({{ json_encode($options) }}).filter(([value, label]) =>
                label.toLowerCase().includes(this.search.toLowerCase())
            )
        );
    },
    
    isSelected(value) {
        return Array.isArray(this.selected) ? this.selected.includes(value) : this.selected === value;
    },
    
    toggle(value) {
        if (Array.isArray(this.selected)) {
            if (this.isSelected(value)) {
                this.selected = this.selected.filter(item => item !== value);
            } else {
                this.selected = [...this.selected, value];
            }
        } else {
            this.selected = this.isSelected(value) ? null : value;
        }
    },
    
    getSelectedLabels() {
        if (!this.selected) return [];
        const selectedArray = Array.isArray(this.selected) ? this.selected : [this.selected];
        return selectedArray.map(value => {{ json_encode($options) }}[value] || value);
    }
}" class="relative">
    
    {{-- 選擇框 --}}
    <div @click="open = !open"
         class="relative w-full cursor-pointer rounded-md border border-gray-300 bg-white py-2 pl-3 pr-10 text-left shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 sm:text-sm {{ $error ? 'border-red-300' : '' }}">
        
        <span class="block truncate" x-text="getSelectedLabels().length > 0 ? getSelectedLabels().join(', ') : '{{ $placeholder }}'"></span>
        
        <span class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-2">
            <svg class="h-5 w-5 text-gray-400" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M10 3a1 1 0 01.707.293l3 3a1 1 0 01-1.414 1.414L10 5.414 7.707 7.707a1 1 0 01-1.414-1.414l3-3A1 1 0 0110 3zm-3.707 9.293a1 1 0 011.414 0L10 14.586l2.293-2.293a1 1 0 011.414 1.414l-3 3a1 1 0 01-1.414 0l-3-3a1 1 0 010-1.414z" clip-rule="evenodd" />
            </svg>
        </span>
    </div>

    {{-- 下拉選單 --}}
    <div x-show="open"
         x-transition:leave="transition ease-in duration-100"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         @click.away="open = false"
         class="absolute z-10 mt-1 max-h-60 w-full overflow-auto rounded-md bg-white py-1 text-base shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none sm:text-sm">
        
        {{-- 搜尋框 --}}
        @if($searchable)
            <div class="sticky top-0 bg-white p-2 border-b">
                <input type="text"
                       x-model="search"
                       placeholder="搜尋選項..."
                       class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
            </div>
        @endif
        
        {{-- 選項列表 --}}
        <template x-for="[value, label] in Object.entries(filteredOptions)" :key="value">
            <div @click="toggle(value)"
                 class="relative cursor-pointer select-none py-2 pl-3 pr-9 hover:bg-gray-100"
                 :class="{ 'bg-blue-100': isSelected(value) }">
                
                <span class="block truncate" :class="{ 'font-medium': isSelected(value) }" x-text="label"></span>
                
                <span x-show="isSelected(value)" class="absolute inset-y-0 right-0 flex items-center pr-4 text-blue-600">
                    <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                    </svg>
                </span>
            </div>
        </template>
        
        {{-- 無選項提示 --}}
        <div x-show="Object.keys(filteredOptions).length === 0" class="py-2 px-3 text-gray-500 text-sm">
            找不到符合的選項
        </div>
    </div>
</div>