<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Activity Log Export Report</title>
    <style>
        @page {
            margin: 15mm;
            size: A4 landscape;
        }
        
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 9px;
            line-height: 1.3;
            color: #333;
            margin: 0;
            padding: 0;
        }
        
        /* 標題樣式 */
        .header {
            text-align: center;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #333;
        }
        
        .header h1 {
            font-size: 18px;
            margin: 0 0 5px 0;
            font-weight: bold;
        }
        
        .header .subtitle {
            font-size: 12px;
            color: #666;
            margin: 0;
        }
        
        .export-info {
            margin-bottom: 15px;
            font-size: 8px;
            color: #666;
        }
        
        .export-info span {
            margin-right: 20px;
        }
        
        /* 表格樣式 */
        .activities-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 7px;
            margin-top: 10px;
        }
        
        .activities-table th {
            background-color: #f5f5f5;
            border: 1px solid #ddd;
            padding: 4px 3px;
            text-align: left;
            font-weight: bold;
            font-size: 7px;
        }
        
        .activities-table td {
            border: 1px solid #ddd;
            padding: 3px 2px;
            vertical-align: top;
            font-size: 6px;
        }
        
        .activities-table tbody tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        
        /* 列寬控制 */
        .col-id { width: 30px; }
        .col-time { width: 60px; }
        .col-type { width: 80px; }
        .col-desc { width: 150px; }
        .col-user { width: 60px; }
        .col-module { width: 60px; }
        .col-ip { width: 80px; }
        .col-result { width: 40px; }
        .col-risk { width: 30px; }
        .col-props { width: 120px; }
        
        /* 狀態樣式 */
        .status-success { color: #28a745; font-weight: bold; }
        .status-failed { color: #dc3545; font-weight: bold; }
        .status-warning { color: #ffc107; font-weight: bold; }
        .status-error { color: #dc3545; font-weight: bold; }
        
        .risk-high { color: #dc3545; font-weight: bold; }
        .risk-medium { color: #ffc107; font-weight: bold; }
        .risk-low { color: #28a745; }
        
        /* 文字處理 */
        .text-truncate {
            overflow: hidden;
            white-space: nowrap;
            text-overflow: ellipsis;
            max-width: 100px;
        }
        
        .properties {
            font-size: 5px;
            color: #666;
            word-break: break-all;
            max-width: 120px;
        }
        
        /* 頁尾 */
        .footer {
            position: fixed;
            bottom: 10mm;
            left: 0;
            right: 0;
            text-align: center;
            font-size: 7px;
            color: #666;
            border-top: 1px solid #ddd;
            padding-top: 5px;
        }
        
        /* Unicode 中文字符回退 */
        .chinese-text {
            font-family: 'DejaVu Sans', monospace;
        }
        
        /* 改進的中文顯示 */
        .unicode-fallback {
            font-family: 'DejaVu Sans', 'Arial Unicode MS', monospace;
        }
        
        /* 中文字符提示 */
        .chinese-notice {
            background-color: #fff3cd;
            border: 1px solid #ffeaa7;
            padding: 8px;
            margin: 10px 0;
            border-radius: 4px;
            font-size: 8px;
            color: #856404;
        }
    </style>
</head>
<body>
    <!-- 頁首 -->
    <div class="header">
        <h1>Activity Log Export Report</h1>
        <p class="subtitle">{{ $export_info['exported_at'] }}</p>
    </div>

    <!-- 匯出資訊 -->
    <div class="export-info">
        <span><strong>Total Records:</strong> {{ number_format($export_info['total_records']) }}</span>
        <span><strong>Format:</strong> PDF</span>
        <span><strong>Include User Details:</strong> {{ $options['include_user_details'] ? 'Yes' : 'No' }}</span>
        <span><strong>Include Properties:</strong> {{ $options['include_properties'] ? 'Yes' : 'No' }}</span>
        @if(isset($font_config))
        <span><strong>Chinese Support:</strong> {{ $font_config['supports_chinese'] ? 'Yes' : 'Unicode Fallback' }}</span>
        @endif
    </div>

    <!-- 中文字符說明 -->
    @if(isset($font_config) && !$font_config['supports_chinese'])
    <div class="chinese-notice">
        <strong>Notice:</strong> Chinese characters may display as boxes in this PDF. 
        For perfect Chinese character support, please use HTML export format.
        <br>
        <strong>注意：</strong> 此 PDF 中的中文字符可能顯示為方框。如需完美的中文支援，請使用 HTML 匯出格式。
    </div>
    @endif

    <!-- 活動記錄表格 -->
    <table class="activities-table">
        <thead>
            <tr>
                <th class="col-id">ID</th>
                <th class="col-time">Time</th>
                <th class="col-type">Type</th>
                <th class="col-desc">Description</th>
                @if($options['include_user_details'])
                <th class="col-user">User</th>
                @endif
                <th class="col-module">Module</th>
                <th class="col-ip">IP Address</th>
                <th class="col-result">Result</th>
                <th class="col-risk">Risk</th>
                @if($options['include_properties'])
                <th class="col-props">Properties</th>
                @endif
            </tr>
        </thead>
        <tbody>
            @foreach($activities as $activity)
            <tr>
                <td>{{ $activity->id ?? $activity['id'] }}</td>
                <td>
                    @if(isset($activity->created_at))
                        {{ $activity->created_at->format('m-d H:i') }}
                    @else
                        {{ \Carbon\Carbon::parse($activity['created_at'])->format('m-d H:i') }}
                    @endif
                </td>
                <td class="text-truncate unicode-fallback">
                    @php
                        $type = $activity->type ?? $activity['type'];
                        // 使用英文類型名稱以避免中文顯示問題
                        $typeDisplay = match($type) {
                            'login' => 'Login',
                            'logout' => 'Logout', 
                            'login_failed' => 'Login Failed',
                            'create_user' => 'Create User',
                            'update_user' => 'Update User',
                            'delete_user' => 'Delete User',
                            'view_dashboard' => 'View Dashboard',
                            'export_data' => 'Export Data',
                            'system_setting' => 'System Setting',
                            'security_event' => 'Security Event',
                            default => $type
                        };
                    @endphp
                    {{ $typeDisplay }}
                </td>
                <td class="text-truncate unicode-fallback">
                    @php
                        $description = $activity->description ?? $activity['description'];
                        // 如果描述包含中文，提供英文翻譯或簡化版本
                        if (preg_match('/[\x{4e00}-\x{9fff}]/u', $description)) {
                            // 簡化中文描述為英文關鍵詞
                            $description = preg_replace('/[\x{4e00}-\x{9fff}]+/u', '[Chinese Text]', $description);
                        }
                    @endphp
                    {{ Str::limit($description, 50) }}
                </td>
                
                @if($options['include_user_details'])
                <td class="unicode-fallback">
                    @if(isset($activity->user))
                        {{ $activity->user->username ?? $activity->user->name }}
                    @elseif(isset($activity['user']))
                        {{ $activity['user']['username'] ?? $activity['user']['name'] }}
                    @else
                        System
                    @endif
                </td>
                @endif
                
                <td class="unicode-fallback">{{ $activity->module ?? $activity['module'] ?? '' }}</td>
                <td>{{ $activity->ip_address ?? $activity['ip_address'] ?? '' }}</td>
                <td>
                    @php
                        $result = $activity->result ?? $activity['result'];
                        $resultClass = 'status-' . $result;
                        $resultDisplay = match($result) {
                            'success' => 'Success',
                            'failed' => 'Failed',
                            'warning' => 'Warning',
                            'error' => 'Error',
                            default => ucfirst($result)
                        };
                    @endphp
                    <span class="{{ $resultClass }}">{{ $resultDisplay }}</span>
                </td>
                <td>
                    @php
                        $riskLevel = $activity->risk_level ?? $activity['risk_level'] ?? 0;
                        $riskClass = $riskLevel >= 6 ? 'risk-high' : ($riskLevel >= 3 ? 'risk-medium' : 'risk-low');
                    @endphp
                    <span class="{{ $riskClass }}">{{ $riskLevel }}</span>
                </td>
                
                @if($options['include_properties'])
                <td>
                    @php
                        $properties = $activity->properties ?? $activity['properties'] ?? null;
                        if (is_array($properties)) {
                            $propertiesText = json_encode($properties, JSON_UNESCAPED_UNICODE);
                        } elseif (is_string($properties)) {
                            $propertiesText = $properties;
                        } else {
                            $propertiesText = '';
                        }
                        // 移除或替換中文字符以避免顯示問題
                        if (preg_match('/[\x{4e00}-\x{9fff}]/u', $propertiesText)) {
                            $propertiesText = preg_replace('/[\x{4e00}-\x{9fff}]+/u', '[Chinese]', $propertiesText);
                        }
                    @endphp
                    @if($propertiesText)
                    <div class="properties">
                        {{ Str::limit($propertiesText, 80) }}
                    </div>
                    @endif
                </td>
                @endif
            </tr>
            @endforeach
        </tbody>
    </table>

    <!-- 頁尾 -->
    <div class="footer">
        <p>Activity Log Export Report - {{ number_format($export_info['total_records']) }} records</p>
        <p>Note: Chinese characters may display as boxes in PDF format. For better Chinese support, use HTML export format.</p>
    </div>
</body>
</html>