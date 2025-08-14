<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Admin Interface Language Lines
    |--------------------------------------------------------------------------
    |
    | The following language lines are used for various elements and messages
    | in the admin interface.
    |
    */

    'title' => 'Admin Management System',
    'welcome' => 'Welcome to Admin Management System',

    // Navigation menu
    'navigation' => [
        'dashboard' => 'Dashboard',
        'users' => 'User Management',
        'roles' => 'Role Management',
        'permissions' => 'Permission Management',
        'settings' => 'System Settings',
    ],

    // Dashboard
    'dashboard' => [
        'title' => 'Dashboard',
        'stats' => [
            'total_users' => 'Total Users',
            'total_roles' => 'Total Roles',
            'total_permissions' => 'Total Permissions',
            'online_users' => 'Online Users',
        ],
        'recent_activity' => 'Recent Activity',
        'quick_actions' => 'Quick Actions',
    ],

    // User management
    'users' => [
        'title' => 'User Management',
        'list' => 'User List',
        'create' => 'Create User',
        'edit' => 'Edit User',
        'delete' => 'Delete User',
        'search' => 'Search Users',
        'filter' => 'Filter',
        'username' => 'Username',
        'name' => 'Name',
        'email' => 'Email',
        'roles' => 'Roles',
        'status' => 'Status',
        'created_at' => 'Created At',
        'actions' => 'Actions',
        'active' => 'Active',
        'inactive' => 'Inactive',
        'no_users' => 'No users found',
        'management' => 'Manage system user accounts and permissions',
        'add_user' => 'Add User',
        'search_placeholder' => 'Search name, username or email...',
        'filter_by_role' => 'Filter by Role',
        'filter_by_status' => 'Filter by Status',
        'all_roles' => 'All Roles',
        'all_status' => 'All Status',
        'clear_filters' => 'Clear Filters',
        'total_users' => 'Total Users',
        'active_users' => 'Active Users',
        'inactive_users' => 'Inactive Users',
        'users_with_roles' => 'Users with Roles',
        'no_role' => 'No Role',
        'recent_users' => 'Recent Users',
        'statistics' => 'Statistics',
        'show_details' => 'Show Details',
        'hide_details' => 'Hide Details',
        'refresh' => 'Refresh',
        'loading_stats' => 'Loading statistics...',
        'stats_refreshed' => 'Statistics refreshed',
        'total_users_desc' => 'All users in the system',
        'active_users_desc' => 'Currently active users',
        'inactive_users_desc' => 'Currently inactive users',
        'recent_users_desc' => 'Users added in the last 30 days',
        'activity_rate' => 'Activity Rate',
        'active_vs_total' => 'Active / Total',
        'top_role' => 'Most Popular Role',
        'users_count' => ':count users',
        'role_distribution' => 'Role Distribution',
        'users_without_roles_warning' => 'Warning: Some users have no roles assigned',
        'users_without_roles_count' => ':count users have no roles',
        'no_roles' => 'No Roles',
        'toggle_status' => 'Toggle Status',
        'cannot_disable_self' => 'You cannot disable your own account',
        'cannot_modify_super_admin' => 'You do not have permission to modify super admin status',
        'user_activated' => 'User activated',
        'user_deactivated' => 'User deactivated',
        'no_permission_view' => 'You do not have permission to view user list',
        'no_permission_edit' => 'You do not have permission to edit user status',
        'no_permission_create' => 'You do not have permission to create users',
        'no_permission_delete' => 'You do not have permission to delete users',
        'search_help' => 'Try adjusting your search criteria or filter settings',
        
        // User deletion related
        'confirm_delete_title' => 'Confirm Permanent User Deletion',
        'confirm_disable_title' => 'Confirm User Deactivation',
        'select_action' => 'Please select an action',
        'disable_user' => 'Disable User',
        'delete_permanently' => 'Delete User Permanently',
        'recommended' => 'Recommended',
        'irreversible' => 'Irreversible',
        'delete_action_description' => 'This action will permanently delete the user and all related data, including role associations. This action cannot be undone, please consider carefully.',
        'disable_action_description' => 'This action will disable the user account, preventing them from logging into the system. User data will be preserved and you can reactivate this account at any time.',
        'confirm_username_label' => 'Please enter the username ":username" to confirm deletion',
        'confirm_username_required' => 'Please enter the username to confirm deletion',
        'confirm_username_mismatch' => 'The entered username is incorrect',
        'confirm_delete' => 'Confirm Delete',
        'confirm_disable' => 'Confirm Disable',
        'processing' => 'Processing...',
        'delete_failed' => 'Operation failed',
        'user_deleted_permanently' => 'User ":username" has been permanently deleted',
        'user_disabled' => 'User ":username" has been disabled',
        'cannot_delete_self' => 'You cannot delete your own account',
        'cannot_delete_super_admin' => 'You do not have permission to delete super admin',
        'cannot_disable_self' => 'You cannot disable your own account',
        'type_username_to_confirm' => 'Please type the complete username to confirm this action',
        'user_not_found' => 'The specified user was not found',
        'never' => 'Never',
        'just_now' => 'Just now',
        'loading' => 'Loading...',
    ],

    // Role management
    'roles' => [
        'title' => 'Role Management',
        'list' => 'Role List',
        'create' => 'Create Role',
        'edit' => 'Edit Role',
        'delete' => 'Delete Role',
        'name' => 'Role Name',
        'display_name' => 'Display Name',
        'description' => 'Description',
        'permissions' => 'Permissions',
        'users_count' => 'Users Count',
        'permissions_count' => 'Permissions Count',
        'no_roles' => 'No roles found',
        
        // Role names localization
        'names' => [
            'super_admin' => 'Super Administrator',
            'admin' => 'Administrator',
            'moderator' => 'Moderator',
            'user' => 'User',
            'guest' => 'Guest',
        ],
        
        // Role descriptions localization
        'descriptions' => [
            'super_admin' => 'Highest level administrator with all system permissions',
            'admin' => 'System administrator with most management permissions',
            'moderator' => 'Moderator with content management permissions',
            'user' => 'Regular system user',
            'guest' => 'Guest user with limited permissions',
        ],
        'management' => 'Manage system roles and permission settings',
        'add_role' => 'Add Role',
        'search' => 'Search Roles',
        'search_placeholder' => 'Search role name, display name or description...',
        'filter_by_status' => 'Filter by Status',
        'all_status' => 'All Status',
        'active' => 'Active',
        'inactive' => 'Inactive',
        'status' => 'Status',
        'created_at' => 'Created At',
        'actions' => 'Actions',
        'clear_filters' => 'Clear Filters',
        'total_roles' => 'Total Roles',
        'active_roles' => 'Active Roles',
        'inactive_roles' => 'Inactive Roles',
        'total_assigned_users' => 'Total Assigned Users',
        'search_help' => 'Try adjusting your search criteria or filter settings',
        'manage_permissions' => 'Manage Permissions',
        'cannot_disable_super_admin' => 'Cannot disable super admin role',
        'status_not_supported' => 'Role status management is not supported in this system version',
        'role_activated' => 'Role activated',
        'role_deactivated' => 'Role deactivated',
        'no_permission_view' => 'You do not have permission to view role list',
        'no_permission_edit' => 'You do not have permission to edit roles',
        'no_permission_create' => 'You do not have permission to create roles',
        'no_permission_delete' => 'You do not have permission to delete roles',
        'basic_info' => 'Basic Information',
        'cannot_modify_super_admin' => 'Cannot modify super admin role',
        'cannot_change_super_admin_name' => 'Cannot change super admin role name',
        
        // Role deletion related
        'confirm_delete_title' => 'Confirm Role Deletion',
        'confirm_role_name_label' => 'Please enter the role name ":name" to confirm deletion',
        'confirm_role_name_required' => 'Please enter the role name to confirm deletion',
        'confirm_role_name_mismatch' => 'The entered role name is incorrect',
        'type_role_name_to_confirm' => 'Please type the complete role name to confirm this action',
        'confirm_delete' => 'Confirm Delete',
        'processing' => 'Processing...',
        'delete_failed' => 'Delete failed',
        'role_not_found' => 'The specified role was not found',
        'cannot_delete_super_admin' => 'Cannot delete super admin role',
        'cannot_delete_system_role' => 'You do not have permission to delete system default roles',
        'role_has_users' => 'This role currently has :count users, please check the force delete option first',
        'delete_warning_title' => 'Delete Warning',
        'delete_warning_users' => 'This operation will affect :count users, they will lose all permissions from this role.',
        'delete_warning_permissions' => 'This role has :count permissions, these permission associations will be removed.',
        'delete_warning_irreversible' => 'This operation cannot be undone, please consider carefully.',
        'users_will_be_affected' => 'Users Will Be Affected',
        'users_affected_description' => 'Deleting this role will affect :count users, they will lose all permissions provided by this role.',
        'force_delete_confirmation' => 'I understand the impact of this operation and still want to force delete this role',
        'role_deleted_successfully' => 'Role ":name" has been successfully deleted, affecting :users_affected users',
    ],

    // Permission management
    'permissions' => [
        'title' => 'Permission Management',
        'matrix' => 'Permission Matrix',
        'matrix_description' => 'Manage role and permission relationships with batch operations and real-time preview',
        'name' => 'Permission Name',
        'display_name' => 'Display Name',
        'description' => 'Description',
        'module' => 'Module',
        'no_permissions' => 'No permissions found',
        'search' => 'Search Permissions',
        'search_placeholder' => 'Search permission name, display name or description...',
        'filter_by_module' => 'Filter by Module',
        'all_modules' => 'All Modules',
        'clear_filters' => 'Clear Filters',
        'search_help' => 'Try adjusting your search criteria or filter settings',
        'no_permission_edit' => 'You do not have permission to edit permission settings',
    ],

    // Common actions
    'actions' => [
        'create' => 'Create',
        'edit' => 'Edit',
        'delete' => 'Delete',
        'save' => 'Save',
        'cancel' => 'Cancel',
        'confirm' => 'Confirm',
        'back' => 'Back',
        'search' => 'Search',
        'filter' => 'Filter',
        'reset' => 'Reset',
        'submit' => 'Submit',
        'close' => 'Close',
        'view' => 'View',
        'update' => 'Update',
    ],

    // Status messages
    'messages' => [
        'success' => [
            'created' => ':item has been successfully created',
            'updated' => ':item has been successfully updated',
            'deleted' => ':item has been successfully deleted',
            'saved' => 'Data has been successfully saved',
        ],
        'error' => [
            'create_failed' => 'Failed to create :item',
            'update_failed' => 'Failed to update :item',
            'delete_failed' => 'Failed to delete :item',
            'not_found' => 'The specified :item was not found',
            'permission_denied' => 'Permission denied',
        ],
        'confirm' => [
            'delete' => 'Are you sure you want to delete this :item? This action cannot be undone.',
        ],
    ],

    // Form validation
    'validation' => [
        'required' => 'The :attribute field is required',
        'unique' => 'The :attribute has already been taken',
        'min' => 'The :attribute must be at least :min characters',
        'max' => 'The :attribute may not be greater than :max characters',
        'email' => 'The :attribute must be a valid email address',
        'confirmed' => 'The :attribute confirmation does not match',
    ],

    // Pagination
    'pagination' => [
        'previous' => 'Previous',
        'next' => 'Next',
        'showing' => 'Showing :first to :last of :total results',
        'per_page' => 'Per page',
        'navigation' => 'Pagination Navigation',
    ],

    // Theme and language
    'theme' => [
        'title' => 'Theme Settings',
        'light' => 'Light Theme',
        'dark' => 'Dark Theme',
        'toggle' => 'Toggle Theme',
    ],

    'language' => [
        'title' => 'Language Settings',
        'current' => 'Current Language',
        'select' => 'Select Language',
        'zh_TW' => '正體中文',
        'en' => 'English',
        'unsupported' => 'Unsupported language',
        'switched' => 'Language switched to :language',
    ],

];