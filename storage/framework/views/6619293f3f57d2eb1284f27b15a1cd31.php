<div class="flex flex-col h-full bg-white dark:bg-gray-800 border-r border-gray-200 dark:border-gray-700">
    
    <!-- Logo 區域 -->
    <div class="flex items-center justify-center h-16 px-4 bg-primary-600 dark:bg-primary-700 border-b border-primary-700 dark:border-primary-600">
        <div class="flex items-center">
            <div class="w-8 h-8 bg-white rounded-lg flex items-center justify-center mr-3">
                <svg class="w-5 h-5 text-primary-600" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M3 4a1 1 0 011-1h12a1 1 0 011 1v2a1 1 0 01-1 1H4a1 1 0 01-1-1V4zM3 10a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H4a1 1 0 01-1-1v-6zM14 9a1 1 0 00-1 1v6a1 1 0 001 1h2a1 1 0 001-1v-6a1 1 0 00-1-1h-2z"></path>
                </svg>
            </div>
            <h1 class="text-lg font-bold text-white truncate">
                <?php echo e(config('app.name', 'Admin System')); ?>

            </h1>
        </div>
    </div>
    
    <!-- 導航選單 -->
    <nav class="flex-1 px-3 py-4 space-y-1 overflow-y-auto scrollbar-thin scrollbar-thumb-gray-300 dark:scrollbar-thumb-gray-600 scrollbar-track-transparent">
        
        <?php $__currentLoopData = $menuItems; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <?php if(isset($item['children']) && count($item['children']) > 0): ?>
                <!-- 有子選單的項目 -->
                <div x-data="{ open: <?php echo e($this->isActiveRoute($item['route']) ? 'true' : 'false'); ?> }" class="space-y-1">
                    
                    <!-- 父選單項目 -->
                    <button @click="open = !open" 
                            class="w-full flex items-center justify-between px-3 py-2 text-sm font-medium rounded-lg transition-colors duration-200 <?php echo e($this->isActiveRoute($item['route']) ? 'bg-primary-100 dark:bg-primary-900/50 text-primary-700 dark:text-primary-300' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700'); ?>">
                        <div class="flex items-center">
                            <svg class="w-5 h-5 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <?php echo $this->getIcon($item['icon']); ?>

                            </svg>
                            <span class="truncate"><?php echo e($item['name']); ?></span>
                        </div>
                        <svg class="w-4 h-4 transition-transform duration-200" 
                             :class="{ 'rotate-90': open }" 
                             fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                        </svg>
                    </button>
                    
                    <!-- 子選單項目 -->
                    <div x-show="open" 
                         x-transition:enter="transition ease-out duration-200"
                         x-transition:enter-start="opacity-0 transform -translate-y-2"
                         x-transition:enter-end="opacity-100 transform translate-y-0"
                         x-transition:leave="transition ease-in duration-150"
                         x-transition:leave-start="opacity-100 transform translate-y-0"
                         x-transition:leave-end="opacity-0 transform -translate-y-2"
                         class="ml-6 space-y-1">
                        
                        <?php $__currentLoopData = $item['children']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $child): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <a href="<?php echo e(route($child['route'])); ?>" 
                               class="flex items-center px-3 py-2 text-sm rounded-lg transition-colors duration-200 <?php echo e($this->isActiveRoute($child['route']) ? 'bg-primary-100 dark:bg-primary-900/50 text-primary-700 dark:text-primary-300 border-r-2 border-primary-500' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 hover:text-gray-900 dark:hover:text-gray-200'); ?>">
                                <div class="w-2 h-2 bg-current rounded-full mr-3 flex-shrink-0 opacity-60"></div>
                                <span class="truncate"><?php echo e($child['name']); ?></span>
                            </a>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        
                    </div>
                    
                </div>
            <?php else: ?>
                <!-- 單一選單項目 -->
                <a href="<?php echo e(route($item['route'])); ?>" 
                   class="flex items-center px-3 py-2 text-sm font-medium rounded-lg transition-colors duration-200 <?php echo e($this->isActiveRoute($item['route']) ? 'bg-primary-100 dark:bg-primary-900/50 text-primary-700 dark:text-primary-300 border-r-2 border-primary-500' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 hover:text-gray-900 dark:hover:text-gray-200'); ?>">
                    <svg class="w-5 h-5 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <?php echo $this->getIcon($item['icon']); ?>

                    </svg>
                    <span class="truncate"><?php echo e($item['name']); ?></span>
                </a>
            <?php endif; ?>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        
    </nav>
    
    <!-- 使用者資訊區域 -->
    <div class="px-3 py-4 border-t border-gray-200 dark:border-gray-700">
        <div class="flex items-center px-3 py-2 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
            <div class="flex-shrink-0">
                <div class="w-8 h-8 bg-primary-500 rounded-full flex items-center justify-center">
                    <span class="text-sm font-medium text-white">
                        <?php echo e(mb_substr(auth()->user()->name ?? auth()->user()->username ?? 'U', 0, 1)); ?>

                    </span>
                </div>
            </div>
            <div class="ml-3 flex-1 min-w-0">
                <p class="text-sm font-medium text-gray-900 dark:text-gray-100 truncate">
                    <?php echo e(auth()->user()->name ?? auth()->user()->username); ?>

                </p>
                <p class="text-xs text-gray-500 dark:text-gray-400 truncate">
                    <?php echo e(auth()->user()->email ?? '無電子郵件'); ?>

                </p>
            </div>
        </div>
        
        <!-- 快速操作按鈕 -->
        <div class="mt-3 flex space-x-2">
            <button class="flex-1 px-3 py-2 text-xs font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-md hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors duration-200">
                個人設定
            </button>
            <button class="flex-1 px-3 py-2 text-xs font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-md hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors duration-200">
                說明
            </button>
        </div>
    </div>
    
</div>

<?php $__env->startPush('styles'); ?>
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
</style>
<?php $__env->stopPush(); ?><?php /**PATH /home/chris/Projects/Taipei_Projects/mg.fg168.net/resources/views/livewire/admin/layout/sidebar.blade.php ENDPATH**/ ?>