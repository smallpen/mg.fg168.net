<div class="bg-white rounded-lg shadow-sm border border-gray-200">
    <!-- 統計資訊 -->
    <div class="px-6 py-4 border-b border-gray-200">
        <div class="flex justify-end">
            <div class="flex items-center space-x-2">
                @if($totalRecords > 0)
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                        {{ number_format($totalRecords) }} 筆記錄
                    </span>
                @endif
                @if($estimatedSize > 0)
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                        約 {{ $this->formatFileSize($estimatedSize) }}
                    </span>
                @endif
            </div>
        </div>
    </div>

    <div class="p-6">
        <!-- 匯出格式選擇 -->
        <div class="mb-6">
            <label class="block text-sm font-medium text-gray-700 mb-3">匯出格式</label>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                @foreach($availableFormats as $format => $label)
                    <label class="relative flex cursor-pointer rounded-lg border p-4 focus:outline-none {{ $exportFormat === $format ? 'border-blue-600 ring-2 ring-blue-600' : 'border-gray-300' }}">
                        <input type="radio" wire:model.live="exportFormat" wire:key="export-format-{{ $format }}" name="exportFormat" value="{{ $format }}" class="sr-only">
                        <span class="flex flex-1">
                            <span class="flex flex-col">
                                <span class="block text-sm font-medium text-gray-900">
                                    {{ $label }}
                                </span>
                                <span class="mt-1 flex items-center text-sm text-gray-500">
                                    @switch($format)
                                        @case('csv')
                                            適合 Excel 分析和資料處理
                                            @break
                                        @case('json')
                                            適合程式處理和 API 整合
                                            @break
                                        @case('pdf')
                                            適合報告列印和歸檔
                                            <br><span class="text-orange-600 text-xs">⚠️ 中文字符可能顯示為方框</span>
                                            @break
                                        @case('html')
                                            <span class="text-green-600 font-medium">推薦：完美支援中文，可列印</span>
                                            @break
                                    @endswitch
                                </span>
                            </span>
                        </span>
                        @if($exportFormat === $format)
                            <svg class="h-5 w-5 text-blue-600" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                            </svg>
                        @endif
                    </label>
                @endforeach
            </div>
        </div>

        <!-- 時間範圍設定 -->
        <div class="mb-6">
            <label class="block text-sm font-medium text-gray-700 mb-3">時間範圍</label>
            <div class="grid grid-cols-2 md:grid-cols-5 gap-3 mb-4">
                @foreach($timeRangeOptions as $range => $label)
                    <label class="relative flex cursor-pointer rounded-md border p-3 focus:outline-none {{ $timeRange === $range ? 'border-blue-600 ring-1 ring-blue-600 bg-blue-50' : 'border-gray-300' }}">
                        <input type="radio" wire:model.live="timeRange" wire:key="time-range-{{ $range }}" name="timeRange" value="{{ $range }}" class="sr-only">
                        <span class="flex flex-1 justify-center">
                            <span class="block text-sm font-medium {{ $timeRange === $range ? 'text-blue-900' : 'text-gray-900' }}">
                                {{ $label }}
                            </span>
                        </span>
                    </label>
                @endforeach
            </div>

            @if($timeRange === 'custom')
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">開始日期</label>
                        <input type="date" wire:model.live="dateFrom" wire:key="date-from-input" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">結束日期</label>
                        <input type="date" wire:model.live="dateTo" wire:key="date-to-input" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                    </div>
                </div>
            @endif
        </div>

        <!-- 篩選條件 -->
        <div class="mb-6">
            <div class="flex items-center justify-between mb-3">
                <label class="block text-sm font-medium text-gray-700">篩選條件</label>
                <button type="button" wire:click="resetFilters" class="text-sm text-blue-600 hover:text-blue-500">
                    重設篩選
                </button>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <!-- 使用者篩選 -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">使用者</label>
                    <select wire:model.live="userFilter" wire:key="user-filter-select" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                        <option value="">全部使用者</option>
                        @foreach($this->userOptions as $userId => $userName)
                            <option value="{{ $userId }}">{{ $userName }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- 活動類型篩選 -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">活動類型</label>
                    <select wire:model.live="typeFilter" wire:key="type-filter-select" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                        <option value="">全部類型</option>
                        @foreach($this->typeOptions as $type => $typeName)
                            <option value="{{ $type }}">{{ $typeName }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- 模組篩選 -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">模組</label>
                    <select wire:model.live="moduleFilter" wire:key="module-filter-select" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                        <option value="">全部模組</option>
                        @foreach($this->moduleOptions as $module => $moduleName)
                            <option value="{{ $module }}">{{ $moduleName }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- 結果篩選 -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">操作結果</label>
                    <select wire:model.live="resultFilter" wire:key="result-filter-select" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                        <option value="">全部結果</option>
                        <option value="success">成功</option>
                        <option value="failed">失敗</option>
                        <option value="warning">警告</option>
                        <option value="error">錯誤</option>
                    </select>
                </div>

                <!-- IP 位址篩選 -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">IP 位址</label>
                    <input type="text" wire:model.live="ipFilter" wire:key="ip-filter-input" placeholder="輸入 IP 位址" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                </div>

                <!-- 風險等級篩選 -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">風險等級</label>
                    <select wire:model.live="riskLevelFilter" wire:key="risk-level-filter-select" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                        <option value="">全部等級</option>
                        <option value="high">高風險 (≥7)</option>
                        <option value="6">高 (6)</option>
                        <option value="5">中高 (5)</option>
                        <option value="4">中 (4)</option>
                        <option value="3">中低 (3)</option>
                        <option value="2">低 (2)</option>
                        <option value="1">極低 (1)</option>
                    </select>
                </div>
            </div>

            <!-- 安全事件篩選 -->
            <div class="mt-4">
                <label class="flex items-center">
                    <input type="checkbox" wire:model.live="securityEventsOnly" wire:key="security-events-checkbox" class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    <span class="ml-2 text-sm text-gray-700">僅匯出安全事件</span>
                </label>
            </div>
        </div>

        <!-- 匯出選項 -->
        <div class="mb-6">
            <label class="block text-sm font-medium text-gray-700 mb-3">匯出選項</label>
            <div class="space-y-3">
                <label class="flex items-center">
                    <input type="checkbox" wire:model.live="includeUserDetails" wire:key="include-user-details-checkbox" class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    <span class="ml-2 text-sm text-gray-700">包含使用者詳細資訊</span>
                </label>
                <label class="flex items-center">
                    <input type="checkbox" wire:model.live="includeProperties" wire:key="include-properties-checkbox" class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    <span class="ml-2 text-sm text-gray-700">包含活動詳細屬性</span>
                </label>
                <label class="flex items-center">
                    <input type="checkbox" wire:model.live="includeRelatedData" wire:key="include-related-data-checkbox" class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    <span class="ml-2 text-sm text-gray-700">包含相關資料</span>
                </label>
            </div>

            @if($totalRecords > $batchSize)
                <div class="mt-4 p-3 bg-yellow-50 border border-yellow-200 rounded-md">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-yellow-400" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div class="ml-3">
                            <h3 class="text-sm font-medium text-yellow-800">大量資料匯出</h3>
                            <div class="mt-2 text-sm text-yellow-700">
                                <p>資料量較大 ({{ number_format($totalRecords) }} 筆)，將使用批量處理，預估需要 {{ $estimatedTime }}。</p>
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        </div>

        <!-- 統計資訊 -->
        @if($totalRecords > 0)
            <div class="mb-6 p-4 bg-gray-50 rounded-lg">
                <h4 class="text-sm font-medium text-gray-900 mb-2">匯出預覽</h4>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                    <div>
                        <span class="text-gray-500">記錄數量：</span>
                        <span class="font-medium">{{ number_format($totalRecords) }}</span>
                    </div>
                    <div>
                        <span class="text-gray-500">預估大小：</span>
                        <span class="font-medium">{{ $this->formatFileSize($estimatedSize) }}</span>
                    </div>
                    <div>
                        <span class="text-gray-500">預估時間：</span>
                        <span class="font-medium">{{ $estimatedTime }}</span>
                    </div>
                    <div>
                        <span class="text-gray-500">處理方式：</span>
                        <span class="font-medium">{{ $totalRecords > $batchSize ? '批量處理' : '直接匯出' }}</span>
                    </div>
                </div>
            </div>
        @endif

        <!-- 匯出進度 -->
        @if($isExporting)
            <div class="mb-6 p-4 bg-blue-50 border border-blue-200 rounded-lg">
                <div class="flex items-center justify-between mb-2">
                    <h4 class="text-sm font-medium text-blue-900">匯出進度</h4>
                    <button type="button" wire:click="cancelExport" class="text-sm text-red-600 hover:text-red-500">
                        取消匯出
                    </button>
                </div>
                <div class="mb-2">
                    <div class="flex justify-between text-sm text-blue-700">
                        <span>{{ $exportStatus }}</span>
                        <span>{{ $exportProgress }}%</span>
                    </div>
                    <div class="mt-1 bg-blue-200 rounded-full h-2">
                        <div class="bg-blue-600 h-2 rounded-full transition-all duration-300" style="width: {{ $exportProgress }}%"></div>
                    </div>
                </div>
            </div>
        @endif

        <!-- 下載區域 -->
        @if($downloadUrl && !$isExporting)
            <div class="mb-6 p-4 bg-green-50 border border-green-200 rounded-lg">
                <div class="flex items-center justify-between">
                    <div>
                        <h4 class="text-sm font-medium text-green-900">匯出完成</h4>
                        <p class="text-sm text-green-700">檔案已準備就緒，可以下載</p>
                    </div>
                    <button type="button" wire:click="downloadExport" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                        <svg class="-ml-1 mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        下載檔案
                    </button>
                </div>
            </div>
        @endif

        <!-- 操作按鈕 -->
        <div class="flex items-center justify-between">
            <div>
                @if($totalRecords === 0)
                    <p class="text-sm text-gray-500">沒有符合條件的記錄可匯出</p>
                @elseif($totalRecords > $maxRecords)
                    <p class="text-sm text-red-600">記錄數量超過限制 ({{ number_format($maxRecords) }})，請縮小範圍</p>
                @endif
            </div>
            <div class="flex space-x-3">
                <button type="button" wire:click="resetFilters" class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    重設
                </button>
                <button type="button" wire:click="startExport" 
                    @disabled($isExporting || $totalRecords === 0 || $totalRecords > $maxRecords)
                    class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed">
                    @if($isExporting)
                        <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        匯出中...
                    @else
                        <svg class="-ml-1 mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        開始匯出
                    @endif
                </button>
            </div>
        </div>

        <!-- 匯出歷史 -->
        @if(count($exportHistory) > 0)
            <div class="mt-8 border-t border-gray-200 pt-6">
                <div class="flex items-center justify-between mb-4">
                    <h4 class="text-sm font-medium text-gray-900">最近匯出</h4>
                    <button type="button" wire:click="clearExportHistory" class="text-sm text-gray-500 hover:text-gray-700">
                        清除歷史
                    </button>
                </div>
                <div class="space-y-3">
                    @foreach($exportHistory as $export)
                        <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                            <div class="flex-1">
                                <div class="flex items-center space-x-2">
                                    <span class="text-sm font-medium text-gray-900">{{ $export['filename'] }}</span>
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-800">
                                        {{ strtoupper($export['format']) }}
                                    </span>
                                </div>
                                <div class="mt-1 text-xs text-gray-500">
                                    {{ number_format($export['record_count']) }} 筆記錄 • 
                                    {{ $this->formatFileSize($export['file_size']) }} • 
                                    {{ \Carbon\Carbon::parse($export['completed_at'])->diffForHumans() }}
                                </div>
                            </div>
                            @if(isset($export['file_path']) && \Storage::exists($export['file_path']))
                                <a href="{{ \Storage::url($export['file_path']) }}" 
                                   class="inline-flex items-center px-3 py-1 border border-gray-300 text-xs font-medium rounded text-gray-700 bg-white hover:bg-gray-50">
                                    下載
                                </a>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    </div>
</div>

@script
<script>
    // 監聽匯出進度更新
    $wire.on('export-progress-updated', (data) => {
        // 可以在這裡加入額外的前端處理邏輯
        console.log('Export progress:', data);
    });

    // 監聽匯出完成
    $wire.on('export-completed', (data) => {
        // 可以在這裡加入額外的前端處理邏輯
        console.log('Export completed:', data);
    });

    // 監聽匯出失敗
    $wire.on('export-failed', (data) => {
        // 可以在這裡加入額外的前端處理邏輯
        console.log('Export failed:', data);
    });

    // 監聽下載檔案事件
    $wire.on('download-file', (data) => {
        if (data.url) {
            const link = document.createElement('a');
            link.href = data.url;
            link.download = data.filename || '';
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }
    });

    // 監聽活動匯出重置事件
    $wire.on('activity-export-reset', () => {
        console.log('🔄 收到 activity-export-reset 事件，手動更新前端...');
        
        // 重置其他表單元素（radio buttons 單獨處理）
        const formElements = [
            // 日期輸入框
            'input[wire\\:key="date-from-input"]',
            'input[wire\\:key="date-to-input"]',
            // 篩選下拉選單
            'select[wire\\:key="user-filter-select"]',
            'select[wire\\:key="type-filter-select"]',
            'select[wire\\:key="module-filter-select"]',
            'select[wire\\:key="result-filter-select"]',
            'select[wire\\:key="risk-level-filter-select"]',
            // IP 篩選輸入框
            'input[wire\\:key="ip-filter-input"]',
            // 核取方塊
            'input[wire\\:key="security-events-checkbox"]',
            'input[wire\\:key="include-user-details-checkbox"]',
            'input[wire\\:key="include-properties-checkbox"]',
            'input[wire\\:key="include-related-data-checkbox"]'
        ];
        
        // 重置匯出格式 radio group
        const exportFormatRadios = document.querySelectorAll('input[wire\\:model\\.live="exportFormat"]');
        exportFormatRadios.forEach(radio => {
            if (radio.value === 'csv') {
                radio.checked = true;
                radio.dispatchEvent(new Event('change', { bubbles: true }));
            } else {
                radio.checked = false;
            }
        });
        
        // 重置時間範圍 radio group
        const timeRangeRadios = document.querySelectorAll('input[wire\\:model\\.live="timeRange"]');
        timeRangeRadios.forEach(radio => {
            if (radio.value === '7d') {
                radio.checked = true;
                radio.dispatchEvent(new Event('change', { bubbles: true }));
            } else {
                radio.checked = false;
            }
        });
        
        // 重置其他表單元素
        formElements.forEach(selector => {
            const elements = document.querySelectorAll(selector);
            elements.forEach(element => {
                if (element.type === 'checkbox') {
                    // 重置核取方塊 - 根據預設值設定
                    const defaultChecked = element.getAttribute('wire:key').includes('include-user-details') || 
                                          element.getAttribute('wire:key').includes('include-properties');
                    element.checked = defaultChecked;
                    element.dispatchEvent(new Event('change', { bubbles: true }));
                } else if (element.tagName === 'SELECT') {
                    // 重置下拉選單為第一個選項
                    element.selectedIndex = 0;
                    element.dispatchEvent(new Event('change', { bubbles: true }));
                } else if (element.type === 'text' || element.type === 'date') {
                    // 清空文字和日期輸入框
                    element.value = '';
                    element.dispatchEvent(new Event('input', { bubbles: true }));
                }
                
                // 觸發 blur 事件確保同步
                element.blur();
            });
        });
        
        // 延遲刷新以確保同步
        setTimeout(() => {
            console.log('🔄 ActivityExport 延遲刷新執行');
            $wire.$refresh();
        }, 500);
    });

    // 簡化的事件處理
    document.addEventListener('DOMContentLoaded', function() {
        console.log('ActivityExport 頁面已載入');
    });
</script>
@endscript