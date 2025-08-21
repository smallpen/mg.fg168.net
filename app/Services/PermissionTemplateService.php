<?php

namespace App\Services;

use App\Models\Permission;
use App\Models\PermissionTemplate;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * 權限模板服務類別
 * 
 * 提供權限模板相關的業務邏輯處理
 */
class PermissionTemplateService
{
    /**
     * 應用模板並建立權限
     * 
     * @param PermissionTemplate $template
     * @param string $modulePrefix
     * @param array $options
     * @return array
     */
    public function applyTemplate(PermissionTemplate $template, string $modulePrefix, array $options = []): array
    {
        $results = [
            'created' => [],
            'skipped' => [],
            'errors' => [],
        ];

        DB::beginTransaction();
        
        try {
            foreach ($template->permissions as $permissionData) {
                $permissionName = $this->generatePermissionName($permissionData, $modulePrefix);
                
                // 檢查權限是否已存在
                if (Permission::where('name', $permissionName)->exists()) {
                    $results['skipped'][] = [
                        'name' => $permissionName,
                        'reason' => '權限已存在',
                    ];
                    continue;
                }

                // 建立權限
                try {
                    $permission = Permission::create([
                        'name' => $permissionName,
                        'display_name' => $permissionData['display_name'],
                        'description' => $permissionData['description'] ?? null,
                        'module' => $modulePrefix,
                        'type' => $permissionData['type'],
                    ]);

                    $results['created'][] = $permission;
                    
                    Log::info('權限模板應用：建立權限', [
                        'template_id' => $template->id,
                        'template_name' => $template->name,
                        'permission_name' => $permissionName,
                        'module_prefix' => $modulePrefix,
                    ]);
                } catch (\Exception $e) {
                    $results['errors'][] = [
                        'name' => $permissionName,
                        'error' => $e->getMessage(),
                    ];
                }
            }

            DB::commit();
            
            Log::info('權限模板應用完成', [
                'template_id' => $template->id,
                'created_count' => count($results['created']),
                'skipped_count' => count($results['skipped']),
                'error_count' => count($results['errors']),
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }

        return $results;
    }

    /**
     * 從現有權限建立模板
     * 
     * @param Collection $permissions
     * @param array $templateData
     * @return PermissionTemplate
     */
    public function createTemplateFromPermissions(Collection $permissions, array $templateData): PermissionTemplate
    {
        $permissionArray = $permissions->map(function ($permission) {
            return [
                'action' => $this->extractActionFromName($permission->name),
                'display_name' => $permission->display_name,
                'description' => $permission->description,
                'type' => $permission->type,
            ];
        })->toArray();

        $template = PermissionTemplate::create([
            'name' => $templateData['name'],
            'display_name' => $templateData['display_name'],
            'description' => $templateData['description'] ?? null,
            'module' => $templateData['module'],
            'permissions' => $permissionArray,
            'is_system_template' => $templateData['is_system_template'] ?? false,
            'created_by' => $templateData['created_by'] ?? auth()->user()?->getKey(),
        ]);

        Log::info('從權限建立模板', [
            'template_id' => $template->id,
            'template_name' => $template->name,
            'permission_count' => $permissions->count(),
            'source_permissions' => $permissions->pluck('name')->toArray(),
        ]);

        return $template;
    }

    /**
     * 取得模板預覽
     * 
     * @param PermissionTemplate $template
     * @param string $modulePrefix
     * @return array
     */
    public function getTemplatePreview(PermissionTemplate $template, string $modulePrefix): array
    {
        $preview = [];
        $existingPermissions = Permission::whereIn(
            'name', 
            collect($template->permissions)->map(function ($permissionData) use ($modulePrefix) {
                return $this->generatePermissionName($permissionData, $modulePrefix);
            })
        )->pluck('name')->toArray();

        foreach ($template->permissions as $permissionData) {
            $permissionName = $this->generatePermissionName($permissionData, $modulePrefix);
            $exists = in_array($permissionName, $existingPermissions);

            $preview[] = [
                'name' => $permissionName,
                'display_name' => $permissionData['display_name'],
                'description' => $permissionData['description'] ?? '',
                'type' => $permissionData['type'],
                'exists' => $exists,
                'will_create' => !$exists,
                'action' => $permissionData['action'] ?? $permissionData['type'],
            ];
        }

        return $preview;
    }

    /**
     * 驗證模板資料
     * 
     * @param array $templateData
     * @return array
     */
    public function validateTemplateData(array $templateData): array
    {
        $errors = [];

        // 驗證基本欄位
        if (empty($templateData['name'])) {
            $errors['name'] = '模板名稱不能為空';
        } elseif (!preg_match('/^[a-z_]+$/', $templateData['name'])) {
            $errors['name'] = '模板名稱只能包含小寫字母和底線';
        }

        if (empty($templateData['display_name'])) {
            $errors['display_name'] = '顯示名稱不能為空';
        }

        if (empty($templateData['module'])) {
            $errors['module'] = '模組不能為空';
        }

        if (empty($templateData['permissions']) || !is_array($templateData['permissions'])) {
            $errors['permissions'] = '至少需要一個權限配置';
        } else {
            // 驗證權限配置
            foreach ($templateData['permissions'] as $index => $permission) {
                if (empty($permission['action'])) {
                    $errors["permissions.{$index}.action"] = '動作不能為空';
                }
                if (empty($permission['display_name'])) {
                    $errors["permissions.{$index}.display_name"] = '顯示名稱不能為空';
                }
                if (empty($permission['type'])) {
                    $errors["permissions.{$index}.type"] = '類型不能為空';
                }
            }
        }

        return $errors;
    }

    /**
     * 取得可用的模組列表
     * 
     * @return array
     */
    public function getAvailableModules(): array
    {
        return [
            'users' => '使用者管理',
            'roles' => '角色管理',
            'permissions' => '權限管理',
            'dashboard' => '儀表板',
            'reports' => '報表',
            'settings' => '系統設定',
            'general' => '一般功能',
            'auth' => '認證授權',
            'admin' => '系統管理',
            'api' => 'API 管理',
            'logs' => '日誌管理',
            'monitoring' => '監控管理',
        ];
    }

    /**
     * 取得可用的權限類型
     * 
     * @return array
     */
    public function getAvailableTypes(): array
    {
        return [
            'view' => '檢視',
            'create' => '建立',
            'edit' => '編輯',
            'delete' => '刪除',
            'manage' => '管理',
            'export' => '匯出',
            'import' => '匯入',
            'approve' => '審核',
            'publish' => '發布',
        ];
    }

    /**
     * 取得模板統計資料
     * 
     * @return array
     */
    public function getTemplateStats(): array
    {
        return [
            'total_templates' => PermissionTemplate::count(),
            'system_templates' => PermissionTemplate::where('is_system_template', true)->count(),
            'custom_templates' => PermissionTemplate::where('is_system_template', false)->count(),
            'active_templates' => PermissionTemplate::where('is_active', true)->count(),
            'most_used_modules' => $this->getMostUsedModules(),
            'recent_templates' => $this->getRecentTemplates(),
        ];
    }

    /**
     * 取得最常用的模組
     * 
     * @return array
     */
    private function getMostUsedModules(): array
    {
        return PermissionTemplate::select('module', DB::raw('count(*) as count'))
                                ->groupBy('module')
                                ->orderBy('count', 'desc')
                                ->limit(5)
                                ->get()
                                ->toArray();
    }

    /**
     * 取得最近建立的模板
     * 
     * @return Collection
     */
    private function getRecentTemplates(): Collection
    {
        return PermissionTemplate::with('creator')
                                ->orderBy('created_at', 'desc')
                                ->limit(5)
                                ->get();
    }

    /**
     * 複製模板
     * 
     * @param PermissionTemplate $template
     * @param array $newData
     * @return PermissionTemplate
     */
    public function duplicateTemplate(PermissionTemplate $template, array $newData): PermissionTemplate
    {
        $duplicatedTemplate = PermissionTemplate::create([
            'name' => $newData['name'],
            'display_name' => $newData['display_name'] ?? $template->display_name . ' (複製)',
            'description' => $newData['description'] ?? $template->description,
            'module' => $newData['module'] ?? $template->module,
            'permissions' => $template->permissions,
            'is_system_template' => false, // 複製的模板不是系統模板
            'created_by' => auth()->user()?->getKey(),
        ]);

        Log::info('模板複製完成', [
            'original_template_id' => $template->id,
            'new_template_id' => $duplicatedTemplate->id,
            'new_template_name' => $duplicatedTemplate->name,
        ]);

        return $duplicatedTemplate;
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
     * 從權限名稱提取動作
     * 
     * @param string $permissionName
     * @return string
     */
    private function extractActionFromName(string $permissionName): string
    {
        $parts = explode('.', $permissionName);
        return end($parts);
    }

    /**
     * 匯出模板
     * 
     * @param PermissionTemplate $template
     * @return array
     */
    public function exportTemplate(PermissionTemplate $template): array
    {
        return [
            'name' => $template->name,
            'display_name' => $template->display_name,
            'description' => $template->description,
            'module' => $template->module,
            'permissions' => $template->permissions,
            'exported_at' => now()->toISOString(),
            'version' => '1.0',
        ];
    }

    /**
     * 匯入模板
     * 
     * @param array $templateData
     * @return PermissionTemplate
     */
    public function importTemplate(array $templateData): PermissionTemplate
    {
        // 驗證匯入資料
        $errors = $this->validateTemplateData($templateData);
        if (!empty($errors)) {
            throw new \InvalidArgumentException('模板資料驗證失敗：' . implode(', ', $errors));
        }

        // 檢查名稱衝突
        if (PermissionTemplate::where('name', $templateData['name'])->exists()) {
            $templateData['name'] = $templateData['name'] . '_imported_' . time();
        }

        $template = PermissionTemplate::create([
            'name' => $templateData['name'],
            'display_name' => $templateData['display_name'],
            'description' => $templateData['description'] ?? null,
            'module' => $templateData['module'],
            'permissions' => $templateData['permissions'],
            'is_system_template' => false,
            'created_by' => $templateData['created_by'] ?? auth()->user()?->getKey(),
        ]);

        Log::info('模板匯入完成', [
            'template_id' => $template->id,
            'template_name' => $template->name,
            'permission_count' => count($template->permissions),
        ]);

        return $template;
    }
}