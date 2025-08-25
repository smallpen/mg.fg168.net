@extends('layouts.admin')

@section('title', $permission->display_name . ' - ' . __('permissions.titles.permission_management', ['default' => '權限管理']))

@section('content')
    <div class="space-y-6">
        
        <!-- 頁面標題和操作 -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">
                    {{ $permission->display_name }}
                </h1>
                <p class="text-gray-600 dark:text-gray-400 mt-1">
                    {{ $permission->description ?: __('permissions.empty.no_description', ['default' => '無描述']) }}
                </p>
                
                <!-- 權限基本資訊標籤 -->
                <div class="mt-3 flex items-center space-x-4 text-sm">
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200">
                        <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                        </svg>
                        {{ $permission->name }}
                    </span>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                        <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14-7H5m14 14H5"/>
                        </svg>
                        {{ $permission->module }}
                    </span>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                        <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 100 4m0-4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 100 4m0-4v2m0-6V4"/>
                        </svg>
                        {{ $permission->type }}
                    </span>
                    @if($permission->is_system_permission)
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200">
                            <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                            </svg>
                            系統權限
                        </span>
                    @endif
                </div>
            </div>
            
            <!-- 操作按鈕 -->
            <div class="flex items-center space-x-3 mt-4 sm:mt-0">
                @can('permissions.edit')
                    <a href="{{ route('admin.permissions.edit', $permission) }}" 
                       class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors duration-200">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                        </svg>
                        {{ __('admin.common.edit', ['default' => '編輯']) }}
                    </a>
                @endcan
                
                <a href="{{ route('admin.permissions.index') }}" 
                   class="inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors duration-200">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                    {{ __('admin.common.back', ['default' => '返回']) }}
                </a>
            </div>
        </div>
        
        <!-- 權限詳細資訊 -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            
            <!-- 基本資訊 -->
            <div class="lg:col-span-2 space-y-6">
                
                <!-- 權限基本資訊卡片 -->
                <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-6">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">
                        基本資訊
                    </h3>
                    <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">權限名稱</dt>
                            <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100 font-mono bg-gray-50 dark:bg-gray-700 px-2 py-1 rounded">{{ $permission->name }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">顯示名稱</dt>
                            <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $permission->display_name }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">所屬模組</dt>
                            <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $permission->module }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">權限類型</dt>
                            <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $permission->type }}</dd>
                        </div>
                        <div class="sm:col-span-2">
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">描述</dt>
                            <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">
                                {{ $permission->description ?: '無描述' }}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">建立時間</dt>
                            <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $permission->created_at->format('Y-m-d H:i:s') }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">最後更新</dt>
                            <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $permission->updated_at->format('Y-m-d H:i:s') }}</dd>
                        </div>
                    </dl>
                </div>
                
                <!-- 權限依賴關係 -->
                @if($permission->dependencies()->exists() || $permission->dependents()->exists())
                    <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-6">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">
                            依賴關係
                        </h3>
                        
                        @if($permission->dependencies()->exists())
                            <div class="mb-6">
                                <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    此權限依賴的權限：
                                </h4>
                                <div class="flex flex-wrap gap-2">
                                    @foreach($permission->dependencies as $dependency)
                                        <a href="{{ route('admin.permissions.show', $dependency) }}" 
                                           class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200 hover:bg-blue-200 dark:hover:bg-blue-800 transition-colors duration-200">
                                            <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                                            </svg>
                                            {{ $dependency->display_name }}
                                        </a>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                        
                        @if($permission->dependents()->exists())
                            <div>
                                <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    依賴此權限的權限：
                                </h4>
                                <div class="flex flex-wrap gap-2">
                                    @foreach($permission->dependents as $dependent)
                                        <a href="{{ route('admin.permissions.show', $dependent) }}" 
                                           class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200 hover:bg-green-200 dark:hover:bg-green-800 transition-colors duration-200">
                                            <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 17l-5-5m0 0l5-5m-5 5h12"/>
                                            </svg>
                                            {{ $dependent->display_name }}
                                        </a>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    </div>
                @endif
                
            </div>
            
            <!-- 側邊欄資訊 -->
            <div class="space-y-6">
                
                <!-- 使用統計 -->
                <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-6">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">
                        使用統計
                    </h3>
                    <div class="space-y-4">
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-gray-600 dark:text-gray-400">使用角色數</span>
                            <span class="text-lg font-semibold text-gray-900 dark:text-gray-100">{{ $permission->roles()->count() }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-gray-600 dark:text-gray-400">間接使用者數</span>
                            <span class="text-lg font-semibold text-gray-900 dark:text-gray-100">{{ $permission->user_count ?? 0 }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-gray-600 dark:text-gray-400">依賴權限數</span>
                            <span class="text-lg font-semibold text-gray-900 dark:text-gray-100">{{ $permission->dependencies()->count() }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-gray-600 dark:text-gray-400">被依賴數</span>
                            <span class="text-lg font-semibold text-gray-900 dark:text-gray-100">{{ $permission->dependents()->count() }}</span>
                        </div>
                    </div>
                </div>
                
                <!-- 使用此權限的角色 -->
                @if($permission->roles()->exists())
                    <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-6">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">
                            使用此權限的角色
                        </h3>
                        <div class="space-y-2">
                            @foreach($permission->roles as $role)
                                <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                                    <div>
                                        <div class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                            {{ $role->display_name }}
                                        </div>
                                        <div class="text-xs text-gray-500 dark:text-gray-400">
                                            {{ $role->name }}
                                        </div>
                                    </div>
                                    @can('roles.view')
                                        <a href="{{ route('admin.roles.show', $role) }}" 
                                           class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                                            </svg>
                                        </a>
                                    @endcan
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
                
                <!-- 快速操作 -->
                <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-6">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">
                        快速操作
                    </h3>
                    <div class="space-y-2">
                        @can('permissions.view')
                            <a href="{{ route('admin.permissions.dependencies', ['permission_id' => $permission->id]) }}" 
                               class="flex items-center w-full px-3 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors duration-200">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"/>
                                </svg>
                                檢視依賴關係圖表
                            </a>
                        @endcan
                        
                        @can('permissions.test')
                            <a href="{{ route('admin.permissions.test') }}?permission={{ $permission->name }}" 
                               class="flex items-center w-full px-3 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors duration-200">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                測試權限檢查
                            </a>
                        @endcan
                        
                        @can('permissions.view')
                            <a href="{{ route('admin.permissions.matrix') }}" 
                               class="flex items-center w-full px-3 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors duration-200">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7m0 10a2 2 0 002 2h2a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2h2a2 2 0 002-2z"/>
                                </svg>
                                檢視權限矩陣
                            </a>
                        @endcan
                    </div>
                </div>
                
            </div>
            
        </div>
        
    </div>
@endsection

@push('scripts')
<script>
    // 權限詳情頁面的 JavaScript 功能
    document.addEventListener('DOMContentLoaded', function() {
        // 複製權限名稱功能
        const permissionNameElement = document.querySelector('.font-mono');
        if (permissionNameElement) {
            permissionNameElement.style.cursor = 'pointer';
            permissionNameElement.title = '點擊複製權限名稱';
            
            permissionNameElement.addEventListener('click', function() {
                const permissionName = this.textContent.trim();
                navigator.clipboard.writeText(permissionName).then(function() {
                    // 顯示複製成功提示
                    const originalBg = permissionNameElement.style.backgroundColor;
                    permissionNameElement.style.backgroundColor = '#10b981';
                    permissionNameElement.style.color = 'white';
                    
                    setTimeout(function() {
                        permissionNameElement.style.backgroundColor = originalBg;
                        permissionNameElement.style.color = '';
                    }, 1000);
                    
                    if (typeof showToast === 'function') {
                        showToast('權限名稱已複製到剪貼簿', 'success');
                    }
                }).catch(function(err) {
                    console.error('複製失敗:', err);
                    if (typeof showToast === 'function') {
                        showToast('複製失敗', 'error');
                    }
                });
            });
        }
        
        // 統計數字動畫
        const statNumbers = document.querySelectorAll('.text-lg.font-semibold');
        statNumbers.forEach(function(element) {
            const finalValue = parseInt(element.textContent);
            if (finalValue > 0) {
                let currentValue = 0;
                const increment = Math.ceil(finalValue / 20);
                const timer = setInterval(function() {
                    currentValue += increment;
                    if (currentValue >= finalValue) {
                        currentValue = finalValue;
                        clearInterval(timer);
                    }
                    element.textContent = currentValue;
                }, 50);
            }
        });
    });
</script>
@endpush

@push('styles')
<style>
    /* 權限詳情頁面的自定義樣式 */
    .permission-detail-container {
        /* 確保詳情容器有適當的間距 */
        max-width: 100%;
    }
    
    /* 權限標籤懸停效果 */
    .permission-tag {
        transition: all 0.2s ease-in-out;
    }
    
    .permission-tag:hover {
        transform: translateY(-1px);
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }
    
    /* 統計卡片樣式 */
    .stat-card {
        background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
        border-radius: 0.75rem;
        padding: 1.5rem;
        border: 1px solid #e2e8f0;
    }
    
    .dark .stat-card {
        background: linear-gradient(135deg, #1f2937 0%, #111827 100%);
        border-color: #374151;
    }
    
    /* 角色卡片樣式 */
    .role-card {
        transition: all 0.2s ease-in-out;
        border: 1px solid transparent;
    }
    
    .role-card:hover {
        border-color: #3b82f6;
        transform: translateY(-1px);
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    }
    
    .dark .role-card:hover {
        border-color: #60a5fa;
    }
    
    /* 依賴關係連接線 */
    .dependency-connector {
        position: relative;
    }
    
    .dependency-connector::before {
        content: '';
        position: absolute;
        left: -8px;
        top: 50%;
        width: 16px;
        height: 1px;
        background-color: #d1d5db;
        transform: translateY(-50%);
    }
    
    .dark .dependency-connector::before {
        background-color: #4b5563;
    }
    
    /* 快速操作按鈕樣式 */
    .quick-action-btn {
        transition: all 0.2s ease-in-out;
        border-radius: 0.5rem;
    }
    
    .quick-action-btn:hover {
        transform: translateX(4px);
        background-color: #f3f4f6;
    }
    
    .dark .quick-action-btn:hover {
        background-color: #374151;
    }
    
    /* 響應式調整 */
    @media (max-width: 1024px) {
        .permission-detail-grid {
            grid-template-columns: 1fr;
        }
    }
    
    /* 載入動畫 */
    .loading-shimmer {
        background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
        background-size: 200% 100%;
        animation: shimmer 2s infinite;
    }
    
    .dark .loading-shimmer {
        background: linear-gradient(90deg, #374151 25%, #4b5563 50%, #374151 75%);
        background-size: 200% 100%;
    }
    
    @keyframes shimmer {
        0% {
            background-position: -200% 0;
        }
        100% {
            background-position: 200% 0;
        }
    }
</style>
@endpush
</x-admin.layout.admin-layout>