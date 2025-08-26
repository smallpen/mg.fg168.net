@extends('layouts.admin')

@section('title', __('admin.roles.permission_matrix.title'))

@push('styles')
<style>
    /* 權限矩陣樣式 */
    .permission-matrix-container {
        overflow-x: auto;
    }
    
    .permission-matrix {
        min-width: 800px;
    }
    
    .module-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        font-weight: 600;
        padding: 1rem;
        border-radius: 0.5rem 0.5rem 0 0;
    }
    
    .permission-row {
        transition: all 0.2s ease-in-out;
    }
    
    .permission-row:hover {
        background-color: #f8fafc;
        transform: translateX(2px);
    }
    
    .permission-checkbox {
        width: 18px;
        height: 18px;
        border-radius: 4px;
        border: 2px solid #d1d5db;
        transition: all 0.2s ease-in-out;
    }
    
    .permission-checkbox:checked {
        background-color: #10b981;
        border-color: #10b981;
    }
    
    .permission-checkbox:hover {
        border-color: #10b981;
        transform: scale(1.1);
    }
    
    .module-toggle {
        transition: all 0.2s ease-in-out;
    }
    
    .module-toggle:hover {
        background-color: rgba(255, 255, 255, 0.2);
    }
    
    .batch-actions {
        position: sticky;
        top: 0;
        z-index: 10;
        background: white;
        border-bottom: 1px solid #e5e7eb;
        padding: 1rem;
        margin: -1rem -1rem 1rem -1rem;
    }
    
    .permission-count {
        font-size: 0.75rem;
        background: #3b82f6;
        color: white;
        padding: 0.25rem 0.5rem;
        border-radius: 9999px;
        margin-left: 0.5rem;
    }
    
    .dependency-indicator {
        width: 8px;
        height: 8px;
        background: #f59e0b;
        border-radius: 50%;
        margin-left: 0.5rem;
        position: relative;
    }
    
    .dependency-indicator::after {
        content: '';
        position: absolute;
        top: -2px;
        left: -2px;
        right: -2px;
        bottom: -2px;
        border: 1px solid #f59e0b;
        border-radius: 50%;
        animation: pulse 2s infinite;
    }
    
    @keyframes pulse {
        0% {
            transform: scale(0.95);
            opacity: 1;
        }
        70% {
            transform: scale(1);
            opacity: 0.5;
        }
        100% {
            transform: scale(0.95);
            opacity: 1;
        }
    }
    
    .search-highlight {
        background-color: #fef3c7;
        padding: 0.125rem 0.25rem;
        border-radius: 0.25rem;
    }
</style>
@endpush

@section('content')
<div class="min-h-screen bg-gray-50 dark:bg-gray-900">
    <div class="container mx-auto px-4 py-6">
        {{-- 麵包屑導航 --}}
        <nav class="flex mb-6" aria-label="Breadcrumb">
            <ol class="inline-flex items-center space-x-1 md:space-x-3">
                <li class="inline-flex items-center">
                    <a href="{{ route('admin.dashboard') }}" class="inline-flex items-center text-sm font-medium text-gray-700 hover:text-blue-600 dark:text-gray-400 dark:hover:text-white">
                        <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z"></path>
                        </svg>
                        {{ __('admin.dashboard.title') }}
                    </a>
                </li>
                <li>
                    <div class="flex items-center">
                        <svg class="w-6 h-6 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"></path>
                        </svg>
                        <a href="{{ route('admin.roles.index') }}" class="ml-1 text-sm font-medium text-gray-700 hover:text-blue-600 md:ml-2 dark:text-gray-400 dark:hover:text-white">
                            {{ __('admin.roles.title') }}
                        </a>
                    </div>
                </li>
                @if($role)
                <li>
                    <div class="flex items-center">
                        <svg class="w-6 h-6 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"></path>
                        </svg>
                        <a href="{{ route('admin.roles.show', $role) }}" class="ml-1 text-sm font-medium text-gray-700 hover:text-blue-600 md:ml-2 dark:text-gray-400 dark:hover:text-white">
                            {{ $role->display_name }}
                        </a>
                    </div>
                </li>
                @endif
                <li>
                    <div class="flex items-center">
                        <svg class="w-6 h-6 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"></path>
                        </svg>
                        <span class="ml-1 text-sm font-medium text-gray-500 md:ml-2 dark:text-gray-400">
                            {{ __('admin.roles.permission_matrix.title') }}
                        </span>
                    </div>
                </li>
            </ol>
        </nav>

        {{-- 權限矩陣 Livewire 元件 --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm">
            @if($role)
                <livewire:admin.roles.permission-matrix :role="$role" />
            @else
                <livewire:admin.permissions.permission-matrix />
            @endif
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // 權限矩陣互動功能
    const permissionMatrix = {
        init() {
            this.bindEvents();
            this.initTooltips();
            this.initKeyboardNavigation();
        },
        
        bindEvents() {
            // 模組全選/取消全選
            document.addEventListener('click', (e) => {
                if (e.target.matches('.module-select-all')) {
                    this.toggleModulePermissions(e.target);
                }
            });
            
            // 權限依賴關係處理
            document.addEventListener('change', (e) => {
                if (e.target.matches('.permission-checkbox')) {
                    this.handlePermissionDependency(e.target);
                }
            });
            
            // 搜尋功能
            const searchInput = document.querySelector('#permission-search');
            if (searchInput) {
                searchInput.addEventListener('input', this.debounce(this.filterPermissions.bind(this), 300));
            }
        },
        
        toggleModulePermissions(button) {
            const module = button.dataset.module;
            const checkboxes = document.querySelectorAll(`input[data-module="${module}"]`);
            const allChecked = Array.from(checkboxes).every(cb => cb.checked);
            
            checkboxes.forEach(checkbox => {
                checkbox.checked = !allChecked;
                this.handlePermissionDependency(checkbox);
            });
            
            // 更新按鈕文字
            button.textContent = allChecked ? '全選' : '取消全選';
        },
        
        handlePermissionDependency(checkbox) {
            const permissionName = checkbox.value;
            const dependencies = this.getPermissionDependencies(permissionName);
            
            if (checkbox.checked) {
                // 勾選時自動勾選依賴項目
                dependencies.required.forEach(dep => {
                    const depCheckbox = document.querySelector(`input[value="${dep}"]`);
                    if (depCheckbox && !depCheckbox.checked) {
                        depCheckbox.checked = true;
                        this.showDependencyNotification(dep, 'required');
                    }
                });
            } else {
                // 取消勾選時自動取消被依賴的項目
                dependencies.dependent.forEach(dep => {
                    const depCheckbox = document.querySelector(`input[value="${dep}"]`);
                    if (depCheckbox && depCheckbox.checked) {
                        depCheckbox.checked = false;
                        this.showDependencyNotification(dep, 'dependent');
                    }
                });
            }
        },
        
        getPermissionDependencies(permissionName) {
            // 定義權限依賴關係
            const dependencies = {
                'users.edit': { required: ['users.view'], dependent: [] },
                'users.delete': { required: ['users.view'], dependent: [] },
                'roles.edit': { required: ['roles.view'], dependent: [] },
                'roles.delete': { required: ['roles.view'], dependent: [] },
                'permissions.edit': { required: ['permissions.view'], dependent: [] },
                'permissions.delete': { required: ['permissions.view'], dependent: [] },
            };
            
            return dependencies[permissionName] || { required: [], dependent: [] };
        },
        
        showDependencyNotification(permissionName, type) {
            const message = type === 'required' 
                ? `自動勾選依賴權限：${permissionName}`
                : `自動取消被依賴權限：${permissionName}`;
                
            this.showToast(message, 'info');
        },
        
        filterPermissions(searchTerm) {
            const rows = document.querySelectorAll('.permission-row');
            const term = searchTerm.toLowerCase();
            
            rows.forEach(row => {
                const permissionName = row.querySelector('.permission-name').textContent.toLowerCase();
                const permissionDesc = row.querySelector('.permission-description')?.textContent.toLowerCase() || '';
                
                const matches = permissionName.includes(term) || permissionDesc.includes(term);
                
                if (matches) {
                    row.style.display = '';
                    this.highlightSearchTerm(row, term);
                } else {
                    row.style.display = 'none';
                }
            });
            
            // 隱藏空的模組
            this.hideEmptyModules();
        },
        
        highlightSearchTerm(row, term) {
            if (!term) return;
            
            const nameElement = row.querySelector('.permission-name');
            const descElement = row.querySelector('.permission-description');
            
            [nameElement, descElement].forEach(element => {
                if (element) {
                    const text = element.textContent;
                    const regex = new RegExp(`(${term})`, 'gi');
                    element.innerHTML = text.replace(regex, '<span class="search-highlight">$1</span>');
                }
            });
        },
        
        hideEmptyModules() {
            const modules = document.querySelectorAll('.permission-module');
            
            modules.forEach(module => {
                const visibleRows = module.querySelectorAll('.permission-row:not([style*="display: none"])');
                module.style.display = visibleRows.length > 0 ? '' : 'none';
            });
        },
        
        initTooltips() {
            // 初始化權限說明提示
            const tooltipElements = document.querySelectorAll('[data-tooltip]');
            
            tooltipElements.forEach(element => {
                element.addEventListener('mouseenter', this.showTooltip.bind(this));
                element.addEventListener('mouseleave', this.hideTooltip.bind(this));
            });
        },
        
        showTooltip(e) {
            const element = e.target;
            const text = element.dataset.tooltip;
            
            const tooltip = document.createElement('div');
            tooltip.className = 'absolute z-50 px-2 py-1 text-sm text-white bg-gray-900 rounded shadow-lg';
            tooltip.textContent = text;
            tooltip.id = 'permission-tooltip';
            
            document.body.appendChild(tooltip);
            
            const rect = element.getBoundingClientRect();
            tooltip.style.left = rect.left + 'px';
            tooltip.style.top = (rect.top - tooltip.offsetHeight - 5) + 'px';
        },
        
        hideTooltip() {
            const tooltip = document.getElementById('permission-tooltip');
            if (tooltip) {
                tooltip.remove();
            }
        },
        
        initKeyboardNavigation() {
            document.addEventListener('keydown', (e) => {
                // Ctrl/Cmd + S 儲存權限
                if ((e.ctrlKey || e.metaKey) && e.key === 's') {
                    e.preventDefault();
                    const saveButton = document.querySelector('#save-permissions');
                    if (saveButton) {
                        saveButton.click();
                    }
                }
                
                // Ctrl/Cmd + A 全選所有權限
                if ((e.ctrlKey || e.metaKey) && e.key === 'a' && e.target.matches('.permission-checkbox')) {
                    e.preventDefault();
                    this.toggleAllPermissions();
                }
            });
        },
        
        toggleAllPermissions() {
            const checkboxes = document.querySelectorAll('.permission-checkbox');
            const allChecked = Array.from(checkboxes).every(cb => cb.checked);
            
            checkboxes.forEach(checkbox => {
                checkbox.checked = !allChecked;
            });
        },
        
        showToast(message, type = 'info') {
            const toast = document.createElement('div');
            toast.className = `fixed top-4 right-4 z-50 px-4 py-2 rounded-md shadow-lg text-white ${
                type === 'success' ? 'bg-green-500' :
                type === 'error' ? 'bg-red-500' :
                type === 'warning' ? 'bg-yellow-500' :
                'bg-blue-500'
            }`;
            toast.textContent = message;
            
            document.body.appendChild(toast);
            
            setTimeout(() => {
                toast.style.opacity = '0';
                toast.style.transform = 'translateX(100%)';
                setTimeout(() => toast.remove(), 300);
            }, 3000);
        },
        
        debounce(func, wait) {
            let timeout;
            return function executedFunction(...args) {
                const later = () => {
                    clearTimeout(timeout);
                    func(...args);
                };
                clearTimeout(timeout);
                timeout = setTimeout(later, wait);
            };
        }
    };
    
    // 初始化權限矩陣
    permissionMatrix.init();
    
    // Livewire 事件監聽
    document.addEventListener('livewire:init', () => {
        Livewire.on('permissions-updated', (event) => {
            permissionMatrix.showToast(event.message, 'success');
        });
        
        Livewire.on('permission-dependency-resolved', (event) => {
            permissionMatrix.showToast(`已自動處理權限依賴：${event.permissions.join(', ')}`, 'info');
        });
    });
});
</script>
@endpush