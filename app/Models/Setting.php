<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use App\Traits\EncryptableSettings;

/**
 * 系統設定模型
 * 
 * @property int $id
 * @property string $key 設定鍵值
 * @property mixed $value 設定值
 * @property string $category 設定分類
 * @property string $type 設定類型
 * @property array|null $options 設定選項
 * @property string|null $description 設定描述
 * @property mixed $default_value 預設值
 * @property bool $is_encrypted 是否加密儲存
 * @property bool $is_system 是否為系統設定
 * @property bool $is_public 是否為公開設定
 * @property int $sort_order 排序順序
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class Setting extends Model
{
    use HasFactory, EncryptableSettings;

    /**
     * 可批量賦值的屬性
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'key',
        'value',
        'category',
        'type',
        'options',
        'description',
        'default_value',
        'is_encrypted',
        'is_system',
        'is_public',
        'sort_order',
    ];

    /**
     * 屬性轉換
     *
     * @var array<string, string>
     */
    protected $casts = [
        'value' => 'json',
        'options' => 'json',
        'default_value' => 'json',
        'is_encrypted' => 'boolean',
        'is_system' => 'boolean',
        'is_public' => 'boolean',
        'sort_order' => 'integer',
    ];

    /**
     * 設定值存取器 - 處理加密設定的解密
     *
     * @param mixed $value
     * @return mixed
     */
    public function getValueAttribute($value)
    {
        if ($this->is_encrypted && $value !== null) {
            try {
                $decoded = json_decode($value, true);
                if (isset($decoded['encrypted'])) {
                    return json_decode(Crypt::decryptString($decoded['encrypted']), true);
                }
                // 向後相容：嘗試直接解密
                return json_decode(Crypt::decryptString($value), true);
            } catch (\Exception $e) {
                // 如果解密失敗，返回原值
                return json_decode($value, true);
            }
        }

        return json_decode($value, true);
    }

    /**
     * 設定值修改器 - 處理敏感設定的加密
     *
     * @param mixed $value
     * @return void
     */
    public function setValueAttribute($value)
    {
        if ($this->is_encrypted && $value !== null) {
            // 加密後包裝成 JSON 格式以符合資料庫欄位要求
            $encrypted = Crypt::encryptString(json_encode($value));
            $this->attributes['value'] = json_encode(['encrypted' => $encrypted]);
        } else {
            $this->attributes['value'] = json_encode($value);
        }
    }

    /**
     * 取得顯示值
     *
     * @return string
     */
    public function getDisplayValueAttribute(): string
    {
        if ($this->is_encrypted) {
            return '***';
        }

        $value = $this->value;
        
        if (is_array($value)) {
            return json_encode($value, JSON_UNESCAPED_UNICODE);
        }
        
        if (is_bool($value)) {
            return $value ? '是' : '否';
        }
        
        return (string) $value;
    }

    /**
     * 檢查設定是否已變更
     *
     * @return bool
     */
    public function getIsChangedAttribute(): bool
    {
        return $this->value !== $this->default_value;
    }

    /**
     * 取得驗證規則
     *
     * @return array
     */
    public function getValidationRulesAttribute(): array
    {
        $rules = [];
        
        // 如果 options 中有直接的 validation 規則，優先使用
        if (isset($this->options['validation'])) {
            if (is_string($this->options['validation'])) {
                return explode('|', $this->options['validation']);
            } elseif (is_array($this->options['validation'])) {
                return $this->options['validation'];
            }
        }
        
        switch ($this->type) {
            case 'text':
                $rules[] = 'string';
                if (isset($this->options['max_length'])) {
                    $rules[] = 'max:' . $this->options['max_length'];
                }
                break;
            case 'number':
                $rules[] = 'numeric';
                if (isset($this->options['min'])) {
                    $rules[] = 'min:' . $this->options['min'];
                }
                if (isset($this->options['max'])) {
                    $rules[] = 'max:' . $this->options['max'];
                }
                break;
            case 'email':
                $rules[] = 'email';
                break;
            case 'url':
                $rules[] = 'url';
                break;
            case 'boolean':
                $rules[] = 'boolean';
                break;
            case 'json':
                $rules[] = 'json';
                break;
            case 'select':
                if (isset($this->options['values'])) {
                    $rules[] = 'in:' . implode(',', array_keys($this->options['values']));
                }
                break;
        }

        if (isset($this->options['required']) && $this->options['required']) {
            $rules[] = 'required';
        }

        return $rules;
    }

    /**
     * 更新設定值
     *
     * @param mixed $value
     * @return bool
     */
    public function updateValue($value): bool
    {
        $oldValue = $this->value;
        $this->value = $value;
        $result = $this->save();

        if ($result) {
            // 清除快取
            $this->clearCache();
            
            // 記錄變更
            $this->logChange($oldValue, $value);
        }

        return $result;
    }

    /**
     * 重設為預設值
     *
     * @return bool
     */
    public function resetToDefault(): bool
    {
        return $this->updateValue($this->default_value);
    }

    /**
     * 加密設定值
     *
     * @return void
     */
    public function encrypt(): void
    {
        if (!$this->is_encrypted && $this->value !== null) {
            $this->is_encrypted = true;
            $this->save();
        }
    }

    /**
     * 解密設定值
     *
     * @return mixed
     */
    public function decrypt()
    {
        return $this->value;
    }

    /**
     * 清除相關快取
     *
     * @return void
     */
    public function clearCache(): void
    {
        Cache::forget("settings_key_{$this->key}");
        Cache::forget("settings_category_{$this->category}");
        Cache::forget('settings_all');
        Cache::forget('settings_by_category');
        Cache::forget('settings_changed');
        Cache::forget('settings_categories');
        Cache::forget('settings_types');
    }

    /**
     * 記錄設定變更
     *
     * @param mixed $oldValue
     * @param mixed $newValue
     * @return void
     */
    protected function logChange($oldValue, $newValue): void
    {
        if (class_exists(\App\Models\SettingChange::class) && auth()->check()) {
            try {
                // 使用安全服務記錄變更
                $securityService = app(\App\Services\SettingsSecurityService::class);
                $securityService->logSettingChange($this->key, $oldValue, $newValue);
            } catch (\Exception $e) {
                // 如果安全服務不可用，回退到基本記錄
                try {
                    \App\Models\SettingChange::create([
                        'setting_key' => $this->key,
                        'old_value' => $oldValue,
                        'new_value' => $newValue,
                        'changed_by' => auth()->id(),
                        'ip_address' => request()->ip(),
                        'user_agent' => request()->userAgent(),
                    ]);
                } catch (\Exception $fallbackError) {
                    // 如果記錄變更失敗，記錄錯誤但不影響主要操作
                    \Log::error('記錄設定變更失敗', [
                        'setting_key' => $this->key,
                        'error' => $fallbackError->getMessage(),
                        'user_id' => auth()->id(),
                    ]);
                }
            }
        }
    }

    /**
     * 設定備份關聯
     *
     * @return HasMany
     */
    public function backups(): HasMany
    {
        return $this->hasMany(SettingBackup::class);
    }

    /**
     * 設定變更記錄關聯
     *
     * @return HasMany
     */
    public function changes(): HasMany
    {
        return $this->hasMany(SettingChange::class, 'setting_key', 'key');
    }

    /**
     * 範圍查詢：按分類
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $category
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    /**
     * 範圍查詢：按類型
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $type
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * 範圍查詢：公開設定
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopePublic($query)
    {
        return $query->where('is_public', true);
    }

    /**
     * 範圍查詢：系統設定
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeSystem($query)
    {
        return $query->where('is_system', true);
    }

    /**
     * 範圍查詢：已變更的設定
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeChanged($query)
    {
        return $query->whereRaw('JSON_EXTRACT(value, "$") != JSON_EXTRACT(default_value, "$")');
    }
}
