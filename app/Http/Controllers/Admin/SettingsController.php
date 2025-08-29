<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Repositories\SettingsRepositoryInterface;
use App\Services\ConfigurationService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;

/**
 * 系統設定控制器
 * 
 * 處理系統設定管理的 HTTP 請求，包含設定查詢、更新、備份還原等功能
 */
class SettingsController extends Controller
{
    /**
     * 設定儲存庫介面
     */
    protected SettingsRepositoryInterface $settingsRepository;

    /**
     * 配置服務
     */
    protected ConfigurationService $configurationService;

    /**
     * 建構函式
     */
    public function __construct(
        SettingsRepositoryInterface $settingsRepository,
        ConfigurationService $configurationService
    ) {
        $this->settingsRepository = $settingsRepository;
        $this->configurationService = $configurationService;
    }

    /**
     * 顯示設定管理主頁面
     */
    public function index(): View
    {
        $this->authorize('settings.view');

        $categories = $this->configurationService->getCategories();
        $stats = $this->getSettingsStats();

        return view('admin.settings.index', compact('categories', 'stats'));
    }

    /**
     * 顯示系統設定管理頁面
     */
    public function system(): View
    {
        $this->authorize('settings.view');

        return view('admin.settings.system');
    }

    /**
     * 顯示基本設定頁面
     */
    public function basic(): View
    {
        $this->authorize('settings.view');

        return view('admin.settings.basic');
    }

    /**
     * 顯示安全設定頁面
     */
    public function security(): View
    {
        $this->authorize('system.security');

        return view('admin.settings.security');
    }

    /**
     * 顯示外觀設定頁面
     */
    public function appearance(): View
    {
        $this->authorize('settings.view');

        return view('admin.settings.appearance');
    }

    /**
     * 顯示通知設定頁面
     */
    public function notifications(): View
    {
        $this->authorize('settings.view');

        return view('admin.settings.notifications');
    }

    /**
     * 顯示整合設定頁面
     */
    public function integration(): View
    {
        $this->authorize('settings.view');

        return view('admin.settings.integration');
    }

    /**
     * 顯示維護設定頁面
     */
    public function maintenance(): View
    {
        $this->authorize('system.maintenance');

        return view('admin.settings.maintenance');
    }

    /**
     * 顯示備份管理頁面
     */
    public function backups(): View
    {
        $this->authorize('settings.backup');

        return view('admin.settings.backups');
    }

    /**
     * 顯示設定變更歷史頁面
     */
    public function history(): View
    {
        $this->authorize('settings.view');

        return view('admin.settings.history');
    }

    /**
     * API: 取得所有設定
     */
    public function getAllSettings(Request $request): JsonResponse
    {
        $this->authorize('settings.view');

        try {
            $category = $request->get('category');
            $search = $request->get('search');
            $changed = $request->get('changed');

            if ($category && $category !== 'all') {
                $settings = $this->settingsRepository->getSettingsByCategory($category);
            } else {
                $settings = $this->settingsRepository->getAllSettings();
            }

            // 搜尋篩選
            if ($search) {
                $settings = $settings->filter(function ($setting) use ($search) {
                    return str_contains(strtolower($setting->key), strtolower($search)) ||
                           str_contains(strtolower($setting->description ?? ''), strtolower($search));
                });
            }

            // 變更狀態篩選
            if ($changed === 'changed') {
                $settings = $settings->filter(function ($setting) {
                    return $setting->is_changed;
                });
            } elseif ($changed === 'unchanged') {
                $settings = $settings->filter(function ($setting) {
                    return !$setting->is_changed;
                });
            }

            return response()->json([
                'success' => true,
                'data' => $settings->values(),
                'total' => $settings->count(),
            ]);

        } catch (\Exception $e) {
            Log::error('取得設定清單失敗', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
            ]);

            return response()->json([
                'success' => false,
                'message' => '取得設定清單失敗',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * API: 取得單一設定
     */
    public function getSetting(string $key): JsonResponse
    {
        $this->authorize('settings.view');

        try {
            $setting = $this->settingsRepository->getSetting($key);

            if (!$setting) {
                return response()->json([
                    'success' => false,
                    'message' => '設定項目不存在',
                ], 404);
            }

            $config = $this->configurationService->getSettingConfig($key);

            return response()->json([
                'success' => true,
                'data' => $setting,
                'config' => $config,
            ]);

        } catch (\Exception $e) {
            Log::error('取得設定項目失敗', [
                'key' => $key,
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
            ]);

            return response()->json([
                'success' => false,
                'message' => '取得設定項目失敗',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * API: 更新設定
     */
    public function updateSetting(Request $request, string $key): JsonResponse
    {
        $this->authorize('settings.edit');

        try {
            $value = $request->input('value');

            // 驗證設定值
            if (!$this->configurationService->validateSettingValue($key, $value)) {
                return response()->json([
                    'success' => false,
                    'message' => '設定值格式不正確',
                ], 422);
            }

            // 更新設定
            $result = $this->settingsRepository->updateSetting($key, $value);

            if (!$result) {
                return response()->json([
                    'success' => false,
                    'message' => '更新設定失敗',
                ], 500);
            }

            // 記錄操作日誌
            Log::info('設定已更新', [
                'key' => $key,
                'value' => $value,
                'user_id' => auth()->id(),
            ]);

            return response()->json([
                'success' => true,
                'message' => '設定已成功更新',
                'data' => $this->settingsRepository->getSetting($key),
            ]);

        } catch (\Exception $e) {
            Log::error('更新設定失敗', [
                'key' => $key,
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
            ]);

            return response()->json([
                'success' => false,
                'message' => '更新設定失敗',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * API: 批量更新設定
     */
    public function batchUpdate(Request $request): JsonResponse
    {
        $this->authorize('settings.edit');

        $validator = Validator::make($request->all(), [
            'settings' => 'required|array',
            'settings.*.key' => 'required|string',
            'settings.*.value' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => '請求資料格式不正確',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $settings = $request->input('settings');
            $results = [];
            $errors = [];

            foreach ($settings as $settingData) {
                $key = $settingData['key'];
                $value = $settingData['value'];

                try {
                    // 驗證設定值
                    if (!$this->configurationService->validateSettingValue($key, $value)) {
                        $errors[$key] = '設定值格式不正確';
                        continue;
                    }

                    // 更新設定
                    $result = $this->settingsRepository->updateSetting($key, $value);
                    $results[$key] = $result;

                } catch (\Exception $e) {
                    $errors[$key] = $e->getMessage();
                }
            }

            // 記錄操作日誌
            Log::info('批量更新設定', [
                'settings_count' => count($settings),
                'success_count' => count($results),
                'error_count' => count($errors),
                'user_id' => auth()->id(),
            ]);

            return response()->json([
                'success' => count($errors) === 0,
                'message' => count($errors) === 0 ? '所有設定已成功更新' : '部分設定更新失敗',
                'results' => $results,
                'errors' => $errors,
            ]);

        } catch (\Exception $e) {
            Log::error('批量更新設定失敗', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
            ]);

            return response()->json([
                'success' => false,
                'message' => '批量更新設定失敗',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * API: 重設設定為預設值
     */
    public function resetSetting(string $key): JsonResponse
    {
        $this->authorize('settings.reset');

        try {
            $result = $this->settingsRepository->resetSetting($key);

            if (!$result) {
                return response()->json([
                    'success' => false,
                    'message' => '重設設定失敗',
                ], 500);
            }

            // 記錄操作日誌
            Log::info('設定已重設', [
                'key' => $key,
                'user_id' => auth()->id(),
            ]);

            return response()->json([
                'success' => true,
                'message' => '設定已重設為預設值',
                'data' => $this->settingsRepository->getSetting($key),
            ]);

        } catch (\Exception $e) {
            Log::error('重設設定失敗', [
                'key' => $key,
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
            ]);

            return response()->json([
                'success' => false,
                'message' => '重設設定失敗',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * API: 測試連線
     */
    public function testConnection(Request $request): JsonResponse
    {
        $this->authorize('settings.view');

        $validator = Validator::make($request->all(), [
            'type' => 'required|string|in:smtp,database,redis,api',
            'config' => 'required|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => '請求資料格式不正確',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $type = $request->input('type');
            $config = $request->input('config');

            $result = $this->configurationService->testConnection($type, $config);

            return response()->json([
                'success' => $result,
                'message' => $result ? '連線測試成功' : '連線測試失敗',
            ]);

        } catch (\Exception $e) {
            Log::error('連線測試失敗', [
                'type' => $request->input('type'),
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
            ]);

            return response()->json([
                'success' => false,
                'message' => '連線測試失敗',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * API: 匯出設定
     */
    public function exportSettings(Request $request): JsonResponse
    {
        $this->authorize('settings.backup');

        try {
            $categories = $request->input('categories', []);
            $data = $this->settingsRepository->exportSettings($categories);

            // 記錄操作日誌
            Log::info('設定已匯出', [
                'categories' => $categories,
                'settings_count' => count($data),
                'user_id' => auth()->id(),
            ]);

            return response()->json([
                'success' => true,
                'data' => $data,
                'filename' => 'settings_export_' . date('Y-m-d_H-i-s') . '.json',
            ]);

        } catch (\Exception $e) {
            Log::error('匯出設定失敗', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
            ]);

            return response()->json([
                'success' => false,
                'message' => '匯出設定失敗',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * API: 匯入設定
     */
    public function importSettings(Request $request): JsonResponse
    {
        $this->authorize('settings.backup');

        $validator = Validator::make($request->all(), [
            'data' => 'required|array',
            'conflict_strategy' => 'required|string|in:skip,update,merge',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => '請求資料格式不正確',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $data = $request->input('data');
            $conflictStrategy = $request->input('conflict_strategy');

            $result = $this->settingsRepository->importSettings($data, $conflictStrategy);

            // 記錄操作日誌
            Log::info('設定已匯入', [
                'settings_count' => count($data),
                'conflict_strategy' => $conflictStrategy,
                'result' => $result,
                'user_id' => auth()->id(),
            ]);

            return response()->json([
                'success' => true,
                'message' => '設定匯入完成',
                'result' => $result,
            ]);

        } catch (\Exception $e) {
            Log::error('匯入設定失敗', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
            ]);

            return response()->json([
                'success' => false,
                'message' => '匯入設定失敗',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * API: 清除設定快取
     */
    public function clearCache(): JsonResponse
    {
        $this->authorize('system.maintenance');

        try {
            Cache::tags(['settings'])->flush();

            // 記錄操作日誌
            Log::info('設定快取已清除', [
                'user_id' => auth()->id(),
            ]);

            return response()->json([
                'success' => true,
                'message' => '設定快取已清除',
            ]);

        } catch (\Exception $e) {
            Log::error('清除設定快取失敗', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
            ]);

            return response()->json([
                'success' => false,
                'message' => '清除設定快取失敗',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * 取得設定統計資訊
     */
    protected function getSettingsStats(): array
    {
        try {
            $allSettings = $this->settingsRepository->getAllSettings();
            $changedSettings = $this->settingsRepository->getChangedSettings();
            $categories = $this->configurationService->getCategories();

            return [
                'total' => $allSettings->count(),
                'changed' => $changedSettings->count(),
                'categories' => count($categories),
                'by_category' => $allSettings->groupBy('category')->map->count(),
            ];

        } catch (\Exception $e) {
            Log::warning('取得設定統計失敗', [
                'error' => $e->getMessage(),
            ]);

            return [
                'total' => 0,
                'changed' => 0,
                'categories' => 0,
                'by_category' => [],
            ];
        }
    }
}