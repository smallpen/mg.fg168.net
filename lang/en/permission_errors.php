<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Permission Management Error Messages
    |--------------------------------------------------------------------------
    |
    | The following language lines are used for permission management error
    | messages and validation failures.
    |
    */

    // General errors
    'general' => [
        'operation_failed' => 'Operation failed',
        'unexpected_error' => 'An unexpected error occurred',
        'server_error' => 'Server error',
        'network_error' => 'Network connection error',
        'timeout_error' => 'Operation timeout',
        'access_denied' => 'Access denied',
        'resource_not_found' => 'Resource not found',
        'invalid_request' => 'Invalid request',
    ],

    // Permission CRUD errors
    'crud' => [
        'permission_not_found' => 'Permission not found',
        'permission_creation_failed' => 'Failed to create permission',
        'permission_update_failed' => 'Failed to update permission',
        'permission_deletion_failed' => 'Failed to delete permission',
        'permission_duplication_failed' => 'Failed to duplicate permission',
        'permission_activation_failed' => 'Failed to activate permission',
        'permission_deactivation_failed' => 'Failed to deactivate permission',
    ],

    // Dependency errors
    'dependencies' => [
        'dependency_creation_failed' => 'Failed to create permission dependency',
        'dependency_removal_failed' => 'Failed to remove permission dependency',
        'dependency_sync_failed' => 'Failed to sync permission dependencies',
        'invalid_dependency' => 'Invalid dependency permission',
        'dependency_not_found' => 'Dependency permission not found',
        'circular_dependency' => 'Circular dependency detected',
        'dependency_chain_too_deep' => 'Dependency chain too deep',
        'self_dependency' => 'Permission cannot depend on itself',
    ],

    // System permission errors
    'system_permissions' => [
        'cannot_modify_system_permission' => 'System permissions cannot be modified',
        'cannot_delete_system_permission' => 'System permissions cannot be deleted',
        'cannot_change_system_permission_name' => 'System permission name cannot be changed',
        'cannot_remove_system_dependencies' => 'System permission dependencies cannot be removed',
        'system_permission_required' => 'At least one system permission must exist',
    ],

    // Validation errors
    'validation' => [
        'name_required' => 'Permission name is required',
        'name_invalid_format' => 'Invalid permission name format. Use module.action format',
        'name_too_short' => 'Permission name must be at least :min characters',
        'name_too_long' => 'Permission name cannot exceed :max characters',
        'name_already_exists' => 'A permission with this name already exists',
        'display_name_required' => 'Display name is required',
        'display_name_too_long' => 'Display name cannot exceed :max characters',
        'description_too_long' => 'Description cannot exceed :max characters',
        'invalid_module' => 'Invalid module selection',
        'invalid_type' => 'Invalid permission type selection',
        'dependencies_invalid' => 'One or more selected dependencies are invalid',
        'module_required' => 'Module is required',
        'type_required' => 'Permission type is required',
    ],

    // Authorization errors
    'authorization' => [
        'insufficient_permissions' => 'You do not have sufficient permissions to perform this action',
        'permission_view_denied' => 'You do not have permission to view permissions',
        'permission_create_denied' => 'You do not have permission to create permissions',
        'permission_edit_denied' => 'You do not have permission to edit permissions',
        'permission_delete_denied' => 'You do not have permission to delete permissions',
        'dependency_manage_denied' => 'You do not have permission to manage permission dependencies',
        'system_permission_access_denied' => 'You do not have permission to access system permissions',
        'template_manage_denied' => 'You do not have permission to manage permission templates',
        'audit_view_denied' => 'You do not have permission to view audit logs',
        'test_permission_denied' => 'You do not have permission to test permissions',
    ],

    // Deletion restrictions
    'deletion' => [
        'permission_has_roles' => 'Cannot delete permission because it is used by :count roles',
        'permission_has_dependents' => 'Cannot delete permission because :count permissions depend on it',
        'confirmation_required' => 'Deletion confirmation required',
        'confirmation_mismatch' => 'Confirmation text does not match permission name',
        'force_delete_required' => 'Force delete must be enabled to delete permissions with dependencies',
        'system_permission_deletion_blocked' => 'System permissions cannot be deleted',
        'core_permission_deletion_blocked' => 'Core permissions cannot be deleted',
    ],

    // Bulk operation errors
    'bulk' => [
        'no_permissions_selected' => 'No permissions selected for bulk operation',
        'invalid_bulk_action' => 'Invalid bulk action specified',
        'bulk_operation_failed' => 'Bulk operation failed',
        'partial_bulk_failure' => 'Bulk operation completed but :failed out of :total operations failed',
        'bulk_dependency_assignment_failed' => 'Failed to assign dependencies to some permissions',
        'bulk_deletion_blocked' => 'Some permissions could not be deleted due to restrictions',
        'mixed_permission_types_error' => 'Cannot perform bulk operations on mixed permission types',
    ],

    // Import/Export errors
    'import_export' => [
        'import_failed' => 'Permission import failed',
        'export_failed' => 'Permission export failed',
        'invalid_file_format' => 'Invalid file format',
        'file_too_large' => 'File size exceeds maximum limit',
        'corrupted_data' => 'Corrupted data detected',
        'missing_required_fields' => 'Missing required fields in import data',
        'invalid_permission_data' => 'Invalid permission data format',
        'version_incompatible' => 'Import file version incompatible',
        'conflict_resolution_failed' => 'Conflict resolution failed',
        'dependency_resolution_failed' => 'Dependency resolution failed',
        'preview_generation_failed' => 'Preview generation failed',
    ],

    // Template errors
    'templates' => [
        'template_not_found' => 'Template not found',
        'template_creation_failed' => 'Failed to create template',
        'template_update_failed' => 'Failed to update template',
        'template_deletion_failed' => 'Failed to delete template',
        'template_application_failed' => 'Failed to apply template',
        'invalid_template_data' => 'Invalid template data',
        'template_name_exists' => 'Template name already exists',
        'template_permissions_invalid' => 'Template permission configuration invalid',
    ],

    // Testing errors
    'testing' => [
        'test_execution_failed' => 'Permission test execution failed',
        'invalid_test_subject' => 'Invalid test subject',
        'test_user_not_found' => 'Test user not found',
        'test_role_not_found' => 'Test role not found',
        'test_permission_not_found' => 'Test permission not found',
        'batch_test_failed' => 'Batch test failed',
        'test_report_generation_failed' => 'Test report generation failed',
        'test_data_invalid' => 'Test data invalid',
    ],

    // Audit errors
    'audit' => [
        'audit_log_creation_failed' => 'Failed to create audit log',
        'audit_log_retrieval_failed' => 'Failed to retrieve audit log',
        'audit_log_cleanup_failed' => 'Failed to cleanup audit log',
        'audit_data_corruption' => 'Audit data corruption',
        'audit_permission_denied' => 'No permission to access audit logs',
    ],

    // Usage analysis errors
    'usage_analysis' => [
        'analysis_failed' => 'Usage analysis failed',
        'statistics_calculation_failed' => 'Statistics calculation failed',
        'usage_data_unavailable' => 'Usage data unavailable',
        'analysis_timeout' => 'Analysis operation timeout',
        'cache_update_failed' => 'Cache update failed',
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

    // Cache errors
    'cache' => [
        'cache_clear_failed' => 'Failed to clear permission cache',
        'cache_update_failed' => 'Failed to update permission cache',
        'cache_corruption' => 'Permission cache corruption detected',
        'cache_unavailable' => 'Permission cache service unavailable',
    ],

    // Search and filter errors
    'search' => [
        'search_failed' => 'Permission search failed',
        'invalid_search_criteria' => 'Invalid search criteria',
        'search_timeout' => 'Search operation timeout',
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
        'session_expired' => 'Your session has expired, please log in again',
        'invalid_session_state' => 'Invalid session state',
        'concurrent_modification' => 'Permission has been modified by another user',
        'stale_data_error' => 'The data you are trying to modify is stale',
        'session_conflict' => 'Session conflict detected',
    ],

    // Filesystem errors
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
        'invalid_configuration' => 'Invalid permission management configuration',
        'missing_configuration' => 'Missing required configuration',
        'configuration_load_failed' => 'Failed to load configuration',
        'permission_config_error' => 'Permission configuration error',
        'module_config_mismatch' => 'Module configuration mismatch',
    ],

    // Localization errors
    'localization' => [
        'translation_missing' => 'Permission management translation missing',
        'invalid_locale' => 'Invalid locale specified',
        'localization_load_failed' => 'Failed to load localization files',
        'unsupported_language' => 'Unsupported language for permission management',
    ],

];