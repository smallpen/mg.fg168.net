<div class="space-y-6">
    {{-- 表單卡片 --}}
    <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg">
        <div class="px-4 py-5 sm:p-6">
            <form wire:submit="save">
                <div class="space-y-6">
                    {{-- 基本資訊區塊 --}}
                    <div>
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">
                            基本資訊
                        </h3>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            {{-- 代理姓名 --}}
                            <div>
                                <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                    代理姓名 <span class="text-red-500">*</span>
                                </label>
                                <input 
                                    type="text" 
                                    id="name"
                                    wire:model.blur="name"
                                    class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-white sm:text-sm @error('name') border-red-300 focus:border-red-500 focus:ring-red-500 @enderror"
                                    placeholder="請輸入代理姓名"
                                />
                                @error('name')
                                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                            </div>

                            {{-- 使用者名稱 --}}
                            <div>
                                <label for="username" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                    使用者名稱 <span class="text-red-500">*</span>
                                </label>
                                <input 
                                    type="text" 
                                    id="username"
                                    wire:model.blur="username"
                                    class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-white sm:text-sm @error('username') border-red-300 focus:border-red-500 focus:ring-red-500 @enderror"
                                    placeholder="請輸入使用者名稱"
                                />
                                @error('username')
                                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                    只能包含英文字母、數字和底線
                                </p>
                            </div>

                            {{-- 電子郵件 --}}
                            <div>
                                <label for="email" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                    電子郵件 <span class="text-red-500">*</span>
                                </label>
                                <input 
                                    type="email" 
                                    id="email"
                                    wire:model.blur="email"
                                    class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-white sm:text-sm @error('email') border-red-300 focus:border-red-500 focus:ring-red-500 @enderror"
                                    placeholder="請輸入電子郵件"
                                />
                                @error('email')
                                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                            </div>

                            {{-- 電話號碼 --}}
                            <div>
                                <label for="phone" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                    電話號碼
                                </label>
                                <input 
                                    type="text" 
                                    id="phone"
                                    wire:model.blur="phone"
                                    class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-white sm:text-sm @error('phone') border-red-300 focus:border-red-500 focus:ring-red-500 @enderror"
                                    placeholder="請輸入電話號碼"
                                />
                                @error('phone')
                                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    </div>

                    {{-- 層級設定區塊 --}}
                    @if(!$isEdit)
                        <div>
                            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">
                                層級設定
                            </h3>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                {{-- 上層代理選擇 --}}
                                @if($showParentField)
                                    <div class="md:col-span-2">
                                        <label for="parent_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                            上層代理
                                        </label>
                                        <select 
                                            id="parent_id"
                                            wire:model.live="parent_id"
                                            class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-white sm:text-sm @error('parent_id') border-red-300 focus:border-red-500 focus:ring-red-500 @enderror"
                                        >
                                            <option value="">選擇上層代理（留空表示建立第一層代理）</option>
                                            @foreach($parentOptions as $parentAgent)
                                                <option value="{{ $parentAgent->id }}">
                                                    {{ $this->getParentDisplayText($parentAgent) }} - 剩餘點數: {{ number_format($parentAgent->remaining_points, 2) }}
                                                </option>
                                            @endforeach
                                        </select>
                                        @error('parent_id')
                                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                        @enderror
                                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                            選擇上層代理將建立下層代理，留空將建立第一層代理
                                        </p>
                                    </div>
                                @endif

                                {{-- 前置符號選擇 --}}
                                @if($showPrefixField)
                                    <div>
                                        <label for="prefix" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                            前置符號 <span class="text-red-500">*</span>
                                        </label>
                                        <select 
                                            id="prefix"
                                            wire:model.live="prefix"
                                            class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-white sm:text-sm @error('prefix') border-red-300 focus:border-red-500 focus:ring-red-500 @enderror"
                                        >
                                            <option value="">請選擇前置符號</option>
                                            @foreach($prefixOptions as $value => $label)
                                                <option value="{{ $value }}">{{ $label }}</option>
                                            @endforeach
                                        </select>
                                        @error('prefix')
                                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                        @enderror
                                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                            第一層代理需要選擇唯一的前置符號（可用選項：{{ count($prefixOptions) }} 個）
                                        </p>
                                    </div>
                                @else
                                    {{-- 偵錯資訊：顯示為什麼前置符號欄位沒有顯示 --}}
                                    @if(config('app.debug'))
                                        <div class="text-xs text-gray-400 p-2 bg-gray-100 dark:bg-gray-800 rounded">
                                            偵錯：前置符號欄位隱藏 - showPrefixField: {{ $showPrefixField ? 'true' : 'false' }}, parent_id: {{ $parent_id ?? 'null' }}, isEdit: {{ $isEdit ? 'true' : 'false' }}
                                        </div>
                                    @endif
                                @endif

                                {{-- 初始點數 --}}
                                <div>
                                    <label for="initial_points" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                        初始點數 <span class="text-red-500">*</span>
                                    </label>
                                    <input 
                                        type="number" 
                                        id="initial_points"
                                        wire:model.blur="initial_points"
                                        step="0.01"
                                        min="0"
                                        max="999999999.99"
                                        class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-white sm:text-sm @error('initial_points') border-red-300 focus:border-red-500 focus:ring-red-500 @enderror"
                                        placeholder="0.00"
                                    />
                                    @error('initial_points')
                                        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                    @enderror
                                    @if($parent_id)
                                        @php
                                            $pointsCheck = $this->checkParentPoints();
                                        @endphp
                                        <p class="mt-1 text-xs {{ $pointsCheck['sufficient'] ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                                            上層代理可用點數：{{ number_format($pointsCheck['available'], 2) }}
                                        </p>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endif

                    {{-- 帳號預覽 --}}
                    @if($previewAccount)
                        <div>
                            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">
                                帳號預覽
                            </h3>
                            <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                                <div class="flex items-center">
                                    <svg class="w-5 h-5 text-gray-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                    </svg>
                                    <span class="text-sm text-gray-600 dark:text-gray-400">完整帳號：</span>
                                    <span class="ml-2 text-lg font-mono font-medium text-gray-900 dark:text-white">{{ $previewAccount }}</span>
                                </div>
                            </div>
                        </div>
                    @endif

                    {{-- 其他設定區塊 --}}
                    <div>
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">
                            其他設定
                        </h3>
                        
                        <div class="space-y-4">
                            {{-- 啟用狀態 --}}
                            <div class="flex items-center">
                                <input 
                                    type="checkbox" 
                                    id="is_active"
                                    wire:model="is_active"
                                    class="h-4 w-4 text-primary-600 focus:ring-primary-500 border-gray-300 dark:border-gray-600 rounded dark:bg-gray-700"
                                />
                                <label for="is_active" class="ml-2 block text-sm text-gray-700 dark:text-gray-300">
                                    啟用代理
                                </label>
                            </div>

                            {{-- 備註 --}}
                            <div>
                                <label for="notes" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                    備註
                                </label>
                                <textarea 
                                    id="notes"
                                    wire:model.blur="notes"
                                    rows="3"
                                    class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:text-white sm:text-sm @error('notes') border-red-300 focus:border-red-500 focus:ring-red-500 @enderror"
                                    placeholder="請輸入備註資訊..."
                                ></textarea>
                                @error('notes')
                                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                    最多 1000 個字元
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- 表單按鈕 --}}
                <div class="mt-8 flex justify-end space-x-3">
                    <button 
                        type="button"
                        wire:click="cancel"
                        class="inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 transition-colors duration-200"
                    >
                        取消
                    </button>
                    
                    <button 
                        type="submit"
                        class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 transition-colors duration-200 disabled:opacity-50 disabled:cursor-not-allowed"
                        wire:loading.attr="disabled"
                    >
                        <svg wire:loading class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span wire:loading.remove>
                            {{ $isEdit ? '更新代理' : '建立代理' }}
                        </span>
                        <span wire:loading>
                            {{ $isEdit ? '更新中...' : '建立中...' }}
                        </span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- 說明資訊 --}}
    <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4">
        <div class="flex">
            <div class="flex-shrink-0">
                <svg class="h-5 w-5 text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                </svg>
            </div>
            <div class="ml-3">
                <h3 class="text-sm font-medium text-blue-800 dark:text-blue-200">
                    代理建立說明
                </h3>
                <div class="mt-2 text-sm text-blue-700 dark:text-blue-300">
                    <ul class="list-disc list-inside space-y-1">
                        @if(!$isEdit)
                            <li>第一層代理需要選擇唯一的前置符號（a-z），此符號將用於生成完整帳號</li>
                            <li>下層代理會自動繼承上層代理的前置符號，無需另外設定</li>
                            <li>初始點數將從上層代理的剩餘點數中扣除，請確保上層代理有足夠點數</li>
                            <li>代理建立後可以進一步建立其下層代理和管理玩家</li>
                        @else
                            <li>編輯模式下無法變更代理的層級結構和前置符號</li>
                            <li>如需調整點數，請使用專門的點數管理功能</li>
                            <li>變更使用者名稱會同步更新所有下層代理和玩家的帳號</li>
                        @endif
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>