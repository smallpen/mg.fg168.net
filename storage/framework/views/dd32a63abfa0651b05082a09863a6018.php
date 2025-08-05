<!DOCTYPE html>
<html lang="<?php echo e(str_replace('_', '-', app()->getLocale())); ?>" class="<?php echo e(auth()->user()?->theme_preference === 'dark' ? 'dark' : ''); ?>" x-data="{ theme: '<?php echo e(auth()->user()?->theme_preference ?? 'light'); ?>' }">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="<?php echo e(csrf_token()); ?>">

    <title><?php echo e(config('app.name', 'Laravel Admin System')); ?> - <?php echo $__env->yieldContent('title', '管理後台'); ?></title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet" />

    <!-- Scripts -->
    <?php echo app('Illuminate\Foundation\Vite')(['resources/css/app.css', 'resources/js/app.js']); ?>
    <?php echo \Livewire\Mechanisms\FrontendAssets\FrontendAssets::styles(); ?>

    <?php echo $__env->yieldPushContent('styles'); ?>
</head>
<body class="font-sans antialiased">
    
    <?php
$__split = function ($name, $params = []) {
    return [$name, $params];
};
[$__name, $__params] = $__split('admin.layout.admin-layout');

$__html = app('livewire')->mount($__name, $__params, 'lw-460593167-0', $__slots ?? [], get_defined_vars());

echo $__html;

unset($__html);
unset($__name);
unset($__params);
unset($__split);
if (isset($__slots)) unset($__slots);
?>
        <?php echo $__env->yieldContent('content'); ?>
    @endlivewire

    <?php echo \Livewire\Mechanisms\FrontendAssets\FrontendAssets::scripts(); ?>

    <?php echo $__env->yieldPushContent('scripts'); ?>
    
    <!-- 主題初始化腳本 -->
    <script>
        // 頁面載入時初始化主題
        document.addEventListener('DOMContentLoaded', function() {
            // 從 localStorage 或使用者偏好設定取得主題
            const savedTheme = localStorage.getItem('theme') || '<?php echo e(auth()->user()?->theme_preference ?? 'light'); ?>';
            const htmlElement = document.documentElement;
            
            // 應用主題
            if (savedTheme === 'dark') {
                htmlElement.classList.add('dark');
            } else {
                htmlElement.classList.remove('dark');
            }
            
            // 同步 localStorage
            localStorage.setItem('theme', savedTheme);
        });
        
        // 監聽系統主題變更（可選功能）
        if (window.matchMedia) {
            const mediaQuery = window.matchMedia('(prefers-color-scheme: dark)');
            
            // 如果使用者沒有設定偏好，跟隨系統主題
            function handleSystemThemeChange(e) {
                const userTheme = localStorage.getItem('theme');
                if (!userTheme || userTheme === 'system') {
                    const htmlElement = document.documentElement;
                    if (e.matches) {
                        htmlElement.classList.add('dark');
                    } else {
                        htmlElement.classList.remove('dark');
                    }
                }
            }
            
            mediaQuery.addListener(handleSystemThemeChange);
        }
    </script>
</body>
</html><?php /**PATH /home/chris/Projects/Taipei_Projects/mg.fg168.net/resources/views/layouts/admin.blade.php ENDPATH**/ ?>