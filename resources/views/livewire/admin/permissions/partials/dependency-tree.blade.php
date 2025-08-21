{{-- 樹狀依賴關係檢視 --}}
<div class="space-y-4">
    {{-- 中心節點 --}}
    <div class="flex items-center justify-center mb-6">
        <div class="bg-blue-100 dark:bg-blue-900 border-2 border-blue-300 dark:border-blue-700 rounded-lg p-4 max-w-sm">
            <div class="text-center">
                <div class="text-sm font-medium text-blue-900 dark:text-blue-100">
                    {{ $selectedPermission->display_name }}
                </div>
                <div class="text-xs text-blue-700 dark:text-blue-300 mt-1">
                    {{ $selectedPermission->name }}
                </div>
                <div class="text-xs text-blue-600 dark:text-blue-400 mt-1">
                    {{ ucfirst($selectedPermission->module) }} • {{ ucfirst($selectedPermission->type) }}
                </div>
            </div>
        </div>
    </div>

    {{-- 依賴關係樹 --}}
    @if($direction === 'dependencies' || $direction === 'both')
        @if(!empty($dependencyTree))
            <div class="mb-8">
                <h4 class="text-lg font-medium text-gray-900 dark:text-white mb-4 flex items-center">
                    <svg class="w-5 h-5 mr-2 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"/>
                    </svg>
                    依賴的權限
                </h4>
                <div class="pl-4 border-l-2 border-green-200 dark:border-green-800">
                    @include('livewire.admin.permissions.partials.tree-node', ['nodes' => $dependencyTree, 'type' => 'dependency'])
                </div>
            </div>
        @else
            <div class="text-center py-4">
                <div class="text-gray-500 dark:text-gray-400 text-sm">
                    此權限沒有依賴其他權限
                </div>
            </div>
        @endif
    @endif

    {{-- 被依賴關係樹 --}}
    @if($direction === 'dependents' || $direction === 'both')
        @if(!empty($dependentTree))
            <div>
                <h4 class="text-lg font-medium text-gray-900 dark:text-white mb-4 flex items-center">
                    <svg class="w-5 h-5 mr-2 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"/>
                    </svg>
                    被依賴的權限
                </h4>
                <div class="pl-4 border-l-2 border-orange-200 dark:border-orange-800">
                    @include('livewire.admin.permissions.partials.tree-node', ['nodes' => $dependentTree, 'type' => 'dependent'])
                </div>
            </div>
        @else
            <div class="text-center py-4">
                <div class="text-gray-500 dark:text-gray-400 text-sm">
                    沒有其他權限依賴此權限
                </div>
            </div>
        @endif
    @endif
</div>