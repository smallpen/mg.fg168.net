<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>焦點管理器測試</title>
    @livewireStyles
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; }
        .test-section { margin: 20px 0; padding: 15px; border: 1px solid #ccc; }
        button { margin: 5px; padding: 10px 15px; }
        .modal { display: none; position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); 
                background: white; border: 2px solid #333; padding: 20px; z-index: 1000; }
        .modal.show { display: block; }
        .overlay { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; 
                  background: rgba(0,0,0,0.5); z-index: 999; }
        .overlay.show { display: block; }
    </style>
</head>
<body>
    <h1>焦點管理器測試頁面</h1>
    
    <!-- 焦點管理器元件 -->
    <livewire:admin.layout.focus-manager />
    
    <div class="test-section">
        <h2>基本焦點測試</h2>
        <button id="btn1" onclick="testFocus('btn2')">設定焦點到按鈕2</button>
        <button id="btn2" onclick="testFocus('btn3')">設定焦點到按鈕3</button>
        <button id="btn3" onclick="testFocus('btn1')">設定焦點到按鈕1</button>
    </div>
    
    <div class="test-section">
        <h2>模態框測試</h2>
        <button onclick="openModal()">開啟模態框</button>
        
        <div class="overlay" id="modal-overlay" onclick="closeModal()"></div>
        <div class="modal" id="test-modal" data-closable>
            <h3>測試模態框</h3>
            <p>這是一個測試模態框，應該會自動設定焦點陷阱。</p>
            <button id="modal-btn1">模態框按鈕1</button>
            <button id="modal-btn2">模態框按鈕2</button>
            <button onclick="closeModal()" data-close>關閉</button>
        </div>
    </div>
    
    <div class="test-section">
        <h2>選單導航測試</h2>
        <div class="menu" style="border: 1px solid #ddd; padding: 10px;">
            <a href="#" role="menuitem">選單項目 1</a><br>
            <a href="#" role="menuitem">選單項目 2</a><br>
            <a href="#" role="menuitem">選單項目 3</a><br>
            <a href="#" role="menuitem">選單項目 4</a><br>
        </div>
        <p>使用方向鍵在選單中導航</p>
    </div>
    
    <script>
        function testFocus(elementId) {
            // 直接設定焦點進行測試
            const element = document.getElementById(elementId);
            if (element) {
                element.focus();
                console.log('焦點設定到:', elementId);
            }
        }
        
        function openModal() {
            document.getElementById('modal-overlay').classList.add('show');
            document.getElementById('test-modal').classList.add('show');
            
            // 觸發模態框開啟事件
            window.dispatchEvent(new CustomEvent('modal-opened', { 
                detail: { modalId: 'test-modal' } 
            }));
        }
        
        function closeModal() {
            document.getElementById('modal-overlay').classList.remove('show');
            document.getElementById('test-modal').classList.remove('show');
            
            // 觸發模態框關閉事件
            window.dispatchEvent(new CustomEvent('modal-closed'));
        }
        
        // 測試鍵盤事件
        document.addEventListener('keydown', function(event) {
            console.log('按鍵:', event.key);
        });
    </script>
    
    @livewireScripts
</body>
</html>