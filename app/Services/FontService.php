<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Dompdf\Dompdf;
use Dompdf\FontMetrics;

class FontService
{
    protected string $fontDir;
    protected array $chineseFonts = [
        'noto-sans-tc' => [
            'name' => 'Noto Sans TC',
            'url' => 'https://fonts.google.com/download?family=Noto%20Sans%20TC',
            'files' => [
                'regular' => 'NotoSansTC-Regular.ttf',
                'bold' => 'NotoSansTC-Bold.ttf',
            ],
            'fallback_name' => 'notosanstc'
        ],
        'source-han-sans' => [
            'name' => 'Source Han Sans TC',
            'files' => [
                'regular' => 'SourceHanSansTC-Regular.otf',
                'bold' => 'SourceHanSansTC-Bold.otf',
            ],
            'fallback_name' => 'sourcehansanstc'
        ]
    ];

    public function __construct()
    {
        $this->fontDir = storage_path('fonts');
        $this->ensureFontDirectoryExists();
    }

    /**
     * 確保字體目錄存在
     */
    protected function ensureFontDirectoryExists(): void
    {
        if (!is_dir($this->fontDir)) {
            mkdir($this->fontDir, 0755, true);
        }
    }

    /**
     * 檢查是否有可用的中文字體
     */
    public function hasChineseFontSupport(): bool
    {
        foreach ($this->chineseFonts as $fontKey => $fontInfo) {
            if ($this->isFontInstalled($fontKey)) {
                return true;
            }
        }
        return false;
    }

    /**
     * 檢查特定字體是否已安裝
     */
    public function isFontInstalled(string $fontKey): bool
    {
        if (!isset($this->chineseFonts[$fontKey])) {
            return false;
        }

        $fontInfo = $this->chineseFonts[$fontKey];
        $regularFile = $this->fontDir . '/' . $fontInfo['files']['regular'];
        
        return file_exists($regularFile);
    }

    /**
     * 安裝 DejaVu Sans 的中文擴展（使用系統字體）
     */
    public function installSystemChineseFonts(): bool
    {
        try {
            // 嘗試使用系統中可能存在的中文字體
            $systemFonts = $this->findSystemChineseFonts();
            
            if (empty($systemFonts)) {
                Log::warning('未找到系統中文字體，將使用 Unicode 回退方案');
                return $this->createUnicodeFallbackFont();
            }

            // 複製系統字體到 DomPDF 字體目錄
            foreach ($systemFonts as $fontPath) {
                $this->copySystemFont($fontPath);
            }

            return true;

        } catch (\Exception $e) {
            Log::error('安裝系統中文字體失敗', [
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * 尋找系統中的中文字體
     */
    protected function findSystemChineseFonts(): array
    {
        $possiblePaths = [
            // Linux 系統字體路徑
            '/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf',
            '/usr/share/fonts/truetype/liberation/LiberationSans-Regular.ttf',
            '/usr/share/fonts/TTF/DejaVuSans.ttf',
            // 可能的中文字體
            '/usr/share/fonts/truetype/arphic/uming.ttc',
            '/usr/share/fonts/truetype/wqy/wqy-microhei.ttc',
            '/usr/share/fonts/opentype/noto/NotoSansCJK-Regular.ttc',
        ];

        $availableFonts = [];
        foreach ($possiblePaths as $path) {
            if (file_exists($path)) {
                $availableFonts[] = $path;
            }
        }

        return $availableFonts;
    }

    /**
     * 複製系統字體到 DomPDF 目錄
     */
    protected function copySystemFont(string $fontPath): bool
    {
        try {
            $fontName = basename($fontPath);
            $targetPath = $this->fontDir . '/' . $fontName;
            
            if (!file_exists($targetPath)) {
                copy($fontPath, $targetPath);
                Log::info('已複製字體', [
                    'source' => $fontPath,
                    'target' => $targetPath
                ]);
            }
            
            return true;
        } catch (\Exception $e) {
            Log::error('複製字體失敗', [
                'font_path' => $fontPath,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * 創建 Unicode 回退字體配置
     */
    protected function createUnicodeFallbackFont(): bool
    {
        try {
            // 創建一個字體映射配置文件
            $fontConfig = [
                'chinese-fallback' => [
                    'normal' => 'DejaVuSans',
                    'bold' => 'DejaVuSans-Bold',
                    'italic' => 'DejaVuSans-Oblique',
                    'bold_italic' => 'DejaVuSans-BoldOblique',
                ]
            ];

            $configPath = $this->fontDir . '/chinese_font_config.json';
            file_put_contents($configPath, json_encode($fontConfig, JSON_PRETTY_PRINT));

            Log::info('已創建 Unicode 回退字體配置', [
                'config_path' => $configPath
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('創建 Unicode 回退字體配置失敗', [
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * 獲取推薦的 PDF 字體配置
     */
    public function getRecommendedPdfFontConfig(): array
    {
        // 如果有中文字體支援，使用中文字體
        if ($this->hasChineseFontSupport()) {
            return [
                'font_family' => 'noto-sans-tc, source-han-sans, DejaVu Sans',
                'supports_chinese' => true
            ];
        }

        // 否則使用 Unicode 回退方案
        return [
            'font_family' => 'DejaVu Sans',
            'supports_chinese' => false,
            'note' => '使用 Unicode 編碼顯示中文字符'
        ];
    }

    /**
     * 為 DomPDF 配置中文字體
     */
    public function configureDompdfForChinese(Dompdf $dompdf): void
    {
        try {
            $options = $dompdf->getOptions();
            
            // 設置字體目錄
            $options->setFontDir($this->fontDir);
            $options->setFontCache($this->fontDir);
            
            // 啟用字體子集化以減少文件大小
            $options->setIsFontSubsettingEnabled(true);
            
            // 設置預設字體
            if ($this->hasChineseFontSupport()) {
                $options->setDefaultFont('noto-sans-tc');
            } else {
                $options->setDefaultFont('DejaVu Sans');
            }

            Log::info('已配置 DomPDF 中文字體支援', [
                'font_dir' => $this->fontDir,
                'has_chinese_support' => $this->hasChineseFontSupport()
            ]);

        } catch (\Exception $e) {
            Log::error('配置 DomPDF 中文字體失敗', [
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * 獲取字體狀態報告
     */
    public function getFontStatusReport(): array
    {
        $report = [
            'font_directory' => $this->fontDir,
            'directory_exists' => is_dir($this->fontDir),
            'directory_writable' => is_writable($this->fontDir),
            'chinese_support' => $this->hasChineseFontSupport(),
            'installed_fonts' => [],
            'system_fonts' => $this->findSystemChineseFonts(),
        ];

        // 檢查已安裝的字體
        foreach ($this->chineseFonts as $fontKey => $fontInfo) {
            $report['installed_fonts'][$fontKey] = [
                'name' => $fontInfo['name'],
                'installed' => $this->isFontInstalled($fontKey),
                'files' => $fontInfo['files']
            ];
        }

        return $report;
    }
}