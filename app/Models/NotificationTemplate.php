<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * 通知範本模型
 * 
 * 管理系統通知郵件範本
 */
class NotificationTemplate extends Model
{
    use HasFactory;

    /**
     * 可批量賦值的屬性
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'key',
        'name',
        'category',
        'subject',
        'content',
        'variables',
        'is_active',
        'is_system',
        'description',
    ];

    /**
     * 屬性類型轉換
     *
     * @var array<string, string>
     */
    protected $casts = [
        'variables' => 'array',
        'is_active' => 'boolean',
        'is_system' => 'boolean',
    ];

    /**
     * 範本分類常數
     */
    const CATEGORY_SYSTEM = 'system';
    const CATEGORY_USER = 'user';
    const CATEGORY_SECURITY = 'security';
    const CATEGORY_MARKETING = 'marketing';

    /**
     * 取得所有分類
     */
    public static function getCategories(): array
    {
        return [
            self::CATEGORY_SYSTEM => '系統通知',
            self::CATEGORY_USER => '使用者通知',
            self::CATEGORY_SECURITY => '安全通知',
            self::CATEGORY_MARKETING => '行銷通知',
        ];
    }

    /**
     * 範圍查詢：啟用的範本
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * 範圍查詢：系統範本
     */
    public function scopeSystem($query)
    {
        return $query->where('is_system', true);
    }

    /**
     * 範圍查詢：自訂範本
     */
    public function scopeCustom($query)
    {
        return $query->where('is_system', false);
    }

    /**
     * 範圍查詢：依分類
     */
    public function scopeByCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    /**
     * 替換範本變數
     */
    public function render(array $variables = []): array
    {
        $subject = $this->subject;
        $content = $this->content;

        foreach ($variables as $key => $value) {
            $placeholder = '{' . $key . '}';
            $subject = str_replace($placeholder, $value, $subject);
            $content = str_replace($placeholder, $value, $content);
        }

        return [
            'subject' => $subject,
            'content' => $content,
        ];
    }

    /**
     * 取得範本可用變數
     */
    public function getAvailableVariables(): array
    {
        return $this->variables ?? [];
    }

    /**
     * 檢查範本是否可以刪除
     */
    public function canBeDeleted(): bool
    {
        return !$this->is_system;
    }

    /**
     * 檢查範本是否可以編輯
     */
    public function canBeEdited(): bool
    {
        return true; // 系統範本也可以編輯，但會建立副本
    }

    /**
     * 複製範本
     */
    public function duplicate(string $newKey = null): self
    {
        $newKey = $newKey ?? $this->key . '_copy_' . time();
        
        return self::create([
            'key' => $newKey,
            'name' => $this->name . ' (副本)',
            'category' => $this->category,
            'subject' => $this->subject,
            'content' => $this->content,
            'variables' => $this->variables,
            'is_active' => false, // 副本預設為停用
            'is_system' => false, // 副本不是系統範本
            'description' => $this->description,
        ]);
    }

    /**
     * 驗證範本內容
     */
    public function validateTemplate(): array
    {
        $errors = [];

        // 檢查必要欄位
        if (empty($this->name)) {
            $errors[] = '範本名稱不能為空';
        }

        if (empty($this->subject)) {
            $errors[] = '郵件主旨不能為空';
        }

        if (empty($this->content)) {
            $errors[] = '郵件內容不能為空';
        }

        // 檢查變數格式
        if ($this->variables && !is_array($this->variables)) {
            $errors[] = '變數格式不正確';
        }

        // 檢查內容長度
        if (strlen($this->subject) > 200) {
            $errors[] = '郵件主旨過長（最多 200 字元）';
        }

        if (strlen($this->content) > 10000) {
            $errors[] = '郵件內容過長（最多 10000 字元）';
        }

        return $errors;
    }

    /**
     * 取得範本統計資訊
     */
    public static function getStatistics(): array
    {
        return [
            'total' => self::count(),
            'active' => self::active()->count(),
            'system' => self::system()->count(),
            'custom' => self::custom()->count(),
            'by_category' => self::selectRaw('category, COUNT(*) as count')
                                ->groupBy('category')
                                ->pluck('count', 'category')
                                ->toArray(),
        ];
    }
}