{{-- SkeletonLoader 骨架屏載入元件視圖 --}}
<div class="{{ $this->skeletonContainerClasses }}"
     x-data="{ 
         isLoading: @entangle('isLoading'),
         progress: @entangle('loadingProgress'),
         showProgress: @entangle('showProgress')
     }"
     x-show="isLoading"
     x-transition:enter="transition ease-out duration-300"
     x-transition:enter-start="opacity-0"
     x-transition:enter-end="opacity-100"
     x-transition:leave="transition ease-in duration-300"
     x-transition:leave-start="opacity-100"
     x-transition:leave-end="opacity-0"
     style="display: none;">
    
    {{-- 進度指示器 --}}
    @if($showProgress)
        <div class="skeleton-progress-bar">
            <div class="progress-fill" 
                 :style="`width: ${progress}%`"
                 style="width: {{ $loadingProgress }}%"></div>
        </div>
    @endif
    
    {{-- 根據類型渲染不同的骨架屏 --}}
    @switch($skeletonType)
        @case('dashboard')
            {{-- 儀表板骨架屏 --}}
            <div class="skeleton-dashboard {{ $this->animationClasses }}">
                {{-- 統計卡片 --}}
                <div class="skeleton-stats-grid">
                    @for($i = 0; $i < 4; $i++)
                        <div class="skeleton-stats-card">
                            <div class="skeleton-stats-icon"></div>
                            <div class="skeleton-stats-content">
                                <div class="skeleton-stats-value"></div>
                                <div class="skeleton-stats-label"></div>
                                <div class="skeleton-stats-change"></div>
                            </div>
                        </div>
                    @endfor
                </div>
                
                {{-- 圖表區域 --}}
                <div class="skeleton-charts-grid">
                    <div class="skeleton-chart large">
                        <div class="skeleton-chart-header">
                            <div class="skeleton-chart-title"></div>
                            <div class="skeleton-chart-subtitle"></div>
                        </div>
                        <div class="skeleton-chart-body"></div>
                    </div>
                    
                    <div class="skeleton-chart small">
                        <div class="skeleton-chart-header">
                            <div class="skeleton-chart-title"></div>
                        </div>
                        <div class="skeleton-chart-body"></div>
                    </div>
                </div>
                
                {{-- 最近活動 --}}
                <div class="skeleton-recent-activity">
                    <div class="skeleton-section-title"></div>
                    @for($i = 0; $i < 5; $i++)
                        <div class="skeleton-activity-item">
                            <div class="skeleton-activity-avatar"></div>
                            <div class="skeleton-activity-content">
                                <div class="skeleton-activity-text"></div>
                                <div class="skeleton-activity-time"></div>
                            </div>
                        </div>
                    @endfor
                </div>
            </div>
            @break
            
        @case('table')
            {{-- 表格骨架屏 --}}
            <div class="skeleton-table {{ $this->animationClasses }}">
                {{-- 表格標題 --}}
                <div class="skeleton-table-title"></div>
                
                {{-- 表格工具列 --}}
                <div class="skeleton-table-toolbar">
                    <div class="skeleton-search-box"></div>
                    <div class="skeleton-filter-buttons">
                        <div class="skeleton-button"></div>
                        <div class="skeleton-button"></div>
                    </div>
                </div>
                
                {{-- 表格標頭 --}}
                <div class="skeleton-table-header">
                    @for($i = 0; $i < 5; $i++)
                        <div class="skeleton-table-header-cell"></div>
                    @endfor
                </div>
                
                {{-- 表格內容 --}}
                <div class="skeleton-table-body">
                    @for($r = 0; $r < 8; $r++)
                        <div class="skeleton-table-row">
                            @for($c = 0; $c < 5; $c++)
                                <div class="skeleton-table-cell"></div>
                            @endfor
                        </div>
                    @endfor
                </div>
                
                {{-- 分頁 --}}
                <div class="skeleton-pagination">
                    <div class="skeleton-pagination-info"></div>
                    <div class="skeleton-pagination-buttons">
                        @for($i = 0; $i < 5; $i++)
                            <div class="skeleton-pagination-button"></div>
                        @endfor
                    </div>
                </div>
            </div>
            @break
            
        @case('form')
            {{-- 表單骨架屏 --}}
            <div class="skeleton-form {{ $this->animationClasses }}">
                <div class="skeleton-form-header">
                    <div class="skeleton-form-title"></div>
                    <div class="skeleton-form-subtitle"></div>
                </div>
                
                <div class="skeleton-form-body">
                    @for($i = 0; $i < 6; $i++)
                        <div class="skeleton-form-field">
                            <div class="skeleton-form-label"></div>
                            <div class="skeleton-form-input {{ $i % 3 === 0 ? 'textarea' : '' }}"></div>
                            @if($i % 4 === 0)
                                <div class="skeleton-form-help"></div>
                            @endif
                        </div>
                    @endfor
                    
                    {{-- 檔案上傳欄位 --}}
                    <div class="skeleton-form-field">
                        <div class="skeleton-form-label"></div>
                        <div class="skeleton-file-upload">
                            <div class="skeleton-file-drop-zone"></div>
                        </div>
                    </div>
                </div>
                
                <div class="skeleton-form-actions">
                    <div class="skeleton-form-button primary"></div>
                    <div class="skeleton-form-button secondary"></div>
                </div>
            </div>
            @break
            
        @case('card-list')
            {{-- 卡片列表骨架屏 --}}
            <div class="skeleton-card-list {{ $this->animationClasses }}">
                <div class="skeleton-list-header">
                    <div class="skeleton-list-title"></div>
                    <div class="skeleton-list-actions">
                        <div class="skeleton-button"></div>
                        <div class="skeleton-button"></div>
                    </div>
                </div>
                
                <div class="skeleton-card-grid">
                    @for($i = 0; $i < 6; $i++)
                        <div class="skeleton-card">
                            <div class="skeleton-card-image"></div>
                            <div class="skeleton-card-content">
                                <div class="skeleton-card-title"></div>
                                <div class="skeleton-card-text"></div>
                                <div class="skeleton-card-text short"></div>
                                <div class="skeleton-card-meta">
                                    <div class="skeleton-card-tag"></div>
                                    <div class="skeleton-card-date"></div>
                                </div>
                            </div>
                            <div class="skeleton-card-actions">
                                <div class="skeleton-card-button"></div>
                                <div class="skeleton-card-button"></div>
                            </div>
                        </div>
                    @endfor
                </div>
            </div>
            @break
            
        @case('profile')
            {{-- 個人資料骨架屏 --}}
            <div class="skeleton-profile {{ $this->animationClasses }}">
                <div class="skeleton-profile-header">
                    <div class="skeleton-profile-avatar"></div>
                    <div class="skeleton-profile-info">
                        <div class="skeleton-profile-name"></div>
                        <div class="skeleton-profile-title"></div>
                        <div class="skeleton-profile-email"></div>
                    </div>
                    <div class="skeleton-profile-actions">
                        <div class="skeleton-button"></div>
                        <div class="skeleton-button"></div>
                    </div>
                </div>
                
                <div class="skeleton-profile-tabs">
                    @for($i = 0; $i < 4; $i++)
                        <div class="skeleton-tab"></div>
                    @endfor
                </div>
                
                <div class="skeleton-profile-content">
                    <div class="skeleton-profile-section">
                        <div class="skeleton-section-title"></div>
                        @for($i = 0; $i < 4; $i++)
                            <div class="skeleton-profile-field">
                                <div class="skeleton-field-label"></div>
                                <div class="skeleton-field-value"></div>
                            </div>
                        @endfor
                    </div>
                </div>
            </div>
            @break
            
        @case('sidebar')
            {{-- 側邊欄骨架屏 --}}
            <div class="skeleton-sidebar {{ $this->animationClasses }}">
                <div class="skeleton-sidebar-header">
                    <div class="skeleton-logo"></div>
                </div>
                
                <div class="skeleton-sidebar-menu">
                    @for($i = 0; $i < 8; $i++)
                        <div class="skeleton-menu-item {{ $i % 3 === 0 ? 'has-submenu' : '' }}">
                            <div class="skeleton-menu-icon"></div>
                            <div class="skeleton-menu-text"></div>
                            @if($i % 3 === 0)
                                <div class="skeleton-menu-arrow"></div>
                            @endif
                        </div>
                        
                        @if($i % 3 === 0)
                            <div class="skeleton-submenu">
                                @for($j = 0; $j < 3; $j++)
                                    <div class="skeleton-submenu-item">
                                        <div class="skeleton-submenu-text"></div>
                                    </div>
                                @endfor
                            </div>
                        @endif
                    @endfor
                </div>
            </div>
            @break
            
        @default
            {{-- 預設骨架屏 --}}
            <div class="skeleton-default {{ $this->animationClasses }}">
                <div class="skeleton-header">
                    <div class="skeleton-title"></div>
                    <div class="skeleton-subtitle"></div>
                </div>
                
                <div class="skeleton-content">
                    @for($i = 0; $i < 5; $i++)
                        <div class="skeleton-line {{ $i % 3 === 2 ? 'short' : '' }}"></div>
                    @endfor
                </div>
                
                <div class="skeleton-footer">
                    <div class="skeleton-button"></div>
                    <div class="skeleton-button"></div>
                </div>
            </div>
    @endswitch
</div>

{{-- 骨架屏樣式 --}}
<style>
/* 基礎骨架屏樣式 */
.skeleton-container {
    @apply w-full;
}

.skeleton-container.loading {
    @apply animate-pulse;
}

/* 進度條 */
.skeleton-progress-bar {
    @apply w-full h-1 bg-gray-200 dark:bg-gray-700 mb-4 overflow-hidden;
}

.skeleton-progress-bar .progress-fill {
    @apply h-full bg-blue-500 transition-all duration-300 ease-out;
}

/* 基礎骨架元素 */
.skeleton-element {
    @apply bg-gray-200 dark:bg-gray-700 rounded;
}

/* 動畫類型 */
.skeleton-pulse .skeleton-element,
.skeleton-pulse [class*="skeleton-"] {
    @apply bg-gray-200 dark:bg-gray-700;
    animation: skeleton-pulse 1.5s ease-in-out infinite;
}

.skeleton-wave .skeleton-element,
.skeleton-wave [class*="skeleton-"] {
    @apply bg-gray-200 dark:bg-gray-700 relative overflow-hidden;
}

.skeleton-wave .skeleton-element::after,
.skeleton-wave [class*="skeleton-"]::after {
    @apply absolute inset-0;
    content: '';
    background: linear-gradient(90deg, transparent, rgba(255,255,255,0.4), transparent);
    animation: skeleton-wave 1.5s ease-in-out infinite;
}

.skeleton-shimmer .skeleton-element,
.skeleton-shimmer [class*="skeleton-"] {
    @apply bg-gradient-to-r from-gray-200 via-gray-300 to-gray-200 dark:from-gray-700 dark:via-gray-600 dark:to-gray-700;
    background-size: 200% 100%;
    animation: skeleton-shimmer 1.5s ease-in-out infinite;
}

/* 動畫關鍵幀 */
@keyframes skeleton-pulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.5; }
}

@keyframes skeleton-wave {
    0% { transform: translateX(-100%); }
    100% { transform: translateX(100%); }
}

@keyframes skeleton-shimmer {
    0% { background-position: -200% 0; }
    100% { background-position: 200% 0; }
}

/* 儀表板骨架屏 */
.skeleton-dashboard {
    @apply space-y-6;
}

.skeleton-stats-grid {
    @apply grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4;
}

.skeleton-stats-card {
    @apply bg-white dark:bg-gray-800 p-4 rounded-lg border border-gray-200 dark:border-gray-700 flex items-center space-x-4;
}

.skeleton-stats-icon {
    @apply w-12 h-12 bg-gray-200 dark:bg-gray-700 rounded-lg;
}

.skeleton-stats-content {
    @apply flex-1 space-y-2;
}

.skeleton-stats-value {
    @apply h-6 bg-gray-200 dark:bg-gray-700 rounded w-20;
}

.skeleton-stats-label {
    @apply h-4 bg-gray-200 dark:bg-gray-700 rounded w-24;
}

.skeleton-stats-change {
    @apply h-3 bg-gray-200 dark:bg-gray-700 rounded w-16;
}

.skeleton-charts-grid {
    @apply grid grid-cols-1 lg:grid-cols-3 gap-6;
}

.skeleton-chart {
    @apply bg-white dark:bg-gray-800 p-6 rounded-lg border border-gray-200 dark:border-gray-700;
}

.skeleton-chart.large {
    @apply lg:col-span-2;
}

.skeleton-chart-header {
    @apply mb-4 space-y-2;
}

.skeleton-chart-title {
    @apply h-5 bg-gray-200 dark:bg-gray-700 rounded w-32;
}

.skeleton-chart-subtitle {
    @apply h-3 bg-gray-200 dark:bg-gray-700 rounded w-48;
}

.skeleton-chart-body {
    @apply h-64 bg-gray-200 dark:bg-gray-700 rounded;
}

.skeleton-recent-activity {
    @apply bg-white dark:bg-gray-800 p-6 rounded-lg border border-gray-200 dark:border-gray-700 space-y-4;
}

.skeleton-section-title {
    @apply h-5 bg-gray-200 dark:bg-gray-700 rounded w-32 mb-4;
}

.skeleton-activity-item {
    @apply flex items-center space-x-3;
}

.skeleton-activity-avatar {
    @apply w-8 h-8 bg-gray-200 dark:bg-gray-700 rounded-full;
}

.skeleton-activity-content {
    @apply flex-1 space-y-1;
}

.skeleton-activity-text {
    @apply h-4 bg-gray-200 dark:bg-gray-700 rounded;
}

.skeleton-activity-time {
    @apply h-3 bg-gray-200 dark:bg-gray-700 rounded w-20;
}

/* 表格骨架屏 */
.skeleton-table {
    @apply bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden;
}

.skeleton-table-title {
    @apply h-6 bg-gray-200 dark:bg-gray-700 rounded w-48 m-6;
}

.skeleton-table-toolbar {
    @apply flex items-center justify-between px-6 py-4 border-b border-gray-200 dark:border-gray-700;
}

.skeleton-search-box {
    @apply h-10 bg-gray-200 dark:bg-gray-700 rounded w-64;
}

.skeleton-filter-buttons {
    @apply flex space-x-2;
}

.skeleton-button {
    @apply h-10 bg-gray-200 dark:bg-gray-700 rounded w-20;
}

.skeleton-table-header {
    @apply grid grid-cols-5 gap-4 px-6 py-3 bg-gray-50 dark:bg-gray-700 border-b border-gray-200 dark:border-gray-600;
}

.skeleton-table-header-cell {
    @apply h-4 bg-gray-200 dark:bg-gray-600 rounded;
}

.skeleton-table-body {
    @apply divide-y divide-gray-200 dark:divide-gray-700;
}

.skeleton-table-row {
    @apply grid grid-cols-5 gap-4 px-6 py-4;
}

.skeleton-table-cell {
    @apply h-4 bg-gray-200 dark:bg-gray-700 rounded;
}

.skeleton-pagination {
    @apply flex items-center justify-between px-6 py-4 border-t border-gray-200 dark:border-gray-700;
}

.skeleton-pagination-info {
    @apply h-4 bg-gray-200 dark:bg-gray-700 rounded w-32;
}

.skeleton-pagination-buttons {
    @apply flex space-x-1;
}

.skeleton-pagination-button {
    @apply h-8 w-8 bg-gray-200 dark:bg-gray-700 rounded;
}

/* 表單骨架屏 */
.skeleton-form {
    @apply bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6 space-y-6;
}

.skeleton-form-header {
    @apply space-y-2 pb-4 border-b border-gray-200 dark:border-gray-700;
}

.skeleton-form-title {
    @apply h-6 bg-gray-200 dark:bg-gray-700 rounded w-48;
}

.skeleton-form-subtitle {
    @apply h-4 bg-gray-200 dark:bg-gray-700 rounded w-64;
}

.skeleton-form-body {
    @apply space-y-4;
}

.skeleton-form-field {
    @apply space-y-2;
}

.skeleton-form-label {
    @apply h-4 bg-gray-200 dark:bg-gray-700 rounded w-24;
}

.skeleton-form-input {
    @apply h-10 bg-gray-200 dark:bg-gray-700 rounded;
}

.skeleton-form-input.textarea {
    @apply h-24;
}

.skeleton-form-help {
    @apply h-3 bg-gray-200 dark:bg-gray-700 rounded w-48;
}

.skeleton-file-upload {
    @apply space-y-2;
}

.skeleton-file-drop-zone {
    @apply h-32 bg-gray-200 dark:bg-gray-700 rounded-lg border-2 border-dashed border-gray-300 dark:border-gray-600;
}

.skeleton-form-actions {
    @apply flex space-x-3 pt-4 border-t border-gray-200 dark:border-gray-700;
}

.skeleton-form-button {
    @apply h-10 bg-gray-200 dark:bg-gray-700 rounded;
}

.skeleton-form-button.primary {
    @apply w-24;
}

.skeleton-form-button.secondary {
    @apply w-20;
}

/* 卡片列表骨架屏 */
.skeleton-card-list {
    @apply space-y-6;
}

.skeleton-list-header {
    @apply flex items-center justify-between;
}

.skeleton-list-title {
    @apply h-6 bg-gray-200 dark:bg-gray-700 rounded w-48;
}

.skeleton-list-actions {
    @apply flex space-x-2;
}

.skeleton-card-grid {
    @apply grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6;
}

.skeleton-card {
    @apply bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden;
}

.skeleton-card-image {
    @apply h-48 bg-gray-200 dark:bg-gray-700;
}

.skeleton-card-content {
    @apply p-4 space-y-3;
}

.skeleton-card-title {
    @apply h-5 bg-gray-200 dark:bg-gray-700 rounded;
}

.skeleton-card-text {
    @apply h-4 bg-gray-200 dark:bg-gray-700 rounded;
}

.skeleton-card-text.short {
    @apply w-3/4;
}

.skeleton-card-meta {
    @apply flex items-center justify-between;
}

.skeleton-card-tag {
    @apply h-3 bg-gray-200 dark:bg-gray-700 rounded w-16;
}

.skeleton-card-date {
    @apply h-3 bg-gray-200 dark:bg-gray-700 rounded w-20;
}

.skeleton-card-actions {
    @apply flex space-x-2 p-4 border-t border-gray-200 dark:border-gray-700;
}

.skeleton-card-button {
    @apply h-8 bg-gray-200 dark:bg-gray-700 rounded w-16;
}

/* 個人資料骨架屏 */
.skeleton-profile {
    @apply space-y-6;
}

.skeleton-profile-header {
    @apply bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6 flex items-center space-x-6;
}

.skeleton-profile-avatar {
    @apply w-24 h-24 bg-gray-200 dark:bg-gray-700 rounded-full;
}

.skeleton-profile-info {
    @apply flex-1 space-y-2;
}

.skeleton-profile-name {
    @apply h-6 bg-gray-200 dark:bg-gray-700 rounded w-48;
}

.skeleton-profile-title {
    @apply h-4 bg-gray-200 dark:bg-gray-700 rounded w-32;
}

.skeleton-profile-email {
    @apply h-4 bg-gray-200 dark:bg-gray-700 rounded w-56;
}

.skeleton-profile-actions {
    @apply flex space-x-2;
}

.skeleton-profile-tabs {
    @apply flex space-x-1 border-b border-gray-200 dark:border-gray-700;
}

.skeleton-tab {
    @apply h-10 bg-gray-200 dark:bg-gray-700 rounded-t w-24;
}

.skeleton-profile-content {
    @apply bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6;
}

.skeleton-profile-section {
    @apply space-y-4;
}

.skeleton-profile-field {
    @apply flex items-center space-x-4;
}

.skeleton-field-label {
    @apply h-4 bg-gray-200 dark:bg-gray-700 rounded w-24;
}

.skeleton-field-value {
    @apply h-4 bg-gray-200 dark:bg-gray-700 rounded flex-1;
}

/* 側邊欄骨架屏 */
.skeleton-sidebar {
    @apply w-64 bg-white dark:bg-gray-800 border-r border-gray-200 dark:border-gray-700 p-4 space-y-4;
}

.skeleton-sidebar-header {
    @apply pb-4 border-b border-gray-200 dark:border-gray-700;
}

.skeleton-logo {
    @apply h-8 bg-gray-200 dark:bg-gray-700 rounded w-32;
}

.skeleton-sidebar-menu {
    @apply space-y-2;
}

.skeleton-menu-item {
    @apply flex items-center space-x-3 p-2;
}

.skeleton-menu-icon {
    @apply w-5 h-5 bg-gray-200 dark:bg-gray-700 rounded;
}

.skeleton-menu-text {
    @apply h-4 bg-gray-200 dark:bg-gray-700 rounded flex-1;
}

.skeleton-menu-arrow {
    @apply w-4 h-4 bg-gray-200 dark:bg-gray-700 rounded;
}

.skeleton-submenu {
    @apply ml-8 space-y-1;
}

.skeleton-submenu-item {
    @apply p-2;
}

.skeleton-submenu-text {
    @apply h-3 bg-gray-200 dark:bg-gray-700 rounded;
}

/* 預設骨架屏 */
.skeleton-default {
    @apply bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6 space-y-4;
}

.skeleton-header {
    @apply space-y-2 pb-4 border-b border-gray-200 dark:border-gray-700;
}

.skeleton-title {
    @apply h-6 bg-gray-200 dark:bg-gray-700 rounded w-64;
}

.skeleton-subtitle {
    @apply h-4 bg-gray-200 dark:bg-gray-700 rounded w-48;
}

.skeleton-content {
    @apply space-y-3;
}

.skeleton-line {
    @apply h-4 bg-gray-200 dark:bg-gray-700 rounded;
}

.skeleton-line.short {
    @apply w-3/4;
}

.skeleton-footer {
    @apply flex space-x-3 pt-4 border-t border-gray-200 dark:border-gray-700;
}

/* 響應式設計 */
@media (max-width: 768px) {
    .skeleton-stats-grid {
        @apply grid-cols-1;
    }
    
    .skeleton-charts-grid {
        @apply grid-cols-1;
    }
    
    .skeleton-card-grid {
        @apply grid-cols-1;
    }
    
    .skeleton-table-header,
    .skeleton-table-row {
        @apply grid-cols-3;
    }
    
    .skeleton-profile-header {
        @apply flex-col items-start space-x-0 space-y-4;
    }
}

/* 動畫效能優化 */
@media (prefers-reduced-motion: reduce) {
    .skeleton-pulse,
    .skeleton-wave,
    .skeleton-shimmer {
        animation: none;
    }
    
    .skeleton-container.loading {
        @apply animate-none;
    }
}

/* 高對比模式 */
@media (prefers-contrast: high) {
    [class*="skeleton-"] {
        @apply border border-gray-400 dark:border-gray-500;
    }
}
</style>