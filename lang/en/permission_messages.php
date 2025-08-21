<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Permission Success Messages
    |--------------------------------------------------------------------------
    |
    | The following language lines are used for permission management
    | success messages and notifications.
    |
    */

    // CRUD success messages
    'crud' => [
        'created' => 'Permission ":name" has been created successfully.',
        'updated' => 'Permission ":name" has been updated successfully.',
        'deleted' => 'Permission ":name" has been deleted successfully.',
        'activated' => 'Permission ":name" has been activated successfully.',
        'deactivated' => 'Permission ":name" has been deactivated successfully.',
        'duplicated' => 'Permission ":name" has been duplicated successfully as ":new_name".',
        'restored' => 'Permission ":name" has been restored successfully.',
    ],

    // Dependency management messages
    'dependencies' => [
        'added' => 'Dependency ":dependency" has been added to permission ":permission".',
        'removed' => 'Dependency ":dependency" has been removed from permission ":permission".',
        'synced' => 'Dependencies for permission ":permission" have been synchronized successfully.',
        'cleared' => 'All dependencies have been cleared from permission ":permission".',
        'auto_resolved' => 'Dependencies have been automatically resolved for permission ":permission".',
    ],

    // Bulk operation messages
    'bulk' => [
        'deleted' => ':count permissions have been deleted successfully.',
        'activated' => ':count permissions have been activated successfully.',
        'deactivated' => ':count permissions have been deactivated successfully.',
        'exported' => ':count permissions have been exported successfully.',
        'updated' => ':count permissions have been updated successfully.',
        'partial_success' => 'Bulk operation completed: :success successful, :failed failed.',
        'dependencies_assigned' => 'Dependencies have been assigned to :count permissions.',
    ],

    // Import/Export messages
    'import_export' => [
        'exported' => 'Permissions have been exported successfully to ":filename".',
        'imported' => ':count permissions have been imported successfully.',
        'import_preview' => 'Import preview generated: :create to create, :update to update, :conflicts conflicts.',
        'conflicts_resolved' => 'Import conflicts have been resolved successfully.',
        'backup_created' => 'Backup created before import: ":filename".',
        'import_completed' => 'Import completed successfully. :created created, :updated updated, :skipped skipped.',
    ],

    // Template messages
    'templates' => [
        'created' => 'Permission template ":name" has been created successfully.',
        'updated' => 'Permission template ":name" has been updated successfully.',
        'deleted' => 'Permission template ":name" has been deleted successfully.',
        'applied' => 'Template ":name" has been applied successfully. :count permissions created.',
        'exported' => 'Template ":name" has been exported successfully.',
        'imported' => 'Template ":name" has been imported successfully.',
    ],

    // Testing messages
    'testing' => [
        'test_completed' => 'Permission test completed successfully.',
        'batch_test_completed' => 'Batch permission test completed: :passed passed, :failed failed.',
        'test_results_exported' => 'Test results have been exported to ":filename".',
        'test_data_cleared' => 'Test data has been cleared successfully.',
        'permission_verified' => 'Permission ":permission" verified for :subject.',
        'access_granted' => 'Access granted: :subject has permission ":permission".',
        'access_denied' => 'Access denied: :subject does not have permission ":permission".',
    ],

    // Usage analysis messages
    'usage' => [
        'analysis_completed' => 'Permission usage analysis completed successfully.',
        'statistics_updated' => 'Permission usage statistics have been updated.',
        'cache_refreshed' => 'Permission usage cache has been refreshed.',
        'report_generated' => 'Usage analysis report has been generated: ":filename".',
        'unused_permissions_identified' => ':count unused permissions have been identified.',
    ],

    // Audit messages
    'audit' => [
        'log_created' => 'Audit log entry has been created for permission ":permission".',
        'logs_exported' => 'Audit logs have been exported to ":filename".',
        'logs_cleared' => 'Old audit logs have been cleared. :count entries removed.',
        'log_archived' => 'Audit logs have been archived successfully.',
        'retention_applied' => 'Audit log retention policy has been applied.',
    ],

    // Cache messages
    'cache' => [
        'cleared' => 'Permission cache has been cleared successfully.',
        'refreshed' => 'Permission cache has been refreshed successfully.',
        'warmed' => 'Permission cache has been warmed successfully.',
        'optimized' => 'Permission cache has been optimized successfully.',
    ],

    // System messages
    'system' => [
        'maintenance_completed' => 'Permission system maintenance completed successfully.',
        'integrity_check_passed' => 'Permission system integrity check passed.',
        'database_optimized' => 'Permission database has been optimized.',
        'indexes_rebuilt' => 'Permission database indexes have been rebuilt.',
        'cleanup_completed' => 'Permission system cleanup completed. :count orphaned records removed.',
    ],

    // Configuration messages
    'config' => [
        'updated' => 'Permission configuration has been updated successfully.',
        'reset' => 'Permission configuration has been reset to defaults.',
        'validated' => 'Permission configuration validation passed.',
        'backup_created' => 'Configuration backup created: ":filename".',
        'restored' => 'Permission configuration has been restored from backup.',
    ],

    // Search and filter messages
    'search' => [
        'results_found' => ':count permissions found matching your search criteria.',
        'no_results' => 'No permissions found matching your search criteria.',
        'filters_applied' => 'Search filters have been applied successfully.',
        'filters_cleared' => 'Search filters have been cleared.',
        'search_saved' => 'Search criteria have been saved as ":name".',
    ],

    // Notification messages
    'notifications' => [
        'permission_created' => 'New permission ":name" has been created.',
        'permission_modified' => 'Permission ":name" has been modified.',
        'permission_deleted' => 'Permission ":name" has been deleted.',
        'system_permission_warning' => 'System permission ":name" requires attention.',
        'dependency_conflict' => 'Dependency conflict detected in permission ":name".',
        'usage_threshold_reached' => 'Permission ":name" has reached usage threshold.',
    ],

    // Email messages
    'email' => [
        'permission_created_subject' => 'New Permission Created: :name',
        'permission_deleted_subject' => 'Permission Deleted: :name',
        'bulk_operation_subject' => 'Bulk Permission Operation Completed',
        'import_completed_subject' => 'Permission Import Completed',
        'system_alert_subject' => 'Permission System Alert',
    ],

    // API messages
    'api' => [
        'permission_retrieved' => 'Permission data retrieved successfully.',
        'permissions_listed' => 'Permissions list retrieved successfully.',
        'operation_queued' => 'Permission operation has been queued for processing.',
        'batch_processed' => 'Batch permission operation processed successfully.',
        'sync_completed' => 'Permission synchronization completed successfully.',
    ],

    // General success messages
    'general' => [
        'operation_successful' => 'Operation completed successfully.',
        'changes_saved' => 'Changes have been saved successfully.',
        'action_completed' => 'Action completed successfully.',
        'request_processed' => 'Request has been processed successfully.',
        'task_finished' => 'Task has been finished successfully.',
        'process_completed' => 'Process completed successfully.',
    ],

];