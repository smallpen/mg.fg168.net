@extends('layouts.admin')

@section('title', '玩家管理')

@section('content')
    <div class="space-y-6">
        {{-- 頁面標題 --}}
        <div class="flex justify-between items-center">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                    玩家管理
                </h1>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                    管理系統中的所有玩家，包含玩家資訊、隸屬代理和點數管理
                </p>
            </div>
        </div>

        {{-- 玩家列表元件 --}}
        <livewire:admin.channels.player-list />
    </div>
@endsection