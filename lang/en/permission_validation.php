<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Permission Validation Language Lines
    |--------------------------------------------------------------------------
    |
    | The following language lines contain the default validation messages
    | used by the permission management system.
    |
    */

    'name' => [
        'required' => 'The permission name field is required.',
        'string' => 'The permission name must be a string.',
        'max' => 'The permission name may not be greater than :max characters.',
        'min' => 'The permission name must be at least :min characters.',
        'unique' => 'The permission name has already been taken.',
        'regex' => 'The permission name format is invalid. Use format: module.action',
        'reserved' => 'The permission name ":name" is reserved and cannot be used.',
    ],

    'display_name' => [
        'required' => 'The display name field is required.',
        'string' => 'The display name must be a string.',
        'max' => 'The display name may not be greater than :max characters.',
        'min' => 'The display name must be at least :min characters.',
    ],

    'description' => [
        'string' => 'The description must be a string.',
        'max' => 'The description may not be greater than :max characters.',
    ],

    'module' => [
        'required' => 'The module field is required.',
        'string' => 'The module must be a string.',
        'in' => 'The selected module is invalid.',
        'exists' => 'The selected module does not exist.',
    ],

    'type' => [
        'required' => 'The permission type field is required.',
        'string' => 'The permission type must be a string.',
        'in' => 'The selected permission type is invalid.',
    ],

    'dependencies' => [
        'array' => 'The dependencies must be an array.',
        'exists' => 'One or more selected dependencies are invalid.',
        'circular' => 'Circular dependency detected. Permission cannot depend on itself or create a circular chain.',
        'max_depth' => 'Dependency chain exceeds maximum depth of :max levels.',
        'self_reference' => 'Permission cannot depend on itself.',
        'invalid_permission' => 'Invalid permission ID in dependencies: :id',
    ],

    'is_system' => [
        'boolean' => 'The system permission field must be true or false.',
        'immutable' => 'System permission status cannot be changed for existing permissions.',
    ],

    'is_active' => [
        'boolean' => 'The active status field must be true or false.',
    ],

    // Custom validation rules
    'custom' => [
        'permission_name_format' => 'Permission name must follow the format: module.action (e.g., users.create)',
        'system_permission_modification' => 'System permissions cannot be modified in this way.',
        'permission_in_use' => 'This permission is currently in use and cannot be deleted.',
        'dependency_exists' => 'This dependency relationship already exists.',
        'invalid_dependency_target' => 'Cannot create dependency on the specified permission.',
        'module_mismatch' => 'Permission module does not match the expected module.',
        'type_restriction' => 'This permission type is not allowed for the selected module.',
        'name_pattern' => 'Permission name must contain only lowercase letters, numbers, dots, and underscores.',
        'reserved_name' => 'This permission name is reserved for system use.',
        'duplicate_display_name' => 'A permission with this display name already exists in the same module.',
    ],

    // Bulk operation validation
    'bulk' => [
        'no_selection' => 'No permissions selected for bulk operation.',
        'invalid_action' => 'Invalid bulk action specified.',
        'mixed_types' => 'Cannot perform bulk operations on mixed permission types.',
        'system_permissions_included' => 'Bulk operation cannot include system permissions.',
        'permissions_in_use' => 'Some selected permissions are in use and cannot be modified.',
        'max_selection_exceeded' => 'Maximum selection limit of :max permissions exceeded.',
    ],

    // Import validation
    'import' => [
        'invalid_format' => 'Invalid import file format.',
        'missing_required_fields' => 'Import data is missing required fields: :fields',
        'invalid_permission_structure' => 'Invalid permission data structure in import file.',
        'version_mismatch' => 'Import file version is not compatible with current system.',
        'duplicate_names' => 'Duplicate permission names found in import data: :names',
        'invalid_dependencies' => 'Invalid dependency references found in import data.',
        'file_too_large' => 'Import file size exceeds maximum limit of :max MB.',
        'corrupted_data' => 'Import file contains corrupted or invalid data.',
    ],

    // Template validation
    'template' => [
        'name_required' => 'Template name is required.',
        'name_unique' => 'A template with this name already exists.',
        'description_required' => 'Template description is required.',
        'permissions_required' => 'Template must include at least one permission.',
        'invalid_permission_data' => 'Template contains invalid permission data.',
        'circular_dependencies' => 'Template contains circular dependencies.',
    ],

    // Test validation
    'test' => [
        'user_required' => 'User selection is required for user permission test.',
        'role_required' => 'Role selection is required for role permission test.',
        'permission_required' => 'Permission selection is required for test.',
        'invalid_user' => 'Selected user does not exist or is inactive.',
        'invalid_role' => 'Selected role does not exist or is inactive.',
        'invalid_permission' => 'Selected permission does not exist or is inactive.',
        'test_mode_required' => 'Test mode must be specified.',
        'invalid_test_mode' => 'Invalid test mode specified.',
    ],

];