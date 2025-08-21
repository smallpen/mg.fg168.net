{{-- 角色本地化使用範例 --}}
@extends('layouts.admin')

@section('title', __('role_management.title'))
@section('page-title', __('role_management.title'))

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6">
        <h1 class="text-2xl font-bold mb-6 text-gray-900 dark:text-white">
            {{ __('role_management.title') }} - 使用範例
        </h1>

        {{-- 使用 Blade 指令 --}}
        <div class="mb-8">
            <h2 class="text-lg font-semibold mb-4 text-gray-800 dark:text-gray-200">使用 Blade 指令</h2>
            <div class="space-y-2">
                <p><strong>角色名稱:</strong> @roleDisplayName('admin')</p>
                <p><strong>角色描述:</strong> @roleDescription('admin')</p>
                <p><strong>權限名稱:</strong> @permissionDisplayName('roles.view')</p>
                <p><strong>權限描述:</strong> @permissionDescription('roles.view')</p>
                <p><strong>模組名稱:</strong> @moduleDisplayName('roles')</p>
            </div>
        </div>

        {{-- 使用 Helper 類別 --}}
        <div class="mb-8">
            <h2 class="text-lg font-semibold mb-4 text-gray-800 dark:text-gray-200">使用 Helper 類別</h2>
            <div class="space-y-2">
                <p><strong>角色名稱:</strong> {{ App\Helpers\RoleLocalizationHelper::getRoleDisplayName('admin') }}</p>
                <p><strong>權限名稱:</strong> {{ App\Helpers\RoleLocalizationHelper::getPermissionDisplayName('roles.view') }}</p>
                <p><strong>模組名稱:</strong> {{ App\Helpers\RoleLocalizationHelper::getModuleDisplayName('roles') }}</p>
                <p><strong>成功訊息:</strong> {{ App\Helpers\RoleLocalizationHelper::getSuccessMessage('created', ['name' => '測試角色']) }}</p>
                <p><strong>錯誤訊息:</strong> {{ App\Helpers\RoleLocalizationHelper::getErrorMessage('crud.role_not_found') }}</p>
            </div>
        </div>

        {{-- 使用 Blade 元件 --}}
        <div class="mb-8">
            <h2 class="text-lg font-semibold mb-4 text-gray-800 dark:text-gray-200">使用 Blade 元件</h2>
            <div class="space-y-2">
                <p><strong>角色:</strong> <x-role-localization type="role" name="admin" :show-description="true" /></p>
                <p><strong>權限:</strong> <x-role-localization type="permission" name="roles.view" :show-description="true" /></p>
                <p><strong>模組:</strong> <x-role-localization type="module" name="roles" /></p>
            </div>
        </div>

        {{-- 所有可用的翻譯 --}}
        <div class="mb-8">
            <h2 class="text-lg font-semibold mb-4 text-gray-800 dark:text-gray-200">所有可用的翻譯</h2>
            
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                {{-- 角色名稱 --}}
                <div>
                    <h3 class="font-medium mb-2 text-gray-700 dark:text-gray-300">角色名稱</h3>
                    <ul class="text-sm space-y-1">
                        @foreach(App\Helpers\RoleLocalizationHelper::getAllRoleNames() as $key => $name)
                            <li><code>{{ $key }}</code>: {{ $name }}</li>
                        @endforeach
                    </ul>
                </div>

                {{-- 權限名稱 --}}
                <div>
                    <h3 class="font-medium mb-2 text-gray-700 dark:text-gray-300">權限名稱</h3>
                    <ul class="text-sm space-y-1 max-h-64 overflow-y-auto">
                        @foreach(App\Helpers\RoleLocalizationHelper::getAllPermissionNames() as $key => $name)
                            <li><code>{{ $key }}</code>: {{ $name }}</li>
                        @endforeach
                    </ul>
                </div>

                {{-- 模組名稱 --}}
                <div>
                    <h3 class="font-medium mb-2 text-gray-700 dark:text-gray-300">模組名稱</h3>
                    <ul class="text-sm space-y-1">
                        @foreach(App\Helpers\RoleLocalizationHelper::getAllModuleNames() as $key => $name)
                            <li><code>{{ $key }}</code>: {{ $name }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>

        {{-- 語言切換 --}}
        <div class="mb-8">
            <h2 class="text-lg font-semibold mb-4 text-gray-800 dark:text-gray-200">語言切換</h2>
            <div class="flex space-x-4">
                <a href="?locale=en" class="px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600">
                    English
                </a>
                <a href="?locale=zh_TW" class="px-4 py-2 bg-green-500 text-white rounded hover:bg-green-600">
                    正體中文
                </a>
            </div>
            <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                目前語言: {{ App::getLocale() }}
                @if(App\Helpers\RoleLocalizationHelper::isChineseLocale())
                    (中文環境)
                @else
                    (英文環境)
                @endif
            </p>
        </div>

        {{-- 日期格式化 --}}
        <div class="mb-8">
            <h2 class="text-lg font-semibold mb-4 text-gray-800 dark:text-gray-200">日期格式化</h2>
            <p><strong>目前時間:</strong> @localizedDate(now())</p>
            <p><strong>格式化時間:</strong> {{ App\Helpers\RoleLocalizationHelper::formatDate(now()) }}</p>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
// 載入前端本地化支援
import('/js/role-localization.js').then(module => {
    const RoleLocalization = module.default;
    const roleLocalization = new RoleLocalization();
    
    // 測試前端本地化功能
    console.log('Permission Display Name:', roleLocalization.getPermissionDisplayName('roles.view'));
    console.log('Role Display Name:', roleLocalization.getRoleDisplayName('admin'));
    console.log('Module Display Name:', roleLocalization.getModuleDisplayName('roles'));
});
</script>
@endpush