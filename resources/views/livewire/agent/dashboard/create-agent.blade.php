<div class="bg-white dark:bg-gray-800 shadow rounded-lg">
    <div class="px-4 py-5 sm:p-6">
        <form wire:submit="createAgent">
            <div class="grid grid-cols-1 gap-6">
                <!-- 基本資訊 -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- 代理姓名 -->
                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            代理姓名 <span class="text-red-500">*</span>
                        </label>
                        <input type="text" 
                               wire:model="name"
                               id="name"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white @error('name') border-red-500 @enderror">
                        @error('name')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- 使用者名稱 -->
                    <div>
                        <label for="username" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            使用者名稱 <span class="text-red-500">*</span>
                        </label>
                        <input type="text" 
                               wire:model.live="username"
                               id="username"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white @error('username') border-red-500 @enderror">
                        @error('username')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                        
                        <!-- 預覽完整帳號 -->
                        @if($this->previewAccount)
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                完整帳號：<span class="font-medium">{{ $this->previewAccount }}</span>
                            </p>
                        @endif
                    </div>
                </div>

                <!-- 聯絡資訊 -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- 電子郵件 -->
                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            電子郵件 <span class="text-red-500">*</span>
                        </label>
                        <input type="email" 
                               wire:model="email"
                               id="email"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white @error('email') border-red-500 @enderror">
                        @error('email')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- 電話號碼 -->
                    <div>
                        <label for="phone" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            電話號碼
                        </label>
                        <input type="text" 
                               wire:model="phone"
                               id="phone"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white @error('phone') border-red-500 @enderror">
                        @error('phone')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <!-- 點數分配 -->
                <div>
                    <label for="initial_points" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                        初始點數分配 <span class="text-red-500">*</span>
                    </label>
                    <div class="mt-1 relative rounded-md shadow-sm">
                        <input type="number" 
                               wire:model="initial_points"
                               id="initial_points"
                               step="0.01"
                               min="0"
                               max="{{ $parentAgent->remaining_points }}"
                               class="block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white @error('initial_points') border-red-500 @enderror">
                    </div>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        您的剩餘點數：{{ number_format($parentAgent->remaining_points, 2) }}
                    </p>
                    @error('initial_points')
                        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <!-- 備註 -->
                <div>
                    <label for="notes" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                        備註
                    </label>
                    <textarea wire:model="notes"
                              id="notes"
                              rows="3"
                              class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white @error('notes') border-red-500 @enderror"
                              placeholder="輸入備註資訊..."></textarea>
                    @error('notes')
                        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <!-- 層級資訊 -->
                <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                    <h4 class="text-sm font-medium text-gray-900 dark:text-white mb-2">
                        層級資訊
                    </h4>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
                        <div>
                            <span class="text-gray-500 dark:text-gray-400">上層代理：</span>
                            <span class="font-medium text-gray-900 dark:text-white">{{ $parentAgent->name }}</span>
                        </div>
                        <div>
                            <span class="text-gray-500 dark:text-gray-400">上層帳號：</span>
                            <span class="font-medium text-gray-900 dark:text-white">{{ $parentAgent->account }}</span>
                        </div>
                        <div>
                            <span class="text-gray-500 dark:text-gray-400">新代理層級：</span>
                            <span class="font-medium text-gray-900 dark:text-white">第 {{ $parentAgent->level + 1 }} 層</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 提交按鈕 -->
            <div class="mt-6 flex justify-end space-x-3">
                <a href="{{ route('agent.dashboard.agents') }}" 
                   class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 disabled:opacity-25 transition ease-in-out duration-150 dark:bg-gray-700 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-600">
                    取消
                </a>
                <button type="submit" 
                        class="inline-flex items-center px-4 py-2 bg-primary-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-primary-700 focus:bg-primary-700 active:bg-primary-900 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 transition ease-in-out duration-150">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                    </svg>
                    建立代理
                </button>
            </div>
        </form>
    </div>
</div>