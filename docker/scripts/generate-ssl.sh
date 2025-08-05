#!/bin/bash
# SSL 憑證生成腳本（用於開發和測試環境）

set -e

SSL_DIR="./docker/ssl"
DOMAIN="localhost"

# 建立 SSL 目錄
mkdir -p "$SSL_DIR"

echo "生成 SSL 憑證用於域名: $DOMAIN"

# 生成私鑰
openssl genrsa -out "$SSL_DIR/key.pem" 2048

# 生成憑證簽名請求
openssl req -new -key "$SSL_DIR/key.pem" -out "$SSL_DIR/cert.csr" -subj "/C=TW/ST=Taiwan/L=Taipei/O=Laravel Admin/OU=IT Department/CN=$DOMAIN"

# 生成自簽名憑證
openssl x509 -req -days 365 -in "$SSL_DIR/cert.csr" -signkey "$SSL_DIR/key.pem" -out "$SSL_DIR/cert.pem"

# 設定檔案權限
chmod 600 "$SSL_DIR/key.pem"
chmod 644 "$SSL_DIR/cert.pem"

# 清理臨時檔案
rm "$SSL_DIR/cert.csr"

echo "SSL 憑證生成完成！"
echo "憑證位置: $SSL_DIR/cert.pem"
echo "私鑰位置: $SSL_DIR/key.pem"
echo ""
echo "注意：這是自簽名憑證，僅適用於開發和測試環境。"
echo "生產環境請使用由受信任的憑證授權機構簽發的憑證。"