<?php

namespace App\Services;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;

/**
 * 無障礙功能稽核服務
 * 
 * 檢查管理後台的無障礙功能實作狀況
 */
class AccessibilityAuditService
{
    /**
     * 執行完整的無障礙功能稽核
     */
    public function performAccessibilityAudit(): array
    {
        $results = [
            'aria_labels' => $this->checkAriaLabels(),
            'semantic_html' => $this->checkSemanticHTML(),
            'keyboard_navigation' => $this->checkKeyboardNavigation(),
            'color_contrast' => $this->checkColorContrast(),
            'focus_management' => $this->checkFocusManagement(),
            'screen_reader_support' => $this->checkScreenReaderSupport(),
            'skip_links' => $this->checkSkipLinks(),
            'form_accessibility' => $this->checkFormAccessibility(),
            'image_alt_text' => $this->checkImageAltText(),
            'heading_structure' => $this->checkHeadingStructure()
        ];
        
        // 計算整體分數
        $totalChecks = count($results);
        $passedChecks = count(array_filter($results, fn($result) => $result['status'] === 'passed'));
        $overallScore = round(($passedChecks / $totalChecks) * 100, 2);
        
        return [
            'status' => $overallScore >= 80 ? 'passed' : 'failed',
            'overall_score' => $overallScore,
            'passed_checks' => $passedChecks,
            'total_checks' => $totalChecks,
            'detailed_results' => $results,
            'recommendations' => $this->generateRecommendations($results)
        ];
    }
    
    /**
     * 檢查 ARIA 標籤使用情況
     */
    protected function checkAriaLabels(): array
    {
        $viewFiles = $this->getAdminViewFiles();
        $ariaIssues = [];
        $ariaUsageCount = 0;
        
        foreach ($viewFiles as $file) {
            $content = File::get($file);
            
            // 檢查 ARIA 標籤使用
            $ariaMatches = [];
            preg_match_all('/aria-[a-z]+=["\'][^"\']*["\']/', $content, $ariaMatches);
            $ariaUsageCount += count($ariaMatches[0]);
            
            // 檢查缺少 aria-label 的互動元素
            $interactiveElements = ['button', 'input', 'select', 'textarea', 'a'];
            foreach ($interactiveElements as $element) {
                $pattern = "/<{$element}[^>]*(?!.*aria-label)(?!.*aria-labelledby)[^>]*>/i";
                if (preg_match_all($pattern, $content, $matches)) {
                    foreach ($matches[0] as $match) {
                        // 檢查是否有文字內容或其他標籤方式
                        if (!$this->hasAccessibleLabel($match, $content)) {
                            $ariaIssues[] = [
                                'file' => basename($file),
                                'element' => $element,
                                'issue' => 'Missing accessible label',
                                'code' => trim($match)
                            ];
                        }
                    }
                }
            }
        }
        
        return [
            'status' => count($ariaIssues) < 5 ? 'passed' : 'failed',
            'aria_usage_count' => $ariaUsageCount,
            'issues' => $ariaIssues,
            'score' => count($ariaIssues) === 0 ? 100 : max(0, 100 - (count($ariaIssues) * 10))
        ];
    }
    
    /**
     * 檢查語義化 HTML 使用情況
     */
    protected function checkSemanticHTML(): array
    {
        $viewFiles = $this->getAdminViewFiles();
        $semanticElements = ['header', 'nav', 'main', 'section', 'article', 'aside', 'footer'];
        $semanticUsage = [];
        $issues = [];
        
        foreach ($viewFiles as $file) {
            $content = File::get($file);
            $fileName = basename($file);
            
            foreach ($semanticElements as $element) {
                $count = preg_match_all("/<{$element}[^>]*>/i", $content);
                $semanticUsage[$element] = ($semanticUsage[$element] ?? 0) + $count;
            }
            
            // 檢查是否過度使用 div 而非語義化元素
            $divCount = preg_match_all('/<div[^>]*>/', $content);
            $semanticCount = 0;
            foreach ($semanticElements as $element) {
                $semanticCount += preg_match_all("/<{$element}[^>]*>/i", $content);
            }
            
            if ($divCount > 10 && $semanticCount === 0) {
                $issues[] = [
                    'file' => $fileName,
                    'issue' => 'Excessive div usage without semantic elements',
                    'div_count' => $divCount
                ];
            }
        }
        
        $totalSemanticUsage = array_sum($semanticUsage);
        
        return [
            'status' => $totalSemanticUsage > 20 && count($issues) < 3 ? 'passed' : 'failed',
            'semantic_usage' => $semanticUsage,
            'total_usage' => $totalSemanticUsage,
            'issues' => $issues,
            'score' => min(100, $totalSemanticUsage * 2)
        ];
    }
    
    /**
     * 檢查鍵盤導航支援
     */
    protected function checkKeyboardNavigation(): array
    {
        $viewFiles = $this->getAdminViewFiles();
        $keyboardFeatures = [
            'tabindex_usage' => 0,
            'keyboard_event_handlers' => 0,
            'focus_indicators' => 0,
            'skip_links' => 0
        ];
        $issues = [];
        
        foreach ($viewFiles as $file) {
            $content = File::get($file);
            
            // 檢查 tabindex 使用
            $keyboardFeatures['tabindex_usage'] += preg_match_all('/tabindex=["\'][^"\']*["\']/', $content);
            
            // 檢查鍵盤事件處理
            $keyboardEvents = ['@keydown', '@keyup', '@keypress', 'onkeydown', 'onkeyup', 'onkeypress'];
            foreach ($keyboardEvents as $event) {
                $keyboardFeatures['keyboard_event_handlers'] += preg_match_all("/{$event}/i", $content);
            }
            
            // 檢查焦點指示器
            $focusPatterns = [':focus', 'focus:', 'focus-'];
            foreach ($focusPatterns as $pattern) {
                $keyboardFeatures['focus_indicators'] += preg_match_all("/{$pattern}/i", $content);
            }
            
            // 檢查跳轉連結
            if (strpos($content, 'skip') !== false && strpos($content, 'main') !== false) {
                $keyboardFeatures['skip_links']++;
            }
        }
        
        // 檢查是否有不當的 tabindex 使用
        foreach ($viewFiles as $file) {
            $content = File::get($file);
            if (preg_match('/tabindex=["\'](?!0|[-]1)[0-9]+["\']/', $content)) {
                $issues[] = [
                    'file' => basename($file),
                    'issue' => 'Inappropriate positive tabindex values found'
                ];
            }
        }
        
        $totalFeatures = array_sum($keyboardFeatures);
        
        return [
            'status' => $totalFeatures > 10 && count($issues) === 0 ? 'passed' : 'failed',
            'features' => $keyboardFeatures,
            'total_features' => $totalFeatures,
            'issues' => $issues,
            'score' => min(100, $totalFeatures * 5)
        ];
    }
    
    /**
     * 檢查色彩對比度
     */
    protected function checkColorContrast(): array
    {
        $cssFiles = [
            'resources/css/app.css',
            'public/build/assets/app.css'
        ];
        
        $colorIssues = [];
        $colorPairs = [];
        
        foreach ($cssFiles as $cssFile) {
            if (File::exists($cssFile)) {
                $content = File::get($cssFile);
                
                // 提取顏色定義
                preg_match_all('/(?:color|background-color|border-color):\s*([^;]+);/', $content, $matches);
                foreach ($matches[1] as $color) {
                    $colorPairs[] = trim($color);
                }
                
                // 檢查是否有低對比度的顏色組合
                $lowContrastPatterns = [
                    'color:\s*#[a-f0-9]{6};\s*background-color:\s*#[a-f0-9]{6};',
                    'color:\s*gray;\s*background-color:\s*lightgray;'
                ];
                
                foreach ($lowContrastPatterns as $pattern) {
                    if (preg_match_all("/{$pattern}/i", $content, $matches)) {
                        foreach ($matches[0] as $match) {
                            $colorIssues[] = [
                                'file' => basename($cssFile),
                                'issue' => 'Potential low contrast color combination',
                                'code' => trim($match)
                            ];
                        }
                    }
                }
            }
        }
        
        return [
            'status' => count($colorIssues) < 3 ? 'passed' : 'failed',
            'color_pairs_found' => count($colorPairs),
            'potential_issues' => $colorIssues,
            'score' => count($colorIssues) === 0 ? 100 : max(0, 100 - (count($colorIssues) * 20))
        ];
    }
    
    /**
     * 檢查焦點管理
     */
    protected function checkFocusManagement(): array
    {
        $jsFiles = [
            'resources/js/app.js',
            'public/build/assets/app.js'
        ];
        
        $focusFeatures = [
            'focus_methods' => 0,
            'focus_trapping' => 0,
            'focus_restoration' => 0,
            'focus_indicators' => 0
        ];
        
        foreach ($jsFiles as $jsFile) {
            if (File::exists($jsFile)) {
                $content = File::get($jsFile);
                
                // 檢查焦點方法使用
                $focusMethods = ['focus()', 'blur()', 'focusin', 'focusout'];
                foreach ($focusMethods as $method) {
                    $focusFeatures['focus_methods'] += substr_count($content, $method);
                }
                
                // 檢查焦點陷阱
                if (strpos($content, 'trap') !== false && strpos($content, 'focus') !== false) {
                    $focusFeatures['focus_trapping']++;
                }
                
                // 檢查焦點恢復
                if (strpos($content, 'restore') !== false && strpos($content, 'focus') !== false) {
                    $focusFeatures['focus_restoration']++;
                }
            }
        }
        
        // 檢查 CSS 焦點指示器
        $cssFiles = ['resources/css/app.css', 'public/build/assets/app.css'];
        foreach ($cssFiles as $cssFile) {
            if (File::exists($cssFile)) {
                $content = File::get($cssFile);
                $focusFeatures['focus_indicators'] += preg_match_all('/:focus[^{]*{[^}]*outline[^}]*}/', $content);
            }
        }
        
        $totalFeatures = array_sum($focusFeatures);
        
        return [
            'status' => $totalFeatures > 5 ? 'passed' : 'failed',
            'features' => $focusFeatures,
            'total_features' => $totalFeatures,
            'score' => min(100, $totalFeatures * 10)
        ];
    }
    
    /**
     * 檢查螢幕閱讀器支援
     */
    protected function checkScreenReaderSupport(): array
    {
        $viewFiles = $this->getAdminViewFiles();
        $srFeatures = [
            'sr_only_classes' => 0,
            'aria_live_regions' => 0,
            'aria_describedby' => 0,
            'role_attributes' => 0
        ];
        
        foreach ($viewFiles as $file) {
            $content = File::get($file);
            
            // 檢查螢幕閱讀器專用類別
            $srFeatures['sr_only_classes'] += preg_match_all('/class=["\'][^"\']*sr-only[^"\']*["\']/', $content);
            
            // 檢查 ARIA live regions
            $srFeatures['aria_live_regions'] += preg_match_all('/aria-live=["\'][^"\']*["\']/', $content);
            
            // 檢查 aria-describedby
            $srFeatures['aria_describedby'] += preg_match_all('/aria-describedby=["\'][^"\']*["\']/', $content);
            
            // 檢查 role 屬性
            $srFeatures['role_attributes'] += preg_match_all('/role=["\'][^"\']*["\']/', $content);
        }
        
        $totalFeatures = array_sum($srFeatures);
        
        return [
            'status' => $totalFeatures > 10 ? 'passed' : 'failed',
            'features' => $srFeatures,
            'total_features' => $totalFeatures,
            'score' => min(100, $totalFeatures * 5)
        ];
    }
    
    /**
     * 檢查跳轉連結
     */
    protected function checkSkipLinks(): array
    {
        $layoutFiles = [
            'resources/views/livewire/admin/layout/admin-layout.blade.php',
            'resources/views/livewire/admin/layout/skip-links.blade.php'
        ];
        
        $skipLinkFeatures = [
            'skip_to_main' => false,
            'skip_to_nav' => false,
            'skip_to_search' => false,
            'proper_positioning' => false
        ];
        
        foreach ($layoutFiles as $file) {
            if (File::exists($file)) {
                $content = File::get($file);
                
                if (strpos($content, 'skip') !== false && strpos($content, 'main') !== false) {
                    $skipLinkFeatures['skip_to_main'] = true;
                }
                
                if (strpos($content, 'skip') !== false && strpos($content, 'nav') !== false) {
                    $skipLinkFeatures['skip_to_nav'] = true;
                }
                
                if (strpos($content, 'skip') !== false && strpos($content, 'search') !== false) {
                    $skipLinkFeatures['skip_to_search'] = true;
                }
                
                // 檢查是否有適當的 CSS 定位
                if (strpos($content, 'sr-only') !== false || strpos($content, 'visually-hidden') !== false) {
                    $skipLinkFeatures['proper_positioning'] = true;
                }
            }
        }
        
        $implementedFeatures = count(array_filter($skipLinkFeatures));
        
        return [
            'status' => $implementedFeatures >= 2 ? 'passed' : 'failed',
            'features' => $skipLinkFeatures,
            'implemented_features' => $implementedFeatures,
            'score' => ($implementedFeatures / count($skipLinkFeatures)) * 100
        ];
    }
    
    /**
     * 檢查表單無障礙功能
     */
    protected function checkFormAccessibility(): array
    {
        $viewFiles = $this->getAdminViewFiles();
        $formFeatures = [
            'label_associations' => 0,
            'fieldset_usage' => 0,
            'error_associations' => 0,
            'required_indicators' => 0
        ];
        $issues = [];
        
        foreach ($viewFiles as $file) {
            $content = File::get($file);
            
            // 檢查 label 關聯
            $formFeatures['label_associations'] += preg_match_all('/<label[^>]*for=["\'][^"\']*["\'][^>]*>/', $content);
            
            // 檢查 fieldset 使用
            $formFeatures['fieldset_usage'] += preg_match_all('/<fieldset[^>]*>/', $content);
            
            // 檢查錯誤訊息關聯
            $formFeatures['error_associations'] += preg_match_all('/aria-describedby=["\'][^"\']*error[^"\']*["\']/', $content);
            
            // 檢查必填欄位指示
            $formFeatures['required_indicators'] += preg_match_all('/required[^>]*>/', $content);
            
            // 檢查沒有 label 的 input
            preg_match_all('/<input[^>]*>/', $content, $inputs);
            foreach ($inputs[0] as $input) {
                if (strpos($input, 'type="hidden"') === false && 
                    strpos($input, 'aria-label') === false && 
                    strpos($input, 'aria-labelledby') === false) {
                    
                    // 檢查是否有對應的 label
                    $idMatch = [];
                    if (preg_match('/id=["\']([^"\']*)["\']/', $input, $idMatch)) {
                        $id = $idMatch[1];
                        if (strpos($content, "for=\"{$id}\"") === false) {
                            $issues[] = [
                                'file' => basename($file),
                                'issue' => 'Input without associated label',
                                'element' => trim($input)
                            ];
                        }
                    }
                }
            }
        }
        
        $totalFeatures = array_sum($formFeatures);
        
        return [
            'status' => $totalFeatures > 5 && count($issues) < 3 ? 'passed' : 'failed',
            'features' => $formFeatures,
            'total_features' => $totalFeatures,
            'issues' => $issues,
            'score' => count($issues) === 0 ? min(100, $totalFeatures * 10) : max(0, 100 - (count($issues) * 15))
        ];
    }
    
    /**
     * 檢查圖片替代文字
     */
    protected function checkImageAltText(): array
    {
        $viewFiles = $this->getAdminViewFiles();
        $imageStats = [
            'total_images' => 0,
            'images_with_alt' => 0,
            'decorative_images' => 0
        ];
        $issues = [];
        
        foreach ($viewFiles as $file) {
            $content = File::get($file);
            
            // 找出所有圖片
            preg_match_all('/<img[^>]*>/', $content, $images);
            $imageStats['total_images'] += count($images[0]);
            
            foreach ($images[0] as $img) {
                if (strpos($img, 'alt=') !== false) {
                    $imageStats['images_with_alt']++;
                    
                    // 檢查是否為裝飾性圖片
                    if (preg_match('/alt=["\']["\']/', $img)) {
                        $imageStats['decorative_images']++;
                    }
                } else {
                    $issues[] = [
                        'file' => basename($file),
                        'issue' => 'Image without alt attribute',
                        'element' => trim($img)
                    ];
                }
            }
        }
        
        $altTextCoverage = $imageStats['total_images'] > 0 
            ? ($imageStats['images_with_alt'] / $imageStats['total_images']) * 100 
            : 100;
        
        return [
            'status' => $altTextCoverage >= 95 ? 'passed' : 'failed',
            'stats' => $imageStats,
            'alt_text_coverage' => round($altTextCoverage, 2),
            'issues' => $issues,
            'score' => round($altTextCoverage, 2)
        ];
    }
    
    /**
     * 檢查標題結構
     */
    protected function checkHeadingStructure(): array
    {
        $viewFiles = $this->getAdminViewFiles();
        $headingStats = [
            'h1' => 0, 'h2' => 0, 'h3' => 0, 'h4' => 0, 'h5' => 0, 'h6' => 0
        ];
        $issues = [];
        
        foreach ($viewFiles as $file) {
            $content = File::get($file);
            $fileName = basename($file);
            
            // 統計各級標題
            for ($i = 1; $i <= 6; $i++) {
                $count = preg_match_all("/<h{$i}[^>]*>/i", $content);
                $headingStats["h{$i}"] += $count;
            }
            
            // 檢查標題層級跳躍
            $headingLevels = [];
            for ($i = 1; $i <= 6; $i++) {
                if (preg_match_all("/<h{$i}[^>]*>/i", $content) > 0) {
                    $headingLevels[] = $i;
                }
            }
            
            for ($i = 1; $i < count($headingLevels); $i++) {
                if ($headingLevels[$i] - $headingLevels[$i-1] > 1) {
                    $issues[] = [
                        'file' => $fileName,
                        'issue' => "Heading level jump from h{$headingLevels[$i-1]} to h{$headingLevels[$i]}"
                    ];
                }
            }
        }
        
        $totalHeadings = array_sum($headingStats);
        
        return [
            'status' => $totalHeadings > 0 && count($issues) < 2 ? 'passed' : 'failed',
            'heading_stats' => $headingStats,
            'total_headings' => $totalHeadings,
            'issues' => $issues,
            'score' => count($issues) === 0 ? min(100, $totalHeadings * 5) : max(0, 100 - (count($issues) * 25))
        ];
    }
    
    /**
     * 生成改善建議
     */
    protected function generateRecommendations(array $results): array
    {
        $recommendations = [];
        
        foreach ($results as $category => $result) {
            if ($result['status'] === 'failed') {
                switch ($category) {
                    case 'aria_labels':
                        $recommendations[] = '為互動元素添加適當的 ARIA 標籤，特別是按鈕和連結';
                        break;
                    case 'semantic_html':
                        $recommendations[] = '使用更多語義化 HTML 元素，減少過度依賴 div 標籤';
                        break;
                    case 'keyboard_navigation':
                        $recommendations[] = '改善鍵盤導航支援，添加焦點指示器和鍵盤事件處理';
                        break;
                    case 'color_contrast':
                        $recommendations[] = '檢查並改善色彩對比度，確保符合 WCAG 標準';
                        break;
                    case 'focus_management':
                        $recommendations[] = '實作更好的焦點管理，包括焦點陷阱和焦點恢復';
                        break;
                    case 'screen_reader_support':
                        $recommendations[] = '增加螢幕閱讀器支援，使用 ARIA live regions 和描述性文字';
                        break;
                    case 'skip_links':
                        $recommendations[] = '添加跳轉連結，讓使用者能快速導航到主要內容';
                        break;
                    case 'form_accessibility':
                        $recommendations[] = '改善表單無障礙功能，確保所有輸入欄位都有適當的標籤';
                        break;
                    case 'image_alt_text':
                        $recommendations[] = '為所有圖片添加替代文字，裝飾性圖片使用空的 alt 屬性';
                        break;
                    case 'heading_structure':
                        $recommendations[] = '改善標題結構，避免標題層級跳躍，確保邏輯順序';
                        break;
                }
            }
        }
        
        return $recommendations;
    }
    
    /**
     * 取得管理後台視圖檔案列表
     */
    protected function getAdminViewFiles(): array
    {
        $viewPath = resource_path('views/livewire/admin');
        
        if (!File::exists($viewPath)) {
            return [];
        }
        
        return File::allFiles($viewPath);
    }
    
    /**
     * 檢查元素是否有可存取的標籤
     */
    protected function hasAccessibleLabel(string $element, string $content): bool
    {
        // 檢查是否有 aria-label 或 aria-labelledby
        if (strpos($element, 'aria-label') !== false || strpos($element, 'aria-labelledby') !== false) {
            return true;
        }
        
        // 檢查是否有文字內容（簡化檢查）
        if (preg_match('/>([^<]+)</', $element)) {
            return true;
        }
        
        // 檢查是否有 title 屬性
        if (strpos($element, 'title=') !== false) {
            return true;
        }
        
        return false;
    }
}