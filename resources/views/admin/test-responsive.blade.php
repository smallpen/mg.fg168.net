<x-admin-layout>
    <div class="responsive-container">
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900 dark:text-gray-100 mb-4">響應式設計測試頁面</h1>
            <p class="text-gray-600 dark:text-gray-400">此頁面用於測試響應式設計功能，包括手機版抽屜選單、平板版收合選單、觸控手勢支援和響應式內容適配。</p>
        </div>

        <!-- 響應式資訊顯示 -->
        <div class="responsive-card mb-8">
            <h2 class="text-xl font-semibold mb-4">目前裝置資訊</h2>
            <div class="responsive-grid cols-3">
                <div class="text-center p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
                    <div class="text-2xl font-bold text-blue-600 dark:text-blue-400" x-text="window.innerWidth + 'px'"></div>
                    <div class="text-sm text-gray-600 dark:text-gray-400">螢幕寬度</div>
                </div>
                <div class="text-center p-4 bg-green-50 dark:bg-green-900/20 rounded-lg">
                    <div class="text-2xl font-bold text-green-600 dark:text-green-400" 
                         x-data="{ device: '' }"
                         x-init="
                            const updateDevice = () => {
                                const width = window.innerWidth;
                                if (width < 768) device = '手機';
                                else if (width < 1024) device = '平板';
                                else device = '桌面';
                            };
                            updateDevice();
                            window.addEventListener('resize', updateDevice);
                         "
                         x-text="device"></div>
                    <div class="text-sm text-gray-600 dark:text-gray-400">裝置類型</div>
                </div>
                <div class="text-center p-4 bg-purple-50 dark:bg-purple-900/20 rounded-lg">
                    <div class="text-2xl font-bold text-purple-600 dark:text-purple-400" 
                         x-text="'ontouchstart' in window ? '是' : '否'"></div>
                    <div class="text-sm text-gray-600 dark:text-gray-400">觸控支援</div>
                </div>
            </div>
        </div>

        <!-- 響應式內容測試 -->
        <div class="responsive-card mb-8">
            <h2 class="text-xl font-semibold mb-4">響應式內容適配</h2>
            
            <x-responsive-content
                :mobile="'<div class=&quot;bg-red-100 dark:bg-red-900/20 p-4 rounded-lg&quot;><h3 class=&quot;font-semibold text-red-800 dark:text-red-200&quot;>手機版內容</h3><p class=&quot;text-red-600 dark:text-red-300&quot;>這是專為手機裝置優化的內容，字體較小，佈局緊湊。</p></div>'"
                :tablet="'<div class=&quot;bg-yellow-100 dark:bg-yellow-900/20 p-4 rounded-lg&quot;><h3 class=&quot;font-semibold text-yellow-800 dark:text-yellow-200&quot;>平板版內容</h3><p class=&quot;text-yellow-600 dark:text-yellow-300&quot;>這是專為平板裝置優化的內容，平衡了可讀性和空間利用。</p></div>'"
                :desktop="'<div class=&quot;bg-green-100 dark:bg-green-900/20 p-4 rounded-lg&quot;><h3 class=&quot;font-semibold text-green-800 dark:text-green-200&quot;>桌面版內容</h3><p class=&quot;text-green-600 dark:text-green-300&quot;>這是專為桌面裝置優化的內容，充分利用大螢幕空間，提供豐富的資訊。</p></div>'"
            />
        </div>

        <!-- 響應式圖片測試 -->
        <div class="responsive-card mb-8">
            <h2 class="text-xl font-semibold mb-4">響應式圖片適配</h2>
            <div class="responsive-grid cols-2">
                <div>
                    <h3 class="font-semibold mb-2">封面圖片 (16:9)</h3>
                    <x-responsive-image
                        src="https://via.placeholder.com/800x450/3B82F6/FFFFFF?text=響應式圖片"
                        alt="響應式圖片測試"
                        aspect-ratio="16:9"
                        object-fit="cover"
                        sizes="mobile:100vw tablet:50vw desktop:33vw"
                        class="rounded-lg shadow-md"
                    />
                </div>
                <div>
                    <h3 class="font-semibold mb-2">方形圖片 (1:1)</h3>
                    <x-responsive-image
                        src="https://via.placeholder.com/400x400/10B981/FFFFFF?text=方形圖片"
                        alt="方形圖片測試"
                        aspect-ratio="square"
                        object-fit="cover"
                        sizes="mobile:100vw tablet:50vw desktop:33vw"
                        class="rounded-lg shadow-md"
                    />
                </div>
            </div>
        </div>

        <!-- 觸控手勢測試 -->
        <div class="responsive-card mb-8">
            <h2 class="text-xl font-semibold mb-4">觸控手勢測試</h2>
            <div class="show-mobile">
                <div class="bg-blue-50 dark:bg-blue-900/20 p-6 rounded-lg border-2 border-dashed border-blue-300 dark:border-blue-600">
                    <h3 class="font-semibold text-blue-800 dark:text-blue-200 mb-2">手機版手勢說明</h3>
                    <ul class="text-blue-600 dark:text-blue-300 space-y-1">
                        <li>• 從左邊緣向右滑動：開啟側邊選單</li>
                        <li>• 在側邊選單開啟時向左滑動：關閉選單</li>
                        <li>• 點擊遮罩層：關閉選單</li>
                        <li>• 按 ESC 鍵：關閉選單</li>
                    </ul>
                </div>
            </div>
            
            <div class="show-tablet">
                <div class="bg-yellow-50 dark:bg-yellow-900/20 p-6 rounded-lg border-2 border-dashed border-yellow-300 dark:border-yellow-600">
                    <h3 class="font-semibold text-yellow-800 dark:text-yellow-200 mb-2">平板版操作說明</h3>
                    <ul class="text-yellow-600 dark:text-yellow-300 space-y-1">
                        <li>• 點擊側邊欄右側的切換按鈕：展開/收合選單</li>
                        <li>• 使用 Ctrl+B 快捷鍵：切換側邊欄</li>
                        <li>• 預設為收合狀態以節省空間</li>
                    </ul>
                </div>
            </div>
            
            <div class="show-desktop">
                <div class="bg-green-50 dark:bg-green-900/20 p-6 rounded-lg border-2 border-dashed border-green-300 dark:border-green-600">
                    <h3 class="font-semibold text-green-800 dark:text-green-200 mb-2">桌面版操作說明</h3>
                    <ul class="text-green-600 dark:text-green-300 space-y-1">
                        <li>• 使用 Ctrl+B 快捷鍵：切換側邊欄</li>
                        <li>• 點擊選單按鈕：切換側邊欄</li>
                        <li>• 側邊欄狀態會自動儲存</li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- 響應式表格測試 -->
        <div class="responsive-card mb-8">
            <h2 class="text-xl font-semibold mb-4">響應式表格</h2>
            <div class="responsive-table-container">
                <table class="responsive-table">
                    <thead class="bg-gray-50 dark:bg-gray-800">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                功能
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                手機版
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                平板版
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                桌面版
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-900 divide-y divide-gray-200 dark:divide-gray-700">
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-gray-100" data-label="功能">
                                側邊選單
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400" data-label="手機版">
                                抽屜模式
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400" data-label="平板版">
                                收合模式
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400" data-label="桌面版">
                                標準模式
                            </td>
                        </tr>
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-gray-100" data-label="功能">
                                觸控手勢
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400" data-label="手機版">
                                ✅ 支援
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400" data-label="平板版">
                                ⚠️ 部分支援
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400" data-label="桌面版">
                                ❌ 不支援
                            </td>
                        </tr>
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-gray-100" data-label="功能">
                                響應式圖片
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400" data-label="手機版">
                                ✅ 自動適配
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400" data-label="平板版">
                                ✅ 自動適配
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400" data-label="桌面版">
                                ✅ 自動適配
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- 響應式按鈕測試 -->
        <div class="responsive-card mb-8">
            <h2 class="text-xl font-semibold mb-4">響應式按鈕</h2>
            <div class="flex flex-wrap gap-4">
                <button class="responsive-button bg-blue-600 hover:bg-blue-700 text-white touch-feedback">
                    主要按鈕
                </button>
                <button class="responsive-button bg-gray-600 hover:bg-gray-700 text-white touch-feedback">
                    次要按鈕
                </button>
                <button class="responsive-button bg-green-600 hover:bg-green-700 text-white touch-feedback">
                    成功按鈕
                </button>
                <button class="responsive-button bg-red-600 hover:bg-red-700 text-white touch-feedback">
                    危險按鈕
                </button>
            </div>
        </div>

        <!-- 測試結果 -->
        <div class="responsive-card">
            <h2 class="text-xl font-semibold mb-4">測試結果</h2>
            <div class="space-y-4">
                <div class="flex items-center space-x-2">
                    <div class="w-4 h-4 bg-green-500 rounded-full"></div>
                    <span>響應式 CSS 架構和斷點 - 已實作</span>
                </div>
                <div class="flex items-center space-x-2">
                    <div class="w-4 h-4 bg-green-500 rounded-full"></div>
                    <span>手機版抽屜選單 - 已實作</span>
                </div>
                <div class="flex items-center space-x-2">
                    <div class="w-4 h-4 bg-green-500 rounded-full"></div>
                    <span>平板版收合選單 - 已實作</span>
                </div>
                <div class="flex items-center space-x-2">
                    <div class="w-4 h-4 bg-green-500 rounded-full"></div>
                    <span>觸控手勢支援 - 已實作</span>
                </div>
                <div class="flex items-center space-x-2">
                    <div class="w-4 h-4 bg-green-500 rounded-full"></div>
                    <span>響應式圖片和內容適配 - 已實作</span>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        // 測試頁面專用腳本
        document.addEventListener('DOMContentLoaded', function() {
            console.log('響應式設計測試頁面已載入');
            
            // 監聽視窗大小變化
            window.addEventListener('resize', function() {
                console.log('視窗大小變更:', window.innerWidth + 'x' + window.innerHeight);
            });
            
            // 監聽觸控事件
            if ('ontouchstart' in window) {
                console.log('觸控裝置已偵測');
                
                document.addEventListener('touchstart', function(e) {
                    console.log('觸控開始:', e.touches[0].clientX, e.touches[0].clientY);
                });
            }
        });
    </script>
    @endpush
</x-admin-layout>