<?php

namespace App\Services\LivewireFormReset;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Collection;

/**
 * Livewire 元件掃描器
 * 
 * 掃描 app/Livewire 和 resources/views/livewire 目錄，
 * 識別需要修復的 Livewire 元件和相關的表單重置方法
 */
class LivewireComponentScanner
{
    /**
     * 掃描路徑配置
     */
    protected array $scanPaths = [
        'php' => 'app/Livewire',
        'views' => 'resources/views/livewire'
    ];

    /**
     * 重置方法模式
     */
    protected array $resetMethodPatterns = [
        'resetFilters' => '/public\s+function\s+resetFilters\s*\(\s*\)\s*:\s*void/',
        'resetForm' => '/(?:public|private|protected)\s+function\s+resetForm\s*\(\s*\)/',
        'clearFilters' => '/public\s+function\s+clearFilters\s*\(\s*\)\s*:\s*void/',
        'reset' => '/\$this->reset\s*\(/',
    ];

    /**
     * wire:model 使用模式
     */
    protected array $wireModelPatterns = [
        'wire:model.lazy' => '/wire:model\.lazy\s*=\s*["\']([^"\']+)["\']/',
        'wire:model.live' => '/wire:model\.live(?:\.debounce\.\d+ms)?\s*=\s*["\']([^"\']+)["\']/',
        'wire:model.defer' => '/wire:model\.defer\s*=\s*["\']([^"\']+)["\']/',
        'wire:model' => '/wire:model\s*=\s*["\']([^"\']+)["\']/',
    ];

    /**
     * 掃描所有 Livewire 元件
     */
    public function scanAllComponents(): Collection
    {
        $components = collect();

        // 掃描 PHP 元件檔案
        $phpFiles = $this->scanPhpComponents();
        
        // 掃描視圖檔案
        $viewFiles = $this->scanViewComponents();

        // 合併結果並建立元件資訊
        foreach ($phpFiles as $phpFile) {
            $componentInfo = $this->analyzePhpComponent($phpFile);
            
            // 尋找對應的視圖檔案
            $viewFile = $this->findCorrespondingView($phpFile);
            if ($viewFile) {
                $viewInfo = $this->analyzeViewComponent($viewFile);
                $componentInfo = $this->mergeComponentInfo($componentInfo, $viewInfo);
            }

            $components->push($componentInfo);
        }

        return $components;
    }

    /**
     * 掃描 PHP 元件檔案
     */
    public function scanPhpComponents(): Collection
    {
        $phpPath = base_path($this->scanPaths['php']);
        
        if (!File::exists($phpPath)) {
            return collect();
        }

        return collect(File::allFiles($phpPath))
            ->filter(fn($file) => $file->getExtension() === 'php')
            ->map(fn($file) => [
                'path' => $file->getPathname(),
                'relative_path' => str_replace(base_path() . DIRECTORY_SEPARATOR, '', $file->getPathname()),
                'name' => $file->getFilenameWithoutExtension(),
                'namespace' => $this->extractNamespace($file->getPathname()),
                'class_name' => $this->extractClassName($file->getPathname()),
            ]);
    }

    /**
     * 掃描視圖元件檔案
     */
    public function scanViewComponents(): Collection
    {
        $viewPath = base_path($this->scanPaths['views']);
        
        if (!File::exists($viewPath)) {
            return collect();
        }

        return collect(File::allFiles($viewPath))
            ->filter(fn($file) => $file->getExtension() === 'php')
            ->map(fn($file) => [
                'path' => $file->getPathname(),
                'relative_path' => str_replace(base_path() . DIRECTORY_SEPARATOR, '', $file->getPathname()),
                'name' => str_replace('.blade', '', $file->getFilenameWithoutExtension()),
                'view_name' => $this->extractViewName($file->getPathname()),
            ]);
    }

    /**
     * 分析 PHP 元件檔案
     */
    public function analyzePhpComponent(array $fileInfo): array
    {
        $content = File::get($fileInfo['path']);
        
        return [
            'type' => 'php',
            'path' => $fileInfo['path'],
            'relative_path' => $fileInfo['relative_path'],
            'name' => $fileInfo['name'],
            'namespace' => $fileInfo['namespace'],
            'class_name' => $fileInfo['class_name'],
            'reset_methods' => $this->findResetMethods($content),
            'wire_model_usage' => $this->findWireModelUsageInPhp($content),
            'properties' => $this->extractPublicProperties($content),
            'methods' => $this->extractMethods($content),
            'has_reset_functionality' => $this->hasResetFunctionality($content),
            'complexity_score' => $this->calculateComplexityScore($content),
        ];
    }

    /**
     * 分析視圖元件檔案
     */
    public function analyzeViewComponent(array $fileInfo): array
    {
        $content = File::get($fileInfo['path']);
        
        return [
            'type' => 'view',
            'path' => $fileInfo['path'],
            'relative_path' => $fileInfo['relative_path'],
            'name' => $fileInfo['name'],
            'view_name' => $fileInfo['view_name'],
            'wire_model_usage' => $this->findWireModelUsageInView($content),
            'wire_key_usage' => $this->findWireKeyUsage($content),
            'form_elements' => $this->findFormElements($content),
            'reset_buttons' => $this->findResetButtons($content),
            'javascript_events' => $this->findJavaScriptEvents($content),
        ];
    }

    /**
     * 尋找重置方法
     */
    public function findResetMethods(string $content): array
    {
        $methods = [];

        foreach ($this->resetMethodPatterns as $methodName => $pattern) {
            if (preg_match_all($pattern, $content, $matches, PREG_OFFSET_CAPTURE)) {
                foreach ($matches[0] as $match) {
                    $methods[] = [
                        'name' => $methodName,
                        'pattern' => $pattern,
                        'found_at' => $match[1],
                        'full_match' => $match[0],
                        'method_body' => $this->extractMethodBody($content, $match[1]),
                    ];
                }
            }
        }

        return $methods;
    }

    /**
     * 在 PHP 檔案中尋找 wire:model 使用情況
     */
    public function findWireModelUsageInPhp(string $content): array
    {
        $usage = [];

        // 在 PHP 檔案中尋找字串中的 wire:model
        $stringPatterns = [
            '/["\'].*?wire:model\.lazy.*?["\']/',
            '/["\'].*?wire:model\.live.*?["\']/',
            '/["\'].*?wire:model\.defer.*?["\']/',
        ];

        foreach ($stringPatterns as $pattern) {
            if (preg_match_all($pattern, $content, $matches, PREG_OFFSET_CAPTURE)) {
                foreach ($matches[0] as $match) {
                    $usage[] = [
                        'type' => $this->identifyWireModelType($match[0]),
                        'found_at' => $match[1],
                        'context' => $match[0],
                    ];
                }
            }
        }

        return $usage;
    }

    /**
     * 在視圖檔案中尋找 wire:model 使用情況
     */
    public function findWireModelUsageInView(string $content): array
    {
        $usage = [];

        foreach ($this->wireModelPatterns as $type => $pattern) {
            if (preg_match_all($pattern, $content, $matches, PREG_OFFSET_CAPTURE)) {
                foreach ($matches[0] as $index => $match) {
                    $usage[] = [
                        'type' => $type,
                        'property' => $matches[1][$index][0] ?? '',
                        'found_at' => $match[1],
                        'full_match' => $match[0],
                        'line_number' => $this->getLineNumber($content, $match[1]),
                    ];
                }
            }
        }

        return $usage;
    }

    /**
     * 尋找 wire:key 使用情況
     */
    public function findWireKeyUsage(string $content): array
    {
        $pattern = '/wire:key\s*=\s*["\']([^"\']+)["\']/';
        $usage = [];

        if (preg_match_all($pattern, $content, $matches, PREG_OFFSET_CAPTURE)) {
            foreach ($matches[0] as $index => $match) {
                $usage[] = [
                    'key_value' => $matches[1][$index][0],
                    'found_at' => $match[1],
                    'full_match' => $match[0],
                    'line_number' => $this->getLineNumber($content, $match[1]),
                ];
            }
        }

        return $usage;
    }

    /**
     * 尋找表單元素
     */
    public function findFormElements(string $content): array
    {
        $elements = [];
        
        $patterns = [
            'input' => '/<input[^>]*>/',
            'select' => '/<select[^>]*>.*?<\/select>/s',
            'textarea' => '/<textarea[^>]*>.*?<\/textarea>/s',
            'form' => '/<form[^>]*>.*?<\/form>/s',
        ];

        foreach ($patterns as $type => $pattern) {
            if (preg_match_all($pattern, $content, $matches, PREG_OFFSET_CAPTURE)) {
                foreach ($matches[0] as $match) {
                    $elements[] = [
                        'type' => $type,
                        'found_at' => $match[1],
                        'content' => $match[0],
                        'line_number' => $this->getLineNumber($content, $match[1]),
                        'has_wire_model' => $this->hasWireModel($match[0]),
                        'wire_model_type' => $this->identifyWireModelType($match[0]),
                    ];
                }
            }
        }

        return $elements;
    }

    /**
     * 尋找重置按鈕
     */
    public function findResetButtons(string $content): array
    {
        $buttons = [];
        
        $patterns = [
            'reset_button' => '/<button[^>]*type\s*=\s*["\']reset["\'][^>]*>/',
            'wire_click_reset' => '/wire:click\s*=\s*["\'](?:resetFilters|resetForm|clearFilters)["\']/',
            'onclick_reset' => '/onclick\s*=\s*["\'][^"\']*(?:reset|clear)[^"\']*["\']/',
        ];

        foreach ($patterns as $type => $pattern) {
            if (preg_match_all($pattern, $content, $matches, PREG_OFFSET_CAPTURE)) {
                foreach ($matches[0] as $match) {
                    $buttons[] = [
                        'type' => $type,
                        'found_at' => $match[1],
                        'content' => $match[0],
                        'line_number' => $this->getLineNumber($content, $match[1]),
                    ];
                }
            }
        }

        return $buttons;
    }

    /**
     * 尋找 JavaScript 事件
     */
    public function findJavaScriptEvents(string $content): array
    {
        $events = [];
        
        $patterns = [
            'livewire_on' => '/Livewire\.on\s*\(\s*["\']([^"\']+)["\']/',
            'dispatch' => '/\$this->dispatch\s*\(\s*["\']([^"\']+)["\']/',
            'alpine_event' => '/@[a-zA-Z-]+\s*=\s*["\'][^"\']*["\']/',
        ];

        foreach ($patterns as $type => $pattern) {
            if (preg_match_all($pattern, $content, $matches, PREG_OFFSET_CAPTURE)) {
                foreach ($matches[0] as $index => $match) {
                    $events[] = [
                        'type' => $type,
                        'event_name' => $matches[1][$index][0] ?? '',
                        'found_at' => $match[1],
                        'content' => $match[0],
                        'line_number' => $this->getLineNumber($content, $match[1]),
                    ];
                }
            }
        }

        return $events;
    }

    /**
     * 提取公開屬性
     */
    public function extractPublicProperties(string $content): array
    {
        $pattern = '/public\s+(?:string|int|bool|array|float|\?string|\?int|\?bool|\?array|\?float)\s+\$([a-zA-Z_][a-zA-Z0-9_]*)\s*(?:=\s*[^;]+)?;/';
        $properties = [];

        if (preg_match_all($pattern, $content, $matches, PREG_OFFSET_CAPTURE)) {
            foreach ($matches[1] as $index => $match) {
                $properties[] = [
                    'name' => $match[0],
                    'found_at' => $match[1],
                    'full_declaration' => $matches[0][$index][0],
                    'line_number' => $this->getLineNumber($content, $match[1]),
                ];
            }
        }

        return $properties;
    }

    /**
     * 提取方法
     */
    public function extractMethods(string $content): array
    {
        $pattern = '/(public|protected|private)\s+function\s+([a-zA-Z_][a-zA-Z0-9_]*)\s*\([^)]*\)/';
        $methods = [];

        if (preg_match_all($pattern, $content, $matches, PREG_OFFSET_CAPTURE)) {
            foreach ($matches[2] as $index => $match) {
                $methods[] = [
                    'name' => $match[0],
                    'visibility' => $matches[1][$index][0],
                    'found_at' => $match[1],
                    'full_declaration' => $matches[0][$index][0],
                    'line_number' => $this->getLineNumber($content, $match[1]),
                ];
            }
        }

        return $methods;
    }

    /**
     * 檢查是否有重置功能
     */
    public function hasResetFunctionality(string $content): bool
    {
        foreach ($this->resetMethodPatterns as $pattern) {
            if (preg_match($pattern, $content)) {
                return true;
            }
        }

        return false;
    }

    /**
     * 計算複雜度分數
     */
    public function calculateComplexityScore(string $content): int
    {
        $score = 0;

        // 基於方法數量
        $methodCount = substr_count($content, 'function ');
        $score += $methodCount * 2;

        // 基於屬性數量
        $propertyCount = substr_count($content, 'public $');
        $score += $propertyCount;

        // 基於 wire:model 使用數量
        $wireModelCount = substr_count($content, 'wire:model');
        $score += $wireModelCount * 3;

        // 基於條件語句數量
        $conditionalCount = substr_count($content, 'if (') + substr_count($content, 'foreach (');
        $score += $conditionalCount;

        // 基於 Livewire 特定功能
        $livewireFeatures = [
            '$this->dispatch',
            '$this->reset',
            'WithPagination',
            'WithFileUploads',
            '#[On(',
        ];

        foreach ($livewireFeatures as $feature) {
            if (strpos($content, $feature) !== false) {
                $score += 5;
            }
        }

        return $score;
    }

    /**
     * 尋找對應的視圖檔案
     */
    protected function findCorrespondingView(array $phpFile): ?array
    {
        // 從 PHP 檔案路徑推導視圖路徑
        $relativePath = str_replace('app/Livewire/', '', $phpFile['relative_path']);
        $relativePath = str_replace('.php', '', $relativePath);
        $viewPath = 'resources/views/livewire/' . strtolower(str_replace(['\\', '/'], ['-', '-'], $relativePath)) . '.blade.php';
        
        $fullViewPath = base_path($viewPath);
        
        if (File::exists($fullViewPath)) {
            return [
                'path' => $fullViewPath,
                'relative_path' => $viewPath,
                'name' => basename($viewPath, '.blade.php'),
                'view_name' => str_replace(['/', '\\'], '.', strtolower($relativePath)),
            ];
        }

        return null;
    }

    /**
     * 合併元件資訊
     */
    protected function mergeComponentInfo(array $phpInfo, array $viewInfo): array
    {
        return array_merge($phpInfo, [
            'view_path' => $viewInfo['path'],
            'view_relative_path' => $viewInfo['relative_path'],
            'view_name' => $viewInfo['view_name'],
            'view_wire_model_usage' => $viewInfo['wire_model_usage'] ?? [],
            'view_wire_key_usage' => $viewInfo['wire_key_usage'] ?? [],
            'view_form_elements' => $viewInfo['form_elements'] ?? [],
            'view_reset_buttons' => $viewInfo['reset_buttons'] ?? [],
            'view_javascript_events' => $viewInfo['javascript_events'] ?? [],
        ]);
    }

    /**
     * 提取命名空間
     */
    protected function extractNamespace(string $filePath): string
    {
        $content = File::get($filePath);
        
        if (preg_match('/namespace\s+([^;]+);/', $content, $matches)) {
            return $matches[1];
        }

        return '';
    }

    /**
     * 提取類別名稱
     */
    protected function extractClassName(string $filePath): string
    {
        $content = File::get($filePath);
        
        if (preg_match('/class\s+([a-zA-Z_][a-zA-Z0-9_]*)\s+extends/', $content, $matches)) {
            return $matches[1];
        }

        return '';
    }

    /**
     * 提取視圖名稱
     */
    protected function extractViewName(string $filePath): string
    {
        $relativePath = str_replace(base_path('resources/views/'), '', $filePath);
        $relativePath = str_replace('.blade.php', '', $relativePath);
        
        return str_replace(['/', '\\'], '.', $relativePath);
    }

    /**
     * 提取方法主體
     */
    protected function extractMethodBody(string $content, int $startPosition): string
    {
        $braceCount = 0;
        $inMethod = false;
        $methodBody = '';
        $length = strlen($content);

        for ($i = $startPosition; $i < $length; $i++) {
            $char = $content[$i];
            
            if ($char === '{') {
                $braceCount++;
                $inMethod = true;
            } elseif ($char === '}') {
                $braceCount--;
            }
            
            if ($inMethod) {
                $methodBody .= $char;
            }
            
            if ($inMethod && $braceCount === 0) {
                break;
            }
        }

        return $methodBody;
    }

    /**
     * 識別 wire:model 類型
     */
    protected function identifyWireModelType(string $content): string
    {
        if (strpos($content, 'wire:model.lazy') !== false) {
            return 'wire:model.lazy';
        } elseif (strpos($content, 'wire:model.live') !== false) {
            return 'wire:model.live';
        } elseif (strpos($content, 'wire:model.defer') !== false) {
            return 'wire:model.defer';
        } elseif (strpos($content, 'wire:model') !== false) {
            return 'wire:model';
        }

        return 'none';
    }

    /**
     * 檢查是否有 wire:model
     */
    protected function hasWireModel(string $content): bool
    {
        return strpos($content, 'wire:model') !== false;
    }

    /**
     * 取得行號
     */
    protected function getLineNumber(string $content, int $position): int
    {
        return substr_count(substr($content, 0, $position), "\n") + 1;
    }
}