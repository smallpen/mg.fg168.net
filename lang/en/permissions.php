<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Permission Management Language Lines
    |--------------------------------------------------------------------------
    |
    | The following language lines are used for permission management
    | interface, messages, and labels.
    |
    */

    // Page titles and headings
    'titles' => [
        'permission_management' => 'Permission Management',
        'permission_list' => 'Permission List',
        'create_permission' => 'Create Permission',
        'edit_permission' => 'Edit Permission',
        'permission_details' => 'Permission Details',
        'dependency_graph' => 'Dependency Graph',
        'permission_test' => 'Permission Test',
        'usage_analysis' => 'Usage Analysis',
        'audit_log' => 'Audit Log',
        'import_export' => 'Import/Export',
        'templates' => 'Permission Templates',
    ],

    // Navigation and menu items
    'navigation' => [
        'permissions' => 'Permissions',
        'list' => 'List',
        'create' => 'Create',
        'dependencies' => 'Dependencies',
        'templates' => 'Templates',
        'test_tool' => 'Test Tool',
        'usage_stats' => 'Usage Statistics',
        'audit' => 'Audit',
        'settings' => 'Settings',
    ],

    // Form labels and placeholders
    'form' => [
        'name' => 'Permission Name',
        'name_placeholder' => 'e.g., users.create',
        'display_name' => 'Display Name',
        'display_name_placeholder' => 'e.g., Create Users',
        'description' => 'Description',
        'description_placeholder' => 'Brief description of this permission',
        'module' => 'Module',
        'module_placeholder' => 'Select module',
        'type' => 'Permission Type',
        'type_placeholder' => 'Select type',
        'dependencies' => 'Dependencies',
        'dependencies_placeholder' => 'Select dependent permissions',
        'is_system' => 'System Permission',
        'is_active' => 'Active',
    ],

    // Permission types
    'types' => [
        'view' => 'View',
        'create' => 'Create',
        'edit' => 'Edit',
        'delete' => 'Delete',
        'manage' => 'Manage',
        'admin' => 'Admin',
        'system' => 'System',
    ],

    // Modules (can be extended based on your application)
    'modules' => [
        'users' => 'Users',
        'roles' => 'Roles',
        'permissions' => 'Permissions',
        'dashboard' => 'Dashboard',
        'settings' => 'Settings',
        'reports' => 'Reports',
        'audit' => 'Audit',
        'system' => 'System',
    ],

    // Table headers
    'table' => [
        'name' => 'Name',
        'display_name' => 'Display Name',
        'description' => 'Description',
        'module' => 'Module',
        'type' => 'Type',
        'roles_count' => 'Roles',
        'users_count' => 'Users',
        'dependencies_count' => 'Dependencies',
        'dependents_count' => 'Dependents',
        'created_at' => 'Created',
        'updated_at' => 'Updated',
        'status' => 'Status',
        'actions' => 'Actions',
    ],

    // Status labels
    'status' => [
        'active' => 'Active',
        'inactive' => 'Inactive',
        'system' => 'System',
        'used' => 'Used',
        'unused' => 'Unused',
        'deprecated' => 'Deprecated',
    ],

    // Action buttons and links
    'actions' => [
        'create' => 'Create Permission',
        'edit' => 'Edit',
        'delete' => 'Delete',
        'view' => 'View',
        'duplicate' => 'Duplicate',
        'activate' => 'Activate',
        'deactivate' => 'Deactivate',
        'manage_dependencies' => 'Manage Dependencies',
        'view_usage' => 'View Usage',
        'test_permission' => 'Test Permission',
        'export' => 'Export',
        'import' => 'Import',
        'save' => 'Save',
        'cancel' => 'Cancel',
        'back' => 'Back',
        'refresh' => 'Refresh',
        'clear' => 'Clear',
        'apply' => 'Apply',
        'reset' => 'Reset',
    ],

    // Search and filter
    'search' => [
        'search_placeholder' => 'Search permissions...',
        'filter_by_module' => 'Filter by Module',
        'filter_by_type' => 'Filter by Type',
        'filter_by_status' => 'Filter by Status',
        'filter_by_usage' => 'Filter by Usage',
        'all_modules' => 'All Modules',
        'all_types' => 'All Types',
        'all_statuses' => 'All Statuses',
        'show_system' => 'Show System Permissions',
        'show_unused' => 'Show Unused Only',
        'advanced_search' => 'Advanced Search',
    ],

    // View modes
    'view_modes' => [
        'list' => 'List View',
        'grid' => 'Grid View',
        'tree' => 'Tree View',
        'grouped' => 'Grouped View',
    ],

    // Bulk operations
    'bulk' => [
        'select_all' => 'Select All',
        'deselect_all' => 'Deselect All',
        'selected_count' => ':count selected',
        'bulk_actions' => 'Bulk Actions',
        'bulk_delete' => 'Delete Selected',
        'bulk_activate' => 'Activate Selected',
        'bulk_deactivate' => 'Deactivate Selected',
        'bulk_export' => 'Export Selected',
        'confirm_bulk_delete' => 'Are you sure you want to delete :count permissions?',
    ],

    // Messages and notifications
    'messages' => [
        'created_successfully' => 'Permission created successfully.',
        'updated_successfully' => 'Permission updated successfully.',
        'deleted_successfully' => 'Permission deleted successfully.',
        'activated_successfully' => 'Permission activated successfully.',
        'deactivated_successfully' => 'Permission deactivated successfully.',
        'duplicated_successfully' => 'Permission duplicated successfully.',
        'exported_successfully' => 'Permissions exported successfully.',
        'imported_successfully' => 'Permissions imported successfully.',
        'no_permissions_found' => 'No permissions found.',
        'loading' => 'Loading permissions...',
        'processing' => 'Processing...',
        'operation_completed' => 'Operation completed successfully.',
        'bulk_operation_completed' => 'Bulk operation completed. :success successful, :failed failed.',
    ],

    // Confirmation dialogs
    'confirmations' => [
        'delete_title' => 'Delete Permission',
        'delete_message' => 'Are you sure you want to delete this permission?',
        'delete_warning' => 'This action cannot be undone.',
        'delete_system_warning' => 'This is a system permission and cannot be deleted.',
        'delete_used_warning' => 'This permission is used by :count roles and cannot be deleted.',
        'force_delete' => 'Force Delete',
        'type_permission_name' => 'Type the permission name to confirm:',
        'confirm' => 'Confirm',
        'cancel' => 'Cancel',
    ],

    // Dependency management
    'dependencies' => [
        'title' => 'Permission Dependencies',
        'description' => 'Manage permission dependency relationships',
        'add_dependency' => 'Add Dependency',
        'remove_dependency' => 'Remove Dependency',
        'no_dependencies' => 'No dependencies configured',
        'circular_dependency_warning' => 'Circular dependency detected',
        'dependency_chain' => 'Dependency Chain',
        'depends_on' => 'Depends On',
        'required_by' => 'Required By',
        'auto_assign' => 'Auto-assign dependent permissions',
        'dependency_graph' => 'Dependency Graph',
        'view_graph' => 'View Graph',
    ],

    // Usage analysis
    'usage' => [
        'title' => 'Permission Usage Analysis',
        'total_permissions' => 'Total Permissions',
        'used_permissions' => 'Used Permissions',
        'unused_permissions' => 'Unused Permissions',
        'system_permissions' => 'System Permissions',
        'usage_frequency' => 'Usage Frequency',
        'last_used' => 'Last Used',
        'never_used' => 'Never Used',
        'most_used' => 'Most Used Permissions',
        'least_used' => 'Least Used Permissions',
        'usage_by_module' => 'Usage by Module',
        'usage_trend' => 'Usage Trend',
        'roles_using' => 'Roles Using This Permission',
        'users_affected' => 'Users Affected',
    ],

    // Permission testing
    'testing' => [
        'title' => 'Permission Testing Tool',
        'description' => 'Test permission assignments and access control',
        'test_user_permission' => 'Test User Permission',
        'test_role_permission' => 'Test Role Permission',
        'select_user' => 'Select User',
        'select_role' => 'Select Role',
        'select_permission' => 'Select Permission',
        'test_result' => 'Test Result',
        'has_permission' => 'Has Permission',
        'no_permission' => 'No Permission',
        'permission_path' => 'Permission Path',
        'direct_assignment' => 'Direct Assignment',
        'inherited_from_role' => 'Inherited from Role',
        'dependency_chain' => 'Dependency Chain',
        'run_test' => 'Run Test',
        'clear_results' => 'Clear Results',
        'batch_test' => 'Batch Test',
        'export_results' => 'Export Results',
    ],

    // Import/Export
    'import_export' => [
        'export_title' => 'Export Permissions',
        'import_title' => 'Import Permissions',
        'export_description' => 'Export permissions to JSON format',
        'import_description' => 'Import permissions from JSON file',
        'select_file' => 'Select File',
        'file_format' => 'File Format',
        'include_dependencies' => 'Include Dependencies',
        'include_system_permissions' => 'Include System Permissions',
        'conflict_resolution' => 'Conflict Resolution',
        'skip_conflicts' => 'Skip Conflicts',
        'overwrite_existing' => 'Overwrite Existing',
        'merge_permissions' => 'Merge Permissions',
        'preview_import' => 'Preview Import',
        'import_summary' => 'Import Summary',
        'permissions_to_create' => 'Permissions to Create',
        'permissions_to_update' => 'Permissions to Update',
        'conflicts_detected' => 'Conflicts Detected',
        'proceed_import' => 'Proceed with Import',
    ],

    // Templates
    'templates' => [
        'title' => 'Permission Templates',
        'description' => 'Manage permission templates for quick setup',
        'create_template' => 'Create Template',
        'apply_template' => 'Apply Template',
        'template_name' => 'Template Name',
        'template_description' => 'Template Description',
        'template_permissions' => 'Template Permissions',
        'available_templates' => 'Available Templates',
        'custom_templates' => 'Custom Templates',
        'system_templates' => 'System Templates',
        'template_applied' => 'Template applied successfully',
        'permissions_created' => ':count permissions created from template',
    ],

    // Audit log
    'audit' => [
        'title' => 'Permission Audit Log',
        'description' => 'Track all permission-related changes',
        'event_type' => 'Event Type',
        'permission_name' => 'Permission',
        'user' => 'User',
        'timestamp' => 'Timestamp',
        'ip_address' => 'IP Address',
        'user_agent' => 'User Agent',
        'changes' => 'Changes',
        'old_value' => 'Old Value',
        'new_value' => 'New Value',
        'event_types' => [
            'created' => 'Created',
            'updated' => 'Updated',
            'deleted' => 'Deleted',
            'activated' => 'Activated',
            'deactivated' => 'Deactivated',
            'dependency_added' => 'Dependency Added',
            'dependency_removed' => 'Dependency Removed',
        ],
        'filter_by_event' => 'Filter by Event',
        'filter_by_user' => 'Filter by User',
        'filter_by_date' => 'Filter by Date',
        'export_log' => 'Export Log',
        'clear_old_logs' => 'Clear Old Logs',
    ],

    // Validation messages
    'validation' => [
        'name_format' => 'Permission name must follow the format: module.action',
        'name_unique' => 'This permission name already exists',
        'display_name_required' => 'Display name is required',
        'module_required' => 'Module selection is required',
        'type_required' => 'Permission type is required',
        'circular_dependency' => 'Circular dependency detected in permission chain',
        'invalid_dependency' => 'Invalid dependency selection',
        'system_permission_restriction' => 'System permissions have restrictions on modifications',
    ],

    // Help and tooltips
    'help' => [
        'permission_name' => 'Use format: module.action (e.g., users.create, posts.edit)',
        'display_name' => 'Human-readable name shown in the interface',
        'description' => 'Brief description of what this permission allows',
        'module' => 'Logical grouping for related permissions',
        'type' => 'Category of operation this permission controls',
        'dependencies' => 'Other permissions required before this one can be granted',
        'system_permission' => 'Core permissions required for system operation',
        'dependency_graph' => 'Visual representation of permission relationships',
        'usage_analysis' => 'Statistics about how permissions are used across the system',
        'permission_test' => 'Tool to verify permission assignments work correctly',
    ],

    // Pagination
    'pagination' => [
        'showing' => 'Showing :from to :to of :total permissions',
        'per_page' => 'Per page',
        'go_to_page' => 'Go to page',
        'first' => 'First',
        'last' => 'Last',
        'previous' => 'Previous',
        'next' => 'Next',
    ],

    // Empty states
    'empty' => [
        'no_permissions' => 'No permissions found',
        'no_search_results' => 'No permissions match your search criteria',
        'no_dependencies' => 'No dependencies configured',
        'no_usage_data' => 'No usage data available',
        'no_audit_logs' => 'No audit logs found',
        'no_templates' => 'No templates available',
        'create_first_permission' => 'Create your first permission',
        'adjust_filters' => 'Try adjusting your search filters',
    ],

];