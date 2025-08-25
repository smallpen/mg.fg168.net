<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Permission Management Language Lines
    |--------------------------------------------------------------------------
    |
    | The following language lines are used for various elements and messages
    | in the permission management system.
    |
    */

    // Titles
    'titles' => [
        'permission_management' => 'Permission Management',
    ],

    // Basic labels
    'permission_name' => 'Permission Name',
    'display_name' => 'Display Name',
    'description' => 'Description',
    'module' => 'Module',
    'type' => 'Type',
    'roles_count' => 'Roles Count',
    'usage_status' => 'Usage Status',
    'created_at' => 'Created At',
    'actions_label' => 'Actions',

    // Search and filters
    'search_label' => 'Search Permissions',
    'search' => [
        'search_placeholder' => 'Search permission name, display name or description...',
        'all_usage' => 'All Usage Status',
        'used' => 'Used',
        'unused' => 'Unused',
    ],

    'filter_by_module' => 'Filter by Module',
    'filter_by_type' => 'Filter by Type',
    'filter_by_usage' => 'Filter by Usage Status',
    'all_modules' => 'All Modules',
    'all_types' => 'All Types',
    'clear_filters' => 'Clear Filters',

    // View modes
    'view_mode' => 'View Mode',
    'view_modes' => [
        'list' => 'List View',
        'grouped' => 'Grouped View',
        'tree' => 'Tree View',
    ],

    // Action buttons
    'create_permission' => 'Create Permission',
    'create_first_permission' => 'Create First Permission',
    'edit' => 'Edit',
    'delete' => 'Delete',
    'export' => 'Export',
    'import' => 'Import',
    'more_actions' => 'More Actions',
    'view_dependencies' => 'View Dependencies',
    'view_usage' => 'View Usage',

    // Bulk operations
    'selected_permissions' => ':count permissions selected',
    'cancel_selection' => 'Cancel Selection',

    // Status and labels
    'status' => [
        'used' => 'Used',
        'unused' => 'Unused',
    ],

    'types' => [
        'view' => 'View',
        'create' => 'Create',
        'edit' => 'Edit',
        'delete' => 'Delete',
        'manage' => 'Manage',
        'export' => 'Export',
        'import' => 'Import',
        'test' => 'Test',
    ],

    // Statistics
    'usage' => [
        'total_permissions' => 'Total Permissions',
        'used_permissions' => 'Used Permissions',
        'unused_permissions' => 'Unused Permissions',
        'usage_frequency' => 'Usage Frequency',
    ],

    // Empty states
    'no_permissions_found' => 'No permissions found',
    'no_permissions_description' => 'Try adjusting your search criteria or create a new permission',

    // Loading states
    'loading' => 'Loading...',

    // Messages
    'messages' => [
        'no_permission_create' => 'You do not have permission to create permissions',
        'no_permission_edit' => 'You do not have permission to edit permissions',
        'no_permission_delete' => 'You do not have permission to delete permissions',
        'permission_not_found' => 'Permission not found',
        'cannot_delete_permission' => 'Cannot delete this permission',
    ],

];