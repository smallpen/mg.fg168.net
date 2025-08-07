-- MySQL 生產環境初始化腳本

-- 設定字符集
SET NAMES utf8mb4;
SET CHARACTER SET utf8mb4;

-- 建立應用程式資料庫（如果不存在）
CREATE DATABASE IF NOT EXISTS laravel_admin CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- 使用應用程式資料庫
USE laravel_admin;

-- 設定時區
SET time_zone = '+08:00';

-- 建立效能監控使用者（可選）
-- CREATE USER IF NOT EXISTS 'monitor'@'%' IDENTIFIED BY 'monitor_password';
-- GRANT PROCESS, REPLICATION CLIENT, SELECT ON *.* TO 'monitor'@'%';

-- 最佳化設定（在配置檔案中設定，這裡註解掉）
-- SET GLOBAL innodb_buffer_pool_size = 512 * 1024 * 1024;
-- SET GLOBAL query_cache_size = 0;
-- SET GLOBAL query_cache_type = 0;

-- 顯示初始化狀態
SELECT 'MySQL 生產環境初始化完成' AS status;