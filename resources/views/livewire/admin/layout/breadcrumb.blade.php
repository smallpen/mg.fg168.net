<div class="breadcrumb-container">
    {{-- 麵包屑導航 --}}
    <nav class="breadcrumb-nav" aria-label="麵包屑導航">
        <ol class="breadcrumb-list">
            @forelse($this->displayBreadcrumbs as $index => $breadcrumb)
                <li class="breadcrumb-item {{ $breadcrumb['active'] ?? false ? 'active' : '' }}">
                    @if(isset($breadcrumb['ellipsis']) && $breadcrumb['ellipsis'])
                        {{-- 省略號下拉選單 --}}
                        <div class="breadcrumb-ellipsis" x-data="{ open: false }">
                            <button 
                                type="button" 
                                class="breadcrumb-ellipsis-btn"
                                @click="open = !open"
                                aria-label="顯示隱藏的麵包屑項目"
                            >
                                <span class="ellipsis-text">{{ $breadcrumb['title'] }}</span>
                                <svg class="ellipsis-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                </svg>
                            </button>
                            
                            {{-- 下拉選單 --}}
                            <div 
                                class="breadcrumb-dropdown"
                                x-show="open"
                                x-transition:enter="transition ease-out duration-200"
                                x-transition:enter-start="opacity-0 scale-95"
                                x-transition:enter-end="opacity-100 scale-100"
                                x-transition:leave="transition ease-in duration-150"
                                x-transition:leave-start="opacity-100 scale-100"
                                x-transition:leave-end="opacity-0 scale-95"
                                @click.away="open = false"
                                x-cloak
                            >
                                @foreach($this->fullBreadcrumbs as $fullIndex => $fullBreadcrumb)
                                    @if($fullIndex > 0 && $fullIndex < count($this->fullBreadcrumbs) - 2)
                                        <button 
                                            type="button"
                                            class="breadcrumb-dropdown-item"
                                            wire:click="navigateTo('{{ $fullBreadcrumb['route'] ?? '' }}')"
                                            @if(empty($fullBreadcrumb['route'])) disabled @endif
                                        >
                                            {{ $fullBreadcrumb['title'] }}
                                        </button>
                                    @endif
                                @endforeach
                            </div>
                        </div>
                    @elseif($breadcrumb['active'] ?? false)
                        {{-- 當前頁面（不可點擊） --}}
                        <span class="breadcrumb-current" aria-current="page">
                            {{ $breadcrumb['title'] }}
                        </span>
                    @elseif(!empty($breadcrumb['route']))
                        {{-- 可點擊的麵包屑項目 --}}
                        <button 
                            type="button"
                            class="breadcrumb-link"
                            wire:click="navigateTo('{{ $breadcrumb['route'] }}')"
                            title="前往 {{ $breadcrumb['title'] }}"
                        >
                            {{ $breadcrumb['title'] }}
                        </button>
                    @else
                        {{-- 不可點擊的項目 --}}
                        <span class="breadcrumb-text">
                            {{ $breadcrumb['title'] }}
                        </span>
                    @endif
                    
                    {{-- 分隔符號 --}}
                    @if(!$loop->last)
                        <svg class="breadcrumb-separator" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                        </svg>
                    @endif
                </li>
            @empty
                {{-- 沒有麵包屑時顯示首頁 --}}
                <li class="breadcrumb-item active">
                    <span class="breadcrumb-current" aria-current="page">首頁</span>
                </li>
            @endforelse
        </ol>
        
        {{-- 行動版簡化顯示 --}}
        <div class="breadcrumb-mobile md:hidden">
            @php
                $displayBreadcrumbs = $this->displayBreadcrumbs;
            @endphp
            @if(count($displayBreadcrumbs) > 1)
                @php
                    $backBreadcrumb = $displayBreadcrumbs[count($displayBreadcrumbs) - 2] ?? null;
                @endphp
                <button 
                    type="button"
                    class="breadcrumb-back-btn"
                    wire:click="navigateTo('{{ $backBreadcrumb['route'] ?? '' }}')"
                    @if(empty($backBreadcrumb['route'] ?? '')) disabled @endif
                >
                    <svg class="back-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                    </svg>
                    <span>返回</span>
                </button>
            @endif
            
            <span class="breadcrumb-mobile-current">
                @php
                    $lastBreadcrumb = end($displayBreadcrumbs);
                @endphp
                {{ $lastBreadcrumb['title'] ?? '首頁' }}
            </span>
        </div>
    </nav>
</div>