

<?php $__env->startSection('title', __('admin.users.create')); ?>

<?php $__env->startSection('content'); ?>
    <?php
$__split = function ($name, $params = []) {
    return [$name, $params];
};
[$__name, $__params] = $__split('admin.users.user-form', []);

$__html = app('livewire')->mount($__name, $__params, 'lw-3234468110-0', $__slots ?? [], get_defined_vars());

echo $__html;

unset($__html);
unset($__name);
unset($__params);
unset($__split);
if (isset($__slots)) unset($__slots);
?>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.admin', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /home/chris/Projects/Taipei_Projects/mg.fg168.net/resources/views/admin/users/create.blade.php ENDPATH**/ ?>