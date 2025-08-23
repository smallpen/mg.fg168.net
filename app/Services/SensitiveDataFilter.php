<?php

namespace App\Services;

use Illuminate\Support\Str;

/**
 * 敏感資料過濾服務
 * 
 * 用於過濾和遮蔽活動記錄中的敏感資訊
 * 確保敏感資料不會在日誌中明文顯示
 */
class SensitiveDataFilter
{
    /**
     * 敏感欄位關鍵字列表
     */
    protected array $sensitiveFields = [
        'password',
        'passwd',
        'pwd',
        'token',
        'secret',
        'key',
        'api_key',
        'access_token',
        'refresh_token',
        'credit_card',
        'card_number',
        'cvv',
        'ssn',
        'social_security',
        'phone',
        'mobile',
        'email',
        'address',
        'location',
        'ip_address',
        'session',
        'cookie',
        'auth',
        'private',
        'confidential'
    ];
    
    /**
     * 敏感值模式（正規表達式）
     */
    protected array $sensitivePatterns = [
        // 信用卡號碼
        '/\b\d{4}[\s-]?\d{4}[\s-]?\d{4}[\s-]?\d{4}\b/',
        // 電子郵件
        '/\b[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Z|a-z]{2,}\b/',
        // 電話號碼
        '/\b\d{3}[-.]?\d{3}[-.]?\d{4}\b/',
        // IP 位址
        '/\b\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}\b/',
        // JWT Token
        '/eyJ[A-Za-z0-9_-]*\.[A-Za-z0-9_-]*\.[A-Za-z0-9_-]*/',
        // API Key 格式
        '/[A-Za-z0-9]{32,}/',
    ];
    
    /**
     * 遮蔽字元
     */
    protected string $maskCharacter = '*';
    
    /**
     * 過濾屬性陣列中的敏感資料
     * 
     * @param array $properties 屬性陣列
     * @return array 過濾後的屬性陣列
     */
    public function filterProperties(array $properties): array
    {
        return $this->recursiveFilter($properties);
    }
    
    /**
     * 過濾字串中的敏感資料
     * 
     * @param string $text 原始文字
     * @return string 過濾後的文字
     */
    public function filterText(string $text): string
    {
        foreach ($this->sensitivePatterns as $pattern) {
            $text = preg_replace_callback($pattern, function ($matches) {
                return $this->maskValue($matches[0]);
            }, $text);
        }
        
        return $text;
    }
    
    /**
     * 檢查欄位名稱是否為敏感欄位
     * 
     * @param string $fieldName 欄位名稱
     * @return bool 是否為敏感欄位
     */
    public function isSensitiveField(string $fieldName): bool
    {
        $fieldName = strtolower($fieldName);
        
        foreach ($this->sensitiveFields as $sensitive) {
            if (str_contains($fieldName, $sensitive)) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * 檢查值是否包含敏感資料
     * 
     * @param mixed $value 值
     * @return bool 是否包含敏感資料
     */
    public function containsSensitiveData($value): bool
    {
        if (!is_string($value)) {
            return false;
        }
        
        foreach ($this->sensitivePatterns as $pattern) {
            if (preg_match($pattern, $value)) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * 遮蔽敏感值
     * 
     * @param mixed $value 原始值
     * @param int $visibleChars 可見字元數量
     * @return mixed 遮蔽後的值
     */
    public function maskValue($value, int $visibleChars = 4)
    {
        if (!is_string($value)) {
            return '[FILTERED]';
        }
        
        $length = strlen($value);
        
        // 如果值太短，完全遮蔽
        if ($length <= $visibleChars) {
            return str_repeat($this->maskCharacter, $length);
        }
        
        // 顯示前幾個字元，其餘遮蔽
        $visible = substr($value, 0, $visibleChars);
        $masked = str_repeat($this->maskCharacter, $length - $visibleChars);
        
        return $visible . $masked;
    }
    
    /**
     * 遞迴過濾陣列中的敏感資料
     * 
     * @param array $data 資料陣列
     * @return array 過濾後的資料陣列
     */
    protected function recursiveFilter(array $data): array
    {
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                // 遞迴處理陣列
                $data[$key] = $this->recursiveFilter($value);
            } elseif (is_string($value)) {
                // 檢查欄位名稱是否敏感
                if ($this->isSensitiveField($key)) {
                    $data[$key] = $this->maskValue($value);
                } elseif ($this->containsSensitiveData($value)) {
                    // 檢查值是否包含敏感資料
                    $data[$key] = $this->filterText($value);
                }
            }
        }
        
        return $data;
    }
    
    /**
     * 新增敏感欄位關鍵字
     * 
     * @param string|array $fields 欄位關鍵字
     * @return void
     */
    public function addSensitiveFields($fields): void
    {
        if (is_string($fields)) {
            $fields = [$fields];
        }
        
        $this->sensitiveFields = array_merge($this->sensitiveFields, $fields);
        $this->sensitiveFields = array_unique($this->sensitiveFields);
    }
    
    /**
     * 新增敏感值模式
     * 
     * @param string|array $patterns 正規表達式模式
     * @return void
     */
    public function addSensitivePatterns($patterns): void
    {
        if (is_string($patterns)) {
            $patterns = [$patterns];
        }
        
        $this->sensitivePatterns = array_merge($this->sensitivePatterns, $patterns);
    }
    
    /**
     * 設定遮蔽字元
     * 
     * @param string $character 遮蔽字元
     * @return void
     */
    public function setMaskCharacter(string $character): void
    {
        $this->maskCharacter = $character;
    }
    
    /**
     * 取得敏感欄位列表
     * 
     * @return array 敏感欄位列表
     */
    public function getSensitiveFields(): array
    {
        return $this->sensitiveFields;
    }
    
    /**
     * 取得敏感值模式列表
     * 
     * @return array 敏感值模式列表
     */
    public function getSensitivePatterns(): array
    {
        return $this->sensitivePatterns;
    }
    
    /**
     * 重設為預設設定
     * 
     * @return void
     */
    public function reset(): void
    {
        $this->sensitiveFields = [
            'password', 'passwd', 'pwd', 'token', 'secret', 'key',
            'api_key', 'access_token', 'refresh_token', 'credit_card',
            'card_number', 'cvv', 'ssn', 'social_security', 'phone',
            'mobile', 'email', 'address', 'location', 'ip_address',
            'session', 'cookie', 'auth', 'private', 'confidential'
        ];
        
        $this->maskCharacter = '*';
    }
}