<div class="screen-reader-support">
    <!-- ARIA 即時區域 - 用於螢幕閱讀器宣告 -->
    <div id="aria-live-region"
         aria-live="{{ $this->ariaLive }}"
         aria-atomic="{{ $this->ariaAtomic }}"
         class="sr-only">
        @foreach($announcements as $announcement)
            <div id="{{ $announcement['id'] }}"
                 aria-live="{{ $announcement['priority'] }}">
                {{ $announcement['message'] }}
            </div>
        @endforeach
    </div>

    <!-- 頁面地標說明 - 僅供螢幕閱讀器 -->
    <div class="sr-only">
        <h1>頁面結構說明</h1>
        <p>此頁面包含以下主要區域：</p>
        <ul>
            @foreach($landmarks as $landmark => $description)
                <li>{{ $description }}（{{ $landmark }}）</li>
            @endforeach
        </ul>
        <p>您可以使用螢幕閱讀器的地標導航功能快速移動到這些區域。</p>
    </div>

    <!-- 當前頁面資訊 -->
    @if($currentRegion)
        <div class="sr-only" aria-live="polite">
            目前位於：{{ $landmarks[$currentRegion] ?? $currentRegion }}
        </div>
    @endif

    <!-- 鍵盤導航說明 -->
    <div class="sr-only">
        <h2>鍵盤導航說明</h2>
        <p>您可以使用以下鍵盤快捷鍵：</p>
        <ul>
            <li>Tab 鍵：移動到下一個可聚焦元素</li>
            <li>Shift + Tab：移動到上一個可聚焦元素</li>
            <li>Enter 或 Space：啟動按鈕或連結</li>
            <li>Escape：關閉對話框或選單</li>
            <li>方向鍵：在選單或表格中導航</li>
            <li>Alt + M：開啟或關閉主選單</li>
            <li>Alt + S：聚焦搜尋框</li>
            <li>Alt + N：開啟通知中心</li>
            <li>Alt + U：開啟使用者選單</li>
        </ul>
    </div>

    <!-- 表單說明 -->
    <div class="sr-only">
        <h2>表單填寫說明</h2>
        <p>在填寫表單時：</p>
        <ul>
            <li>必填欄位會標示為「必填」</li>
            <li>錯誤訊息會在欄位後方顯示</li>
            <li>表單提交後會宣告結果</li>
            <li>使用 Tab 鍵在欄位間移動</li>
        </ul>
    </div>

    <!-- 表格說明 -->
    <div class="sr-only">
        <h2>表格導航說明</h2>
        <p>在瀏覽表格時：</p>
        <ul>
            <li>使用方向鍵在儲存格間移動</li>
            <li>表格標題會自動宣告</li>
            <li>排序功能可透過 Enter 鍵啟動</li>
            <li>篩選功能位於表格上方</li>
        </ul>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('livewire:init', () => {
    // 監聽螢幕閱讀器宣告事件
    Livewire.on('screen-reader-announce', (data) => {
        const announcement = data[0];
        announceToScreenReader(announcement.message, announcement.priority);
    });
    
    // 宣告函數
    function announceToScreenReader(message, priority = 'polite') {
        // 建立臨時的 ARIA 即時區域
        const liveRegion = document.createElement('div');
        liveRegion.setAttribute('aria-live', priority);
        liveRegion.setAttribute('aria-atomic', 'true');
        liveRegion.className = 'sr-only';
        liveRegion.textContent = message;
        
        document.body.appendChild(liveRegion);
        
        // 短暫延遲後移除，確保螢幕閱讀器有時間讀取
        setTimeout(() => {
            document.body.removeChild(liveRegion);
        }, 1000);
    }
    
    // 監聽頁面變更
    let lastUrl = location.href;
    new MutationObserver(() => {
        const url = location.href;
        if (url !== lastUrl) {
            lastUrl = url;
            
            // 宣告頁面變更
            const pageTitle = document.title;
            const breadcrumb = getBreadcrumbText();
            
            @this.call('announcePageChange', pageTitle, breadcrumb);
        }
    }).observe(document, { subtree: true, childList: true });
    
    // 監聽表單提交
    document.addEventListener('submit', (event) => {
        const form = event.target;
        const formName = form.getAttribute('aria-label') || form.id || '表單';
        
        announceToScreenReader(`正在提交${formName}`, 'assertive');
    });
    
    // 監聽表單驗證錯誤
    document.addEventListener('invalid', (event) => {
        const field = event.target;
        const fieldName = field.getAttribute('aria-label') || 
                         field.previousElementSibling?.textContent || 
                         field.name || '欄位';
        const errorMessage = field.validationMessage;
        
        announceToScreenReader(`${fieldName}：${errorMessage}`, 'assertive');
    });
    
    // 監聽模態框開啟
    const observer = new MutationObserver((mutations) => {
        mutations.forEach((mutation) => {
            mutation.addedNodes.forEach((node) => {
                if (node.nodeType === 1) { // Element node
                    // 檢查是否為模態框
                    if (node.matches('.modal, [role="dialog"]') || 
                        node.querySelector('.modal, [role="dialog"]')) {
                        const modal = node.matches('.modal, [role="dialog"]') ? 
                                    node : node.querySelector('.modal, [role="dialog"]');
                        const modalTitle = modal.querySelector('h1, h2, h3, [aria-labelledby]')?.textContent || '對話框';
                        
                        announceToScreenReader(`${modalTitle}已開啟`, 'assertive');
                    }
                    
                    // 檢查是否為通知
                    if (node.matches('.toast, .notification, .alert') ||
                        node.querySelector('.toast, .notification, .alert')) {
                        const notification = node.matches('.toast, .notification, .alert') ?
                                           node : node.querySelector('.toast, .notification, .alert');
                        const message = notification.textContent.trim();
                        
                        if (message) {
                            const priority = notification.classList.contains('error') || 
                                           notification.classList.contains('danger') ? 'assertive' : 'polite';
                            announceToScreenReader(message, priority);
                        }
                    }
                }
            });
        });
    });
    
    observer.observe(document.body, {
        childList: true,
        subtree: true
    });
    
    // 監聽載入狀態
    document.addEventListener('htmx:beforeRequest', () => {
        announceToScreenReader('正在載入內容', 'assertive');
    });
    
    document.addEventListener('htmx:afterRequest', () => {
        announceToScreenReader('內容載入完成', 'polite');
    });
    
    // 監聽 Livewire 載入狀態
    document.addEventListener('livewire:load', () => {
        announceToScreenReader('頁面載入完成', 'polite');
    });
    
    document.addEventListener('livewire:update', () => {
        announceToScreenReader('內容已更新', 'polite');
    });
    
    // 輔助函數：取得麵包屑文字
    function getBreadcrumbText() {
        const breadcrumb = document.querySelector('.breadcrumb, [aria-label*="breadcrumb"], nav[aria-label*="麵包屑"]');
        if (breadcrumb) {
            return breadcrumb.textContent.replace(/\s+/g, ' ').trim();
        }
        return '';
    }
    
    // 輔助函數：宣告表格資訊
    function announceTableInfo(table) {
        const rows = table.querySelectorAll('tbody tr').length;
        const cols = table.querySelectorAll('thead th').length;
        const caption = table.querySelector('caption')?.textContent || '資料表格';
        
        announceToScreenReader(`${caption}，包含 ${rows} 列 ${cols} 欄`, 'polite');
    }
    
    // 監聽表格焦點
    document.addEventListener('focusin', (event) => {
        const table = event.target.closest('table');
        if (table && !table.hasAttribute('data-announced')) {
            announceTableInfo(table);
            table.setAttribute('data-announced', 'true');
        }
    });
    
    // 提供全域宣告函數
    window.announceToScreenReader = announceToScreenReader;
});
</script>
@endpush

@push('styles')
<style>
/* 螢幕閱讀器專用樣式 */
.sr-only {
    position: absolute;
    width: 1px;
    height: 1px;
    padding: 0;
    margin: -1px;
    overflow: hidden;
    clip: rect(0, 0, 0, 0);
    white-space: nowrap;
    border: 0;
}

/* 當元素獲得焦點時顯示（用於跳轉連結等） */
.sr-only:focus {
    position: static;
    width: auto;
    height: auto;
    padding: inherit;
    margin: inherit;
    overflow: visible;
    clip: auto;
    white-space: normal;
}
</style>
@endpush