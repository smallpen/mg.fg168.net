<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class TestPermissionService extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:permission-service';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test permission service';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Testing permission service...');
        
        try {
            // Test existing service first
            if (class_exists('App\Services\PermissionService')) {
                $this->info('✓ PermissionService class exists');
                $existingService = app('App\Services\PermissionService');
                $this->info('✓ Existing PermissionService resolved successfully');
            }
            
            // Test if class exists
            if (class_exists('App\Services\PermissionCacheService')) {
                $this->info('✓ PermissionCacheService class exists');
            } else {
                $this->error('✗ PermissionCacheService class not found');
                
                // Try to load it manually
                if (file_exists(app_path('Services/PermissionCacheService.php'))) {
                    $this->info('File exists, trying to include...');
                    require_once app_path('Services/PermissionCacheService.php');
                    if (class_exists('App\Services\PermissionCacheService')) {
                        $this->info('✓ Class loaded after manual include');
                    }
                }
                return 1;
            }
            
            // Test service resolution
            $service = app('App\Services\PermissionCacheService');
            $this->info('✓ Service resolved successfully');
            
            // Test a simple method
            $service->warmupCache();
            $this->info('✓ Service method executed successfully');
            
        } catch (\Exception $e) {
            $this->error('Error: ' . $e->getMessage());
            return 1;
        }
        
        return 0;
    }
}
