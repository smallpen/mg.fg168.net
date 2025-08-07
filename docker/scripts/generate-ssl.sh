#!/bin/bash

# 生成自簽 SSL 憑證腳本
# 用於開發和測試環境

SSL_DIR="docker/nginx/ssl"
CERT_FILE="$SSL_DIR/cert.pem"
KEY_FILE="$SSL_DIR/key.pem"

# 建立 SSL 目錄
mkdir -p "$SSL_DIR"

# 生成私鑰
openssl genrsa -out "$KEY_FILE" 2048

# 生成自簽憑證
openssl req -new -x509 -key "$KEY_FILE" -out "$CERT_FILE" -days 365 -subj "/C=TW/ST=Taipei/L=Taipei/O=Laravel Admin/OU=IT Department/CN=localhost"

echo "✅ SSL 憑證已生成："
echo "   憑證檔案: $CERT_FILE"
echo "   私鑰檔案: $KEY_FILE"
echo "   有效期限: 365 天"

# 設定檔案權限
chmod 600 "$KEY_FILE"
chmod 644 "$CERT_FILE"

echo "🔒 檔案權限已設定完成"