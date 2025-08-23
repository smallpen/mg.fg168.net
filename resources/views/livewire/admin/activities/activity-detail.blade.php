<div>
    @if($showModal && $activity)
        <!-- 活動詳情對話框 -->
        <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <!-- 背景遮罩 -->
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" wire:click="closeModal"></div>

                <!-- 對話框內容 -->
                <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-4xl sm:w-full">
                    <!-- 標題列 -->
                    <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4 border-b border-gray-200">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center space-x-3">
                                <div class="flex-shrink-0">
                                    <x-heroicon-o-document-text class="h-6 w-6 text-gray-400" />
                                </div>
                                <div>
                                    <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">
                                        活動詳情 #{{ $activity->id }}
                                    </h3>
                                    <p class="text-sm text-gray-500">
                                        {{ $activity->created_at->format('Y-m-d H:i:s') }}
                                    </p>
                                </div>
                                
                                <!-- 風險等級標籤 -->
                                @if($isSecurityEvent || $securityRiskLevel !== 'low')
                                    <div class="flex items-center space-x-2">
                                        @if($isSecurityEvent)
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                                <x-heroicon-o-shield-exclamation class="w-3 h-3 mr-1" />
                                                安全事件
                                            </span>
                                        @endif
                                        
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                                            @if($riskColor === 'green') bg-green-100 text-green-800
                                            @elseif($riskColor === 'yellow') bg-yellow-100 text-yellow-800
                                            @elseif($riskColor === 'orange') bg-orange-100 text-orange-800
                                            @elseif($riskColor === 'red') bg-red-100 text-red-800
                                            @else bg-gray-100 text-gray-800 @endif">
                                            @if($riskIcon === 'shield-check')
                                                <x-heroicon-o-shield-check class="w-3 h-3 mr-1" />
                                            @elseif($riskIcon === 'exclamation-triangle')
                                                <x-heroicon-o-exclamation-triangle class="w-3 h-3 mr-1" />
                                            @elseif($riskIcon === 'exclamation-circle')
                                                <x-heroicon-o-exclamation-circle class="w-3 h-3 mr-1" />
                                            @elseif($riskIcon === 'fire')
                                                <x-heroicon-o-fire class="w-3 h-3 mr-1" />
                                            @else
                                                <x-heroicon-o-question-mark-circle class="w-3 h-3 mr-1" />
                                            @endif
                                            {{ $activity->risk_level_text }}風險
                                        </span>
                                    </div>
                                @endif
                            </div>

                            <!-- 操作按鈕 -->
                            <div class="flex items-center space-x-2">
                                <!-- 導航按鈕 -->
                                @if($previousActivityId)
                                    <button type="button" wire:click="goToPrevious" 
                                            class="inline-flex items-center p-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                        <x-heroicon-o-chevron-left class="h-4 w-4" />
                                        <span class="sr-only">上一筆</span>
                                    </button>
                                @endif

                                @if($nextActivityId)
                                    <button type="button" wire:click="goToNext" 
                                            class="inline-flex items-center p-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                        <x-heroicon-o-chevron-right class="h-4 w-4" />
                                        <span class="sr-only">下一筆</span>
                                    </button>
                                @endif

                                <!-- 功能按鈕 -->
                                <button type="button" wire:click="copyToClipboard" 
                                        class="inline-flex items-center px-3 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                    <x-heroicon-o-clipboard class="h-4 w-4 mr-1" />
                                    複製
                                </button>

                                <button type="button" wire:click="exportDetail" 
                                        class="inline-flex items-center px-3 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                    <x-heroicon-o-arrow-down-tray class="h-4 w-4 mr-1" />
                                    匯出
                                </button>

                                <button type="button" wire:click="flagAsSuspicious" 
                                        class="inline-flex items-center px-3 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium 
                                        @if($isSuspicious) text-red-700 bg-red-50 border-red-300 hover:bg-red-100 @else text-gray-700 bg-white hover:bg-gray-50 @endif
                                        focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                    <x-heroicon-o-flag class="h-4 w-4 mr-1" />
                                    @if($isSuspicious) 取消標記 @else 標記可疑 @endif
                                </button>

                                <button type="button" wire:click="closeModal" 
                                        class="inline-flex items-center p-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                    <x-heroicon-o-x-mark class="h-4 w-4" />
                                    <span class="sr-only">關閉</span>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- 內容區域 -->
                    <div class="bg-white px-4 pt-5 pb-4 sm:p-6 max-h-96 overflow-y-auto">
                        <div class="space-y-6">
                            <!-- 基本資訊 -->
                            <div>
                                <h4 class="text-sm font-medium text-gray-900 mb-3">基本資訊</h4>
                                <dl class="grid grid-cols-1 gap-x-4 gap-y-3 sm:grid-cols-2">
                                    <div>
                                        <dt class="text-sm font-medium text-gray-500">活動類型</dt>
                                        <dd class="mt-1 text-sm text-gray-900">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                                                @if($activity->color === 'success') bg-green-100 text-green-800
                                                @elseif($activity->color === 'warning') bg-yellow-100 text-yellow-800
                                                @elseif($activity->color === 'danger') bg-red-100 text-red-800
                                                @elseif($activity->color === 'info') bg-blue-100 text-blue-800
                                                @elseif($activity->color === 'primary') bg-indigo-100 text-indigo-800
                                                @else bg-gray-100 text-gray-800 @endif">
                                                {{ $activity->type }}
                                            </span>
                                        </dd>
                                    </div>

                                    <div>
                                        <dt class="text-sm font-medium text-gray-500">模組</dt>
                                        <dd class="mt-1 text-sm text-gray-900">{{ $activity->module ?? '未指定' }}</dd>
                                    </div>

                                    <div>
                                        <dt class="text-sm font-medium text-gray-500">操作者</dt>
                                        <dd class="mt-1 text-sm text-gray-900">
                                            @if($activity->user)
                                                <div class="flex items-center space-x-2">
                                                    <span class="font-medium">{{ $activity->user->name }}</span>
                                                    <span class="text-gray-500">({{ $activity->user->username }})</span>
                                                </div>
                                            @else
                                                <span class="text-gray-500">系統</span>
                                            @endif
                                        </dd>
                                    </div>

                                    <div>
                                        <dt class="text-sm font-medium text-gray-500">操作結果</dt>
                                        <dd class="mt-1 text-sm text-gray-900">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                                                @if($activity->result === 'success') bg-green-100 text-green-800
                                                @elseif($activity->result === 'failed') bg-red-100 text-red-800
                                                @elseif($activity->result === 'warning') bg-yellow-100 text-yellow-800
                                                @else bg-gray-100 text-gray-800 @endif">
                                                {{ $activity->result }}
                                            </span>
                                        </dd>
                                    </div>

                                    <div class="sm:col-span-2">
                                        <dt class="text-sm font-medium text-gray-500">操作描述</dt>
                                        <dd class="mt-1 text-sm text-gray-900">{{ $activity->description }}</dd>
                                    </div>
                                </dl>
                            </div>

                            <!-- 技術資訊 -->
                            <div>
                                <h4 class="text-sm font-medium text-gray-900 mb-3">技術資訊</h4>
                                <dl class="grid grid-cols-1 gap-x-4 gap-y-3 sm:grid-cols-2">
                                    <div>
                                        <dt class="text-sm font-medium text-gray-500">IP 位址</dt>
                                        <dd class="mt-1 text-sm text-gray-900 font-mono">{{ $activity->ip_address ?? '未記錄' }}</dd>
                                    </div>

                                    <div>
                                        <dt class="text-sm font-medium text-gray-500">風險等級</dt>
                                        <dd class="mt-1 text-sm text-gray-900">{{ $activity->risk_level ?? 0 }} / 10</dd>
                                    </div>

                                    @if($activity->user_agent)
                                        <div class="sm:col-span-2">
                                            <dt class="text-sm font-medium text-gray-500">使用者代理</dt>
                                            <dd class="mt-1 text-sm text-gray-900 font-mono break-all">{{ $activity->user_agent }}</dd>
                                        </div>
                                    @endif

                                    @if($activity->signature)
                                        <div class="sm:col-span-2">
                                            <dt class="text-sm font-medium text-gray-500">數位簽章</dt>
                                            <dd class="mt-1 text-sm text-gray-900 font-mono break-all">
                                                {{ substr($activity->signature, 0, 64) }}...
                                                <span class="ml-2 text-xs text-green-600">
                                                    @if($activity->verifyIntegrity())
                                                        ✓ 已驗證
                                                    @else
                                                        ✗ 驗證失敗
                                                    @endif
                                                </span>
                                            </dd>
                                        </div>
                                    @endif
                                </dl>
                            </div>

                            <!-- 操作資料 -->
                            @if(!empty($formattedData))
                                <div>
                                    <div class="flex items-center justify-between mb-3">
                                        <h4 class="text-sm font-medium text-gray-900">操作資料</h4>
                                        <button type="button" wire:click="toggleRawData" 
                                                class="text-sm text-indigo-600 hover:text-indigo-500">
                                            @if($showRawData) 顯示格式化資料 @else 顯示原始資料 @endif
                                        </button>
                                    </div>

                                    @if($showRawData)
                                        <!-- 原始資料 -->
                                        <div class="bg-gray-50 rounded-lg p-4">
                                            <pre class="text-sm text-gray-900 whitespace-pre-wrap">{{ json_encode($activity->properties, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                                        </div>
                                    @else
                                        <!-- 格式化資料 -->
                                        <div class="bg-gray-50 rounded-lg p-4">
                                            <dl class="space-y-3">
                                                @foreach($formattedData as $item)
                                                    <div class="flex flex-col sm:flex-row sm:items-start">
                                                        <dt class="text-sm font-medium text-gray-500 sm:w-1/3">{{ $item['key'] }}</dt>
                                                        <dd class="mt-1 sm:mt-0 text-sm text-gray-900 sm:w-2/3">
                                                            @if($item['type'] === 'array' || str_contains($item['value'], '{'))
                                                                <pre class="whitespace-pre-wrap text-xs">{{ $item['value'] }}</pre>
                                                            @else
                                                                {{ $item['value'] }}
                                                            @endif
                                                        </dd>
                                                    </div>
                                                @endforeach
                                            </dl>
                                        </div>
                                    @endif
                                </div>
                            @endif

                            <!-- 相關活動 -->
                            @if($showRelatedActivities && $relatedActivities->isNotEmpty())
                                <div>
                                    <div class="flex items-center justify-between mb-3">
                                        <h4 class="text-sm font-medium text-gray-900">
                                            相關活動 ({{ $relatedActivities->count() }} 筆)
                                        </h4>
                                        <button type="button" wire:click="toggleRelatedActivities" 
                                                class="text-sm text-indigo-600 hover:text-indigo-500">
                                            隱藏相關活動
                                        </button>
                                    </div>

                                    <div class="bg-gray-50 rounded-lg p-4">
                                        <div class="space-y-3">
                                            @foreach($relatedActivities as $relatedActivity)
                                                <div class="flex items-center justify-between p-3 bg-white rounded-lg border border-gray-200 hover:border-gray-300 cursor-pointer"
                                                     wire:click="viewRelatedActivity({{ $relatedActivity->id }})">
                                                    <div class="flex-1">
                                                        <div class="flex items-center space-x-2">
                                                            <span class="text-sm font-medium text-gray-900">{{ $relatedActivity->type }}</span>
                                                            <span class="text-xs text-gray-500">{{ $relatedActivity->created_at->format('m-d H:i') }}</span>
                                                        </div>
                                                        <p class="text-sm text-gray-600 mt-1">{{ $relatedActivity->description }}</p>
                                                    </div>
                                                    <x-heroicon-o-chevron-right class="h-4 w-4 text-gray-400" />
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            @elseif($showRelatedActivities)
                                <div>
                                    <h4 class="text-sm font-medium text-gray-900 mb-3">相關活動</h4>
                                    <p class="text-sm text-gray-500">沒有找到相關活動記錄</p>
                                </div>
                            @endif

                            <!-- 註記區域 -->
                            <div>
                                <div class="flex items-center justify-between mb-3">
                                    <h4 class="text-sm font-medium text-gray-900">註記</h4>
                                    @if(!$showNoteForm)
                                        <button type="button" wire:click="showNoteForm" 
                                                class="text-sm text-indigo-600 hover:text-indigo-500">
                                            新增註記
                                        </button>
                                    @endif
                                </div>

                                @if($showNoteForm)
                                    <div class="bg-gray-50 rounded-lg p-4">
                                        <div class="space-y-3">
                                            <div>
                                                <label for="note" class="block text-sm font-medium text-gray-700">註記內容</label>
                                                <textarea id="note" wire:model="note" rows="3" 
                                                          class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                                          placeholder="請輸入註記內容..."></textarea>
                                            </div>
                                            <div class="flex justify-end space-x-2">
                                                <button type="button" wire:click="hideNoteForm" 
                                                        class="px-3 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                                    取消
                                                </button>
                                                <button type="button" wire:click="addNote('{{ $note }}')" 
                                                        class="px-3 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                                    新增註記
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                @else
                                    <p class="text-sm text-gray-500">點擊「新增註記」來為此活動新增註記</p>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>

@push('scripts')
<script>
    // 複製到剪貼簿功能
    document.addEventListener('livewire:init', () => {
        Livewire.on('copy-to-clipboard', (event) => {
            if (navigator.clipboard && window.isSecureContext) {
                navigator.clipboard.writeText(event.text).then(() => {
                    console.log('Text copied to clipboard');
                }).catch(err => {
                    console.error('Failed to copy text: ', err);
                });
            } else {
                // 降級方案
                const textArea = document.createElement('textarea');
                textArea.value = event.text;
                document.body.appendChild(textArea);
                textArea.focus();
                textArea.select();
                try {
                    document.execCommand('copy');
                } catch (err) {
                    console.error('Failed to copy text: ', err);
                }
                document.body.removeChild(textArea);
            }
        });
    });
</script>
@endpush