{{-- 活動記錄列表骨架載入器 --}}
<div class="animate-pulse">
    {{-- 統計摘要骨架 --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-6 gap-4 mb-6">
        @for($i = 0; $i < 6; $i++)
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow rounded-lg">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="h-6 w-6 bg-gray-300 dark:bg-gray-600 rounded"></div>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <div class="h-4 bg-gray-300 dark:bg-gray-600 rounded w-16 mb-2"></div>
                            <div class="h-6 bg-gray-300 dark:bg-gray-600 rounded w-12"></div>
                        </div>
                    </div>
                </div>
            </div>
        @endfor
    </div>

    {{-- 搜尋和篩選骨架 --}}
    <div class="bg-white dark:bg-gray-800 shadow rounded-lg mb-6">
        <div class="px-4 py-5 sm:p-6">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between space-y-3 sm:space-y-0 sm:space-x-4">
                <div class="flex-1">
                    <div class="h-10 bg-gray-300 dark:bg-gray-600 rounded-md"></div>
                </div>
                <div class="flex items-center space-x-3">
                    <div class="h-10 w-20 bg-gray-300 dark:bg-gray-600 rounded-md"></div>
                    <div class="h-10 w-16 bg-gray-300 dark:bg-gray-600 rounded-md"></div>
                </div>
            </div>
        </div>
    </div>

    {{-- 表格骨架 --}}
    <div class="bg-white dark:bg-gray-800 shadow overflow-hidden sm:rounded-lg">
        {{-- 桌面版表格骨架 --}}
        <div class="hidden lg:block">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-900">
                        <tr>
                            @for($i = 0; $i < 9; $i++)
                                <th class="px-6 py-3">
                                    <div class="h-4 bg-gray-300 dark:bg-gray-600 rounded w-16"></div>
                                </th>
                            @endfor
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                        @for($row = 0; $row < 10; $row++)
                            <tr>
                                <td class="px-6 py-4">
                                    <div class="h-4 w-4 bg-gray-300 dark:bg-gray-600 rounded"></div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="space-y-1">
                                        <div class="h-4 bg-gray-300 dark:bg-gray-600 rounded w-16"></div>
                                        <div class="h-3 bg-gray-300 dark:bg-gray-600 rounded w-20"></div>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center">
                                        <div class="h-8 w-8 bg-gray-300 dark:bg-gray-600 rounded-full"></div>
                                        <div class="ml-3 space-y-1">
                                            <div class="h-4 bg-gray-300 dark:bg-gray-600 rounded w-20"></div>
                                            <div class="h-3 bg-gray-300 dark:bg-gray-600 rounded w-16"></div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center">
                                        <div class="h-4 w-4 bg-gray-300 dark:bg-gray-600 rounded mr-2"></div>
                                        <div class="h-4 bg-gray-300 dark:bg-gray-600 rounded w-16"></div>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="h-4 bg-gray-300 dark:bg-gray-600 rounded w-32"></div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="h-4 bg-gray-300 dark:bg-gray-600 rounded w-24"></div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="h-6 bg-gray-300 dark:bg-gray-600 rounded-full w-16"></div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="h-6 bg-gray-300 dark:bg-gray-600 rounded-full w-12"></div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="h-4 bg-gray-300 dark:bg-gray-600 rounded w-12"></div>
                                </td>
                            </tr>
                        @endfor
                    </tbody>
                </table>
            </div>
        </div>

        {{-- 行動版卡片骨架 --}}
        <div class="lg:hidden">
            @for($i = 0; $i < 5; $i++)
                <div class="border-b border-gray-200 dark:border-gray-700 p-4">
                    <div class="flex items-start space-x-3">
                        <div class="h-4 w-4 bg-gray-300 dark:bg-gray-600 rounded mt-1"></div>
                        <div class="h-10 w-10 bg-gray-300 dark:bg-gray-600 rounded-full"></div>
                        <div class="flex-1 space-y-2">
                            <div class="flex items-center space-x-2">
                                <div class="h-4 w-4 bg-gray-300 dark:bg-gray-600 rounded"></div>
                                <div class="h-4 bg-gray-300 dark:bg-gray-600 rounded w-20"></div>
                                <div class="h-5 bg-gray-300 dark:bg-gray-600 rounded-full w-12"></div>
                            </div>
                            <div class="h-4 bg-gray-300 dark:bg-gray-600 rounded w-full"></div>
                            <div class="flex flex-wrap gap-2">
                                <div class="h-4 bg-gray-300 dark:bg-gray-600 rounded w-16"></div>
                                <div class="h-4 bg-gray-300 dark:bg-gray-600 rounded w-20"></div>
                                <div class="h-4 bg-gray-300 dark:bg-gray-600 rounded w-24"></div>
                            </div>
                        </div>
                        <div class="h-4 bg-gray-300 dark:bg-gray-600 rounded w-8"></div>
                    </div>
                </div>
            @endfor
        </div>
    </div>
</div>