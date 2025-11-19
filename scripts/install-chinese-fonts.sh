#!/bin/bash

# 中文字體安裝腳本
# 此腳本會下載並安裝支援中文的字體到 DomPDF

set -e

echo "🔧 開始安裝中文字體支援..."

# 設定目錄
FONT_DIR="/var/www/html/storage/fonts"
TEMP_DIR="/tmp/fonts"

# 確保目錄存在
mkdir -p "$FONT_DIR"
mkdir -p "$TEMP_DIR"

echo "📁 字體目錄: $FONT_DIR"

# 檢查是否有網路連線
if ! ping -c 1 google.com &> /dev/null; then
    echo "⚠️ 無網路連線，嘗試使用系統字體..."
    
    # 尋找系統中的字體
    SYSTEM_FONTS=(
        "/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf"
        "/usr/share/fonts/truetype/liberation/LiberationSans-Regular.ttf"
        "/usr/share/fonts/TTF/DejaVuSans.ttf"
        "/System/Library/Fonts/Arial.ttf"
        "/Windows/Fonts/arial.ttf"
    )
    
    for font in "${SYSTEM_FONTS[@]}"; do
        if [ -f "$font" ]; then
            echo "📋 複製系統字體: $(basename "$font")"
            cp "$font" "$FONT_DIR/"
        fi
    done
else
    echo "🌐 有網路連線，下載中文字體..."
    
    # 下載 Noto Sans CJK (Google 字體)
    echo "📥 下載 Noto Sans CJK..."
    cd "$TEMP_DIR"
    
    # 使用 GitHub 上的 Noto 字體
    if command -v wget &> /dev/null; then
        wget -q "https://github.com/googlefonts/noto-cjk/raw/main/Sans/OTC/NotoSansCJK-Regular.ttc" -O "NotoSansCJK-Regular.ttc" || echo "⚠️ 下載失敗"
        wget -q "https://github.com/googlefonts/noto-cjk/raw/main/Sans/OTC/NotoSansCJK-Bold.ttc" -O "NotoSansCJK-Bold.ttc" || echo "⚠️ 下載失敗"
    elif command -v curl &> /dev/null; then
        curl -sL "https://github.com/googlefonts/noto-cjk/raw/main/Sans/OTC/NotoSansCJK-Regular.ttc" -o "NotoSansCJK-Regular.ttc" || echo "⚠️ 下載失敗"
        curl -sL "https://github.com/googlefonts/noto-cjk/raw/main/Sans/OTC/NotoSansCJK-Bold.ttc" -o "NotoSansCJK-Bold.ttc" || echo "⚠️ 下載失敗"
    fi
    
    # 複製下載的字體
    for font_file in *.ttc *.ttf *.otf; do
        if [ -f "$font_file" ]; then
            echo "📋 安裝字體: $font_file"
            cp "$font_file" "$FONT_DIR/"
        fi
    done
fi

# 創建字體映射配置
echo "⚙️ 創建字體配置..."
cat > "$FONT_DIR/font_mapping.json" << 'EOF'
{
    "chinese_fonts": {
        "primary": "NotoSansCJK-Regular.ttc",
        "bold": "NotoSansCJK-Bold.ttc",
        "fallback": "DejaVuSans.ttf"
    },
    "font_families": {
        "noto-sans-cjk": {
            "normal": "NotoSansCJK-Regular.ttc",
            "bold": "NotoSansCJK-Bold.ttc"
        },
        "dejavu-sans": {
            "normal": "DejaVuSans.ttf",
            "bold": "DejaVuSans-Bold.ttf"
        }
    }
}
EOF

# 設定權限
echo "🔐 設定權限..."
chown -R www-data:www-data "$FONT_DIR"
chmod -R 755 "$FONT_DIR"

# 清理臨時檔案
echo "🧹 清理臨時檔案..."
rm -rf "$TEMP_DIR"

# 檢查安裝結果
echo "✅ 字體安裝完成！"
echo ""
echo "📊 安裝的字體檔案:"
ls -la "$FONT_DIR"

echo ""
echo "💡 使用建議:"
echo "  • 執行 'php artisan fonts:manage status' 檢查字體狀態"
echo "  • 執行 'php artisan fonts:manage test' 測試字體支援"
echo "  • 如果 PDF 中文仍有問題，建議使用 HTML 格式匯出"

echo ""
echo "🎉 中文字體安裝腳本執行完成！"