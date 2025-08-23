<div>
    <form wire:submit.prevent="save" class="space-y-8">
        <div class="space-y-6">
            {{-- 預設主題 --}}
            <div>
                <label for="default_theme" class="block text-sm font-medium text-gray-700 dark:text-gray-300">預設主題</label>
                <select id="default_theme" wire:model.defer="settings.appearance.default_theme" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-800 dark:border-gray-600 dark:text-white sm:text-sm">
                    <option value="light">亮色主題</option>
                    <option value="dark">暗色主題</option>
                    <option value="auto">自動（跟隨系統）</option>
                </select>
                <p class="mt-2 text-sm text-gray-500">系統預設主題模式</p>
            </div>

            {{-- 顏色設定 --}}
            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                <div>
                    <label for="primary_color" class="block text-sm font-medium text-gray-700 dark:text-gray-300">主要顏色</label>
                    <input type="color" id="primary_color" wire:model.defer="settings.appearance.primary_color" class="mt-1 h-10 w-full rounded-md border-gray-300 dark:bg-gray-800 dark:border-gray-600">
                    @error('settings.appearance.primary_color') <span class="text-sm text-red-500">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label for="secondary_color" class="block text-sm font-medium text-gray-700 dark:text-gray-300">次要顏色</label>
                    <input type="color" id="secondary_color" wire:model.defer="settings.appearance.secondary_color" class="mt-1 h-10 w-full rounded-md border-gray-300 dark:bg-gray-800 dark:border-gray-600">
                     @error('settings.appearance.secondary_color') <span class="text-sm text-red-500">{{ $message }}</span> @enderror
                </div>
            </div>

            {{-- 檔案上傳 --}}
            <div class="space-y-6">
                <div>
                    <label for="logo" class="block text-sm font-medium text-gray-700 dark:text-gray-300">系統標誌 (Logo)</label>
                    <input type="file" id="logo" wire:model="logo" class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 dark:file:bg-indigo-900/20 dark:file:text-indigo-300 dark:hover:file:bg-indigo-900/40">
                    @if($settings['appearance.logo_url'] && !$logo) <img src="{{ asset('storage/' . $settings['appearance.logo_url']) }}" alt="Current Logo" class="mt-2 h-10"> @endif
                    @error('logo') <span class="text-sm text-red-500">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label for="favicon" class="block text-sm font-medium text-gray-700 dark:text-gray-300">網站圖示 (Favicon)</label>
                    <input type="file" id="favicon" wire:model="favicon" class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 dark:file:bg-indigo-900/20 dark:file:text-indigo-300 dark:hover:file:bg-indigo-900/40">
                    @if($settings['appearance.favicon_url'] && !$favicon) <img src="{{ asset('storage/' . $settings['appearance.favicon_url']) }}" alt="Current Favicon" class="mt-2 h-8 w-8"> @endif
                    @error('favicon') <span class="text-sm text-red-500">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label for="login_background" class="block text-sm font-medium text-gray-700 dark:text-gray-300">登入頁面背景</label>
                    <input type="file" id="login_background" wire:model="loginBackground" class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 dark:file:bg-indigo-900/20 dark:file:text-indigo-300 dark:hover:file:bg-indigo-900/40">
                    @if($settings['appearance.login_background_url'] && !$loginBackground) <img src="{{ asset('storage/' . $settings['appearance.login_background_url']) }}" alt="Current Login Background" class="mt-2 h-20 object-cover"> @endif
                    @error('loginBackground') <span class="text-sm text-red-500">{{ $message }}</span> @enderror
                </div>
            </div>

            {{-- 頁面標題格式 --}}
            <div>
                <label for="page_title_format" class="block text-sm font-medium text-gray-700 dark:text-gray-300">頁面標題格式</label>
                <input type="text" id="page_title_format" wire:model.defer="settings.appearance.page_title_format" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-800 dark:border-gray-600 dark:text-white sm:text-sm">
                <p class="mt-2 text-sm text-gray-500">可使用 {page} 和 {app} 作為預留位置。</p>
            </div>

            {{-- 自訂 CSS --}}
            <div>
                <label for="custom_css" class="block text-sm font-medium text-gray-700 dark:text-gray-300">自訂 CSS</label>
                <textarea id="custom_css" wire:model.defer="settings.appearance.custom_css" rows="10" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-800 dark:border-gray-600 dark:text-white sm:text-sm font-mono"></textarea>
                <p class="mt-2 text-sm text-gray-500">自訂 CSS 將會載入到所有頁面。</p>
            </div>

            {{-- 響應式設計設定 --}}
            <div class="space-y-6">
                <div>
                    <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">響應式設計設定</h3>
                    <p class="mt-1 text-sm text-gray-500">設定不同裝置的斷點和響應式功能</p>
                </div>

                {{-- 斷點設定 --}}
                <div class="grid grid-cols-1 gap-6 sm:grid-cols-3">
                    <div>
                        <label for="mobile_breakpoint" class="block text-sm font-medium text-gray-700 dark:text-gray-300">手機斷點 (px)</label>
                        <input type="number" id="mobile_breakpoint" wire:model.defer="responsiveConfig.mobile_breakpoint" min="320" max="1024" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-800 dark:border-gray-600 dark:text-white sm:text-sm">
                        <p class="mt-1 text-xs text-gray-500">建議值：768px</p>
                    </div>
                    <div>
                        <label for="tablet_breakpoint" class="block text-sm font-medium text-gray-700 dark:text-gray-300">平板斷點 (px)</label>
                        <input type="number" id="tablet_breakpoint" wire:model.defer="responsiveConfig.tablet_breakpoint" min="768" max="1440" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-800 dark:border-gray-600 dark:text-white sm:text-sm">
                        <p class="mt-1 text-xs text-gray-500">建議值：1024px</p>
                    </div>
                    <div>
                        <label for="desktop_breakpoint" class="block text-sm font-medium text-gray-700 dark:text-gray-300">桌面斷點 (px)</label>
                        <input type="number" id="desktop_breakpoint" wire:model.defer="responsiveConfig.desktop_breakpoint" min="1024" max="2560" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-800 dark:border-gray-600 dark:text-white sm:text-sm">
                        <p class="mt-1 text-xs text-gray-500">建議值：1280px</p>
                    </div>
                </div>

                {{-- 響應式功能開關 --}}
                <div class="space-y-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <label for="enable_mobile_menu" class="text-sm font-medium text-gray-700 dark:text-gray-300">啟用手機選單</label>
                            <p class="text-sm text-gray-500">在小螢幕裝置上顯示摺疊式選單</p>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" wire:model.defer="responsiveConfig.enable_mobile_menu" class="sr-only peer">
                            <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-indigo-300 dark:peer-focus:ring-indigo-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-indigo-600"></div>
                        </label>
                    </div>
                    <div class="flex items-center justify-between">
                        <div>
                            <label for="enable_responsive_tables" class="text-sm font-medium text-gray-700 dark:text-gray-300">啟用響應式表格</label>
                            <p class="text-sm text-gray-500">在小螢幕上自動調整表格顯示方式</p>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" wire:model.defer="responsiveConfig.enable_responsive_tables" class="sr-only peer">
                            <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-indigo-300 dark:peer-focus:ring-indigo-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-indigo-600"></div>
                        </label>
                    </div>
                    <div class="flex items-center justify-between">
                        <div>
                            <label for="enable_touch_friendly" class="text-sm font-medium text-gray-700 dark:text-gray-300">啟用觸控友善介面</label>
                            <p class="text-sm text-gray-500">增大按鈕和連結的觸控區域</p>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" wire:model.defer="responsiveConfig.enable_touch_friendly" class="sr-only peer">
                            <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-indigo-300 dark:peer-focus:ring-indigo-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-indigo-600"></div>
                        </label>
                    </div>
                </div>
            </div>

        </div>

        {{-- 即時預覽區域 --}}
        @if($previewMode)
            <div class="mt-8 rounded-lg border border-gray-200 bg-gray-50 p-6 dark:bg-gray-800 dark:border-gray-700">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">即時預覽</h3>
                    <div class="flex space-x-2">
                        <button wire:click="switchPreviewDevice('mobile')" 
                                class="px-3 py-1 text-xs font-medium rounded-md {{ $previewDevice === 'mobile' ? 'bg-indigo-600 text-white' : 'bg-white text-gray-700 border border-gray-300' }}">
                            手機
                        </button>
                        <button wire:click="switchPreviewDevice('tablet')" 
                                class="px-3 py-1 text-xs font-medium rounded-md {{ $previewDevice === 'tablet' ? 'bg-indigo-600 text-white' : 'bg-white text-gray-700 border border-gray-300' }}">
                            平板
                        </button>
                        <button wire:click="switchPreviewDevice('desktop')" 
                                class="px-3 py-1 text-xs font-medium rounded-md {{ $previewDevice === 'desktop' ? 'bg-indigo-600 text-white' : 'bg-white text-gray-700 border border-gray-300' }}">
                            桌面
                        </button>
                    </div>
                </div>

                {{-- 預覽框架 --}}
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden"
                     style="
                        width: {{ $previewDevice === 'mobile' ? '375px' : ($previewDevice === 'tablet' ? '768px' : '100%') }};
                        max-width: 100%;
                        margin: 0 auto;
                        transition: width 0.3s ease;
                     ">
                    
                    {{-- 預覽標頭 --}}
                    <div class="flex items-center justify-between p-4 border-b border-gray-200"
                         style="background-color: {{ $settings['appearance.primary_color'] ?? '#3B82F6' }}; color: white;">
                        <div class="flex items-center space-x-3">
                            @if($settings['appearance.logo_url'])
                                <img src="{{ asset('storage/' . $settings['appearance.logo_url']) }}" alt="Logo" class="h-8 w-8 rounded">
                            @else
                                <div class="h-8 w-8 rounded bg-white bg-opacity-20"></div>
                            @endif
                            <span class="font-semibold">{{ $settings['app.name'] ?? 'Laravel Admin System' }}</span>
                        </div>
                        @if($previewDevice === 'mobile' && $responsiveConfig['enable_mobile_menu'])
                            <button class="p-2 rounded-md bg-white bg-opacity-20">
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                                </svg>
                            </button>
                        @endif
                    </div>

                    {{-- 預覽內容 --}}
                    <div class="p-4 space-y-4">
                        <div class="flex space-x-2">
                            <button class="px-4 py-2 text-sm font-medium text-white rounded-md"
                                    style="background-color: {{ $settings['appearance.primary_color'] ?? '#3B82F6' }}">
                                主要按鈕
                            </button>
                            <button class="px-4 py-2 text-sm font-medium rounded-md border"
                                    style="border-color: {{ $settings['appearance.secondary_color'] ?? '#6B7280' }}; color: {{ $settings['appearance.secondary_color'] ?? '#6B7280' }}">
                                次要按鈕
                            </button>
                        </div>
                        
                        <div class="space-y-2">
                            <h4 class="text-base font-medium text-gray-900">範例內容</h4>
                            <p class="text-sm text-gray-600">這是預覽內容，展示外觀設定的效果。</p>
                            <a href="#" class="text-sm font-medium hover:underline"
                               style="color: {{ $settings['appearance.primary_color'] ?? '#3B82F6' }}">
                                範例連結
                            </a>
                        </div>

                        {{-- 響應式表格預覽 --}}
                        @if($responsiveConfig['enable_responsive_tables'])
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200 text-sm">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th class="px-3 py-2 text-left font-medium text-gray-500">欄位 1</th>
                                            <th class="px-3 py-2 text-left font-medium text-gray-500">欄位 2</th>
                                            <th class="px-3 py-2 text-left font-medium text-gray-500">欄位 3</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200">
                                        <tr>
                                            <td class="px-3 py-2 text-gray-900">資料 1</td>
                                            <td class="px-3 py-2 text-gray-900">資料 2</td>
                                            <td class="px-3 py-2 text-gray-900">資料 3</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </div>
                </div>

                {{-- 預覽資訊 --}}
                <div class="mt-4 text-xs text-gray-500">
                    <p>目前預覽裝置：{{ $previewDevice === 'mobile' ? '手機' : ($previewDevice === 'tablet' ? '平板' : '桌面') }}</p>
                    <p>斷點設定：手機 ≤ {{ $responsiveConfig['mobile_breakpoint'] }}px，平板 ≤ {{ $responsiveConfig['tablet_breakpoint'] }}px，桌面 ≥ {{ $responsiveConfig['desktop_breakpoint'] }}px</p>
                </div>
            </div>
        @endif

        <div class="pt-5">
            <div class="flex justify-between">
                <div class="flex space-x-3">
                    <button type="button" wire:click="togglePreview" 
                            class="inline-flex items-center rounded-md border border-gray-300 bg-white py-2 px-4 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:bg-gray-700 dark:text-gray-200 dark:border-gray-600 dark:hover:bg-gray-600">
                        @if($previewMode)
                            <svg class="mr-2 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.878 9.878L8.464 8.464M9.878 9.878l-1.414-1.414M14.12 14.12l1.414 1.414M14.12 14.12L15.536 15.536M14.12 14.12l1.414 1.414" />
                            </svg>
                            關閉預覽
                        @else
                            <svg class="mr-2 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                            </svg>
                            即時預覽
                        @endif
                    </button>
                    <button type="button" wire:click="resetToDefaults" 
                            class="inline-flex items-center rounded-md border border-gray-300 bg-white py-2 px-4 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:bg-gray-700 dark:text-gray-200 dark:border-gray-600 dark:hover:bg-gray-600">
                        <svg class="mr-2 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                        </svg>
                        重設預設值
                    </button>
                </div>
                <div class="flex space-x-3">
                    <button type="button" wire:click="loadSettings" class="rounded-md border border-gray-300 bg-white py-2 px-4 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:bg-gray-700 dark:text-gray-200 dark:border-gray-600 dark:hover:bg-gray-600">取消</button>
                    <button type="submit" class="ml-3 inline-flex justify-center rounded-md border border-transparent bg-indigo-600 py-2 px-4 text-sm font-medium text-white shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                        <span wire:loading.remove wire:target="save">儲存</span>
                        <span wire:loading wire:target="save">儲存中...</span>
                    </button>
                </div>
            </div>
        </div>
    </form>
</div>
