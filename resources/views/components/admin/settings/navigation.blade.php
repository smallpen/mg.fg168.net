@props([
    'currentCategory' => 'all',
    'categories' => [],
    'stats' => []
])

<div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
    <!-- 導航標頭 -->
    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center">
            <x-heroicon-o-squares-2x2 class="w-5 h-5 mr-2 text-blue-600 dark:text-blue-400" />
            設定分類
        </h3>
        <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
            按分類瀏覽和管理系統設定
        </p>
    </div>

    <!-- 分類列表 -->
    <div class="p-2">
        <!-- 全部設定 -->
        <a href="{{ route('admin.settings.system') }}" 
           class="flex items-center justify-between px-4 py-3 rounded-lg transition-colors hover:bg-gray-50 dark:hover:bg-gray-700 {{ $currentCategory === 'all' ? 'bg-blue-50 dark:bg-blue-900/20 text-blue-700 dark:text-blue-300' : 'text-gray-700 dark:text-gray-300' }}">
            <div class="flex items-center">
                <x-heroicon-o-cog-6-tooth class="w-5 h-5 mr-3 {{ $currentCategory === 'all' ? 'text-blue-600 dark:text-blue-400' : 'text-gray-400' }}" />
                <span class="font-medium">全部設定</span>
            </div>
            <span class="text-sm bg-gray-100 dark:bg-gray-700 px-2 py-1 rounded-full">
                {{ $stats['total'] ?? 0 }}
            </span>
        </a>

        <!-- 分類項目 -->
        @foreach($categories as $key => $category)
            <a href="{{ route('admin.settings.system', ['category' => $key]) }}" 
               class="flex items-center justify-between px-4 py-3 rounded-lg transition-colors hover:bg-gray-50 dark:hover:bg-gray-700 {{ $currentCategory === $key ? 'bg-blue-50 dark:bg-blue-900/20 text-blue-700 dark:text-blue-300' : 'text-gray-700 dark:text-gray-300' }}">
                <div class="flex items-center">
                    @php
                        $iconName = $category['icon'] ?? 'cog';
                        $iconComponent = match($iconName) {
                            'cog' => 'heroicon-o-cog-6-tooth',
                            'shield-check' => 'heroicon-o-shield-check',
                            'bell' => 'heroicon-o-bell',
                            'palette' => 'heroicon-o-paint-brush',
                            'link' => 'heroicon-o-link',
                            'wrench' => 'heroicon-o-wrench-screwdriver',
                            default => 'heroicon-o-cog-6-tooth'
                        };
                    @endphp
                    <x-dynamic-component 
                        :component="$iconComponent" 
                        class="w-5 h-5 mr-3 {{ $currentCategory === $key ? 'text-blue-600 dark:text-blue-400' : 'text-gray-400' }}" 
                    />
                    <div>
                        <div class="font-medium">{{ $category['name'] }}</div>
                        <div class="text-xs text-gray-500 dark:text-gray-400">
                            {{ $category['description'] }}
                        </div>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    @if(isset($stats['categories'][$key]['changed']) && $stats['categories'][$key]['changed'] > 0)
                        <span class="text-xs bg-orange-100 dark:bg-orange-900/30 text-orange-600 dark:text-orange-400 px-2 py-1 rounded-full">
                            {{ $stats['categories'][$key]['changed'] }} 已變更
                        </span>
                    @endif
                    <span class="text-sm bg-gray-100 dark:bg-gray-700 px-2 py-1 rounded-full">
                        {{ $stats['categories'][$key]['total'] ?? 0 }}
                    </span>
                </div>
            </a>
        @endforeach
    </div>

    <!-- 快速操作 -->
    <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700">
        <div class="space-y-2">
            <button onclick="Livewire.dispatch('export-settings')" 
                    class="w-full flex items-center justify-center px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-gray-50 dark:bg-gray-700 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-600 transition-colors">
                <x-heroicon-o-arrow-down-tray class="w-4 h-4 mr-2" />
                匯出設定
            </button>
            
            <button onclick="Livewire.dispatch('import-settings')" 
                    class="w-full flex items-center justify-center px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-gray-50 dark:bg-gray-700 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-600 transition-colors">
                <x-heroicon-o-arrow-up-tray class="w-4 h-4 mr-2" />
                匯入設定
            </button>
            
            <button onclick="Livewire.dispatch('create-backup')" 
                    class="w-full flex items-center justify-center px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 transition-colors">
                <x-heroicon-o-archive-box class="w-4 h-4 mr-2" />
                建立備份
            </button>
        </div>
    </div>
</div>