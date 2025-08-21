<div class="space-y-6">
    <!-- 標題和統計 -->
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white">角色層級管理</h2>
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                管理角色的層級關係和權限繼承
            </p>
        </div>
        
        <!-- 統計資訊 -->
        <div class="grid grid-cols-2 gap-4 text-center">
            <div class="bg-white dark:bg-gray-800 rounded-lg p-3 shadow">
                <div class="text-2xl font-bold text-indigo-600 dark:text-indigo-400">
                    {{ $this->hierarchyStats['root_roles'] }}
                </div>
                <div class="text-xs text-gray-500 dark:text-gray-400">根角色</div>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-lg p-3 shadow">
                <div class="text-2xl font-bold text-green-600 dark:text-green-400">
                    {{ $this->hierarchyStats['max_depth'] }}
                </div>
                <div class="text-xs text-gray-500 dark:text-gray-400">最大深度</div>
            </div>
        </div>
    </div>

    <!-- 控制面板 -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <!-- 搜尋框 -->
            <div class="flex-1 min-w-64">
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                        </svg>
                    </div>
                    <input 
                        type="text" 
                        wire:model.live.debounce.300ms="search"
                        class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md leading-5 bg-white dark:bg-gray-700 dark:border-gray-600 dark:text-white placeholder-gray-500 focus:outline-none focus:placeholder-gray-400 focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                        placeholder="搜尋角色..."
                    >
                </div>
            </div>

            <!-- 顯示選項 -->
            <div class="flex items-center space-x-4">
                <label class="flex items-center">
                    <input 
                        type="checkbox" 
                        wire:model.live="showInactive"
                        class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded dark:border-gray-600 dark:bg-gray-700"
                    >
                    <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">顯示停用角色</span>
                </label>
                
                <label class="flex items-center">
                    <input 
                        type="checkbox" 
                        wire:model.live="showSystemRoles"
                        class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded dark:border-gray-600 dark:bg-gray-700"
                    >
                    <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">顯示系統角色</span>
                </label>
            </div>

            <!-- 操作按鈕 -->
            <div class="flex items-center space-x-2">
                <button 
                    wire:click="expandAll"
                    class="px-3 py-2 text-xs font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-600"
                >
                    全部展開
                </button>
                <button 
                    wire:click="collapseAll"
                    class="px-3 py-2 text-xs font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-600"
                >
                    全部收合
                </button>
            </div>
        </div>
    </div>

    <!-- 錯誤訊息 -->
    @if($errors->any())
        <div class="bg-red-50 border border-red-200 rounded-md p-4 dark:bg-red-900/20 dark:border-red-800">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                    </svg>
                </div>
                <div class="ml-3">
                    @foreach($errors->all() as $error)
                        <p class="text-sm text-red-800 dark:text-red-200">{{ $error }}</p>
                    @endforeach
                </div>
            </div>
        </div>
    @endif

    <!-- 角色層級樹 -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <h3 class="text-lg font-medium text-gray-900 dark:text-white">角色層級結構</h3>
        </div>
        
        <div class="p-6">
            @if($this->hierarchyTree->isEmpty())
                <div class="text-center py-12">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                    </svg>
                    <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">沒有找到角色</h3>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        @if(!empty($search))
                            沒有符合搜尋條件的角色
                        @else
                            目前沒有任何角色
                        @endif
                    </p>
                </div>
            @else
                <div class="space-y-2">
                    @foreach($this->hierarchyTree as $role)
                        @include('livewire.admin.roles.partials.hierarchy-node', ['role' => $role, 'depth' => 0])
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    <!-- 載入狀態 -->
    <div wire:loading class="fixed inset-0 bg-gray-500 bg-opacity-75 flex items-center justify-center z-50">
        <div class="bg-white dark:bg-gray-800 rounded-lg p-6 flex items-center space-x-3">
            <svg class="animate-spin h-5 w-5 text-indigo-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <span class="text-sm font-medium text-gray-900 dark:text-white">處理中...</span>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('livewire:init', () => {
    // 監聽拖拽事件
    Livewire.on('role-moved', (event) => {
        // 顯示成功訊息
        if (event.message) {
            // 這裡可以整合通知系統
            console.log(event.message);
        }
    });
    
    Livewire.on('roles-bulk-moved', (event) => {
        // 顯示批量移動成功訊息
        if (event.message) {
            console.log(event.message);
        }
    });
});
</script>
@endpush