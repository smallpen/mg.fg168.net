<div class="space-y-6">
    <!-- 頁面標題和統計 -->
    <div class="bg-white shadow rounded-lg p-6">
        <div class="flex justify-between items-center mb-4">
            <div>
                <h2 class="text-2xl font-bold text-gray-900">通知規則管理</h2>
                <p class="text-gray-600">管理活動記錄的通知規則和警報設定</p>
            </div>
            @can('activity_logs.create')
                <button wire:click="create" 
                        class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md flex items-center">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                    </svg>
                    建立規則
                </button>
            @endcan
        </div>

        <!-- 統計資訊 -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div class="bg-blue-50 p-4 rounded-lg">
                <div class="text-2xl font-bold text-blue-600">{{ $statistics['total'] ?? 0 }}</div>
                <div class="text-sm text-blue-600">總規則數</div>
            </div>
            <div class="bg-green-50 p-4 rounded-lg">
                <div class="text-2xl font-bold text-green-600">{{ $statistics['active'] ?? 0 }}</div>
                <div class="text-sm text-green-600">啟用規則</div>
            </div>
            <div class="bg-gray-50 p-4 rounded-lg">
                <div class="text-2xl font-bold text-gray-600">{{ $statistics['inactive'] ?? 0 }}</div>
                <div class="text-sm text-gray-600">停用規則</div>
            </div>
            <div class="bg-yellow-50 p-4 rounded-lg">
                <div class="text-2xl font-bold text-yellow-600">{{ $statistics['recently_triggered'] ?? 0 }}</div>
                <div class="text-sm text-yellow-600">近期觸發</div>
            </div>
        </div>
    </div>

    <!-- 篩選和搜尋 -->
    <div class="bg-white shadow rounded-lg p-6">
        <div class="grid grid-cols-1 md:grid-cols-5 gap-4 mb-4">
            <!-- 搜尋 -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">搜尋</label>
                <input type="text" 
                       wire:model.live.debounce.300ms="search"
                       placeholder="搜尋規則名稱或描述..."
                       class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500">
            </div>

            <!-- 狀態篩選 -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">狀態</label>
                <select wire:model.live="statusFilter" 
                        class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    <option value="all">全部狀態</option>
                    <option value="active">啟用</option>
                    <option value="inactive">停用</option>
                </select>
            </div>

            <!-- 優先級篩選 -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">優先級</label>
                <select wire:model.live="priorityFilter" 
                        class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    <option value="all">全部優先級</option>
                    <option value="1">低</option>
                    <option value="2">一般</option>
                    <option value="3">高</option>
                    <option value="4">緊急</option>
                </select>
            </div>

            <!-- 建立者篩選 -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">建立者</label>
                <select wire:model.live="creatorFilter" 
                        class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    <option value="all">全部建立者</option>
                    @foreach($users as $user)
                        <option value="{{ $user->id }}">{{ $user->name }}</option>
                    @endforeach
                </select>
            </div>

            <!-- 清除篩選 -->
            <div class="flex items-end">
                <button wire:click="clearFilters" 
                        class="w-full bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-md">
                    清除篩選
                </button>
            </div>
        </div>

        <!-- 批量操作 -->
        @if(!empty($selectedRules))
            <div class="flex items-center space-x-4 mb-4 p-3 bg-blue-50 rounded-lg">
                <span class="text-sm text-blue-700">已選擇 {{ count($selectedRules) }} 個規則</span>
                <select wire:model="bulkAction" 
                        class="border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    <option value="">選擇操作...</option>
                    <option value="activate">啟用</option>
                    <option value="deactivate">停用</option>
                    @can('activity_logs.delete')
                        <option value="delete">刪除</option>
                    @endcan
                </select>
                <button wire:click="executeBulkAction" 
                        class="bg-blue-600 hover:bg-blue-700 text-white px-3 py-1 rounded text-sm">
                    執行
                </button>
            </div>
        @endif
    </div>

    <!-- 規則列表 -->
    <div class="bg-white shadow rounded-lg overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left">
                            <input type="checkbox" 
                                   wire:model="selectAll"
                                   class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer"
                            wire:click="updateSort('name')">
                            規則名稱
                            @if($sortField === 'name')
                                <span class="ml-1">{{ $sortDirection === 'asc' ? '↑' : '↓' }}</span>
                            @endif
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            條件摘要
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            動作摘要
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer"
                            wire:click="updateSort('priority')">
                            優先級
                            @if($sortField === 'priority')
                                <span class="ml-1">{{ $sortDirection === 'asc' ? '↑' : '↓' }}</span>
                            @endif
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer"
                            wire:click="updateSort('is_active')">
                            狀態
                            @if($sortField === 'is_active')
                                <span class="ml-1">{{ $sortDirection === 'asc' ? '↑' : '↓' }}</span>
                            @endif
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            觸發統計
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer"
                            wire:click="updateSort('created_at')">
                            建立時間
                            @if($sortField === 'created_at')
                                <span class="ml-1">{{ $sortDirection === 'asc' ? '↑' : '↓' }}</span>
                            @endif
                        </th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                            操作
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($rules as $rule)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4">
                                <input type="checkbox" 
                                       wire:model="selectedRules" 
                                       value="{{ $rule->id }}"
                                       class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-sm font-medium text-gray-900">{{ $rule->name }}</div>
                                @if($rule->description)
                                    <div class="text-sm text-gray-500">{{ Str::limit($rule->description, 50) }}</div>
                                @endif
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-sm text-gray-900">{{ $rule->conditions_summary }}</div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-sm text-gray-900">{{ $rule->actions_summary }}</div>
                            </td>
                            <td class="px-6 py-4">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                           bg-{{ $rule->priority_color }}-100 text-{{ $rule->priority_color }}-800">
                                    {{ $rule->priority_label }}
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <button wire:click="toggleStatus({{ $rule->id }})"
                                        class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                               {{ $rule->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                    {{ $rule->is_active ? '啟用' : '停用' }}
                                </button>
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-sm text-gray-900">{{ $rule->triggered_count }} 次</div>
                                <div class="text-sm text-gray-500">{{ $rule->trigger_frequency }}</div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-sm text-gray-900">{{ $rule->created_at->format('Y-m-d') }}</div>
                                <div class="text-sm text-gray-500">{{ $rule->creator->name ?? '未知' }}</div>
                            </td>
                            <td class="px-6 py-4 text-right text-sm font-medium space-x-2">
                                <button wire:click="testRule({{ $rule->id }})"
                                        class="text-blue-600 hover:text-blue-900"
                                        title="測試規則">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                              d="M13 10V3L4 14h7v7l9-11h-7z"/>
                                    </svg>
                                </button>

                                @can('activity_logs.edit')
                                    <button wire:click="edit({{ $rule->id }})"
                                            class="text-indigo-600 hover:text-indigo-900"
                                            title="編輯">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                                  d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                        </svg>
                                    </button>
                                @endcan

                                <button wire:click="duplicate({{ $rule->id }})"
                                        class="text-green-600 hover:text-green-900"
                                        title="複製">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                              d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                                    </svg>
                                </button>

                                @can('activity_logs.delete')
                                    <button wire:click="delete({{ $rule->id }})"
                                            wire:confirm="確定要刪除這個通知規則嗎？"
                                            class="text-red-600 hover:text-red-900"
                                            title="刪除">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                                  d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                        </svg>
                                    </button>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="px-6 py-12 text-center text-gray-500">
                                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                          d="M15 17h5l-5 5v-5zM9 7H4l5-5v5zm6 10V7a1 1 0 00-1-1H5a1 1 0 00-1 1v10a1 1 0 001 1h9a1 1 0 001-1z"/>
                                </svg>
                                <h3 class="mt-2 text-sm font-medium text-gray-900">沒有通知規則</h3>
                                <p class="mt-1 text-sm text-gray-500">開始建立第一個通知規則來監控活動記錄。</p>
                                @can('activity_logs.create')
                                    <div class="mt-6">
                                        <button wire:click="create" 
                                                class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700">
                                            建立通知規則
                                        </button>
                                    </div>
                                @endcan
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- 分頁 -->
        @if($rules->hasPages())
            <div class="px-6 py-3 border-t border-gray-200">
                {{ $rules->links() }}
            </div>
        @endif
    </div>

    <!-- 建立/編輯表單模態 -->
    @if($showForm)
        <div class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
            <div class="relative top-20 mx-auto p-5 border w-11/12 max-w-4xl shadow-lg rounded-md bg-white">
                <div class="mt-3">
                    <!-- 表單標題 -->
                    <div class="flex justify-between items-center mb-6">
                        <h3 class="text-lg font-medium text-gray-900">
                            {{ $editMode ? '編輯通知規則' : '建立通知規則' }}
                        </h3>
                        <button wire:click="cancel" class="text-gray-400 hover:text-gray-600">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>

                    <form wire:submit="save" class="space-y-6">
                        <!-- 基本資訊 -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">規則名稱 *</label>
                                <input type="text" 
                                       wire:model="name"
                                       class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                       placeholder="輸入規則名稱">
                                @error('name') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">優先級</label>
                                <select wire:model="priority" 
                                        class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                    <option value="1">低</option>
                                    <option value="2">一般</option>
                                    <option value="3">高</option>
                                    <option value="4">緊急</option>
                                </select>
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">描述</label>
                            <textarea wire:model="description"
                                      rows="3"
                                      class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                      placeholder="輸入規則描述"></textarea>
                        </div>

                        <div class="flex items-center">
                            <input type="checkbox" 
                                   wire:model="isActive"
                                   class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                            <label class="ml-2 text-sm text-gray-700">啟用此規則</label>
                        </div>

                        <!-- 觸發條件 -->
                        <div class="border-t pt-6">
                            <h4 class="text-lg font-medium text-gray-900 mb-4">觸發條件</h4>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <!-- 活動類型 -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">活動類型</label>
                                    <div class="space-y-2">
                                        @foreach($activityTypeOptions as $value => $label)
                                            <label class="flex items-center">
                                                <input type="checkbox" 
                                                       wire:model="activityTypes" 
                                                       value="{{ $value }}"
                                                       class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                                <span class="ml-2 text-sm text-gray-700">{{ $label }}</span>
                                            </label>
                                        @endforeach
                                    </div>
                                </div>

                                <!-- 最低風險等級 -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">最低風險等級</label>
                                    <input type="range" 
                                           wire:model.live="minRiskLevel"
                                           min="0" max="10" step="1"
                                           class="w-full">
                                    <div class="flex justify-between text-xs text-gray-500">
                                        <span>0 (低)</span>
                                        <span class="font-medium">{{ $minRiskLevel }}</span>
                                        <span>10 (高)</span>
                                    </div>
                                </div>
                            </div>

                            <!-- IP 模式 -->
                            <div class="mt-4">
                                <label class="block text-sm font-medium text-gray-700 mb-1">IP 位址模式</label>
                                <div class="space-y-2">
                                    @foreach($ipPatterns as $index => $pattern)
                                        <div class="flex items-center space-x-2">
                                            <input type="text" 
                                                   wire:model="ipPatterns.{{ $index }}"
                                                   placeholder="例如：192.168.1.* 或 10.0.0.0/24"
                                                   class="flex-1 border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                            <button type="button" 
                                                    wire:click="removeIpPattern({{ $index }})"
                                                    class="text-red-600 hover:text-red-800">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                                </svg>
                                            </button>
                                        </div>
                                    @endforeach
                                    <button type="button" 
                                            wire:click="addIpPattern"
                                            class="text-blue-600 hover:text-blue-800 text-sm">
                                        + 新增 IP 模式
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- 通知動作 -->
                        <div class="border-t pt-6">
                            <h4 class="text-lg font-medium text-gray-900 mb-4">通知動作</h4>

                            <!-- 接收者 -->
                            <div class="mb-6">
                                <label class="block text-sm font-medium text-gray-700 mb-2">通知接收者 *</label>
                                <div class="space-y-2">
                                    <label class="flex items-center">
                                        <input type="checkbox" 
                                               wire:model="recipients" 
                                               value='{"type": "all_admins"}'
                                               class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                        <span class="ml-2 text-sm text-gray-700">所有管理員</span>
                                    </label>
                                    @foreach($users as $user)
                                        <label class="flex items-center">
                                            <input type="checkbox" 
                                                   wire:model="recipients" 
                                                   value='{"type": "user", "id": {{ $user->id }}}'
                                                   class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                            <span class="ml-2 text-sm text-gray-700">{{ $user->name }} ({{ $user->username }})</span>
                                        </label>
                                    @endforeach
                                </div>
                                @error('recipients') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                            </div>

                            <!-- 通知範本 -->
                            <div class="grid grid-cols-1 gap-4 mb-6">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">通知標題範本 *</label>
                                    <input type="text" 
                                           wire:model="titleTemplate"
                                           class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                           placeholder="活動記錄警報：{activity_type}">
                                    @error('titleTemplate') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">通知訊息範本 *</label>
                                    <textarea wire:model="messageTemplate"
                                              rows="3"
                                              class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                              placeholder="使用者 {user_name} 在 {time} 執行了 {activity_type} 操作：{description}"></textarea>
                                    @error('messageTemplate') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                </div>
                            </div>

                            <!-- 通知方式 -->
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                                <div class="space-y-3">
                                    <label class="flex items-center">
                                        <input type="checkbox" 
                                               wire:model="emailNotification"
                                               class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                        <span class="ml-2 text-sm text-gray-700">郵件通知</span>
                                    </label>

                                    <label class="flex items-center">
                                        <input type="checkbox" 
                                               wire:model="browserNotification"
                                               class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                        <span class="ml-2 text-sm text-gray-700">瀏覽器通知</span>
                                    </label>

                                    <label class="flex items-center">
                                        <input type="checkbox" 
                                               wire:model="securityAlert"
                                               class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                        <span class="ml-2 text-sm text-gray-700">建立安全警報</span>
                                    </label>
                                </div>

                                <div class="space-y-3">
                                    <label class="flex items-center">
                                        <input type="checkbox" 
                                               wire:model="webhookNotification"
                                               class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                        <span class="ml-2 text-sm text-gray-700">Webhook 通知</span>
                                    </label>

                                    @if($webhookNotification)
                                        <input type="url" 
                                               wire:model="webhookUrl"
                                               placeholder="https://example.com/webhook"
                                               class="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                        @error('webhookUrl') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                    @endif
                                </div>
                            </div>

                            <!-- 通知合併設定 -->
                            <div class="border-t pt-4">
                                <label class="flex items-center mb-3">
                                    <input type="checkbox" 
                                           wire:model="mergeSimilar"
                                           class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                    <span class="ml-2 text-sm text-gray-700">合併相似通知</span>
                                </label>

                                @if($mergeSimilar)
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">合併時間窗口（秒）</label>
                                        <input type="number" 
                                               wire:model="mergeWindow"
                                               min="60" max="3600"
                                               class="w-32 border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                        <span class="text-sm text-gray-500 ml-2">60-3600 秒</span>
                                    </div>
                                @endif
                            </div>
                        </div>

                        <!-- 表單按鈕 -->
                        <div class="flex justify-end space-x-3 pt-6 border-t">
                            <button type="button" 
                                    wire:click="cancel"
                                    class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                                取消
                            </button>
                            <button type="submit"
                                    class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700">
                                {{ $editMode ? '更新規則' : '建立規則' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
</div>

@push('scripts')
<script>
    // 處理通知顯示
    document.addEventListener('livewire:init', () => {
        Livewire.on('notification', (event) => {
            const { type, message } = event;
            
            // 這裡可以整合您的通知系統
            // 例如 toast 通知或 alert
            if (type === 'success') {
                alert('成功：' + message);
            } else if (type === 'error') {
                alert('錯誤：' + message);
            } else {
                alert(message);
            }
        });
    });
</script>
@endpush