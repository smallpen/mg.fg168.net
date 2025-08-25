<div class="theme-toggle-wrapper">
    <div class="relative flex items-center">
        <!-- 主題切換按鈕 -->
        <button 
            wire:click="toggleTheme"
            type="button"
            class="theme-toggle-btn {{ $isLoading ? 'theme-loading' : '' }}"
            title="{{ __('theme.current') }}：{{ $this->themeName }}，{{ __('theme.toggle') }}"
            aria-label="{{ __('theme.current') }}：{{ $this->themeName }}，{{ __('theme.toggle') }}"
            @disabled($isLoading)
        >
        <!-- 亮色主題圖示 (太陽) -->
        <svg 
            class="theme-toggle-icon {{ $currentTheme === 'light' ? 'active' : 'inactive' }}"
            fill="none" 
            stroke="currentColor" 
            viewBox="0 0 24 24"
        >
            <path 
                stroke-linecap="round" 
                stroke-linejoin="round" 
                stroke-width="2" 
                d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"
            />
        </svg>
        
        <!-- 暗色主題圖示 (月亮) -->
        <svg 
            class="theme-toggle-icon {{ $currentTheme === 'dark' ? 'active' : 'inactive' }}"
            fill="none" 
            stroke="currentColor" 
            viewBox="0 0 24 24"
        >
            <path 
                stroke-linecap="round" 
                stroke-linejoin="round" 
                stroke-width="2" 
                d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"
            />
        </svg>

        <!-- 自動模式圖示 (電腦) -->
        <svg 
            class="theme-toggle-icon {{ $currentTheme === 'auto' ? 'active' : 'inactive' }}"
            fill="none" 
            stroke="currentColor" 
            viewBox="0 0 24 24"
        >
            <path 
                stroke-linecap="round" 
                stroke-linejoin="round" 
                stroke-width="2" 
                d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"
            />
        </svg>

        <!-- 自動模式指示器 -->
        @if($currentTheme === 'auto')
            <div class="auto-theme-indicator"></div>
        @endif
    </button>

    <!-- 主題選擇下拉選單 (可選) -->
    <div 
        x-data="{ open: false }" 
        class="relative"
        @click.away="open = false"
    >
        <!-- 下拉選單觸發按鈕 -->
        <button 
            @click="open = !open"
            type="button"
            class="ml-2 relative inline-flex items-center justify-center w-8 h-8 rounded-md bg-gray-100 dark:bg-gray-800 hover:bg-gray-200 dark:hover:bg-gray-700 transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800"
            title="{{ __('theme.title') }}"
            aria-label="{{ __('theme.title') }}"
        >
            <svg class="w-4 h-4 text-gray-600 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
            </svg>
        </button>

        <!-- 下拉選單內容 -->
        <div 
            x-show="open"
            x-transition:enter="transition ease-out duration-100"
            x-transition:enter-start="transform opacity-0 scale-95"
            x-transition:enter-end="transform opacity-100 scale-100"
            x-transition:leave="transition ease-in duration-75"
            x-transition:leave-start="transform opacity-100 scale-100"
            x-transition:leave-end="transform opacity-0 scale-95"
            class="theme-dropdown"
            style="display: none;"
        >
            <div class="py-1">
                <!-- 亮色主題選項 -->
                <button 
                    wire:click="setTheme('light')"
                    @click="open = false"
                    class="theme-dropdown-item {{ $currentTheme === 'light' ? 'active' : '' }}"
                >
                    <svg class="theme-dropdown-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/>
                    </svg>
                    {{ __('theme.light') }}
                    @if($currentTheme === 'light')
                        <svg class="theme-dropdown-check" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                        </svg>
                    @endif
                </button>

                <!-- 暗色主題選項 -->
                <button 
                    wire:click="setTheme('dark')"
                    @click="open = false"
                    class="theme-dropdown-item {{ $currentTheme === 'dark' ? 'active' : '' }}"
                >
                    <svg class="theme-dropdown-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/>
                    </svg>
                    {{ __('theme.dark') }}
                    @if($currentTheme === 'dark')
                        <svg class="theme-dropdown-check" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                        </svg>
                    @endif
                </button>

                <!-- 自動模式選項 -->
                <button 
                    wire:click="setTheme('auto')"
                    @click="open = false"
                    class="theme-dropdown-item {{ $currentTheme === 'auto' ? 'active' : '' }}"
                >
                    <svg class="theme-dropdown-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                    </svg>
                    {{ __('theme.auto') }}
                    <span class="text-xs text-gray-500 dark:text-gray-400 ml-1">({{ __('theme.follow_system') }})</span>
                    @if($currentTheme === 'auto')
                        <svg class="theme-dropdown-check" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                        </svg>
                    @endif
                </button>

                @if(!empty($customThemes))
                    <!-- 分隔線 -->
                    <div class="border-t border-gray-200 dark:border-gray-600 my-1"></div>
                    
                    <!-- 自訂主題選項 -->
                    @foreach($customThemes as $themeKey => $themeConfig)
                        <button 
                            wire:click="setTheme('{{ $themeKey }}')"
                            @click="open = false"
                            class="theme-dropdown-item {{ $currentTheme === $themeKey ? 'active' : '' }}"
                        >
                            <svg class="theme-dropdown-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zM21 5a2 2 0 00-2-2h-4a2 2 0 00-2 2v12a4 4 0 004 4h4a2 2 0 002-2V5z"/>
                            </svg>
                            {{ $themeConfig['name'] ?? $themeKey }}
                            @if($currentTheme === $themeKey)
                                <svg class="theme-dropdown-check" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                </svg>
                            @endif
                        </button>
                    @endforeach
                @endif
            </div>
            </div>
        </div>
    </div>

    <!-- 主題切換過渡效果遮罩 -->
    <div class="theme-transition-overlay" id="themeTransitionOverlay"></div>

    <!-- JavaScript 用於主題系統管理 -->
<script>
document.addEventListener('livewire:init', () => {
    const htmlElement = document.documentElement;
    let systemThemeMediaQuery = window.matchMedia('(prefers-color-scheme: dark)');
    
    // 系統主題檢測函數
    function getSystemTheme() {
        return systemThemeMediaQuery.matches ? 'dark' : 'light';
    }
    
    // 應用主題到 DOM
    function applyThemeToDOM(theme) {
        const overlay = document.getElementById('themeTransitionOverlay');
        
        // 顯示過渡效果
        if (overlay) {
            overlay.classList.add('active');
        }
        
        setTimeout(() => {
            if (theme === 'auto') {
                const systemTheme = getSystemTheme();
                htmlElement.setAttribute('data-theme', 'auto');
                if (systemTheme === 'dark') {
                    htmlElement.classList.add('dark');
                } else {
                    htmlElement.classList.remove('dark');
                }
            } else {
                htmlElement.setAttribute('data-theme', theme);
                if (theme === 'dark') {
                    htmlElement.classList.add('dark');
                } else {
                    htmlElement.classList.remove('dark');
                }
            }
            
            // 隱藏過渡效果
            if (overlay) {
                setTimeout(() => {
                    overlay.classList.remove('active');
                }, 150);
            }
        }, 50);
    }
    
    // 應用自訂主題到 DOM
    function applyCustomThemeToDOM(config) {
        const overlay = document.getElementById('themeTransitionOverlay');
        
        // 顯示過渡效果
        if (overlay) {
            overlay.classList.add('active');
        }
        
        setTimeout(() => {
            // 設定主題屬性
            htmlElement.setAttribute('data-theme', 'custom');
            htmlElement.setAttribute('data-custom-theme', config.name);
            
            // 移除暗色類別（自訂主題有自己的顏色管理）
            htmlElement.classList.remove('dark');
            
            // 應用自訂顏色變數
            if (config.colors) {
                Object.entries(config.colors).forEach(([key, value]) => {
                    htmlElement.style.setProperty(`--custom-${key}`, value);
                });
            }
            
            if (config.backgrounds) {
                Object.entries(config.backgrounds).forEach(([key, value]) => {
                    htmlElement.style.setProperty(`--custom-bg-${key}`, value);
                });
            }
            
            if (config.texts) {
                Object.entries(config.texts).forEach(([key, value]) => {
                    htmlElement.style.setProperty(`--custom-text-${key}`, value);
                });
            }
            
            if (config.borders) {
                Object.entries(config.borders).forEach(([key, value]) => {
                    htmlElement.style.setProperty(`--custom-border-${key}`, value);
                });
            }
            
            // 隱藏過渡效果
            if (overlay) {
                setTimeout(() => {
                    overlay.classList.remove('active');
                }, 150);
            }
        }, 50);
    }
    
    // 監聽主題變更事件
    Livewire.on('update-theme-attribute', (event) => {
        const theme = event.theme;
        applyThemeToDOM(theme);
    });
    
    // 監聽自訂主題應用事件
    Livewire.on('apply-custom-theme', (event) => {
        const config = event.config;
        applyCustomThemeToDOM(config);
    });
    
    // 監聽儲存主題到本地存儲事件
    Livewire.on('save-theme-to-storage', (event) => {
        const theme = event.theme;
        localStorage.setItem('theme', theme);
        
        // 觸發自定義事件，通知其他頁面標籤
        window.dispatchEvent(new CustomEvent('theme-changed', { 
            detail: { theme } 
        }));
    });
    
    // 監聽系統主題檢測事件
    Livewire.on('detect-system-theme', () => {
        const systemTheme = getSystemTheme();
        Livewire.dispatch('system-theme-changed', { systemTheme });
    });
    
    // 監聽系統主題變更
    systemThemeMediaQuery.addEventListener('change', (e) => {
        const systemTheme = e.matches ? 'dark' : 'light';
        
        // 只有在自動模式下才響應系統主題變更
        if (htmlElement.getAttribute('data-theme') === 'auto') {
            applyThemeToDOM('auto');
        }
        
        // 通知 Livewire 元件
        Livewire.dispatch('system-theme-changed', { systemTheme });
    });
    
    // 監聽其他頁面標籤的主題變更
    window.addEventListener('storage', (e) => {
        if (e.key === 'theme' && e.newValue) {
            applyThemeToDOM(e.newValue);
        }
    });
    
    // 監聽自定義主題變更事件（同一頁面內的其他元件）
    window.addEventListener('theme-changed', (e) => {
        applyThemeToDOM(e.detail.theme);
    });
    
    // 頁面載入時初始化主題
    function initializeTheme() {
        const savedTheme = localStorage.getItem('theme') || '{{ $currentTheme }}';
        const validThemes = ['light', 'dark', 'auto'];
        const theme = validThemes.includes(savedTheme) ? savedTheme : 'light';
        
        applyThemeToDOM(theme);
        
        // 如果本地存儲的主題與元件狀態不同，同步到後端
        if (savedTheme !== '{{ $currentTheme }}' && validThemes.includes(savedTheme)) {
            Livewire.dispatch('sync-theme-from-storage', { theme: savedTheme });
        }
    }
    
    // 初始化主題
    initializeTheme();
    
    // 監聽主題同步事件
    Livewire.on('sync-theme-from-storage', (event) => {
        // 這個事件由前端觸發，用於同步本地存儲的主題到後端
        // 實際的同步邏輯在 Livewire 元件中處理
    });
    
    // 鍵盤快捷鍵支援 (Ctrl/Cmd + Shift + T)
    document.addEventListener('keydown', (e) => {
        if ((e.ctrlKey || e.metaKey) && e.shiftKey && e.key === 'T') {
            e.preventDefault();
            Livewire.dispatch('toggle-theme-shortcut');
        }
    });
    
    // 監聽鍵盤快捷鍵事件
    Livewire.on('toggle-theme-shortcut', () => {
        // 觸發主題切換
        @this.toggleTheme();
    });
    
    // 無障礙功能：高對比模式檢測
    if (window.matchMedia('(prefers-contrast: high)').matches) {
        htmlElement.classList.add('high-contrast');
    }
    
    // 無障礙功能：減少動畫偏好檢測
    if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
        htmlElement.classList.add('reduce-motion');
    }
    
    // 調試模式：在控制台顯示主題狀態
    if (localStorage.getItem('theme-debug') === 'true') {
        console.log('Theme System Debug Mode Enabled');
        
        // 監聽所有主題相關事件
        ['theme-changed', 'update-theme-attribute', 'system-theme-changed'].forEach(eventName => {
            Livewire.on(eventName, (event) => {
                console.log(`Theme Event: ${eventName}`, event);
            });
        });
        
        // 顯示當前主題狀態
        const observer = new MutationObserver((mutations) => {
            mutations.forEach((mutation) => {
                if (mutation.type === 'attributes' && mutation.attributeName === 'data-theme') {
                    console.log('Theme changed to:', htmlElement.getAttribute('data-theme'));
                    console.log('Dark class present:', htmlElement.classList.contains('dark'));
                    console.log('System theme:', getSystemTheme());
                }
            });
        });
        
        observer.observe(htmlElement, {
            attributes: true,
            attributeFilter: ['data-theme', 'class']
        });
    }
});

// 全域函數：手動觸發主題切換（供其他腳本使用）
window.toggleTheme = function() {
    Livewire.dispatch('toggle-theme-global');
};

// 全域函數：設定特定主題（供其他腳本使用）
window.setTheme = function(theme) {
    if (['light', 'dark', 'auto'].includes(theme)) {
        Livewire.dispatch('set-theme-global', { theme });
    }
};

// 全域函數：取得當前主題
window.getCurrentTheme = function() {
    return document.documentElement.getAttribute('data-theme') || 'light';
};

// 全域函數：取得系統主題
window.getSystemTheme = function() {
    return window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
};
</script>
</div>