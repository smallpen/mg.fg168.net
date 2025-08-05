<div class="card">
    <div class="card-header flex justify-between items-center">
        <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">
            <?php echo e($chartData['title'] ?? '統計圖表'); ?>

        </h3>
        <div class="flex items-center space-x-2">
            <!-- 圖表類型切換 -->
            <div class="flex rounded-lg bg-gray-100 dark:bg-gray-700 p-1">
                <button 
                    wire:click="switchChart('user_activity')"
                    class="px-3 py-1 text-sm rounded-md transition-colors <?php echo e($chartType === 'user_activity' ? 'bg-white dark:bg-gray-600 text-gray-900 dark:text-gray-100 shadow-sm' : 'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100'); ?>"
                >
                    使用者活動
                </button>
                <button 
                    wire:click="switchChart('role_distribution')"
                    class="px-3 py-1 text-sm rounded-md transition-colors <?php echo e($chartType === 'role_distribution' ? 'bg-white dark:bg-gray-600 text-gray-900 dark:text-gray-100 shadow-sm' : 'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100'); ?>"
                >
                    角色分佈
                </button>
            </div>
            
            <!-- 重新整理按鈕 -->
            <button 
                wire:click="refreshChart" 
                class="btn btn-sm btn-outline-primary"
                wire:loading.attr="disabled"
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
    </div>
    
    <div class="card-body">
        <?php if(!empty($chartData)): ?>
            <div class="relative">
                <!-- 圖表容器 -->
                <div class="h-64 flex items-center justify-center" id="chart-container-<?php echo e($chartType); ?>">
                    <?php if($chartType === 'user_activity'): ?>
                        <!-- 線性圖表 (使用 CSS 模擬) -->
                        <div class="w-full h-full flex items-end justify-between space-x-2 px-4">
                            <?php $__currentLoopData = $chartData['datasets'][0]['data']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $index => $value): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <?php
                                    $maxValue = max($chartData['datasets'][0]['data']);
                                    $height = $maxValue > 0 ? ($value / $maxValue) * 100 : 0;
                                ?>
                                <div class="flex flex-col items-center flex-1">
                                    <div class="w-full bg-primary-500 rounded-t transition-all duration-300 hover:bg-primary-600" 
                                         style="height: <?php echo e($height); ?>%"
                                         title="<?php echo e($chartData['labels'][$index]); ?>: <?php echo e($value); ?> 人">
                                    </div>
                                    <span class="text-xs text-gray-500 dark:text-gray-400 mt-2">
                                        <?php echo e($chartData['labels'][$index]); ?>

                                    </span>
                                </div>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </div>
                    <?php elseif($chartType === 'role_distribution'): ?>
                        <!-- 圓餅圖 (使用 CSS 模擬) -->
                        <div class="flex items-center justify-center space-x-8">
                            <div class="relative w-32 h-32">
                                <?php
                                    $total = array_sum($chartData['datasets'][0]['data']);
                                    $currentAngle = 0;
                                    $colors = $chartData['datasets'][0]['backgroundColor'];
                                ?>
                                
                                <?php $__currentLoopData = $chartData['datasets'][0]['data']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $index => $value): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <?php
                                        $percentage = $total > 0 ? ($value / $total) * 100 : 0;
                                        $angle = $total > 0 ? ($value / $total) * 360 : 0;
                                    ?>
                                    
                                    <?php if($percentage > 0): ?>
                                        <div class="absolute inset-0 rounded-full border-8 border-transparent"
                                             style="border-color: <?php echo e($colors[$index] ?? '#3B82F6'); ?>; 
                                                    transform: rotate(<?php echo e($currentAngle); ?>deg);
                                                    clip-path: polygon(50% 50%, 50% 0%, <?php echo e(50 + 50 * cos(deg2rad($angle))); ?>% <?php echo e(50 - 50 * sin(deg2rad($angle))); ?>%, 50% 50%);">
                                        </div>
                                        <?php $currentAngle += $angle; ?>
                                    <?php endif; ?>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                
                                <div class="absolute inset-4 bg-white dark:bg-gray-800 rounded-full flex items-center justify-center">
                                    <span class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                        <?php echo e($total); ?>

                                    </span>
                                </div>
                            </div>
                            
                            <!-- 圖例 -->
                            <div class="space-y-2">
                                <?php $__currentLoopData = $chartData['labels']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $index => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <div class="flex items-center space-x-2">
                                        <div class="w-3 h-3 rounded-full" 
                                             style="background-color: <?php echo e($colors[$index] ?? '#3B82F6'); ?>">
                                        </div>
                                        <span class="text-sm text-gray-700 dark:text-gray-300">
                                            <?php echo e($label); ?> (<?php echo e($chartData['datasets'][0]['data'][$index]); ?>)
                                        </span>
                                    </div>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- 圖表摘要 -->
                <?php if(isset($chartData['summary'])): ?>
                    <div class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700">
                        <div class="grid grid-cols-2 gap-4 text-sm">
                            <?php if($chartType === 'user_activity'): ?>
                                <div>
                                    <span class="text-gray-500 dark:text-gray-400">本週總計：</span>
                                    <span class="font-medium text-gray-900 dark:text-gray-100">
                                        <?php echo e($chartData['summary']['total']); ?> 人
                                    </span>
                                </div>
                                <?php if($chartData['summary']['peak']): ?>
                                    <div>
                                        <span class="text-gray-500 dark:text-gray-400">最高峰：</span>
                                        <span class="font-medium text-gray-900 dark:text-gray-100">
                                            <?php echo e($chartData['summary']['peak']['label']); ?> (<?php echo e($chartData['summary']['peak']['count']); ?> 人)
                                        </span>
                                    </div>
                                <?php endif; ?>
                            <?php elseif($chartType === 'role_distribution'): ?>
                                <div>
                                    <span class="text-gray-500 dark:text-gray-400">角色總數：</span>
                                    <span class="font-medium text-gray-900 dark:text-gray-100">
                                        <?php echo e($chartData['summary']['total_roles']); ?> 個
                                    </span>
                                </div>
                                <?php if($chartData['summary']['largest_role']): ?>
                                    <div>
                                        <span class="text-gray-500 dark:text-gray-400">最大角色：</span>
                                        <span class="font-medium text-gray-900 dark:text-gray-100">
                                            <?php echo e($chartData['summary']['largest_role']['name']); ?> (<?php echo e($chartData['summary']['largest_role']['count']); ?> 人)
                                        </span>
                                    </div>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <!-- 無資料狀態 -->
            <div class="h-64 flex items-center justify-center">
                <div class="text-center">
                    <svg class="w-12 h-12 text-gray-400 dark:text-gray-600 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                    </svg>
                    <p class="text-gray-500 dark:text-gray-400">暫無圖表資料</p>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php $__env->startPush('scripts'); ?>
<script>
    // 監聽圖表更新事件
    window.addEventListener('chart-refreshed', event => {
        if (event.detail.message) {
            console.log(event.detail.message);
        }
    });
</script>
<?php $__env->stopPush(); ?><?php /**PATH /home/chris/Projects/Taipei_Projects/mg.fg168.net/resources/views/livewire/admin/dashboard/stats-chart.blade.php ENDPATH**/ ?>