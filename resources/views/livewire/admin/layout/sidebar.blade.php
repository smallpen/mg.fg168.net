@php
use Illuminate\Support\Facades\Route;
@endphp

<div class="sidebar-container flex flex-col h-full bg-white dark:bg-gray-800 border-r border-gray-200 dark:border-gray-700 transition-all duration-300 ease-in-out {{ $collapsed ? 'w-16' : 'w-64' }}" 
     x-data="{ 
         collapsed: @entangle('collapsed'),
         hoveredItem: null,
         expandedMenus: @entangle('expandedMenus'),
         showMobileMenu: false 
     }"
     :class="{ 'collapsed-mode': collapsed }"
     @resize.window="if (window.innerWidth < 768) { showMobileMenu = false; collapsed = true; }"
     x-init="
         if (window.innerWidth < 768) { 
             collapsed = true; 
             showMobileMenu = false; 
         }
     ">
    
    <!-- Logo 區域 -->
    <div class="logo-section flex items-center h-16 px-4 bg-gradient-to-r from-primary-600 to-primary-700 dark:from-primary-700 dark:to-primary-800 border-b border-primary-700 dark:border-primary-600 relative overflow-hidden"
         :class="collapsed ? 'justify-center' : 'justify-between'">
        
        <!-- 背景裝飾 -->
        <div class="absolute inset-0 bg-gradient-to-br from-white/5 to-transparent pointer-events-none"></div>
        
        <!-- Logo 和標題 -->
        <div class="flex items-center relative z-10 transition-all duration-300"
             x-show="!collapsed || true"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 transform scale-95"
             x-transition:enter-end="opacity-100 transform scale-100">
            
            <div class="logo-container w-8 h-8 bg-white rounded-lg flex items-center justify-center shadow-lg transform transition-transform duration-200 hover:scale-105"
                 :class="collapsed ? '' : 'mr-3'">
                <svg class="w-5 h-5 text-primary-600 transition-colors duration-200" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M3 4a1 1 0 011-1h12a1 1 0 011 1v2a1 1 0 01-1 1H4a1 1 0 01-1-1V4zM3 10a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H4a1 1 0 01-1-1v-6zM14 9a1 1 0 00-1 1v6a1 1 0 001 1h2a1 1 0 001-1v-6a1 1 0 00-1-1h-2z"></path>
                </svg>
            </div>
            
            <div x-show="!collapsed" 
                 x-transition:enter="transition ease-out duration-300 delay-100"
                 x-transition:enter-start="opacity-0 transform translate-x-2"
                 x-transition:enter-end="opacity-100 transform translate-x-0"
                 x-transition:leave="transition ease-in duration-200"
                 x-transition:leave-start="opacity-100 transform translate-x-0"
                 x-transition:leave-end="opacity-0 transform translate-x-2">
                <h1 class="text-lg font-bold text-white truncate tracking-wide">
                    {{ config('app.name', '管理系統') }}
                </h1>
                <div class="text-xs text-primary-200 opacity-75">
                    管理控制台
                </div>
            </div>
        </div>
        
        <!-- 收合按鈕 -->
        <button wire:click="toggleCollapse" 
                x-show="!collapsed"
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0 transform rotate-180"
                x-transition:enter-end="opacity-100 transform rotate-0"
                class="collapse-btn p-2 text-white hover:bg-white/10 rounded-lg transition-all duration-200 hover:scale-105 active:scale-95 relative z-10">
            <svg class="w-4 h-4 transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 19l-7-7 7-7m8 14l-7-7 7-7"></path>
            </svg>
        </button>
    </div>
    
    @if(!$collapsed)
        <!-- 搜尋區域 -->
        <div x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 transform -translate-y-2"
             x-transition:enter-end="opacity-100 transform translate-y-0"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100 transform translate-y-0"
             x-transition:leave-end="opacity-0 transform -translate-y-2"
             class="search-section px-3 py-3 border-b border-gray-200 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-800/50">
        
        <div class="relative group">
            <input type="text" 
                   wire:model.live.debounce.300ms="menuSearch"
                   placeholder="搜尋選單..."
                   class="search-input w-full pl-10 pr-10 py-2.5 text-sm bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-xl shadow-sm focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition-all duration-200 hover:shadow-md"
                   x-data="{ focused: false }"
                   @focus="focused = true"
                   @blur="focused = false"
                   :class="{ 'ring-2 ring-primary-500 border-primary-500': focused }">
            
            <!-- 搜尋圖示 -->
            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                <svg class="w-4 h-4 text-gray-400 group-hover:text-primary-500 transition-colors duration-200" 
                     fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    {!! $this->getIcon('search') !!}
                </svg>
            </div>
            
            <!-- 清除按鈕 -->
            @if($menuSearch)
                <button wire:click="clearMenuSearch" 
                        x-transition:enter="transition ease-out duration-200"
                        x-transition:enter-start="opacity-0 transform scale-75"
                        x-transition:enter-end="opacity-100 transform scale-100"
                        class="absolute inset-y-0 right-0 pr-3 flex items-center group">
                    <div class="p-1 rounded-full hover:bg-gray-100 dark:hover:bg-gray-600 transition-colors duration-200">
                        <svg class="w-3 h-3 text-gray-400 group-hover:text-gray-600 dark:group-hover:text-gray-300" 
                             fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </div>
                </button>
            @endif
            
            <!-- 搜尋快捷鍵提示 -->
            @if(!$menuSearch)
                <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                    <kbd class="hidden sm:inline-block px-2 py-1 text-xs text-gray-400 bg-gray-100 dark:bg-gray-600 dark:text-gray-300 rounded border border-gray-200 dark:border-gray-500">
                        Ctrl+K
                    </kbd>
                </div>
            @endif
        </div>
        
        <!-- 搜尋狀態指示 -->
        @if($showSearch && $menuSearch)
            <div class="mt-2 flex items-center text-xs text-gray-500 dark:text-gray-400">
                <svg class="w-3 h-3 mr-1 animate-pulse" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd"></path>
                </svg>
                搜尋中...
            </div>
        @endif
        </div>
    @endif
    
    <!-- 導航選單 -->
    <nav class="navigation-menu flex-1 px-3 py-4 space-y-1 overflow-y-auto scrollbar-thin scrollbar-thumb-gray-300 dark:scrollbar-thumb-gray-600 scrollbar-track-transparent"
         x-data="{ 
             animateMenuItems: true,
             getMenuItemDelay: (index) => index * 50 + 'ms'
         }"
         :class="{ 'collapsed-nav': collapsed }">
        
        @if($showSearch && !empty($searchResults))
            <!-- 搜尋結果 -->
            <div class="search-results space-y-1"
                 x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0 transform translate-y-2"
                 x-transition:enter-end="opacity-100 transform translate-y-0">
                
                <!-- 搜尋結果標題 -->
                <div class="flex items-center justify-between px-3 py-2 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                    <span class="flex items-center">
                        <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd"></path>
                        </svg>
                        搜尋結果
                    </span>
                    <span class="px-2 py-1 text-xs bg-primary-100 dark:bg-primary-900/50 text-primary-700 dark:text-primary-300 rounded-full">
                        {{ count($searchResults) }}
                    </span>
                </div>
                
                <!-- 搜尋結果列表 -->
                <div class="space-y-1">
                    @foreach($searchResults as $index => $result)
                        <a href="{{ isset($result['route']) && Route::has($result['route']) ? route($result['route']) : '#' }}" 
                           class="search-result-item flex items-center px-3 py-2.5 text-sm rounded-lg transition-all duration-200 text-gray-700 dark:text-gray-300 hover:bg-primary-50 dark:hover:bg-primary-900/20 hover:text-primary-700 dark:hover:text-primary-300 group border border-transparent hover:border-primary-200 dark:hover:border-primary-800"
                           x-transition:enter="transition ease-out duration-200"
                           x-transition:enter-start="opacity-0 transform translate-x-2"
                           x-transition:enter-end="opacity-100 transform translate-x-0"
                           :style="'transition-delay: ' + ({{ $index }} * 50) + 'ms'">
                            
                            <!-- 圖示 -->
                            <div class="flex-shrink-0 w-8 h-8 flex items-center justify-center rounded-lg bg-gray-100 dark:bg-gray-700 group-hover:bg-primary-100 dark:group-hover:bg-primary-900/30 transition-colors duration-200 mr-3">
                                <svg class="w-4 h-4 text-gray-500 dark:text-gray-400 group-hover:text-primary-600 dark:group-hover:text-primary-400 transition-colors duration-200" 
                                     fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    {!! $this->getIcon($result['icon'] ?? 'chart-bar') !!}
                                </svg>
                            </div>
                            
                            <!-- 內容 -->
                            <div class="flex-1 min-w-0">
                                <div class="font-medium truncate group-hover:text-primary-700 dark:group-hover:text-primary-300 transition-colors duration-200">
                                    {{ $result['title'] }}
                                </div>
                                @if(isset($result['parent']) && $result['parent'])
                                    <div class="text-xs text-gray-500 dark:text-gray-400 truncate mt-0.5 flex items-center">
                                        <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                        </svg>
                                        {{ $result['parent'] }}
                                    </div>
                                @endif
                            </div>
                            
                            <!-- 箭頭指示 -->
                            <div class="flex-shrink-0 opacity-0 group-hover:opacity-100 transition-opacity duration-200">
                                <svg class="w-4 h-4 text-primary-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                </svg>
                            </div>
                        </a>
                    @endforeach
                </div>
                
                <!-- 無結果提示 -->
                @if(empty($searchResults) && $menuSearch)
                    <div class="flex flex-col items-center justify-center py-8 text-gray-500 dark:text-gray-400">
                        <svg class="w-12 h-12 mb-3 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                        <p class="text-sm font-medium">找不到相關選單</p>
                        <p class="text-xs mt-1">請嘗試其他關鍵字</p>
                    </div>
                @endif
            </div>
        @else
            <!-- 一般選單 -->
            <div class="menu-items space-y-1">
                @foreach($menuItems as $index => $item)
                    <div class="menu-item-wrapper"
                         x-transition:enter="transition ease-out duration-300"
                         x-transition:enter-start="opacity-0 transform -translate-x-4"
                         x-transition:enter-end="opacity-100 transform translate-x-0"
                         :style="animateMenuItems ? 'transition-delay: ' + getMenuItemDelay({{ $index }}) : ''">
                        
                        @if(isset($item['children']) && count($item['children']) > 0)
                            <!-- 有子選單的項目 -->
                            <div class="menu-group space-y-1">
                                
                                <!-- 父選單項目 -->
                                <button wire:click="toggleMenu('{{ $item['key'] ?? '' }}')" 
                                        class="parent-menu-item w-full flex items-center px-3 py-2.5 text-sm font-medium rounded-xl transition-all duration-200 group relative overflow-hidden {{ $this->isMenuActive($item) ? 'bg-gradient-to-r from-primary-500 to-primary-600 text-white shadow-lg shadow-primary-500/25' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700/50 hover:text-gray-900 dark:hover:text-gray-200' }}"
                                        :class="collapsed ? 'justify-center' : 'justify-between'"
                                        @if($collapsed) 
                                            x-data="{ tooltip: false }"
                                            @mouseenter="tooltip = true"
                                            @mouseleave="tooltip = false"
                                        @endif>
                                    
                                    <!-- 背景動畫 -->
                                    <div class="absolute inset-0 bg-gradient-to-r from-primary-500/10 to-primary-600/10 opacity-0 group-hover:opacity-100 transition-opacity duration-200 rounded-xl"></div>
                                    
                                    <div class="flex items-center relative z-10" :class="collapsed ? 'justify-center' : ''">
                                        <!-- 圖示容器 -->
                                        <div class="icon-container flex-shrink-0 w-8 h-8 flex items-center justify-center rounded-lg transition-all duration-200 {{ $this->isMenuActive($item) ? 'bg-white/20' : 'group-hover:bg-primary-100 dark:group-hover:bg-primary-900/30' }}"
                                             :class="collapsed ? '' : 'mr-3'">
                                            <svg class="w-5 h-5 transition-all duration-200 {{ $this->isMenuActive($item) ? 'text-white' : 'text-gray-500 dark:text-gray-400 group-hover:text-primary-600 dark:group-hover:text-primary-400' }}" 
                                                 fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                {!! $this->getIcon($item['icon'] ?? 'chart-bar') !!}
                                            </svg>
                                        </div>
                                        
                                        <!-- 標題和徽章 -->
                                        <div x-show="!collapsed" 
                                             x-transition:enter="transition ease-out duration-200 delay-100"
                                             x-transition:enter-start="opacity-0 transform translate-x-2"
                                             x-transition:enter-end="opacity-100 transform translate-x-0"
                                             class="flex items-center flex-1 min-w-0">
                                            <span class="truncate font-medium">{{ $item['title'] }}</span>
                                            
                                            <!-- 子選單數量徽章 -->
                                            @if(isset($item['children']) && count($item['children']) > 0)
                                                <span class="ml-2 px-2 py-0.5 text-xs rounded-full transition-colors duration-200 {{ $this->isMenuActive($item) ? 'bg-white/20 text-white' : 'bg-gray-200 dark:bg-gray-600 text-gray-600 dark:text-gray-300 group-hover:bg-primary-100 dark:group-hover:bg-primary-900/50 group-hover:text-primary-700 dark:group-hover:text-primary-300' }}">
                                                    {{ count($item['children']) }}
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                    
                                    <!-- 展開箭頭 -->
                                    <div x-show="!collapsed" 
                                         x-transition:enter="transition ease-out duration-200 delay-150"
                                         x-transition:enter-start="opacity-0 transform rotate-180"
                                         x-transition:enter-end="opacity-100 transform rotate-0"
                                         class="flex-shrink-0 relative z-10">
                                        <svg class="w-4 h-4 transition-transform duration-300 {{ $this->isMenuExpanded($item['key'] ?? '') ? 'rotate-90' : '' }} {{ $this->isMenuActive($item) ? 'text-white' : 'text-gray-400 group-hover:text-primary-500' }}" 
                                             fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                        </svg>
                                    </div>
                                    
                                    <!-- 工具提示（收合模式） -->
                                    @if($collapsed)
                                        <div x-show="tooltip" 
                                             x-transition:enter="transition ease-out duration-200"
                                             x-transition:enter-start="opacity-0 transform scale-95 translate-x-2"
                                             x-transition:enter-end="opacity-100 transform scale-100 translate-x-0"
                                             x-transition:leave="transition ease-in duration-150"
                                             x-transition:leave-start="opacity-100 transform scale-100 translate-x-0"
                                             x-transition:leave-end="opacity-0 transform scale-95 translate-x-2"
                                             class="tooltip absolute left-16 z-50 px-3 py-2 text-sm text-white bg-gray-900 dark:bg-gray-700 rounded-lg shadow-xl whitespace-nowrap border border-gray-700 dark:border-gray-600">
                                            <div class="font-medium">{{ $item['title'] }}</div>
                                            @if(isset($item['children']) && count($item['children']) > 0)
                                                <div class="text-xs opacity-75 mt-1">{{ count($item['children']) }} 個子項目</div>
                                            @endif
                                            <!-- 箭頭 -->
                                            <div class="absolute top-1/2 -left-1 transform -translate-y-1/2">
                                                <div class="w-2 h-2 bg-gray-900 dark:bg-gray-700 border-l border-b border-gray-700 dark:border-gray-600 rotate-45"></div>
                                            </div>
                                        </div>
                                    @endif
                                </button>
                                
                                <!-- 子選單項目 -->
                                <div x-show="!collapsed && {{ $this->isMenuExpanded($item['key'] ?? '') ? 'true' : 'false' }}"
                                     x-transition:enter="transition ease-out duration-300"
                                     x-transition:enter-start="opacity-0 transform -translate-y-2"
                                     x-transition:enter-end="opacity-100 transform translate-y-0"
                                     x-transition:leave="transition ease-in duration-200"
                                     x-transition:leave-start="opacity-100 transform translate-y-0"
                                     x-transition:leave-end="opacity-0 transform -translate-y-2"
                                     class="submenu ml-6 space-y-1 border-l-2 border-gray-200 dark:border-gray-700 pl-4">
                                    
                                    @foreach($item['children'] as $childIndex => $child)
                                        <a href="{{ isset($child['route']) && Route::has($child['route']) ? route($child['route']) : '#' }}" 
                                           class="submenu-item flex items-center px-3 py-2 text-sm rounded-lg transition-all duration-200 group relative {{ isset($child['route']) && $this->isActiveRoute($child['route']) ? 'bg-primary-50 dark:bg-primary-900/20 text-primary-700 dark:text-primary-300 border-r-2 border-primary-500 shadow-sm' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-700/30 hover:text-gray-900 dark:hover:text-gray-200' }}"
                                           x-transition:enter="transition ease-out duration-200"
                                           x-transition:enter-start="opacity-0 transform translate-x-2"
                                           x-transition:enter-end="opacity-100 transform translate-x-0"
                                           :style="'transition-delay: ' + ({{ $childIndex }} * 50 + 100) + 'ms'">
                                            
                                            <!-- 子項目指示器 -->
                                            <div class="flex-shrink-0 w-6 h-6 flex items-center justify-center mr-2">
                                                <div class="w-2 h-2 rounded-full transition-all duration-200 {{ isset($child['route']) && $this->isActiveRoute($child['route']) ? 'bg-primary-500 shadow-sm' : 'bg-gray-300 dark:bg-gray-600 group-hover:bg-primary-400' }}"></div>
                                            </div>
                                            
                                            <!-- 子項目標題 -->
                                            <span class="truncate flex-1">{{ $child['title'] }}</span>
                                            
                                            <!-- 新項目徽章（示例） -->
                                            @if(isset($child['is_new']) && $child['is_new'])
                                                <span class="ml-2 px-1.5 py-0.5 text-xs font-medium bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400 rounded-full">
                                                    新
                                                </span>
                                            @endif
                                            
                                            <!-- 活躍指示器 -->
                                            @if(isset($child['route']) && $this->isActiveRoute($child['route']))
                                                <div class="absolute -right-1 top-1/2 transform -translate-y-1/2 w-1 h-6 bg-primary-500 rounded-l-full"></div>
                                            @endif
                                        </a>
                                    @endforeach
                                </div>
                                
                            </div>
                        @else
                            <!-- 單一選單項目 -->
                            <a href="{{ isset($item['route']) && Route::has($item['route']) ? route($item['route']) : '#' }}" 
                               class="single-menu-item flex items-center px-3 py-2.5 text-sm font-medium rounded-xl transition-all duration-200 group relative overflow-hidden {{ isset($item['route']) && $this->isActiveRoute($item['route']) ? 'bg-gradient-to-r from-primary-500 to-primary-600 text-white shadow-lg shadow-primary-500/25' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700/50 hover:text-gray-900 dark:hover:text-gray-200' }}"
                               :class="collapsed ? 'justify-center' : ''"
                               @if($collapsed) 
                                   x-data="{ tooltip: false }"
                                   @mouseenter="tooltip = true"
                                   @mouseleave="tooltip = false"
                               @endif>
                                
                                <!-- 背景動畫 -->
                                <div class="absolute inset-0 bg-gradient-to-r from-primary-500/10 to-primary-600/10 opacity-0 group-hover:opacity-100 transition-opacity duration-200 rounded-xl"></div>
                                
                                <!-- 圖示容器 -->
                                <div class="icon-container flex-shrink-0 w-8 h-8 flex items-center justify-center rounded-lg transition-all duration-200 {{ isset($item['route']) && $this->isActiveRoute($item['route']) ? 'bg-white/20' : 'group-hover:bg-primary-100 dark:group-hover:bg-primary-900/30' }}"
                                     :class="collapsed ? '' : 'mr-3'">
                                    <svg class="w-5 h-5 transition-all duration-200 {{ isset($item['route']) && $this->isActiveRoute($item['route']) ? 'text-white' : 'text-gray-500 dark:text-gray-400 group-hover:text-primary-600 dark:group-hover:text-primary-400' }}" 
                                         fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        {!! $this->getIcon($item['icon'] ?? 'chart-bar') !!}
                                    </svg>
                                </div>
                                
                                <!-- 標題 -->
                                <div x-show="!collapsed" 
                                     x-transition:enter="transition ease-out duration-200 delay-100"
                                     x-transition:enter-start="opacity-0 transform translate-x-2"
                                     x-transition:enter-end="opacity-100 transform translate-x-0"
                                     class="flex items-center flex-1 min-w-0 relative z-10">
                                    <span class="truncate font-medium">{{ $item['title'] }}</span>
                                    
                                    <!-- 通知徽章（示例） -->
                                    @if(isset($item['badge']) && $item['badge'])
                                        <span class="ml-2 px-2 py-0.5 text-xs font-medium rounded-full transition-colors duration-200 {{ isset($item['route']) && $this->isActiveRoute($item['route']) ? 'bg-white/20 text-white' : 'bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-400' }}">
                                            {{ $item['badge'] }}
                                        </span>
                                    @endif
                                </div>
                                
                                <!-- 工具提示（收合模式） -->
                                @if($collapsed)
                                    <div x-show="tooltip" 
                                         x-transition:enter="transition ease-out duration-200"
                                         x-transition:enter-start="opacity-0 transform scale-95 translate-x-2"
                                         x-transition:enter-end="opacity-100 transform scale-100 translate-x-0"
                                         x-transition:leave="transition ease-in duration-150"
                                         x-transition:leave-start="opacity-100 transform scale-100 translate-x-0"
                                         x-transition:leave-end="opacity-0 transform scale-95 translate-x-2"
                                         class="tooltip absolute left-16 z-50 px-3 py-2 text-sm text-white bg-gray-900 dark:bg-gray-700 rounded-lg shadow-xl whitespace-nowrap border border-gray-700 dark:border-gray-600">
                                        <div class="font-medium">{{ $item['title'] }}</div>
                                        @if(isset($item['badge']) && $item['badge'])
                                            <div class="text-xs opacity-75 mt-1">{{ $item['badge'] }} 個通知</div>
                                        @endif
                                        <!-- 箭頭 -->
                                        <div class="absolute top-1/2 -left-1 transform -translate-y-1/2">
                                            <div class="w-2 h-2 bg-gray-900 dark:bg-gray-700 border-l border-b border-gray-700 dark:border-gray-600 rotate-45"></div>
                                        </div>
                                    </div>
                                @endif
                                
                                <!-- 活躍指示器 -->
                                @if(isset($item['route']) && $this->isActiveRoute($item['route']))
                                    <div class="absolute -right-1 top-1/2 transform -translate-y-1/2 w-1 h-8 bg-white rounded-l-full opacity-80"></div>
                                @endif
                            </a>
                        @endif
                    </div>
                @endforeach
            </div>
        @endif
        
    </nav>
    
    <!-- 使用者資訊區域 -->
    <div class="user-section border-t border-gray-200 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-800/50">
        
        @if(!$collapsed)
            <!-- 展開模式的使用者資訊 -->
            <div x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0 transform translate-y-4"
                 x-transition:enter-end="opacity-100 transform translate-y-0"
                 x-transition:leave="transition ease-in duration-200"
                 x-transition:leave-start="opacity-100 transform translate-y-0"
                 x-transition:leave-end="opacity-0 transform translate-y-4"
                 class="px-3 py-4">
            
            <!-- 使用者資訊卡片 -->
            <div class="user-card flex items-center px-3 py-3 bg-white dark:bg-gray-700/50 rounded-xl shadow-sm border border-gray-200 dark:border-gray-600 hover:shadow-md transition-all duration-200 group">
                
                <!-- 使用者頭像 -->
                <div class="flex-shrink-0 relative">
                    <div class="w-10 h-10 bg-gradient-to-br from-primary-500 to-primary-600 rounded-full flex items-center justify-center shadow-lg group-hover:shadow-xl transition-shadow duration-200">
                        <span class="text-sm font-bold text-white">
                            {{ mb_substr(auth()->user()->name ?? auth()->user()->username ?? 'U', 0, 1) }}
                        </span>
                    </div>
                    <!-- 線上狀態指示器 -->
                    <div class="absolute -bottom-0.5 -right-0.5 w-3 h-3 bg-green-400 border-2 border-white dark:border-gray-700 rounded-full"></div>
                </div>
                
                <!-- 使用者資訊 -->
                <div class="ml-3 flex-1 min-w-0">
                    <p class="text-sm font-semibold text-gray-900 dark:text-gray-100 truncate group-hover:text-primary-700 dark:group-hover:text-primary-300 transition-colors duration-200">
                        {{ auth()->user()->name ?? auth()->user()->username }}
                    </p>
                    <p class="text-xs text-gray-500 dark:text-gray-400 truncate">
                        {{ auth()->user()->email ?? '無電子郵件' }}
                    </p>
                    <!-- 角色標籤 -->
                    @if(auth()->user()->roles->isNotEmpty())
                        <div class="mt-1">
                            <span class="inline-flex items-center px-2 py-0.5 text-xs font-medium bg-primary-100 dark:bg-primary-900/30 text-primary-700 dark:text-primary-300 rounded-full">
                                {{ auth()->user()->roles->first()->display_name ?? auth()->user()->roles->first()->name }}
                            </span>
                        </div>
                    @endif
                </div>
                
                <!-- 設定按鈕 -->
                <div class="flex-shrink-0">
                    <button class="p-1.5 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-600 rounded-lg transition-all duration-200">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                        </svg>
                    </button>
                </div>
            </div>
            
            <!-- 快速操作按鈕 -->
            <div class="mt-3 grid grid-cols-2 gap-2">
                <button class="quick-action-btn flex items-center justify-center px-3 py-2 text-xs font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-600 hover:border-primary-300 dark:hover:border-primary-600 transition-all duration-200 group">
                    <svg class="w-3 h-3 mr-1.5 text-gray-500 group-hover:text-primary-500 transition-colors duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                    </svg>
                    個人設定
                </button>
                <button class="quick-action-btn flex items-center justify-center px-3 py-2 text-xs font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-600 hover:border-primary-300 dark:hover:border-primary-600 transition-all duration-200 group">
                    <svg class="w-3 h-3 mr-1.5 text-gray-500 group-hover:text-primary-500 transition-colors duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    說明
                </button>
            </div>
            </div>
        @else
            <!-- 收合模式的使用者資訊 -->
            <div x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0 transform scale-95"
                 x-transition:enter-end="opacity-100 transform scale-100"
                 class="px-3 py-4">
            
            <div class="flex justify-center" 
                 x-data="{ tooltip: false }"
                 @mouseenter="tooltip = true"
                 @mouseleave="tooltip = false">
                
                <!-- 使用者頭像 -->
                <div class="relative cursor-pointer group">
                    <div class="w-10 h-10 bg-gradient-to-br from-primary-500 to-primary-600 rounded-full flex items-center justify-center shadow-lg group-hover:shadow-xl transition-all duration-200 group-hover:scale-105">
                        <span class="text-sm font-bold text-white">
                            {{ mb_substr(auth()->user()->name ?? auth()->user()->username ?? 'U', 0, 1) }}
                        </span>
                    </div>
                    <!-- 線上狀態指示器 -->
                    <div class="absolute -bottom-0.5 -right-0.5 w-3 h-3 bg-green-400 border-2 border-white dark:border-gray-800 rounded-full animate-pulse"></div>
                </div>
                
                <!-- 工具提示 -->
                <div x-show="tooltip" 
                     x-transition:enter="transition ease-out duration-200"
                     x-transition:enter-start="opacity-0 transform scale-95 translate-x-2"
                     x-transition:enter-end="opacity-100 transform scale-100 translate-x-0"
                     x-transition:leave="transition ease-in duration-150"
                     x-transition:leave-start="opacity-100 transform scale-100 translate-x-0"
                     x-transition:leave-end="opacity-0 transform scale-95 translate-x-2"
                     class="tooltip absolute left-16 bottom-4 z-50 px-4 py-3 text-sm text-white bg-gray-900 dark:bg-gray-700 rounded-xl shadow-xl border border-gray-700 dark:border-gray-600 min-w-max">
                    
                    <div class="flex items-center space-x-3">
                        <div class="w-8 h-8 bg-gradient-to-br from-primary-500 to-primary-600 rounded-full flex items-center justify-center">
                            <span class="text-xs font-bold text-white">
                                {{ mb_substr(auth()->user()->name ?? auth()->user()->username ?? 'U', 0, 1) }}
                            </span>
                        </div>
                        <div>
                            <div class="font-semibold">{{ auth()->user()->name ?? auth()->user()->username }}</div>
                            <div class="text-xs opacity-75">{{ auth()->user()->email ?? '無電子郵件' }}</div>
                            @if(auth()->user()->roles->isNotEmpty())
                                <div class="text-xs opacity-75 mt-1">
                                    {{ auth()->user()->roles->first()->display_name ?? auth()->user()->roles->first()->name }}
                                </div>
                            @endif
                        </div>
                    </div>
                    
                    <!-- 箭頭 -->
                    <div class="absolute top-1/2 -left-1 transform -translate-y-1/2">
                        <div class="w-2 h-2 bg-gray-900 dark:bg-gray-700 border-l border-b border-gray-700 dark:border-gray-600 rotate-45"></div>
                    </div>
                </div>
            </div>
            </div>
        @endif
    </div>
    
    @if($collapsed)
        <!-- 展開按鈕（收合模式） -->
        <div x-transition:enter="transition ease-out duration-300 delay-200"
             x-transition:enter-start="opacity-0 transform scale-75 rotate-180"
             x-transition:enter-end="opacity-100 transform scale-100 rotate-0"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100 transform scale-100 rotate-0"
             x-transition:leave-end="opacity-0 transform scale-75 rotate-180"
             class="expand-button absolute top-4 -right-3 z-20">
        
        <button wire:click="toggleCollapse" 
                class="w-8 h-8 bg-gradient-to-r from-primary-600 to-primary-700 hover:from-primary-700 hover:to-primary-800 text-white rounded-full flex items-center justify-center shadow-lg hover:shadow-xl transition-all duration-200 hover:scale-110 active:scale-95 group border-2 border-white dark:border-gray-800"
                x-data="{ hover: false }"
                @mouseenter="hover = true"
                @mouseleave="hover = false">
            
            <!-- 背景動畫 -->
            <div class="absolute inset-0 bg-white/20 rounded-full opacity-0 group-hover:opacity-100 transition-opacity duration-200"></div>
            
            <!-- 箭頭圖示 -->
            <svg class="w-4 h-4 transition-transform duration-200 relative z-10" 
                 :class="hover ? 'transform translate-x-0.5' : ''"
                 fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"></path>
            </svg>
            
            <!-- 脈衝動畫 -->
            <div class="absolute inset-0 rounded-full bg-primary-400 animate-ping opacity-20"></div>
        </button>
        </div>
    @endif
    
    <!-- 手機版覆蓋層 -->
    <div x-show="showMobileMenu && window.innerWidth < 768" 
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         @click="showMobileMenu = false"
         class="fixed inset-0 bg-black/50 z-30 lg:hidden"></div>
    
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // 鍵盤快捷鍵支援
    document.addEventListener('keydown', function(e) {
        // Ctrl+K 或 Cmd+K 開啟搜尋
        if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
            e.preventDefault();
            const searchInput = document.querySelector('.search-input');
            if (searchInput && !searchInput.closest('[x-show]')?.style.display === 'none') {
                searchInput.focus();
            }
        }
        
        // Escape 清除搜尋
        if (e.key === 'Escape') {
            const searchInput = document.querySelector('.search-input');
            if (searchInput && searchInput === document.activeElement) {
                searchInput.blur();
                // 觸發 Livewire 清除搜尋
                if (window.Livewire) {
                    window.Livewire.find(searchInput.closest('[wire\\:id]')?.getAttribute('wire:id'))?.call('clearMenuSearch');
                }
            }
        }
        
        // Ctrl+B 或 Cmd+B 切換側邊欄
        if ((e.ctrlKey || e.metaKey) && e.key === 'b') {
            e.preventDefault();
            const toggleButton = document.querySelector('[wire\\:click="toggleCollapse"]');
            if (toggleButton) {
                toggleButton.click();
            }
        }
    });
    
    // 觸控手勢支援（手機版）
    let touchStartX = 0;
    let touchStartY = 0;
    let touchEndX = 0;
    let touchEndY = 0;
    
    document.addEventListener('touchstart', function(e) {
        touchStartX = e.changedTouches[0].screenX;
        touchStartY = e.changedTouches[0].screenY;
    });
    
    document.addEventListener('touchend', function(e) {
        touchEndX = e.changedTouches[0].screenX;
        touchEndY = e.changedTouches[0].screenY;
        handleSwipe();
    });
    
    function handleSwipe() {
        const swipeThreshold = 50;
        const swipeDistance = touchEndX - touchStartX;
        const verticalDistance = Math.abs(touchEndY - touchStartY);
        
        // 只在垂直滑動距離較小時處理水平滑動
        if (verticalDistance < 100) {
            if (swipeDistance > swipeThreshold && window.innerWidth < 768) {
                // 向右滑動，開啟側邊欄
                const sidebar = document.querySelector('.sidebar-container');
                if (sidebar && sidebar.classList.contains('collapsed-mode')) {
                    // 觸發展開
                    const expandButton = document.querySelector('[wire\\:click="toggleCollapse"]');
                    if (expandButton) {
                        expandButton.click();
                    }
                }
            } else if (swipeDistance < -swipeThreshold && window.innerWidth < 768) {
                // 向左滑動，關閉側邊欄
                const sidebar = document.querySelector('.sidebar-container');
                if (sidebar && !sidebar.classList.contains('collapsed-mode')) {
                    // 觸發收合
                    const collapseButton = document.querySelector('[wire\\:click="toggleCollapse"]');
                    if (collapseButton) {
                        collapseButton.click();
                    }
                }
            }
        }
    }
    
    // 響應式處理
    function handleResize() {
        const sidebar = document.querySelector('.sidebar-container');
        if (!sidebar) return;
        
        if (window.innerWidth < 768) {
            // 手機版：強制收合
            sidebar.classList.add('mobile-mode');
        } else if (window.innerWidth < 1024) {
            // 平板版：懸停展開
            sidebar.classList.remove('mobile-mode');
            sidebar.classList.add('tablet-mode');
        } else {
            // 桌面版：正常模式
            sidebar.classList.remove('mobile-mode', 'tablet-mode');
        }
    }
    
    // 初始化響應式處理
    handleResize();
    window.addEventListener('resize', handleResize);
    
    // 平滑滾動到活躍選單項目
    function scrollToActiveMenuItem() {
        const activeItem = document.querySelector('.parent-menu-item.bg-gradient-to-r, .single-menu-item.bg-gradient-to-r, .submenu-item.bg-primary-50');
        if (activeItem) {
            activeItem.scrollIntoView({
                behavior: 'smooth',
                block: 'nearest'
            });
        }
    }
    
    // 頁面載入後滾動到活躍項目
    setTimeout(scrollToActiveMenuItem, 500);
    
    // 搜尋結果高亮
    function highlightSearchTerm(text, term) {
        if (!term) return text;
        const regex = new RegExp(`(${term})`, 'gi');
        return text.replace(regex, '<mark class="bg-yellow-200 dark:bg-yellow-800 px-1 rounded">$1</mark>');
    }
    
    // 監聽搜尋輸入變化
    document.addEventListener('input', function(e) {
        if (e.target.classList.contains('search-input')) {
            const searchTerm = e.target.value;
            const resultItems = document.querySelectorAll('.search-result-item');
            
            resultItems.forEach(item => {
                const titleElement = item.querySelector('.font-medium');
                if (titleElement && searchTerm) {
                    const originalText = titleElement.textContent;
                    titleElement.innerHTML = highlightSearchTerm(originalText, searchTerm);
                }
            });
        }
    });
    
    // 無障礙支援：焦點管理
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Tab') {
            const focusableElements = document.querySelectorAll(
                '.sidebar-container a, .sidebar-container button, .sidebar-container input'
            );
            
            const firstElement = focusableElements[0];
            const lastElement = focusableElements[focusableElements.length - 1];
            
            if (e.shiftKey && document.activeElement === firstElement) {
                e.preventDefault();
                lastElement.focus();
            } else if (!e.shiftKey && document.activeElement === lastElement) {
                e.preventDefault();
                firstElement.focus();
            }
        }
    });
});
</script>
@endpush

@push('styles')
<style>
    /* 自定義滾動條樣式 */
    .scrollbar-thin {
        scrollbar-width: thin;
    }
    
    .scrollbar-thumb-gray-300::-webkit-scrollbar-thumb {
        background-color: rgb(209 213 219);
        border-radius: 0.375rem;
    }
    
    .dark .scrollbar-thumb-gray-600::-webkit-scrollbar-thumb {
        background-color: rgb(75 85 99);
    }
    
    .scrollbar-track-transparent::-webkit-scrollbar-track {
        background-color: transparent;
    }
    
    .scrollbar-thin::-webkit-scrollbar {
        width: 6px;
    }
    
    /* 側邊欄容器樣式 */
    .sidebar-container {
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        will-change: width;
    }
    
    /* Logo 區域動畫 */
    .logo-section {
        background: linear-gradient(135deg, var(--tw-gradient-from), var(--tw-gradient-to));
        position: relative;
    }
    
    .logo-container {
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }
    
    .logo-container:hover {
        transform: scale(1.05) rotate(5deg);
    }
    
    /* 搜尋區域樣式 */
    .search-section {
        backdrop-filter: blur(10px);
    }
    
    .search-input {
        transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
    }
    
    .search-input:focus {
        transform: translateY(-1px);
        box-shadow: 0 10px 25px -5px rgba(59, 130, 246, 0.1), 0 10px 10px -5px rgba(59, 130, 246, 0.04);
    }
    
    /* 導航選單樣式 */
    .navigation-menu {
        position: relative;
    }
    
    .collapsed-nav {
        padding-left: 0.5rem;
        padding-right: 0.5rem;
    }
    
    /* 選單項目動畫 */
    .menu-item-wrapper {
        transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
    }
    
    .parent-menu-item,
    .single-menu-item {
        position: relative;
        transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
        transform-origin: left center;
    }
    
    .parent-menu-item:hover,
    .single-menu-item:hover {
        transform: translateX(4px) scale(1.02);
    }
    
    .collapsed-mode .parent-menu-item:hover,
    .collapsed-mode .single-menu-item:hover {
        transform: scale(1.1);
    }
    
    /* 圖示容器動畫 */
    .icon-container {
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }
    
    .parent-menu-item:hover .icon-container,
    .single-menu-item:hover .icon-container {
        transform: rotate(5deg) scale(1.1);
    }
    
    /* 子選單動畫 */
    .submenu {
        position: relative;
    }
    
    .submenu::before {
        content: '';
        position: absolute;
        left: -2px;
        top: 0;
        bottom: 0;
        width: 2px;
        background: linear-gradient(to bottom, transparent, rgb(59 130 246 / 0.3), transparent);
        transition: all 0.3s ease;
    }
    
    .submenu-item {
        position: relative;
        transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
    }
    
    .submenu-item:hover {
        transform: translateX(6px);
        padding-left: 1rem;
    }
    
    /* 搜尋結果樣式 */
    .search-results {
        animation: slideInUp 0.3s ease-out;
    }
    
    .search-result-item {
        transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
    }
    
    .search-result-item:hover {
        transform: translateX(4px) scale(1.02);
        box-shadow: 0 4px 12px rgba(59, 130, 246, 0.15);
    }
    
    /* 使用者區域樣式 */
    .user-section {
        backdrop-filter: blur(10px);
    }
    
    .user-card {
        transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
    }
    
    .user-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
    }
    
    .quick-action-btn {
        transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
    }
    
    .quick-action-btn:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    }
    
    /* 工具提示樣式 */
    .tooltip {
        backdrop-filter: blur(10px);
        box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
    }
    
    /* 展開按鈕樣式 */
    .expand-button button {
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }
    
    .expand-button button:hover {
        box-shadow: 0 8px 25px rgba(59, 130, 246, 0.3);
    }
    
    /* 響應式設計 */
    @media (max-width: 767px) {
        .sidebar-container {
            position: fixed;
            top: 0;
            left: 0;
            height: 100vh;
            z-index: 40;
            transform: translateX(-100%);
        }
        
        .sidebar-container.mobile-open {
            transform: translateX(0);
        }
        
        .navigation-menu {
            padding-bottom: 2rem;
        }
    }
    
    @media (min-width: 768px) and (max-width: 1023px) {
        .sidebar-container {
            width: 4rem !important;
        }
        
        .sidebar-container:hover {
            width: 16rem !important;
        }
    }
    
    /* 動畫關鍵幀 */
    @keyframes slideInUp {
        from {
            opacity: 0;
            transform: translateY(10px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    
    @keyframes pulse {
        0%, 100% {
            opacity: 1;
        }
        50% {
            opacity: 0.5;
        }
    }
    
    @keyframes bounce {
        0%, 100% {
            transform: translateY(0);
        }
        50% {
            transform: translateY(-4px);
        }
    }
    
    /* 深色模式特殊樣式 */
    .dark .user-card:hover {
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.3);
    }
    
    .dark .search-input:focus {
        box-shadow: 0 10px 25px -5px rgba(59, 130, 246, 0.2), 0 10px 10px -5px rgba(59, 130, 246, 0.1);
    }
    
    .dark .search-result-item:hover {
        box-shadow: 0 4px 12px rgba(59, 130, 246, 0.25);
    }
    
    /* 高對比模式支援 */
    @media (prefers-contrast: high) {
        .parent-menu-item,
        .single-menu-item,
        .submenu-item {
            border: 1px solid transparent;
        }
        
        .parent-menu-item:hover,
        .single-menu-item:hover,
        .submenu-item:hover {
            border-color: currentColor;
        }
    }
    
    /* 減少動畫偏好 */
    @media (prefers-reduced-motion: reduce) {
        .sidebar-container,
        .menu-item-wrapper,
        .parent-menu-item,
        .single-menu-item,
        .submenu-item,
        .search-result-item,
        .user-card,
        .quick-action-btn {
            transition: none;
        }
        
        .search-results {
            animation: none;
        }
    }
</style>
@endpush