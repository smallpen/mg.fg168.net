<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Activity Log Validation Language Lines
    |--------------------------------------------------------------------------
    |
    | The following language lines contain the default validation messages
    | used by the activity log system validation classes.
    |
    */

    // Activity creation validation
    'activity' => [
        'type_required' => 'Activity type is required',
        'type_string' => 'Activity type must be a string',
        'type_max' => 'Activity type may not be greater than :max characters',
        'type_in' => 'Selected activity type is invalid',
        
        'description_required' => 'Activity description is required',
        'description_string' => 'Activity description must be a string',
        'description_max' => 'Activity description may not be greater than :max characters',
        
        'subject_type_string' => 'Subject type must be a string',
        'subject_type_max' => 'Subject type may not be greater than :max characters',
        
        'subject_id_integer' => 'Subject ID must be an integer',
        'subject_id_min' => 'Subject ID must be at least :min',
        
        'causer_type_string' => 'Causer type must be a string',
        'causer_type_max' => 'Causer type may not be greater than :max characters',
        
        'causer_id_integer' => 'Causer ID must be an integer',
        'causer_id_min' => 'Causer ID must be at least :min',
        
        'properties_array' => 'Properties must be an array',
        'properties_json' => 'Properties must be valid JSON',
        
        'ip_address_ip' => 'IP address must be a valid IP address',
        'ip_address_max' => 'IP address may not be greater than :max characters',
        
        'user_agent_string' => 'User agent must be a string',
        'user_agent_max' => 'User agent may not be greater than :max characters',
        
        'result_string' => 'Result must be a string',
        'result_max' => 'Result may not be greater than :max characters',
        'result_in' => 'Selected result is invalid',
        
        'risk_level_integer' => 'Risk level must be an integer',
        'risk_level_between' => 'Risk level must be between :min and :max',
        
        'signature_string' => 'Signature must be a string',
        'signature_max' => 'Signature may not be greater than :max characters',
    ],

    // Filter validation
    'filters' => [
        'date_from_date' => 'From date must be a valid date',
        'date_from_date_format' => 'From date must be in format Y-m-d',
        'date_from_before_or_equal' => 'From date must be before or equal to end date',
        
        'date_to_date' => 'To date must be a valid date',
        'date_to_date_format' => 'To date must be in format Y-m-d',
        'date_to_after_or_equal' => 'To date must be after or equal to start date',
        
        'user_filter_string' => 'User filter must be a string',
        'user_filter_max' => 'User filter may not be greater than :max characters',
        
        'type_filter_string' => 'Type filter must be a string',
        'type_filter_in' => 'Selected type filter is invalid',
        
        'subject_filter_string' => 'Subject filter must be a string',
        'subject_filter_max' => 'Subject filter may not be greater than :max characters',
        
        'result_filter_string' => 'Result filter must be a string',
        'result_filter_in' => 'Selected result filter is invalid',
        
        'risk_filter_integer' => 'Risk filter must be an integer',
        'risk_filter_between' => 'Risk filter must be between :min and :max',
        
        'ip_filter_string' => 'IP filter must be a string',
        'ip_filter_ip' => 'IP filter must be a valid IP address',
        
        'session_filter_string' => 'Session filter must be a string',
        'session_filter_max' => 'Session filter may not be greater than :max characters',
        
        'search_string' => 'Search query must be a string',
        'search_min' => 'Search query must be at least :min characters',
        'search_max' => 'Search query may not be greater than :max characters',
        
        'per_page_integer' => 'Per page must be an integer',
        'per_page_between' => 'Per page must be between :min and :max',
        
        'sort_field_string' => 'Sort field must be a string',
        'sort_field_in' => 'Selected sort field is invalid',
        
        'sort_direction_string' => 'Sort direction must be a string',
        'sort_direction_in' => 'Sort direction must be either asc or desc',
    ],

    // Export validation
    'export' => [
        'format_required' => 'Export format is required',
        'format_string' => 'Export format must be a string',
        'format_in' => 'Selected export format is invalid',
        
        'range_required' => 'Export range is required',
        'range_string' => 'Export range must be a string',
        'range_in' => 'Selected export range is invalid',
        
        'columns_array' => 'Columns must be an array',
        'columns_min' => 'At least :min column must be selected',
        'columns_max' => 'No more than :max columns can be selected',
        
        'file_name_string' => 'File name must be a string',
        'file_name_max' => 'File name may not be greater than :max characters',
        'file_name_regex' => 'File name contains invalid characters',
        
        'compression_string' => 'Compression type must be a string',
        'compression_in' => 'Selected compression type is invalid',
        
        'include_properties_boolean' => 'Include properties must be true or false',
        'include_notes_boolean' => 'Include notes must be true or false',
        'include_related_boolean' => 'Include related must be true or false',
        
        'email_notification_boolean' => 'Email notification must be true or false',
        'email_address_email' => 'Email address must be a valid email address',
    ],

    // Import validation
    'import' => [
        'file_required' => 'Import file is required',
        'file_file' => 'Import must be a valid file',
        'file_mimes' => 'Import file must be of type: :values',
        'file_max' => 'Import file may not be greater than :max kilobytes',
        
        'format_required' => 'Import format is required',
        'format_string' => 'Import format must be a string',
        'format_in' => 'Selected import format is invalid',
        
        'validate_data_boolean' => 'Validate data must be true or false',
        'skip_duplicates_boolean' => 'Skip duplicates must be true or false',
        'update_existing_boolean' => 'Update existing must be true or false',
        
        'batch_size_integer' => 'Batch size must be an integer',
        'batch_size_between' => 'Batch size must be between :min and :max',
        
        'password_string' => 'Password must be a string',
        'password_min' => 'Password must be at least :min characters',
    ],

    // Monitoring rule validation
    'monitoring_rule' => [
        'name_required' => 'Rule name is required',
        'name_string' => 'Rule name must be a string',
        'name_max' => 'Rule name may not be greater than :max characters',
        'name_unique' => 'Rule name has already been taken',
        
        'description_string' => 'Rule description must be a string',
        'description_max' => 'Rule description may not be greater than :max characters',
        
        'conditions_required' => 'Rule conditions are required',
        'conditions_array' => 'Rule conditions must be an array',
        'conditions_min' => 'At least :min condition must be specified',
        
        'actions_required' => 'Rule actions are required',
        'actions_array' => 'Rule actions must be an array',
        'actions_min' => 'At least :min action must be specified',
        
        'priority_integer' => 'Priority must be an integer',
        'priority_between' => 'Priority must be between :min and :max',
        
        'is_active_boolean' => 'Active status must be true or false',
        
        'threshold_numeric' => 'Threshold must be a number',
        'threshold_min' => 'Threshold must be at least :min',
        
        'time_window_integer' => 'Time window must be an integer',
        'time_window_min' => 'Time window must be at least :min minutes',
    ],

    // Backup validation
    'backup' => [
        'name_required' => 'Backup name is required',
        'name_string' => 'Backup name must be a string',
        'name_max' => 'Backup name may not be greater than :max characters',
        'name_regex' => 'Backup name contains invalid characters',
        
        'description_string' => 'Backup description must be a string',
        'description_max' => 'Backup description may not be greater than :max characters',
        
        'type_required' => 'Backup type is required',
        'type_string' => 'Backup type must be a string',
        'type_in' => 'Selected backup type is invalid',
        
        'compression_boolean' => 'Compression must be true or false',
        'encryption_boolean' => 'Encryption must be true or false',
        
        'password_required_if' => 'Password is required when encryption is enabled',
        'password_string' => 'Password must be a string',
        'password_min' => 'Password must be at least :min characters',
        'password_confirmed' => 'Password confirmation does not match',
        
        'location_required' => 'Backup location is required',
        'location_string' => 'Backup location must be a string',
        'location_in' => 'Selected backup location is invalid',
        
        'schedule_string' => 'Backup schedule must be a string',
        'schedule_in' => 'Selected backup schedule is invalid',
    ],

    // Retention policy validation
    'retention' => [
        'general_days_required' => 'General activities retention days is required',
        'general_days_integer' => 'General activities retention days must be an integer',
        'general_days_min' => 'General activities retention days must be at least :min',
        'general_days_max' => 'General activities retention days may not be greater than :max',
        
        'security_days_required' => 'Security events retention days is required',
        'security_days_integer' => 'Security events retention days must be an integer',
        'security_days_min' => 'Security events retention days must be at least :min',
        'security_days_max' => 'Security events retention days may not be greater than :max',
        
        'system_days_required' => 'System activities retention days is required',
        'system_days_integer' => 'System activities retention days must be an integer',
        'system_days_min' => 'System activities retention days must be at least :min',
        'system_days_max' => 'System activities retention days may not be greater than :max',
        
        'auto_cleanup_boolean' => 'Auto cleanup must be true or false',
        
        'cleanup_schedule_required_if' => 'Cleanup schedule is required when auto cleanup is enabled',
        'cleanup_schedule_string' => 'Cleanup schedule must be a string',
        'cleanup_schedule_in' => 'Selected cleanup schedule is invalid',
        
        'cleanup_time_date_format' => 'Cleanup time must be in format H:i',
        
        'archive_before_delete_boolean' => 'Archive before delete must be true or false',
        
        'archive_location_required_if' => 'Archive location is required when archiving is enabled',
        'archive_location_string' => 'Archive location must be a string',
        
        'compression_level_integer' => 'Compression level must be an integer',
        'compression_level_between' => 'Compression level must be between :min and :max',
    ],

    // Notification rule validation
    'notification_rule' => [
        'name_required' => 'Notification rule name is required',
        'name_string' => 'Notification rule name must be a string',
        'name_max' => 'Notification rule name may not be greater than :max characters',
        'name_unique' => 'Notification rule name has already been taken',
        
        'description_string' => 'Notification rule description must be a string',
        'description_max' => 'Notification rule description may not be greater than :max characters',
        
        'conditions_required' => 'Trigger conditions are required',
        'conditions_array' => 'Trigger conditions must be an array',
        'conditions_min' => 'At least :min condition must be specified',
        
        'channels_required' => 'Notification channels are required',
        'channels_array' => 'Notification channels must be an array',
        'channels_min' => 'At least :min channel must be selected',
        
        'recipients_required' => 'Recipients are required',
        'recipients_array' => 'Recipients must be an array',
        'recipients_min' => 'At least :min recipient must be specified',
        
        'template_required' => 'Message template is required',
        'template_string' => 'Message template must be a string',
        'template_max' => 'Message template may not be greater than :max characters',
        
        'is_enabled_boolean' => 'Enabled status must be true or false',
        
        'rate_limit_integer' => 'Rate limit must be an integer',
        'rate_limit_min' => 'Rate limit must be at least :min',
        
        'quiet_hours_start_date_format' => 'Quiet hours start time must be in format H:i',
        'quiet_hours_end_date_format' => 'Quiet hours end time must be in format H:i',
    ],

    // API validation
    'api' => [
        'api_key_required' => 'API key is required',
        'api_key_string' => 'API key must be a string',
        'api_key_size' => 'API key must be exactly :size characters',
        'api_key_exists' => 'Invalid API key',
        
        'endpoint_required' => 'API endpoint is required',
        'endpoint_string' => 'API endpoint must be a string',
        'endpoint_in' => 'Invalid API endpoint',
        
        'method_required' => 'HTTP method is required',
        'method_string' => 'HTTP method must be a string',
        'method_in' => 'HTTP method must be one of: :values',
        
        'version_string' => 'API version must be a string',
        'version_in' => 'Unsupported API version',
        
        'limit_integer' => 'Limit must be an integer',
        'limit_between' => 'Limit must be between :min and :max',
        
        'offset_integer' => 'Offset must be an integer',
        'offset_min' => 'Offset must be at least :min',
        
        'fields_array' => 'Fields must be an array',
        'fields_distinct' => 'Fields must not contain duplicates',
    ],

    // Security validation
    'security' => [
        'encryption_key_required' => 'Encryption key is required',
        'encryption_key_string' => 'Encryption key must be a string',
        'encryption_key_size' => 'Encryption key must be exactly :size characters',
        
        'signature_required' => 'Digital signature is required',
        'signature_string' => 'Digital signature must be a string',
        'signature_size' => 'Digital signature must be exactly :size characters',
        
        'mask_type_required' => 'Mask type is required',
        'mask_type_string' => 'Mask type must be a string',
        'mask_type_in' => 'Selected mask type is invalid',
        
        'field_pattern_required' => 'Field pattern is required',
        'field_pattern_string' => 'Field pattern must be a string',
        'field_pattern_regex' => 'Field pattern must be a valid regular expression',
        
        'custom_mask_required_if' => 'Custom mask is required when mask type is custom',
        'custom_mask_string' => 'Custom mask must be a string',
        'custom_mask_max' => 'Custom mask may not be greater than :max characters',
    ],

    // Performance validation
    'performance' => [
        'optimization_type_required' => 'Optimization type is required',
        'optimization_type_string' => 'Optimization type must be a string',
        'optimization_type_in' => 'Selected optimization type is invalid',
        
        'partition_size_integer' => 'Partition size must be an integer',
        'partition_size_min' => 'Partition size must be at least :min',
        'partition_size_max' => 'Partition size may not be greater than :max',
        
        'cache_ttl_integer' => 'Cache TTL must be an integer',
        'cache_ttl_min' => 'Cache TTL must be at least :min seconds',
        
        'compression_level_integer' => 'Compression level must be an integer',
        'compression_level_between' => 'Compression level must be between :min and :max',
        
        'index_columns_array' => 'Index columns must be an array',
        'index_columns_min' => 'At least :min column must be specified for indexing',
    ],

    // Statistics validation
    'statistics' => [
        'time_range_required' => 'Time range is required',
        'time_range_string' => 'Time range must be a string',
        'time_range_in' => 'Selected time range is invalid',
        
        'chart_type_string' => 'Chart type must be a string',
        'chart_type_in' => 'Selected chart type is invalid',
        
        'metrics_array' => 'Metrics must be an array',
        'metrics_min' => 'At least :min metric must be selected',
        'metrics_distinct' => 'Metrics must not contain duplicates',
        
        'group_by_string' => 'Group by field must be a string',
        'group_by_in' => 'Selected group by field is invalid',
        
        'aggregation_string' => 'Aggregation function must be a string',
        'aggregation_in' => 'Selected aggregation function is invalid',
    ],

    // Custom validation messages
    'custom' => [
        'activity_type_exists' => 'The selected activity type does not exist',
        'subject_exists' => 'The specified subject does not exist',
        'causer_exists' => 'The specified causer does not exist',
        'date_range_valid' => 'The date range is invalid or too large',
        'export_permission' => 'You do not have permission to export activities',
        'import_permission' => 'You do not have permission to import activities',
        'monitoring_permission' => 'You do not have permission to manage monitoring rules',
        'backup_permission' => 'You do not have permission to manage backups',
        'retention_permission' => 'You do not have permission to manage retention policies',
        'api_permission' => 'You do not have permission to access the API',
        'security_permission' => 'You do not have permission to manage security settings',
    ],

];