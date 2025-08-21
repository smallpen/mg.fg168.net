{{-- 樹狀節點遞迴模板 --}}
@foreach($nodes as $node)
    <div class="mb-3">
        <div class="flex items-center space-x-3 p-3 rounded-lg border 
                    @if($type === 'dependency') 
                        border-green-200 dark:border-green-800 bg-green-50 dark:bg-green-900/20
                    @else 
                        border-orange-200 dark:border-orange-800 bg-orange-50 dark:bg-orange-900/20
                    @endif">
            
            {{-- 展開/收合按鈕 --}}
            @if(!empty($node['children']))
                <button wire:click="toggleNode('{{ $type }}_{{ $node['id'] }}')" 
                        class="flex-shrink-0 w-6 h-6 rounded-full border-2 
                               @if($type === 'dependency') 
                                   border-green-300 dark:border-green-700 text-green-600 dark:text-green-400 hover:bg-green-100 dark:hover:bg-green-800
                               @else 
                                   border-orange-300 dark:border-orange-700 text-orange-600 dark:text-orange-400 hover:bg-orange-100 dark:hover:bg-orange-800
                               @endif
                               flex items-center justify-center transition-colors duration-200">
                    @if($this->isNodeExpanded($type . '_' . $node['id']))
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"/>
                        </svg>
                    @else
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                        </svg>
                    @endif
                </button>
            @else
                <div class="w-6 h-6 flex-shrink-0"></div>
            @endif

            {{-- 權限資訊 --}}
            <div class="flex-1 min-w-0">
                <div class="flex items-center space-x-2">
                    <div class="font-medium text-gray-900 dark:text-white truncate">
                        {{ $node['display_name'] }}
                    </div>
                    
                    {{-- 系統權限標記 --}}
                    @if($node['is_system'])
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200">
                            系統
                        </span>
                    @endif
                    
                    {{-- 權限類型標記 --}}
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium 
                                 @if($node['type'] === 'view') bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200
                                 @elseif($node['type'] === 'create') bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200
                                 @elseif($node['type'] === 'edit') bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200
                                 @elseif($node['type'] === 'delete') bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200
                                 @elseif($node['type'] === 'manage') bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200
                                 @else bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200
                                 @endif">
                        {{ ucfirst($node['type']) }}
                    </span>
                </div>
                
                <div class="text-sm text-gray-500 dark:text-gray-400 truncate">
                    {{ $node['name'] }} • {{ ucfirst($node['module']) }}
                </div>
            </div>

            {{-- 操作按鈕 --}}
            <div class="flex items-center space-x-2">
                {{-- 選擇此權限 --}}
                <button wire:click="selectPermission({{ $node['id'] }})" 
                        class="p-1 rounded-full text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors duration-200"
                        title="選擇此權限">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                    </svg>
                </button>

                {{-- 移除依賴關係 --}}
                @if($type === 'dependency' && auth()->user()->hasPermission('permissions.edit'))
                    <button wire:click="removeDependency({{ $node['id'] }})" 
                            wire:confirm="確定要移除此依賴關係嗎？"
                            class="p-1 rounded-full text-red-400 hover:text-red-600 dark:hover:text-red-300 transition-colors duration-200"
                            title="移除依賴關係">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                        </svg>
                    </button>
                @endif
            </div>
        </div>

        {{-- 子節點 --}}
        @if(!empty($node['children']) && $this->isNodeExpanded($type . '_' . $node['id']))
            <div class="ml-6 mt-2 pl-4 border-l-2 
                        @if($type === 'dependency') 
                            border-green-200 dark:border-green-800
                        @else 
                            border-orange-200 dark:border-orange-800
                        @endif">
                @include('livewire.admin.permissions.partials.tree-node', ['nodes' => $node['children'], 'type' => $type])
            </div>
        @endif
    </div>
@endforeach