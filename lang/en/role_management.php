<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Role Management Language Lines
    |--------------------------------------------------------------------------
    |
    | The following language lines are used for role management functionality
    | including role CRUD operations, permission matrix, and hierarchy management.
    |
    */

    // Page titles and navigation
    'title' => 'Role Management',
    'subtitle' => 'Manage system roles and permission assignments',
    'breadcrumb' => 'Role Management',

    // Role list page
    'list' => [
        'title' => 'Role List',
        'description' => 'Manage system roles and permission settings',
        'create_button' => 'Create Role',
        'create_first' => 'Create First Role',
        'search_placeholder' => 'Search role name, display name or description...',
        'no_results' => 'No roles found',
        'empty_state' => [
            'title' => 'No roles yet',
            'description' => 'Start by creating your first role to manage user permissions',
        ],
    ],

    // Role form (create/edit)
    'form' => [
        'create_title' => 'Create Role',
        'edit_title' => 'Edit Role',
        'basic_info' => 'Basic Information',
        'hierarchy' => 'Role Hierarchy',
        'permissions' => 'Permissions',
        
        'fields' => [
            'name' => 'Role Name',
            'name_placeholder' => 'Enter role name (e.g., admin, editor)',
            'name_help' => 'System identifier for the role. Use lowercase letters and underscores only.',
            'display_name' => 'Display Name',
            'display_name_placeholder' => 'Enter display name',
            'display_name_help' => 'Human-readable name shown in the interface.',
            'description' => 'Description',
            'description_placeholder' => 'Enter role description (optional)',
            'description_help' => 'Brief description of the role\'s purpose and responsibilities.',
            'parent_role' => 'Parent Role',
            'parent_role_placeholder' => 'Select parent role (optional)',
            'parent_role_help' => 'Child roles inherit permissions from their parent role.',
            'no_parent' => 'No Parent (Root Role)',
        ],
        
        'actions' => [
            'save' => 'Save Role',
            'save_and_permissions' => 'Save & Set Permissions',
            'cancel' => 'Cancel',
            'reset' => 'Reset Form',
        ],
    ],

    // Permission matrix
    'permissions' => [
        'title' => 'Permission Matrix',
        'subtitle' => 'Manage role permissions with visual matrix interface',
        'role_permissions' => 'Permissions for :role',
        'module_filter' => 'Filter by Module',
        'all_modules' => 'All Modules',
        'search_permissions' => 'Search permissions...',
        'select_all' => 'Select All',
        'clear_all' => 'Clear All',
        'save_permissions' => 'Save Permissions',
        'inherited_from' => 'Inherited from :parent',
        'direct_permission' => 'Direct Permission',
        'dependency_required' => 'Required by dependency',
        
        'stats' => [
            'total_permissions' => 'Total Permissions',
            'selected_permissions' => 'Selected Permissions',
            'inherited_permissions' => 'Inherited Permissions',
            'module_coverage' => 'Module Coverage',
        ],
        
        'bulk_actions' => [
            'select_module' => 'Select Module',
            'clear_module' => 'Clear Module',
            'toggle_module' => 'Toggle Module',
        ],
    ],

    // Role hierarchy
    'hierarchy' => [
        'title' => 'Role Hierarchy',
        'subtitle' => 'Manage role parent-child relationships',
        'root_roles' => 'Root Roles',
        'child_roles' => 'Child Roles',
        'no_children' => 'No child roles',
        'create_child' => 'Create Child Role',
        'move_role' => 'Move Role',
        'circular_dependency' => 'Circular dependency detected',
        'inheritance_info' => 'Child roles automatically inherit all permissions from their parent roles.',
    ],

    // Role deletion
    'delete' => [
        'title' => 'Delete Role',
        'confirm_title' => 'Confirm Role Deletion',
        'warning' => 'This action cannot be undone',
        'type_name_to_confirm' => 'Type the role name ":name" to confirm deletion',
        'name_placeholder' => 'Enter role name to confirm',
        'force_delete' => 'Force delete (ignore warnings)',
        'force_delete_help' => 'Check this to delete the role even if it has users or child roles',
        
        'checks' => [
            'system_role_error' => 'System roles cannot be deleted',
            'system_role_ok' => 'Not a system role, can be deleted',
            'users_warning' => 'This role has :count users. Deletion will remove their role associations.',
            'users_ok' => 'This role has no user associations',
            'children_warning' => 'This role has :count child roles. They will become root roles after deletion.',
            'children_ok' => 'This role has no child roles',
            'permissions_info' => 'This role has :count permissions. All permission associations will be removed.',
            'permissions_ok' => 'This role has no permission associations',
        ],
        
        'actions' => [
            'confirm_delete' => 'Confirm Delete',
            'cancel' => 'Cancel',
        ],
    ],

    // Bulk operations
    'bulk' => [
        'title' => 'Bulk Operations',
        'selected_count' => ':count roles selected',
        'select_action' => 'Select Action',
        'actions' => [
            'delete' => 'Delete Selected',
            'activate' => 'Activate Selected',
            'deactivate' => 'Deactivate Selected',
            'assign_permissions' => 'Assign Permissions',
            'remove_permissions' => 'Remove Permissions',
            'replace_permissions' => 'Replace Permissions',
        ],
        
        'permissions' => [
            'title' => 'Bulk Permission Assignment',
            'description' => 'Assign permissions to :count selected roles',
            'operation_type' => 'Operation Type',
            'selected_roles' => 'Selected Roles',
            'available_permissions' => 'Available Permissions',
            'execute' => 'Execute Operation',
            
            'operations' => [
                'add' => 'Add Permissions',
                'remove' => 'Remove Permissions',
                'replace' => 'Replace Permissions',
            ],
            
            'operation_descriptions' => [
                'add' => 'Add selected permissions to roles (keeps existing permissions)',
                'remove' => 'Remove selected permissions from roles',
                'replace' => 'Replace all role permissions with selected permissions',
            ],
        ],
        
        'results' => [
            'title' => 'Operation Results',
            'completed_at' => 'Completed at: :time',
            'total_processed' => 'Total Processed',
            'successful' => 'Successful',
            'failed' => 'Failed',
            'success_rate' => 'Success Rate',
            'retry_failed' => 'Retry Failed Items',
            'export_csv' => 'Export CSV',
        ],
    ],

    // Table columns
    'table' => [
        'select' => 'Select',
        'select_all' => 'Select All',
        'name' => 'Role Name',
        'display_name' => 'Display Name',
        'description' => 'Description',
        'permissions_count' => 'Permissions',
        'users_count' => 'Users',
        'parent_role' => 'Parent Role',
        'created_at' => 'Created',
        'updated_at' => 'Updated',
        'actions' => 'Actions',
        'status' => 'Status',
    ],

    // Filters and sorting
    'filters' => [
        'title' => 'Filters',
        'reset' => 'Reset Filters',
        'permission_count' => 'Permission Count',
        'user_count' => 'User Count',
        'role_type' => 'Role Type',
        'status' => 'Status',
        'parent_role' => 'Parent Role',
        
        'options' => [
            'all' => 'All',
            'none' => 'None',
            'system_roles' => 'System Roles',
            'custom_roles' => 'Custom Roles',
            'root_roles' => 'Root Roles',
            'child_roles' => 'Child Roles',
            'active' => 'Active',
            'inactive' => 'Inactive',
            'low' => 'Low (:count)',
            'medium' => 'Medium (:range)',
            'high' => 'High (:count+)',
        ],
    ],

    'sort' => [
        'name' => 'Role Name',
        'display_name' => 'Display Name',
        'created_at' => 'Created Date',
        'updated_at' => 'Updated Date',
        'users_count' => 'User Count',
        'permissions_count' => 'Permission Count',
    ],

    // Actions
    'actions' => [
        'view' => 'View',
        'edit' => 'Edit',
        'duplicate' => 'Duplicate',
        'delete' => 'Delete',
        'activate' => 'Activate',
        'deactivate' => 'Deactivate',
        'manage_permissions' => 'Manage Permissions',
        'view_hierarchy' => 'View Hierarchy',
        'create_child' => 'Create Child Role',
    ],

    // Status labels
    'status' => [
        'active' => 'Active',
        'inactive' => 'Inactive',
        'system' => 'System',
        'custom' => 'Custom',
        'root' => 'Root',
        'child' => 'Child',
    ],

    // Statistics
    'stats' => [
        'total_roles' => 'Total Roles',
        'active_roles' => 'Active Roles',
        'inactive_roles' => 'Inactive Roles',
        'system_roles' => 'System Roles',
        'custom_roles' => 'Custom Roles',
        'roles_with_users' => 'Roles with Users',
        'roles_with_permissions' => 'Roles with Permissions',
        'total_assigned_users' => 'Total Assigned Users',
        'average_permissions_per_role' => 'Avg. Permissions per Role',
    ],

    // Success messages
    'messages' => [
        'created' => 'Role ":name" has been successfully created',
        'updated' => 'Role ":name" has been successfully updated',
        'deleted' => 'Role ":name" has been successfully deleted',
        'duplicated' => 'Role ":name" has been successfully duplicated',
        'activated' => 'Role ":name" has been activated',
        'deactivated' => 'Role ":name" has been deactivated',
        'permissions_updated' => 'Permissions for role ":name" have been updated',
        'hierarchy_updated' => 'Role hierarchy has been updated',
        'bulk_operation_completed' => 'Bulk operation completed successfully',
        'bulk_permissions_updated' => 'Permissions updated for :count roles',
    ],

    // Error messages
    'errors' => [
        'not_found' => 'Role not found',
        'creation_failed' => 'Failed to create role',
        'update_failed' => 'Failed to update role',
        'deletion_failed' => 'Failed to delete role',
        'duplicate_name' => 'Role name already exists',
        'invalid_parent' => 'Invalid parent role selected',
        'circular_dependency' => 'Cannot create circular dependency in role hierarchy',
        'system_role_modification' => 'System roles cannot be modified',
        'system_role_deletion' => 'System roles cannot be deleted',
        'role_has_users' => 'Cannot delete role with assigned users',
        'role_has_children' => 'Cannot delete role with child roles',
        'permission_assignment_failed' => 'Failed to assign permissions',
        'unauthorized' => 'You do not have permission to perform this action',
        'validation_failed' => 'Validation failed',
        'bulk_operation_failed' => 'Bulk operation failed',
        'confirmation_mismatch' => 'Confirmation text does not match',
        'force_delete_required' => 'Force delete must be enabled for roles with dependencies',
    ],

    // Validation messages
    'validation' => [
        'name_required' => 'Role name is required',
        'name_unique' => 'Role name must be unique',
        'name_format' => 'Role name must contain only lowercase letters, numbers, and underscores',
        'name_min' => 'Role name must be at least :min characters',
        'name_max' => 'Role name cannot exceed :max characters',
        'display_name_required' => 'Display name is required',
        'display_name_max' => 'Display name cannot exceed :max characters',
        'description_max' => 'Description cannot exceed :max characters',
        'parent_exists' => 'Selected parent role does not exist',
        'parent_not_self' => 'Role cannot be its own parent',
        'parent_no_circular' => 'Parent selection would create circular dependency',
    ],

    // Permission names localization
    'permission_names' => [
        // Role management permissions
        'roles.view' => 'View Roles',
        'roles.create' => 'Create Roles',
        'roles.edit' => 'Edit Roles',
        'roles.delete' => 'Delete Roles',
        'roles.manage_permissions' => 'Manage Role Permissions',
        
        // User management permissions
        'users.view' => 'View Users',
        'users.create' => 'Create Users',
        'users.edit' => 'Edit Users',
        'users.delete' => 'Delete Users',
        'users.assign_roles' => 'Assign User Roles',
        
        // Permission management permissions
        'permissions.view' => 'View Permissions',
        'permissions.create' => 'Create Permissions',
        'permissions.edit' => 'Edit Permissions',
        'permissions.delete' => 'Delete Permissions',
        
        // Dashboard permissions
        'dashboard.view' => 'View Dashboard',
        'dashboard.stats' => 'View Statistics',
        
        // System permissions
        'system.settings' => 'System Settings',
        'system.logs' => 'View System Logs',
        'system.maintenance' => 'System Maintenance',
        
        // Profile permissions
        'profile.view' => 'View Profile',
        'profile.edit' => 'Edit Profile',
    ],

    // Permission descriptions localization
    'permission_descriptions' => [
        // Role management permissions
        'roles.view' => 'Can view role list and details',
        'roles.create' => 'Can create new roles',
        'roles.edit' => 'Can edit role information and settings',
        'roles.delete' => 'Can delete roles',
        'roles.manage_permissions' => 'Can assign or remove permissions from roles',
        
        // User management permissions
        'users.view' => 'Can view user list and details',
        'users.create' => 'Can create new user accounts',
        'users.edit' => 'Can edit user information and settings',
        'users.delete' => 'Can delete user accounts',
        'users.assign_roles' => 'Can assign or remove roles from users',
        
        // Permission management permissions
        'permissions.view' => 'Can view permission list and details',
        'permissions.create' => 'Can create new permissions',
        'permissions.edit' => 'Can edit permission information',
        'permissions.delete' => 'Can delete permissions',
        
        // Dashboard permissions
        'dashboard.view' => 'Can access admin dashboard',
        'dashboard.stats' => 'Can view system statistics',
        
        // System permissions
        'system.settings' => 'Can modify system settings',
        'system.logs' => 'Can view system logs and error records',
        'system.maintenance' => 'Can perform system maintenance operations',
        
        // Profile permissions
        'profile.view' => 'Can view own profile information',
        'profile.edit' => 'Can edit own profile information',
    ],

    // Module names localization
    'modules' => [
        'roles' => 'Role Management',
        'users' => 'User Management',
        'permissions' => 'Permission Management',
        'dashboard' => 'Dashboard',
        'system' => 'System Management',
        'profile' => 'Profile Management',
    ],

    // Role names localization (for system roles)
    'role_names' => [
        'super_admin' => 'Super Administrator',
        'admin' => 'Administrator',
        'moderator' => 'Moderator',
        'editor' => 'Editor',
        'user' => 'User',
        'guest' => 'Guest',
    ],

    // Role descriptions localization (for system roles)
    'role_descriptions' => [
        'super_admin' => 'Highest level administrator with all system permissions',
        'admin' => 'System administrator with most management permissions',
        'moderator' => 'Moderator with content management permissions',
        'editor' => 'Content editor with publishing permissions',
        'user' => 'Regular system user with basic permissions',
        'guest' => 'Guest user with limited read-only permissions',
    ],

];