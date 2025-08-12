<?php

namespace App\Livewire\Admin\Layout;

use Livewire\Component;
use Livewire\Attributes\On;

/**
 * SkeletonLoader 骨架屏載入元件
 * 
 * 提供各種類型的骨架屏載入動畫
 */
class SkeletonLoader extends Component
{
    // 骨架屏狀態
    public bool $isLoading = false;
    public string $skeletonType = 'default';
    public array $skeletonConfig = [];
    
    // 預定義骨架屏類型
    protected array $skeletonTypes = [
        'default' => [
            'name' => '預設骨架屏',
            'components' => ['header', 'content', 'footer']
        ],
        'dashboard' => [
            'name' => '儀表板骨架屏',
            'components' => ['stats-cards', 'charts', 'recent-activity']
        ],
        'table' => [
            'name' => '表格骨架屏',
            'components' => ['table-header', 'table-rows']
        ],
        'form' => [
            'name' => '表單骨架屏',
            'components' => ['form-fields', 'form-actions']
        ],
        'card-list' => [
            'name' => '卡片列表骨架屏',
            'components' => ['card-grid']
        ],
        'profile' => [
            'name' => '個人資料骨架屏',
            'components' => ['profile-header', 'profile-content']
        ],
        'sidebar' => [
            'name' => '側邊欄骨架屏',
            'components' => ['menu-items']
        ]
    ];
    
    // 動畫設定
    public string $animationType = 'pulse'; // pulse, wave, shimmer
    public int $animationDuration = 1500; // 毫秒
    public bool $showProgress = false;
    public int $loadingProgress = 0;
    
    /**
     * 顯示骨架屏
     */
    public function showSkeleton(
        string $type = 'default',
        array $config = [],
        string $animation = 'pulse'
    ): void {
        $this->isLoading = true;
        $this->skeletonType = $type;
        $this->skeletonConfig = array_merge(
            $this->skeletonTypes[$type] ?? $this->skeletonTypes['default'],
            $config
        );
        $this->animationType = $animation;
        $this->loadingProgress = 0;
        
        $this->dispatch('skeleton-loading-started', [
            'type' => $type,
            'config' => $this->skeletonConfig
        ]);
    }
    
    /**
     * 隱藏骨架屏
     */
    public function hideSkeleton(): void
    {
        $this->loadingProgress = 100;
        $this->isLoading = false;
        
        $this->dispatch('skeleton-loading-finished');
    }
    
    /**
     * 更新載入進度
     */
    public function updateProgress(int $progress): void
    {
        $this->loadingProgress = max(0, min(100, $progress));
        $this->showProgress = true;
        
        if ($progress >= 100) {
            $this->hideSkeleton();
        }
    }
    
    /**
     * 取得骨架屏配置
     */
    public function getSkeletonConfigProperty(): array
    {
        return $this->skeletonConfig;
    }
    
    /**
     * 取得動畫 CSS 類別
     */
    public function getAnimationClassesProperty(): string
    {
        $classes = ['skeleton-animation'];
        
        switch ($this->animationType) {
            case 'wave':
                $classes[] = 'skeleton-wave';
                break;
            case 'shimmer':
                $classes[] = 'skeleton-shimmer';
                break;
            default:
                $classes[] = 'skeleton-pulse';
        }
        
        return implode(' ', $classes);
    }
    
    /**
     * 取得骨架屏容器類別
     */
    public function getSkeletonContainerClassesProperty(): string
    {
        $classes = ['skeleton-container', "skeleton-{$this->skeletonType}"];
        
        if ($this->isLoading) {
            $classes[] = 'loading';
        }
        
        return implode(' ', $classes);
    }
    
    /**
     * 渲染統計卡片骨架屏
     */
    public function renderStatsCards(int $count = 4): string
    {
        $cards = '';
        for ($i = 0; $i < $count; $i++) {
            $cards .= '<div class="skeleton-stats-card">
                <div class="skeleton-stats-icon"></div>
                <div class="skeleton-stats-content">
                    <div class="skeleton-stats-value"></div>
                    <div class="skeleton-stats-label"></div>
                    <div class="skeleton-stats-change"></div>
                </div>
            </div>';
        }
        return $cards;
    }
    
    /**
     * 渲染表格骨架屏
     */
    public function renderTableSkeleton(int $rows = 5, int $columns = 4): string
    {
        $table = '<div class="skeleton-table">
            <div class="skeleton-table-header">';
        
        for ($i = 0; $i < $columns; $i++) {
            $table .= '<div class="skeleton-table-header-cell"></div>';
        }
        
        $table .= '</div><div class="skeleton-table-body">';
        
        for ($r = 0; $r < $rows; $r++) {
            $table .= '<div class="skeleton-table-row">';
            for ($c = 0; $c < $columns; $c++) {
                $table .= '<div class="skeleton-table-cell"></div>';
            }
            $table .= '</div>';
        }
        
        $table .= '</div></div>';
        return $table;
    }
    
    /**
     * 渲染表單骨架屏
     */
    public function renderFormSkeleton(int $fields = 6): string
    {
        $form = '<div class="skeleton-form">';
        
        for ($i = 0; $i < $fields; $i++) {
            $form .= '<div class="skeleton-form-field">
                <div class="skeleton-form-label"></div>
                <div class="skeleton-form-input"></div>
            </div>';
        }
        
        $form .= '<div class="skeleton-form-actions">
            <div class="skeleton-form-button primary"></div>
            <div class="skeleton-form-button secondary"></div>
        </div></div>';
        
        return $form;
    }
    
    /**
     * 渲染卡片網格骨架屏
     */
    public function renderCardGrid(int $cards = 6): string
    {
        $grid = '<div class="skeleton-card-grid">';
        
        for ($i = 0; $i < $cards; $i++) {
            $grid .= '<div class="skeleton-card">
                <div class="skeleton-card-image"></div>
                <div class="skeleton-card-content">
                    <div class="skeleton-card-title"></div>
                    <div class="skeleton-card-text"></div>
                    <div class="skeleton-card-text short"></div>
                </div>
                <div class="skeleton-card-actions">
                    <div class="skeleton-card-button"></div>
                    <div class="skeleton-card-button"></div>
                </div>
            </div>';
        }
        
        $grid .= '</div>';
        return $grid;
    }
    
    // 事件監聽器
    
    /**
     * 監聽顯示骨架屏事件
     */
    #[On('show-skeleton')]
    public function handleShowSkeleton(
        string $type = 'default',
        array $config = [],
        string $animation = 'pulse'
    ): void {
        $this->showSkeleton($type, $config, $animation);
    }
    
    /**
     * 監聽隱藏骨架屏事件
     */
    #[On('hide-skeleton')]
    public function handleHideSkeleton(): void
    {
        $this->hideSkeleton();
    }
    
    /**
     * 監聽進度更新事件
     */
    #[On('update-skeleton-progress')]
    public function handleUpdateProgress(int $progress): void
    {
        $this->updateProgress($progress);
    }
    
    /**
     * 監聽頁面載入事件
     */
    #[On('page-loading')]
    public function handlePageLoading(): void
    {
        $this->showSkeleton('default', [], 'shimmer');
    }
    
    /**
     * 監聽資料載入事件
     */
    #[On('data-loading')]
    public function handleDataLoading(string $type = 'table'): void
    {
        $this->showSkeleton($type, [], 'wave');
    }
    
    /**
     * 渲染元件
     */
    public function render()
    {
        return view('livewire.admin.layout.skeleton-loader');
    }
}