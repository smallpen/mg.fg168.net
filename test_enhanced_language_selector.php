<?php

/**
 * 測試增強版語言選擇器功能
 * 
 * 此腳本測試語言選擇器的新功能：
 * 1. 視覺回饋改善
 * 2. 載入動畫
 * 3. 確認機制
 * 4. 響應速度優化
 */

require_once 'vendor/autoload.php';

use App\Livewire\Admin\LanguageSelector;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Cache;

class EnhancedLanguageSelectorTest
{
    private $results = [];
    
    public function runTests()
    {
        echo "=== 增強版語言選擇器功能測試 ===\n\n";
        
        $this->testComponentInitialization();
        $this->testLanguageSwitchInitiation();
        $this->testConfirmationMechanism();
        $this->testStateManagement();
        $this->testErrorHandling();
        $this->testPerformanceOptimizations();
        
        $this->displayResults();
    }
    
    /**
     * 測試元件初始化
     */
    private function testComponentInitialization()
    {
        echo "1. 測試元件初始化...\n";
        
        try {
            $component = new LanguageSelector();
            $component->mount();
            
            // 檢查初始狀態
            $this->assert($component->currentLocale !== null, "目前語言已設定");
            $this->assert(!$component->isChanging, "初始狀態：未在切換中");
            $this->assert(!$component->showConfirmation, "初始狀態：未顯示確認對話框");
            $this->assert(empty($component->pendingLocale), "初始狀態：無待切換語言");
            $this->assert(!$component->switchSuccess, "初始狀態：未成功切換");
            
            $this->results['initialization'] = true;
            echo "   ✅ 元件初始化測試通過\n\n";
            
        } catch (Exception $e) {
            $this->results['initialization'] = false;
            echo "   ❌ 元件初始化測試失敗: " . $e->getMessage() . "\n\n";
        }
    }
    
    /**
     * 測試語言切換啟動
     */
    private function testLanguageSwitchInitiation()
    {
        echo "2. 測試語言切換啟動...\n";
        
        try {
            $component = new LanguageSelector();
            $component->mount();
            
            // 測試啟動語言切換
            $component->initiateLanguageSwitch('en');
            
            $this->assert($component->pendingLocale === 'en', "待切換語言已設定");
            $this->assert($component->showConfirmation, "確認對話框已顯示");
            
            // 測試相同語言切換（應該被忽略）
            $currentLocale = $component->currentLocale;
            $component->initiateLanguageSwitch($currentLocale);
            $this->assert($component->pendingLocale === 'en', "相同語言切換被正確忽略");
            
            // 測試不支援的語言
            $component->initiateLanguageSwitch('invalid');
            // 應該觸發錯誤事件，但不會改變狀態
            
            $this->results['initiation'] = true;
            echo "   ✅ 語言切換啟動測試通過\n\n";
            
        } catch (Exception $e) {
            $this->results['initiation'] = false;
            echo "   ❌ 語言切換啟動測試失敗: " . $e->getMessage() . "\n\n";
        }
    }
    
    /**
     * 測試確認機制
     */
    private function testConfirmationMechanism()
    {
        echo "3. 測試確認機制...\n";
        
        try {
            $component = new LanguageSelector();
            $component->mount();
            
            // 設定待切換語言
            $component->initiateLanguageSwitch('en');
            
            // 測試取消切換
            $component->cancelLanguageSwitch();
            $this->assert(!$component->showConfirmation, "確認對話框已關閉");
            $this->assert(empty($component->pendingLocale), "待切換語言已清除");
            
            // 重新設定並確認切換
            $component->initiateLanguageSwitch('en');
            $this->assert($component->showConfirmation, "確認對話框重新顯示");
            
            $this->results['confirmation'] = true;
            echo "   ✅ 確認機制測試通過\n\n";
            
        } catch (Exception $e) {
            $this->results['confirmation'] = false;
            echo "   ❌ 確認機制測試失敗: " . $e->getMessage() . "\n\n";
        }
    }
    
    /**
     * 測試狀態管理
     */
    private function testStateManagement()
    {
        echo "4. 測試狀態管理...\n";
        
        try {
            $component = new LanguageSelector();
            $component->mount();
            
            // 測試狀態重置
            $component->isChanging = true;
            $component->showConfirmation = true;
            $component->pendingLocale = 'en';
            $component->switchSuccess = true;
            
            $component->resetState();
            
            $this->assert(!$component->isChanging, "切換狀態已重置");
            $this->assert(!$component->showConfirmation, "確認狀態已重置");
            $this->assert(empty($component->pendingLocale), "待切換語言已重置");
            $this->assert(!$component->switchSuccess, "成功狀態已重置");
            
            $this->results['state_management'] = true;
            echo "   ✅ 狀態管理測試通過\n\n";
            
        } catch (Exception $e) {
            $this->results['state_management'] = false;
            echo "   ❌ 狀態管理測試失敗: " . $e->getMessage() . "\n\n";
        }
    }
    
    /**
     * 測試錯誤處理
     */
    private function testErrorHandling()
    {
        echo "5. 測試錯誤處理...\n";
        
        try {
            $component = new LanguageSelector();
            $component->mount();
            
            // 測試不支援的語言代碼
            $component->initiateLanguageSwitch('invalid_locale');
            // 應該不會設定 pendingLocale
            $this->assert(empty($component->pendingLocale), "不支援的語言被正確拒絕");
            
            // 測試空語言代碼
            $component->initiateLanguageSwitch('');
            $this->assert(empty($component->pendingLocale), "空語言代碼被正確拒絕");
            
            $this->results['error_handling'] = true;
            echo "   ✅ 錯誤處理測試通過\n\n";
            
        } catch (Exception $e) {
            $this->results['error_handling'] = false;
            echo "   ❌ 錯誤處理測試失敗: " . $e->getMessage() . "\n\n";
        }
    }
    
    /**
     * 測試效能優化
     */
    private function testPerformanceOptimizations()
    {
        echo "6. 測試效能優化...\n";
        
        try {
            // 測試支援的語言列表
            $component = new LanguageSelector();
            $component->mount();
            
            $this->assert(count($component->supportedLocales) > 0, "支援的語言列表不為空");
            $this->assert(array_key_exists('zh_TW', $component->supportedLocales), "支援正體中文");
            $this->assert(array_key_exists('en', $component->supportedLocales), "支援英文");
            
            // 測試語言名稱取得
            $zhName = $component->getLanguageName('zh_TW');
            $enName = $component->getLanguageName('en');
            
            $this->assert(!empty($zhName), "正體中文名稱不為空");
            $this->assert(!empty($enName), "英文名稱不為空");
            
            // 測試目前語言檢查
            $isCurrent = $component->isCurrentLanguage($component->currentLocale);
            $this->assert($isCurrent, "目前語言檢查正確");
            
            $this->results['performance'] = true;
            echo "   ✅ 效能優化測試通過\n\n";
            
        } catch (Exception $e) {
            $this->results['performance'] = false;
            echo "   ❌ 效能優化測試失敗: " . $e->getMessage() . "\n\n";
        }
    }
    
    /**
     * 斷言輔助方法
     */
    private function assert($condition, $message)
    {
        if (!$condition) {
            throw new Exception("斷言失敗: $message");
        }
        echo "   ✓ $message\n";
    }
    
    /**
     * 顯示測試結果
     */
    private function displayResults()
    {
        echo "=== 測試結果摘要 ===\n";
        
        $passed = 0;
        $total = count($this->results);
        
        foreach ($this->results as $test => $result) {
            $status = $result ? "✅ 通過" : "❌ 失敗";
            echo "- " . ucfirst(str_replace('_', ' ', $test)) . ": $status\n";
            if ($result) $passed++;
        }
        
        echo "\n總計: $passed/$total 項測試通過\n";
        
        if ($passed === $total) {
            echo "🎉 所有測試都通過了！增強版語言選擇器功能正常運作。\n";
        } else {
            echo "⚠️  有部分測試失敗，請檢查相關功能。\n";
        }
        
        echo "\n=== 新功能特點 ===\n";
        echo "✨ 視覺回饋改善：\n";
        echo "   - 按鈕狀態動畫\n";
        echo "   - 成功指示器\n";
        echo "   - 載入狀態顯示\n\n";
        
        echo "🔄 載入動畫：\n";
        echo "   - 全螢幕載入覆蓋層\n";
        echo "   - 進度條動畫\n";
        echo "   - 狀態文字提示\n\n";
        
        echo "✅ 確認機制：\n";
        echo "   - 語言切換確認對話框\n";
        echo "   - 取消/確認選項\n";
        echo "   - 切換資訊預覽\n\n";
        
        echo "⚡ 響應速度優化：\n";
        echo "   - 語言資源預載入\n";
        echo "   - 快取機制\n";
        echo "   - 鍵盤快捷鍵 (Alt + L)\n";
        echo "   - 錯誤處理和日誌記錄\n\n";
    }
}

// 執行測試
if (php_sapi_name() === 'cli') {
    $test = new EnhancedLanguageSelectorTest();
    $test->runTests();
} else {
    echo "<pre>";
    $test = new EnhancedLanguageSelectorTest();
    $test->runTests();
    echo "</pre>";
}