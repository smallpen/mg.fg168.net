/**
 * 角色本地化 JavaScript 輔助函數
 * 
 * 提供前端角色和權限本地化功能
 */

class RoleLocalization {
    constructor() {
        this.translations = {};
        this.locale = document.documentElement.lang || 'en';
        this.loadTranslations();
    }

    /**
     * 載入翻譯資料
     */
    async loadTranslations() {
        try {
            // 從後端 API 載入翻譯資料
            const response = await fetch('/api/role-translations');
            if (response.ok) {
                this.translations = await response.json();
            }
        } catch (error) {
            console.warn('Failed to load role translations:', error);
        }
    }

    /**
     * 取得權限顯示名稱
     * @param {string} permissionName 權限名稱
     * @returns {string} 本地化的權限名稱
     */
    getPermissionDisplayName(permissionName) {
        const key = `permission_names.${permissionName}`;
        return this.get(key) || this.formatPermissionName(permissionName);
    }

    /**
     * 取得權限描述
     * @param {string} permissionName 權限名稱
     * @returns {string} 本地化的權限描述
     */
    getPermissionDescription(permissionName) {
        const key = `permission_descriptions.${permissionName}`;
        return this.get(key) || '';
    }

    /**
     * 取得角色顯示名稱
     * @param {string} roleName 角色名稱
     * @returns {string} 本地化的角色名稱
     */
    getRoleDisplayName(roleName) {
        const key = `role_names.${roleName}`;
        return this.get(key) || this.formatRoleName(roleName);
    }

    /**
     * 取得角色描述
     * @param {string} roleName 角色名稱
     * @returns {string} 本地化的角色描述
     */
    getRoleDescription(roleName) {
        const key = `role_descriptions.${roleName}`;
        return this.get(key) || '';
    }

    /**
     * 取得模組顯示名稱
     * @param {string} moduleName 模組名稱
     * @returns {string} 本地化的模組名稱
     */
    getModuleDisplayName(moduleName) {
        const key = `modules.${moduleName}`;
        return this.get(key) || this.formatModuleName(moduleName);
    }

    /**
     * 取得錯誤訊息
     * @param {string} errorKey 錯誤鍵值
     * @param {Object} params 參數
     * @returns {string} 本地化的錯誤訊息
     */
    getErrorMessage(errorKey, params = {}) {
        const key = `errors.${errorKey}`;
        let message = this.get(key) || errorKey;
        
        // 替換參數
        Object.keys(params).forEach(param => {
            message = message.replace(`:${param}`, params[param]);
        });
        
        return message;
    }

    /**
     * 取得成功訊息
     * @param {string} messageKey 訊息鍵值
     * @param {Object} params 參數
     * @returns {string} 本地化的成功訊息
     */
    getSuccessMessage(messageKey, params = {}) {
        const key = `messages.${messageKey}`;
        let message = this.get(key) || messageKey;
        
        // 替換參數
        Object.keys(params).forEach(param => {
            message = message.replace(`:${param}`, params[param]);
        });
        
        return message;
    }

    /**
     * 從翻譯資料中取得值
     * @param {string} key 鍵值
     * @returns {string|null} 翻譯值
     */
    get(key) {
        const keys = key.split('.');
        let value = this.translations;
        
        for (const k of keys) {
            if (value && typeof value === 'object' && k in value) {
                value = value[k];
            } else {
                return null;
            }
        }
        
        return typeof value === 'string' ? value : null;
    }

    /**
     * 格式化權限名稱
     * @param {string} permissionName 權限名稱
     * @returns {string} 格式化的權限名稱
     */
    formatPermissionName(permissionName) {
        const parts = permissionName.split('.');
        return parts.map(part => 
            part.charAt(0).toUpperCase() + part.slice(1).replace(/_/g, ' ')
        ).join(' ');
    }

    /**
     * 格式化角色名稱
     * @param {string} roleName 角色名稱
     * @returns {string} 格式化的角色名稱
     */
    formatRoleName(roleName) {
        return roleName.split('_').map(part => 
            part.charAt(0).toUpperCase() + part.slice(1)
        ).join(' ');
    }

    /**
     * 格式化模組名稱
     * @param {string} moduleName 模組名稱
     * @returns {string} 格式化的模組名稱
     */
    formatModuleName(moduleName) {
        return moduleName.split('_').map(part => 
            part.charAt(0).toUpperCase() + part.slice(1)
        ).join(' ');
    }

    /**
     * 更新頁面上的本地化元素
     */
    updatePageElements() {
        // 更新角色名稱
        document.querySelectorAll('[data-role-name]').forEach(element => {
            const roleName = element.dataset.roleName;
            element.textContent = this.getRoleDisplayName(roleName);
        });

        // 更新權限名稱
        document.querySelectorAll('[data-permission-name]').forEach(element => {
            const permissionName = element.dataset.permissionName;
            element.textContent = this.getPermissionDisplayName(permissionName);
        });

        // 更新模組名稱
        document.querySelectorAll('[data-module-name]').forEach(element => {
            const moduleName = element.dataset.moduleName;
            element.textContent = this.getModuleDisplayName(moduleName);
        });

        // 更新描述
        document.querySelectorAll('[data-role-description]').forEach(element => {
            const roleName = element.dataset.roleDescription;
            const description = this.getRoleDescription(roleName);
            if (description) {
                element.textContent = description;
                element.style.display = '';
            } else {
                element.style.display = 'none';
            }
        });

        document.querySelectorAll('[data-permission-description]').forEach(element => {
            const permissionName = element.dataset.permissionDescription;
            const description = this.getPermissionDescription(permissionName);
            if (description) {
                element.textContent = description;
                element.style.display = '';
            } else {
                element.style.display = 'none';
            }
        });
    }

    /**
     * 檢查是否為中文語言環境
     * @returns {boolean}
     */
    isChineseLocale() {
        return ['zh_TW', 'zh_CN', 'zh'].includes(this.locale);
    }

    /**
     * 格式化日期
     * @param {Date|string} date 日期
     * @returns {string} 格式化的日期
     */
    formatDate(date) {
        const dateObj = typeof date === 'string' ? new Date(date) : date;
        
        if (this.isChineseLocale()) {
            return dateObj.toLocaleDateString('zh-TW', {
                year: 'numeric',
                month: 'long',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
        } else {
            return dateObj.toLocaleDateString('en-US', {
                year: 'numeric',
                month: 'short',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
        }
    }
}

// 建立全域實例
window.roleLocalization = new RoleLocalization();

// DOM 載入完成後更新頁面元素
document.addEventListener('DOMContentLoaded', () => {
    window.roleLocalization.updatePageElements();
});

// 監聽語言變更事件
document.addEventListener('locale-changed', (event) => {
    window.roleLocalization.locale = event.detail.locale;
    window.roleLocalization.loadTranslations().then(() => {
        window.roleLocalization.updatePageElements();
    });
});

// 匯出類別供其他模組使用
export default RoleLocalization;