# 安全監控標記按鈕修復報告

## 問題描述

用戶反映按下安全監控頁面的「標記為已處理」按鈕時出現以下錯誤：
```
Livewire\Exceptions\MethodNotFoundException
Unable to call component method. Public method [resolveIncident] not found on component
```

## 問題分析

### 根本原因
1. **方法名稱衝突**：Livewire 元件中同時存在屬性 `$showResolveConfirm` 和方法 `showResolveConfirm()`，導致 Livewire 無法正確識別方法
2. **視圖調用錯誤**：視圖中調用了不存在的 `resolveIncident` 方法
3. **認證問題**：`auth()->id()` 在某些情況下返回 0 或字串，導致資料庫外鍵約束失敗

### 錯誤日誌
```
Alpine Expression Error: $wire.showResolveConfirm is not a function
TypeError: $wire.showResolveConfirm is not a function
```

```
SQLSTATE[23000]: Integrity constraint violation: 1452 Cannot add or update a child row: 
a foreign key constraint fails (`laravel_admin`.`security_incidents`, 
CONSTRAINT `security_incidents_resolved_by_foreign` FOREIGN KEY (`resolved_by`) 
REFERENCES `users` (`id`) ON DELETE SET NULL)
```

## 修復措施

### 1. 解決方法名稱衝突
**檔案**: `app/Livewire/Admin/Activities/SecurityMonitor.php`

**修改前**:
```php
public bool $showResolveConfirm = false;

public function showResolveConfirm(int $incidentId): void
{
    $this->incidentToResolve = $incidentId;
    $this->showResolveConfirm = true;
}
```

**修改後**:
```php
public bool $showResolveConfirm = false;

public function showResolveDialog(int $incidentId): void
{
    $this->incidentToResolve = $incidentId;
    $this->showResolveConfirm = true;
}
```

### 2. 更新視圖調用
**檔案**: `resources/views/livewire/admin/activities/security-monitor.blade.php`

**修改前**:
```blade
wire:click="showResolveConfirm({{ $incident->id }})"
```

**修改後**:
```blade
wire:click="showResolveDialog({{ $incident->id }})"
```

### 3. 增強認證檢查
**檔案**: `app/Livewire/Admin/Activities/SecurityMonitor.php`

**修改前**:
```php
$incident->update([
    'resolved' => true,
    'resolved_by' => auth()->id(),
    'resolved_at' => now(),
    'resolution_notes' => '透過安全監控介面標記為已處理'
]);
```

**修改後**:
```php
// 詳細的認證檢查和日誌記錄
$authCheck = auth()->check();
$userId = auth()->id();
$user = auth()->user();

\Log::info('認證狀態檢查', [
    'auth_check' => $authCheck,
    'user_id' => $userId,
    'user_exists' => !!$user,
    'user_username' => $user ? $user->username : null,
    'incident_id' => $this->incidentToResolve
]);

if (!$authCheck) {
    throw new \Exception('使用者未登入');
}

if (!$userId) {
    throw new \Exception('無法取得使用者 ID');
}

$incident->update([
    'resolved' => true,
    'resolved_by' => (int) $userId,
    'resolved_at' => now(),
    'resolution_notes' => '透過安全監控介面標記為已處理'
]);
```

## 測試結果

### 修復前
- ❌ 點擊標記按鈕出現 `MethodNotFoundException`
- ❌ 確認對話框無法顯示
- ❌ 無法標記事件為已處理

### 修復後
- ✅ 點擊標記按鈕正常顯示確認對話框
- ✅ 確認對話框正確顯示和關閉
- ⚠️ 資料庫更新仍有問題（認證相關）

## 當前狀態

### 已解決的問題
1. ✅ 方法名稱衝突已解決
2. ✅ 確認對話框可以正常顯示
3. ✅ 視圖調用已修正

### 待解決的問題
1. ⚠️ 認證狀態檢查日誌未出現，可能有快取或其他問題
2. ⚠️ `resolved_by` 仍然為 0，導致外鍵約束失敗
3. ⚠️ 事件無法成功標記為已處理

## 後續建議

### 短期解決方案
1. 檢查 Livewire 快取和配置
2. 驗證使用者認證狀態
3. 考慮移除 `resolved_by` 的外鍵約束或設為可空

### 長期改進
1. 添加更完善的錯誤處理
2. 實作前端狀態同步機制
3. 增加單元測試覆蓋

## 相關檔案

### 修改的檔案
- `app/Livewire/Admin/Activities/SecurityMonitor.php`
- `resources/views/livewire/admin/activities/security-monitor.blade.php`

### 測試檔案
- 安全監控頁面：`http://localhost/admin/activities/security`
- 測試用安全事件 ID：10

## 技術細節

### Livewire 3.0 注意事項
- 屬性和方法不能同名
- 使用 `dispatch()` 而非 `emit()` 發送事件
- 確保正確的命名空間 `App\Livewire`

### 資料庫約束
- `security_incidents.resolved_by` 必須是有效的 `users.id`
- 外鍵約束：`FOREIGN KEY (resolved_by) REFERENCES users (id) ON DELETE SET NULL`

---

**修復時間**: 2025-08-31 20:40
**修復狀態**: 部分完成（UI 修復完成，資料庫更新待解決）
**測試環境**: Docker + Laravel + Livewire 3.0