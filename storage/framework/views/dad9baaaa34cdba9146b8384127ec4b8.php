

<?php $__env->startSection('title', '主題切換測試'); ?>

<?php $__env->startSection('content'); ?>
<div class="space-y-6">
    
    <!-- 主題切換測試標題 -->
    <div class="card">
        <div class="card-header">
            <h2 class="text-2xl font-bold text-gray-900 dark:text-gray-100">
                主題切換功能測試
            </h2>
            <p class="text-gray-600 dark:text-gray-400 mt-2">
                測試淺色和暗黑主題的切換功能，以及各種 UI 元件在不同主題下的顯示效果。
            </p>
        </div>
    </div>
    
    <!-- 主題狀態顯示 -->
    <div class="card">
        <div class="card-header">
            <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">當前主題狀態</h3>
        </div>
        <div class="card-body">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="form-label">使用者偏好設定</label>
                    <div class="p-3 bg-gray-50 dark:bg-gray-700 rounded-md">
                        <code class="text-sm text-gray-900 dark:text-gray-100">
                            <?php echo e(auth()->user()->theme_preference ?? 'null (預設為 light)'); ?>

                        </code>
                    </div>
                </div>
                <div>
                    <label class="form-label">HTML class</label>
                    <div class="p-3 bg-gray-50 dark:bg-gray-700 rounded-md">
                        <code class="text-sm text-gray-900 dark:text-gray-100" id="html-class-display">
                            載入中...
                        </code>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- 色彩測試 -->
    <div class="card">
        <div class="card-header">
            <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">色彩測試</h3>
        </div>
        <div class="card-body">
            <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4">
                <!-- Primary 色彩 -->
                <div class="text-center">
                    <div class="w-16 h-16 bg-primary-500 rounded-lg mx-auto mb-2"></div>
                    <p class="text-sm text-gray-600 dark:text-gray-400">Primary</p>
                </div>
                
                <!-- Secondary 色彩 -->
                <div class="text-center">
                    <div class="w-16 h-16 bg-secondary-500 rounded-lg mx-auto mb-2"></div>
                    <p class="text-sm text-gray-600 dark:text-gray-400">Secondary</p>
                </div>
                
                <!-- Success 色彩 -->
                <div class="text-center">
                    <div class="w-16 h-16 bg-success-500 rounded-lg mx-auto mb-2"></div>
                    <p class="text-sm text-gray-600 dark:text-gray-400">Success</p>
                </div>
                
                <!-- Warning 色彩 -->
                <div class="text-center">
                    <div class="w-16 h-16 bg-warning-500 rounded-lg mx-auto mb-2"></div>
                    <p class="text-sm text-gray-600 dark:text-gray-400">Warning</p>
                </div>
                
                <!-- Danger 色彩 -->
                <div class="text-center">
                    <div class="w-16 h-16 bg-danger-500 rounded-lg mx-auto mb-2"></div>
                    <p class="text-sm text-gray-600 dark:text-gray-400">Danger</p>
                </div>
                
                <!-- Gray 色彩 -->
                <div class="text-center">
                    <div class="w-16 h-16 bg-gray-500 rounded-lg mx-auto mb-2"></div>
                    <p class="text-sm text-gray-600 dark:text-gray-400">Gray</p>
                </div>
            </div>
        </div>
    </div>
    
    <!-- 按鈕測試 -->
    <div class="card">
        <div class="card-header">
            <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">按鈕測試</h3>
        </div>
        <div class="card-body">
            <div class="flex flex-wrap gap-4">
                <button class="btn-primary">Primary 按鈕</button>
                <button class="btn-secondary">Secondary 按鈕</button>
                <button class="btn-success">Success 按鈕</button>
                <button class="btn-warning">Warning 按鈕</button>
                <button class="btn-danger">Danger 按鈕</button>
                <button class="btn-outline">Outline 按鈕</button>
            </div>
        </div>
    </div>
    
    <!-- 表單測試 -->
    <div class="card">
        <div class="card-header">
            <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">表單測試</h3>
        </div>
        <div class="card-body">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="form-label">文字輸入框</label>
                    <input type="text" class="form-input" placeholder="請輸入文字">
                </div>
                <div>
                    <label class="form-label">選擇框</label>
                    <select class="form-input">
                        <option>選項 1</option>
                        <option>選項 2</option>
                        <option>選項 3</option>
                    </select>
                </div>
                <div class="md:col-span-2">
                    <label class="form-label">文字區域</label>
                    <textarea class="form-input" rows="3" placeholder="請輸入多行文字"></textarea>
                </div>
            </div>
        </div>
    </div>
    
    <!-- 通知測試 -->
    <div class="card">
        <div class="card-header">
            <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">通知測試</h3>
        </div>
        <div class="card-body">
            <div class="space-y-4">
                <div class="alert-success">
                    <strong>成功！</strong> 這是一個成功通知的範例。
                </div>
                <div class="alert-warning">
                    <strong>警告！</strong> 這是一個警告通知的範例。
                </div>
                <div class="alert-danger">
                    <strong>錯誤！</strong> 這是一個錯誤通知的範例。
                </div>
                <div class="alert-info">
                    <strong>資訊！</strong> 這是一個資訊通知的範例。
                </div>
            </div>
        </div>
    </div>
    
    <!-- 表格測試 -->
    <div class="card">
        <div class="card-header">
            <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">表格測試</h3>
        </div>
        <div class="card-body">
            <div class="overflow-x-auto">
                <table class="table">
                    <thead class="table-header">
                        <tr>
                            <th class="table-header-cell">姓名</th>
                            <th class="table-header-cell">電子郵件</th>
                            <th class="table-header-cell">角色</th>
                            <th class="table-header-cell">狀態</th>
                        </tr>
                    </thead>
                    <tbody class="table-body">
                        <tr>
                            <td class="table-cell">張三</td>
                            <td class="table-cell">zhang@example.com</td>
                            <td class="table-cell">管理員</td>
                            <td class="table-cell">
                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-success-100 text-success-800 dark:bg-success-900 dark:text-success-200">
                                    啟用
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <td class="table-cell">李四</td>
                            <td class="table-cell">li@example.com</td>
                            <td class="table-cell">使用者</td>
                            <td class="table-cell">
                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-danger-100 text-danger-800 dark:bg-danger-900 dark:text-danger-200">
                                    停用
                                </span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
</div>

<script>
// 顯示當前 HTML class
function updateHtmlClassDisplay() {
    const htmlElement = document.documentElement;
    const classes = htmlElement.className || '(無)';
    document.getElementById('html-class-display').textContent = classes;
}

// 頁面載入時更新顯示
document.addEventListener('DOMContentLoaded', updateHtmlClassDisplay);

// 監聽主題變更事件
document.addEventListener('livewire:init', () => {
    Livewire.on('theme-changed', () => {
        setTimeout(updateHtmlClassDisplay, 100);
    });
});
</script>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.admin', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /home/chris/Projects/Taipei_Projects/mg.fg168.net/resources/views/admin/test-theme.blade.php ENDPATH**/ ?>