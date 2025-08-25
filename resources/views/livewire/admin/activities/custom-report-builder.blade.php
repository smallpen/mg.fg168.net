<div class="space-y-6">
    {{-- 頁面標題 --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white">自訂統計報告</h2>
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                建立自訂的活動統計報告，選擇指標、篩選條件和匯出格式
            </p>
        </div>
        
        <div class="mt-4 sm:mt-0 flex space-x-3">
            <button wire:click="resetConfig" 
                    class="inline-flex items-center px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
                <x-heroicon-s-arrow-path class="w-4 h-4 mr-1"/>
                重設
            </button>
            
            <button wire:click="saveTemplate" 
                    class="inline-flex items-center px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
                <x-heroicon-s-bookmark class="w-4 h-4 mr-1"/>
                儲存範本
            </button>
            
            <button wire:click="generateReport" 
                    class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700">
                <x-heroicon-s-document-arrow-down class="w-4 h-4 mr-1"/>
                生成報告
            </button>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- 左側：報告設定 --}}
        <div class="lg:col-span-2 space-y-6">
            {{-- 基本設定 --}}
            <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">基本設定</h3>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="reportName" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            報告名稱
                        </label>
                        <input wire:model="reportConfig.name" 
                               type="text" 
                               id="reportName"
                               placeholder="輸入報告名稱"
                               class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                    </div>
                    
                    <div>
                        <label for="reportDescription" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            報告描述
                        </label>
                        <input wire:model="reportConfig.description" 
                               type="text" 
                               id="reportDescription"
                               placeholder="輸入報告描述"
                               class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                    </div>
                    
                    <div>
                        <label for="dateFrom" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            開始日期
                        </label>
                        <input wire:model.live="reportConfig.date_from" 
                               type="date" 
                               id="dateFrom"
                               class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                    </div>
                    
                    <div>
                        <label for="dateTo" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            結束日期
                        </label>
                        <input wire:model.live="reportConfig.date_to" 
                               type="date" 
                               id="dateTo"
                               class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                    </div>
                </div>
            </div>

            {{-- 統計指標選擇 --}}
            <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">統計指標</h3>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    @foreach($availableMetrics as $metric => $label)
                        <label class="flex items-center space-x-3 p-3 border border-gray-200 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 cursor-pointer">
                            <input type="checkbox" 
                                   wire:model.live="reportConfig.metrics" 
                                   value="{{ $metric }}"
                                   class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                            <div class="flex-1">
                                <div class="text-sm font-medium text-gray-900 dark:text-white">
                                    {{ $label }}
                                </div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">
                                    {{ $this->getMetricDescription($metric) }}
                                </div>
                            </div>
                        </label>
                    @endforeach
                </div>
            </div>

            {{-- 篩選條件 --}}
            <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">篩選條件</h3>
                
                <div class="space-y-4">
                    @foreach($availableFilters as $filter => $label)
                        <div class="flex items-center space-x-3">
                            <label class="w-32 text-sm font-medium text-gray-700 dark:text-gray-300">
                                {{ $label }}:
                            </label>
                            <input wire:model.live="reportConfig.filters.{{ $filter }}" 
                                   type="text" 
                                   placeholder="輸入篩選值"
                                   class="flex-1 rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm">
                            @if(isset($reportConfig['filters'][$filter]) && !empty($reportConfig['filters'][$filter]))
                                <button wire:click="removeFilter('{{ $filter }}')" 
                                        class="text-red-600 hover:text-red-700">
                                    <x-heroicon-s-x-mark class="w-4 h-4"/>
                                </button>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- 圖表類型 --}}
            <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">圖表類型</h3>
                
                <div class="grid grid-cols-2 md:grid-cols-3 gap-3">
                    @foreach($availableChartTypes as $chartType => $label)
                        <label class="flex items-center space-x-2 p-3 border border-gray-200 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 cursor-pointer">
                            <input type="checkbox" 
                                   wire:model.live="reportConfig.chart_types" 
                                   value="{{ $chartType }}"
                                   class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                            <span class="text-sm text-gray-900 dark:text-white">{{ $label }}</span>
                        </label>
                    @endforeach
                </div>
            </div>

            {{-- 匯出格式 --}}
            <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">匯出格式</h3>
                
                <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                    @foreach($availableExportFormats as $format => $label)
                        <label class="flex items-center space-x-2 p-3 border border-gray-200 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 cursor-pointer">
                            <input type="checkbox" 
                                   wire:model.live="reportConfig.export_formats" 
                                   value="{{ $format }}"
                                   class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                            <span class="text-sm text-gray-900 dark:text-white">{{ $label }}</span>
                        </label>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- 右側：預覽和範本 --}}
        <div class="space-y-6">
            {{-- 儲存的範本 --}}
            @if(count($savedTemplates) > 0)
                <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">儲存的範本</h3>
                    
                    <div class="space-y-3">
                        @foreach($savedTemplates as $template)
                            <div class="flex items-center justify-between p-3 border border-gray-200 dark:border-gray-600 rounded-lg">
                                <div class="flex-1">
                                    <div class="text-sm font-medium text-gray-900 dark:text-white">
                                        {{ $template['name'] }}
                                    </div>
                                    @if(!empty($template['description']))
                                        <div class="text-xs text-gray-500 dark:text-gray-400">
                                            {{ $template['description'] }}
                                        </div>
                                    @endif
                                    <div class="text-xs text-gray-400 dark:text-gray-500">
                                        {{ Carbon\Carbon::parse($template['created_at'])->diffForHumans() }}
                                    </div>
                                </div>
                                <div class="flex space-x-1">
                                    <button wire:click="loadTemplate('{{ $template['id'] }}')" 
                                            class="text-blue-600 hover:text-blue-700">
                                        <x-heroicon-s-arrow-down-tray class="w-4 h-4"/>
                                    </button>
                                    <button wire:click="deleteTemplate('{{ $template['id'] }}')" 
                                            class="text-red-600 hover:text-red-700">
                                        <x-heroicon-s-trash class="w-4 h-4"/>
                                    </button>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- 預覽區域 --}}
            @if($showPreview && $previewData)
                <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white">報告預覽</h3>
                        <button wire:click="updatePreview" 
                                class="text-sm text-blue-600 hover:text-blue-700">
                            <x-heroicon-s-arrow-path class="w-4 h-4 inline mr-1"/>
                            更新預覽
                        </button>
                    </div>
                    
                    {{-- 摘要資訊 --}}
                    <div class="space-y-3 mb-4">
                        <div class="text-sm">
                            <span class="font-medium text-gray-700 dark:text-gray-300">時間範圍:</span>
                            <span class="text-gray-600 dark:text-gray-400">{{ $previewData['summary']['date_range'] }}</span>
                        </div>
                        <div class="text-sm">
                            <span class="font-medium text-gray-700 dark:text-gray-300">統計指標:</span>
                            <span class="text-gray-600 dark:text-gray-400">{{ $previewData['summary']['total_metrics'] }} 個</span>
                        </div>
                        @if(isset($previewData['summary']['total_activities']))
                            <div class="text-sm">
                                <span class="font-medium text-gray-700 dark:text-gray-300">總活動數:</span>
                                <span class="text-gray-600 dark:text-gray-400">{{ number_format($previewData['summary']['total_activities']) }}</span>
                            </div>
                        @endif
                    </div>
                    
                    {{-- 指標預覽 --}}
                    <div class="space-y-2">
                        @foreach($previewData['data'] as $metric => $data)
                            <div class="flex items-center justify-between p-2 bg-gray-50 dark:bg-gray-700 rounded">
                                <span class="text-sm font-medium text-gray-700 dark:text-gray-300">
                                    {{ $availableMetrics[$metric] ?? $metric }}
                                </span>
                                <span class="text-sm text-gray-600 dark:text-gray-400">
                                    @if(is_array($data) && isset($data['total']))
                                        {{ number_format($data['total']) }}
                                    @elseif(is_numeric($data))
                                        {{ number_format($data) }}
                                    @else
                                        有資料
                                    @endif
                                </span>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- 快速設定 --}}
            <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">快速設定</h3>
                
                <div class="space-y-3">
                    <button wire:click="$set('reportConfig.metrics', ['total_activities', 'unique_users', 'security_events'])" 
                            class="w-full text-left p-3 border border-gray-200 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700">
                        <div class="text-sm font-medium text-gray-900 dark:text-white">基本統計報告</div>
                        <div class="text-xs text-gray-500 dark:text-gray-400">總活動數、活躍使用者、安全事件</div>
                    </button>
                    
                    <button wire:click="$set('reportConfig.metrics', ['activity_by_type', 'activity_by_module', 'hourly_distribution'])" 
                            class="w-full text-left p-3 border border-gray-200 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700">
                        <div class="text-sm font-medium text-gray-900 dark:text-white">分佈分析報告</div>
                        <div class="text-xs text-gray-500 dark:text-gray-400">類型分佈、模組分佈、時間分佈</div>
                    </button>
                    
                    <button wire:click="$set('reportConfig.metrics', ['security_events', 'risk_analysis', 'top_users'])" 
                            class="w-full text-left p-3 border border-gray-200 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700">
                        <div class="text-sm font-medium text-gray-900 dark:text-white">安全分析報告</div>
                        <div class="text-xs text-gray-500 dark:text-gray-400">安全事件、風險分析、活躍使用者</div>
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- 載入指示器 --}}
    <div wire:loading class="fixed inset-0 bg-gray-600 bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white dark:bg-gray-800 rounded-lg p-6 flex items-center space-x-3">
            <div class="animate-spin rounded-full h-6 w-6 border-b-2 border-blue-600"></div>
            <span class="text-gray-900 dark:text-white">處理中...</span>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('livewire:initialized', function () {
    // 監聽報告生成完成事件
    Livewire.on('report-generated', (event) => {
        // 顯示下載連結
        const files = event.files;
        let message = '報告已生成完成！\n\n';
        
        files.forEach(file => {
            message += `${file.format.toUpperCase()}: ${file.filename}\n`;
        });
        
        if (confirm(message + '\n是否要立即下載所有檔案？')) {
            files.forEach(file => {
                setTimeout(() => {
                    const link = document.createElement('a');
                    link.href = file.url;
                    link.download = file.filename;
                    document.body.appendChild(link);
                    link.click();
                    document.body.removeChild(link);
                }, 100);
            });
        }
    });
    
    // 監聽預覽更新事件
    Livewire.on('preview-updated', (event) => {
        console.log('Preview updated:', event);
    });
});
</script>
@endpush