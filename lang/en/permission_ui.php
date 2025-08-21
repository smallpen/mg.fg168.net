<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Permission UI Language Lines
    |--------------------------------------------------------------------------
    |
    | The following language lines are used for permission management
    | user interface components, buttons, and interactive elements.
    |
    */

    // Component titles
    'components' => [
        'permission_list' => 'Permission List',
        'permission_form' => 'Permission Form',
        'dependency_graph' => 'Dependency Graph',
        'permission_test' => 'Permission Test Tool',
        'usage_analysis' => 'Usage Analysis',
        'audit_viewer' => 'Audit Log Viewer',
        'import_wizard' => 'Import Wizard',
        'export_dialog' => 'Export Dialog',
        'template_manager' => 'Template Manager',
        'bulk_operations' => 'Bulk Operations',
    ],

    // Form elements
    'form_elements' => [
        'search_input' => 'Search permissions...',
        'module_select' => 'Select Module',
        'type_select' => 'Select Type',
        'status_select' => 'Select Status',
        'dependency_multiselect' => 'Select Dependencies',
        'template_select' => 'Choose Template',
        'user_select' => 'Select User',
        'role_select' => 'Select Role',
        'date_picker' => 'Select Date',
        'file_upload' => 'Choose File',
    ],

    // Buttons and actions
    'buttons' => [
        'create_new' => 'Create New Permission',
        'edit_permission' => 'Edit Permission',
        'delete_permission' => 'Delete Permission',
        'duplicate_permission' => 'Duplicate Permission',
        'view_details' => 'View Details',
        'manage_dependencies' => 'Manage Dependencies',
        'test_permission' => 'Test Permission',
        'export_permissions' => 'Export Permissions',
        'import_permissions' => 'Import Permissions',
        'apply_template' => 'Apply Template',
        'save_template' => 'Save as Template',
        'run_analysis' => 'Run Analysis',
        'clear_cache' => 'Clear Cache',
        'refresh_data' => 'Refresh Data',
        'download_report' => 'Download Report',
        'view_audit_log' => 'View Audit Log',
        'bulk_select' => 'Bulk Select',
        'select_all' => 'Select All',
        'deselect_all' => 'Deselect All',
        'apply_filters' => 'Apply Filters',
        'clear_filters' => 'Clear Filters',
        'advanced_search' => 'Advanced Search',
        'save_search' => 'Save Search',
        'load_search' => 'Load Search',
    ],

    // Status indicators
    'status_indicators' => [
        'active' => 'Active',
        'inactive' => 'Inactive',
        'system' => 'System',
        'deprecated' => 'Deprecated',
        'loading' => 'Loading...',
        'processing' => 'Processing...',
        'completed' => 'Completed',
        'failed' => 'Failed',
        'pending' => 'Pending',
        'in_use' => 'In Use',
        'unused' => 'Unused',
        'has_dependencies' => 'Has Dependencies',
        'no_dependencies' => 'No Dependencies',
    ],

    // Modal dialogs
    'modals' => [
        'confirm_delete' => 'Confirm Deletion',
        'permission_details' => 'Permission Details',
        'dependency_manager' => 'Dependency Manager',
        'test_results' => 'Test Results',
        'import_preview' => 'Import Preview',
        'export_options' => 'Export Options',
        'template_selector' => 'Template Selector',
        'bulk_actions' => 'Bulk Actions',
        'error_details' => 'Error Details',
        'success_notification' => 'Success',
    ],

    // Tabs and navigation
    'tabs' => [
        'overview' => 'Overview',
        'details' => 'Details',
        'dependencies' => 'Dependencies',
        'usage' => 'Usage',
        'audit' => 'Audit',
        'settings' => 'Settings',
        'advanced' => 'Advanced',
        'import' => 'Import',
        'export' => 'Export',
        'templates' => 'Templates',
        'test' => 'Test',
        'reports' => 'Reports',
    ],

    // Data table elements
    'table' => [
        'no_data' => 'No data available',
        'loading_data' => 'Loading data...',
        'search_results' => 'Search Results',
        'filtered_results' => 'Filtered Results',
        'showing_entries' => 'Showing :start to :end of :total entries',
        'no_matching_records' => 'No matching records found',
        'sort_ascending' => 'Sort ascending',
        'sort_descending' => 'Sort descending',
        'column_visibility' => 'Column Visibility',
        'export_table' => 'Export Table',
        'print_table' => 'Print Table',
        'refresh_table' => 'Refresh Table',
    ],

    // Filters and search
    'filters' => [
        'all_modules' => 'All Modules',
        'all_types' => 'All Types',
        'all_statuses' => 'All Statuses',
        'active_only' => 'Active Only',
        'inactive_only' => 'Inactive Only',
        'system_only' => 'System Only',
        'used_only' => 'Used Only',
        'unused_only' => 'Unused Only',
        'with_dependencies' => 'With Dependencies',
        'without_dependencies' => 'Without Dependencies',
        'created_today' => 'Created Today',
        'created_this_week' => 'Created This Week',
        'created_this_month' => 'Created This Month',
        'modified_recently' => 'Modified Recently',
    ],

    // Tooltips and help text
    'tooltips' => [
        'permission_name_help' => 'Use format: module.action (e.g., users.create)',
        'display_name_help' => 'Human-readable name for this permission',
        'description_help' => 'Brief description of what this permission allows',
        'module_help' => 'Logical grouping for related permissions',
        'type_help' => 'Category of operation this permission controls',
        'dependencies_help' => 'Other permissions required before this one',
        'system_permission_help' => 'Core permissions required for system operation',
        'active_status_help' => 'Whether this permission is currently active',
        'usage_count_help' => 'Number of roles using this permission',
        'dependency_count_help' => 'Number of permissions this depends on',
        'dependent_count_help' => 'Number of permissions that depend on this',
        'last_used_help' => 'When this permission was last accessed',
        'created_date_help' => 'When this permission was created',
        'modified_date_help' => 'When this permission was last modified',
    ],

    // Progress indicators
    'progress' => [
        'initializing' => 'Initializing...',
        'loading_permissions' => 'Loading permissions...',
        'processing_request' => 'Processing request...',
        'saving_changes' => 'Saving changes...',
        'deleting_permission' => 'Deleting permission...',
        'updating_dependencies' => 'Updating dependencies...',
        'running_analysis' => 'Running analysis...',
        'generating_report' => 'Generating report...',
        'importing_data' => 'Importing data...',
        'exporting_data' => 'Exporting data...',
        'validating_data' => 'Validating data...',
        'completing_operation' => 'Completing operation...',
    ],

    // Breadcrumbs
    'breadcrumbs' => [
        'home' => 'Home',
        'admin' => 'Administration',
        'permissions' => 'Permissions',
        'create' => 'Create',
        'edit' => 'Edit',
        'view' => 'View',
        'dependencies' => 'Dependencies',
        'test' => 'Test',
        'usage' => 'Usage',
        'audit' => 'Audit',
        'templates' => 'Templates',
        'import' => 'Import',
        'export' => 'Export',
        'settings' => 'Settings',
    ],

    // Keyboard shortcuts
    'shortcuts' => [
        'create_new' => 'Ctrl+N',
        'save' => 'Ctrl+S',
        'search' => 'Ctrl+F',
        'refresh' => 'F5',
        'delete' => 'Delete',
        'edit' => 'Enter',
        'cancel' => 'Escape',
        'select_all' => 'Ctrl+A',
        'copy' => 'Ctrl+C',
        'paste' => 'Ctrl+V',
    ],

    // Accessibility labels
    'accessibility' => [
        'main_content' => 'Main content',
        'navigation_menu' => 'Navigation menu',
        'search_form' => 'Search form',
        'data_table' => 'Permissions data table',
        'action_buttons' => 'Action buttons',
        'filter_controls' => 'Filter controls',
        'pagination_controls' => 'Pagination controls',
        'modal_dialog' => 'Modal dialog',
        'close_button' => 'Close',
        'expand_button' => 'Expand',
        'collapse_button' => 'Collapse',
        'sort_button' => 'Sort',
        'filter_button' => 'Filter',
        'menu_button' => 'Menu',
    ],

];