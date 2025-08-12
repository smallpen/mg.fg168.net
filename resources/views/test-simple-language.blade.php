<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>簡單語言測試</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="bg-gray-100 p-8">
    <div class="max-w-2xl mx-auto bg-white p-6 rounded-lg shadow">
        <h1 class="text-2xl font-bold mb-4">語言切換測試</h1>
        
        <div class="mb-4">
            <p><strong>當前語言：</strong> {{ app()->getLocale() }}</p>
            <p><strong>Session 語言：</strong> {{ session('locale', '未設定') }}</p>
        </div>
        
        <div class="mb-4">
            <h2 class="text-lg font-semibold mb-2">手動切換語言：</h2>
            <div class="space-x-2">
                <a href="?locale=zh_TW" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">正體中文</a>
                <a href="?locale=en" class="bg-green-500 text-white px-4 py-2 rounded hover:bg-green-600">English</a>
            </div>
        </div>
        
        <div class="mb-4">
            <h2 class="text-lg font-semibold mb-2">Livewire 語言選擇器：</h2>
            <livewire:admin.language-selector />
        </div>
        
        <div class="mt-6">
            <a href="{{ route('admin.dashboard') }}" class="text-blue-500 hover:underline">返回管理後台</a>
        </div>
    </div>
    
    @livewireScripts
</body>
</html>