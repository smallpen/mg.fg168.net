{{-- 活動記錄載入骨架屏 --}}
<div class="bg-white dark:bg-gray-800 rounded-lg shadow">
    <div class="p-4 border-b border-gray-200 dark:border-gray-700">
        <div class="skeleton skeleton-text w-32"></div>
    </div>
    
    <div class="p-4 space-y-4">
        @for ($i = 0; $i < 5; $i++)
        <div class="flex items-start space-x-3">
            <div class="skeleton skeleton-avatar flex-shrink-0"></div>
            <div class="flex-1 min-w-0">
                <div class="skeleton skeleton-text w-3/4 mb-2"></div>
                <div class="skeleton skeleton-text w-1/2 mb-1"></div>
                <div class="skeleton skeleton-text w-1/4"></div>
            </div>
            <div class="skeleton skeleton-text w-16"></div>
        </div>
        @endfor
    </div>
    
    <div class="p-4 border-t border-gray-200 dark:border-gray-700">
        <div class="skeleton skeleton-button w-full"></div>
    </div>
</div>