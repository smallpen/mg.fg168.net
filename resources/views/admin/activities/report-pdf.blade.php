<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>活動統計報告</title>
    <style>
        body {
            font-family: 'Microsoft JhengHei', Arial, sans-serif;
            margin: 20px;
            line-height: 1.6;
            color: #333;
        }
        
        .header {
            text-align: center;
            border-bottom: 2px solid #3b82f6;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        
        .header h1 {
            color: #1f2937;
            margin: 0;
            font-size: 28px;
        }
        
        .header .subtitle {
            color: #6b7280;
            margin: 10px 0 0 0;
            font-size: 14px;
        }
        
        .section {
            margin-bottom: 30px;
        }
        
        .section h2 {
            color: #1f2937;
            border-left: 4px solid #3b82f6;
            padding-left: 15px;
            margin-bottom: 15px;
            font-size: 20px;
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .info-item {
            background: #f9fafb;
            padding: 15px;
            border-radius: 8px;
            border: 1px solid #e5e7eb;
        }
        
        .info-item .label {
            font-weight: bold;
            color: #374151;
            margin-bottom: 5px;
        }
        
        .info-item .value {
            color: #1f2937;
            font-size: 18px;
        }
        
        .metrics-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        
        .metrics-table th,
        .metrics-table td {
            border: 1px solid #d1d5db;
            padding: 12px;
            text-align: left;
        }
        
        .metrics-table th {
            background-color: #f3f4f6;
            font-weight: bold;
            color: #374151;
        }
        
        .metrics-table tr:nth-child(even) {
            background-color: #f9fafb;
        }
        
        .footer {
            margin-top: 50px;
            padding-top: 20px;
            border-top: 1px solid #e5e7eb;
            text-align: center;
            color: #6b7280;
            font-size: 12px;
        }
        
        .chart-placeholder {
            background: #f3f4f6;
            border: 2px dashed #d1d5db;
            padding: 40px;
            text-align: center;
            color: #6b7280;
            margin: 20px 0;
            border-radius: 8px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>活動統計報告</h1>
        <p class="subtitle">
            @if(!empty($reportData['config']['name']))
                {{ $reportData['config']['name'] }}
            @endif
            @if(!empty($reportData['config']['description']))
                - {{ $reportData['config']['description'] }}
            @endif
        </p>
        <p class="subtitle">生成時間：{{ $reportData['generated_at'] }}</p>
    </div>

    {{-- 報告摘要 --}}
    <div class="section">
        <h2>報告摘要</h2>
        <div class="info-grid">
            <div class="info-item">
                <div class="label">時間範圍</div>
                <div class="value">{{ $reportData['summary']['date_range'] }}</div>
            </div>
            <div class="info-item">
                <div class="label">統計指標數量</div>
                <div class="value">{{ $reportData['summary']['total_metrics'] }} 個</div>
            </div>
            @if(isset($reportData['summary']['total_activities']))
                <div class="info-item">
                    <div class="label">總活動數</div>
                    <div class="value">{{ number_format($reportData['summary']['total_activities']) }}</div>
                </div>
            @endif
            @if(isset($reportData['summary']['unique_users']))
                <div class="info-item">
                    <div class="label">活躍使用者</div>
                    <div class="value">{{ number_format($reportData['summary']['unique_users']) }}</div>
                </div>
            @endif
        </div>
    </div>

    {{-- 統計資料 --}}
    <div class="section">
        <h2>統計資料</h2>
        <table class="metrics-table">
            <thead>
                <tr>
                    <th>統計指標</th>
                    <th>數值</th>
                    <th>說明</th>
                </tr>
            </thead>
            <tbody>
                @foreach($reportData['data'] as $metric => $data)
                    @php
                        $metricNames = [
                            'total_activities' => '總活動數',
                            'unique_users' => '活躍使用者數',
                            'security_events' => '安全事件數',
                            'success_rate' => '操作成功率',
                            'activity_by_type' => '活動類型分佈',
                            'activity_by_module' => '模組活動分佈',
                            'hourly_distribution' => '每小時分佈',
                            'daily_trends' => '每日趨勢',
                            'top_users' => '最活躍使用者',
                            'risk_analysis' => '風險分析',
                        ];
                        $metricName = $metricNames[$metric] ?? $metric;
                    @endphp
                    
                    @if(is_array($data))
                        @if(isset($data['total']))
                            <tr>
                                <td>{{ $metricName }}</td>
                                <td>{{ number_format($data['total']) }}</td>
                                <td>總計數量</td>
                            </tr>
                        @elseif(isset($data['success_rate']))
                            <tr>
                                <td>{{ $metricName }}</td>
                                <td>{{ number_format($data['success_rate'], 1) }}%</td>
                                <td>成功率百分比</td>
                            </tr>
                        @else
                            <tr>
                                <td>{{ $metricName }}</td>
                                <td>{{ count($data) }} 項</td>
                                <td>包含多個子項目</td>
                            </tr>
                        @endif
                    @else
                        <tr>
                            <td>{{ $metricName }}</td>
                            <td>{{ is_numeric($data) ? number_format($data) : $data }}</td>
                            <td>-</td>
                        </tr>
                    @endif
                @endforeach
            </tbody>
        </table>
    </div>

    {{-- 詳細資料 --}}
    @foreach($reportData['data'] as $metric => $data)
        @if(is_array($data) && count($data) > 0)
            <div class="section">
                <h2>{{ $metricNames[$metric] ?? $metric }} - 詳細資料</h2>
                
                @if($metric === 'activity_by_type' || $metric === 'activity_by_module')
                    <table class="metrics-table">
                        <thead>
                            <tr>
                                <th>項目</th>
                                <th>數量</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($data as $key => $value)
                                <tr>
                                    <td>{{ $key }}</td>
                                    <td>{{ number_format($value) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @elseif($metric === 'top_users')
                    <table class="metrics-table">
                        <thead>
                            <tr>
                                <th>排名</th>
                                <th>使用者</th>
                                <th>活動數量</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach(array_slice($data, 0, 10) as $index => $user)
                                <tr>
                                    <td>{{ $index + 1 }}</td>
                                    <td>{{ $user['user']['name'] ?? '未知使用者' }}</td>
                                    <td>{{ number_format($user['activity_count'] ?? 0) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @else
                    <div class="chart-placeholder">
                        <p>{{ $metricNames[$metric] ?? $metric }} 圖表</p>
                        <p>（圖表資料已包含在匯出的 JSON 檔案中）</p>
                    </div>
                @endif
            </div>
        @endif
    @endforeach

    {{-- 報告設定 --}}
    <div class="section">
        <h2>報告設定</h2>
        <div class="info-grid">
            <div class="info-item">
                <div class="label">選擇的指標</div>
                <div class="value">
                    @foreach($reportData['config']['metrics'] as $metric)
                        {{ $metricNames[$metric] ?? $metric }}@if(!$loop->last), @endif
                    @endforeach
                </div>
            </div>
            <div class="info-item">
                <div class="label">圖表類型</div>
                <div class="value">
                    @php
                        $chartTypeNames = [
                            'line' => '線圖',
                            'bar' => '柱狀圖',
                            'pie' => '圓餅圖',
                            'doughnut' => '甜甜圈圖',
                            'area' => '面積圖',
                            'heatmap' => '熱力圖',
                        ];
                    @endphp
                    @foreach($reportData['config']['chart_types'] as $type)
                        {{ $chartTypeNames[$type] ?? $type }}@if(!$loop->last), @endif
                    @endforeach
                </div>
            </div>
            @if(!empty($reportData['config']['filters']))
                <div class="info-item">
                    <div class="label">套用的篩選</div>
                    <div class="value">{{ count($reportData['config']['filters']) }} 個篩選條件</div>
                </div>
            @endif
            <div class="info-item">
                <div class="label">匯出格式</div>
                <div class="value">
                    @foreach($reportData['config']['export_formats'] as $format)
                        {{ strtoupper($format) }}@if(!$loop->last), @endif
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    <div class="footer">
        <p>此報告由系統自動生成 | 生成時間：{{ $reportData['generated_at'] }}</p>
        <p>如有疑問，請聯繫系統管理員</p>
    </div>
</body>
</html>