<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>活動記錄匯出報告</title>
    <style>
        body {
            font-family: 'DejaVu Sans', 'SimSun', 'Microsoft YaHei', sans-serif;
            font-size: 10px;
            line-height: 1.4;
            color: #333;
            margin: 0;
            padding: 20px;
        }
        
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #333;
            padding-bottom: 15px;
        }
        
        .header h1 {
            font-size: 18px;
            margin: 0 0 10px 0;
            color: #1f2937;
        }
        
        .header .subtitle {
            font-size: 12px;
            color: #6b7280;
            margin: 5px 0;
        }
        
        .export-info {
            background-color: #f9fafb;
            padding: 15px;
            margin-bottom: 20px;
            border: 1px solid #e5e7eb;
            border-radius: 4px;
        }
        
        .export-info h2 {
            font-size: 14px;
            margin: 0 0 10px 0;
            color: #374151;
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 10px;
        }
        
        .info-item {
            display: flex;
            justify-content: space-between;
        }
        
        .info-label {
            font-weight: bold;
            color: #4b5563;
        }
        
        .info-value {
            color: #1f2937;
        }
        
        .activities-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            font-size: 9px;
        }
        
        .activities-table th,
        .activities-table td {
            border: 1px solid #d1d5db;
            padding: 6px 4px;
            text-align: left;
            vertical-align: top;
        }
        
        .activities-table th {
            background-color: #f3f4f6;
            font-weight: bold;
            color: #374151;
            font-size: 9px;
        }
        
        .activities-table tr:nth-child(even) {
            background-color: #f9fafb;
        }
        
        .risk-level {
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 8px;
            font-weight: bold;
            text-align: center;
        }
        
        .risk-high {
            background-color: #fee2e2;
            color: #dc2626;
        }
        
        .risk-medium {
            background-color: #fef3c7;
            color: #d97706;
        }
        
        .risk-low {
            background-color: #d1fae5;
            color: #059669;
        }
        
        .result-success {
            color: #059669;
            font-weight: bold;
        }
        
        .result-failed {
            color: #dc2626;
            font-weight: bold;
        }
        
        .result-warning {
            color: #d97706;
            font-weight: bold;
        }
        
        .result-error {
            color: #dc2626;
            font-weight: bold;
        }
        
        .page-break {
            page-break-before: always;
        }
        
        .footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            height: 30px;
            text-align: center;
            font-size: 8px;
            color: #6b7280;
            border-top: 1px solid #e5e7eb;
            padding-top: 5px;
            background-color: white;
        }
        
        .text-truncate {
            max-width: 150px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        
        .user-info {
            font-size: 8px;
            color: #6b7280;
        }
        
        .properties {
            font-size: 7px;
            color: #4b5563;
            max-width: 200px;
            word-break: break-all;
        }
    </style>
</head>
<body>
    <!-- 頁首 -->
    <div class="header">
        <h1>活動記錄匯出報告</h1>
        <div class="subtitle">Activity Log Export Report</div>
        <div class="subtitle">匯出時間：{{ $export_info['exported_at'] }}</div>
    </div>

    <!-- 匯出資訊 -->
    <div class="export-info">
        <h2>匯出資訊</h2>
        <div class="info-grid">
            <div class="info-item">
                <span class="info-label">總記錄數：</span>
                <span class="info-value">{{ number_format($export_info['total_records']) }} 筆</span>
            </div>
            <div class="info-item">
                <span class="info-label">匯出格式：</span>
                <span class="info-value">PDF</span>
            </div>
            @if(isset($export_info['job_id']))
            <div class="info-item">
                <span class="info-label">工作 ID：</span>
                <span class="info-value">{{ $export_info['job_id'] }}</span>
            </div>
            @endif
            <div class="info-item">
                <span class="info-label">包含使用者詳情：</span>
                <span class="info-value">{{ $options['include_user_details'] ? '是' : '否' }}</span>
            </div>
            <div class="info-item">
                <span class="info-label">包含屬性資料：</span>
                <span class="info-value">{{ $options['include_properties'] ? '是' : '否' }}</span>
            </div>
            <div class="info-item">
                <span class="info-label">包含相關資料：</span>
                <span class="info-value">{{ $options['include_related_data'] ? '是' : '否' }}</span>
            </div>
        </div>
    </div>

    <!-- 活動記錄表格 -->
    <table class="activities-table">
        <thead>
            <tr>
                <th style="width: 8%;">ID</th>
                <th style="width: 12%;">時間</th>
                <th style="width: 12%;">類型</th>
                <th style="width: 20%;">描述</th>
                @if($options['include_user_details'])
                <th style="width: 10%;">使用者</th>
                @endif
                <th style="width: 8%;">模組</th>
                <th style="width: 10%;">IP位址</th>
                <th style="width: 8%;">結果</th>
                <th style="width: 8%;">風險</th>
                @if($options['include_properties'])
                <th style="width: 15%;">屬性</th>
                @endif
            </tr>
        </thead>
        <tbody>
            @foreach($activities as $index => $activity)
                @if($index > 0 && $index % 25 === 0)
                    </tbody>
                </table>
                <div class="page-break"></div>
                <table class="activities-table">
                    <thead>
                        <tr>
                            <th style="width: 8%;">ID</th>
                            <th style="width: 12%;">時間</th>
                            <th style="width: 12%;">類型</th>
                            <th style="width: 20%;">描述</th>
                            @if($options['include_user_details'])
                            <th style="width: 10%;">使用者</th>
                            @endif
                            <th style="width: 8%;">模組</th>
                            <th style="width: 10%;">IP位址</th>
                            <th style="width: 8%;">結果</th>
                            <th style="width: 8%;">風險</th>
                            @if($options['include_properties'])
                            <th style="width: 15%;">屬性</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody>
                @endif
                
                <tr>
                    <td>{{ $activity->id ?? $activity['id'] }}</td>
                    <td>
                        @if(isset($activity->created_at))
                            {{ $activity->created_at->format('m-d H:i') }}
                        @else
                            {{ \Carbon\Carbon::parse($activity['created_at'])->format('m-d H:i') }}
                        @endif
                    </td>
                    <td class="text-truncate">
                        @php
                            $type = $activity->type ?? $activity['type'];
                            $typeDisplay = $activity->type_display ?? $activity['type_display'] ?? $type;
                        @endphp
                        {{ $typeDisplay }}
                    </td>
                    <td class="text-truncate">{{ $activity->description ?? $activity['description'] }}</td>
                    
                    @if($options['include_user_details'])
                    <td class="user-info">
                        @if(isset($activity->user))
                            {{ $activity->user->name }}
                        @elseif(isset($activity['user']))
                            {{ $activity['user']['name'] }}
                        @else
                            系統
                        @endif
                    </td>
                    @endif
                    
                    <td>{{ $activity->module ?? $activity['module'] ?? '' }}</td>
                    <td>{{ $activity->ip_address ?? $activity['ip_address'] ?? '' }}</td>
                    <td>
                        @php
                            $result = $activity->result ?? $activity['result'];
                            $resultDisplay = $activity->result_display ?? $activity['result_display'] ?? $result;
                        @endphp
                        <span class="result-{{ $result }}">{{ $resultDisplay }}</span>
                    </td>
                    <td>
                        @php
                            $riskLevel = $activity->risk_level ?? $activity['risk_level'] ?? 0;
                            $riskText = $activity->risk_level_text ?? $activity['risk_level_text'] ?? '';
                            $riskClass = $riskLevel >= 6 ? 'risk-high' : ($riskLevel >= 3 ? 'risk-medium' : 'risk-low');
                        @endphp
                        <span class="risk-level {{ $riskClass }}">{{ $riskText ?: $riskLevel }}</span>
                    </td>
                    
                    @if($options['include_properties'])
                    <td class="properties">
                        @php
                            $properties = $activity->properties ?? $activity['properties'] ?? null;
                            if (is_array($properties)) {
                                $propertiesText = json_encode($properties, JSON_UNESCAPED_UNICODE);
                            } elseif (is_string($properties)) {
                                $propertiesText = $properties;
                            } else {
                                $propertiesText = '';
                            }
                        @endphp
                        {{ Str::limit($propertiesText, 100) }}
                    </td>
                    @endif
                </tr>
            @endforeach
        </tbody>
    </table>

    <!-- 頁尾 -->
    <div class="footer">
        <div>活動記錄匯出報告 - 第 <span class="pagenum"></span> 頁 - 共 {{ number_format($export_info['total_records']) }} 筆記錄</div>
    </div>

    <script type="text/php">
        if (isset($pdf)) {
            $pdf->page_script('
                $font = $fontMetrics->get_font("DejaVu Sans", "normal");
                $size = 8;
                $pageText = "第 " . $PAGE_NUM . " 頁，共 " . $PAGE_COUNT . " 頁";
                $y = $pdf->get_height() - 25;
                $x = ($pdf->get_width() - $fontMetrics->get_text_width($pageText, $font, $size)) / 2;
                $pdf->text($x, $y, $pageText, $font, $size);
            ');
        }
    </script>
</body>
</html>