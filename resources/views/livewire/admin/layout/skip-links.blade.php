<div class="skip-links" 
     x-data="{ visible: @entangle('visible') }"
     x-show="visible"
     x-transition:enter="transition ease-out duration-200"
     x-transition:enter-start="opacity-0 -translate-y-2"
     x-transition:enter-end="opacity-100 translate-y-0"
     x-transition:leave="transition ease-in duration-150"
     x-transition:leave-start="opacity-100 translate-y-0"
     x-transition:leave-end="opacity-0 -translate-y-2"
     @if(!$this->isEnabled) style="display: none;" @endif
     x-init="
        let hideTimeout;
        
        // 監聽延遲隱藏事件
        Livewire.on('hide-skip-links-delayed', () => {
            hideTimeout = setTimeout(() => {
                $wire.call('hide');
            }, 300);
        });
        
        // 監聽跳轉到元素事件
        Livewire.on('skip-to-element', (data) => {
            const targetId = data[0].targetId;
            const targetElement = document.getElementById(targetId);
            
            if (targetElement) {
                // 滾動到目標元素
                targetElement.scrollIntoView({ 
                    behavior: 'smooth', 
                    block: 'start' 
                });
                
                // 設定焦點
                if (targetElement.tabIndex === -1) {
                    targetElement.tabIndex = -1;
                }
                targetElement.focus();
                
                // 添加視覺提示
                targetElement.classList.add('skip-target-highlight');
                setTimeout(() => {
                    targetElement.classList.remove('skip-target-highlight');
                }, 2000);
            }
        });
        
        // 監聽鍵盤快捷鍵
        document.addEventListener('keydown', (event) => {
            if (event.altKey) {
                switch (event.key.toLowerCase()) {
                    case 'c':
                        event.preventDefault();
                        $wire.call('skipTo', 'main-content');
                        break;
                    case 'n':
                        event.preventDefault();
                        $wire.call('skipTo', 'navigation');
                        break;
                    case 's':
                        event.preventDefault();
                        $wire.call('skipTo', 'search');
                        break;
                    case 'u':
                        event.preventDefault();
                        $wire.call('skipTo', 'user-menu');
                        break;
                }
            }
            
            // Tab 鍵顯示跳轉連結
            if (event.key === 'Tab' && !event.shiftKey) {
                $wire.call('show');
            }
        });
        
        // 清除延遲隱藏計時器
        document.addEventListener('focusin', () => {
            if (hideTimeout) {
                clearTimeout(hideTimeout);
            }
        });
     ">

    <nav class="fixed top-0 left-0 right-0 z-50 bg-blue-600 text-white shadow-lg"
         role="navigation"
         aria-label="跳轉連結">
        
        <div class="container mx-auto px-4 py-2">
            <ul class="flex flex-wrap gap-4" role="list">
                @foreach($links as $link)
                    <li role="listitem">
                        <a href="#{{ $link['id'] }}"
                           wire:click="skipTo('{{ $link['id'] }}')"
                           @focus="$wire.handleFocus()"
                           @blur="$wire.handleBlur()"
                           class="inline-flex items-center px-3 py-2 text-sm font-medium bg-blue-700 hover:bg-blue-800 rounded-md focus:outline-none focus:ring-2 focus:ring-white focus:ring-offset-2 focus:ring-offset-blue-600 transition-colors"
                           title="{{ $link['description'] }} ({{ $link['shortcut'] }})">
                            
                            <!-- 圖示 -->
                            @switch($link['id'])
                                @case('main-content')
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                    </svg>
                                    @break
                                @case('navigation')
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                                    </svg>
                                    @break
                                @case('search')
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                    </svg>
                                    @break
                                @case('user-menu')
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                    </svg>
                                    @break
                            @endswitch
                            
                            <span>{{ $link['label'] }}</span>
                            
                            <!-- 快捷鍵提示 -->
                            <span class="ml-2 text-xs opacity-75 font-mono">
                                {{ $link['shortcut'] }}
                            </span>
                        </a>
                    </li>
                @endforeach
            </ul>
        </div>
        
        <!-- 關閉按鈕 -->
        <button type="button"
                wire:click="hide"
                class="absolute top-2 right-4 p-1 text-white hover:text-gray-200 focus:outline-none focus:ring-2 focus:ring-white rounded"
                aria-label="隱藏跳轉連結">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
            </svg>
        </button>
    </nav>

    <style>
        .skip-target-highlight {
            outline: 3px solid #3B82F6 !important;
            outline-offset: 2px;
            animation: pulse 1s ease-in-out 2;
        }

        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.7; }
        }
    </style>
</div>