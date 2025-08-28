<?php

namespace App\Livewire\Admin\Permissions;

use App\Models\Permission;
use App\Models\PermissionTemplate;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Collection;

/**
 * 權限模板管理元件
 * 
 * 提供權限模板的建立、編輯、刪除和應用功能
 */
class PermissionTemplateManager extends Component
{
    use WithPagination;

    // 搜尋和篩選
    public string $search = '';
    public string $moduleFilter = 'all';
    public string $typeFilter = 'all'; // all, system, custom

    // 模板表單
    public bool $showTemplateForm = false;
    public ?PermissionTemplate $editingTemplate = null;
    public string $templateName = '';
    public string $templateDisplayName = '';
    public string $templateDescription = '';
    public string $templateModule = '';
    public array $templatePermissions = [];

    // 模板應用
    public bool $showApplyModal = false;
    public ?PermissionTemplate $applyingTemplate = null;
    public string $applyModulePrefix = '';
    public array $previewPermissions = [];

    // 從權限建立模板
    public bool $showCreateFromPermissionsModal = false;
    public array $selectedPermissions = [];
    public string $createFromModule = '';

    // 可用選項
    public array $availableModules = [
        'users' => '使用者管理',
        'roles' => '角色管理',
        'permissions' => '權限管理',
        'dashboard' => '儀表板',
        'reports' => '報表',
        'settings' => '系統設定',
        'general' => '一般功能',
    ];

    public array $availableTypes = [
        'view' => '檢視',
        'create' => '建立',
        'edit' => '編輯',
        'delete' => '刪除',
        'manage' => '管理',
    ];

    /**
     * 元件初始化
     */
    public function mount()
    {
        $this->resetTemplateForm();
    }

    /**
     * 取得模板列表
     */
    public function getTemplatesProperty()
    {
        return PermissionTemplate::query()
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('name', 'like', '%' . $this->search . '%')
                      ->orWhere('display_name', 'like', '%' . $this->search . '%')
                      ->orWhere('description', 'like', '%' . $this->search . '%');
                });
            })
            ->when($this->moduleFilter !== 'all', function ($query) {
                $query->where('module', $this->moduleFilter);
            })
            ->when($this->typeFilter === 'system', function ($query) {
                $query->where('is_system_template', true);
            })
            ->when($this->typeFilter === 'custom', function ($query) {
                $query->where('is_system_template', false);
            })
            ->with('creator')
            ->orderBy('is_system_template', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate(15);
    }

    /**
     * 取得模組權限列表（用於從權限建立模板）
     */
    public function getModulePermissionsProperty()
    {
        if (!$this->createFromModule) {
            return collect();
        }

        return Permission::where('module', $this->createFromModule)
                        ->orderBy('type')
                        ->orderBy('name')
                        ->get();
    }

    /**
     * 建立新模板
     */
    public function createTemplate()
    {
        $this->resetTemplateForm();
        $this->showTemplateForm = true;
    }

    /**
     * 編輯模板
     */
    public function editTemplate(PermissionTemplate $template)
    {
        if ($template->is_system_template) {
            session()->flash('error', '系統模板無法編輯');
            return;
        }

        $this->editingTemplate = $template;
        $this->templateName = $template->name;
        $this->templateDisplayName = $template->display_name;
        $this->templateDescription = $template->description ?? '';
        $this->templateModule = $template->module;
        $this->templatePermissions = $template->permissions;
        $this->showTemplateForm = true;
    }

    /**
     * 儲存模板
     */
    public function saveTemplate()
    {
        $this->validate([
            'templateName' => 'required|string|max:100|regex:/^[a-z_]+$/',
            'templateDisplayName' => 'required|string|max:50',
            'templateDescription' => 'nullable|string|max:255',
            'templateModule' => 'required|string',
            'templatePermissions' => 'required|array|min:1',
            'templatePermissions.*.action' => 'required|string',
            'templatePermissions.*.display_name' => 'required|string|max:50',
            'templatePermissions.*.type' => 'required|string|in:' . implode(',', array_keys($this->availableTypes)),
        ], [
            'templateName.regex' => '模板名稱只能包含小寫字母和底線',
            'templatePermissions.required' => '至少需要一個權限配置',
        ]);

        // 檢查名稱唯一性
        $query = PermissionTemplate::where('name', $this->templateName);
        if ($this->editingTemplate) {
            $query->where('id', '!=', $this->editingTemplate->id);
        }
        
        if ($query->exists()) {
            $this->addError('templateName', '模板名稱已存在');
            return;
        }

        $templateData = [
            'name' => $this->templateName,
            'display_name' => $this->templateDisplayName,
            'description' => $this->templateDescription,
            'module' => $this->templateModule,
            'permissions' => $this->templatePermissions,
        ];

        // 只在建立時設定 created_by
        if (!$this->editingTemplate) {
            $templateData['created_by'] = auth()->user()->getKey();
        }

        if ($this->editingTemplate) {
            $this->editingTemplate->update($templateData);
            session()->flash('success', '模板更新成功');
        } else {
            PermissionTemplate::create($templateData);
            session()->flash('success', '模板建立成功');
        }

        $this->resetTemplateForm();
        $this->showTemplateForm = false;
    }

    /**
     * 刪除模板
     */
    public function deleteTemplate(PermissionTemplate $template)
    {
        if ($template->is_system_template) {
            session()->flash('error', '系統模板無法刪除');
            return;
        }

        $template->delete();
        session()->flash('success', '模板刪除成功');
    }

    /**
     * 顯示應用模板對話框
     */
    public function showApplyTemplate(PermissionTemplate $template)
    {
        $this->applyingTemplate = $template;
        $this->applyModulePrefix = $template->module;
        $this->updatePreview();
        $this->showApplyModal = true;
    }

    /**
     * 更新預覽
     */
    public function updatedApplyModulePrefix()
    {
        $this->updatePreview();
    }

    /**
     * 更新預覽資料
     */
    private function updatePreview()
    {
        if ($this->applyingTemplate && $this->applyModulePrefix) {
            $this->previewPermissions = $this->applyingTemplate->getPreview($this->applyModulePrefix);
        }
    }

    /**
     * 應用模板
     */
    public function applyTemplate()
    {
        $this->validate([
            'applyModulePrefix' => 'required|string|max:50|regex:/^[a-z_]+$/',
        ], [
            'applyModulePrefix.regex' => '模組前綴只能包含小寫字母和底線',
        ]);

        try {
            $templateService = app(\App\Services\PermissionTemplateService::class);
            $results = $templateService->applyTemplate($this->applyingTemplate, $this->applyModulePrefix);
            
            if (empty($results['created'])) {
                session()->flash('warning', '沒有建立新權限，所有權限都已存在');
            } else {
                session()->flash('success', "成功建立 " . count($results['created']) . " 個權限");
            }

            $this->showApplyModal = false;
            $this->resetApplyForm();
        } catch (\Exception $e) {
            session()->flash('error', '應用模板失敗：' . $e->getMessage());
        }
    }

    /**
     * 顯示從權限建立模板對話框
     */
    public function showCreateFromPermissions()
    {
        $this->resetCreateFromPermissionsForm();
        $this->showCreateFromPermissionsModal = true;
    }

    /**
     * 更新選中的權限
     */
    public function updatedCreateFromModule()
    {
        $this->selectedPermissions = [];
    }

    /**
     * 從選中的權限建立模板
     */
    public function createFromSelectedPermissions()
    {
        $this->validate([
            'createFromModule' => 'required|string',
            'selectedPermissions' => 'required|array|min:1',
            'templateName' => 'required|string|max:100|regex:/^[a-z_]+$/',
            'templateDisplayName' => 'required|string|max:50',
        ], [
            'selectedPermissions.required' => '請選擇至少一個權限',
            'templateName.regex' => '模板名稱只能包含小寫字母和底線',
        ]);

        // 檢查名稱唯一性
        if (PermissionTemplate::where('name', $this->templateName)->exists()) {
            $this->addError('templateName', '模板名稱已存在');
            return;
        }

        $permissions = Permission::whereIn('id', $this->selectedPermissions)->get();
        
        try {
            $templateService = app(\App\Services\PermissionTemplateService::class);
            $templateService->createTemplateFromPermissions($permissions, [
                'name' => $this->templateName,
                'display_name' => $this->templateDisplayName,
                'description' => $this->templateDescription,
                'module' => $this->createFromModule,
                'created_by' => auth()->user()->getKey(),
            ]);

            session()->flash('success', '模板建立成功');
            $this->showCreateFromPermissionsModal = false;
            $this->resetCreateFromPermissionsForm();
        } catch (\Exception $e) {
            session()->flash('error', '建立模板失敗：' . $e->getMessage());
        }
    }

    /**
     * 新增權限配置
     */
    public function addPermissionConfig()
    {
        $this->templatePermissions[] = [
            'action' => '',
            'display_name' => '',
            'description' => '',
            'type' => 'view',
        ];
    }

    /**
     * 移除權限配置
     */
    public function removePermissionConfig($index)
    {
        unset($this->templatePermissions[$index]);
        $this->templatePermissions = array_values($this->templatePermissions);
    }

    /**
     * 重設模板表單
     */
    public function resetTemplateForm()
    {
        $this->editingTemplate = null;
        $this->templateName = '';
        $this->templateDisplayName = '';
        $this->templateDescription = '';
        $this->templateModule = '';
        $this->templatePermissions = [
            [
                'action' => 'view',
                'display_name' => '檢視',
                'description' => '',
                'type' => 'view',
            ]
        ];
        $this->showTemplateForm = false;
        
        // 強制重新渲染元件以確保前端同步
        $this->dispatch('$refresh');
        
        // 發送前端刷新事件
        $this->dispatch('permission-template-manager-reset');
    }

    /**
     * 重設應用表單
     */
    public function resetApplyForm()
    {
        $this->applyingTemplate = null;
        $this->applyModulePrefix = '';
        $this->previewPermissions = [];
        $this->showApplyModal = false;
        
        // 強制重新渲染元件以確保前端同步
        $this->dispatch('$refresh');
        
        // 發送前端刷新事件
        $this->dispatch('permission-template-apply-reset');
    }

    /**
     * 重設從權限建立模板表單
     */
    public function resetCreateFromPermissionsForm()
    {
        $this->selectedPermissions = [];
        $this->createFromModule = '';
        $this->templateName = '';
        $this->templateDisplayName = '';
        $this->templateDescription = '';
        $this->showCreateFromPermissionsModal = false;
        
        // 強制重新渲染元件以確保前端同步
        $this->dispatch('$refresh');
        
        // 發送前端刷新事件
        $this->dispatch('permission-template-create-from-permissions-reset');
    }

    /**
     * 渲染元件
     */
    public function render()
    {
        return view('livewire.admin.permissions.permission-template-manager', [
            'templates' => $this->templates,
            'modulePermissions' => $this->modulePermissions,
        ]);
    }
}
