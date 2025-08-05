# 秘密檔案目錄

此目錄用於存放生產環境的敏感資訊檔案。這些檔案不應該提交到版本控制中。

## 需要建立的檔案

在部署到生產環境之前，請手動建立以下檔案：

### mysql_root_password.txt
```
your_mysql_root_password_here
```

### mysql_password.txt  
```
your_mysql_user_password_here
```

### redis_password.txt
```
your_redis_password_here
```

### app_key.txt
```
your_laravel_app_key_here
```

## 安全注意事項

1. 這些檔案包含敏感資訊，絕對不要提交到版本控制中
2. 使用強密碼，建議至少 32 個字符
3. 定期更換密碼
4. 限制檔案權限為 600 (僅擁有者可讀寫)
5. 考慮使用專業的秘密管理工具，如 HashiCorp Vault

## 生成強密碼的方法

```bash
# 生成 32 字符的隨機密碼
openssl rand -base64 32

# 或使用 pwgen (如果已安裝)
pwgen -s 32 1
```