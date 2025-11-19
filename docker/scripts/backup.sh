#!/bin/sh
# Laravel Admin 系統備份腳本

set -e

# 設定變數
BACKUP_DIR="/backup/output"
DATE=$(date +%Y%m%d_%H%M%S)
MYSQL_PASSWORD=$(cat /run/secrets/mysql_password)

# 建立備份目錄
mkdir -p "$BACKUP_DIR/mysql"
mkdir -p "$BACKUP_DIR/storage"
mkdir -p "$BACKUP_DIR/logs"

echo "開始備份程序 - $DATE"

# 備份 MySQL 資料庫
echo "備份 MySQL 資料庫..."
mysqldump -h "$MYSQL_HOST" -u "$MYSQL_USER" -p"$MYSQL_PASSWORD" \
    --single-transaction \
    --routines \
    --triggers \
    --events \
    --hex-blob \
    --opt \
    "$MYSQL_DATABASE" > "$BACKUP_DIR/mysql/laravel_admin_$DATE.sql"

# 壓縮資料庫備份
gzip "$BACKUP_DIR/mysql/laravel_admin_$DATE.sql"

echo "MySQL 資料庫備份完成"

# 備份應用程式檔案
echo "備份應用程式檔案..."
tar -czf "$BACKUP_DIR/storage/storage_$DATE.tar.gz" -C /backup/storage .

echo "應用程式檔案備份完成"

# 清理舊備份（保留 7 天）
echo "清理舊備份檔案..."
find "$BACKUP_DIR/mysql" -name "*.sql.gz" -mtime +7 -delete
find "$BACKUP_DIR/storage" -name "*.tar.gz" -mtime +7 -delete

# 記錄備份狀態
echo "備份完成 - $DATE" >> "$BACKUP_DIR/logs/backup.log"
echo "備份程序完成 - $DATE"

# 顯示備份檔案大小
echo "備份檔案資訊："
ls -lh "$BACKUP_DIR/mysql/laravel_admin_$DATE.sql.gz" 2>/dev/null || echo "MySQL 備份檔案不存在"
ls -lh "$BACKUP_DIR/storage/storage_$DATE.tar.gz" 2>/dev/null || echo "Storage 備份檔案不存在"