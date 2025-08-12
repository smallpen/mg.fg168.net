<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Collection;

/**
 * 元件懶載入服務
 * 
 * 負責管理 Livewire 元件的懶載入機制，提升頁面載入效能
 */
class ComponentLazyLoadingService
{
    /**
     * 懶載入元件配置
     */
    protected array $lazyComponents = [
        'admin.dashboard.stats-chart' => [
            'priority' => 'low',
            'defer' => true,
            'placeholder' => 'components.loading.chart-skeleton',
        ],
        'admin.dashboard.recent-activity' => [
            'priority' => 'medium',
            'defer' => true,
            'placeholder' => 'components.loading.activity-skeleton',
        ],
        'admin.layout.notification-center' => [
            'priority' => 'high',
            'defer' => false,
            'placeholder' => 'components.loading.notification-skeleton',
        ],
        'admin.layout.global-search' => [
            'priority' => 'medium',
            'defer' => true,
            'placeholder' => 'components.loading.search-skeleton',
        ],
    ];

    /**
     * 檢查元件是否應該懶載入
     */
    public function shouldLazyLoad(string $componentName): bool
    {
        return isset($this->lazyComponents[$componentName]) && 
               $this->lazyComponents[$componentName]['defer'];
    }

    /**
     * 取得元件的載入優先級
     */
    public function getComponentPriority(string $componentName): string
    {
        return $this->lazyComponents[$componentName]['priority'] ?? 'medium';
    }

    /**
     * 取得元件的佔位符視圖
     */
    public function getPlaceholderView(string $componentName): ?string
    {
        return $this->lazyComponents[$componentName]['placeholder'] ?? null;
    }

    /**
     * 註冊新的懶載入元件
     */
    public function registerLazyComponent(string $componentName, array $config): void
    {
        $this->lazyComponents[$componentName] = array_merge([
            'priority' => 'medium',
            'defer' => true,
            'placeholder' => null,
        ], $config);
    }

    /**
     * 取得所有懶載入元件配置
     */
    public function getLazyComponents(): array
    {
        return $this->lazyComponents;
    }

    /**
     * 根據優先級排序元件載入順序
     */
    public function getComponentsByPriority(): array
    {
        $priorityOrder = ['high' => 1, 'medium' => 2, 'low' => 3];
        
        return collect($this->lazyComponents)
            ->sortBy(function ($config, $componentName) use ($priorityOrder) {
                return $priorityOrder[$config['priority']] ?? 2;
            })
            ->toArray();
    }

    /**
     * 生成懶載入元件的 HTML
     */
    public function generateLazyComponentHtml(string $componentName, array $attributes = []): string
    {
        $config = $this->lazyComponents[$componentName] ?? [];
        $placeholder = $config['placeholder'] ?? null;
        
        $attributeString = collect($attributes)
            ->map(fn($value, $key) => "{$key}=\"{$value}\"")
            ->implode(' ');

        if ($placeholder && View::exists($placeholder)) {
            return "<div data-lazy-component=\"{$componentName}\" {$attributeString}>" .
                   view($placeholder)->render() .
                   "</div>";
        }

        return "<div data-lazy-component=\"{$componentName}\" {$attributeString}>" .
               "<div class=\"animate-pulse bg-gray-200 dark:bg-gray-700 rounded h-32\"></div>" .
               "</div>";
    }

    /**
     * 預載入關鍵元件
     */
    public function preloadCriticalComponents(): array
    {
        return collect($this->lazyComponents)
            ->filter(fn($config) => $config['priority'] === 'high')
            ->keys()
            ->toArray();
    }

    /**
     * 取得元件載入統計
     */
    public function getLoadingStats(): array
    {
        $cacheKey = 'component_loading_stats';
        
        return Cache::remember($cacheKey, 300, function () {
            return [
                'total_components' => count($this->lazyComponents),
                'high_priority' => count(array_filter($this->lazyComponents, fn($c) => $c['priority'] === 'high')),
                'medium_priority' => count(array_filter($this->lazyComponents, fn($c) => $c['priority'] === 'medium')),
                'low_priority' => count(array_filter($this->lazyComponents, fn($c) => $c['priority'] === 'low')),
                'deferred_components' => count(array_filter($this->lazyComponents, fn($c) => $c['defer'])),
            ];
        });
    }

    /**
     * 清除載入統計快取
     */
    public function clearLoadingStatsCache(): void
    {
        Cache::forget('component_loading_stats');
    }
}