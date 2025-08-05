<div class="relative flex items-center">
    <!-- 主題切換按鈕 -->
    <button 
        wire:click="toggleTheme"
        type="button"
        class="theme-toggle-btn"
        title="<?php echo e($currentTheme === 'light' ? '切換到暗黑主題' : '切換到淺色主題'); ?>"
        aria-label="<?php echo e($currentTheme === 'light' ? '切換到暗黑主題' : '切換到淺色主題'); ?>"
    >
        <!-- 淺色主題圖示 (太陽) -->
        <svg 
            class="theme-toggle-icon <?php echo e($currentTheme === 'light' ? 'active' : 'inactive'); ?>"
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
        
        <!-- 暗黑主題圖示 (月亮) -->
        <svg 
            class="theme-toggle-icon <?php echo e($currentTheme === 'dark' ? 'active' : 'inactive'); ?>"
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
            title="主題選項"
            aria-label="主題選項"
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
                <!-- 淺色主題選項 -->
                <button 
                    wire:click="setTheme('light')"
                    @click="open = false"
                    class="theme-dropdown-item <?php echo e($currentTheme === 'light' ? 'active' : ''); ?>"
                >
                    <svg class="theme-dropdown-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/>
                    </svg>
                    淺色主題
                    <?php if($currentTheme === 'light'): ?>
                        <svg class="theme-dropdown-check" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                        </svg>
                    <?php endif; ?>
                </button>

                <!-- 暗黑主題選項 -->
                <button 
                    wire:click="setTheme('dark')"
                    @click="open = false"
                    class="theme-dropdown-item <?php echo e($currentTheme === 'dark' ? 'active' : ''); ?>"
                >
                    <svg class="theme-dropdown-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/>
                    </svg>
                    暗黑主題
                    <?php if($currentTheme === 'dark'): ?>
                        <svg class="theme-dropdown-check" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                        </svg>
                    <?php endif; ?>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- JavaScript 用於即時更新 HTML class -->
<script>
document.addEventListener('livewire:init', () => {
    // 監聽主題變更事件
    Livewire.on('update-theme-class', (event) => {
        const theme = event.theme;
        const htmlElement = document.documentElement;
        
        if (theme === 'dark') {
            htmlElement.classList.add('dark');
        } else {
            htmlElement.classList.remove('dark');
        }
        
        // 儲存到 localStorage 以便下次訪問時使用
        localStorage.setItem('theme', theme);
    });
    
    // 頁面載入時檢查並應用主題
    const savedTheme = localStorage.getItem('theme') || '<?php echo e($currentTheme); ?>';
    const htmlElement = document.documentElement;
    
    if (savedTheme === 'dark') {
        htmlElement.classList.add('dark');
    } else {
        htmlElement.classList.remove('dark');
    }
});
</script><?php /**PATH /home/chris/Projects/Taipei_Projects/mg.fg168.net/resources/views/livewire/admin/layout/theme-toggle.blade.php ENDPATH**/ ?>