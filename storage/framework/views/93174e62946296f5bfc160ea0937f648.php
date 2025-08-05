
<?php if($showConfirmDialog): ?>
<div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" 
             wire:click="closeDialog"></div>

        
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

        
        <div class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg px-4 pt-5 pb-4 text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full sm:p-6">
            <div class="sm:flex sm:items-start">
                
                <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full 
                           <?php echo e($deleteAction === 'delete' ? 'bg-red-100 dark:bg-red-900' : 'bg-orange-100 dark:bg-orange-900'); ?> sm:mx-0 sm:h-10 sm:w-10">
                    <?php if($deleteAction === 'delete'): ?>
                        <svg class="h-6 w-6 text-red-600 dark:text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                        </svg>
                    <?php else: ?>
                        <svg class="h-6 w-6 text-orange-600 dark:text-orange-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728L5.636 5.636m12.728 12.728L18.364 5.636M5.636 18.364l12.728-12.728"/>
                        </svg>
                    <?php endif; ?>
                </div>

                
                <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                    
                    <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white" id="modal-title">
                        <?php if($deleteAction === 'delete'): ?>
                            <?php echo e(__('admin.users.confirm_delete_title')); ?>

                        <?php else: ?>
                            <?php echo e(__('admin.users.confirm_disable_title')); ?>

                        <?php endif; ?>
                    </h3>

                    
                    <?php if($user): ?>
                    <div class="mt-4 p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
                        <div class="flex items-center space-x-3">
                            <div class="flex-shrink-0 h-10 w-10">
                                <div class="h-10 w-10 rounded-full bg-gray-300 dark:bg-gray-600 flex items-center justify-center">
                                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">
                                        <?php echo e(strtoupper(substr($user->display_name, 0, 1))); ?>

                                    </span>
                                </div>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-gray-900 dark:text-white">
                                    <?php echo e($user->display_name); ?>

                                </p>
                                <p class="text-sm text-gray-500 dark:text-gray-400">
                                    <?php echo e(__('admin.users.username')); ?>: <?php echo e($user->username); ?>

                                </p>
                                <?php if($user->email): ?>
                                <p class="text-sm text-gray-500 dark:text-gray-400">
                                    <?php echo e(__('admin.users.email')); ?>: <?php echo e($user->email); ?>

                                </p>
                                <?php endif; ?>
                                <?php if($user->roles->isNotEmpty()): ?>
                                <div class="mt-2 flex flex-wrap gap-1">
                                    <?php $__currentLoopData = $user->roles; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $role): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium 
                                                   <?php echo e($role->name === 'super_admin' ? 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200' : 
                                                      ($role->name === 'admin' ? 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200' : 
                                                       'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200')); ?>">
                                            <?php echo e($role->display_name); ?>

                                        </span>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                    
                    <div class="mt-4">
                        <label class="text-sm font-medium text-gray-700 dark:text-gray-300">
                            <?php echo e(__('admin.users.select_action')); ?>

                        </label>
                        <div class="mt-2 space-y-2">
                            
                            <label class="flex items-center">
                                <input type="radio" 
                                       wire:model="deleteAction" 
                                       value="disable"
                                       class="h-4 w-4 text-orange-600 focus:ring-orange-500 border-gray-300 dark:border-gray-600">
                                <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">
                                    <?php echo e(__('admin.users.disable_user')); ?>

                                    <span class="text-gray-500 dark:text-gray-400">
                                        (<?php echo e(__('admin.users.recommended')); ?>)
                                    </span>
                                </span>
                            </label>

                            
                            <label class="flex items-center">
                                <input type="radio" 
                                       wire:model="deleteAction" 
                                       value="delete"
                                       class="h-4 w-4 text-red-600 focus:ring-red-500 border-gray-300 dark:border-gray-600">
                                <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">
                                    <?php echo e(__('admin.users.delete_permanently')); ?>

                                    <span class="text-red-500 dark:text-red-400">
                                        (<?php echo e(__('admin.users.irreversible')); ?>)
                                    </span>
                                </span>
                            </label>
                        </div>
                    </div>

                    
                    <div class="mt-4 p-3 rounded-lg <?php echo e($deleteAction === 'delete' ? 'bg-red-50 dark:bg-red-900/20' : 'bg-orange-50 dark:bg-orange-900/20'); ?>">
                        <p class="text-sm <?php echo e($deleteAction === 'delete' ? 'text-red-700 dark:text-red-300' : 'text-orange-700 dark:text-orange-300'); ?>">
                            <?php echo e($deleteActionDescription); ?>

                        </p>
                    </div>

                    
                    <?php if($deleteAction === 'delete'): ?>
                    <div class="mt-4">
                        <label for="confirmUsername" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            <?php echo e(__('admin.users.confirm_username_label', ['username' => $user->username ?? ''])); ?>

                        </label>
                        <input type="text" 
                               id="confirmUsername"
                               wire:model.defer="confirmUsername"
                               placeholder="<?php echo e($user->username ?? ''); ?>"
                               class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-transparent bg-white dark:bg-gray-700 text-gray-900 dark:text-white">
                        <?php $__errorArgs = ['confirmUsername'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400"><?php echo e($message); ?></p>
                        <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            
            <div class="mt-5 sm:mt-4 sm:flex sm:flex-row-reverse">
                
                <button type="button" 
                        wire:click="confirmDelete"
                        <?php echo e(!$canConfirm ? 'disabled' : ''); ?>

                        class="<?php echo e($confirmButtonClass); ?> <?php echo e(!$canConfirm ? 'opacity-50 cursor-not-allowed' : ''); ?> sm:ml-3 sm:w-auto sm:text-sm">
                    <?php if($isProcessing): ?>
                        <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                    <?php endif; ?>
                    <?php echo e($confirmButtonText); ?>

                </button>

                
                <button type="button" 
                        wire:click="closeDialog"
                        <?php echo e($isProcessing ? 'disabled' : ''); ?>

                        class="mt-3 w-full inline-flex justify-center px-4 py-2 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg shadow-sm text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors duration-200 sm:mt-0 sm:w-auto sm:text-sm <?php echo e($isProcessing ? 'opacity-50 cursor-not-allowed' : ''); ?>">
                    <?php echo e(__('admin.actions.cancel')); ?>

                </button>
            </div>
        </div>
    </div>
</div>
<?php endif; ?><?php /**PATH /home/chris/Projects/Taipei_Projects/mg.fg168.net/resources/views/livewire/admin/users/user-delete.blade.php ENDPATH**/ ?>