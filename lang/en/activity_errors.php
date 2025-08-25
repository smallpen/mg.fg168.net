<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Activity Log Error Messages
    |--------------------------------------------------------------------------
    |
    | The following language lines are used for activity log error messages
    | and validation failures.
    |
    */

    // General errors
    'general' => [
        'system_error' => 'System error occurred while processing activity log',
        'database_connection' => 'Database connection failed',
        'permission_denied' => 'You do not have permission to access activity logs',
        'feature_disabled' => 'Activity logging is currently disabled',
        'maintenance_mode' => 'Activity log system is under maintenance',
        'rate_limit_exceeded' => 'Too many requests. Please try again later',
        'invalid_request' => 'Invalid request format',
        'service_unavailable' => 'Activity log service is temporarily unavailable',
    ],

    // Activity retrieval errors
    'retrieval' => [
        'activity_not_found' => 'Activity record not found',
        'invalid_activity_id' => 'Invalid activity ID format',
        'access_denied' => 'Access denied to this activity record',
        'data_corrupted' => 'Activity data appears to be corrupted',
        'integrity_check_failed' => 'Activity integrity verification failed',
        'decryption_failed' => 'Failed to decrypt activity data',
        'query_timeout' => 'Query timeout while retrieving activities',
        'too_many_results' => 'Query returned too many results. Please refine your search',
    ],

    // Filter and search errors
    'filtering' => [
        'invalid_date_format' => 'Invalid date format. Please use YYYY-MM-DD',
        'invalid_date_range' => 'Invalid date range. End date must be after start date',
        'date_range_too_large' => 'Date range is too large. Maximum allowed range is :days days',
        'invalid_user_filter' => 'Invalid user filter value',
        'invalid_type_filter' => 'Invalid activity type filter',
        'invalid_result_filter' => 'Invalid result filter value',
        'invalid_risk_filter' => 'Invalid risk level filter',
        'invalid_ip_format' => 'Invalid IP address format',
        'search_query_too_short' => 'Search query must be at least :min characters',
        'search_query_too_long' => 'Search query cannot exceed :max characters',
        'invalid_regex_pattern' => 'Invalid regular expression pattern',
        'filter_combination_invalid' => 'Invalid filter combination',
    ],

    // Export errors
    'export' => [
        'export_failed' => 'Export operation failed',
        'invalid_format' => 'Invalid export format specified',
        'no_data_to_export' => 'No data available for export',
        'file_creation_failed' => 'Failed to create export file',
        'file_write_failed' => 'Failed to write export data',
        'compression_failed' => 'Failed to compress export file',
        'encryption_failed' => 'Failed to encrypt export file',
        'file_size_exceeded' => 'Export file size exceeds maximum limit of :size',
        'disk_space_insufficient' => 'Insufficient disk space for export',
        'export_timeout' => 'Export operation timed out',
        'invalid_columns' => 'Invalid column selection for export',
        'email_delivery_failed' => 'Failed to send export notification email',
    ],

    // Import errors
    'import' => [
        'import_failed' => 'Import operation failed',
        'file_not_found' => 'Import file not found',
        'file_format_invalid' => 'Invalid import file format',
        'file_corrupted' => 'Import file appears to be corrupted',
        'file_too_large' => 'Import file size exceeds maximum limit of :size',
        'decryption_failed' => 'Failed to decrypt import file',
        'decompression_failed' => 'Failed to decompress import file',
        'data_validation_failed' => 'Import data validation failed',
        'duplicate_activities' => 'Duplicate activities found in import data',
        'invalid_activity_structure' => 'Invalid activity data structure',
        'import_timeout' => 'Import operation timed out',
        'partial_import_failure' => 'Import partially failed. :success imported, :failed failed',
    ],

    // Monitoring errors
    'monitoring' => [
        'monitoring_disabled' => 'Real-time monitoring is disabled',
        'rule_creation_failed' => 'Failed to create monitoring rule',
        'rule_update_failed' => 'Failed to update monitoring rule',
        'rule_deletion_failed' => 'Failed to delete monitoring rule',
        'invalid_rule_conditions' => 'Invalid monitoring rule conditions',
        'invalid_rule_actions' => 'Invalid monitoring rule actions',
        'rule_execution_failed' => 'Monitoring rule execution failed',
        'alert_creation_failed' => 'Failed to create security alert',
        'alert_notification_failed' => 'Failed to send alert notification',
        'monitoring_service_error' => 'Monitoring service error',
        'rule_limit_exceeded' => 'Maximum number of monitoring rules exceeded',
        'invalid_threshold_value' => 'Invalid threshold value for monitoring rule',
    ],

    // Statistics errors
    'statistics' => [
        'stats_calculation_failed' => 'Statistics calculation failed',
        'invalid_time_range' => 'Invalid time range for statistics',
        'insufficient_data' => 'Insufficient data for statistical analysis',
        'chart_generation_failed' => 'Failed to generate statistics chart',
        'data_aggregation_error' => 'Error during data aggregation',
        'memory_limit_exceeded' => 'Memory limit exceeded during statistics calculation',
        'stats_cache_error' => 'Statistics cache error',
        'invalid_chart_type' => 'Invalid chart type specified',
    ],

    // Backup and restore errors
    'backup' => [
        'backup_creation_failed' => 'Backup creation failed',
        'backup_not_found' => 'Backup file not found',
        'backup_corrupted' => 'Backup file is corrupted',
        'backup_decryption_failed' => 'Failed to decrypt backup file',
        'backup_decompression_failed' => 'Failed to decompress backup file',
        'restore_failed' => 'Restore operation failed',
        'restore_validation_failed' => 'Restore data validation failed',
        'backup_verification_failed' => 'Backup integrity verification failed',
        'backup_storage_full' => 'Backup storage is full',
        'backup_permission_denied' => 'Permission denied for backup operation',
        'backup_schedule_conflict' => 'Backup schedule conflict detected',
        'invalid_backup_format' => 'Invalid backup file format',
    ],

    // Retention policy errors
    'retention' => [
        'cleanup_failed' => 'Cleanup operation failed',
        'invalid_retention_period' => 'Invalid retention period specified',
        'cleanup_in_progress' => 'Another cleanup operation is already in progress',
        'archive_creation_failed' => 'Failed to create archive before cleanup',
        'cleanup_permission_denied' => 'Permission denied for cleanup operation',
        'retention_policy_conflict' => 'Retention policy configuration conflict',
        'cleanup_validation_failed' => 'Cleanup validation failed',
        'archive_storage_full' => 'Archive storage is full',
    ],

    // API errors
    'api' => [
        'invalid_api_key' => 'Invalid API key',
        'api_key_expired' => 'API key has expired',
        'api_key_revoked' => 'API key has been revoked',
        'rate_limit_exceeded' => 'API rate limit exceeded',
        'invalid_endpoint' => 'Invalid API endpoint',
        'method_not_allowed' => 'HTTP method not allowed',
        'invalid_parameters' => 'Invalid API parameters',
        'authentication_required' => 'API authentication required',
        'insufficient_permissions' => 'Insufficient API permissions',
        'api_version_unsupported' => 'Unsupported API version',
        'request_payload_too_large' => 'Request payload too large',
        'invalid_content_type' => 'Invalid content type',
    ],

    // Notification errors
    'notification' => [
        'notification_failed' => 'Notification delivery failed',
        'invalid_notification_channel' => 'Invalid notification channel',
        'notification_rule_failed' => 'Notification rule execution failed',
        'email_service_unavailable' => 'Email service is unavailable',
        'sms_service_unavailable' => 'SMS service is unavailable',
        'webhook_delivery_failed' => 'Webhook delivery failed',
        'notification_template_invalid' => 'Invalid notification template',
        'recipient_list_empty' => 'Notification recipient list is empty',
        'notification_rate_limited' => 'Notification rate limit exceeded',
        'notification_queue_full' => 'Notification queue is full',
    ],

    // Security errors
    'security' => [
        'encryption_key_missing' => 'Encryption key is missing',
        'encryption_key_invalid' => 'Invalid encryption key',
        'decryption_key_mismatch' => 'Decryption key mismatch',
        'signature_verification_failed' => 'Digital signature verification failed',
        'integrity_violation' => 'Data integrity violation detected',
        'unauthorized_access_attempt' => 'Unauthorized access attempt detected',
        'suspicious_activity_detected' => 'Suspicious activity pattern detected',
        'security_policy_violation' => 'Security policy violation',
        'access_token_invalid' => 'Invalid access token',
        'session_expired' => 'Session has expired',
        'ip_address_blocked' => 'IP address is blocked',
        'user_account_locked' => 'User account is locked',
    ],

    // Performance errors
    'performance' => [
        'query_timeout' => 'Database query timeout',
        'memory_limit_exceeded' => 'Memory limit exceeded',
        'cpu_limit_exceeded' => 'CPU usage limit exceeded',
        'disk_space_full' => 'Disk space is full',
        'connection_pool_exhausted' => 'Database connection pool exhausted',
        'cache_service_unavailable' => 'Cache service is unavailable',
        'index_corruption_detected' => 'Database index corruption detected',
        'partition_limit_exceeded' => 'Database partition limit exceeded',
        'optimization_failed' => 'Performance optimization failed',
        'resource_contention' => 'Resource contention detected',
    ],

    // Validation errors
    'validation' => [
        'required_field_missing' => 'Required field is missing: :field',
        'invalid_field_format' => 'Invalid format for field: :field',
        'field_too_long' => 'Field :field exceeds maximum length of :max characters',
        'field_too_short' => 'Field :field must be at least :min characters',
        'invalid_email_format' => 'Invalid email address format',
        'invalid_url_format' => 'Invalid URL format',
        'invalid_json_format' => 'Invalid JSON format',
        'invalid_timestamp_format' => 'Invalid timestamp format',
        'value_out_of_range' => 'Value for :field is out of valid range',
        'invalid_enum_value' => 'Invalid value for :field. Allowed values: :values',
        'duplicate_value' => 'Duplicate value detected for :field',
        'foreign_key_constraint' => 'Foreign key constraint violation for :field',
    ],

    // Configuration errors
    'configuration' => [
        'config_file_missing' => 'Configuration file is missing',
        'config_file_corrupted' => 'Configuration file is corrupted',
        'invalid_config_format' => 'Invalid configuration file format',
        'required_config_missing' => 'Required configuration is missing: :config',
        'config_validation_failed' => 'Configuration validation failed',
        'config_update_failed' => 'Failed to update configuration',
        'config_backup_failed' => 'Failed to backup configuration',
        'config_restore_failed' => 'Failed to restore configuration',
        'environment_mismatch' => 'Environment configuration mismatch',
        'dependency_missing' => 'Required dependency is missing: :dependency',
    ],

    // File system errors
    'filesystem' => [
        'file_not_found' => 'File not found: :file',
        'file_permission_denied' => 'File permission denied: :file',
        'file_already_exists' => 'File already exists: :file',
        'directory_not_found' => 'Directory not found: :directory',
        'directory_permission_denied' => 'Directory permission denied: :directory',
        'disk_space_insufficient' => 'Insufficient disk space',
        'file_size_exceeded' => 'File size exceeds maximum limit',
        'file_type_not_allowed' => 'File type not allowed: :type',
        'file_upload_failed' => 'File upload failed',
        'file_download_failed' => 'File download failed',
        'file_corruption_detected' => 'File corruption detected',
        'temporary_file_cleanup_failed' => 'Failed to cleanup temporary files',
    ],

    // Network errors
    'network' => [
        'connection_timeout' => 'Network connection timeout',
        'connection_refused' => 'Network connection refused',
        'host_unreachable' => 'Host unreachable',
        'dns_resolution_failed' => 'DNS resolution failed',
        'ssl_certificate_invalid' => 'Invalid SSL certificate',
        'ssl_handshake_failed' => 'SSL handshake failed',
        'proxy_authentication_failed' => 'Proxy authentication failed',
        'network_unreachable' => 'Network unreachable',
        'bandwidth_limit_exceeded' => 'Bandwidth limit exceeded',
        'request_too_large' => 'Request size too large',
    ],

    // Queue and job errors
    'queue' => [
        'job_failed' => 'Background job failed',
        'queue_connection_failed' => 'Queue connection failed',
        'job_timeout' => 'Job execution timeout',
        'job_retry_limit_exceeded' => 'Job retry limit exceeded',
        'queue_full' => 'Job queue is full',
        'invalid_job_payload' => 'Invalid job payload',
        'job_serialization_failed' => 'Job serialization failed',
        'job_deserialization_failed' => 'Job deserialization failed',
        'worker_not_available' => 'No queue worker available',
        'job_priority_invalid' => 'Invalid job priority',
    ],

];