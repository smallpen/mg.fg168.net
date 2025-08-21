<?php

namespace App\View\Components;

use Illuminate\View\Component;
use App\Helpers\RoleLocalizationHelper;

/**
 * 角色本地化 Blade 元件
 * 
 * 提供角色和權限的本地化顯示功能
 */
class RoleLocalization extends Component
{
    public string $type;
    public string $name;
    public bool $showDescription;
    public string $fallback;

    /**
     * 建立元件實例
     *
     * @param string $type 類型：role, permission, module
     * @param string $name 名稱
     * @param bool $showDescription 是否顯示描述
     * @param string $fallback 備用文字
     */
    public function __construct(
        string $type,
        string $name,
        bool $showDescription = false,
        string $fallback = ''
    ) {
        $this->type = $type;
        $this->name = $name;
        $this->showDescription = $showDescription;
        $this->fallback = $fallback;
    }

    /**
     * 取得顯示名稱
     */
    public function getDisplayName(): string
    {
        switch ($this->type) {
            case 'role':
                return RoleLocalizationHelper::getRoleDisplayName($this->name);
            case 'permission':
                return RoleLocalizationHelper::getPermissionDisplayName($this->name);
            case 'module':
                return RoleLocalizationHelper::getModuleDisplayName($this->name);
            default:
                return $this->fallback ?: $this->name;
        }
    }

    /**
     * 取得描述
     */
    public function getDescription(): string
    {
        if (!$this->showDescription) {
            return '';
        }

        switch ($this->type) {
            case 'role':
                return RoleLocalizationHelper::getRoleDescription($this->name);
            case 'permission':
                return RoleLocalizationHelper::getPermissionDescription($this->name);
            default:
                return '';
        }
    }

    /**
     * 取得元件視圖
     */
    public function render()
    {
        return view('components.role-localization');
    }
}