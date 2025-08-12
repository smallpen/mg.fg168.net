{{-- 焦點管理器使用範例 --}}
<div class="focus-example-container">
    
    {{-- 包含焦點管理器 --}}
    <livewire:admin.layout.focus-manager />
    
    {{-- 跳轉連結區域 --}}
    <div class="skip-links" style="position: absolute; top: -40px; left: 0; z-index: 1000;">
        <a href="#main-content" 
           class="skip-link"
           style="position: absolute; left: -10000px; top: auto; width: 1px; height: 1px; overflow: hidden;"
           onfocus="this.style.position='static'; this.style.width='auto'; this.style.height='auto'; this.style.left='auto';"
           onblur="this.style.position='absolute'; this.style.width='1px'; this.style.height='1px'; this.style.left='-10000px';"
           onclick="skipToElement('main-content')">
            跳轉到主要內容
        </a>
        <a href="#navigation" 
           class="skip-link"
           style="position: absolute; left: -10000px; top: auto; width: 1px; height: 1px; overflow: hidden;"
           onfocus="this.style.position='static'; this.style.width='auto'; this.style.height='auto'; this.style.left='auto';"
           onblur="this.style.position='absolute'; this.style.width='1px'; this.style.height='1px'; this.style.left='-10000px';"
           onclick="skipToElement('navigation')">
            跳轉到導航選單
        </a>
    </div>
    
    {{-- 導航選單 --}}
    <nav id="navigation" class="main-navigation">
        <h2>主要導航</h2>
        <ul class="nav-menu" role="menubar">
            <li role="none">
                <a href="#" role="menuitem" tabindex="0">首頁</a>
            </li>
            <li role="none">
                <a href="#" role="menuitem" tabindex="-1">使用者管理</a>
                <ul class="submenu" role="menu" style="display: none;">
                    <li role="none">
                        <a href="#" role="menuitem" tabindex="-1">使用者列表</a>
                    </li>
                    <li role="none">
                        <a href="#" role="menuitem" tabindex="-1">新增使用者</a>
                    </li>
                </ul>
            </li>
            <li role="none">
                <a href="#" role="menuitem" tabindex="-1">設定</a>
            </li>
        </ul>
    </nav>
    
    {{-- 主要內容區域 --}}
    <main id="main-content" class="main-content" tabindex="-1">
        <h1>焦點管理器範例頁面</h1>
        
        {{-- 表單範例 --}}
        <section class="form-section">
            <h2>表單範例</h2>
            <form>
                <div class="form-group">
                    <label for="username">使用者名稱：</label>
                    <input type="text" id="username" name="username" required>
                </div>
                
                <div class="form-group">
                    <label for="email">電子郵件：</label>
                    <input type="email" id="email" name="email" required>
                </div>
                
                <div class="form-group">
                    <label for="role">角色：</label>
                    <select id="role" name="role">
                        <option value="">請選擇角色</option>
                        <option value="admin">管理員</option>
                        <option value="user">一般使用者</option>
                    </select>
                </div>
                
                <div class="form-actions">
                    <button type="submit">儲存</button>
                    <button type="button" onclick="openConfirmModal()">刪除</button>
                    <button type="reset">重設</button>
                </div>
            </form>
        </section>
        
        {{-- 資料表格範例 --}}
        <section class="table-section">
            <h2>資料表格範例</h2>
            <table class="data-table" role="table">
                <thead>
                    <tr role="row">
                        <th role="columnheader" tabindex="0">ID</th>
                        <th role="columnheader" tabindex="-1">姓名</th>
                        <th role="columnheader" tabindex="-1">電子郵件</th>
                        <th role="columnheader" tabindex="-1">操作</th>
                    </tr>
                </thead>
                <tbody>
                    <tr role="row">
                        <td tabindex="-1">1</td>
                        <td tabindex="-1">張三</td>
                        <td tabindex="-1">zhang@example.com</td>
                        <td>
                            <button tabindex="-1">編輯</button>
                            <button tabindex="-1">刪除</button>
                        </td>
                    </tr>
                    <tr role="row">
                        <td tabindex="-1">2</td>
                        <td tabindex="-1">李四</td>
                        <td tabindex="-1">li@example.com</td>
                        <td>
                            <button tabindex="-1">編輯</button>
                            <button tabindex="-1">刪除</button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </section>
    </main>
    
    {{-- 確認模態框 --}}
    <div class="modal-overlay" id="confirm-overlay" style="display: none;">
        <div class="modal" id="confirm-modal" role="dialog" aria-labelledby="modal-title" aria-describedby="modal-description">
            <div class="modal-header">
                <h3 id="modal-title">確認刪除</h3>
                <button class="modal-close" onclick="closeConfirmModal()" aria-label="關閉對話框">&times;</button>
            </div>
            <div class="modal-body">
                <p id="modal-description">您確定要刪除這個項目嗎？此操作無法復原。</p>
            </div>
            <div class="modal-footer">
                <button id="confirm-delete" class="btn-danger">確認刪除</button>
                <button id="cancel-delete" onclick="closeConfirmModal()">取消</button>
            </div>
        </div>
    </div>
    
</div>

<style>
/* 基本樣式 */
.focus-example-container {
    font-family: Arial, sans-serif;
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
}

/* 跳轉連結樣式 */
.skip-link:focus {
    background: #000;
    color: #fff;
    padding: 8px 16px;
    text-decoration: none;
    border-radius: 4px;
}

/* 導航樣式 */
.main-navigation {
    background: #f8f9fa;
    padding: 15px;
    margin-bottom: 20px;
    border-radius: 4px;
}

.nav-menu {
    list-style: none;
    padding: 0;
    margin: 0;
    display: flex;
    gap: 20px;
}

.nav-menu li {
    position: relative;
}

.nav-menu a {
    display: block;
    padding: 10px 15px;
    text-decoration: none;
    color: #333;
    border-radius: 4px;
}

.nav-menu a:hover,
.nav-menu a:focus {
    background: #007bff;
    color: white;
    outline: 2px solid #0056b3;
}

.submenu {
    position: absolute;
    top: 100%;
    left: 0;
    background: white;
    border: 1px solid #ddd;
    border-radius: 4px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    min-width: 200px;
    z-index: 1000;
}

/* 表單樣式 */
.form-section {
    background: #f8f9fa;
    padding: 20px;
    margin-bottom: 20px;
    border-radius: 4px;
}

.form-group {
    margin-bottom: 15px;
}

.form-group label {
    display: block;
    margin-bottom: 5px;
    font-weight: bold;
}

.form-group input,
.form-group select {
    width: 100%;
    padding: 8px 12px;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 14px;
}

.form-group input:focus,
.form-group select:focus {
    outline: 2px solid #007bff;
    border-color: #007bff;
}

.form-actions {
    display: flex;
    gap: 10px;
    margin-top: 20px;
}

.form-actions button {
    padding: 10px 20px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-size: 14px;
}

.form-actions button[type="submit"] {
    background: #28a745;
    color: white;
}

.form-actions button[type="button"] {
    background: #dc3545;
    color: white;
}

.form-actions button[type="reset"] {
    background: #6c757d;
    color: white;
}

.form-actions button:focus {
    outline: 2px solid #333;
}

/* 表格樣式 */
.table-section {
    margin-bottom: 20px;
}

.data-table {
    width: 100%;
    border-collapse: collapse;
    background: white;
    border-radius: 4px;
    overflow: hidden;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.data-table th,
.data-table td {
    padding: 12px;
    text-align: left;
    border-bottom: 1px solid #ddd;
}

.data-table th {
    background: #f8f9fa;
    font-weight: bold;
}

.data-table th:focus,
.data-table td:focus {
    outline: 2px solid #007bff;
    background: #e3f2fd;
}

.data-table button {
    padding: 5px 10px;
    margin-right: 5px;
    border: 1px solid #ddd;
    border-radius: 3px;
    background: white;
    cursor: pointer;
    font-size: 12px;
}

.data-table button:hover,
.data-table button:focus {
    background: #007bff;
    color: white;
    outline: 1px solid #0056b3;
}

/* 模態框樣式 */
.modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.5);
    z-index: 1000;
    display: flex;
    align-items: center;
    justify-content: center;
}

.modal {
    background: white;
    border-radius: 8px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
    max-width: 500px;
    width: 90%;
    max-height: 90vh;
    overflow-y: auto;
}

.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 20px;
    border-bottom: 1px solid #ddd;
}

.modal-header h3 {
    margin: 0;
}

.modal-close {
    background: none;
    border: none;
    font-size: 24px;
    cursor: pointer;
    padding: 0;
    width: 30px;
    height: 30px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.modal-close:hover,
.modal-close:focus {
    background: #f8f9fa;
    border-radius: 50%;
    outline: 2px solid #007bff;
}

.modal-body {
    padding: 20px;
}

.modal-footer {
    display: flex;
    justify-content: flex-end;
    gap: 10px;
    padding: 20px;
    border-top: 1px solid #ddd;
}

.modal-footer button {
    padding: 10px 20px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
}

.btn-danger {
    background: #dc3545;
    color: white;
}

.btn-danger:focus {
    outline: 2px solid #721c24;
}

.modal-footer button:not(.btn-danger) {
    background: #6c757d;
    color: white;
}

.modal-footer button:not(.btn-danger):focus {
    outline: 2px solid #333;
}
</style>

<script>
// 跳轉到元素功能
function skipToElement(elementId) {
    const element = document.getElementById(elementId);
    if (element) {
        element.focus();
        element.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
}

// 開啟確認模態框
function openConfirmModal() {
    const overlay = document.getElementById('confirm-overlay');
    const modal = document.getElementById('confirm-modal');
    
    overlay.style.display = 'flex';
    
    // 觸發焦點陷阱
    setTimeout(() => {
        const firstButton = modal.querySelector('button');
        if (firstButton) {
            firstButton.focus();
        }
    }, 100);
    
    // 監聽 Escape 鍵
    document.addEventListener('keydown', handleModalKeydown);
}

// 關閉確認模態框
function closeConfirmModal() {
    const overlay = document.getElementById('confirm-overlay');
    overlay.style.display = 'none';
    
    // 移除鍵盤監聽
    document.removeEventListener('keydown', handleModalKeydown);
    
    // 回復焦點到觸發按鈕
    const deleteButton = document.querySelector('button[onclick="openConfirmModal()"]');
    if (deleteButton) {
        deleteButton.focus();
    }
}

// 處理模態框鍵盤事件
function handleModalKeydown(event) {
    if (event.key === 'Escape') {
        closeConfirmModal();
    }
    
    if (event.key === 'Tab') {
        const modal = document.getElementById('confirm-modal');
        const focusableElements = modal.querySelectorAll('button, [tabindex]:not([tabindex="-1"])');
        const firstElement = focusableElements[0];
        const lastElement = focusableElements[focusableElements.length - 1];
        
        if (event.shiftKey) {
            if (document.activeElement === firstElement) {
                event.preventDefault();
                lastElement.focus();
            }
        } else {
            if (document.activeElement === lastElement) {
                event.preventDefault();
                firstElement.focus();
            }
        }
    }
}

// 處理選單導航
document.addEventListener('keydown', function(event) {
    const activeElement = document.activeElement;
    
    // 處理主選單導航
    if (activeElement.closest('.nav-menu')) {
        handleMenuNavigation(event, activeElement);
    }
    
    // 處理表格導航
    if (activeElement.closest('.data-table')) {
        handleTableNavigation(event, activeElement);
    }
});

function handleMenuNavigation(event, activeElement) {
    const menuItems = Array.from(document.querySelectorAll('.nav-menu > li > a'));
    const currentIndex = menuItems.indexOf(activeElement);
    
    switch (event.key) {
        case 'ArrowRight':
            event.preventDefault();
            const nextIndex = (currentIndex + 1) % menuItems.length;
            menuItems[nextIndex].focus();
            break;
            
        case 'ArrowLeft':
            event.preventDefault();
            const prevIndex = currentIndex - 1 < 0 ? menuItems.length - 1 : currentIndex - 1;
            menuItems[prevIndex].focus();
            break;
            
        case 'ArrowDown':
            event.preventDefault();
            const submenu = activeElement.nextElementSibling;
            if (submenu && submenu.classList.contains('submenu')) {
                submenu.style.display = 'block';
                const firstSubmenuItem = submenu.querySelector('a');
                if (firstSubmenuItem) {
                    firstSubmenuItem.focus();
                }
            }
            break;
            
        case 'Escape':
            activeElement.blur();
            break;
    }
}

function handleTableNavigation(event, activeElement) {
    const table = activeElement.closest('table');
    const cells = Array.from(table.querySelectorAll('th, td'));
    const currentIndex = cells.indexOf(activeElement.closest('th, td'));
    const row = activeElement.closest('tr');
    const rowCells = Array.from(row.querySelectorAll('th, td'));
    const cellIndex = rowCells.indexOf(activeElement.closest('th, td'));
    
    let nextCell;
    
    switch (event.key) {
        case 'ArrowRight':
            event.preventDefault();
            nextCell = cells[currentIndex + 1];
            break;
            
        case 'ArrowLeft':
            event.preventDefault();
            nextCell = cells[currentIndex - 1];
            break;
            
        case 'ArrowDown':
            event.preventDefault();
            const nextRow = row.nextElementSibling;
            if (nextRow) {
                const nextRowCells = Array.from(nextRow.querySelectorAll('th, td'));
                nextCell = nextRowCells[cellIndex];
            }
            break;
            
        case 'ArrowUp':
            event.preventDefault();
            const prevRow = row.previousElementSibling;
            if (prevRow) {
                const prevRowCells = Array.from(prevRow.querySelectorAll('th, td'));
                nextCell = prevRowCells[cellIndex];
            }
            break;
    }
    
    if (nextCell) {
        const focusableInCell = nextCell.querySelector('button, input, select, a, [tabindex]:not([tabindex="-1"])');
        if (focusableInCell) {
            focusableInCell.focus();
        } else {
            nextCell.tabIndex = 0;
            nextCell.focus();
        }
    }
}
</script>