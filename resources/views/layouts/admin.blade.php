<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="{{ auth()->user()?->theme_preference === 'dark' ? 'dark' : '' }}" x-data="{ theme: '{{ auth()->user()?->theme_preference ?? 'light' }}' }">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Laravel Admin System') }} - @yield('title', __('admin.title'))</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet" />

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
    @stack('styles')
</head>
<body class="font-sans antialiased">
    
    <x-admin.layout.admin-layout>
        @yield('content')
    </x-admin.layout.admin-layout>

    @livewireScripts
    @stack('scripts')
    
    <!-- 主題初始化腳本 -->
    <script>
        // 頁面載入時初始化主題
        document.addEventListener('DOMContentLoaded', function() {
            // 從 localStorage 或使用者偏好設定取得主題
            const savedTheme = localStorage.getItem('theme') || '{{ auth()->user()?->theme_preference ?? 'light' }}';
            const htmlElement = document.documentElement;
            
            // 應用主題
            if (savedTheme === 'dark') {
                htmlElement.classList.add('dark');
            } else {
                htmlElement.classList.remove('dark');
            }
            
            // 同步 localStorage
            localStorage.setItem('theme', savedTheme);
        });
        
        // 監聽系統主題變更（可選功能）
        if (window.matchMedia) {
            const mediaQuery = window.matchMedia('(prefers-color-scheme: dark)');
            
            // 如果使用者沒有設定偏好，跟隨系統主題
            function handleSystemThemeChange(e) {
                const userTheme = localStorage.getItem('theme');
                if (!userTheme || userTheme === 'system') {
                    const htmlElement = document.documentElement;
                    if (e.matches) {
                        htmlElement.classList.add('dark');
                    } else {
                        htmlElement.classList.remove('dark');
                    }
                }
            }
            
            mediaQuery.addListener(handleSystemThemeChange);
        }
    </script>
</body>
</html>