#!/bin/sh

# Redis 啟動腳本 - 動態設定密碼

set -e

# 檢查 secrets 檔案是否存在
if [ -f "/run/secrets/redis_password" ]; then
    REDIS_PASSWORD=$(cat /run/secrets/redis_password)
    echo "使用 Docker secrets 中的 Redis 密碼"
else
    echo "警告: 找不到 Redis 密碼 secrets 檔案"
    exit 1
fi

# 建立臨時配置檔案
cp /usr/local/etc/redis/redis.conf /tmp/redis.conf

# 確保配置檔案以換行符結尾，然後加入密碼設定
echo "" >> /tmp/redis.conf
echo "# 動態設定的密碼" >> /tmp/redis.conf
echo "requirepass $REDIS_PASSWORD" >> /tmp/redis.conf

# 啟動 Redis 伺服器
exec redis-server /tmp/redis.conf