

<?php $__env->startSection('title', '儀表板'); ?>
<?php $__env->startSection('page-title', '儀表板'); ?>

<?php $__env->startSection('content'); ?>
<div class="space-y-6">
    
    <!-- 歡迎訊息 -->
    <div class="card">
        <div class="card-body">
            <h2 class="text-2xl font-bold text-gray-900 dark:text-gray-100 mb-2">
                歡迎回來，<?php echo e(auth()->user()->display_name); ?>！
            </h2>
            <p class="text-gray-600 dark:text-gray-400">
                這是您的管理後台儀表板，您可以在這裡管理系統的各項功能。
            </p>
        </div>
    </div>
    
    <!-- 統計卡片 - 使用 Livewire 元件 -->
    <?php
$__split = function ($name, $params = []) {
    return [$name, $params];
};
[$__name, $__params] = $__split('admin.dashboard.dashboard-stats');

$__html = app('livewire')->mount($__name, $__params, 'lw-312391461-0', $__slots ?? [], get_defined_vars());

echo $__html;

unset($__html);
unset($__name);
unset($__params);
unset($__split);
if (isset($__slots)) unset($__slots);
?>
    
    <!-- 統計圖表 -->
    <?php
$__split = function ($name, $params = []) {
    return [$name, $params];
};
[$__name, $__params] = $__split('admin.dashboard.stats-chart');

$__html = app('livewire')->mount($__name, $__params, 'lw-312391461-1', $__slots ?? [], get_defined_vars());

echo $__html;

unset($__html);
unset($__name);
unset($__params);
unset($__split);
if (isset($__slots)) unset($__slots);
?>
    
    <!-- 快速操作和最近活動 -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        
        <!-- 快速操作 - 使用 Livewire 元件 -->
        <?php
$__split = function ($name, $params = []) {
    return [$name, $params];
};
[$__name, $__params] = $__split('admin.dashboard.quick-actions');

$__html = app('livewire')->mount($__name, $__params, 'lw-312391461-2', $__slots ?? [], get_defined_vars());

echo $__html;

unset($__html);
unset($__name);
unset($__params);
unset($__split);
if (isset($__slots)) unset($__slots);
?>
        
        <!-- 最近活動 - 使用 Livewire 元件 -->
        <?php
$__split = function ($name, $params = []) {
    return [$name, $params];
};
[$__name, $__params] = $__split('admin.dashboard.recent-activity');

$__html = app('livewire')->mount($__name, $__params, 'lw-312391461-3', $__slots ?? [], get_defined_vars());

echo $__html;

unset($__html);
unset($__name);
unset($__params);
unset($__split);
if (isset($__slots)) unset($__slots);
?>
        
    </div>
    
    <!-- 系統資訊 -->
    <div class="card">
        <div class="card-header">
            <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">系統資訊</h3>
        </div>
        <div class="card-body">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="text-center">
                    <div class="text-2xl font-bold text-gray-900 dark:text-gray-100"><?php echo e(app()->version()); ?></div>
                    <div class="text-sm text-gray-600 dark:text-gray-400">Laravel 版本</div>
                </div>
                <div class="text-center">
                    <div class="text-2xl font-bold text-gray-900 dark:text-gray-100"><?php echo e(PHP_VERSION); ?></div>
                    <div class="text-sm text-gray-600 dark:text-gray-400">PHP 版本</div>
                </div>
                <div class="text-center">
                    <div class="text-2xl font-bold text-gray-900 dark:text-gray-100 capitalize"><?php echo e(app()->environment()); ?></div>
                    <div class="text-sm text-gray-600 dark:text-gray-400">執行環境</div>
                </div>
                <div class="text-center">
                    <div class="text-2xl font-bold <?php echo e(config('app.debug') ? 'text-warning-600 dark:text-warning-400' : 'text-success-600 dark:text-success-400'); ?>">
                        <?php echo e(config('app.debug') ? '開啟' : '關閉'); ?>

                    </div>
                    <div class="text-sm text-gray-600 dark:text-gray-400">除錯模式</div>
                </div>
            </div>
        </div>
    </div>
    
</div>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.admin', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /var/www/html/resources/views/admin/dashboard.blade.php ENDPATH**/ ?>