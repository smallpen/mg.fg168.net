<div class="space-y-6">
    {{-- 頁面標題 --}}
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white">
                {{ __('permissions.test.title') }}
            </h2>
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                {{ __('permissions.test.description') }}
            </p>
        </div>
        
        @if(!empty($testResults))
            <div class="flex space-x-2">
                <button 
                    wire:click="exportReport"
                    class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                >
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                    {{ __('permissions.test.export_report') }}
                </button>
                
                <button 
                    wire:click="clearResults"
                    class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 dark:bg-gray-800 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-700"
                >
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                    </svg>
                    {{ __('permissions.test.clear_results') }}
                </button>
            </div>
        @endif
    </div>

    {{-- 測試表單 --}}
    <div class="bg-white dark:bg-gray-800 shadow rounded-lg">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <h3 class="text-lg font-medium text-gray-900 dark:text-white">
                {{ __('permissions.test.test_configuration') }}
            </h3>
        </div>
        
        <div class="p-6 space-y-6">
            {{-- 測試模式選擇 --}}
            <div>
                <label class="text-base font-medium text-gray-900 dark:text-white">
                    {{ __('permissions.test.test_mode') }}
                </label>
                <p class="text-sm leading-5 text-gray-500 dark:text-gray-400">
                    {{ __('permissions.test.test_mode_description') }}
                </p>
                <fieldset class="mt-4">
                    <legend class="sr-only">{{ __('permissions.test.test_mode') }}</legend>
                    <div class="space-y-4 sm:flex sm:items-center sm:space-y-0 sm:space-x-10">
                        <div class="flex items-center">
                            <input 
                                id="test-mode-user" 
                                name="test-mode" 
                                type="radio" 
                                value="user"
                                wire:model.live="testMode"
                                class="focus:ring-blue-500 h-4 w-4 text-blue-600 border-gray-300 dark:border-gray-600"
                            >
                            <label for="test-mode-user" class="ml-3 block text-sm font-medium text-gray-700 dark:text-gray-300">
                                {{ __('permissions.test.user_permission') }}
                            </label>
                        </div>
                        <div class="flex items-center">
                            <input 
                                id="test-mode-role" 
                                name="test-mode" 
                                type="radio" 
                                value="role"
                                wire:model.live="testMode"
                                class="focus:ring-blue-500 h-4 w-4 text-blue-600 border-gray-300 dark:border-gray-600"
                            >
                            <label for="test-mode-role" class="ml-3 block text-sm font-medium text-gray-700 dark:text-gray-300">
                                {{ __('permissions.test.role_permission') }}
                            </label>
                        </div>
                    </div>
                </fieldset>
            </div>

            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                {{-- 使用者/角色選擇 --}}
                <div>
                    @if($testMode === 'user')
                        <label for="selectedUserId" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            {{ __('permissions.test.select_user') }}
                        </label>
                        <select 
                            id="selectedUserId"
                            wire:model="selectedUserId"
                            class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm rounded-md dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                        >
                            <option value="0">{{ __('permissions.test.choose_user') }}</option>
                            @foreach($this->users as $user)
                                <option value="{{ $user['id'] }}">{{ $user['display_name'] }}</option>
                            @endforeach
                        </select>
                        @error('selectedUserId')
                            <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    @else
                        <label for="selectedRoleId" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            {{ __('permissions.test.select_role') }}
                        </label>
                        <select 
                            id="selectedRoleId"
                            wire:model="selectedRoleId"
                            class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm rounded-md dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                        >
                            <option value="0">{{ __('permissions.test.choose_role') }}</option>
                            @foreach($this->roles as $role)
                                <option value="{{ $role['id'] }}">
                                    {{ $role['display_name'] }} ({{ $role['user_count'] }} {{ __('permissions.test.users') }})
                                </option>
                            @endforeach
                        </select>
                        @error('selectedRoleId')
                            <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    @endif
                </div>

                {{-- 權限選擇 --}}
                <div>
                    <label for="permissionToTest" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                        {{ __('permissions.test.select_permission') }}
                    </label>
                    <select 
                        id="permissionToTest"
                        wire:model="permissionToTest"
                        class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm rounded-md dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                    >
                        <option value="">{{ __('permissions.test.choose_permission') }}</option>
                        @foreach($this->permissions as $moduleData)
                            <optgroup label="{{ $moduleData['module'] }}">
                                @foreach($moduleData['permissions'] as $permission)
                                    <option value="{{ $permission['name'] }}">
                                        {{ $permission['display_name'] }} ({{ $permission['type'] }})
                                    </option>
                                @endforeach
                            </optgroup>
                        @endforeach
                    </select>
                    @error('permissionToTest')
                        <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            {{-- 執行測試按鈕 --}}
            <div class="flex justify-end">
                <button 
                    wire:click="{{ $testMode === 'user' ? 'testUserPermission' : 'testRolePermission' }}"
                    wire:loading.attr="disabled"
                    class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed"
                >
                    <svg wire:loading wire:target="{{ $testMode === 'user' ? 'testUserPermission' : 'testRolePermission' }}" class="animate-spin -ml-1 mr-3 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <svg wire:loading.remove wire:target="{{ $testMode === 'user' ? 'testUserPermission' : 'testRolePermission' }}" class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    {{ __('permissions.test.run_test') }}
                </button>
            </div>

            @error('test')
                <div class="rounded-md bg-red-50 p-4 dark:bg-red-900/20">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm text-red-800 dark:text-red-200">{{ $message }}</p>
                        </div>
                    </div>
                </div>
            @enderror
        </div>
    </div>

    {{-- 測試結果 --}}
    @if(!empty($testResults))
        <div class="bg-white dark:bg-gray-800 shadow rounded-lg">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <div class="flex items-center justify-between">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white">
                        {{ __('permissions.test.test_results') }}
                    </h3>
                    <span class="text-sm text-gray-500 dark:text-gray-400">
                        {{ __('permissions.test.tested_at') }}: {{ $testResults['tested_at'] }}
                    </span>
                </div>
            </div>
            
            <div class="p-6">
                {{-- 測試結果摘要 --}}
                <div class="mb-6">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            @if($testResults['summary']['result_class'] === 'success')
                                <svg class="h-8 w-8 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            @else
                                <svg class="h-8 w-8 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            @endif
                        </div>
                        <div class="ml-4">
                            <h4 class="text-lg font-medium {{ $testResults['summary']['result_class'] === 'success' ? 'text-green-800 dark:text-green-200' : 'text-red-800 dark:text-red-200' }}">
                                {{ $testResults['summary']['result_text'] }}
                            </h4>
                            <p class="text-sm text-gray-600 dark:text-gray-400">
                                {{ $testResults['summary']['details'] }}
                            </p>
                        </div>
                    </div>
                </div>

                {{-- 測試詳情 --}}
                <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                    <div>
                        <h5 class="text-sm font-medium text-gray-900 dark:text-white mb-2">
                            {{ $testResults['type'] === 'user' ? __('permissions.test.tested_user') : __('permissions.test.tested_role') }}
                        </h5>
                        <div class="bg-gray-50 dark:bg-gray-700 rounded-md p-3">
                            <p class="text-sm font-medium text-gray-900 dark:text-white">
                                {{ $testResults['subject']['name'] }}
                            </p>
                            @if($testResults['type'] === 'user')
                                <p class="text-xs text-gray-500 dark:text-gray-400">
                                    {{ __('permissions.test.username') }}: {{ $testResults['subject']['username'] }}
                                </p>
                            @else
                                <p class="text-xs text-gray-500 dark:text-gray-400">
                                    {{ __('permissions.test.system_name') }}: {{ $testResults['subject']['system_name'] }}
                                </p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">
                                    {{ __('permissions.test.user_count') }}: {{ $testResults['subject']['user_count'] }}
                                </p>
                            @endif
                        </div>
                    </div>

                    <div>
                        <h5 class="text-sm font-medium text-gray-900 dark:text-white mb-2">
                            {{ __('permissions.test.tested_permission') }}
                        </h5>
                        <div class="bg-gray-50 dark:bg-gray-700 rounded-md p-3">
                            <p class="text-sm font-medium text-gray-900 dark:text-white">
                                {{ $testResults['permission']['display_name'] }}
                            </p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                {{ __('permissions.test.system_name') }}: {{ $testResults['permission']['name'] }}
                            </p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                {{ __('permissions.test.module') }}: {{ $testResults['permission']['module'] }} | 
                                {{ __('permissions.test.type') }}: {{ $testResults['permission']['type'] }}
                            </p>
                        </div>
                    </div>
                </div>

                {{-- 權限路徑 --}}
                @if(!empty($permissionPath))
                    <div class="mt-6">
                        <div class="flex items-center justify-between mb-4">
                            <h5 class="text-sm font-medium text-gray-900 dark:text-white">
                                {{ __('permissions.test.permission_path') }}
                            </h5>
                            <button 
                                wire:click="toggleDetailedPath"
                                class="text-sm text-blue-600 hover:text-blue-500 dark:text-blue-400"
                            >
                                {{ $showDetailedPath ? __('permissions.test.hide_details') : __('permissions.test.show_details') }}
                            </button>
                        </div>

                        <div class="space-y-3">
                            @if($testResults['type'] === 'user')
                                @foreach($permissionPath as $userPath)
                                    @if($userPath['type'] === 'super_admin')
                                        <div class="flex items-center p-3 bg-yellow-50 dark:bg-yellow-900/20 rounded-md">
                                            <svg class="h-5 w-5 text-yellow-500 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                                            </svg>
                                            <div>
                                                <p class="text-sm font-medium text-yellow-800 dark:text-yellow-200">
                                                    {{ $userPath['name'] }}
                                                </p>
                                                <p class="text-xs text-yellow-600 dark:text-yellow-300">
                                                    {{ $userPath['description'] }}
                                                </p>
                                            </div>
                                        </div>
                                    @elseif($userPath['type'] === 'role')
                                        <div class="border border-gray-200 dark:border-gray-600 rounded-md p-3">
                                            <div class="flex items-center mb-2">
                                                <svg class="h-4 w-4 text-blue-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                                                </svg>
                                                <span class="text-sm font-medium text-gray-900 dark:text-white">
                                                    {{ __('permissions.test.through_role') }}: {{ $userPath['role_name'] }}
                                                </span>
                                            </div>
                                            
                                            @if($showDetailedPath && !empty($userPath['path']))
                                                <div class="ml-6 space-y-2">
                                                    @foreach($userPath['path'] as $rolePath)
                                                        <div class="text-xs text-gray-600 dark:text-gray-400 p-2 bg-gray-50 dark:bg-gray-700 rounded">
                                                            <span class="font-medium">{{ ucfirst($rolePath['type']) }}:</span>
                                                            {{ $rolePath['permission_display_name'] }}
                                                            @if($rolePath['type'] === 'inherited')
                                                                ({{ __('permissions.test.from_parent') }}: {{ $rolePath['parent_role_name'] }})
                                                            @elseif($rolePath['type'] === 'dependency')
                                                                ({{ __('permissions.test.via_dependency') }}: {{ $rolePath['dependency_display_name'] }})
                                                            @endif
                                                        </div>
                                                    @endforeach
                                                </div>
                                            @endif
                                        </div>
                                    @endif
                                @endforeach
                            @else
                                @foreach($permissionPath as $rolePath)
                                    <div class="border border-gray-200 dark:border-gray-600 rounded-md p-3">
                                        <div class="flex items-center">
                                            @if($rolePath['type'] === 'direct')
                                                <svg class="h-4 w-4 text-green-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                                </svg>
                                                <span class="text-sm text-green-700 dark:text-green-300">
                                                    {{ __('permissions.test.direct_assignment') }}
                                                </span>
                                            @elseif($rolePath['type'] === 'inherited')
                                                <svg class="h-4 w-4 text-blue-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                                                </svg>
                                                <span class="text-sm text-blue-700 dark:text-blue-300">
                                                    {{ __('permissions.test.inherited_from') }}: {{ $rolePath['parent_role_name'] }}
                                                </span>
                                            @elseif($rolePath['type'] === 'dependency')
                                                <svg class="h-4 w-4 text-purple-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16l2.879-2.879m0 0a3 3 0 104.243-4.242 3 3 0 00-4.243 4.242zM21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                                </svg>
                                                <span class="text-sm text-purple-700 dark:text-purple-300">
                                                    {{ __('permissions.test.via_dependency') }}: {{ $rolePath['dependency_display_name'] }}
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            @endif
                        </div>
                    </div>
                @endif
            </div>
        </div>
    @endif
</div>

@push('scripts')
<script>
    document.addEventListener('livewire:init', () => {
        Livewire.on('permission-tested', (event) => {
            const data = event[0];
            const message = data.result 
                ? `✓ ${data.subject} 擁有權限 "${data.permission}"`
                : `✗ ${data.subject} 沒有權限 "${data.permission}"`;
            
            // 可以在這裡添加通知邏輯
            console.log('Permission test result:', message);
        });

        Livewire.on('results-cleared', () => {
            console.log('Test results cleared');
        });
    });
</script>
@endpush