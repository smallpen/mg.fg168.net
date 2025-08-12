@props([
    'mobile' => null,
    'tablet' => null,
    'desktop' => null,
    'class' => '',
    'tag' => 'div'
])

@php
    $responsiveClasses = [
        'responsive-content',
        'transition-all',
        'duration-300',
        'ease-in-out'
    ];
    
    $allClasses = implode(' ', array_merge($responsiveClasses, explode(' ', $class)));
@endphp

<{{ $tag }} 
    class="{{ $allClasses }}"
    x-data="responsiveContent"
    x-init="init()"
    {{ $attributes->except(['mobile', 'tablet', 'desktop', 'class', 'tag']) }}>
    
    @if($mobile || $tablet || $desktop)
        <!-- 響應式內容切換 -->
        @if($mobile)
            <div x-show="isMobile" 
                 x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0 transform scale-95"
                 x-transition:enter-end="opacity-100 transform scale-100"
                 x-transition:leave="transition ease-in duration-200"
                 x-transition:leave-start="opacity-100 transform scale-100"
                 x-transition:leave-end="opacity-0 transform scale-95"
                 class="responsive-content-mobile">
                {!! $mobile !!}
            </div>
        @endif
        
        @if($tablet)
            <div x-show="isTablet" 
                 x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0 transform scale-95"
                 x-transition:enter-end="opacity-100 transform scale-100"
                 x-transition:leave="transition ease-in duration-200"
                 x-transition:leave-start="opacity-100 transform scale-100"
                 x-transition:leave-end="opacity-0 transform scale-95"
                 class="responsive-content-tablet">
                {!! $tablet !!}
            </div>
        @endif
        
        @if($desktop)
            <div x-show="isDesktop" 
                 x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0 transform scale-95"
                 x-transition:enter-end="opacity-100 transform scale-100"
                 x-transition:leave="transition ease-in duration-200"
                 x-transition:leave-start="opacity-100 transform scale-100"
                 x-transition:leave-end="opacity-0 transform scale-95"
                 class="responsive-content-desktop">
                {!! $desktop !!}
            </div>
        @endif
    @else
        <!-- 預設內容 -->
        {{ $slot }}
    @endif
    
</{{ $tag }}>

@push('scripts')
<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('responsiveContent', () => ({
            isMobile: false,
            isTablet: false,
            isDesktop: true,
            
            init() {
                this.checkViewport();
                window.addEventListener('resize', this.handleResize.bind(this));
                
                // 監聽全域響應式變更事件
                document.addEventListener('viewport-changed', (e) => {
                    this.isMobile = e.detail.isMobile;
                    this.isTablet = e.detail.isTablet;
                    this.isDesktop = e.detail.isDesktop;
                });
            },
            
            checkViewport() {
                const width = window.innerWidth;
                this.isMobile = width < 768;
                this.isTablet = width >= 768 && width < 1024;
                this.isDesktop = width >= 1024;
            },
            
            handleResize() {
                clearTimeout(this.resizeTimeout);
                this.resizeTimeout = setTimeout(() => {
                    this.checkViewport();
                }, 150);
            }
        }));
    });
</script>
@endpush

@push('styles')
<style>
    .responsive-content {
        /* 響應式內容基礎樣式 */
    }
    
    .responsive-content-mobile,
    .responsive-content-tablet,
    .responsive-content-desktop {
        /* 響應式內容變體樣式 */
    }
    
    /* 響應式文字大小 */
    .responsive-content .text-responsive {
        font-size: 1rem;
        line-height: 1.6;
    }
    
    @media (max-width: 767px) {
        .responsive-content .text-responsive {
            font-size: 0.875rem;
            line-height: 1.5;
        }
        
        .responsive-content .text-responsive.large {
            font-size: 1rem;
        }
        
        .responsive-content .text-responsive.xl {
            font-size: 1.125rem;
        }
    }
    
    @media (min-width: 768px) and (max-width: 1023px) {
        .responsive-content .text-responsive {
            font-size: 1rem;
            line-height: 1.6;
        }
        
        .responsive-content .text-responsive.large {
            font-size: 1.125rem;
        }
        
        .responsive-content .text-responsive.xl {
            font-size: 1.25rem;
        }
    }
    
    @media (min-width: 1024px) {
        .responsive-content .text-responsive {
            font-size: 1rem;
            line-height: 1.6;
        }
        
        .responsive-content .text-responsive.large {
            font-size: 1.125rem;
        }
        
        .responsive-content .text-responsive.xl {
            font-size: 1.5rem;
        }
    }
    
    /* 響應式間距 */
    .responsive-content .spacing-responsive {
        padding: 1rem;
    }
    
    @media (max-width: 767px) {
        .responsive-content .spacing-responsive {
            padding: 0.5rem;
        }
        
        .responsive-content .spacing-responsive.large {
            padding: 1rem;
        }
    }
    
    @media (min-width: 768px) and (max-width: 1023px) {
        .responsive-content .spacing-responsive {
            padding: 1rem;
        }
        
        .responsive-content .spacing-responsive.large {
            padding: 1.5rem;
        }
    }
    
    @media (min-width: 1024px) {
        .responsive-content .spacing-responsive {
            padding: 1.5rem;
        }
        
        .responsive-content .spacing-responsive.large {
            padding: 2rem;
        }
    }
    
    /* 響應式佈局 */
    .responsive-content .layout-responsive {
        display: flex;
        flex-direction: column;
        gap: 1rem;
    }
    
    @media (min-width: 768px) {
        .responsive-content .layout-responsive {
            flex-direction: row;
            gap: 1.5rem;
        }
    }
    
    @media (min-width: 1024px) {
        .responsive-content .layout-responsive {
            gap: 2rem;
        }
    }
</style>
@endpush