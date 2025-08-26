@extends('layouts.admin')

@section('title', $title ?? '權限不足')

@push('styles')
<style>
    .error-container {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        min-height: 100vh;
        position: relative;
        overflow: hidden;
    }
    
    .error-container::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grain" width="100" height="100" patternUnits="userSpaceOnUse"><circle cx="25" cy="25" r="1" fill="white" opacity="0.1"/><circle cx="75" cy="75" r="1" fill="white" opacity="0.1"/><circle cx="50" cy="10" r="0.5" fill="white" opacity="0.05"/><circle cx="10" cy="60" r="0.5" fill="white" opacity="0.05"/><circle cx="90" cy="40" r="0.5" fill="white" opacity="0.05"/></pattern></defs><rect width="100" height="100" fill="url(%23grain)"/></svg>');
        opacity: 0.3;
    }
    
    .error-card {
        backdrop-filter: blur(10px);
        background: rgba(255, 255, 255, 0.95);
        border: 1px solid rgba(255, 255, 255, 0.2);
        box-shadow: 0 25px 50px rgba(0, 0, 0, 0.15);
        animation: slideInUp 0.6s ease-out;
    }
    
    @keyframes slideInUp {
        from {
            opacity: 0;
            transform: translateY(30px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    
    .error-icon {
        animation: pulse 2s infinite;
        filter: drop-shadow(0 4px 8px rgba(239, 68, 68, 0.3));
    }
    
    @keyframes pulse {
        0%, 100% {
            transform: scale(1);
        }
        50% {
            transform: scale(1.05);
        }
    }
    
    .btn-primary {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border: none;
        transition: all 0.3s ease;
        position: relative;
        overflow: hidden;
    }
    
    .btn-primary::before {
        content: '';
        position: absolute;
        top: 0;
        left: -100%;
        width: 100%;
        height: 100%;
        background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
        transition: left 0.5s;
    }
    
    .btn-primary:hover::before {
        left: 100%;
    }
    
    .btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 25px rgba(102, 126, 234, 0.4);
    }
    
    .btn-secondary {
        transition: all 0.3s ease;
        border: 2px solid #e5e7eb;
    }
    
    .btn-secondary:hover {
        border-color: #667eea;
        color: #667eea;
        transform: translateY(-1px);
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
    }
    
    .info-card {
        background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
        border: 1px solid #f59e0b;
        animation: fadeIn 0.8s ease-out 0.3s both;
    }
    
    @keyframes fadeIn {
        from {
            opacity: 0;
            transform: translateY(10px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    
    .floating-shapes {
        position: absolute;
        width: 100%;
        height: 100%;
        overflow: hidden;
        z-index: 1;
    }
    
    .shape {
        position: absolute;
        opacity: 0.1;
        animation: float 6s ease-in-out infinite;
    }
    
    .shape:nth-child(1) {
        top: 20%;
        left: 10%;
        animation-delay: 0s;
    }
    
    .shape:nth-child(2) {
        top: 60%;
        right: 10%;
        animation-delay: 2s;
    }
    
    .shape:nth-child(3) {
        bottom: 20%;
        left: 20%;
        animation-delay: 4s;
    }
    
    @keyframes float {
        0%, 100% {
            transform: translateY(0px) rotate(0deg);
        }
        50% {
            transform: translateY(-20px) rotate(180deg);
        }
    }
    
    .error-code {
        font-family: 'Courier New', monospace;
        font-weight: bold;
        color: #ef4444;
        text-shadow: 2px 2px 4px rgba(239, 68, 68, 0.2);
    }
</style>
@endpush

@section('content')
<div class="error-container">
    <!-- 浮動裝飾元素 -->
    <div class="floating-shapes">
        <div class="shape">
            <svg width="60" height="60" viewBox="0 0 60 60" fill="none" xmlns="http://www.w3.org/2000/svg">
                <circle cx="30" cy="30" r="30" fill="white"/>
            </svg>
        </div>
        <div class="shape">
            <svg width="40" height="40" viewBox="0 0 40 40" fill="none" xmlns="http://www.w3.org/2000/svg">
                <rect width="40" height="40" rx="8" fill="white"/>
            </svg>
        </div>
        <div class="shape">
            <svg width="50" height="50" viewBox="0 0 50 50" fill="none" xmlns="http://www.w3.org/2000/svg">
                <polygon points="25,5 45,40 5,40" fill="white"/>
            </svg>
        </div>
    </div>

    <div class="min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8 relative z-10">
        <div class="max-w-lg w-full">
            <div class="error-card rounded-2xl p-8 space-y-8">
                <!-- 錯誤圖示和代碼 -->
                <div class="text-center">
                    <div class="error-icon mx-auto h-32 w-32 text-red-500 mb-6">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" 
                                  d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z">
                            </path>
                        </svg>
                    </div>
                    
                    <div class="error-code text-6xl font-bold mb-4">403</div>
                    
                    <h1 class="text-3xl font-bold text-gray-900 mb-2">
                        {{ $title ?? '存取被拒絕' }}
                    </h1>
                    
                    <p class="text-lg text-gray-600 mb-4">
                        {{ $message ?? '您沒有權限存取此功能或資源' }}
                    </p>
                    
                    @if(isset($description))
                    <p class="text-sm text-gray-500 bg-gray-50 rounded-lg p-3">
                        {{ $description }}
                    </p>
                    @endif
                </div>

                <!-- 操作按鈕 -->
                <div class="space-y-4">
                    <a href="{{ $back_url ?? route('admin.dashboard') }}" 
                       class="btn-primary group relative w-full flex justify-center py-3 px-6 text-sm font-medium rounded-xl text-white focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-all duration-300">
                        <span class="absolute left-0 inset-y-0 flex items-center pl-4">
                            <svg class="h-5 w-5 text-white group-hover:text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
                            </svg>
                        </span>
                        返回儀表板
                    </a>
                    
                    <button onclick="history.back()" 
                            class="btn-secondary w-full flex justify-center py-3 px-6 text-sm font-medium rounded-xl text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        <svg class="h-5 w-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                        </svg>
                        返回上一頁
                    </button>
                </div>

                <!-- 使用者資訊 -->
                @auth
                <div class="info-card rounded-xl p-6">
                    <div class="flex items-start">
                        <div class="flex-shrink-0">
                            <svg class="h-6 w-6 text-amber-600" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                            </svg>
                        </div>
                        <div class="ml-4">
                            <h3 class="text-lg font-semibold text-amber-800 mb-2">
                                權限資訊
                            </h3>
                            <div class="space-y-2 text-sm text-amber-700">
                                <div class="flex items-center">
                                    <svg class="h-4 w-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                    </svg>
                                    <span class="font-medium">使用者：</span>
                                    <span class="ml-1">{{ auth()->user()->name ?? auth()->user()->username }}</span>
                                </div>
                                <div class="flex items-center">
                                    <svg class="h-4 w-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                                    </svg>
                                    <span class="font-medium">狀態：</span>
                                    <span class="ml-1">權限不足</span>
                                </div>
                                <p class="mt-3 text-amber-600 bg-amber-50 rounded-lg p-3">
                                    如需存取此功能，請聯繫系統管理員申請相應權限，或確認您的帳號是否具有足夠的存取權限。
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
                @endauth

                <!-- 幫助資訊 -->
                <div class="text-center border-t pt-6">
                    <p class="text-sm text-gray-500 mb-2">
                        需要協助？
                    </p>
                    <div class="flex justify-center space-x-4 text-xs text-gray-400">
                        <span>聯絡系統管理員</span>
                        <span>•</span>
                        <span>檢查權限設定</span>
                        <span>•</span>
                        <span>查看說明文件</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    // 頁面載入動畫
    document.addEventListener('DOMContentLoaded', function() {
        // 自動聚焦到主要按鈕
        setTimeout(function() {
            const primaryButton = document.querySelector('.btn-primary');
            if (primaryButton) {
                primaryButton.focus();
            }
        }, 600);
        
        // 添加鍵盤快捷鍵
        document.addEventListener('keydown', function(e) {
            // ESC 鍵返回上一頁
            if (e.key === 'Escape') {
                history.back();
            }
            // Enter 鍵返回儀表板
            if (e.key === 'Enter' && e.ctrlKey) {
                window.location.href = '{{ $back_url ?? route('admin.dashboard') }}';
            }
        });
    });
    
    // 添加視覺回饋
    document.querySelectorAll('button, a').forEach(element => {
        element.addEventListener('click', function() {
            this.style.transform = 'scale(0.95)';
            setTimeout(() => {
                this.style.transform = '';
            }, 150);
        });
    });
</script>
@endpush