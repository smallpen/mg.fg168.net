<?php

return [
    // Basic labels
    'permissions' => 'Permissions',
    'permission' => 'Permission',
    'permission_management' => 'Permission Management',
    'permission_name' => 'Permission Name',
    'display_name' => 'Display Name',
    'description' => 'Description',
    'module' => 'Module',
    'type' => 'Type',
    'usage_status' => 'Usage Status',
    'created_at' => 'Created At',
    'actions' => 'Actions',

    // Statistics
    'total_permissions' => 'Total Permissions',
    'used_permissions' => 'Used Permissions',
    'unused_permissions' => 'Unused Permissions',
    'usage_percentage' => 'Usage Rate',

    // Search and filters
    'search' => 'Search',
    'search_placeholder' => 'Search permission name, display name or description...',
    'filter_by_module' => 'Filter by Module',
    'filter_by_type' => 'Filter by Type',
    'filter_by_usage' => 'Filter by Usage Status',
    'all_modules' => 'All Modules',
    'all_types' => 'All Types',
    'all_usage' => 'All Status',
    'used' => 'Used',
    'unused' => 'Unused',
    'active' => 'Active',
    'inactive' => 'Inactive',
    'clear_filters' => 'Clear Filters',

    // View modes
    'view_mode' => 'View Mode',
    'view_list' => 'List View',
    'view_grouped' => 'Grouped View',
    'view_tree' => 'Tree View',

    // Permission types
    'type_view' => 'View',
    'type_create' => 'Create',
    'type_edit' => 'Edit',
    'type_delete' => 'Delete',
    'type_manage' => 'Manage',

    // Actions
    'create_permission' => 'Create Permission',
    'create_first_permission' => 'Create First Permission',
    'edit' => 'Edit',
    'delete' => 'Delete',
    'export' => 'Export',
    'import' => 'Import',
    'more_actions' => 'More Actions',
    'view_dependencies' => 'View Dependencies',
    'view_usage' => 'View Usage',
    'duplicate' => 'Duplicate',

    // Bulk operations
    'selected_permissions' => ':count permissions selected',
    'cancel_selection' => 'Cancel Selection',

    // Grouped view
    'expand_all' => 'Expand All',
    'collapse_all' => 'Collapse All',
    'modules' => 'Modules',
    'types' => 'Types',
    'total' => 'Total',
    'in' => 'in',

    // Permission details
    'roles' => 'Roles',
    'roles_count' => 'Role Count',
    'dependencies' => 'Dependencies',
    'system_permission' => 'System Permission',
    'has_dependencies' => 'Has Dependencies',
    'view_details' => 'View Details',

    // Status and messages
    'loading' => 'Loading...',
    'no_permissions_found' => 'No Permissions Found',
    'no_permissions_description' => 'No permissions match the current criteria. You can create a new permission.',

    // Permission checks
    'no_permission_create' => 'You do not have permission to create permissions',
    'no_permission_edit' => 'You do not have permission to edit permissions',
    'no_permission_delete' => 'You do not have permission to delete permissions',

    // Error messages
    'permission_not_found' => 'Permission not found',
    'cannot_delete_permission' => 'Cannot delete this permission',
    'delete_failed' => 'Delete failed',
    'permission_deleted' => 'Permission :name has been successfully deleted',

    // Success messages
    'permission_created' => 'Permission created successfully',
    'permission_updated' => 'Permission updated successfully',

    // Additional entries for the view
    'management_description' => 'Manage fine-grained permission control including permission definition, grouping, dependency management and usage monitoring',
    'matrix' => 'Permission Matrix',
    'templates' => 'Permission Templates',

    // Module names
    'modules' => [
        'users' => 'User Management',
        'roles' => 'Role Management',
        'permissions' => 'Permission Management',
        'dashboard' => 'Dashboard',
        'system' => 'System Management',
        'reports' => 'Report Management',
        'settings' => 'Settings Management',
        'audit' => 'Audit Management',
        'monitoring' => 'Monitoring Management',
        'security' => 'Security Management',
    ],

    // Permission Form
    'form' => [
        // Form titles
        'create_title' => 'Create Permission',
        'edit_title' => 'Edit Permission',
        'duplicate_title' => 'Duplicate Permission',
        
        // Form fields
        'name_label' => 'Permission Name',
        'name_placeholder' => 'e.g., users.view',
        'name_help' => 'Use module.action format with lowercase letters, numbers, dots and underscores only',
        'display_name_label' => 'Display Name',
        'display_name_placeholder' => 'e.g., View Users',
        'description_label' => 'Description',
        'description_placeholder' => 'Detailed description of the permission',
        'module_label' => 'Module',
        'module_placeholder' => 'Please select a module',
        'type_label' => 'Type',
        'type_placeholder' => 'Please select a type',
        'dependencies_label' => 'Dependencies',
        'dependencies_help' => 'Other permissions required by this permission',
        
        // System permission warnings
        'system_permission_warning' => 'This is a system permission, some fields cannot be modified',
        'system_permission_name_readonly' => 'System permission name cannot be modified',
        'system_permission_module_readonly' => 'System permission module cannot be modified',
        
        // Buttons
        'save' => 'Save',
        'cancel' => 'Cancel',
        'saving' => 'Saving...',
        
        // Validation messages
        'name_required' => 'Permission name is required',
        'name_format_invalid' => 'Invalid permission name format, please use module.action format',
        'name_exists' => 'This permission name already exists',
        'display_name_required' => 'Display name is required',
        'module_required' => 'Please select a module',
        'type_required' => 'Please select a type',
        'dependencies_invalid' => 'Selected dependencies are invalid',
        'circular_dependency' => 'Cannot create circular dependency',
    ],

    // Permission Deletion
    'delete' => [
        'title' => 'Delete Permission',
        'confirm_message' => 'Are you sure you want to delete permission ":name"?',
        'warning_message' => 'This action cannot be undone.',
        'cannot_delete_system' => 'System permissions cannot be deleted',
        'cannot_delete_in_use' => 'This permission is being used by the following roles and cannot be deleted:',
        'cannot_delete_has_dependents' => 'This permission is depended upon by other permissions and cannot be deleted:',
        'force_delete' => 'Force Delete',
        'force_delete_warning' => 'Force delete will remove all related role assignments and dependencies',
        'delete_button' => 'Delete',
        'cancel_button' => 'Cancel',
        'deleting' => 'Deleting...',
        'success' => 'Permission deleted successfully',
        'failed' => 'Failed to delete permission',
    ],

    // Dependency Management
    'dependencies' => [
        'title' => 'Permission Dependencies',
        'description' => 'Manage dependencies between permissions',
        'depends_on' => 'Depends On',
        'depends_on_description' => 'This permission requires the following permissions',
        'dependents' => 'Dependents',
        'dependents_description' => 'The following permissions require this permission',
        'add_dependency' => 'Add Dependency',
        'remove_dependency' => 'Remove Dependency',
        'no_dependencies' => 'This permission has no dependencies',
        'no_dependents' => 'No other permissions depend on this permission',
        'circular_dependency_error' => 'Cannot create circular dependency',
        'dependency_added' => 'Dependency added',
        'dependency_removed' => 'Dependency removed',
        'dependency_graph' => 'Dependency Graph',
        'show_graph' => 'Show Dependency Graph',
        'hide_graph' => 'Hide Dependency Graph',
    ],

    // Permission Templates
    'templates' => [
        'title' => 'Permission Templates',
        'description' => 'Use predefined templates to quickly create permissions',
        'select_template' => 'Select Template',
        'apply_template' => 'Apply Template',
        'create_template' => 'Create Template',
        'template_name' => 'Template Name',
        'template_description' => 'Template Description',
        'template_permissions' => 'Template Permissions',
        'save_template' => 'Save Template',
        'delete_template' => 'Delete Template',
        'template_applied' => 'Template applied, created :count permissions',
        'template_saved' => 'Template saved',
        'template_deleted' => 'Template deleted',
        'no_templates' => 'No templates available',
        
        // Predefined templates
        'crud_template' => 'CRUD Permission Template',
        'crud_template_description' => 'Create, Read, Update, Delete permissions',
        'view_template' => 'View Permission Template',
        'view_template_description' => 'View-only permissions',
        'admin_template' => 'Admin Permission Template',
        'admin_template_description' => 'Full administrative permissions',
    ],

    // Permission Audit
    'audit' => [
        'title' => 'Permission Audit Log',
        'description' => 'View permission change history',
        'action' => 'Action',
        'user' => 'User',
        'timestamp' => 'Timestamp',
        'changes' => 'Changes',
        'ip_address' => 'IP Address',
        'user_agent' => 'User Agent',
        'old_value' => 'Old Value',
        'new_value' => 'New Value',
        'no_changes' => 'No change records',
        
        // Action types
        'action_created' => 'Created',
        'action_updated' => 'Updated',
        'action_deleted' => 'Deleted',
        'action_dependency_added' => 'Dependency Added',
        'action_dependency_removed' => 'Dependency Removed',
        
        // Filters
        'filter_by_action' => 'Filter by Action',
        'filter_by_user' => 'Filter by User',
        'filter_by_date' => 'Filter by Date',
        'all_actions' => 'All Actions',
        'all_users' => 'All Users',
    ],

    // Permission Usage Analysis
    'usage_analysis' => [
        'title' => 'Permission Usage Analysis',
        'description' => 'Analyze permission usage and statistics',
        'usage_statistics' => 'Usage Statistics',
        'role_usage' => 'Role Usage',
        'user_impact' => 'User Impact',
        'usage_frequency' => 'Usage Frequency',
        'last_used' => 'Last Used',
        'never_used' => 'Never Used',
        'high_usage' => 'High Usage',
        'medium_usage' => 'Medium Usage',
        'low_usage' => 'Low Usage',
        'unused' => 'Unused',
        'roles_using' => 'Roles Using This Permission',
        'users_affected' => 'Users Affected',
        'usage_trend' => 'Usage Trend',
        'recommendation' => 'Recommendation',
        'consider_removal' => 'Consider removing unused permissions',
        'review_necessity' => 'Review the necessity of this permission',
    ],

    // Permission Testing
    'test' => [
        // Basic labels
        'title' => 'Permission Test Tool',
        'description' => 'Test user or role permission configurations to verify the permission system is working correctly',
        'test_configuration' => 'Test Configuration',
        'test_mode' => 'Test Mode',
        'test_mode_description' => 'Select the type of subject to test',
        'user_permission' => 'User Permission',
        'role_permission' => 'Role Permission',

        // Selectors
        'select_user' => 'Select User',
        'select_role' => 'Select Role',
        'select_permission' => 'Select Permission',
        'choose_user' => 'Please select a user',
        'choose_role' => 'Please select a role',
        'choose_permission' => 'Please select a permission',
        'users' => 'users',

        // Test execution
        'run_test' => 'Run Test',
        'testing' => 'Testing...',
        'test_results' => 'Test Results',
        'tested_at' => 'Tested At',
        'tested_user' => 'Tested User',
        'tested_role' => 'Tested Role',
        'tested_permission' => 'Tested Permission',

        // Test results
        'user_has_permission' => 'User ":user" has permission ":permission"',
        'user_lacks_permission' => 'User ":user" does not have permission ":permission"',
        'role_has_permission' => 'Role ":role" has permission ":permission"',
        'role_lacks_permission' => 'Role ":role" does not have permission ":permission"',
        'permission_granted_through_roles' => 'Permission granted through :count role paths',
        'permission_not_found_in_roles' => 'Permission not found in user\'s roles',
        'permission_granted_through' => 'Permission granted through: :sources',
        'permission_not_assigned_to_role' => 'Permission not assigned to this role',

        // Permission path
        'permission_path' => 'Permission Path',
        'show_details' => 'Show Details',
        'hide_details' => 'Hide Details',
        'through_role' => 'Through Role',
        'direct_assignment' => 'Direct Assignment',
        'inheritance' => 'Inheritance',
        'dependency' => 'Dependency',
        'inherited_from' => 'Inherited From',
        'from_parent' => 'from parent role',
        'via_dependency' => 'via dependency',

        // Super admin
        'super_admin_access' => 'Super Admin Access',
        'super_admin_description' => 'Super admin has all permissions',

        // Details
        'username' => 'Username',
        'system_name' => 'System Name',
        'user_count' => 'User Count',
        'module' => 'Module',
        'type' => 'Type',

        // Actions
        'clear_results' => 'Clear Results',
        'export_report' => 'Export Report',
        'no_results_to_export' => 'No test results to export',

        // Validation errors
        'user_required' => 'Please select a user to test',
        'role_required' => 'Please select a role to test',
        'permission_required' => 'Please select a permission to test',
        'user_not_found' => 'Selected user not found',
        'role_not_found' => 'Selected role not found',
        'permission_not_found' => 'Selected permission not found',
        'invalid_selection' => 'Invalid user or permission selection',

        // Batch testing
        'batch_test' => 'Batch Test',
        'select_permissions' => 'Select multiple permissions to test',
        'batch_results' => 'Batch Test Results',
        'permissions_tested' => ':count permissions tested',

        // Test report
        'report_title' => 'Permission Test Report',
        'report_generated_at' => 'Report Generated At',
        'report_generated_by' => 'Report Generated By',
        'test_summary' => 'Test Summary',
        'detailed_results' => 'Detailed Results',

        // Success and error messages
        'test_completed' => 'Permission test completed',
        'test_failed' => 'Permission test failed',
        'report_exported' => 'Test report exported',
        'export_failed' => 'Report export failed',
    ],
];