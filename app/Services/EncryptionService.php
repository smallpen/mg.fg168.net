<?php

namespace App\Services;

use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;

/**
 * 加密服務
 * 
 * 提供敏感資料的加密和解密功能，用於保護 API 金鑰、密碼等敏感資訊
 */
class EncryptionService
{
    /**
     * 加密敏感資料
     * 
     * @param string $value 要加密的值
     * @return string 加密後的值
     * @throws \Exception 加密失敗時拋出例外
     */
    public function encrypt(string $value): string
    {
        try {
            if (empty($value)) {
                return '';
            }
            
            return Crypt::encryptString($value);
        } catch (\Exception $e) {
            Log::error('Failed to encrypt value', [
                'error' => $e->getMessage(),
                'value_length' => strlen($value)
            ]);
            
            throw new \Exception('加密失敗：' . $e->getMessage());
        }
    }

    /**
     * 解密敏感資料
     * 
     * @param string $encryptedValue 加密的值
     * @return string 解密後的值
     * @throws \Exception 解密失敗時拋出例外
     */
    public function decrypt(string $encryptedValue): string
    {
        try {
            if (empty($encryptedValue)) {
                return '';
            }
            
            return Crypt::decryptString($encryptedValue);
        } catch (\Exception $e) {
            Log::error('Failed to decrypt value', [
                'error' => $e->getMessage(),
                'encrypted_value_length' => strlen($encryptedValue)
            ]);
            
            throw new \Exception('解密失敗：' . $e->getMessage());
        }
    }

    /**
     * 批量加密資料
     * 
     * @param array $data 要加密的資料陣列
     * @param array $encryptFields 需要加密的欄位名稱
     * @return array 加密後的資料陣列
     */
    public function encryptArray(array $data, array $encryptFields): array
    {
        $result = $data;
        
        foreach ($encryptFields as $field) {
            if (isset($result[$field]) && !empty($result[$field])) {
                $result[$field] = $this->encrypt($result[$field]);
            }
        }
        
        return $result;
    }

    /**
     * 批量解密資料
     * 
     * @param array $data 要解密的資料陣列
     * @param array $encryptFields 需要解密的欄位名稱
     * @return array 解密後的資料陣列
     */
    public function decryptArray(array $data, array $encryptFields): array
    {
        $result = $data;
        
        foreach ($encryptFields as $field) {
            if (isset($result[$field]) && !empty($result[$field])) {
                try {
                    $result[$field] = $this->decrypt($result[$field]);
                } catch (\Exception $e) {
                    // 如果解密失敗，保持原值（可能是未加密的舊資料）
                    Log::warning("Failed to decrypt field {$field}, keeping original value", [
                        'error' => $e->getMessage()
                    ]);
                }
            }
        }
        
        return $result;
    }

    /**
     * 檢查值是否已加密
     * 
     * @param string|null $value 要檢查的值
     * @return bool 是否已加密
     */
    public function isEncrypted(?string $value): bool
    {
        if (empty($value)) {
            return false;
        }
        
        try {
            Crypt::decryptString($value);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * 安全地顯示敏感資料（遮罩處理）
     * 
     * @param string $value 敏感資料
     * @param int $visibleStart 開頭顯示的字元數
     * @param int $visibleEnd 結尾顯示的字元數
     * @param string $mask 遮罩字元
     * @return string 遮罩後的字串
     */
    public function maskSensitiveData(string $value, int $visibleStart = 4, int $visibleEnd = 4, string $mask = '*'): string
    {
        if (empty($value)) {
            return '';
        }
        
        $length = strlen($value);
        
        // 如果字串太短，只顯示遮罩
        if ($length <= $visibleStart + $visibleEnd) {
            return str_repeat($mask, min($length, 8));
        }
        
        $start = substr($value, 0, $visibleStart);
        $end = substr($value, -$visibleEnd);
        $maskLength = $length - $visibleStart - $visibleEnd;
        
        return $start . str_repeat($mask, min($maskLength, 8)) . $end;
    }

    /**
     * 生成安全的 API 金鑰
     * 
     * @param int $length 金鑰長度
     * @param string $prefix 金鑰前綴
     * @return string 生成的 API 金鑰
     */
    public function generateApiKey(int $length = 32, string $prefix = ''): string
    {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $key = '';
        
        for ($i = 0; $i < $length; $i++) {
            $key .= $characters[random_int(0, strlen($characters) - 1)];
        }
        
        return $prefix . $key;
    }

    /**
     * 驗證 API 金鑰格式
     * 
     * @param string $apiKey API 金鑰
     * @param array $patterns 允許的格式模式
     * @return bool 是否符合格式
     */
    public function validateApiKeyFormat(string $apiKey, array $patterns = []): bool
    {
        if (empty($apiKey)) {
            return false;
        }
        
        // 預設的常見 API 金鑰格式
        $defaultPatterns = [
            '/^sk-[a-zA-Z0-9]{48}$/',           // OpenAI
            '/^pk_[a-zA-Z0-9_]{24,}$/',         // Stripe Publishable
            '/^sk_[a-zA-Z0-9_]{24,}$/',         // Stripe Secret
            '/^whsec_[a-zA-Z0-9+\/=]{44}$/',    // Stripe Webhook
            '/^AIza[a-zA-Z0-9_-]{35}$/',        // Google API
            '/^ya29\.[a-zA-Z0-9_-]+$/',         // Google OAuth
            '/^[a-zA-Z0-9]{32}$/',              // Generic 32 char
            '/^[a-zA-Z0-9_-]{40,}$/',           // Generic long key
        ];
        
        $allPatterns = array_merge($defaultPatterns, $patterns);
        
        foreach ($allPatterns as $pattern) {
            if (preg_match($pattern, $apiKey)) {
                return true;
            }
        }
        
        // 如果沒有匹配任何模式，檢查基本要求
        return strlen($apiKey) >= 16 && preg_match('/^[a-zA-Z0-9_\-\.]+$/', $apiKey);
    }
}