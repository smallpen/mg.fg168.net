# 系統權限參考指南

## 權限總覽

系統目前共有 **35 個權限**，分為 **10 個功能模組**。經過優化整理，確保權限結構精簡且完整。

## 權限分類詳細列表

### 1. 使用者管理 (users) - 5 個權限
| 權限名稱 | 顯示名稱 | 說明 |
|---------|---------|------|
| `users.view` | 檢視使用者 | 可以檢視使用者列表和詳細資訊 |
| `users.create` | 建立使用者 | 可以建立新的使用者帳號 |
| `users.edit` | 編輯使用者 | 可以編輯使用者資訊和設定 |
| `users.delete` | 刪除使用者 | 可以刪除使用者帳號 |
| `users.assign_roles` | 指派使用者角色 | 可以為使用者指派或移除角色 |

### 2. 角色管理 (roles) - 5 個權限
| 權限名稱 | 顯示名稱 | 說明 |
|---------|---------|------|
| `roles.view` | 檢視角色 | 可以檢視角色列表和詳細資訊 |
| `roles.create` | 建立角色 | 可以建立新的角色 |
| `roles.edit` | 編輯角色 | 可以編輯角色資訊和權限設定 |
| `roles.delete` | 刪除角色 | 可以刪除角色 |
| `roles.manage_permissions` | 管理角色權限 | 可以為角色指派或移除權限 |

### 3. 權限管理 (permissions) - 4 個權限
| 權限名稱 | 顯示名稱 | 說明 |
|---------|---------|------|
| `permissions.view` | 檢視權限 | 可以檢視權限列表和詳細資訊 |
| `permissions.create` | 建立權限 | 可以建立新的權限 |
| `permissions.edit` | 編輯權限 | 可以編輯權限資訊 |
| `permissions.delete` | 刪除權限 | 可以刪除權限 |

### 4. 儀表板 (dashboard) - 2 個權限
| 權限名稱 | 顯示名稱 | 說明 |
|---------|---------|------|
| `dashboard.view` | 檢視儀表板 | 可以存取管理後台儀表板 |
| `dashboard.stats` | 檢視統計資訊 | 可以檢視系統統計資訊 |

### 5. 系統管理 (system) - 3 個權限
| 權限名稱 | 顯示名稱 | 說明 |
|---------|---------|------|
| `system.settings` | 系統設定 | 可以修改系統設定 |
| `system.logs` | 檢視系統日誌 | 可以檢視系統日誌和錯誤記錄 |
| `system.maintenance` | 系統維護 | 可以執行系統維護操作 |

### 6. 個人資料 (profile) - 2 個權限
| 權限名稱 | 顯示名稱 | 說明 |
|---------|---------|------|
| `profile.view` | 檢視個人資料 | 可以檢視自己的個人資料 |
| `profile.edit` | 編輯個人資料 | 可以編輯自己的個人資料 |

### 7. 活動日誌 (activity_logs) - 3 個權限
| 權限名稱 | 顯示名稱 | 說明 |
|---------|---------|------|
| `activity_logs.view` | 檢視活動日誌 | 可以檢視系統活動日誌 |
| `activity_logs.export` | 匯出活動日誌 | 可以匯出活動日誌資料 |
| `activity_logs.delete` | 刪除活動日誌 | 可以刪除舊的活動日誌記錄 |

### 8. 通知管理 (notifications) - 5 個權限
| 權限名稱 | 顯示名稱 | 說明 |
|---------|---------|------|
| `notifications.view` | 檢視通知 | 可以檢視系統通知 |
| `notifications.create` | 建立通知 | 可以建立和發送通知 |
| `notifications.edit` | 編輯通知 | 可以編輯通知內容和設定 |
| `notifications.delete` | 刪除通知 | 可以刪除通知記錄 |
| `notifications.send` | 發送通知 | 可以發送通知給使用者 |

### 9. 設定管理 (settings) - 4 個權限
| 權限名稱 | 顯示名稱 | 說明 |
|---------|---------|------|
| `settings.view` | 檢視設定 | 可以檢視系統設定 |
| `settings.edit` | 編輯設定 | 可以修改系統設定 |
| `settings.backup` | 備份設定 | 可以備份和還原系統設定 |
| `settings.reset` | 重置設定 | 可以重置系統設定為預設值 |

### 10. 安全管理 (security) - 3 個權限
| 權限名稱 | 顯示名稱 | 說明 |
|---------|---------|------|
| `security.view` | 檢視安全資訊 | 可以檢視安全事件和報告 |
| `security.incidents` | 管理安全事件 | 可以處理和管理安全事件 |
| `security.audit` | 安全稽核 | 可以執行安全稽核和檢查 |

## 角色權限分配

### 系統管理員 (admin)
- **權限數量**: 35 個（全部權限）
- **說明**: 擁有系統完整管理權限的管理員角色
- **用途**: 系統部署後的預設管理員角色

### 部門主管 (manager)
- **權限數量**: 18 個（部分管理權限）
- **說明**: 部門主管角色，擁有使用者管理和報告檢視權限
- **主要權限**: 使用者管理、角色檢視、通知管理、活動日誌檢視

### 一般使用者 (user)
- **權限數量**: 4 個（基本權限）
- **具體權限**:
  - `dashboard.view` - 檢視儀表板
  - `profile.view` - 檢視個人資料
  - `profile.edit` - 編輯個人資料
  - `notifications.view` - 檢視通知

## 權限檢查方式

### 在 Controller 中檢查權限
```php
// 檢查單一權限
$this->authorize('users.view');

// 檢查多個權限（任一即可）
$this->authorize(['users.view', 'users.edit']);
```

### 在 Blade 模板中檢查權限
```blade
@can('users.view')
    <a href="{{ route('admin.users.index') }}">使用者管理</a>
@endcan

@canany(['users.create', 'users.edit'])
    <button>編輯使用者</button>
@endcanany
```

### 在 Livewire 元件中檢查權限
```php
public function mount()
{
    $this->authorize('users.view');
}

public function createUser()
{
    $this->authorize('users.create');
    // 建立使用者邏輯
}
```

## 測試資料驗證

### 檢查權限是否正確建立
```sql
-- 檢查權限總數
SELECT COUNT(*) as total_permissions FROM permissions;

-- 檢查各模組權限數量
SELECT module, COUNT(*) as count 
FROM permissions 
GROUP BY module 
ORDER BY module;

-- 檢查角色權限分配
SELECT r.name, r.display_name, COUNT(rp.permission_id) as permission_count
FROM roles r 
LEFT JOIN role_permissions rp ON r.id = rp.role_id 
GROUP BY r.id, r.name, r.display_name;
```

### 檢查使用者權限
```sql
-- 檢查特定使用者的所有權限
SELECT DISTINCT p.name, p.display_name, p.module
FROM users u
JOIN user_roles ur ON u.id = ur.user_id
JOIN roles r ON ur.role_id = r.id
JOIN role_permissions rp ON r.id = rp.role_id
JOIN permissions p ON rp.permission_id = p.id
WHERE u.username = 'admin'
ORDER BY p.module, p.name;
```

## 開發注意事項

⚠️ **重要提醒**: 開發新功能時如果需要新增權限，必須同步更新 PermissionSeeder！

### 新增權限的標準流程

1. **在 PermissionSeeder.php 中新增權限定義**
2. **重新執行 Seeder 更新資料庫**
3. **更新此權限參考文檔**
4. **在相關 Controller/Livewire 中加入權限檢查**
5. **撰寫權限相關測試**

### 開發規範

1. **權限命名規範**: 使用 `模組.動作` 的格式
2. **權限檢查**: 在所有需要權限控制的地方都要加入檢查
3. **測試覆蓋**: 確保所有權限相關功能都有測試覆蓋
4. **文檔更新**: 新增權限時要同步更新此文檔
5. **向後相容**: 修改權限時要考慮現有功能的相容性
6. **Seeder 同步**: 新增功能權限時必須更新 PermissionSeeder

## 故障排除

### 常見問題
1. **權限不存在**: 檢查是否執行了最新的 PermissionSeeder
2. **角色權限不正確**: 重新執行 RoleSeeder
3. **使用者無權限**: 檢查使用者是否被指派正確的角色
4. **權限檢查失敗**: 確認權限名稱拼寫正確

### 重建權限資料
```bash
# 重建所有權限和角色資料
docker-compose exec app php artisan migrate:fresh --seed

# 只重建權限資料
docker-compose exec app php artisan db:seed --class=PermissionSeeder
docker-compose exec app php artisan db:seed --class=RoleSeeder
```

這個權限系統確保了系統的安全性和功能的細粒度控制，同時避免了權限不足導致的開發和測試問題。