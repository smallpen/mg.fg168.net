<?php

use Illuminate\Support\Facades\Route;
use App\Services\ActivityExportService;
use App\Models\Activity;

// 測試 PDF 匯出功能的路由
Route::get('/test-pdf-export', function () {
    try {
        // 取得少量測試資料
        $activities = Activity::with('user:id,username,name,email')
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        $data = [
            'activities' => $activities,
            'options' => [
                'include_user_details' => true,
                'include_properties' => true,
                'include_related_data' => false,
            ],
            'export_info' => [
                'exported_at' => now()->format('Y-m-d H:i:s'),
                'total_records' => $activities->count(),
            ],
        ];

        // 直接返回 PDF 到瀏覽器
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('exports.activities-pdf-chinese', $data)
            ->setPaper('a4', 'landscape')
            ->setOptions([
                'defaultFont' => 'DejaVu Sans',
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled' => false,
            ]);

        return $pdf->stream('test-chinese-export.pdf');

    } catch (\Exception $e) {
        return response()->json([
            'error' => 'PDF 生成失敗',
            'message' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ], 500);
    }
})->middleware(['web', 'auth']);

// 簡單的中文字體測試
Route::get('/test-chinese-font', function () {
    $html = '
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <style>
            body { font-family: "DejaVu Sans", "SimSun", sans-serif; font-size: 14px; }
            .chinese { color: red; }
            .english { color: blue; }
        </style>
    </head>
    <body>
        <h1 class="chinese">中文測試標題</h1>
        <p class="english">English Test Text</p>
        <p class="chinese">這是中文內容測試：登入、登出、建立使用者、系統設定</p>
        <table border="1">
            <tr>
                <th class="chinese">中文標題</th>
                <th class="english">English Title</th>
            </tr>
            <tr>
                <td class="chinese">測試資料</td>
                <td class="english">Test Data</td>
            </tr>
        </table>
    </body>
    </html>';
    
    $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadHTML($html)
        ->setPaper('a4', 'portrait')
        ->setOptions([
            'defaultFont' => 'DejaVu Sans',
            'isHtml5ParserEnabled' => true,
        ]);
    
    return $pdf->stream('chinese-font-test.pdf');
})->middleware(['web', 'auth']);