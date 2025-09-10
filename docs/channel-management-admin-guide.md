# 通路管理系統管理員操作指南

## 目錄

1. [系統架構概述](#系統架構概述)
2. [安裝與部署](#安裝與部署)
3. [系統配置](#系統配置)
4. [使用者與權限管理](#使用者與權限管理)
5. [資料庫管理](#資料庫管理)
6. [系統監控](#系統監控)
7. [備份與還原](#備份與還原)
8. [故障排除](#故障排除)
9. [效能優化](#效能優化)
10. [安全管理](#安全管理)

## 系統架構概述

### 技術堆疊

- **後端框架**：Laravel 10+
- **前端框架**：Livewire 3.0 + Alpine.js
- **資料庫**：MySQL 8.0+
- **快取系統**：Redis
- **容器化**：Docker + Docker Compose
- **網頁伺服器**：Nginx
- **PHP 版本**：PHP 8.2+

### 系統組件

```
┌─────────────────────────────────────────────────────────────┐
│                    通路管理系統架構                              │
├─────────────────────────────────────────────────────────────┤
│  前端層 (Frontend Layer)                                     │
│  ┌─────────────────┐  ┌─────────────────┐  ┌─────────────────┐ │
│  │   代理管理介面    │  │   玩家管理介面    │  │   點數管理介面    │ │
│  │  (Livewire)     │  │  (Livewire)     │  │  (Livewire)     │ │
│  └─────────────────┘  └─────────────────┘  └─────────────────┘ │
├─────────────────────────────────────────────────────────────┤
│  應用層 (Application Layer)                                  │
│  ┌─────────────────┐  ┌─────────────────┐  ┌─────────────────┐ │
│  │   代理服務       │  │   玩家服務       │  │   點數服務       │ │
│  │  AgentService   │  │ PlayerService   │  │ PointService    │ │
│  └─────────────────┘  └─────────────────┘  └─────────────────┘ │
├─────────────────────────────────────────────────────────────┤
│  領域層 (Domain Layer)                                       │
│  ┌─────────────────┐  ┌─────────────────┐  ┌─────────────────┐ │
│  │   代理模型       │  │   玩家模型       │  │   點數模型       │ │
│  │   Agent Model   │  │  Player Model   │  │  Point Model    │ │
│  └─────────────────┘  └─────────────────┘  └─────────────────┘ │
├─────────────────────────────────────────────────────────────┤
│  基礎設施層 (Infrastructure Layer)                            │
│  ┌─────────────────┐  ┌─────────────────┐  ┌─────────────────┐ │
│  │   資料庫存儲     │  │   快取系統       │  │   日誌系統       │ │
│  │   MySQL         │  │   Redis         │  │   Laravel Log   │ │
│  └─────────────────┘  └─────────────────┘  └─────────────────┘ │
└─────────────────────────────────────────────────────────────┘
```

## 安裝與部署

### 系統需求

**硬體需求**：
- CPU：4 核心以上
- 記憶體：8GB 以上
- 硬碟：50GB 以上可用空間
- 網路：穩定的網際網路連線

**軟體需求**：
- Docker 20.10+
- Docker Compose 2.0+
- Git 2.30+

### 安裝步驟

#### 1. 取得原始碼

```bash
# 複製專案
git clone [repository-url] channel-management
cd channel-management

# 切換到正確分支
git checkout main
```

#### 2. 環境配置

```bash
# 複製環境配置檔案
cp .env.example .env

# 編輯環境變數
nano .env
```

**重要環境變數**：

```env
# 應用程式設定
APP_NAME="通路管理系統"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.com

# 資料庫設定
DB_CONNECTION=mysql
DB_HOST=db
DB_PORT=3306
DB_DATABASE=channel_management
DB_USERNAME=channel_user
DB_PASSWORD=secure_password

# Redis 設定
REDIS_HOST=redis
REDIS_PASSWORD=redis_password
REDIS_PORT=6379

# 郵件設定
MAIL_MAILER=smtp
MAIL_HOST=your-smtp-host
MAIL_PORT=587
MAIL_USERNAME=your-email
MAIL_PASSWORD=your-password
```

#### 3. 建置與啟動

```bash
# 建置 Docker 映像
docker-compose build

# 啟動服務
docker-compose up -d

# 安裝 PHP 依賴
docker-compose exec app composer install --optimize-autoloader --no-dev

# 生成應用程式金鑰
docker-compose exec app php artisan key:generate

# 執行資料庫遷移
docker-compose exec app php artisan migrate

# 執行 Seeder
docker-compose exec app php artisan db:seed

# 建立符號連結
docker-compose exec app php artisan storage:link

# 清除快取
docker-compose exec app php artisan config:cache
docker-compose exec app php artisan route:cache
docker-compose exec app php artisan view:cache
```

#### 4. 驗證安裝

```bash
# 檢查服務狀態
docker-compose ps

# 檢查應用程式健康狀態
curl -f http://localhost/health || echo "Health check failed"

# 檢查資料庫連線
docker-compose exec app php artisan migrate:status
```

## 系統配置

### 通路管理專用配置

#### 1. 權限配置

```bash
# 確保通路管理權限已建立
docker-compose exec app php artisan db:seed --class=ChannelPermissionSeeder
```

#### 2. 快取配置

編輯 `config/cache.php`：

```php
// 通路管理專用快取配置
'channel_management' => [
    'driver' => 'redis',
    'connection' => 'default',
    'prefix' => 'channel_mgmt:',
],
```

#### 3. 日誌配置

編輯 `config/logging.php`：

```php
'channels' => [
    // 通路管理專用日誌頻道
    'channel_management' => [
        'driver' => 'daily',
        'path' => storage_path('logs/channel-management.log'),
        'level' => env('LOG_LEVEL', 'debug'),
        'days' => 30,
    ],
],
```

### 效能配置

#### 1. 資料庫索引

```sql
-- 代理表索引
CREATE INDEX idx_agents_parent_id ON agents(parent_id);
CREATE INDEX idx_agents_level ON agents(level);
CREATE INDEX idx_agents_prefix ON agents(prefix);
CREATE INDEX idx_agents_is_active ON agents(is_active);

-- 玩家表索引
CREATE INDEX idx_players_agent_id ON players(agent_id);
CREATE INDEX idx_players_is_active ON players(is_active);

-- 點數交易表索引
CREATE INDEX idx_point_transactions_agent_id ON point_transactions(agent_id);
CREATE INDEX idx_point_transactions_player_id ON point_transactions(player_id);
CREATE INDEX idx_point_transactions_type ON point_transactions(type);
CREATE INDEX idx_point_transactions_created_at ON point_transactions(created_at);
```

#### 2. Redis 配置

```bash
# Redis 記憶體配置
redis-cli CONFIG SET maxmemory 2gb
redis-cli CONFIG SET maxmemory-policy allkeys-lru

# 持久化配置
redis-cli CONFIG SET save "900 1 300 10 60 10000"
```

## 使用者與權限管理

### 建立管理員帳號

```bash
# 使用 Artisan 命令建立管理員
docker-compose exec app php artisan make:admin-user

# 或使用 Tinker
docker-compose exec app php artisan tinker
```

在 Tinker 中執行：

```php
// 建立管理員使用者
$admin = User::create([
    'username' => 'admin',
    'name' => '系統管理員',
    'email' => 'admin@example.com',
    'password' => Hash::make('secure_password'),
    'is_active' => true,
]);

// 指派管理員角色
$adminRole = Role::where('name', 'admin')->first();
$admin->roles()->attach($adminRole);
```

### 權限管理

#### 檢查權限設定

```bash
# 列出所有權限
docker-compose exec app php artisan permission:list

# 檢查角色權限
docker-compose exec app php artisan role:show admin

# 檢查使用者權限
docker-compose exec app php artisan user:permissions admin
```

#### 權限故障排除

```bash
# 重建權限快取
docker-compose exec app php artisan permission:cache-reset

# 重新同步權限
docker-compose exec app php artisan db:seed --class=PermissionSeeder
```

## 資料庫管理

### 日常維護

#### 1. 資料庫備份

```bash
# 建立備份腳本
cat > scripts/backup-database.sh << 'EOF'
#!/bin/bash
DATE=$(date +%Y%m%d_%H%M%S)
BACKUP_DIR="/var/backups/channel-management"
mkdir -p $BACKUP_DIR

# 備份資料庫
docker-compose exec -T db mysqldump \
  -u$DB_USERNAME -p$DB_PASSWORD \
  $DB_DATABASE > $BACKUP_DIR/database_$DATE.sql

# 壓縮備份檔案
gzip $BACKUP_DIR/database_$DATE.sql

# 清理舊備份（保留30天）
find $BACKUP_DIR -name "database_*.sql.gz" -mtime +30 -delete

echo "Database backup completed: database_$DATE.sql.gz"
EOF

chmod +x scripts/backup-database.sh
```

#### 2. 資料庫優化

```bash
# 分析表格
docker-compose exec db mysql -u$DB_USERNAME -p$DB_PASSWORD -e "
ANALYZE TABLE agents, players, point_transactions;
"

# 優化表格
docker-compose exec db mysql -u$DB_USERNAME -p$DB_PASSWORD -e "
OPTIMIZE TABLE agents, players, point_transactions;
"
```

#### 3. 資料完整性檢查

```bash
# 執行資料完整性檢查
docker-compose exec app php artisan channel:check-integrity

# 修復資料不一致
docker-compose exec app php artisan channel:repair-data
```

### 資料遷移

#### 版本升級遷移

```bash
# 檢查待執行的遷移
docker-compose exec app php artisan migrate:status

# 執行遷移（生產環境）
docker-compose exec app php artisan migrate --force

# 回滾遷移（如需要）
docker-compose exec app php artisan migrate:rollback --step=1
```

## 系統監控

### 效能監控

#### 1. 應用程式監控

```bash
# 檢查應用程式狀態
docker-compose exec app php artisan about

# 檢查佇列狀態
docker-compose exec app php artisan queue:monitor

# 檢查快取狀態
docker-compose exec app php artisan cache:table
```

#### 2. 資料庫監控

```sql
-- 檢查資料庫大小
SELECT 
    table_schema AS 'Database',
    ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) AS 'Size (MB)'
FROM information_schema.tables 
WHERE table_schema = 'channel_management'
GROUP BY table_schema;

-- 檢查表格大小
SELECT 
    table_name AS 'Table',
    ROUND(((data_length + index_length) / 1024 / 1024), 2) AS 'Size (MB)',
    table_rows AS 'Rows'
FROM information_schema.TABLES 
WHERE table_schema = 'channel_management'
ORDER BY (data_length + index_length) DESC;

-- 檢查慢查詢
SELECT * FROM mysql.slow_log 
WHERE start_time > DATE_SUB(NOW(), INTERVAL 1 HOUR)
ORDER BY start_time DESC;
```

#### 3. 系統資源監控

```bash
# 檢查容器資源使用
docker stats

# 檢查磁碟使用
df -h

# 檢查記憶體使用
free -h

# 檢查 CPU 使用
top -p $(pgrep -d',' -f 'php-fpm|nginx|mysql|redis')
```

### 日誌監控

#### 1. 應用程式日誌

```bash
# 檢視即時日誌
docker-compose logs -f app

# 檢視通路管理專用日誌
tail -f storage/logs/channel-management.log

# 檢視錯誤日誌
tail -f storage/logs/laravel.log | grep ERROR
```

#### 2. 系統日誌分析

```bash
# 分析錯誤頻率
grep -c "ERROR" storage/logs/laravel-$(date +%Y-%m-%d).log

# 分析慢查詢
grep "slow query" storage/logs/laravel-$(date +%Y-%m-%d).log

# 分析使用者活動
grep "channel_management" storage/logs/laravel-$(date +%Y-%m-%d).log
```

## 備份與還原

### 完整備份策略

#### 1. 自動備份腳本

```bash
cat > scripts/full-backup.sh << 'EOF'
#!/bin/bash
set -e

DATE=$(date +%Y%m%d_%H%M%S)
BACKUP_DIR="/var/backups/channel-management"
mkdir -p $BACKUP_DIR

echo "Starting full backup at $(date)"

# 1. 備份資料庫
echo "Backing up database..."
docker-compose exec -T db mysqldump \
  -u$DB_USERNAME -p$DB_PASSWORD \
  --single-transaction --routines --triggers \
  $DB_DATABASE > $BACKUP_DIR/database_$DATE.sql

# 2. 備份應用程式檔案
echo "Backing up application files..."
tar -czf $BACKUP_DIR/app_files_$DATE.tar.gz \
  --exclude='storage/logs/*' \
  --exclude='storage/framework/cache/*' \
  --exclude='storage/framework/sessions/*' \
  --exclude='storage/framework/views/*' \
  .

# 3. 備份環境配置
echo "Backing up configuration..."
cp .env $BACKUP_DIR/env_$DATE.backup

# 4. 壓縮資料庫備份
gzip $BACKUP_DIR/database_$DATE.sql

# 5. 清理舊備份
find $BACKUP_DIR -name "*_*.sql.gz" -mtime +7 -delete
find $BACKUP_DIR -name "*_*.tar.gz" -mtime +7 -delete
find $BACKUP_DIR -name "*_*.backup" -mtime +7 -delete

echo "Full backup completed at $(date)"
echo "Files created:"
echo "  - database_$DATE.sql.gz"
echo "  - app_files_$DATE.tar.gz"
echo "  - env_$DATE.backup"
EOF

chmod +x scripts/full-backup.sh
```

#### 2. 設定定時備份

```bash
# 編輯 crontab
crontab -e

# 添加定時任務
# 每日凌晨 2 點執行完整備份
0 2 * * * /path/to/channel-management/scripts/full-backup.sh >> /var/log/backup.log 2>&1

# 每小時執行資料庫備份
0 * * * * /path/to/channel-management/scripts/backup-database.sh >> /var/log/backup.log 2>&1
```

### 災難恢復

#### 1. 資料庫還原

```bash
# 停止應用程式
docker-compose stop app

# 還原資料庫
gunzip -c /var/backups/channel-management/database_YYYYMMDD_HHMMSS.sql.gz | \
docker-compose exec -T db mysql -u$DB_USERNAME -p$DB_PASSWORD $DB_DATABASE

# 重啟應用程式
docker-compose start app

# 清除快取
docker-compose exec app php artisan cache:clear
docker-compose exec app php artisan config:cache
```

#### 2. 完整系統還原

```bash
# 停止所有服務
docker-compose down

# 還原應用程式檔案
tar -xzf /var/backups/channel-management/app_files_YYYYMMDD_HHMMSS.tar.gz

# 還原環境配置
cp /var/backups/channel-management/env_YYYYMMDD_HHMMSS.backup .env

# 重新啟動服務
docker-compose up -d

# 還原資料庫
gunzip -c /var/backups/channel-management/database_YYYYMMDD_HHMMSS.sql.gz | \
docker-compose exec -T db mysql -u$DB_USERNAME -p$DB_PASSWORD $DB_DATABASE

# 重建快取
docker-compose exec app php artisan config:cache
docker-compose exec app php artisan route:cache
docker-compose exec app php artisan view:cache
```

## 故障排除

### 常見問題診斷

#### 1. 應用程式無法啟動

**症狀**：網站無法存取，返回 500 錯誤

**診斷步驟**：

```bash
# 檢查容器狀態
docker-compose ps

# 檢查應用程式日誌
docker-compose logs app

# 檢查 PHP 錯誤
docker-compose exec app php artisan about

# 檢查檔案權限
docker-compose exec app ls -la storage/
```

**常見解決方案**：

```bash
# 修復檔案權限
docker-compose exec app chown -R www-data:www-data storage bootstrap/cache
docker-compose exec app chmod -R 775 storage bootstrap/cache

# 重新生成金鑰
docker-compose exec app php artisan key:generate

# 清除快取
docker-compose exec app php artisan cache:clear
docker-compose exec app php artisan config:clear
```

#### 2. 資料庫連線問題

**症狀**：SQLSTATE[HY000] [2002] Connection refused

**診斷步驟**：

```bash
# 檢查資料庫容器
docker-compose logs db

# 測試資料庫連線
docker-compose exec app php artisan migrate:status

# 檢查網路連線
docker-compose exec app ping db
```

**解決方案**：

```bash
# 重啟資料庫服務
docker-compose restart db

# 檢查資料庫配置
docker-compose exec db mysql -u$DB_USERNAME -p$DB_PASSWORD -e "SELECT 1"

# 重建網路
docker-compose down
docker-compose up -d
```

#### 3. 點數計算錯誤

**症狀**：點數總和不一致，代理或玩家點數異常

**診斷步驟**：

```bash
# 執行點數稽核
docker-compose exec app php artisan channel:audit-points

# 檢查點數交易記錄
docker-compose exec app php artisan tinker
```

在 Tinker 中執行：

```php
// 檢查點數總和
$totalAgentPoints = Agent::sum('total_points');
$totalPlayerPoints = Player::sum('points');
$totalTransactions = PointTransaction::sum('amount');

echo "Agent Points: $totalAgentPoints\n";
echo "Player Points: $totalPlayerPoints\n";
echo "Transaction Sum: $totalTransactions\n";

// 檢查特定代理的點數一致性
$agent = Agent::find(1);
$allocatedToChildren = $agent->children->sum('total_points');
$allocatedToPlayers = $agent->players->sum('points');
$totalAllocated = $allocatedToChildren + $allocatedToPlayers;

echo "Agent {$agent->name}:\n";
echo "  Allocated Points: {$agent->allocated_points}\n";
echo "  Calculated Allocated: $totalAllocated\n";
echo "  Difference: " . ($agent->allocated_points - $totalAllocated) . "\n";
```

**修復方案**：

```bash
# 重新計算點數
docker-compose exec app php artisan channel:recalculate-points

# 修復資料不一致
docker-compose exec app php artisan channel:repair-points
```

### 效能問題診斷

#### 1. 頁面載入緩慢

**診斷工具**：

```bash
# 啟用查詢日誌
docker-compose exec app php artisan debugbar:publish

# 分析慢查詢
docker-compose exec db mysql -u$DB_USERNAME -p$DB_PASSWORD -e "
SET GLOBAL slow_query_log = 'ON';
SET GLOBAL long_query_time = 1;
SHOW VARIABLES LIKE 'slow_query_log%';
"

# 檢查快取命中率
docker-compose exec redis redis-cli info stats | grep keyspace
```

**優化方案**：

```bash
# 優化資料庫查詢
docker-compose exec app php artisan optimize

# 啟用 OPcache
docker-compose exec app php -m | grep -i opcache

# 調整快取配置
docker-compose exec app php artisan config:cache
```

## 效能優化

### 資料庫優化

#### 1. 查詢優化

```sql
-- 分析查詢執行計畫
EXPLAIN SELECT * FROM agents WHERE parent_id = 1;

-- 優化索引
CREATE INDEX idx_agents_parent_level ON agents(parent_id, level);
CREATE INDEX idx_players_agent_active ON players(agent_id, is_active);

-- 分析表格統計
ANALYZE TABLE agents, players, point_transactions;
```

#### 2. 連線池優化

編輯 `config/database.php`：

```php
'mysql' => [
    // ... 其他配置
    'options' => [
        PDO::ATTR_PERSISTENT => true,
        PDO::ATTR_EMULATE_PREPARES => false,
    ],
    'pool' => [
        'min_connections' => 5,
        'max_connections' => 20,
        'connect_timeout' => 10,
        'wait_timeout' => 3,
        'heartbeat' => -1,
        'max_idle_time' => 60,
    ],
],
```

### 應用程式優化

#### 1. 快取策略

```php
// 在 AppServiceProvider 中配置
public function boot()
{
    // 代理樹狀結構快取
    Cache::macro('agentTree', function ($rootId = null) {
        return Cache::remember("agent_tree_{$rootId}", 3600, function () use ($rootId) {
            return Agent::with('children.children')
                ->where('parent_id', $rootId)
                ->get();
        });
    });
    
    // 點數統計快取
    Cache::macro('pointStats', function () {
        return Cache::remember('point_statistics', 1800, function () {
            return [
                'total_agent_points' => Agent::sum('total_points'),
                'total_player_points' => Player::sum('points'),
                'total_transactions' => PointTransaction::count(),
            ];
        });
    });
}
```

#### 2. 佇列優化

```bash
# 設定佇列工作者
docker-compose exec app php artisan queue:work --sleep=3 --tries=3 --max-time=3600

# 監控佇列狀態
docker-compose exec app php artisan queue:monitor
```

## 安全管理

### 安全配置

#### 1. 防火牆設定

```bash
# 只允許必要的埠號
ufw allow 22/tcp    # SSH
ufw allow 80/tcp    # HTTP
ufw allow 443/tcp   # HTTPS
ufw deny 3306/tcp   # MySQL (只允許內部存取)
ufw deny 6379/tcp   # Redis (只允許內部存取)
ufw enable
```

#### 2. SSL/TLS 配置

```nginx
# Nginx SSL 配置
server {
    listen 443 ssl http2;
    server_name your-domain.com;
    
    ssl_certificate /etc/ssl/certs/your-domain.crt;
    ssl_certificate_key /etc/ssl/private/your-domain.key;
    
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers ECDHE-RSA-AES256-GCM-SHA512:DHE-RSA-AES256-GCM-SHA512;
    ssl_prefer_server_ciphers off;
    
    # HSTS
    add_header Strict-Transport-Security "max-age=63072000" always;
    
    # 其他安全標頭
    add_header X-Frame-Options DENY;
    add_header X-Content-Type-Options nosniff;
    add_header X-XSS-Protection "1; mode=block";
}
```

#### 3. 應用程式安全

```php
// 在 config/app.php 中
'debug' => env('APP_DEBUG', false),

// 在 .env 中
APP_DEBUG=false
APP_ENV=production

// 隱藏敏感資訊
LOG_LEVEL=warning
```

### 安全監控

#### 1. 入侵檢測

```bash
# 監控登入失敗
grep "authentication failure" /var/log/auth.log

# 監控異常存取
tail -f storage/logs/laravel.log | grep -E "(403|401|419)"

# 檢查檔案完整性
find . -name "*.php" -exec md5sum {} \; > checksums.md5
md5sum -c checksums.md5
```

#### 2. 安全稽核

```bash
# 執行安全檢查
docker-compose exec app php artisan security:audit

# 檢查權限設定
docker-compose exec app php artisan permission:audit

# 檢查使用者活動
docker-compose exec app php artisan user:activity-report
```

---

**版本**：1.0  
**更新日期**：2024年12月  
**適用系統版本**：通路管理系統 v1.0+

**注意事項**：
- 本指南適用於生產環境部署
- 執行任何維護操作前請先備份
- 定期檢查系統安全更新
- 遵循最小權限原則
- 保持監控和日誌記錄