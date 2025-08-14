@extends('layouts.admin')

@section('title', '互動動畫系統展示')

@section('content')
<div class="container-admin">
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
            互動動畫系統展示
        </h1>
        <p class="text-gray-600 dark:text-gray-400 mt-2">
            展示管理後台的各種動畫效果和互動功能
        </p>
    </div>
    
    <!-- 動畫展示元件 -->
    <livewire:admin.components.animation-demo />
</div>
@endsection

@push('styles')
<style>
/* 額外的動畫樣式 */
.demo-container {
    min-height: 100vh;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

.demo-card {
    backdrop-filter: blur(10px);
    background: rgba(255, 255, 255, 0.1);
    border: 1px solid rgba(255, 255, 255, 0.2);
}

.demo-button {
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

.demo-button:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
}

/* 自訂動畫關鍵幀 */
@keyframes demoFadeIn {
    from {
        opacity: 0;
        transform: translateY(30px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.demo-fade-in {
    animation: demoFadeIn 0.6s ease-out;
}

/* 響應式調整 */
@media (max-width: 768px) {
    .demo-container {
        padding: 1rem;
    }
    
    .demo-card {
        margin-bottom: 1rem;
    }
}
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // 初始化動畫展示頁面
    console.log('動畫展示頁面已載入');
    
    // 添加頁面載入動畫
    const cards = document.querySelectorAll('.card');
    cards.forEach((card, index) => {
        card.style.animationDelay = `${index * 0.1}s`;
        card.classList.add('demo-fade-in');
    });
    
    // 監聽動畫事件
    document.addEventListener('animationend', function(e) {
        if (e.animationName === 'demoFadeIn') {
            e.target.style.animation = '';
        }
    });
    
    // 效能監控
    if ('performance' in window) {
        window.addEventListener('load', function() {
            setTimeout(function() {
                const perfData = performance.getEntriesByType('navigation')[0];
                console.log('頁面載入時間:', perfData.loadEventEnd - perfData.fetchStart, 'ms');
            }, 0);
        });
    }
});
</script>
@endpush