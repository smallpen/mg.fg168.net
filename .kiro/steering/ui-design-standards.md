---
inclusion: always
---

# UI 設計標準規範

## 概述

本文件定義了管理後台所有頁面的統一 UI 設計標準，確保整個系統的視覺一致性和使用者體驗的統一性。

## 頁面標題標準

### 基本結構

所有管理後台頁面都必須遵循以下標準結構：

```html
@extends('layouts.admin')

@section('title', '頁面標題')

@section('content')
    <div class="space-y-6">
        <!-- 頁面標題 -->
        <div class="flex justify-between items-center">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                    頁面標題
                </h1>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                    頁面描述文字
                </p>
            </div>
            
            <!-- 可選：右側操作按鈕 -->
            @can('permission.name')
                <div class="flex space-x-3">
                    <button class="...">操作按鈕</button>
                </div>
            @endcan
        </div>

        <!-- 頁面內容 -->
        <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg">
            <!-- 內容區域 -->
        </div>
    </div>
@endsection
```

### 標題樣式規範

#### 主標題 (h1)
- **標籤**：`<h1>`
- **類別**：`text-2xl font-bold text-gray-900 dark:text-white`
- **用途**：頁面主標題

#### 副標題/描述
- **標籤**：`<p>`
- **類別**：`mt-1 text-sm text-gray-600 dark:text-gray-400`
- **用途**：頁面功能描述

#### 區塊標題 (h2)
- **標籤**：`<h2>`
- **類別**：`text-lg font-medium text-gray-900 dark:text-white`
- **用途**：頁面內區塊標題

#### 小節標題 (h3)
- **標籤**：`<h3>`
- **類別**：`text-base font-medium text-gray-900 dark:text-white`
- **用途**：區塊內小節標題

### 排版結構

#### 頁面容器
```html
<div class="space-y-6">
    <!-- 所有頁面內容 -->
</div>
```

#### 標題區域
```html
<div class="flex justify-between items-center">
    <div>
        <!-- 標題和描述 -->
    </div>
    <div class="flex space-x-3">
        <!-- 操作按鈕 -->
    </div>
</div>
```

#### 內容區域
```html
<div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg">
    <!-- 實際內容 -->
</div>
```

## Livewire 元件標準

### 元件結構

Livewire 元件不應包含頁面級標題，標題應在主視圖中定義：

```html
<!-- ❌ 錯誤：在 Livewire 元件中定義頁面標題 -->
<div class="space-y-6">
    <div class="flex justify-between items-center">
        <h1 class="text-2xl font-bold">標題</h1>
    </div>
    <!-- 內容 -->
</div>

<!-- ✅ 正確：Livewire 元件只包含功能內容 -->
<div class="space-y-6">
    <!-- 功能控制項 -->
    <div class="flex justify-end">
        <div class="flex items-center space-x-3">
            <!-- 操作按鈕 -->
        </div>
    </div>
    
    <!-- 主要內容 -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
        <!-- 元件內容 -->
    </div>
</div>
```

### 控制項區域

如果 Livewire 元件需要控制項（如篩選器、按鈕等），使用以下結構：

```html
<!-- 右對齊控制項 -->
<div class="flex justify-end">
    <div class="flex items-center space-x-3">
        <!-- 控制項 -->
    </div>
</div>

<!-- 左右分佈控制項 -->
<div class="flex justify-between items-center">
    <div class="flex items-center space-x-3">
        <!-- 左側控制項 -->
    </div>
    <div class="flex items-center space-x-3">
        <!-- 右側控制項 -->
    </div>
</div>
```

## 按鈕標準

### 主要按鈕
```html
<button class="inline-flex items-center px-4 py-2 bg-primary-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-primary-700 focus:bg-primary-700 active:bg-primary-900 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 transition ease-in-out duration-150">
    <svg class="w-4 h-4 mr-2"><!-- 圖示 --></svg>
    按鈕文字
</button>
```

### 次要按鈕
```html
<button class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 disabled:opacity-25 transition ease-in-out duration-150">
    按鈕文字
</button>
```

### 危險按鈕
```html
<button class="inline-flex items-center px-4 py-2 bg-red-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-700 focus:bg-red-700 active:bg-red-900 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 transition ease-in-out duration-150">
    刪除
</button>
```

## 卡片和容器標準

### 基本卡片
```html
<div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg">
    <div class="px-4 py-5 sm:p-6">
        <!-- 卡片內容 -->
    </div>
</div>
```

### 帶標題的卡片
```html
<div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg">
    <div class="px-4 py-5 sm:px-6 border-b border-gray-200 dark:border-gray-700">
        <h3 class="text-lg font-medium text-gray-900 dark:text-white">卡片標題</h3>
        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">卡片描述</p>
    </div>
    <div class="px-4 py-5 sm:p-6">
        <!-- 卡片內容 -->
    </div>
</div>
```

## 表格標準

### 基本表格結構
```html
<div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg overflow-hidden">
    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
        <thead class="bg-gray-50 dark:bg-gray-900">
            <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                    欄位標題
                </th>
            </tr>
        </thead>
        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                    資料內容
                </td>
            </tr>
        </tbody>
    </table>
</div>
```

## 表單標準

### 基本表單結構
```html
<div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg">
    <div class="px-4 py-5 sm:p-6">
        <form>
            <div class="grid grid-cols-1 gap-6">
                <!-- 表單欄位 -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                        欄位標籤
                    </label>
                    <input type="text" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                </div>
            </div>
            
            <div class="mt-6 flex justify-end space-x-3">
                <button type="button" class="...">取消</button>
                <button type="submit" class="...">儲存</button>
            </div>
        </form>
    </div>
</div>
```

## 間距標準

### 頁面級間距
- 頁面容器：`space-y-6`
- 區塊間距：`mb-6` 或 `mt-6`

### 元件級間距
- 元件內容：`space-y-4`
- 小元件間距：`space-x-3` 或 `space-y-3`

### 內邊距標準
- 卡片內邊距：`px-4 py-5 sm:p-6`
- 表格儲存格：`px-6 py-4`
- 按鈕內邊距：`px-4 py-2`

## 響應式設計

### 斷點使用
- `sm:` - 640px 以上
- `md:` - 768px 以上  
- `lg:` - 1024px 以上
- `xl:` - 1280px 以上

### 常用響應式模式
```html
<!-- 手機垂直，桌面水平 -->
<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between">

<!-- 響應式網格 -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">

<!-- 響應式間距 -->
<div class="mt-4 sm:mt-0">
```

## 深色模式支援

所有元件都必須支援深色模式，使用以下類別：

### 背景色
- 主背景：`bg-white dark:bg-gray-800`
- 次要背景：`bg-gray-50 dark:bg-gray-900`
- 邊框：`border-gray-200 dark:border-gray-700`

### 文字色
- 主文字：`text-gray-900 dark:text-white`
- 次要文字：`text-gray-600 dark:text-gray-400`
- 輔助文字：`text-gray-500 dark:text-gray-500`

## 開發檢查清單

在開發新功能時，請確認以下項目：

- [ ] 頁面標題使用 `h1` + `text-2xl font-bold text-gray-900 dark:text-white`
- [ ] 頁面描述使用 `text-sm text-gray-600 dark:text-gray-400`
- [ ] 使用 `flex justify-between items-center` 排版標題區域
- [ ] 頁面容器使用 `space-y-6`
- [ ] Livewire 元件不包含頁面級標題
- [ ] 所有元件支援深色模式
- [ ] 使用標準的按鈕樣式
- [ ] 表格和表單遵循標準結構
- [ ] 響應式設計適當應用
- [ ] 間距使用標準值

## 範例頁面

參考以下頁面作為標準實作範例：
- `resources/views/admin/users/index.blade.php`
- `resources/views/admin/activities/index.blade.php`
- `resources/views/admin/roles/index.blade.php`

遵循這些標準可確保整個管理後台的視覺一致性和良好的使用者體驗。