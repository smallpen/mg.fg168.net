<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Role Management Error Messages
    |--------------------------------------------------------------------------
    |
    | The following language lines are used for role management error messages
    | and validation failures.
    |
    */

    // General errors
    'general' => [
        'operation_failed' => 'Operation failed',
        'unexpected_error' => 'An unexpected error occurred',
        'server_error' => 'Server error occurred',
        'network_error' => 'Network connection error',
        'timeout_error' => 'Operation timed out',
        'access_denied' => 'Access denied',
        'resource_not_found' => 'Resource not found',
        'invalid_request' => 'Invalid request',
    ],

    // Role CRUD errors
    'crud' => [
        'role_not_found' => 'The specified role was not found',
        'role_creation_failed' => 'Failed to create role',
        'role_update_failed' => 'Failed to update role',
        'role_deletion_failed' => 'Failed to delete role',
        'role_duplication_failed' => 'Failed to duplicate role',
        'role_activation_failed' => 'Failed to activate role',
        'role_deactivation_failed' => 'Failed to deactivate role',
    ],

    // Permission errors
    'permissions' => [
        'permission_assignment_failed' => 'Failed to assign permissions to role',
        'permission_removal_failed' => 'Failed to remove permissions from role',
        'permission_sync_failed' => 'Failed to synchronize role permissions',
        'invalid_permission' => 'Invalid permission specified',
        'permission_not_found' => 'Permission not found',
        'permission_dependency_error' => 'Permission dependency conflict',
        'circular_permission_dependency' => 'Circular permission dependency detected',
    ],

    // Hierarchy errors
    'hierarchy' => [
        'invalid_parent_role' => 'Invalid parent role specified',
        'circular_dependency' => 'Circular dependency detected in role hierarchy',
        'parent_role_not_found' => 'Parent role not found',
        'cannot_set_self_as_parent' => 'Role cannot be its own parent',
        'hierarchy_depth_exceeded' => 'Maximum hierarchy depth exceeded',
        'child_role_conflict' => 'Conflict with existing child roles',
    ],

    // System role errors
    'system_roles' => [
        'cannot_modify_system_role' => 'System roles cannot be modified',
        'cannot_delete_system_role' => 'System roles cannot be deleted',
        'cannot_change_system_role_name' => 'System role names cannot be changed',
        'cannot_remove_core_permissions' => 'Core permissions cannot be removed from system roles',
        'system_role_required' => 'At least one system role must exist',
    ],

    // Validation errors
    'validation' => [
        'name_required' => 'Role name is required',
        'name_invalid_format' => 'Role name format is invalid. Use only lowercase letters, numbers, and underscores',
        'name_too_short' => 'Role name must be at least :min characters long',
        'name_too_long' => 'Role name cannot exceed :max characters',
        'name_already_exists' => 'A role with this name already exists',
        'display_name_required' => 'Display name is required',
        'display_name_too_long' => 'Display name cannot exceed :max characters',
        'description_too_long' => 'Description cannot exceed :max characters',
        'invalid_parent_selection' => 'Invalid parent role selection',
        'permissions_invalid' => 'One or more selected permissions are invalid',
    ],

    // Authorization errors
    'authorization' => [
        'insufficient_permissions' => 'You do not have sufficient permissions to perform this action',
        'role_view_denied' => 'You do not have permission to view roles',
        'role_create_denied' => 'You do not have permission to create roles',
        'role_edit_denied' => 'You do not have permission to edit roles',
        'role_delete_denied' => 'You do not have permission to delete roles',
        'permission_manage_denied' => 'You do not have permission to manage role permissions',
        'system_role_access_denied' => 'You do not have permission to access system roles',
    ],

    // Deletion constraints
    'deletion' => [
        'role_has_users' => 'Cannot delete role because it has :count assigned users',
        'role_has_child_roles' => 'Cannot delete role because it has :count child roles',
        'confirmation_required' => 'Deletion confirmation is required',
        'confirmation_mismatch' => 'Confirmation text does not match the role name',
        'force_delete_required' => 'Force delete must be enabled to delete roles with dependencies',
        'system_role_deletion_blocked' => 'System roles cannot be deleted',
        'last_admin_role' => 'Cannot delete the last administrator role',
    ],

    // Bulk operation errors
    'bulk' => [
        'no_roles_selected' => 'No roles selected for bulk operation',
        'invalid_bulk_action' => 'Invalid bulk action specified',
        'bulk_operation_failed' => 'Bulk operation failed',
        'partial_bulk_failure' => 'Bulk operation completed with :failed failures out of :total operations',
        'bulk_permission_assignment_failed' => 'Failed to assign permissions to some roles',
        'bulk_deletion_blocked' => 'Some roles cannot be deleted due to constraints',
        'mixed_role_types_error' => 'Cannot perform bulk operations on mixed role types',
    ],

    // Database errors
    'database' => [
        'connection_failed' => 'Database connection failed',
        'query_failed' => 'Database query failed',
        'transaction_failed' => 'Database transaction failed',
        'constraint_violation' => 'Database constraint violation',
        'duplicate_entry' => 'Duplicate entry detected',
        'foreign_key_constraint' => 'Foreign key constraint violation',
        'data_integrity_error' => 'Data integrity error',
    ],

    // Import/Export errors
    'import_export' => [
        'import_failed' => 'Role import failed',
        'export_failed' => 'Role export failed',
        'invalid_file_format' => 'Invalid file format',
        'file_too_large' => 'File size exceeds maximum limit',
        'corrupted_data' => 'Corrupted data detected',
        'missing_required_fields' => 'Missing required fields in import data',
        'invalid_role_data' => 'Invalid role data format',
    ],

    // Cache errors
    'cache' => [
        'cache_clear_failed' => 'Failed to clear role cache',
        'cache_update_failed' => 'Failed to update role cache',
        'cache_corruption' => 'Role cache corruption detected',
        'cache_unavailable' => 'Role cache service unavailable',
    ],

    // Search and filter errors
    'search' => [
        'search_failed' => 'Role search failed',
        'invalid_search_criteria' => 'Invalid search criteria',
        'search_timeout' => 'Search operation timed out',
        'filter_error' => 'Filter application failed',
        'sorting_error' => 'Sorting operation failed',
    ],

    // API errors
    'api' => [
        'invalid_api_request' => 'Invalid API request',
        'api_rate_limit_exceeded' => 'API rate limit exceeded',
        'api_authentication_failed' => 'API authentication failed',
        'api_authorization_failed' => 'API authorization failed',
        'malformed_request_data' => 'Malformed request data',
        'unsupported_api_version' => 'Unsupported API version',
    ],

    // Session and state errors
    'session' => [
        'session_expired' => 'Your session has expired. Please log in again',
        'invalid_session_state' => 'Invalid session state',
        'concurrent_modification' => 'Role was modified by another user',
        'stale_data_error' => 'The data you are trying to modify is outdated',
        'session_conflict' => 'Session conflict detected',
    ],

    // File system errors
    'filesystem' => [
        'file_not_found' => 'Required file not found',
        'file_permission_denied' => 'File permission denied',
        'disk_space_insufficient' => 'Insufficient disk space',
        'file_write_failed' => 'Failed to write file',
        'file_read_failed' => 'Failed to read file',
        'directory_creation_failed' => 'Failed to create directory',
    ],

    // Configuration errors
    'config' => [
        'invalid_configuration' => 'Invalid role management configuration',
        'missing_configuration' => 'Missing required configuration',
        'configuration_load_failed' => 'Failed to load configuration',
        'permission_config_error' => 'Permission configuration error',
        'role_config_mismatch' => 'Role configuration mismatch',
    ],

    // Localization errors
    'localization' => [
        'translation_missing' => 'Translation missing for role management',
        'invalid_locale' => 'Invalid locale specified',
        'localization_load_failed' => 'Failed to load localization files',
        'unsupported_language' => 'Unsupported language for role management',
    ],

];