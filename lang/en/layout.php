<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Layout and Navigation Language Lines
    |--------------------------------------------------------------------------
    |
    | The following language lines are used for various elements in the
    | admin layout and navigation system.
    |
    */

    // Top navigation bar
    'topnav' => [
        'toggle_sidebar' => 'Toggle Sidebar',
        'search_placeholder' => 'Search anything...',
        'notifications' => 'Notifications',
        'no_notifications' => 'No new notifications',
        'mark_all_read' => 'Mark All as Read',
        'view_all' => 'View All Notifications',
        'theme_toggle' => 'Toggle Theme',
        'language_selector' => 'Language Selector',
        'user_menu' => 'User Menu',
        'profile' => 'Profile',
        'settings' => 'Settings',
        'logout' => 'Logout',
    ],

    // Sidebar
    'sidebar' => [
        'dashboard' => 'Dashboard',
        'user_management' => 'User Management',
        'user_list' => 'User List',
        'create_user' => 'Create User',
        'user_roles' => 'User Roles',
        'role_management' => 'Role Management',
        'role_list' => 'Role List',
        'permission_settings' => 'Permission Settings',
        'role_hierarchy' => 'Role Hierarchy',
        'permission_management' => 'Permission Management',
        'permission_list' => 'Permission List',
        'permission_groups' => 'Permission Groups',
        'permission_dependencies' => 'Dependencies',
        'permission_templates' => 'Permission Templates',
        'permission_test' => 'Permission Test',
        'permission_usage' => 'Usage Analysis',
        'permission_audit' => 'Permission Audit',
        'permission_import_export' => 'Import/Export',
        'system_settings' => 'System Settings',
        'basic_settings' => 'Basic Settings',
        'security_settings' => 'Security Settings',
        'appearance_settings' => 'Appearance Settings',
        'activity_logs' => 'Activity Logs',
        'operation_logs' => 'Operation Logs',
        'security_events' => 'Security Events',
        'statistical_analysis' => 'Statistical Analysis',
        'search_menu' => 'Search menu...',
        'collapse_menu' => 'Collapse Menu',
        'expand_menu' => 'Expand Menu',
    ],

    // Breadcrumb navigation
    'breadcrumb' => [
        'home' => 'Home',
        'separator' => '/',
        'current_page' => 'Current Page',
    ],

    // Theme system
    'theme' => [
        'light' => 'Light Theme',
        'dark' => 'Dark Theme',
        'auto' => 'Auto Mode',
        'system' => 'Follow System',
        'toggle_tooltip' => 'Toggle Theme Mode',
        'current_theme' => 'Current Theme: :theme',
    ],

    // Language system
    'language' => [
        'zh_TW' => '正體中文',
        'en' => 'English',
        'current' => 'Current Language',
        'switch_to' => 'Switch to :language',
        'switched' => 'Language switched to :language',
        'unsupported' => 'Unsupported language',
        'loading' => 'Switching language...',
    ],

    // Common translations
    'welcome_back' => 'Welcome back, :name',
    'basic_settings' => 'Basic Settings',
    'mail_settings' => 'Mail Settings',
    'cache_management' => 'Cache Management',

    // Notification center
    'notifications' => [
        'title' => 'Notification Center',
        'empty' => 'No notifications',
        'empty_description' => 'You have no notifications at the moment',
        'mark_read' => 'Mark as Read',
        'mark_unread' => 'Mark as Unread',
        'delete' => 'Delete Notification',
        'filter_all' => 'All',
        'filter_unread' => 'Unread',
        'filter_security' => 'Security Events',
        'time_ago' => ':time ago',
        'just_now' => 'Just now',
        'minutes_ago' => ':count minutes ago',
        'hours_ago' => ':count hours ago',
        'days_ago' => ':count days ago',
        'weeks_ago' => ':count weeks ago',
        'months_ago' => ':count months ago',
        'years_ago' => ':count years ago',
    ],

    // Global search
    'search' => [
        'placeholder' => 'Search pages, users, roles...',
        'no_results' => 'No results found',
        'no_results_description' => 'Try using different keywords',
        'recent_searches' => 'Recent Searches',
        'clear_recent' => 'Clear Recent Searches',
        'categories' => [
            'all' => 'All',
            'pages' => 'Pages',
            'users' => 'Users',
            'roles' => 'Roles',
            'permissions' => 'Permissions',
            'activities' => 'Activities',
        ],
        'results_count' => 'Found :count results',
        'keyboard_shortcuts' => [
            'open' => 'Press Ctrl+K to open search',
            'navigate' => 'Use ↑↓ to navigate',
            'select' => 'Press Enter to select',
            'close' => 'Press Esc to close',
        ],
    ],

    // Loading states
    'loading' => [
        'default' => 'Loading...',
        'saving' => 'Saving...',
        'deleting' => 'Deleting...',
        'processing' => 'Processing...',
        'searching' => 'Searching...',
        'switching_language' => 'Switching language...',
        'switching_theme' => 'Switching theme...',
    ],

    // Error messages
    'errors' => [
        'network_error' => 'Network connection error',
        'server_error' => 'Server error',
        'permission_denied' => 'Permission denied',
        'not_found' => 'Page not found',
        'session_expired' => 'Session expired, please login again',
        'maintenance_mode' => 'System under maintenance, please try again later',
    ],

    // Success messages
    'success' => [
        'saved' => 'Saved successfully',
        'deleted' => 'Deleted successfully',
        'updated' => 'Updated successfully',
        'created' => 'Created successfully',
        'language_switched' => 'Language switched successfully',
        'theme_switched' => 'Theme switched successfully',
    ],

    // Confirmation dialogs
    'confirm' => [
        'title' => 'Confirm Action',
        'message' => 'Are you sure you want to perform this action?',
        'yes' => 'Confirm',
        'no' => 'Cancel',
        'delete_title' => 'Confirm Delete',
        'delete_message' => 'This action cannot be undone. Are you sure you want to delete?',
    ],

    // Responsive layout
    'responsive' => [
        'mobile_menu' => 'Mobile Menu',
        'desktop_view' => 'Desktop View',
        'tablet_view' => 'Tablet View',
        'mobile_view' => 'Mobile View',
    ],

    // Accessibility
    'accessibility' => [
        'skip_to_content' => 'Skip to main content',
        'skip_to_navigation' => 'Skip to navigation',
        'menu_button' => 'Menu button',
        'close_menu' => 'Close menu',
        'open_menu' => 'Open menu',
        'current_page' => 'Current page',
        'external_link' => 'External link',
        'new_window' => 'Opens in new window',
    ],

];