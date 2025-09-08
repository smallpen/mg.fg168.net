<div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg">
    <div class="px-4 py-5 sm:p-6">
        <form wire:submit="save">
            <div class="grid grid-cols-1 gap-6">
                <!-- 基本資訊 -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- 標題 -->
                    <div>
                        <label for="title" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            標題 <span class="text-red-500">*</span>
                        </label>
                        <input type="text" 
                               id="title"
                               wire:model.live="title"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white @error('title') border-red-500 @enderror">
                        @error('title')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- 類型 -->
                    <div>
                        <label for="type" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            類型 <span class="text-red-500">*</span>
                        </label>
                        <select id="type" 
                                wire:model.live="type"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white @error('type') border-red-500 @enderror">
                            @foreach($typeOptions as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('type')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <!-- 訊息內容 -->
                <div>
                    <label for="message" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                        訊息內容 <span class="text-red-500">*</span>
                    </label>
                    <textarea id="message" 
                              wire:model.live="message"
                              rows="4"
                              class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white @error('message') border-red-500 @enderror"
                              placeholder="輸入通知的詳細內容..."></textarea>
                    @error('message')
                        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <!-- 設定選項 -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- 優先級 -->
                    <div>
                        <label for="priority" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            優先級 <span class="text-red-500">*</span>
                        </label>
                        <select id="priority" 
                                wire:model.live="priority"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white @error('priority') border-red-500 @enderror">
                            @foreach($priorityOptions as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('priority')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- 圖示 -->
                    <div>
                        <label for="icon" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            圖示 (CSS 類別)
                        </label>
                        <input type="text" 
                               id="icon"
                               wire:model.live="icon"
                               placeholder="例如: fas fa-bell"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                        @error('icon')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <!-- 進階設定 -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- 顏色 -->
                    <div>
                        <label for="color" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            顏色 (CSS 類別)
                        </label>
                        <input type="text" 
                               id="color"
                               wire:model.live="color"
                               placeholder="例如: text-blue-500"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                        @error('color')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- 操作連結 -->
                    <div>
                        <label for="actionUrl" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            操作連結
                        </label>
                        <input type="url" 
                               id="actionUrl"
                               wire:model.live="actionUrl"
                               placeholder="https://example.com/action"
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                        @error('actionUrl')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <!-- 瀏覽器通知選項 -->
                <div>
                    <div class="flex items-center">
                        <input type="checkbox" 
                               id="isBrowserNotification"
                               wire:model.live="isBrowserNotification"
                               class="rounded border-gray-300 text-primary-600 focus:ring-primary-500">
                        <label for="isBrowserNotification" class="ml-2 text-sm text-gray-700 dark:text-gray-300">
                            啟用瀏覽器通知
                        </label>
                    </div>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        勾選此選項將會在使用者瀏覽器中顯示通知
                    </p>
                </div>

                @if(!$isEdit)
                    <!-- 收件人選擇 -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">
                            收件人 <span class="text-red-500">*</span>
                        </label>
                        
                        <!-- 全選選項 -->
                        <div class="mb-4">
                            <div class="flex items-center">
                                <input type="checkbox" 
                                       id="sendToAll"
                                       wire:model.live="sendToAll"
                                       class="rounded border-gray-300 text-primary-600 focus:ring-primary-500">
                                <label for="sendToAll" class="ml-2 text-sm text-gray-700 dark:text-gray-300">
                                    發送給所有使用者
                                </label>
                            </div>
                        </div>

                        @if(!$sendToAll)
                            <!-- 使用者選擇 -->
                            <div class="max-h-60 overflow-y-auto border border-gray-300 dark:border-gray-600 rounded-md p-3 @error('selectedUsers') border-red-500 @enderror">
                                @foreach($users as $user)
                                    <div class="flex items-center py-2">
                                        <input type="checkbox" 
                                               id="user_{{ $user->id }}"
                                               wire:model.live="selectedUsers" 
                                               value="{{ $user->id }}"
                                               class="rounded border-gray-300 text-primary-600 focus:ring-primary-500">
                                        <label for="user_{{ $user->id }}" class="ml-2 text-sm text-gray-700 dark:text-gray-300">
                                            {{ $user->name }} ({{ $user->username }})
                                        </label>
                                    </div>
                                @endforeach
                            </div>
                            @error('selectedUsers')
                                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        @endif
                    </div>
                @endif
            </div>

            <!-- 表單按鈕 -->
            <div class="mt-6 flex justify-end space-x-3">
                <button type="button" 
                        wire:click="cancel"
                        class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 disabled:opacity-25 transition ease-in-out duration-150">
                    取消
                </button>
                <button type="submit" 
                        class="inline-flex items-center px-4 py-2 bg-primary-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-primary-700 focus:bg-primary-700 active:bg-primary-900 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 transition ease-in-out duration-150">
                    @if($isEdit)
                        更新通知
                    @else
                        發送通知
                    @endif
                </button>
            </div>
        </form>
    </div>
</div>