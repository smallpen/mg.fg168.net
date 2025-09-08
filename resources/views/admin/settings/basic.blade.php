@extends('layouts.admin')

@section('title', '基本設定')

@section('content')
<div class="space-y-6">
    
    <!-- 頁面標題和描述 -->
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                基本設定
            </h1>
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                配置應用程式的基本資訊和系統參數
            </p>
        </div>
        
        <div class="flex space-x-3">
            @can('settings.backup')
                <button type="button" 
                        onclick="exportBasicSettings(event)"
                        class="inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path>
                    </svg>
                    匯出設定
                </button>
                <button type="button" 
                        onclick="openBackupModal()"
                        class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3-3m0 0l-3 3m3-3v12"></path>
                    </svg>
                    建立備份
                </button>
            @endcan
        </div>
    </div>

    <!-- 基本設定表單 -->
    <div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg">
        <livewire:admin.settings.basic-settings />
    </div>

</div>

<!-- 建立備份模態對話框 -->
<div id="backupModal" class="fixed inset-0 z-50 overflow-y-auto hidden" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <!-- 背景遮罩 -->
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true" onclick="closeBackupModal()"></div>

        <!-- 模態對話框 -->
        <div class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
            <div class="bg-white dark:bg-gray-800 px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                <div class="sm:flex sm:items-start">
                    <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-blue-100 dark:bg-blue-900 sm:mx-0 sm:h-10 sm:w-10">
                        <svg class="h-6 w-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3-3m0 0l-3 3m3-3v12"></path>
                        </svg>
                    </div>
                    <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                        <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white" id="modal-title">
                            建立基本設定備份
                        </h3>
                        <div class="mt-4">
                            <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">
                                請輸入備份名稱和描述，系統將為您建立基本設定的備份。
                            </p>
                            
                            <!-- 備份名稱 -->
                            <div class="mb-4">
                                <label for="backupName" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    備份名稱 <span class="text-red-500">*</span>
                                </label>
                                <input type="text" 
                                       id="backupName" 
                                       class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:text-white"
                                       placeholder="請輸入備份名稱">
                            </div>
                            
                            <!-- 備份描述 -->
                            <div class="mb-4">
                                <label for="backupDescription" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    備份描述
                                </label>
                                <textarea id="backupDescription" 
                                          rows="3"
                                          class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:text-white"
                                          placeholder="請輸入備份描述（可選）"></textarea>
                            </div>
                            
                            <!-- 備份內容說明 -->
                            <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-3">
                                <h4 class="text-sm font-medium text-blue-800 dark:text-blue-200 mb-2">
                                    <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                    將備份的設定項目
                                </h4>
                                <ul class="text-sm text-blue-700 dark:text-blue-300 space-y-1">
                                    <li>• 應用程式名稱</li>
                                    <li>• 應用程式描述</li>
                                    <li>• 系統時區</li>
                                    <li>• 預設語言</li>
                                    <li>• 日期格式</li>
                                    <li>• 時間格式</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="bg-gray-50 dark:bg-gray-700 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                <button type="button" 
                        onclick="confirmCreateBackup()"
                        class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-primary-600 text-base font-medium text-white hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 sm:ml-3 sm:w-auto sm:text-sm">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                    建立備份
                </button>
                <button type="button" 
                        onclick="closeBackupModal()"
                        class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 dark:border-gray-600 shadow-sm px-4 py-2 bg-white dark:bg-gray-700 text-base font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                    取消
                </button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
/**
 * 匯出基本設定
 */
async function exportBasicSettings(event) {
    const button = event.target.closest('button');
    const originalText = button.innerHTML;
    
    try {
        // 顯示載入狀態
        button.disabled = true;
        button.innerHTML = `
            <svg class="w-4 h-4 mr-2 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
            </svg>
            匯出中...
        `;

        // 使用隱藏的 iframe 來觸發下載，避免頁面導航
        const iframe = document.createElement('iframe');
        iframe.style.display = 'none';
        document.body.appendChild(iframe);
        
        // 建立表單並在 iframe 中提交
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '{{ route("admin.settings.api.export") }}';
        form.target = iframe.name = 'download_frame_' + Date.now();
        
        // 添加 CSRF token
        const csrfInput = document.createElement('input');
        csrfInput.type = 'hidden';
        csrfInput.name = '_token';
        csrfInput.value = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        form.appendChild(csrfInput);
        
        // 添加分類參數
        const categoriesInput = document.createElement('input');
        categoriesInput.type = 'hidden';
        categoriesInput.name = 'categories[]';
        categoriesInput.value = 'basic';
        form.appendChild(categoriesInput);
        
        // 添加下載標記
        const downloadInput = document.createElement('input');
        downloadInput.type = 'hidden';
        downloadInput.name = 'download';
        downloadInput.value = '1';
        form.appendChild(downloadInput);
        
        document.body.appendChild(form);
        form.submit();
        
        // 清理
        setTimeout(() => {
            if (document.body.contains(form)) {
                document.body.removeChild(form);
            }
            if (document.body.contains(iframe)) {
                document.body.removeChild(iframe);
            }
        }, 5000);

        // 顯示成功訊息
        showToast('success', '基本設定匯出成功');

    } catch (error) {
        console.error('匯出基本設定失敗:', error);
        showToast('error', '匯出失敗：' + error.message);
    } finally {
        // 恢復按鈕狀態
        setTimeout(() => {
            const currentButton = document.querySelector('button[onclick="exportBasicSettings(event)"]');
            if (currentButton) {
                currentButton.disabled = false;
                currentButton.innerHTML = originalText;
            }
        }, 100);
    }
}

/**
 * 開啟備份模態對話框
 */
function openBackupModal() {
    // 設定預設備份名稱
    const now = new Date();
    const defaultName = '基本設定備份_' + now.toLocaleString('zh-TW');
    document.getElementById('backupName').value = defaultName;
    document.getElementById('backupDescription').value = '從基本設定頁面建立的備份';
    
    // 顯示模態對話框
    document.getElementById('backupModal').classList.remove('hidden');
    document.body.classList.add('overflow-hidden');
    
    // 聚焦到備份名稱輸入框
    setTimeout(() => {
        document.getElementById('backupName').focus();
        document.getElementById('backupName').select();
    }, 100);
}

/**
 * 關閉備份模態對話框
 */
function closeBackupModal() {
    document.getElementById('backupModal').classList.add('hidden');
    document.body.classList.remove('overflow-hidden');
}

/**
 * 確認建立備份
 */
async function confirmCreateBackup() {
    const backupName = document.getElementById('backupName').value.trim();
    const backupDescription = document.getElementById('backupDescription').value.trim();
    
    // 驗證備份名稱
    if (!backupName) {
        showToast('error', '請輸入備份名稱');
        document.getElementById('backupName').focus();
        return;
    }
    
    const confirmButton = document.querySelector('#backupModal button[onclick="confirmCreateBackup()"]');
    const originalText = confirmButton.innerHTML;
    
    try {
        // 顯示載入狀態
        confirmButton.disabled = true;
        confirmButton.innerHTML = `
            <svg class="w-4 h-4 mr-2 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
            </svg>
            建立中...
        `;

        // 調用建立備份 API
        const response = await fetch('/admin/settings/api/create-backup', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                name: backupName,
                description: backupDescription || '從基本設定頁面建立的備份',
                categories: ['basic']
            })
        });

        const result = await response.json();

        if (result.success) {
            // 關閉模態對話框
            closeBackupModal();
            
            // 顯示成功訊息
            showToast('success', '基本設定備份建立成功');
            
            // 顯示成功後的選項對話框
            showBackupSuccessModal(result.backup);
        } else {
            throw new Error(result.message || '建立備份失敗');
        }

    } catch (error) {
        console.error('建立基本設定備份失敗:', error);
        showToast('error', '建立備份失敗：' + error.message);
    } finally {
        // 恢復按鈕狀態
        confirmButton.disabled = false;
        confirmButton.innerHTML = originalText;
    }
}

/**
 * 顯示備份成功後的選項對話框
 */
function showBackupSuccessModal(backup) {
    const modal = document.createElement('div');
    modal.className = 'fixed inset-0 z-50 overflow-y-auto';
    modal.innerHTML = `
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"></div>
            <div class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-md sm:w-full">
                <div class="bg-white dark:bg-gray-800 px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <div class="sm:flex sm:items-start">
                        <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-green-100 dark:bg-green-900 sm:mx-0 sm:h-10 sm:w-10">
                            <svg class="h-6 w-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                        </div>
                        <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                            <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white">
                                備份建立成功！
                            </h3>
                            <div class="mt-2">
                                <p class="text-sm text-gray-500 dark:text-gray-400">
                                    備份「${backup.name}」已成功建立，包含 ${backup.settings_count} 個設定項目。
                                </p>
                                <p class="text-sm text-gray-500 dark:text-gray-400 mt-2">
                                    您想要前往備份管理頁面查看嗎？
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="bg-gray-50 dark:bg-gray-700 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                    <button type="button" 
                            onclick="window.location.href='{{ route("admin.settings.backups") }}'"
                            class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-primary-600 text-base font-medium text-white hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 sm:ml-3 sm:w-auto sm:text-sm">
                        前往備份管理
                    </button>
                    <button type="button" 
                            onclick="this.closest('.fixed').remove(); document.body.classList.remove('overflow-hidden')"
                            class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 dark:border-gray-600 shadow-sm px-4 py-2 bg-white dark:bg-gray-700 text-base font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                        稍後再說
                    </button>
                </div>
            </div>
        </div>
    `;
    
    document.body.appendChild(modal);
    document.body.classList.add('overflow-hidden');
}

/**
 * 顯示 Toast 訊息
 */
function showToast(type, message) {
    // 如果有 Livewire 元件，使用 Livewire 事件
    if (window.Livewire) {
        window.Livewire.dispatch('show-toast', {
            type: type,
            message: message
        });
    } else {
        // 否則使用簡單的 alert
        alert(message);
    }
}
</script>
@endpush
@endsection