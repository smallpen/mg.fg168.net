<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-gray-50 dark:bg-gray-900">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', '代理管理') - {{ config('app.name', 'Laravel') }}</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="h-full">
    <div class="min-h-full">
        <!-- Navigation -->
        <nav class="bg-white dark:bg-gray-800 shadow-sm border-b border-gray-200 dark:border-gray-700">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="flex h-16 justify-between">
                    <div class="flex">
                        <!-- Logo -->
                        <div class="flex flex-shrink-0 items-center">
                            <h1 class="text-xl font-bold text-gray-900 dark:text-white">
                                代理管理系統
                            </h1>
                        </div>

                        <!-- Navigation Links -->
                        <div class="hidden sm:ml-6 sm:flex sm:space-x-8">
                            <a href="{{ route('agent.dashboard.index') }}" 
                               class="@if(request()->routeIs('agent.dashboard.index')) border-primary-500 text-gray-900 dark:text-white @else border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300 @endif inline-flex items-center border-b-2 px-1 pt-1 text-sm font-medium">
                                儀表板
                            </a>
                            <a href="{{ route('agent.dashboard.organization') }}" 
                               class="@if(request()->routeIs('agent.dashboard.organization')) border-primary-500 text-gray-900 dark:text-white @else border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300 @endif inline-flex items-center border-b-2 px-1 pt-1 text-sm font-medium">
                                組織架構
                            </a>
                            <a href="{{ route('agent.dashboard.agents') }}" 
                               class="@if(request()->routeIs('agent.dashboard.agents*')) border-primary-500 text-gray-900 dark:text-white @else border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300 @endif inline-flex items-center border-b-2 px-1 pt-1 text-sm font-medium">
                                下層代理
                            </a>
                            <a href="{{ route('agent.dashboard.players') }}" 
                               class="@if(request()->routeIs('agent.dashboard.players*')) border-primary-500 text-gray-900 dark:text-white @else border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300 @endif inline-flex items-center border-b-2 px-1 pt-1 text-sm font-medium">
                                玩家管理
                            </a>
                            <a href="{{ route('agent.dashboard.points') }}" 
                               class="@if(request()->routeIs('agent.dashboard.points')) border-primary-500 text-gray-900 dark:text-white @else border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300 @endif inline-flex items-center border-b-2 px-1 pt-1 text-sm font-medium">
                                點數管理
                            </a>
                            <a href="{{ route('agent.dashboard.statistics') }}" 
                               class="@if(request()->routeIs('agent.dashboard.statistics')) border-primary-500 text-gray-900 dark:text-white @else border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300 @endif inline-flex items-center border-b-2 px-1 pt-1 text-sm font-medium">
                                統計報表
                            </a>
                        </div>
                    </div>

                    <!-- User Menu -->
                    <div class="hidden sm:ml-6 sm:flex sm:items-center">
                        <!-- Agent Info -->
                        <div class="mr-4 text-sm text-gray-700 dark:text-gray-300">
                            <span class="font-medium">{{ auth()->user()->agent->name }}</span>
                            <span class="text-gray-500 dark:text-gray-400">({{ auth()->user()->agent->account }})</span>
                        </div>

                        <!-- Profile dropdown -->
                        <div class="relative ml-3" x-data="{ open: false }">
                            <div>
                                <button @click="open = !open" type="button" class="flex max-w-xs items-center rounded-full bg-white text-sm focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 dark:bg-gray-800" id="user-menu-button" aria-expanded="false" aria-haspopup="true">
                                    <span class="sr-only">開啟使用者選單</span>
                                    <div class="h-8 w-8 rounded-full bg-primary-100 dark:bg-primary-900 flex items-center justify-center">
                                        <span class="text-sm font-medium text-primary-600 dark:text-primary-400">
                                            {{ substr(auth()->user()->name, 0, 1) }}
                                        </span>
                                    </div>
                                </button>
                            </div>

                            <div x-show="open" @click.away="open = false" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="transform opacity-0 scale-95" x-transition:enter-end="transform opacity-100 scale-100" x-transition:leave="transition ease-in duration-75" x-transition:leave-start="transform opacity-100 scale-100" x-transition:leave-end="transform opacity-0 scale-95" class="absolute right-0 z-10 mt-2 w-48 origin-top-right rounded-md bg-white py-1 shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none dark:bg-gray-700" role="menu" aria-orientation="vertical" aria-labelledby="user-menu-button" tabindex="-1">
                                <a href="{{ route('admin.dashboard') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-600" role="menuitem" tabindex="-1">
                                    返回管理後台
                                </a>
                                <form method="POST" action="{{ route('admin.logout') }}">
                                    @csrf
                                    <button type="submit" class="block w-full px-4 py-2 text-left text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-600" role="menuitem" tabindex="-1">
                                        登出
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- Mobile menu button -->
                    <div class="-mr-2 flex items-center sm:hidden">
                        <button type="button" class="inline-flex items-center justify-center rounded-md bg-white p-2 text-gray-400 hover:bg-gray-100 hover:text-gray-500 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 dark:bg-gray-800 dark:text-gray-500 dark:hover:bg-gray-700 dark:hover:text-gray-400" aria-controls="mobile-menu" aria-expanded="false">
                            <span class="sr-only">開啟主選單</span>
                            <svg class="block h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
        </nav>

        <!-- Page Content -->
        <main class="py-6">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                @yield('content')
            </div>
        </main>
    </div>

    <!-- Toast Notifications -->
    <div id="toast-container" class="fixed top-4 right-4 z-50 space-y-2"></div>

    @livewireScripts
    
    <script>
        // Toast notification handler
        document.addEventListener('livewire:init', () => {
            Livewire.on('show-toast', (event) => {
                showToast(event.type, event.message);
            });
        });

        function showToast(type, message) {
            const container = document.getElementById('toast-container');
            const toast = document.createElement('div');
            
            const bgColor = type === 'success' ? 'bg-green-500' : 'bg-red-500';
            
            toast.className = `${bgColor} text-white px-6 py-3 rounded-lg shadow-lg transform transition-all duration-300 translate-x-full opacity-0`;
            toast.textContent = message;
            
            container.appendChild(toast);
            
            // Animate in
            setTimeout(() => {
                toast.classList.remove('translate-x-full', 'opacity-0');
            }, 100);
            
            // Animate out and remove
            setTimeout(() => {
                toast.classList.add('translate-x-full', 'opacity-0');
                setTimeout(() => {
                    container.removeChild(toast);
                }, 300);
            }, 3000);
        }
    </script>
</body>
</html>