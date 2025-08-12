@extends('layouts.admin')

@section('title', '儀表板')

@section('content')
    <livewire:admin.dashboard />
@endsection

@push('scripts')
<script>
    // 儀表板相關的 JavaScript
    document.addEventListener('DOMContentLoaded', function() {
        // 監聽 toast 事件
        window.addEventListener('toast', event => {
            const { type, message } = event.detail;
            
            // 這裡可以整合 toast 通知系統
            console.log(`${type}: ${message}`);
            
            // 如果有使用 toast 庫，可以在這裡調用
            // 例如：toast.success(message) 或 toast.error(message)
        });
        
        // 自動重新整理儀表板資料（每 5 分鐘）
        setInterval(() => {
            if (document.visibilityState === 'visible') {
                Livewire.dispatch('refresh-dashboard');
            }
        }, 300000); // 5 分鐘
        
        // 當頁面重新獲得焦點時重新整理資料
        document.addEventListener('visibilitychange', () => {
            if (document.visibilityState === 'visible') {
                Livewire.dispatch('refresh-dashboard');
            }
        });
    });
</script>
@endpush

@push('styles')
<style>
    /* 儀表板特定樣式 */
    .dashboard-container {
        animation: fadeIn 0.5s ease-in-out;
        padding: 1.5rem;
    }
    
    @keyframes fadeIn {
        from {
            opacity: 0;
            transform: translateY(20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    
    .stat-card {
        transform: scale(1);
        transition: all 0.2s ease;
    }
    
    .stat-card:hover {
        transform: translateY(-2px) scale(1.02);
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
    }
    
    .chart-area {
        background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
        border-radius: 0.5rem;
        padding: 1rem;
    }
    
    [data-theme="dark"] .chart-area {
        background: linear-gradient(135deg, #2d3748 0%, #4a5568 100%);
    }
    
    .quick-actions button {
        transition: transform 0.2s ease;
    }
    
    .quick-actions button:hover {
        transform: translateX(4px);
    }
    
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 1.5rem;
    }
    
    /* 響應式調整 */
    @media (max-width: 768px) {
        .dashboard-container {
            padding: 1rem;
        }
        
        .dashboard-header h1 {
            font-size: 1.5rem;
        }
        
        .stat-card {
            padding: 1rem;
        }
        
        .chart-container {
            padding: 1rem;
        }
        
        .stats-grid {
            grid-template-columns: 1fr;
            gap: 1rem;
        }
        
        .chart-area {
            height: 12rem;
        }
    }
</style>
@endpush