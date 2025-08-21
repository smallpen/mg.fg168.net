@extends('layouts.admin')

@section('title', __('admin.permissions.test.title'))

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            {{-- 權限測試工具元件 --}}
            <livewire:admin.permissions.permission-test />
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    /* 權限測試專用樣式 */
    .permission-test-container {
        max-width: 1200px;
        margin: 0 auto;
    }
    
    .test-result-success {
        background-color: #f0f9ff;
        border-color: #10b981;
    }
    
    .test-result-error {
        background-color: #fef2f2;
        border-color: #ef4444;
    }
    
    .permission-path-item {
        transition: all 0.2s ease-in-out;
    }
    
    .permission-path-item:hover {
        transform: translateX(4px);
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }
    
    .test-mode-selector input[type="radio"]:checked + label {
        background-color: #3b82f6;
        color: white;
        border-color: #3b82f6;
    }
    
    .test-mode-selector label {
        transition: all 0.2s ease-in-out;
        cursor: pointer;
        padding: 0.5rem 1rem;
        border: 2px solid #d1d5db;
        border-radius: 0.375rem;
        background-color: white;
    }
    
    .test-mode-selector label:hover {
        border-color: #3b82f6;
        background-color: #f8fafc;
    }
    
    @media (prefers-color-scheme: dark) {
        .test-result-success {
            background-color: #064e3b;
            border-color: #10b981;
        }
        
        .test-result-error {
            background-color: #7f1d1d;
            border-color: #ef4444;
        }
        
        .test-mode-selector label {
            background-color: #374151;
            border-color: #4b5563;
            color: #f9fafb;
        }
        
        .test-mode-selector input[type="radio"]:checked + label {
            background-color: #3b82f6;
            color: white;
            border-color: #3b82f6;
        }
        
        .test-mode-selector label:hover {
            border-color: #3b82f6;
            background-color: #4b5563;
        }
    }
</style>
@endpush

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // 權限測試頁面初始化
        console.log('Permission test page loaded');
        
        // 監聽測試結果事件
        document.addEventListener('livewire:init', () => {
            Livewire.on('permission-tested', (event) => {
                const data = event[0];
                
                // 顯示測試完成通知
                if (typeof window.showNotification === 'function') {
                    const message = data.result 
                        ? `✓ ${data.subject} 擁有權限 "${data.permission}"`
                        : `✗ ${data.subject} 沒有權限 "${data.permission}"`;
                    
                    window.showNotification(message, data.result ? 'success' : 'warning');
                }
                
                // 滾動到結果區域
                const resultsSection = document.querySelector('[data-section="test-results"]');
                if (resultsSection) {
                    resultsSection.scrollIntoView({ 
                        behavior: 'smooth', 
                        block: 'start' 
                    });
                }
            });
            
            Livewire.on('results-cleared', () => {
                if (typeof window.showNotification === 'function') {
                    window.showNotification('測試結果已清除', 'info');
                }
            });
        });
        
        // 鍵盤快捷鍵支援
        document.addEventListener('keydown', function(e) {
            // Ctrl/Cmd + Enter 執行測試
            if ((e.ctrlKey || e.metaKey) && e.key === 'Enter') {
                e.preventDefault();
                const testButton = document.querySelector('[wire\\:click*="test"]');
                if (testButton && !testButton.disabled) {
                    testButton.click();
                }
            }
            
            // Escape 清除結果
            if (e.key === 'Escape') {
                const clearButton = document.querySelector('[wire\\:click="clearResults"]');
                if (clearButton) {
                    clearButton.click();
                }
            }
        });
        
        // 表單自動完成增強
        const userSelect = document.getElementById('selectedUserId');
        const roleSelect = document.getElementById('selectedRoleId');
        const permissionSelect = document.getElementById('permissionToTest');
        
        // 為選擇器添加搜尋功能（如果需要）
        [userSelect, roleSelect, permissionSelect].forEach(select => {
            if (select) {
                select.addEventListener('focus', function() {
                    this.size = Math.min(this.options.length, 10);
                });
                
                select.addEventListener('blur', function() {
                    this.size = 1;
                });
            }
        });
    });
</script>
@endpush