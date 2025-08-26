<?php

namespace App\Exceptions;

use App\Services\LoggingService;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\Request;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * 不應該被報告的例外類型清單
     *
     * @var array<int, class-string<\Throwable>>
     */
    protected $dontReport = [
        //
    ];

    /**
     * 不應該被閃存到 session 的輸入清單
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * 註冊應用程式的例外處理回呼
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            // 使用日誌服務記錄系統錯誤
            if (app()->bound(LoggingService::class)) {
                try {
                    $loggingService = app(LoggingService::class);
                    $loggingService->logSystemError($e, [
                        'url' => request()->fullUrl(),
                        'method' => request()->method(),
                        'input' => request()->except($this->dontFlash),
                    ]);
                } catch (\Exception $logException) {
                    // 如果日誌記錄失敗，避免循環錯誤
                    error_log('日誌記錄失敗: ' . $logException->getMessage());
                }
            }
        });

        // 處理認證例外
        $this->renderable(function (AuthenticationException $e, Request $request) {
            if (app()->bound(LoggingService::class)) {
                try {
                    $loggingService = app(LoggingService::class);
                    $loggingService->logSecurityEvent(
                        'authentication_failed',
                        '未認證的存取嘗試',
                        [
                            'url' => $request->fullUrl(),
                            'method' => $request->method(),
                        ],
                        'medium'
                    );
                } catch (\Exception $logException) {
                    error_log('安全事件日誌記錄失敗: ' . $logException->getMessage());
                }
            }

            if ($request->expectsJson()) {
                return response()->json(['message' => '未認證'], 401);
            }

            return redirect()->guest(route('admin.login'));
        });

        // 處理授權例外
        $this->renderable(function (AuthorizationException $e, Request $request) {
            if (app()->bound(LoggingService::class)) {
                try {
                    $loggingService = app(LoggingService::class);
                    $loggingService->logPermissionViolation(
                        $request->method(),
                        $request->fullUrl(),
                        '權限不足'
                    );
                } catch (\Exception $logException) {
                    error_log('權限違規日誌記錄失敗: ' . $logException->getMessage());
                }
            }

            if ($request->expectsJson()) {
                return response()->json(['message' => '權限不足'], 403);
            }

            // 如果是管理後台路由，使用專門的 403 頁面
            if ($request->is('admin/*')) {
                return response()->view('admin.errors.403', [
                    'message' => '您沒有權限存取此功能',
                    'title' => '權限不足',
                    'description' => '您沒有足夠的權限存取此功能。如需協助，請聯繫系統管理員。',
                    'back_url' => route('admin.dashboard')
                ], 403);
            }

            return response()->view('errors.403', [], 403);
        });

        // 處理 HTTP 權限例外
        $this->renderable(function (HttpException $e, Request $request) {
            if ($e->getStatusCode() === 403) {
                if (app()->bound(LoggingService::class)) {
                    try {
                        $loggingService = app(LoggingService::class);
                        $loggingService->logPermissionViolation(
                            $request->method(),
                            $request->fullUrl(),
                            '權限不足'
                        );
                    } catch (\Exception $logException) {
                        error_log('權限違規日誌記錄失敗: ' . $logException->getMessage());
                    }
                }

                if ($request->expectsJson()) {
                    return response()->json(['message' => '權限不足'], 403);
                }

                // 如果是管理後台路由，使用專門的 403 頁面
                if ($request->is('admin/*')) {
                    return response()->view('admin.errors.403', [
                        'message' => '您沒有權限存取此功能',
                        'title' => '權限不足',
                        'description' => '您沒有足夠的權限存取此功能。如需協助，請聯繫系統管理員。',
                        'back_url' => route('admin.dashboard')
                    ], 403);
                }

                return response()->view('errors.403', [], 403);
            }
        });
    }
}