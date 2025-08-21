<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * 設定變更記錄模型
 * 
 * @property int $id
 * @property string $setting_key 設定鍵值
 * @property mixed $old_value 舊值
 * @property mixed $new_value 新值
 * @property int $changed_by 變更者 ID
 * @property string|null $ip_address IP 位址
 * @property string|null $reason 變更原因
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class SettingChange extends Model
{
    use HasFactory;

    /**
     * 可批量賦值的屬性
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'setting_key',
        'old_value',
        'new_value',
        'changed_by',
        'ip_address',
        'reason',
        'user_agent',
    ];

    /**
     * 屬性轉換
     *
     * @var array<string, string>
     */
    protected $casts = [
        'old_value' => 'json',
        'new_value' => 'json',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * 設定關聯
     *
     * @return BelongsTo
     */
    public function setting(): BelongsTo
    {
        return $this->belongsTo(Setting::class, 'setting_key', 'key');
    }

    /**
     * 變更者關聯
     *
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }

    /**
     * 取得變更摘要
     *
     * @return string
     */
    public function getChangeSummaryAttribute(): string
    {
        $oldValue = $this->getFormattedValue($this->old_value);
        $newValue = $this->getFormattedValue($this->new_value);
        
        return "從 '{$oldValue}' 變更為 '{$newValue}'";
    }

    /**
     * 格式化值顯示
     *
     * @param mixed $value
     * @return string
     */
    protected function getFormattedValue($value): string
    {
        if ($value === null) {
            return '(空值)';
        }
        
        if (is_bool($value)) {
            return $value ? '是' : '否';
        }
        
        if (is_array($value)) {
            return json_encode($value, JSON_UNESCAPED_UNICODE);
        }
        
        $stringValue = (string) $value;
        
        // 如果值太長，截斷顯示
        if (strlen($stringValue) > 100) {
            return substr($stringValue, 0, 97) . '...';
        }
        
        return $stringValue;
    }

    /**
     * 檢查是否為重要變更
     *
     * @return bool
     */
    public function getIsImportantChangeAttribute(): bool
    {
        $setting = $this->setting;
        
        if (!$setting) {
            return false;
        }
        
        // 系統設定的變更都視為重要
        if ($setting->is_system) {
            return true;
        }
        
        // 安全相關設定的變更
        if (in_array($setting->category, ['security', 'auth', 'system'])) {
            return true;
        }
        
        return false;
    }

    /**
     * 範圍查詢：按設定鍵值
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $settingKey
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeBySetting($query, string $settingKey)
    {
        return $query->where('setting_key', $settingKey);
    }

    /**
     * 範圍查詢：按變更者
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $userId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByUser($query, int $userId)
    {
        return $query->where('changed_by', $userId);
    }

    /**
     * 範圍查詢：重要變更
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeImportant($query)
    {
        return $query->whereHas('setting', function ($q) {
            $q->where('is_system', true)
              ->orWhereIn('category', ['security', 'auth', 'system']);
        });
    }

    /**
     * 範圍查詢：最近的變更
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $days
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeRecent($query, int $days = 7)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }
}
