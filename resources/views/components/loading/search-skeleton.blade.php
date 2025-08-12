{{-- 搜尋元件載入骨架屏 --}}
<div class="relative">
    <div class="skeleton skeleton-text h-10 w-full rounded-lg mb-4"></div>
    
    <div class="space-y-2">
        @for ($i = 0; $i < 3; $i++)
        <div class="flex items-center space-x-3 p-2">
            <div class="skeleton skeleton-avatar w-6 h-6 flex-shrink-0"></div>
            <div class="flex-1">
                <div class="skeleton skeleton-text w-3/4 mb-1"></div>
                <div class="skeleton skeleton-text w-1/2"></div>
            </div>
        </div>
        @endfor
    </div>
</div>