<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\RoleSecurityService;
use App\Services\RoleDataValidationService;
use App\Services\AuditLogService;
use App\Services\PermissionService;

/**
 * 角色安全服務提供者
 * 
 * 註冊角色安全相關的服務
 */
class RoleSecurityServiceProvider extends ServiceProvider
{
    /**
     * 註冊服務
     */
    public function register(): void
    {
        // 註冊角色安全服務
        $this->app->singleton(RoleSecurityService::class, function ($app) {
            return new RoleSecurityService(
                $app->make(AuditLogService::class),
                $app->make(PermissionService::class)
            );
        });

        // 註冊角色資料驗證服務
        $this->app->singleton(RoleDataValidationService::class, function ($app) {
            return new RoleDataValidationService();
        });

        // 註冊審計日誌服務
        $this->app->singleton(AuditLogService::class, function ($app) {
            return new AuditLogService();
        });

        // 註冊權限服務
        $this->app->singleton(PermissionService::class, function ($app) {
            return new PermissionService();
        });
    }

    /**
     * 啟動服務
     */
    public function boot(): void
    {
        // 發布配置檔案
        $this->publishes([
            __DIR__.'/../../config/role_security.php' => config_path('role_security.php'),
        ], 'role-security-config');

        // 載入配置
        $this->mergeConfigFrom(
            __DIR__.'/../../config/role_security.php', 'role_security'
        );

        // 註冊中介軟體
        $this->registerMiddleware();

        // 註冊事件監聽器
        $this->registerEventListeners();
    }

    /**
     * 註冊中介軟體
     */
    protected function registerMiddleware(): void
    {
        $router = $this->app['router'];

        // 註冊角色安全中介軟體
        $router->aliasMiddleware('role.security', \App\Http\Middleware\RoleSecurityMiddleware::class);
        
        // 為角色相關路由群組註冊中介軟體
        $router->middlewareGroup('role.management', [
            'auth',
            'admin',
            'role.security',
        ]);
    }

    /**
     * 註冊事件監聽器
     */
    protected function registerEventListeners(): void
    {
        // 監聽角色相關事件
        $this->app['events']->listen(
            'eloquent.created: App\Models\Role',
            function ($role) {
                $this->logRoleEvent('created', $role);
            }
        );

        $this->app['events']->listen(
            'eloquent.updated: App\Models\Role',
            function ($role) {
                $this->logRoleEvent('updated', $role);
            }
        );

        $this->app['events']->listen(
            'eloquent.deleted: App\Models\Role',
            function ($role) {
                $this->logRoleEvent('deleted', $role);
            }
        );
    }

    /**
     * 記錄角色事件
     */
    protected function logRoleEvent(string $event, $role): void
    {
        try {
            $auditService = $this->app->make(AuditLogService::class);
            $auditService->logUserManagementAction("role_{$event}", [
                'role_id' => $role->id,
                'role_name' => $role->name,
                'role_display_name' => $role->display_name,
                'is_system_role' => $role->is_system_role,
            ]);
        } catch (\Exception $e) {
            // 靜默處理錯誤，避免影響主要功能
            logger()->error('Failed to log role event', [
                'event' => $event,
                'role_id' => $role->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * 取得提供的服務
     */
    public function provides(): array
    {
        return [
            RoleSecurityService::class,
            RoleDataValidationService::class,
            AuditLogService::class,
            PermissionService::class,
        ];
    }
}