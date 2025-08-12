<div class="space-y-8 p-6">
    <!-- 動畫展示標題 -->
    <div class="text-center">
        <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-2">
            互動動畫系統展示
        </h1>
        <p class="text-gray-600 dark:text-gray-400">
            展示各種頁面切換、選單、按鈕、載入和手勢動畫效果
        </p>
    </div>

    <!-- 頁面切換動畫展示 -->
    <div class="card">
        <div class="card-header">
            <h2 class="text-xl font-semibold text-gray-900 dark:text-white">
                頁面切換動畫
            </h2>
        </div>
        <div class="card-body">
            <div class="grid grid-cols-2 md:grid-cols-3 gap-4 mb-6">
                @foreach($animationTypes as $type => $name)
                    <button 
                        wire:click="triggerPageTransition('{{ $type }}')"
                        class="btn-animated btn-outline touch-feedback"
                        data-animation="{{ $type }}"
                    >
                        {{ $name }}
                    </button>
                @endforeach
            </div>
            
            <!-- 動畫預覽區域 -->
            <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-8 min-h-32 flex items-center justify-center">
                <div 
                    id="animation-preview" 
                    class="w-24 h-24 bg-blue-500 rounded-lg flex items-center justify-center text-white font-bold"
                >
                    預覽
                </div>
            </div>
        </div>
    </div>

    <!-- 選單動畫展示 -->
    <div class="card">
        <div class="card-header">
            <h2 class="text-xl font-semibold text-gray-900 dark:text-white">
                選單動畫
            </h2>
        </div>
        <div class="card-body">
            <div class="flex gap-4 mb-6">
                <button 
                    wire:click="toggleSidebar"
                    class="btn-animated btn-primary"
                >
                    {{ $sidebarCollapsed ? '展開' : '收合' }}側邊欄
                </button>
                <button 
                    wire:click="toggleMenu"
                    class="btn-animated btn-secondary"
                >
                    {{ $menuExpanded ? '收合' : '展開' }}選單
                </button>
            </div>
            
            <!-- 模擬側邊欄 -->
            <div class="flex bg-gray-50 dark:bg-gray-800 rounded-lg overflow-hidden">
                <div 
                    class="sidebar-transition bg-white dark:bg-gray-700 border-r border-gray-200 dark:border-gray-600 {{ $sidebarCollapsed ? 'w-16' : 'w-64' }}"
                    style="transition: width 0.3s ease;"
                >
                    <div class="p-4">
                        <div class="sidebar-content {{ $sidebarCollapsed ? 'opacity-0' : 'opacity-100' }}" style="transition: opacity 0.2s ease;">
                            <h3 class="font-semibold text-gray-900 dark:text-white mb-4">選單項目</h3>
                            <nav class="space-y-2">
                                <div class="menu-item p-2 rounded hover:bg-blue-50 dark:hover:bg-blue-900/20 cursor-pointer">
                                    <div class="flex items-center">
                                        <div class="menu-item-icon w-5 h-5 bg-blue-500 rounded mr-3"></div>
                                        <span class="menu-item-text">首頁</span>
                                    </div>
                                </div>
                                <div class="menu-item p-2 rounded hover:bg-blue-50 dark:hover:bg-blue-900/20 cursor-pointer" data-menu-toggle>
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center">
                                            <div class="menu-item-icon w-5 h-5 bg-green-500 rounded mr-3"></div>
                                            <span class="menu-item-text">管理</span>
                                        </div>
                                        <div class="menu-toggle-icon transform transition-transform">▶</div>
                                    </div>
                                </div>
                                <div class="submenu {{ $menuExpanded ? 'expanded' : '' }} ml-8 space-y-1">
                                    <div class="submenu-item p-2 text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white cursor-pointer">
                                        使用者管理
                                    </div>
                                    <div class="submenu-item p-2 text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white cursor-pointer">
                                        系統設定
                                    </div>
                                </div>
                            </nav>
                        </div>
                        <div class="sidebar-icon {{ $sidebarCollapsed ? 'block' : 'hidden' }} text-center">
                            <div class="w-8 h-8 bg-blue-500 rounded mx-auto"></div>
                        </div>
                    </div>
                </div>
                <div class="flex-1 p-4">
                    <p class="text-gray-600 dark:text-gray-400">主要內容區域</p>
                </div>
            </div>
        </div>
    </div>

    <!-- 按鈕動畫展示 -->
    <div class="card">
        <div class="card-header">
            <h2 class="text-xl font-semibold text-gray-900 dark:text-white">
                按鈕動畫
            </h2>
        </div>
        <div class="card-body">
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
                <button 
                    wire:click="triggerButtonAnimation('success')"
                    class="btn-animated btn-ripple btn-success"
                >
                    成功動畫
                </button>
                <button 
                    wire:click="triggerButtonAnimation('error')"
                    class="btn-animated btn-ripple btn-danger"
                >
                    錯誤動畫
                </button>
                <button 
                    wire:click="triggerButtonAnimation('warning')"
                    class="btn-animated btn-ripple btn-warning"
                >
                    警告動畫
                </button>
                <button 
                    wire:click="triggerLoading('spinner')"
                    class="btn-animated btn-ripple btn-primary {{ $isLoading ? 'btn-loading' : '' }}"
                    {{ $isLoading ? 'disabled' : '' }}
                >
                    載入動畫
                </button>
            </div>
            
            <!-- 特殊效果按鈕 -->
            <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
                <button class="btn-glow btn-primary">發光效果</button>
                <button class="btn-gradient text-white">漸變背景</button>
                <button class="btn-border-animation btn-outline">邊框動畫</button>
            </div>
        </div>
    </div>

    <!-- 載入動畫展示 -->
    <div class="card">
        <div class="card-header">
            <h2 class="text-xl font-semibold text-gray-900 dark:text-white">
                載入動畫
            </h2>
        </div>
        <div class="card-body">
            <div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-6">
                @foreach($loadingTypes as $type => $name)
                    <button 
                        wire:click="triggerLoading('{{ $type }}')"
                        class="btn-animated btn-outline"
                    >
                        {{ $name }}
                    </button>
                @endforeach
            </div>
            
            <!-- 載入動畫展示區域 -->
            <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-8">
                <div class="grid grid-cols-2 md:grid-cols-5 gap-8 text-center">
                    <div>
                        <div class="loading-spinner"></div>
                        <p class="text-sm text-gray-600 dark:text-gray-400 mt-2">旋轉載入器</p>
                    </div>
                    <div>
                        <div class="loading-dots">
                            <span></span>
                            <span></span>
                            <span></span>
                        </div>
                        <p class="text-sm text-gray-600 dark:text-gray-400 mt-2">點點載入器</p>
                    </div>
                    <div>
                        <div class="loading-pulse"></div>
                        <p class="text-sm text-gray-600 dark:text-gray-400 mt-2">脈衝載入器</p>
                    </div>
                    <div>
                        <div class="loading-wave">
                            <span></span>
                            <span></span>
                            <span></span>
                            <span></span>
                            <span></span>
                        </div>
                        <p class="text-sm text-gray-600 dark:text-gray-400 mt-2">波浪載入器</p>
                    </div>
                    <div>
                        <div class="loading-ring"></div>
                        <p class="text-sm text-gray-600 dark:text-gray-400 mt-2">環形載入器</p>
                    </div>
                </div>
                
                <!-- 進度條展示 -->
                <div class="mt-8 space-y-4">
                    <div>
                        <p class="text-sm text-gray-600 dark:text-gray-400 mb-2">不定進度條</p>
                        <div class="loading-progress">
                            <div class="loading-progress-bar"></div>
                        </div>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600 dark:text-gray-400 mb-2">分段進度條</p>
                        <div class="loading-progress-steps">
                            <div class="loading-progress-step completed"></div>
                            <div class="loading-progress-step completed"></div>
                            <div class="loading-progress-step active"></div>
                            <div class="loading-progress-step"></div>
                            <div class="loading-progress-step"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 狀態變更動畫展示 -->
    <div class="card">
        <div class="card-header">
            <h2 class="text-xl font-semibold text-gray-900 dark:text-white">
                狀態變更動畫
            </h2>
        </div>
        <div class="card-body">
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
                <button 
                    wire:click="triggerStateAnimation('success')"
                    class="btn-animated btn-outline"
                    id="state-success-btn"
                >
                    成功狀態
                </button>
                <button 
                    wire:click="triggerStateAnimation('error')"
                    class="btn-animated btn-outline"
                    id="state-error-btn"
                >
                    錯誤狀態
                </button>
                <button 
                    wire:click="triggerStateAnimation('warning')"
                    class="btn-animated btn-outline"
                    id="state-warning-btn"
                >
                    警告狀態
                </button>
                <button 
                    wire:click="triggerStateAnimation('info')"
                    class="btn-animated btn-outline"
                    id="state-info-btn"
                >
                    資訊狀態
                </button>
            </div>
            
            <!-- 骨架屏展示 -->
            <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-6">
                <h3 class="text-lg font-semibold mb-4">骨架屏載入效果</h3>
                <div class="space-y-4">
                    <div class="flex items-center space-x-4">
                        <div class="skeleton skeleton-avatar"></div>
                        <div class="flex-1 space-y-2">
                            <div class="skeleton skeleton-text"></div>
                            <div class="skeleton skeleton-text"></div>
                        </div>
                    </div>
                    <div class="skeleton skeleton-card"></div>
                    <div class="flex space-x-2">
                        <div class="skeleton skeleton-button"></div>
                        <div class="skeleton skeleton-button"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 手勢動畫展示 -->
    <div class="card">
        <div class="card-header">
            <h2 class="text-xl font-semibold text-gray-900 dark:text-white">
                手勢動畫支援
            </h2>
        </div>
        <div class="card-body">
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
                <button 
                    wire:click="triggerGestureAnimation('swipe-left')"
                    class="btn-animated btn-outline touch-feedback"
                    id="swipe-left-btn"
                >
                    左滑動畫
                </button>
                <button 
                    wire:click="triggerGestureAnimation('swipe-right')"
                    class="btn-animated btn-outline touch-feedback"
                    id="swipe-right-btn"
                >
                    右滑動畫
                </button>
                <button 
                    wire:click="triggerGestureAnimation('long-press')"
                    class="btn-animated btn-outline long-press"
                    id="long-press-btn"
                >
                    長按動畫
                </button>
                <button 
                    class="btn-animated btn-outline draggable"
                    draggable="true"
                >
                    拖拽元素
                </button>
            </div>
            
            <!-- 手勢展示區域 -->
            <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- 滑動操作展示 -->
                    <div>
                        <h3 class="text-lg font-semibold mb-4">滑動操作</h3>
                        <div class="space-y-2">
                            <div class="swipe-to-delete bg-white dark:bg-gray-700 p-4 rounded border cursor-pointer">
                                <p>向左滑動刪除此項目</p>
                            </div>
                            <div class="swipe-actions bg-white dark:bg-gray-700 p-4 rounded border cursor-pointer">
                                <p>左右滑動查看操作選項</p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- 拖放區域展示 -->
                    <div>
                        <h3 class="text-lg font-semibold mb-4">拖放操作</h3>
                        <div class="drop-zone border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-lg p-8 text-center">
                            <p class="text-gray-600 dark:text-gray-400">將元素拖放到此處</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 通知展示 -->
    @if($showNotification)
        <div 
            class="fixed top-4 right-4 z-50 max-w-sm w-full bg-white dark:bg-gray-800 shadow-lg rounded-lg border border-gray-200 dark:border-gray-700 fade-in"
            id="notification"
        >
            <div class="p-4">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        @if($notificationType === 'success')
                            <div class="w-5 h-5 bg-green-500 rounded-full"></div>
                        @elseif($notificationType === 'error')
                            <div class="w-5 h-5 bg-red-500 rounded-full"></div>
                        @elseif($notificationType === 'warning')
                            <div class="w-5 h-5 bg-yellow-500 rounded-full"></div>
                        @else
                            <div class="w-5 h-5 bg-blue-500 rounded-full"></div>
                        @endif
                    </div>
                    <div class="ml-3 flex-1">
                        <p class="text-sm font-medium text-gray-900 dark:text-white">
                            {{ $notificationMessage }}
                        </p>
                    </div>
                    <div class="ml-4 flex-shrink-0">
                        <button 
                            wire:click="hideNotification"
                            class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"
                        >
                            ✕
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>

<!-- JavaScript 動畫控制 -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // 頁面轉換動畫
    Livewire.on('page-transition', (data) => {
        const preview = document.getElementById('animation-preview');
        const type = data[0].type;
        
        preview.className = `w-24 h-24 bg-blue-500 rounded-lg flex items-center justify-center text-white font-bold page-${type}-enter`;
        
        setTimeout(() => {
            preview.className = 'w-24 h-24 bg-blue-500 rounded-lg flex items-center justify-center text-white font-bold';
        }, 500);
    });
    
    // 按鈕動畫
    Livewire.on('button-animation', (data) => {
        const state = data[0].state;
        const buttons = document.querySelectorAll('.btn-animated');
        
        buttons.forEach(button => {
            if (button.textContent.includes(state === 'success' ? '成功' : state === 'error' ? '錯誤' : '警告')) {
                button.classList.add(`btn-${state}-animation`);
                setTimeout(() => {
                    button.classList.remove(`btn-${state}-animation`);
                }, 500);
            }
        });
    });
    
    // 狀態動畫
    Livewire.on('state-animation', (data) => {
        const state = data[0].state;
        const button = document.getElementById(`state-${state}-btn`);
        
        if (button) {
            button.classList.add(`state-${state}`);
            setTimeout(() => {
                button.classList.remove(`state-${state}`);
            }, 500);
        }
    });
    
    // 手勢動畫
    Livewire.on('gesture-animation', (data) => {
        const gesture = data[0].gesture;
        const button = document.getElementById(`${gesture}-btn`);
        
        if (button) {
            button.classList.add(gesture);
            setTimeout(() => {
                button.classList.remove(gesture);
            }, 300);
        }
    });
    
    // 長按動畫
    document.querySelectorAll('.long-press').forEach(element => {
        let pressTimer = null;
        
        const startPress = () => {
            element.classList.add('pressing');
            pressTimer = setTimeout(() => {
                element.dispatchEvent(new CustomEvent('longpress'));
                element.classList.remove('pressing');
                alert('長按觸發！');
            }, 800);
        };
        
        const endPress = () => {
            if (pressTimer) {
                clearTimeout(pressTimer);
                pressTimer = null;
            }
            element.classList.remove('pressing');
        };
        
        element.addEventListener('mousedown', startPress);
        element.addEventListener('mouseup', endPress);
        element.addEventListener('mouseleave', endPress);
        element.addEventListener('touchstart', startPress);
        element.addEventListener('touchend', endPress);
    });
    
    // 拖拽功能
    document.querySelectorAll('.draggable').forEach(element => {
        element.addEventListener('dragstart', (e) => {
            element.classList.add('dragging');
        });
        
        element.addEventListener('dragend', (e) => {
            element.classList.remove('dragging');
        });
    });
    
    // 拖放區域
    document.querySelectorAll('.drop-zone').forEach(zone => {
        zone.addEventListener('dragover', (e) => {
            e.preventDefault();
            zone.classList.add('drag-over');
        });
        
        zone.addEventListener('dragleave', (e) => {
            zone.classList.remove('drag-over');
        });
        
        zone.addEventListener('drop', (e) => {
            e.preventDefault();
            zone.classList.remove('drag-over');
            alert('元素已放置！');
        });
    });
    
    // 選單切換動畫
    document.querySelectorAll('[data-menu-toggle]').forEach(item => {
        item.addEventListener('click', () => {
            const submenu = item.nextElementSibling;
            const icon = item.querySelector('.menu-toggle-icon');
            
            if (submenu && submenu.classList.contains('submenu')) {
                submenu.classList.toggle('expanded');
                item.classList.toggle('expanded');
            }
        });
    });
});
</script>