{{-- 通知中心載入骨架屏 --}}
<div class="bg-white dark:bg-gray-800 rounded-lg shadow max-w-sm">
    <div class="p-4 border-b border-gray-200 dark:border-gray-700">
        <div class="flex items-center justify-between">
            <div class="skeleton skeleton-text w-20"></div>
            <div class="skeleton skeleton-text w-8"></div>
        </div>
    </div>
    
    <div class="max-h-96 overflow-y-auto">
        @for ($i = 0; $i < 4; $i++)
        <div class="p-4 border-b border-gray-100 dark:border-gray-700 last:border-b-0">
            <div class="flex items-start space-x-3">
                <div class="skeleton skeleton-avatar w-8 h-8 flex-shrink-0"></div>
                <div class="flex-1 min-w-0">
                    <div class="skeleton skeleton-text w-full mb-2"></div>
                    <div class="skeleton skeleton-text w-2/3 mb-1"></div>
                    <div class="skeleton skeleton-text w-1/3"></div>
                </div>
            </div>
        </div>
        @endfor
    </div>
    
    <div class="p-4 border-t border-gray-200 dark:border-gray-700">
        <div class="skeleton skeleton-button w-full"></div>
    </div>
</div>