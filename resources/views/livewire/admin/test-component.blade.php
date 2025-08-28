<div class="space-y-6" wire:key="test-component-container" x-data="testComponentHandler"
     @refresh-form-inputs.window="refreshFormInputs()"
     @livewire:update="handleLivewireUpdate()">
    <!-- 基本測試區域 -->
    <div class="bg-yellow-100 border border-yellow-300 rounded-lg p-4" wire:key="basic-test-section">
        <h3 class="text-yellow-800 font-bold mb-2">基本測試元件</h3>
        <p class="mb-3" wire:key="basic-message">{{ $message }}</p>
        
        <div class="mb-4 space-y-2" wire:key="basic-form-container">
            <div wire:key="basic-search-field">
                <label class="block text-sm font-medium text-gray-700" for="basic-test-search">測試搜尋:</label>
                <input type="text" 
                       id="basic-test-search"
                       wire:model.live="testSearch" 
                       wire:key="basic-search-input"
                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
            </div>
            <div wire:key="basic-filter-field">
                <label class="block text-sm font-medium text-gray-700" for="basic-test-filter">測試篩選:</label>
                <select wire:model.live="testFilter" 
                        id="basic-test-filter"
                        wire:key="basic-filter-select"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                    <option value="all">全部</option>
                    <option value="active">啟用</option>
                    <option value="inactive">停用</option>
                </select>
            </div>
            <div class="text-sm text-gray-600" wire:key="basic-status-display">
                當前值: 搜尋="{{$testSearch}}", 篩選="{{$testFilter}}"
            </div>
        </div>
        
        <div class="space-x-2" wire:key="basic-buttons">
            <button wire:click="testAction" 
                    wire:key="basic-test-action-btn"
                    class="px-4 py-2 bg-purple-600 text-white rounded hover:bg-purple-700">
                執行測試動作
            </button>
            
            <button wire:click="testResetFilters" 
                    wire:key="basic-reset-btn"
                    class="px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700">
                重置基本篩選
            </button>
        </div>
    </div>
    
    <!-- UserList 風格測試區域 -->
    <div class="bg-blue-100 border border-blue-300 rounded-lg p-4" wire:key="userlist-test-section">
        <h3 class="text-blue-800 font-bold mb-2">UserList 風格測試</h3>
        
        <div class="mb-4 grid grid-cols-1 md:grid-cols-3 gap-4" wire:key="userlist-form-container">
            <div wire:key="userlist-search-field">
                <label class="block text-sm font-medium text-gray-700" for="userlist-search">搜尋:</label>
                <input type="text" 
                       id="userlist-search"
                       wire:model.live="search" 
                       wire:key="userlist-search-input"
                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm"
                       placeholder="搜尋使用者...">
            </div>
            <div wire:key="userlist-role-field">
                <label class="block text-sm font-medium text-gray-700" for="userlist-role-filter">角色篩選:</label>
                <select wire:model.live="roleFilter" 
                        id="userlist-role-filter"
                        wire:key="userlist-role-select"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                    <option value="">全部角色</option>
                    <option value="super_admin">超級管理員</option>
                    <option value="admin">管理員</option>
                    <option value="user">一般使用者</option>
                </select>
            </div>
            <div wire:key="userlist-status-field">
                <label class="block text-sm font-medium text-gray-700" for="userlist-status-filter">狀態篩選:</label>
                <select wire:model.live="statusFilter" 
                        id="userlist-status-filter"
                        wire:key="userlist-status-select"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                    <option value="">全部狀態</option>
                    <option value="1">啟用</option>
                    <option value="0">停用</option>
                </select>
            </div>
        </div>
        
        <div class="mb-4 text-sm text-gray-600 grid grid-cols-2 gap-4" wire:key="userlist-status-display">
            <div wire:key="userlist-status-left">
                <p>搜尋: "{{ $search }}"</p>
                <p>角色篩選: "{{ $roleFilter }}"</p>
                <p>狀態篩選: "{{ $statusFilter }}"</p>
            </div>
            <div wire:key="userlist-status-right">
                <p>排序: {{ $sortBy }} ({{ $sortDirection }})</p>
                <p>每頁顯示: {{ $perPage }}</p>
            </div>
        </div>
        
        <div class="space-x-2" wire:key="userlist-buttons">
            <button wire:click="resetFilters" 
                    wire:key="userlist-reset-btn"
                    class="px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700">
                重置 UserList 篩選
            </button>
            
            <button wire:click="complexResetFilters" 
                    wire:key="userlist-complex-reset-btn"
                    class="px-4 py-2 bg-orange-600 text-white rounded hover:bg-orange-700">
                複雜重置測試
            </button>
        </div>
    </div>
    
    <!-- 狀態顯示區域 -->
    <div class="bg-gray-100 border border-gray-300 rounded-lg p-4" wire:key="status-monitor-section">
        <h3 class="text-gray-800 font-bold mb-2">即時狀態監控</h3>
        <div class="text-sm text-gray-600 space-y-1" wire:key="status-info">
            <p>元件 ID: {{ $this->getId() }}</p>
            <p>最後更新: {{ now()->format('H:i:s') }}</p>
            <p>Livewire 版本: 3.x</p>
            <p>是否有篩選: {{ ($search || $roleFilter || $statusFilter) ? '是' : '否' }}</p>
        </div>
    </div>
</div>

<script>
function testComponentHandler() {
    return {
        refreshFormInputs() {
            console.log('🔄 收到表單刷新事件');
            
            // 強制刷新所有表單輸入元素
            setTimeout(() => {
                const inputs = [
                    'userlist-search',
                    'userlist-role-filter', 
                    'userlist-status-filter'
                ];
                
                inputs.forEach(id => {
                    const element = document.getElementById(id);
                    if (element) {
                        // 清空值並觸發事件
                        element.value = '';
                        element.dispatchEvent(new Event('input', { bubbles: true }));
                        element.dispatchEvent(new Event('change', { bubbles: true }));
                        console.log(`✅ 已刷新 ${id}`);
                    }
                });
            }, 100);
        },
        
        handleLivewireUpdate() {
            console.log('🔄 Livewire 更新事件');
            // 可以在這裡添加額外的處理邏輯
        }
    }
}
</script>