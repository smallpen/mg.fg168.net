<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection;

/**
 * 權限模板模型
 * 
 * 管理權限模板的建立、應用和管理
 */
class PermissionTemplate extends Model
{
    use HasFactory;

    /**
     * 可批量賦值的屬性
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'display_name',
        'description',
        'module',
        'permissions',
        'is_system_template',
        'is_active',
        'created_by',
    ];

    /**
     * 屬性轉換
     *
     * @var array<string, string>
     */
    protected $casts = [
        'permissions' => 'array',
        'is_system_template' => 'boolean',
        'is_active' => 'boolean',
    ];

    /**
     * 模板建立者關聯
     * 
     * @return BelongsTo
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * 取得啟用的模板
     * 
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * 取得系統模板
     * 
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeSystemTemplates($query)
    {
        return $query->where('is_system_template', true);
    }

    /**
     * 取得自定義模板
     * 
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeCustomTemplates($query)
    {
        return $query->where('is_system_template', false);
    }

    /**
     * 根據模組篩選模板
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $module
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForModule($query, string $module)
    {
        return $query->where('module', $module);
    }

    /**
     * 應用模板建立權限
     * 
     * @param string $modulePrefix 模組前綴，用於生成權限名稱
     * @return Collection 建立的權限集合
     * @throws \Exception
     */
    public function applyTemplate(string $modulePrefix = null): Collection
    {
        $createdPermissions = collect();
        $modulePrefix = $modulePrefix ?: $this->module;

        foreach ($this->permissions as $permissionData) {
            // 生成權限名稱
            $permissionName = $this->generatePermissionName($permissionData, $modulePrefix);
            
            // 檢查權限是否已存在
            if (Permission::where('name', $permissionName)->exists()) {
                continue; // 跳過已存在的權限
            }

            // 建立權限
            $permission = Permission::create([
                'name' => $permissionName,
                'display_name' => $permissionData['display_name'],
                'description' => $permissionData['description'] ?? null,
                'module' => $modulePrefix,
                'type' => $permissionData['type'],
            ]);

            $createdPermissions->push($permission);
        }

        return $createdPermissions;
    }

    /**
     * 生成權限名稱
     * 
     * @param array $permissionData
     * @param string $modulePrefix
     * @return string
     */
    private function generatePermissionName(array $permissionData, string $modulePrefix): string
    {
        $action = $permissionData['action'] ?? $permissionData['type'];
        return "{$modulePrefix}.{$action}";
    }

    /**
     * 從現有權限建立模板
     * 
     * @param Collection $permissions
     * @param array $templateData
     * @return static
     */
    public static function createFromPermissions(Collection $permissions, array $templateData): self
    {
        $permissionArray = $permissions->map(function ($permission) {
            return [
                'action' => static::extractActionFromName($permission->name),
                'display_name' => $permission->display_name,
                'description' => $permission->description,
                'type' => $permission->type,
            ];
        })->toArray();

        return static::create([
            'name' => $templateData['name'],
            'display_name' => $templateData['display_name'],
            'description' => $templateData['description'] ?? null,
            'module' => $templateData['module'],
            'permissions' => $permissionArray,
            'is_system_template' => $templateData['is_system_template'] ?? false,
            'created_by' => auth()->user()?->getKey(),
        ]);
    }

    /**
     * 從權限名稱提取動作
     * 
     * @param string $permissionName
     * @return string
     */
    private static function extractActionFromName(string $permissionName): string
    {
        $parts = explode('.', $permissionName);
        return end($parts);
    }

    /**
     * 取得模板預覽資料
     * 
     * @param string $modulePrefix
     * @return array
     */
    public function getPreview(string $modulePrefix = null): array
    {
        $modulePrefix = $modulePrefix ?: $this->module;
        $preview = [];

        foreach ($this->permissions as $permissionData) {
            $permissionName = $this->generatePermissionName($permissionData, $modulePrefix);
            $exists = Permission::where('name', $permissionName)->exists();

            $preview[] = [
                'name' => $permissionName,
                'display_name' => $permissionData['display_name'],
                'description' => $permissionData['description'] ?? '',
                'type' => $permissionData['type'],
                'exists' => $exists,
                'will_create' => !$exists,
            ];
        }

        return $preview;
    }

    /**
     * 取得權限數量
     * 
     * @return int
     */
    public function getPermissionCountAttribute(): int
    {
        return count($this->permissions);
    }

    /**
     * 檢查模板是否可以刪除
     * 
     * @return bool
     */
    public function getCanBeDeletedAttribute(): bool
    {
        // 系統模板不能刪除
        return !$this->is_system_template;
    }

    /**
     * 取得模板統計資料
     * 
     * @return array
     */
    public function getStatsAttribute(): array
    {
        return [
            'permission_count' => $this->permission_count,
            'can_be_deleted' => $this->can_be_deleted,
            'is_system' => $this->is_system_template,
            'created_at_human' => $this->created_at->diffForHumans(),
            'creator_name' => $this->creator?->name ?? '系統',
        ];
    }

    /**
     * 建立系統預設模板
     * 
     * @return void
     */
    public static function createSystemTemplates(): void
    {
        $systemTemplates = [
            [
                'name' => 'crud_basic',
                'display_name' => '基本 CRUD 權限',
                'description' => '包含檢視、建立、編輯、刪除的基本權限模板',
                'module' => 'general',
                'permissions' => [
                    ['action' => 'view', 'display_name' => '檢視', 'type' => 'view'],
                    ['action' => 'create', 'display_name' => '建立', 'type' => 'create'],
                    ['action' => 'edit', 'display_name' => '編輯', 'type' => 'edit'],
                    ['action' => 'delete', 'display_name' => '刪除', 'type' => 'delete'],
                ],
                'is_system_template' => true,
            ],
            [
                'name' => 'crud_advanced',
                'display_name' => '進階 CRUD 權限',
                'description' => '包含基本 CRUD 加上管理權限的進階模板',
                'module' => 'general',
                'permissions' => [
                    ['action' => 'view', 'display_name' => '檢視', 'type' => 'view'],
                    ['action' => 'create', 'display_name' => '建立', 'type' => 'create'],
                    ['action' => 'edit', 'display_name' => '編輯', 'type' => 'edit'],
                    ['action' => 'delete', 'display_name' => '刪除', 'type' => 'delete'],
                    ['action' => 'manage', 'display_name' => '管理', 'type' => 'manage'],
                ],
                'is_system_template' => true,
            ],
            [
                'name' => 'readonly',
                'display_name' => '唯讀權限',
                'description' => '僅包含檢視權限的唯讀模板',
                'module' => 'general',
                'permissions' => [
                    ['action' => 'view', 'display_name' => '檢視', 'type' => 'view'],
                ],
                'is_system_template' => true,
            ],
            [
                'name' => 'user_management',
                'display_name' => '使用者管理權限',
                'description' => '使用者管理相關的完整權限模板',
                'module' => 'users',
                'permissions' => [
                    ['action' => 'view', 'display_name' => '檢視使用者', 'type' => 'view'],
                    ['action' => 'create', 'display_name' => '建立使用者', 'type' => 'create'],
                    ['action' => 'edit', 'display_name' => '編輯使用者', 'type' => 'edit'],
                    ['action' => 'delete', 'display_name' => '刪除使用者', 'type' => 'delete'],
                    ['action' => 'assign_roles', 'display_name' => '指派角色', 'type' => 'manage'],
                    ['action' => 'reset_password', 'display_name' => '重設密碼', 'type' => 'manage'],
                ],
                'is_system_template' => true,
            ],
        ];

        foreach ($systemTemplates as $templateData) {
            static::updateOrCreate(
                ['name' => $templateData['name']],
                $templateData
            );
        }
    }
}
