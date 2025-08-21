<?php

namespace App\Traits;

use Illuminate\Support\Facades\Crypt;
use Illuminate\Contracts\Encryption\DecryptException;

/**
 * 可加密設定特性
 * 
 * 提供設定值的加密和解密功能
 */
trait EncryptableSettings
{
    /**
     * 需要加密的設定類型
     *
     * @var array
     */
    protected static $encryptableTypes = [
        'password',
        'api_key',
        'secret',
        'token',
        'private_key',
        'database_password',
        'smtp_password',
    ];

    /**
     * 檢查設定是否需要加密
     *
     * @param string $type
     * @return bool
     */
    public function shouldEncrypt(string $type = null): bool
    {
        $type = $type ?? $this->type;
        
        // 如果明確標記為加密
        if ($this->is_encrypted) {
            return true;
        }
        
        // 根據類型判斷
        return in_array($type, static::$encryptableTypes);
    }

    /**
     * 加密設定值
     *
     * @param mixed $value
     * @return string
     */
    public function encryptValue($value): string
    {
        if ($value === null) {
            return json_encode(null);
        }
        
        try {
            $encrypted = Crypt::encryptString(json_encode($value));
            return json_encode(['encrypted' => $encrypted]);
        } catch (\Exception $e) {
            \Log::error('設定值加密失敗', [
                'setting_key' => $this->key ?? 'unknown',
                'error' => $e->getMessage(),
            ]);
            
            // 如果加密失敗，返回原始 JSON
            return json_encode($value);
        }
    }

    /**
     * 解密設定值
     *
     * @param string $encryptedValue
     * @return mixed
     */
    public function decryptValue(string $encryptedValue)
    {
        if (empty($encryptedValue)) {
            return null;
        }
        
        try {
            $decoded = json_decode($encryptedValue, true);
            
            if (isset($decoded['encrypted'])) {
                $decrypted = Crypt::decryptString($decoded['encrypted']);
                return json_decode($decrypted, true);
            }
            
            // 向後相容：嘗試直接解密
            $decrypted = Crypt::decryptString($encryptedValue);
            return json_decode($decrypted, true);
        } catch (DecryptException $e) {
            \Log::warning('設定值解密失敗，嘗試作為普通 JSON 解析', [
                'setting_key' => $this->key ?? 'unknown',
                'error' => $e->getMessage(),
            ]);
            
            // 如果解密失敗，嘗試作為普通 JSON 解析
            return json_decode($encryptedValue, true);
        } catch (\Exception $e) {
            \Log::error('設定值處理失敗', [
                'setting_key' => $this->key ?? 'unknown',
                'error' => $e->getMessage(),
            ]);
            
            return null;
        }
    }

    /**
     * 安全地更新加密設定
     *
     * @param mixed $value
     * @param bool $forceEncrypt
     * @return bool
     */
    public function updateEncryptedValue($value, bool $forceEncrypt = false): bool
    {
        $oldValue = $this->value;
        
        // 決定是否需要加密
        $shouldEncrypt = $forceEncrypt || $this->shouldEncrypt();
        
        if ($shouldEncrypt && !$this->is_encrypted) {
            $this->is_encrypted = true;
        }
        
        $this->value = $value;
        $result = $this->save();
        
        if ($result) {
            $this->clearCache();
            $this->logChange($oldValue, $value);
        }
        
        return $result;
    }

    /**
     * 切換加密狀態
     *
     * @param bool $encrypt
     * @return bool
     */
    public function toggleEncryption(bool $encrypt): bool
    {
        if ($this->is_encrypted === $encrypt) {
            return true; // 已經是目標狀態
        }
        
        $currentValue = $this->value;
        $this->is_encrypted = $encrypt;
        
        // 重新設定值以觸發加密/解密
        $this->value = $currentValue;
        
        return $this->save();
    }

    /**
     * 驗證加密設定的完整性
     *
     * @return bool
     */
    public function validateEncryption(): bool
    {
        if (!$this->is_encrypted) {
            return true;
        }
        
        try {
            // 嘗試解密並重新加密，檢查是否一致
            $decrypted = $this->value;
            $reencrypted = $this->encryptValue($decrypted);
            
            return !empty($reencrypted);
        } catch (\Exception $e) {
            \Log::error('加密設定驗證失敗', [
                'setting_key' => $this->key,
                'error' => $e->getMessage(),
            ]);
            
            return false;
        }
    }

    /**
     * 取得遮罩後的顯示值
     *
     * @param int $visibleChars
     * @return string
     */
    public function getMaskedValue(int $visibleChars = 4): string
    {
        if (!$this->is_encrypted) {
            return $this->display_value;
        }
        
        $value = $this->value;
        
        if (is_string($value) && strlen($value) > $visibleChars) {
            return substr($value, 0, $visibleChars) . str_repeat('*', min(8, strlen($value) - $visibleChars));
        }
        
        return str_repeat('*', 8);
    }
}