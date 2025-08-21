<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cache;

/**
 * 角色翻譯 API 控制器
 * 
 * 提供前端所需的角色管理翻譯資料
 */
class RoleTranslationController extends Controller
{
    /**
     * 取得角色管理翻譯資料
     *
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        $locale = App::getLocale();
        $cacheKey = "role_translations_{$locale}";
        
        // 快取翻譯資料 1 小時
        $translations = Cache::remember($cacheKey, 3600, function () {
            return [
                'permission_names' => __('role_management.permission_names'),
                'permission_descriptions' => __('role_management.permission_descriptions'),
                'role_names' => __('role_management.role_names'),
                'role_descriptions' => __('role_management.role_descriptions'),
                'modules' => __('role_management.modules'),
                'messages' => __('role_management.messages'),
                'errors' => [
                    'general' => __('role_errors.general'),
                    'crud' => __('role_errors.crud'),
                    'permissions' => __('role_errors.permissions'),
                    'hierarchy' => __('role_errors.hierarchy'),
                    'system_roles' => __('role_errors.system_roles'),
                    'validation' => __('role_errors.validation'),
                    'authorization' => __('role_errors.authorization'),
                    'deletion' => __('role_errors.deletion'),
                    'bulk' => __('role_errors.bulk'),
                ],
                'table' => __('role_management.table'),
                'actions' => __('role_management.actions'),
                'status' => __('role_management.status'),
                'filters' => __('role_management.filters'),
                'sort' => __('role_management.sort'),
            ];
        });
        
        return response()->json($translations);
    }

    /**
     * 取得特定類型的翻譯資料
     *
     * @param string $type 翻譯類型
     * @return JsonResponse
     */
    public function show(string $type): JsonResponse
    {
        $locale = App::getLocale();
        $cacheKey = "role_translations_{$locale}_{$type}";
        
        $translations = Cache::remember($cacheKey, 3600, function () use ($type) {
            switch ($type) {
                case 'permissions':
                    return [
                        'names' => __('role_management.permission_names'),
                        'descriptions' => __('role_management.permission_descriptions'),
                    ];
                    
                case 'roles':
                    return [
                        'names' => __('role_management.role_names'),
                        'descriptions' => __('role_management.role_descriptions'),
                    ];
                    
                case 'modules':
                    return __('role_management.modules');
                    
                case 'errors':
                    return [
                        'general' => __('role_errors.general'),
                        'crud' => __('role_errors.crud'),
                        'permissions' => __('role_errors.permissions'),
                        'hierarchy' => __('role_errors.hierarchy'),
                        'system_roles' => __('role_errors.system_roles'),
                        'validation' => __('role_errors.validation'),
                        'authorization' => __('role_errors.authorization'),
                        'deletion' => __('role_errors.deletion'),
                        'bulk' => __('role_errors.bulk'),
                    ];
                    
                case 'messages':
                    return __('role_management.messages');
                    
                default:
                    return [];
            }
        });
        
        if (empty($translations)) {
            return response()->json(['error' => 'Translation type not found'], 404);
        }
        
        return response()->json($translations);
    }

    /**
     * 清除翻譯快取
     *
     * @return JsonResponse
     */
    public function clearCache(): JsonResponse
    {
        $locales = ['en', 'zh_TW'];
        $types = ['permissions', 'roles', 'modules', 'errors', 'messages'];
        
        foreach ($locales as $locale) {
            // 清除完整翻譯快取
            Cache::forget("role_translations_{$locale}");
            
            // 清除特定類型翻譯快取
            foreach ($types as $type) {
                Cache::forget("role_translations_{$locale}_{$type}");
            }
        }
        
        return response()->json(['message' => 'Translation cache cleared successfully']);
    }
}