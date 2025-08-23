@extends('layouts.admin')

@section('title', '活動記錄')

@section('content')
    <livewire:admin.activities.activity-list />
@endsection

@push('scripts')
<script>
    // 處理檔案下載
    document.addEventListener('livewire:init', () => {
        Livewire.on('download-file', (event) => {
            const filePath = event.filePath;
            const link = document.createElement('a');
            link.href = `/storage/${filePath}`;
            link.download = filePath.split('/').pop();
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        });

        // 處理活動詳情對話框
        Livewire.on('open-activity-detail', (event) => {
            // 這裡可以開啟活動詳情模態框
            // 暫時使用 alert 作為示範
            alert('開啟活動詳情 ID: ' + event.activityId);
        });

        // 處理通知
        Livewire.on('notify', (event) => {
            const notification = event[0] || event;
            
            // 建立通知元素
            const notificationEl = document.createElement('div');
            notificationEl.className = `fixed top-4 right-4 z-50 max-w-sm w-full bg-white dark:bg-gray-800 shadow-lg rounded-lg pointer-events-auto ring-1 ring-black ring-opacity-5 overflow-hidden transform transition-all duration-300 ease-in-out translate-x-full`;
            
            // 設定通知內容
            const bgColor = {
                'success': 'bg-green-50 dark:bg-green-900/20',
                'error': 'bg-red-50 dark:bg-red-900/20',
                'warning': 'bg-yellow-50 dark:bg-yellow-900/20',
                'info': 'bg-blue-50 dark:bg-blue-900/20'
            }[notification.type] || 'bg-gray-50 dark:bg-gray-900/20';
            
            const textColor = {
                'success': 'text-green-800 dark:text-green-200',
                'error': 'text-red-800 dark:text-red-200',
                'warning': 'text-yellow-800 dark:text-yellow-200',
                'info': 'text-blue-800 dark:text-blue-200'
            }[notification.type] || 'text-gray-800 dark:text-gray-200';
            
            const iconSvg = {
                'success': '<svg class="h-5 w-5 text-green-400" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>',
                'error': '<svg class="h-5 w-5 text-red-400" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path></svg>',
                'warning': '<svg class="h-5 w-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path></svg>',
                'info': '<svg class="h-5 w-5 text-blue-400" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path></svg>'
            }[notification.type] || '<svg class="h-5 w-5 text-gray-400" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path></svg>';
            
            notificationEl.innerHTML = `
                <div class="p-4">
                    <div class="flex items-start">
                        <div class="flex-shrink-0">
                            ${iconSvg}
                        </div>
                        <div class="ml-3 w-0 flex-1">
                            <p class="text-sm font-medium ${textColor}">
                                ${notification.message}
                            </p>
                        </div>
                        <div class="ml-4 flex-shrink-0 flex">
                            <button class="inline-flex text-gray-400 hover:text-gray-500 focus:outline-none" onclick="this.closest('.fixed').remove()">
                                <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>
            `;
            
            // 添加到頁面
            document.body.appendChild(notificationEl);
            
            // 動畫進入
            setTimeout(() => {
                notificationEl.classList.remove('translate-x-full');
            }, 100);
            
            // 自動移除
            const timeout = notification.timeout || 5000;
            setTimeout(() => {
                notificationEl.classList.add('translate-x-full');
                setTimeout(() => {
                    if (notificationEl.parentNode) {
                        notificationEl.parentNode.removeChild(notificationEl);
                    }
                }, 300);
            }, timeout);
        });
    });
</script>
@endpush