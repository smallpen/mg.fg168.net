<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>活動記錄匯出報告</title>
    <style>
        body {
            font-family: 'DejaVu Sans', 'SimSun', 'Microsoft YaHei', sans-serif;
            font-size: 9px;
            line-height: 1.3;
            color: #333;
            margin: 0;
            padding: 15px;
        }
        
        .header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 2px solid #333;
            padding-bottom: 10px;
        }
        
        .header h1 {
            font-size: 16px;
            margin: 0 0 8px 0;
            color: #1f2937;
            font-weight: bold;
        }
        
        .header .subtitle {
            font-size: 10px;
            color: #6b7280;
            margin: 3px 0;
        }
        
        .export-info {
            background-color: #f9fafb;
            padding: 10px;
            margin-bottom: 15px;
            border: 1px solid #e5e7eb;
            border-radius: 3px;
        }
        
        .export-info h2 {
            font-size: 12px;
            margin: 0 0 8px 0;
            color: #374151;
            font-weight: bold;
        }
        
        .info-grid {
            display: table;
            width: 100%;
        }
        
        .info-row {
            display: table-row;
        }
        
        .info-item {
            display: table-cell;
            padding: 2px 10px 2px 0;
            width: 50%;
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
            margin-top: 15px;
            font-size: 8px;
        }
        
        .activities-table th,
        .activities-table td {
            border: 1px solid #d1d5db;
            padding: 4px 3px;
            text-align: left;
            vertical-align: top;
        }
        
        .activities-table th {
            background-color: #f3f4f6;
            font-weight: bold;
            color: #374151;
            font-size: 8px;
        }
        
        .activities-table tr:nth-child(even) {
            background-color: #f9fafb;
        }
        
        .risk-level {
            padding: 1px 4px;
            border-radius: 2px;
            font-size: 7px;
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
            height: 25px;
            text-align: center;
            font-size: 7px;
            color: #6b7280;
            border-top: 1px solid #e5e7eb;
            padding-top: 3px;
            background-color: white;
        }
        
        .text-truncate {
            max-width: 120px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        
        .user-info {
            font-size: 7px;
            color: #6b7280;
        }
        
        .properties {
            font-size: 6px;
            color: #4b5563;
            max-width: 150px;
            word-break: break-all;
        }
        
        /* 中文字體優化 */
        .chinese-text {
            font-family: 'DejaVu Sans', 'SimSun', sans-serif;
        }
        
        /* 確保表格內容不會過寬 */
        .col-id { width: 6%; }
        .col-time { width: 10%; }
        .col-type { width: 12%; }
        .col-desc { width: 18%; }
        .col-user { width: 8%; }
        .col-module { width: 8%; }
        .col-ip { width: 10%; }
        .col-result { width: 8%; }
        .col-risk { width: 8%; }
        .col-props { width: 12%; }
    </style>
</head>
<body>
    <!-- 頁首 -->
    <div class="header">
        <h1 class="chinese-text">活動記錄匯出報告</h1>
        <div class="subtitle">Activity Log Export Report</div>
        <div class="subtitle chinese-text">匯出時間：{{ $export_info['exported_at'] }}</div>
    </div>

    <!-- 匯出資訊 -->
    <div class="export-info">
        <h2 class="chinese-text">匯出資訊</h2>
        <div class="info-grid">
            <div class="info-row">
                <div class="info-item">
                    <span class="info-label chinese-text">總記錄數：</span>
                    <span class="info-value">{{ number_format($export_info['total_records']) }} 筆</span>
                </div>
                <div class="info-item">
                    <span class="info-label chinese-text">匯出格式：</span>
                    <span class="info-value">PDF</span>
                </div>
            </div>
            <div class="info-row">
                <div class="info-item">
                    <span class="info-label chinese-text">包含使用者詳情：</span>
                    <span class="info-value chinese-text">{{ $options['include_user_details'] ? '是' : '否' }}</span>
                </div>
                <div class="info-item">
                    <span class="info-label chinese-text">包含屬性資料：</span>
                    <span class="info-value chinese-text">{{ $options['include_properties'] ? '是' : '否' }}</span>
                </div>
            </div>
        </div>
    </div>

    <!-- 活動記錄表格 -->
    <table class="activities-table">
        <thead>
            <tr>
                <th class="col-id">ID</th>
                <th class="col-time chinese-text">時間</th>
                <th class="col-type chinese-text">類型</th>
                <th class="col-desc chinese-text">描述</th>
                @if($options['include_user_details'])
                <th class="col-user chinese-text">使用者</th>
                @endif
                <th class="col-module chinese-text">模組</th>
                <th class="col-ip">IP位址</th>
                <th class="col-result chinese-text">結果</th>
                <th class="col-risk chinese-text">風險</th>
                @if($options['include_properties'])
                <th class="col-props chinese-text">屬性</th>
                @endif
            </tr>
        </thead>
        <tbody>
            @foreach($activities as $index => $activity)
                @if($index > 0 && $index % 30 === 0)
                    </tbody>
                </table>
                <div class="page-break"></div>
                <table class="activities-table">
                    <thead>
                        <tr>
                            <th class="col-id">ID</th>
                            <th class="col-time chinese-text">時間</th>
                            <th class="col-type chinese-text">類型</th>
                            <th class="col-desc chinese-text">描述</th>
                            @if($options['include_user_details'])
                            <th class="col-user chinese-text">使用者</th>
                            @endif
                            <th class="col-module chinese-text">模組</th>
                            <th class="col-ip">IP位址</th>
                            <th class="col-result chinese-text">結果</th>
                            <th class="col-risk chinese-text">風險</th>
                            @if($options['include_properties'])
                            <th class="col-props chinese-text">屬性</th>
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
                    <td class="text-truncate chinese-text">
                        @php
                            $type = $activity->type ?? $activity['type'];
                            $typeDisplay = match($type) {
                                'login' => '登入',
                                'logout' => '登出',
                                'login_failed' => '登入失敗',
                                'create_user' => '建立使用者',
                                'update_user' => '更新使用者',
                                'delete_user' => '刪除使用者',
                                'view_dashboard' => '檢視儀表板',
                                'export_data' => '匯出資料',
                                'system_setting' => '系統設定',
                                'security_event' => '安全事件',
                                default => $type
                            };
                        @endphp
                        {{ $typeDisplay }}
                    </td>
                    <td class="text-truncate chinese-text">{{ $activity->description ?? $activity['description'] }}</td>
                    
                    @if($options['include_user_details'])
                    <td class="user-info chinese-text">
                        @if(isset($activity->user))
                            {{ $activity->user->name }}
                        @elseif(isset($activity['user']))
                            {{ $activity['user']['name'] }}
                        @else
                            系統
                        @endif
                    </td>
                    @endif
                    
                    <td class="chinese-text">{{ $activity->module ?? $activity['module'] ?? '' }}</td>
                    <td>{{ $activity->ip_address ?? $activity['ip_address'] ?? '' }}</td>
                    <td>
                        @php
                            $result = $activity->result ?? $activity['result'];
                            $resultDisplay = match($result) {
                                'success' => '成功',
                                'failed' => '失敗',
                                'warning' => '警告',
                                'error' => '錯誤',
                                default => $result
                            };
                        @endphp
                        <span class="result-{{ $result }} chinese-text">{{ $resultDisplay }}</span>
                    </td>
                    <td>
                        @php
                            $riskLevel = $activity->risk_level ?? $activity['risk_level'] ?? 0;
                            $riskText = match(true) {
                                $riskLevel >= 8 => '極高',
                                $riskLevel >= 6 => '高',
                                $riskLevel >= 4 => '中',
                                $riskLevel >= 2 => '低',
                                default => '極低'
                            };
                            $riskClass = $riskLevel >= 6 ? 'risk-high' : ($riskLevel >= 3 ? 'risk-medium' : 'risk-low');
                        @endphp
                        <span class="risk-level {{ $riskClass }} chinese-text">{{ $riskText }}</span>
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
                        {{ Str::limit($propertiesText, 80) }}
                    </td>
                    @endif
                </tr>
            @endforeach
        </tbody>
    </table>

    <!-- 頁尾 -->
    <div class="footer">
        <div class="chinese-text">活動記錄匯出報告 - 共 {{ number_format($export_info['total_records']) }} 筆記錄</div>
    </div>
</body>
</html>