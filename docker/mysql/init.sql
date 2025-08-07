-- MySQL 初始化腳本
-- 確保資料庫和使用者正確建立

-- 建立資料庫（如果不存在）
CREATE DATABASE IF NOT EXISTS mg_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- 建立使用者（如果不存在）
CREATE USER IF NOT EXISTS 'db_user'@'%' IDENTIFIED BY '1qaz1234';

-- 授予權限
GRANT ALL PRIVILEGES ON mg_db.* TO 'db_user'@'%';

-- 重新載入權限
FLUSH PRIVILEGES;

-- 顯示建立的使用者（用於除錯）
SELECT User, Host FROM mysql.user WHERE User = 'db_user';