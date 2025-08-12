<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>語言選擇器測試</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="bg-gray-100 p-8">
    <div class="max-w-4xl mx-auto">
        <h1 class="text-3xl font-bold mb-8">語言選擇器測試頁面</h1>
        
        <div class="bg-white p-6 rounded-lg shadow-lg mb-8">
            <h2 class="text-xl font-semibold mb-4">語言選擇器元件</h2>
            <div class="flex items-center space-x-4">
                <span>語言選擇器：</span>
                <livewire:admin.language-selector />
            </div>
        </div>
        
        <div class="bg-white p-6 rounded-lg shadow-lg mb-8">
            <h2 class="text-xl font-semibold mb-4">當前語言資訊</h2>
            <ul class="space-y-2">
                <li><strong>當前語言：</strong> {{ app()->getLocale() }}</li>
                <li><strong>使用者語言偏好：</strong> {{ auth()->user()->locale ?? '未設定' }}</li>
                <li><strong>Session 語言：</strong> {{ session('locale', '未設定') }}</li>
            </ul>
        </div>
        
        <div class="bg-white p-6 rounded-lg shadow-lg">
            <h2 class="text-xl font-semibold mb-4">測試翻譯</h2>
            <ul class="space-y-2">
                <li><strong>layout.language.current：</strong> {{ __('layout.language.current') }}</li>
                <li><strong>layout.language.switched：</strong> {{ __('layout.language.switched', ['language' => '測試']) }}</li>
                <li><strong>admin.language.title：</strong> {{ __('admin.language.title') }}</li>
            </ul>
        </div>
        
        <div class="mt-8">
            <a href="{{ route('admin.dashboard') }}" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">
                返回管理後台
            </a>
        </div>
    </div>
    
    @livewireScripts
</body>
</html>