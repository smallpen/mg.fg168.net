-- 建立 Laravel Admin 資料庫
CREATE DATABASE IF NOT EXISTS laravel_admin CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- 建立使用者並授予權限
CREATE USER IF NOT EXISTS 'laravel'@'%' IDENTIFIED BY 'secret';
GRANT ALL PRIVILEGES ON laravel_admin.* TO 'laravel'@'%';

-- 設定 MySQL 配置以支援 Laravel
SET GLOBAL sql_mode = 'STRICT_TRANS_TABLES,NO_ZERO_DATE,NO_ZERO_IN_DATE,ERROR_FOR_DIVISION_BY_ZERO';

-- 重新載入權限
FLUSH PRIVILEGES;