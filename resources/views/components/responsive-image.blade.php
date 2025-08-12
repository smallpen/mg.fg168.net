@props([
    'src' => '',
    'alt' => '',
    'class' => '',
    'sizes' => 'mobile:100vw tablet:50vw desktop:33vw',
    'loading' => 'lazy',
    'objectFit' => 'cover',
    'aspectRatio' => null,
    'placeholder' => null,
    'srcset' => null
])

@php
    // 解析 sizes 屬性
    $sizeMap = [
        'mobile' => '(max-width: 767px)',
        'tablet' => '(min-width: 768px) and (max-width: 1023px)',
        'desktop' => '(min-width: 1024px)'
    ];
    
    $parsedSizes = [];
    foreach (explode(' ', $sizes) as $size) {
        if (strpos($size, ':') !== false) {
            [$breakpoint, $width] = explode(':', $size);
            if (isset($sizeMap[$breakpoint])) {
                $parsedSizes[] = $sizeMap[$breakpoint] . ' ' . $width;
            }
        }
    }
    
    $sizesAttr = implode(', ', $parsedSizes);
    
    // 生成響應式類別
    $responsiveClasses = [
        'responsive-image',
        'w-full',
        'h-auto',
        'transition-all',
        'duration-300',
        'ease-in-out'
    ];
    
    // 添加 object-fit 類別
    switch ($objectFit) {
        case 'contain':
            $responsiveClasses[] = 'object-contain';
            break;
        case 'cover':
            $responsiveClasses[] = 'object-cover';
            break;
        case 'fill':
            $responsiveClasses[] = 'object-fill';
            break;
        case 'none':
            $responsiveClasses[] = 'object-none';
            break;
        case 'scale-down':
            $responsiveClasses[] = 'object-scale-down';
            break;
        default:
            $responsiveClasses[] = 'object-cover';
    }
    
    // 添加長寬比類別
    if ($aspectRatio) {
        switch ($aspectRatio) {
            case '1:1':
            case 'square':
                $responsiveClasses[] = 'aspect-square';
                break;
            case '16:9':
            case 'video':
                $responsiveClasses[] = 'aspect-video';
                break;
            case '4:3':
                $responsiveClasses[] = 'aspect-[4/3]';
                break;
            case '3:2':
                $responsiveClasses[] = 'aspect-[3/2]';
                break;
            case '2:1':
                $responsiveClasses[] = 'aspect-[2/1]';
                break;
        }
    }
    
    $allClasses = implode(' ', array_merge($responsiveClasses, explode(' ', $class)));
@endphp

<div class="responsive-image-container relative overflow-hidden">
    @if($placeholder && $loading === 'lazy')
        <!-- 佔位符圖片 -->
        <div class="absolute inset-0 bg-gray-200 dark:bg-gray-700 flex items-center justify-center"
             x-data="{ loaded: false }"
             x-show="!loaded">
            <div class="text-gray-400 dark:text-gray-500">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                          d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z">
                    </path>
                </svg>
            </div>
        </div>
    @endif
    
    <img 
        src="{{ $src }}"
        alt="{{ $alt }}"
        class="{{ $allClasses }}"
        loading="{{ $loading }}"
        @if($sizesAttr)
            sizes="{{ $sizesAttr }}"
        @endif
        @if($srcset)
            srcset="{{ $srcset }}"
        @endif
        @if($placeholder && $loading === 'lazy')
            x-on:load="loaded = true"
            x-on:error="loaded = true"
        @endif
        {{ $attributes->except(['src', 'alt', 'class', 'sizes', 'loading', 'objectFit', 'aspectRatio', 'placeholder', 'srcset']) }}
    >
    
    @if($slot->isNotEmpty())
        <!-- 圖片覆蓋內容 -->
        <div class="absolute inset-0 flex items-center justify-center">
            {{ $slot }}
        </div>
    @endif
</div>

@push('styles')
<style>
    .responsive-image-container {
        /* 響應式圖片容器樣式 */
    }
    
    .responsive-image {
        /* 基礎響應式圖片樣式 */
        max-width: 100%;
        height: auto;
    }
    
    /* 載入動畫 */
    .responsive-image[loading="lazy"] {
        opacity: 0;
        transition: opacity 0.3s ease-in-out;
    }
    
    .responsive-image[loading="lazy"].loaded {
        opacity: 1;
    }
    
    /* 錯誤狀態 */
    .responsive-image.error {
        background-color: rgb(243 244 246);
        background-image: url("data:image/svg+xml,%3csvg width='100' height='100' xmlns='http://www.w3.org/2000/svg'%3e%3ctext x='50%25' y='50%25' style='dominant-baseline:central;text-anchor:middle;font-size:14px;fill:%23999;'%3e圖片載入失敗%3c/text%3e%3c/svg%3e");
        background-repeat: no-repeat;
        background-position: center;
        background-size: contain;
    }
    
    [data-theme="dark"] .responsive-image.error {
        background-color: rgb(55 65 81);
    }
    
    /* 響應式斷點調整 */
    @media (max-width: 767px) {
        .responsive-image-container {
            /* 手機版特定樣式 */
        }
    }
    
    @media (min-width: 768px) and (max-width: 1023px) {
        .responsive-image-container {
            /* 平板版特定樣式 */
        }
    }
    
    @media (min-width: 1024px) {
        .responsive-image-container {
            /* 桌面版特定樣式 */
        }
    }
</style>
@endpush

@push('scripts')
<script>
    // 響應式圖片載入處理
    document.addEventListener('DOMContentLoaded', function() {
        const images = document.querySelectorAll('.responsive-image[loading="lazy"]');
        
        // 使用 Intersection Observer 來處理懶載入
        if ('IntersectionObserver' in window) {
            const imageObserver = new IntersectionObserver((entries, observer) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        const img = entry.target;
                        
                        // 載入完成處理
                        img.addEventListener('load', function() {
                            this.classList.add('loaded');
                        });
                        
                        // 載入錯誤處理
                        img.addEventListener('error', function() {
                            this.classList.add('error');
                            this.classList.add('loaded');
                        });
                        
                        observer.unobserve(img);
                    }
                });
            });
            
            images.forEach(img => imageObserver.observe(img));
        } else {
            // 不支援 Intersection Observer 的瀏覽器直接載入
            images.forEach(img => {
                img.classList.add('loaded');
            });
        }
    });
</script>
@endpush