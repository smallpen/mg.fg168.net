<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

/**
 * 設定備份模型
 * 
 * @property int $id
 * @property string $name 備份名稱
 * @property string|null $description 備份描述
 * @property array $settings_data 設定資料
 * @property int $created_by 建立者 ID
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class SettingBackup extends Model
{
    use HasFactory;

    /**
     * 可批量賦值的屬性
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'description',
        'settings_data',
        'created_by',
        'backup_type',
        'settings_count',
        'checksum',
    ];

    /**
     * 模型事件
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($backup) {
            // 自動計算設定數量和校驗碼
            if (is_array($backup->settings_data)) {
                $backup->settings_count = count($backup->settings_data);
                $backup->checksum = md5(json_encode($backup->settings_data));
            }
        });
    }

    /**
     * 屬性轉換
     *
     * @var array<string, string>
     */
    protected $casts = [
        'settings_data' => 'json',
        'settings_count' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * 建立者關聯
     *
     * @return BelongsTo
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * 還原備份
     *
     * @return bool
     */
    public function restore(): bool
    {
        if (empty($this->settings_data)) {
            return false;
        }

        try {
            foreach ($this->settings_data as $settingData) {
                $setting = Setting::where('key', $settingData['key'])->first();
                
                if ($setting) {
                    // 直接更新值，避免觸發 updateValue 中的變更記錄
                    $setting->value = $settingData['value'];
                    $setting->save();
                } else {
                    // 如果設定不存在，建立新設定
                    Setting::create($settingData);
                }
            }

            return true;
        } catch (\Exception $e) {
            \Log::error('設定備份還原失敗', [
                'backup_id' => $this->id,
                'error' => $e->getMessage(),
            ]);
            
            return false;
        }
    }

    /**
     * 比較備份與當前設定的差異
     *
     * @return array
     */
    public function compare(): array
    {
        $currentSettings = Setting::all()->keyBy('key');
        $backupSettings = collect($this->settings_data)->keyBy('key');
        
        $differences = [
            'added' => [],      // 備份中有但當前沒有的設定
            'removed' => [],    // 當前有但備份中沒有的設定
            'modified' => [],   // 值不同的設定
            'unchanged' => [],  // 值相同的設定
        ];

        // 檢查備份中的設定
        foreach ($backupSettings as $key => $backupSetting) {
            $currentSetting = $currentSettings->get($key);
            
            if (!$currentSetting) {
                $differences['added'][] = [
                    'key' => $key,
                    'backup_value' => $backupSetting['value'],
                    'current_value' => null,
                ];
            } elseif (json_encode($currentSetting->value) !== json_encode($backupSetting['value'])) {
                $differences['modified'][] = [
                    'key' => $key,
                    'backup_value' => $backupSetting['value'],
                    'current_value' => $currentSetting->value,
                ];
            } else {
                $differences['unchanged'][] = [
                    'key' => $key,
                    'value' => $currentSetting->value,
                ];
            }
        }

        // 檢查當前設定中備份沒有的
        foreach ($currentSettings as $key => $currentSetting) {
            if (!$backupSettings->has($key)) {
                $differences['removed'][] = [
                    'key' => $key,
                    'backup_value' => null,
                    'current_value' => $currentSetting->value,
                ];
            }
        }

        return $differences;
    }

    /**
     * 下載備份檔案
     *
     * @return string
     */
    public function download(): string
    {
        $filename = "settings_backup_{$this->id}_{$this->created_at->format('Y-m-d_H-i-s')}.json";
        
        $data = [
            'backup_info' => [
                'id' => $this->id,
                'name' => $this->name,
                'description' => $this->description,
                'created_at' => $this->created_at->toISOString(),
                'created_by' => $this->creator->name ?? 'Unknown',
            ],
            'settings' => $this->settings_data,
        ];

        $content = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        
        // 儲存到臨時檔案
        Storage::disk('local')->put("temp/{$filename}", $content);
        
        return storage_path("app/temp/{$filename}");
    }

    /**
     * 取得備份大小（格式化）
     *
     * @return string
     */
    public function getFormattedSizeAttribute(): string
    {
        $size = strlen(json_encode($this->settings_data));
        
        if ($size < 1024) {
            return $size . ' B';
        } elseif ($size < 1048576) {
            return round($size / 1024, 2) . ' KB';
        } else {
            return round($size / 1048576, 2) . ' MB';
        }
    }

    /**
     * 取得設定數量
     *
     * @return int
     */
    public function getSettingsCountAttribute(): int
    {
        return count($this->settings_data);
    }

    /**
     * 範圍查詢：按建立者
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $userId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByCreator($query, int $userId)
    {
        return $query->where('created_by', $userId);
    }

    /**
     * 範圍查詢：最近的備份
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $days
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeRecent($query, int $days = 30)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }
}
