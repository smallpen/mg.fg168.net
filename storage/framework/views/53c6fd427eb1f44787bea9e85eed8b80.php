<div class="card">
    <div class="card-header flex justify-between items-center">
        <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100"><?php echo e(__('快速操作')); ?></h3>
        <button 
            wire:click="refresh" 
            class="btn btn-sm btn-outline-primary"
            wire:loading.attr="disabled"
            title="<?php echo e(__('重新整理')); ?>"
        >
            <svg wire:loading.remove class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
            </svg>
            <svg wire:loading class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
        </button>
    </div>
    
    <div class="card-body">
        <?php if(empty($this->quickActions)): ?>
            <!-- 無權限時的顯示 -->
            <div class="text-center py-8">
                <svg class="w-12 h-12 text-gray-400 dark:text-gray-600 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                </svg>
                <p class="text-gray-500 dark:text-gray-400"><?php echo e(__('目前沒有可用的快速操作')); ?></p>
                <p class="text-sm text-gray-400 dark:text-gray-500 mt-1"><?php echo e(__('請聯絡管理員取得相關權限')); ?></p>
            </div>
        <?php else: ?>
            <!-- 快速操作網格 -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
                <?php $__currentLoopData = $this->quickActions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $action): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <div class="group relative">
                        <button
                            wire:click="handleAction('<?php echo e($action['route']); ?>')"
                            class="w-full p-4 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg hover:shadow-md hover:border-<?php echo e($action['color']); ?>-300 dark:hover:border-<?php echo e($action['color']); ?>-600 transition-all duration-200 text-left group-hover:scale-105"
                            wire:loading.attr="disabled"
                        >
                            <div class="flex items-start space-x-3">
                                <!-- 圖示 -->
                                <div class="flex-shrink-0">
                                    <div class="w-10 h-10 bg-<?php echo e($action['color']); ?>-100 dark:bg-<?php echo e($action['color']); ?>-900 rounded-lg flex items-center justify-center group-hover:bg-<?php echo e($action['color']); ?>-200 dark:group-hover:bg-<?php echo e($action['color']); ?>-800 transition-colors duration-200">
                                        <?php switch($action['icon']):
                                            case ('user-plus'): ?>
                                                <svg class="w-5 h-5 text-<?php echo e($action['color']); ?>-600 dark:text-<?php echo e($action['color']); ?>-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"></path>
                                                </svg>
                                                <?php break; ?>
                                            <?php case ('users'): ?>
                                                <svg class="w-5 h-5 text-<?php echo e($action['color']); ?>-600 dark:text-<?php echo e($action['color']); ?>-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
                                                </svg>
                                                <?php break; ?>
                                            <?php case ('shield-plus'): ?>
                                                <svg class="w-5 h-5 text-<?php echo e($action['color']); ?>-600 dark:text-<?php echo e($action['color']); ?>-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                                </svg>
                                                <?php break; ?>
                                            <?php case ('shield-check'): ?>
                                                <svg class="w-5 h-5 text-<?php echo e($action['color']); ?>-600 dark:text-<?php echo e($action['color']); ?>-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                                                </svg>
                                                <?php break; ?>
                                            <?php case ('key'): ?>
                                                <svg class="w-5 h-5 text-<?php echo e($action['color']); ?>-600 dark:text-<?php echo e($action['color']); ?>-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"></path>
                                                </svg>
                                                <?php break; ?>
                                            <?php case ('cog'): ?>
                                                <svg class="w-5 h-5 text-<?php echo e($action['color']); ?>-600 dark:text-<?php echo e($action['color']); ?>-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                                </svg>
                                                <?php break; ?>
                                            <?php case ('clipboard-list'): ?>
                                                <svg class="w-5 h-5 text-<?php echo e($action['color']); ?>-600 dark:text-<?php echo e($action['color']); ?>-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path>
                                                </svg>
                                                <?php break; ?>
                                            <?php case ('download'): ?>
                                                <svg class="w-5 h-5 text-<?php echo e($action['color']); ?>-600 dark:text-<?php echo e($action['color']); ?>-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                                </svg>
                                                <?php break; ?>
                                            <?php case ('database'): ?>
                                                <svg class="w-5 h-5 text-<?php echo e($action['color']); ?>-600 dark:text-<?php echo e($action['color']); ?>-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4"></path>
                                                </svg>
                                                <?php break; ?>
                                            <?php default: ?>
                                                <svg class="w-5 h-5 text-<?php echo e($action['color']); ?>-600 dark:text-<?php echo e($action['color']); ?>-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                                </svg>
                                        <?php endswitch; ?>
                                    </div>
                                </div>
                                
                                <!-- 內容 -->
                                <div class="flex-1 min-w-0">
                                    <h4 class="text-sm font-medium text-gray-900 dark:text-gray-100 group-hover:text-<?php echo e($action['color']); ?>-600 dark:group-hover:text-<?php echo e($action['color']); ?>-400 transition-colors duration-200">
                                        <?php echo e($action['title']); ?>

                                    </h4>
                                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1 line-clamp-2">
                                        <?php echo e($action['description']); ?>

                                    </p>
                                </div>
                            </div>
                            
                            <!-- 載入指示器 -->
                            <div wire:loading.flex wire:target="handleAction('<?php echo e($action['route']); ?>')" class="absolute inset-0 bg-white dark:bg-gray-800 bg-opacity-75 rounded-lg items-center justify-center">
                                <svg class="animate-spin w-5 h-5 text-<?php echo e($action['color']); ?>-600" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                            </div>
                        </button>
                    </div>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </div>
            
            <!-- 操作提示 -->
            <div class="mt-4 text-center">
                <p class="text-xs text-gray-400 dark:text-gray-500">
                    <?php echo e(__('點擊上方按鈕快速存取常用功能')); ?>

                </p>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php $__env->startPush('scripts'); ?>
<script>
    // 監聽快速操作更新事件
    window.addEventListener('actions-refreshed', event => {
        if (event.detail.message) {
            // 這裡可以整合通知系統
            console.log(event.detail.message);
        }
    });
</script>
<?php $__env->stopPush(); ?><?php /**PATH /home/chris/Projects/Taipei_Projects/mg.fg168.net/resources/views/livewire/admin/dashboard/quick-actions.blade.php ENDPATH**/ ?>