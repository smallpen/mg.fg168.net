/**
 * 測試配置檔案
 * 定義各種 Livewire 元件的測試配置
 */

const TestConfigurations = {
    // 使用者列表元件配置
    userList: {
        componentUrl: 'http://localhost/admin/users',
        searchSelector: '#search',
        filterSelectors: {
            role: '#roleFilter',
            status: '#statusFilter'
        },
        resetButtonSelector: 'button[wire\\:click="resetFilters"]',
        expectedResetValues: {
            '#search': '',
            '#roleFilter': 'all',
            '#statusFilter': 'all'
        },
        mobileSelectors: {
            search: '#search-mobile',
            role: '#roleFilter-mobile',
            status: '#statusFilter-mobile'
        },
        mobileResetSelector: 'button[wire\\:key="mobile-reset-button"]',
        bulkResetSelector: 'button[wire\\:key="desktop-bulk-reset-button"]',
        statusSelector: '[wire\\:key="filter-status"]'
    },

    // 活動匯出元件配置
    activityExport: {
        componentUrl: 'http://localhost/admin/activities/export',
        searchSelector: '#search',
        filterSelectors: {
            dateFrom: '#date_from',
            dateTo: '#date_to',
            type: '#type_filter'
        },
        resetButtonSelector: 'button[wire\\:click="resetFilters"]',
        expectedResetValues: {
            '#search': '',
            '#date_from': '',
            '#date_to': '',
            '#type_filter': 'all'
        }
    },

    // 權限稽核日誌元件配置
    permissionAuditLog: {
        componentUrl: 'http://localhost/admin/permissions/audit',
        searchSelector: '#search',
        filterSelectors: {
            user: '#user_filter',
            action: '#action_filter',
            dateRange: '#date_range'
        },
        resetButtonSelector: 'button[wire\\:click="resetFilters"]',
        expectedResetValues: {
            '#search': '',
            '#user_filter': 'all',
            '#action_filter': 'all',
            '#date_range': ''
        }
    },

    // 設定列表元件配置
    settingsList: {
        componentUrl: 'http://localhost/admin/settings',
        searchSelector: '#search',
        filterSelectors: {
            category: '#category_filter'
        },
        resetButtonSelector: 'button[wire\\:click="clearFilters"]',
        expectedResetValues: {
            '#search': '',
            '#category_filter': 'all'
        }
    },

    // 通知列表元件配置
    notificationList: {
        componentUrl: 'http://localhost/admin/notifications',
        searchSelector: '#search',
        filterSelectors: {
            type: '#type_filter',
            status: '#status_filter'
        },
        resetButtonSelector: 'button[wire\\:click="clearFilters"]',
        expectedResetValues: {
            '#search': '',
            '#type_filter': 'all',
            '#status_filter': 'all'
        }
    },

    // 權限模板管理器元件配置
    permissionTemplateManager: {
        componentUrl: 'http://localhost/admin/permissions/templates',
        openModalSelector: 'button[wire\\:click="showCreateTemplateModal"]',
        formSelectors: {
            name: '#template_name',
            description: '#template_description'
        },
        resetButtonSelector: 'button[wire\\:click="resetTemplateForm"]',
        closeModalSelector: 'button[wire\\:click="hideCreateTemplateModal"]',
        expectedResetValues: {
            '#template_name': '',
            '#template_description': ''
        }
    },

    // 權限表單元件配置
    permissionForm: {
        componentUrl: 'http://localhost/admin/permissions/create',
        formSelectors: {
            name: '#permission_name',
            displayName: '#permission_display_name',
            module: '#permission_module'
        },
        resetButtonSelector: 'button[wire\\:click="resetForm"]',
        expectedResetValues: {
            '#permission_name': '',
            '#permission_display_name': '',
            '#permission_module': ''
        }
    },

    // 使用者刪除模態元件配置
    userDeleteModal: {
        componentUrl: 'http://localhost/admin/users',
        openModalSelector: 'button[data-action="delete-user"]:first-of-type',
        formSelectors: {
            confirmation: '#delete_confirmation'
        },
        resetButtonSelector: 'button[wire\\:click="resetDeleteForm"]',
        closeModalSelector: 'button[wire\\:click="hideDeleteModal"]',
        expectedResetValues: {
            '#delete_confirmation': ''
        }
    },

    // 權限刪除模態元件配置
    permissionDeleteModal: {
        componentUrl: 'http://localhost/admin/permissions',
        openModalSelector: 'button[data-action="delete-permission"]:first-of-type',
        formSelectors: {
            confirmation: '#delete_confirmation'
        },
        resetButtonSelector: 'button[wire\\:click="resetDeleteForm"]',
        closeModalSelector: 'button[wire\\:click="hideDeleteModal"]',
        expectedResetValues: {
            '#delete_confirmation': ''
        }
    },

    // 保留政策管理器元件配置
    retentionPolicyManager: {
        componentUrl: 'http://localhost/admin/settings/retention',
        formSelectors: {
            days: '#retention_days',
            type: '#retention_type',
            enabled: '#retention_enabled'
        },
        resetButtonSelector: 'button[wire\\:click="resetPolicyForm"]',
        expectedResetValues: {
            '#retention_days': '30',
            '#retention_type': 'days',
            '#retention_enabled': 'false'
        }
    },

    // 效能監控元件配置
    performanceMonitor: {
        componentUrl: 'http://localhost/admin/monitoring/performance',
        controlSelectors: {
            period: '#selected_period',
            autoRefresh: '#auto_refresh'
        },
        resetButtonSelector: 'button[wire\\:click="resetMonitorSettings"]',
        expectedResetValues: {
            '#selected_period': '24h',
            '#auto_refresh': 'false'
        }
    },

    // 系統監控元件配置
    systemMonitor: {
        componentUrl: 'http://localhost/admin/monitoring/system',
        controlSelectors: {
            refreshInterval: '#refresh_interval',
            autoRefresh: '#auto_refresh'
        },
        resetButtonSelector: 'button[wire\\:click="resetSettings"]',
        expectedResetValues: {
            '#refresh_interval': '30',
            '#auto_refresh': 'false'
        }
    },

    // 最近活動元件配置
    recentActivity: {
        componentUrl: 'http://localhost/admin/activities/recent',
        searchSelector: '#search',
        filterSelectors: {
            type: '#activity_type',
            user: '#user_filter'
        },
        resetButtonSelector: 'button[wire\\:click="clearFilters"]',
        expectedResetValues: {
            '#search': '',
            '#activity_type': 'all',
            '#user_filter': 'all'
        }
    },

    // 設定變更歷史元件配置
    settingChangeHistory: {
        componentUrl: 'http://localhost/admin/settings/history',
        searchSelector: '#search',
        filterSelectors: {
            setting: '#setting_filter',
            user: '#user_filter',
            dateRange: '#date_range'
        },
        resetButtonSelector: 'button[wire\\:click="clearFilters"]',
        expectedResetValues: {
            '#search': '',
            '#setting_filter': 'all',
            '#user_filter': 'all',
            '#date_range': ''
        }
    }
};

// 測試套件配置
const TestSuiteConfigurations = {
    // 高優先級元件測試套件
    highPriority: [
        'userList',
        'activityExport',
        'permissionAuditLog',
        'settingsList',
        'notificationList'
    ],

    // 中優先級元件測試套件
    mediumPriority: [
        'permissionTemplateManager',
        'permissionForm',
        'userDeleteModal',
        'permissionDeleteModal',
        'retentionPolicyManager'
    ],

    // 監控元件測試套件
    monitoringComponents: [
        'performanceMonitor',
        'systemMonitor',
        'recentActivity',
        'settingChangeHistory'
    ],

    // 響應式測試視窗大小
    responsiveViewports: [
        { name: 'Desktop', width: 1280, height: 720 },
        { name: 'Laptop', width: 1024, height: 768 },
        { name: 'Tablet', width: 768, height: 1024 },
        { name: 'Mobile', width: 375, height: 667 },
        { name: 'Small Mobile', width: 320, height: 568 }
    ],

    // 效能測試配置
    performanceTest: {
        iterations: 5,
        maxResetTime: 2000, // 最大重置時間（毫秒）
        maxFillTime: 1000,  // 最大填寫時間（毫秒）
        maxTotalTime: 3000  // 最大總時間（毫秒）
    },

    // 截圖配置
    screenshot: {
        path: 'tests/screenshots',
        fullPage: true,
        quality: 90
    },

    // 等待時間配置
    timeouts: {
        componentLoad: 10000,    // 元件載入超時
        formFill: 1000,         // 表單填寫等待
        resetWait: 1500,        // 重置等待時間
        ajaxRequest: 5000,      // AJAX 請求超時
        pageLoad: 30000         // 頁面載入超時
    }
};

module.exports = {
    TestConfigurations,
    TestSuiteConfigurations
};