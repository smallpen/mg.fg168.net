

<?php $__env->startSection('title', '系統設定'); ?>
<?php $__env->startSection('page-title', '系統設定'); ?>

<?php $__env->startSection('content'); ?>
<div class="space-y-6">
    
    <!-- 頁面標題 -->
    <div>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">系統設定</h1>
        <p class="text-gray-600 dark:text-gray-400 mt-1">管理系統的各項設定和配置</p>
    </div>
    
    <!-- 設定選項 -->
    <div class="card">
        <div class="card-body">
            <p class="text-gray-500 dark:text-gray-400 text-center py-8">
                系統設定功能將在後續任務中實作
            </p>
        </div>
    </div>
    
</div>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.admin', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /home/chris/Projects/Taipei_Projects/mg.fg168.net/resources/views/admin/settings/index.blade.php ENDPATH**/ ?>