<?php

return [
    /*
    |--------------------------------------------------------------------------
    | System Settings Language File - English
    |--------------------------------------------------------------------------
    |
    | All language strings for system settings functionality
    |
    */

    // Page titles and navigation
    'title' => 'System Settings',
    'subtitle' => 'Manage application settings',
    'breadcrumb' => [
        'home' => 'Home',
        'settings' => 'System Settings',
        'category' => 'Settings Category',
        'backup' => 'Settings Backup',
        'history' => 'Change History',
        'import_export' => 'Import/Export',
    ],

    // Category names
    'categories' => [
        'basic' => [
            'name' => 'Basic Settings',
            'description' => 'Application basic information and behavior settings',
        ],
        'security' => [
            'name' => 'Security Settings',
            'description' => 'System security policies and authentication settings',
        ],
        'notification' => [
            'name' => 'Notification Settings',
            'description' => 'Email, SMS and push notification settings',
        ],
        'appearance' => [
            'name' => 'Appearance Settings',
            'description' => 'Theme, color and user interface settings',
        ],
        'integration' => [
            'name' => 'Integration Settings',
            'description' => 'Third-party services and API integration settings',
        ],
        'maintenance' => [
            'name' => 'Maintenance Settings',
            'description' => 'Backup, logging and system maintenance settings',
        ],
        'performance' => [
            'name' => 'Performance Settings',
            'description' => 'Cache, batch processing and performance optimization settings',
        ],
    ],

    // Setting item names and descriptions
    'settings' => [
        // Basic settings
        'app.name' => [
            'name' => 'Application Name',
            'description' => 'Application name displayed in browser title and system header',
        ],
        'app.description' => [
            'name' => 'Application Description',
            'description' => 'Brief description of the application for SEO and system introduction',
        ],
        'app.timezone' => [
            'name' => 'System Timezone',
            'description' => 'Default system timezone affecting all time display and logging',
        ],
        'app.locale' => [
            'name' => 'Default Language',
            'description' => 'System default language, default language setting for new users',
        ],
        'app.date_format' => [
            'name' => 'Date Format',
            'description' => 'Format for displaying dates in the system',
        ],
        'app.time_format' => [
            'name' => 'Time Format',
            'description' => 'Format for displaying time in the system',
        ],

        // Security settings
        'security.password_min_length' => [
            'name' => 'Minimum Password Length',
            'description' => 'Minimum character count requirement for user passwords',
        ],
        'security.password_require_uppercase' => [
            'name' => 'Password Requires Uppercase',
            'description' => 'Password must contain at least one uppercase letter',
        ],
        'security.password_require_lowercase' => [
            'name' => 'Password Requires Lowercase',
            'description' => 'Password must contain at least one lowercase letter',
        ],
        'security.password_require_numbers' => [
            'name' => 'Password Requires Numbers',
            'description' => 'Password must contain at least one number',
        ],
        'security.password_require_symbols' => [
            'name' => 'Password Requires Symbols',
            'description' => 'Password must contain at least one special character (!@#$%^&*)',
        ],
        'security.password_expiry_days' => [
            'name' => 'Password Expiry Days',
            'description' => 'Password expiry days, 0 means never expire',
        ],
        'security.login_max_attempts' => [
            'name' => 'Login Failure Lockout Count',
            'description' => 'Number of consecutive login failures before account lockout',
        ],
        'security.lockout_duration' => [
            'name' => 'Account Lockout Duration (Minutes)',
            'description' => 'Minutes before locked account can attempt login again',
        ],
        'security.session_lifetime' => [
            'name' => 'Session Lifetime (Minutes)',
            'description' => 'User session validity time',
        ],
        'security.force_https' => [
            'name' => 'Force HTTPS',
            'description' => 'Force all connections to use HTTPS protocol',
        ],
        'security.two_factor_enabled' => [
            'name' => 'Enable Two-Factor Authentication',
            'description' => 'Enable two-factor authentication functionality',
        ],

        // Notification settings
        'notification.email_enabled' => [
            'name' => 'Enable Email Notifications',
            'description' => 'Enable system email notification functionality',
        ],
        'notification.smtp_host' => [
            'name' => 'SMTP Server Host',
            'description' => 'SMTP server hostname or IP address',
        ],
        'notification.smtp_port' => [
            'name' => 'SMTP Server Port',
            'description' => 'SMTP server port number (usually 25, 465 or 587)',
        ],
        'notification.smtp_encryption' => [
            'name' => 'SMTP Encryption',
            'description' => 'SMTP connection encryption method',
        ],
        'notification.smtp_username' => [
            'name' => 'SMTP Username',
            'description' => 'SMTP server login username',
        ],
        'notification.smtp_password' => [
            'name' => 'SMTP Password',
            'description' => 'SMTP server login password',
        ],
        'notification.from_name' => [
            'name' => 'Sender Name',
            'description' => 'Sender name displayed when system sends emails',
        ],
        'notification.from_email' => [
            'name' => 'Sender Email',
            'description' => 'Sender email address used when system sends emails',
        ],

        // Appearance settings
        'appearance.default_theme' => [
            'name' => 'Default Theme',
            'description' => 'System default theme mode',
        ],
        'appearance.primary_color' => [
            'name' => 'Primary Color',
            'description' => 'System primary color for buttons, links and other elements',
        ],
        'appearance.secondary_color' => [
            'name' => 'Secondary Color',
            'description' => 'System secondary color for auxiliary elements',
        ],
        'appearance.logo_url' => [
            'name' => 'System Logo',
            'description' => 'System logo image, supports PNG, JPG, SVG formats, max 2MB',
        ],
        'appearance.favicon_url' => [
            'name' => 'Website Icon',
            'description' => 'Icon displayed in browser tab, recommended 32x32 pixels',
        ],
        'appearance.login_background_url' => [
            'name' => 'Login Page Background',
            'description' => 'Background image for login page, max 5MB',
        ],
        'appearance.page_title_format' => [
            'name' => 'Page Title Format',
            'description' => 'Browser title format, {page} for page name, {app} for application name',
        ],
        'appearance.custom_css' => [
            'name' => 'Custom CSS',
            'description' => 'Custom CSS styles that will be loaded on all pages',
        ],

        // Integration settings
        'integration.google_analytics_id' => [
            'name' => 'Google Analytics Tracking ID',
            'description' => 'Google Analytics 4 Measurement ID (format: G-XXXXXXXXXX)',
        ],
        'integration.google_oauth_enabled' => [
            'name' => 'Enable Google Login',
            'description' => 'Enable Google OAuth social login functionality',
        ],
        'integration.google_client_id' => [
            'name' => 'Google Client ID',
            'description' => 'Google OAuth application Client ID',
        ],
        'integration.google_client_secret' => [
            'name' => 'Google Client Secret',
            'description' => 'Google OAuth application Client Secret',
        ],

        // Maintenance settings
        'maintenance.auto_backup_enabled' => [
            'name' => 'Enable Auto Backup',
            'description' => 'Enable system automatic backup functionality',
        ],
        'maintenance.backup_frequency' => [
            'name' => 'Backup Frequency',
            'description' => 'Automatic backup execution frequency',
        ],
        'maintenance.backup_retention_days' => [
            'name' => 'Backup Retention Days',
            'description' => 'Backup file retention days, backups older than this will be automatically deleted',
        ],
        'maintenance.log_level' => [
            'name' => 'Log Level',
            'description' => 'Minimum level for system log recording',
        ],
        'maintenance.maintenance_mode' => [
            'name' => 'Maintenance Mode',
            'description' => 'Enable maintenance mode, regular users will not be able to access the system',
        ],

        // Performance settings
        'performance.cache_enabled' => [
            'name' => 'Enable Multi-layer Cache',
            'description' => 'Enable memory, Redis and database multi-layer cache mechanism',
        ],
        'performance.batch_size' => [
            'name' => 'Batch Processing Size',
            'description' => 'Default processing size for batch operations, range 10-1000',
        ],
        'performance.queue_enabled' => [
            'name' => 'Enable Queue Processing',
            'description' => 'Use queue for background processing of bulk operations',
        ],
    ],

    // Action buttons and operations
    'actions' => [
        'save' => 'Save',
        'cancel' => 'Cancel',
        'reset' => 'Reset',
        'edit' => 'Edit',
        'delete' => 'Delete',
        'test' => 'Test',
        'preview' => 'Preview',
        'export' => 'Export',
        'import' => 'Import',
        'backup' => 'Backup',
        'restore' => 'Restore',
        'search' => 'Search',
        'filter' => 'Filter',
        'clear' => 'Clear',
        'apply' => 'Apply',
        'close' => 'Close',
        'confirm' => 'Confirm',
        'back' => 'Back',
        'next' => 'Next',
        'previous' => 'Previous',
        'upload' => 'Upload',
        'download' => 'Download',
        'copy' => 'Copy',
        'refresh' => 'Refresh',
    ],

    // Status and labels
    'status' => [
        'enabled' => 'Enabled',
        'disabled' => 'Disabled',
        'active' => 'Active',
        'inactive' => 'Inactive',
        'changed' => 'Changed',
        'unchanged' => 'Unchanged',
        'default' => 'Default Value',
        'custom' => 'Custom Value',
        'required' => 'Required',
        'optional' => 'Optional',
        'encrypted' => 'Encrypted',
        'testing' => 'Testing',
        'success' => 'Success',
        'failed' => 'Failed',
        'warning' => 'Warning',
        'error' => 'Error',
        'loading' => 'Loading',
        'saving' => 'Saving',
    ],

    // Form labels
    'form' => [
        'category' => 'Category',
        'name' => 'Name',
        'value' => 'Value',
        'description' => 'Description',
        'help' => 'Help',
        'default_value' => 'Default Value',
        'current_value' => 'Current Value',
        'new_value' => 'New Value',
        'search_placeholder' => 'Search settings...',
        'filter_category' => 'Filter Category',
        'filter_status' => 'Filter Status',
        'all_categories' => 'All Categories',
        'all_status' => 'All Status',
        'show_changed_only' => 'Show Changed Only',
        'show_all' => 'Show All',
    ],

    // Messages and notifications
    'messages' => [
        'saved' => 'Settings saved',
        'save_failed' => 'Failed to save settings',
        'reset_success' => 'Settings reset to default values',
        'reset_failed' => 'Failed to reset settings',
        'test_success' => 'Test successful',
        'test_failed' => 'Test failed',
        'connection_success' => 'Connection test successful',
        'connection_failed' => 'Connection test failed',
        'no_settings_found' => 'No settings found matching criteria',
        'loading_settings' => 'Loading settings...',
        'unsaved_changes' => 'You have unsaved changes',
        'confirm_reset' => 'Are you sure you want to reset this setting to default value?',
        'confirm_delete' => 'Are you sure you want to delete this item?',
        'operation_success' => 'Operation completed successfully',
        'operation_failed' => 'Operation failed',
        'validation_error' => 'Validation error',
        'permission_denied' => 'Permission denied',
        'setting_locked' => 'This setting is locked and cannot be modified',
        'dependency_warning' => 'Changes to this setting may affect other related settings',
    ],

    // Backup management
    'backup' => [
        'title' => 'Settings Backup',
        'create' => 'Create Backup',
        'restore' => 'Restore Backup',
        'delete' => 'Delete Backup',
        'download' => 'Download Backup',
        'compare' => 'Compare Backup',
        'name' => 'Backup Name',
        'description' => 'Backup Description',
        'created_at' => 'Created At',
        'created_by' => 'Created By',
        'size' => 'Size',
        'settings_count' => 'Settings Count',
        'no_backups' => 'No backup records yet',
        'create_success' => 'Backup created successfully',
        'create_failed' => 'Failed to create backup',
        'restore_success' => 'Backup restored successfully',
        'restore_failed' => 'Failed to restore backup',
        'delete_success' => 'Backup deleted successfully',
        'delete_failed' => 'Failed to delete backup',
        'confirm_restore' => 'Are you sure you want to restore this backup? This will overwrite current settings.',
        'confirm_delete' => 'Are you sure you want to delete this backup? This action cannot be undone.',
    ],

    // Import/Export
    'import_export' => [
        'title' => 'Import/Export Settings',
        'export' => 'Export Settings',
        'import' => 'Import Settings',
        'export_success' => 'Settings exported successfully',
        'export_failed' => 'Failed to export settings',
        'import_success' => 'Settings imported successfully',
        'import_failed' => 'Failed to import settings',
        'select_file' => 'Select File',
        'file_format' => 'File Format',
        'supported_formats' => 'Supported formats: JSON',
        'export_options' => 'Export Options',
        'export_all' => 'Export All Settings',
        'export_category' => 'Export Specific Category',
        'export_changed' => 'Export Changed Settings Only',
        'import_options' => 'Import Options',
        'import_mode' => 'Import Mode',
        'import_mode_merge' => 'Merge (Keep existing settings)',
        'import_mode_replace' => 'Replace (Overwrite existing settings)',
        'conflict_resolution' => 'Conflict Resolution',
        'conflict_skip' => 'Skip conflicting items',
        'conflict_update' => 'Update conflicting items',
        'preview_changes' => 'Preview Changes',
        'import_summary' => 'Import Summary',
        'imported_count' => 'Imported',
        'skipped_count' => 'Skipped',
        'error_count' => 'Errors',
        'invalid_file' => 'Invalid file format',
        'file_too_large' => 'File too large',
        'no_file_selected' => 'Please select a file to import',
    ],

    // Change history
    'history' => [
        'title' => 'Settings Change History',
        'setting' => 'Setting Item',
        'old_value' => 'Old Value',
        'new_value' => 'New Value',
        'changed_by' => 'Changed By',
        'changed_at' => 'Changed At',
        'reason' => 'Change Reason',
        'ip_address' => 'IP Address',
        'user_agent' => 'User Agent',
        'no_history' => 'No change records yet',
        'view_details' => 'View Details',
        'revert' => 'Revert to This Version',
        'revert_success' => 'Setting reverted to specified version',
        'revert_failed' => 'Failed to revert setting',
        'confirm_revert' => 'Are you sure you want to revert to this version?',
        'filter_setting' => 'Filter Setting',
        'filter_user' => 'Filter User',
        'filter_date' => 'Filter Date',
        'date_from' => 'From Date',
        'date_to' => 'To Date',
    ],

    // Preview functionality
    'preview' => [
        'title' => 'Settings Preview',
        'enable' => 'Enable Preview',
        'disable' => 'Disable Preview',
        'apply' => 'Apply Preview',
        'reset' => 'Reset Preview',
        'theme_preview' => 'Theme Preview',
        'color_preview' => 'Color Preview',
        'layout_preview' => 'Layout Preview',
        'email_preview' => 'Email Preview',
        'preview_mode' => 'Preview Mode',
        'live_preview' => 'Live Preview',
        'preview_warning' => 'Preview mode is for reference only, actual effects may vary slightly',
    ],

    // Test functionality
    'test' => [
        'connection' => 'Test Connection',
        'email' => 'Test Email',
        'smtp' => 'Test SMTP',
        'oauth' => 'Test OAuth',
        'api' => 'Test API',
        'database' => 'Test Database',
        'cache' => 'Test Cache',
        'storage' => 'Test Storage',
        'test_email_subject' => 'System Settings Test Email',
        'test_email_body' => 'This is a test email from system settings. If you receive this email, it means the email configuration is working properly.',
        'send_test_email' => 'Send Test Email',
        'test_recipient' => 'Test Recipient',
        'test_in_progress' => 'Test in progress...',
        'test_completed' => 'Test completed',
        'test_details' => 'Test Details',
        'connection_timeout' => 'Connection timeout',
        'authentication_failed' => 'Authentication failed',
        'invalid_credentials' => 'Invalid credentials',
        'service_unavailable' => 'Service unavailable',
    ],

    // Validation messages
    'validation' => [
        'required' => 'The :attribute field is required',
        'string' => 'The :attribute must be a string',
        'numeric' => 'The :attribute must be a number',
        'integer' => 'The :attribute must be an integer',
        'boolean' => 'The :attribute must be a boolean',
        'email' => 'The :attribute must be a valid email address',
        'url' => 'The :attribute must be a valid URL',
        'min' => 'The :attribute minimum value is :min',
        'max' => 'The :attribute maximum value is :max',
        'between' => 'The :attribute must be between :min and :max',
        'in' => 'The :attribute must be one of: :values',
        'regex' => 'The :attribute format is incorrect',
        'unique' => 'The :attribute already exists',
        'exists' => 'The :attribute does not exist',
        'file' => 'The :attribute must be a file',
        'image' => 'The :attribute must be an image',
        'mimes' => 'The :attribute must be one of the following formats: :values',
        'max_file_size' => 'The :attribute file size cannot exceed :max KB',
        'json' => 'The :attribute must be valid JSON format',
        'array' => 'The :attribute must be an array',
        'date' => 'The :attribute must be a valid date',
        'after' => 'The :attribute must be after :date',
        'before' => 'The :attribute must be before :date',
        'confirmed' => 'The :attribute confirmation does not match',
        'same' => 'The :attribute and :other must match',
        'different' => 'The :attribute and :other must be different',
        'alpha' => 'The :attribute may only contain letters',
        'alpha_num' => 'The :attribute may only contain letters and numbers',
        'alpha_dash' => 'The :attribute may only contain letters, numbers, dashes and underscores',
        'ip' => 'The :attribute must be a valid IP address',
        'ipv4' => 'The :attribute must be a valid IPv4 address',
        'ipv6' => 'The :attribute must be a valid IPv6 address',
        'timezone' => 'The :attribute must be a valid timezone',
    ],

    // Option values
    'options' => [
        'yes' => 'Yes',
        'no' => 'No',
        'enabled' => 'Enabled',
        'disabled' => 'Disabled',
        'true' => 'True',
        'false' => 'False',
        'on' => 'On',
        'off' => 'Off',
        'light' => 'Light Theme',
        'dark' => 'Dark Theme',
        'auto' => 'Auto (Follow System)',
        'none' => 'None',
        'ssl' => 'SSL',
        'tls' => 'TLS',
        'hourly' => 'Hourly',
        'daily' => 'Daily',
        'weekly' => 'Weekly',
        'monthly' => 'Monthly',
        'debug' => 'DEBUG',
        'info' => 'INFO',
        'warning' => 'WARNING',
        'error' => 'ERROR',
        'critical' => 'CRITICAL',
        'file' => 'File Cache',
        'redis' => 'Redis',
        'memcached' => 'Memcached',
        'array' => 'Array Cache (Test Only)',
        'sandbox' => 'Sandbox Mode (Test)',
        'live' => 'Live Environment',
    ],

    // Help text
    'help' => [
        'setting_help' => 'Click setting name to view detailed description',
        'category_help' => 'Settings are organized by category for easy management and search',
        'search_help' => 'Search setting names, descriptions or values',
        'filter_help' => 'Use filters to quickly find specific types of settings',
        'backup_help' => 'Regular backups prevent accidental loss of settings',
        'import_help' => 'Please backup existing settings before importing',
        'preview_help' => 'Preview functionality lets you see changes before applying',
        'test_help' => 'Test functionality validates setting correctness',
        'dependency_help' => 'Some settings are interdependent, consider impact when changing',
        'encryption_help' => 'Sensitive settings are automatically encrypted for storage',
        'validation_help' => 'System automatically validates setting value format and range',
        'history_help' => 'All setting changes are recorded in history',
    ],

    // Statistics
    'stats' => [
        'total_settings' => 'Total Settings',
        'changed_settings' => 'Changed Settings',
        'default_settings' => 'Default Settings',
        'encrypted_settings' => 'Encrypted Settings',
        'categories_count' => 'Categories Count',
        'last_backup' => 'Last Backup',
        'last_change' => 'Last Change',
        'backup_count' => 'Backup Count',
        'history_count' => 'History Records',
    ],
];