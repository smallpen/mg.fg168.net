<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>活動記錄匯出報告 - {{ $export_info['exported_at'] }}</title>
    <style>
        /* 基本樣式 */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Microsoft JhengHei', 'PingFang TC', 'Helvetica Neue', Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 12px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            overflow: hidden;
        }

        /* 標題區域 */
        .header {
            background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }

        .header h1 {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 10px;
            text-shadow: 0 2px 4px rgba(0,0,0,0.3);
        }

        .header .subtitle {
            font-size: 1.1rem;
            opacity: 0.9;
            margin-bottom: 20px;
        }

        /* 匯出資訊卡片 */
        .export-info {
            background: rgba(255,255,255,0.1);
            border-radius: 8px;
            padding: 20px;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }

        .info-item {
            text-align: center;
        }

        .info-label {
            font-size: 0.9rem;
            opacity: 0.8;
            margin-bottom: 5px;
        }

        .info-value {
            font-size: 1.3rem;
            font-weight: 600;
        }

        /* 內容區域 */
        .content {
            padding: 30px;
        }

        /* 統計摘要 */
        .summary {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .summary-card {
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
            border-radius: 10px;
            padding: 20px;
            border-left: 4px solid #4f46e5;
            transition: transform 0.2s ease;
        }

        .summary-card:hover {
            transform: translateY(-2px);
        }

        .summary-title {
            font-size: 0.9rem;
            color: #64748b;
            margin-bottom: 8px;
            font-weight: 500;
        }

        .summary-value {
            font-size: 2rem;
            font-weight: 700;
            color: #1e293b;
        }

        /* 表格樣式 */
        .table-container {
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
        }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.9rem;
        }

        thead {
            background: linear-gradient(135deg, #1e293b 0%, #334155 100%);
            color: white;
        }

        th {
            padding: 15px 12px;
            text-align: left;
            font-weight: 600;
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        tbody tr {
            border-bottom: 1px solid #e2e8f0;
            transition: background-color 0.2s ease;
        }

        tbody tr:hover {
            background-color: #f8fafc;
        }

        tbody tr:nth-child(even) {
            background-color: #f9fafb;
        }

        td {
            padding: 12px;
            vertical-align: top;
        }

        /* 狀態標籤 */
        .status-badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .status-success {
            background-color: #dcfce7;
            color: #166534;
        }

        .status-failed {
            background-color: #fef2f2;
            color: #dc2626;
        }

        .status-warning {
            background-color: #fef3c7;
            color: #d97706;
        }

        .status-error {
            background-color: #fef2f2;
            color: #dc2626;
        }

        /* 風險等級標籤 */
        .risk-badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
        }

        .risk-low {
            background-color: #dcfce7;
            color: #166534;
        }

        .risk-medium {
            background-color: #fef3c7;
            color: #d97706;
        }

        .risk-high {
            background-color: #fed7d7;
            color: #c53030;
        }

        .risk-critical {
            background-color: #fecaca;
            color: #991b1b;
        }

        /* 頁腳 */
        .footer {
            background: #f8fafc;
            padding: 20px 30px;
            text-align: center;
            color: #64748b;
            font-size: 0.9rem;
            border-top: 1px solid #e2e8f0;
        }

        /* 列印樣式 */
        @media print {
            body {
                background: white;
                padding: 0;
            }

            .container {
                box-shadow: none;
                border-radius: 0;
            }

            .header {
                background: #4f46e5 !important;
                -webkit-print-color-adjust: exact;
                color-adjust: exact;
            }

            .summary-card {
                break-inside: avoid;
            }

            table {
                break-inside: avoid;
            }

            thead {
                background: #1e293b !important;
                -webkit-print-color-adjust: exact;
                color-adjust: exact;
            }
        }

        /* 響應式設計 */
        @media (max-width: 768px) {
            .header h1 {
                font-size: 2rem;
            }

            .export-info {
                grid-template-columns: 1fr;
            }

            .summary {
                grid-template-columns: 1fr;
            }

            .content {
                padding: 20px;
            }

            table {
                font-size: 0.8rem;
            }

            th, td {
                padding: 8px 6px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- 標題區域 -->
        <div class="header">
            <h1>🔍 活動記錄匯出報告</h1>
            <div class="subtitle">系統活動與安全事件詳細記錄</div>
            
            <div class="export-info">
                <div class="info-item">
                    <div class="info-label">匯出時間</div>
                    <div class="info-value">{{ $export_info['exported_at'] }}</div>
                </div>
                <div class="info-item">
                    <div class="info-label">記錄總數</div>
                    <div class="info-value">{{ number_format($export_info['total_records']) }} 筆</div>
                </div>
                <div class="info-item">
                    <div class="info-label">匯出格式</div>
                    <div class="info-value">HTML</div>
                </div>
            </div>
        </div>

        <!-- 內容區域 -->
        <div class="content">
            @if($activities->count() > 0)
                <!-- 統計摘要 -->
                <div class="summary">
                    @php
                        $successCount = $activities->where('result', 'success')->count();
                        $failedCount = $activities->where('result', 'failed')->count();
                        $highRiskCount = $activities->where('risk_level', '>=', 6)->count();
                        $uniqueUsers = $activities->whereNotNull('user_id')->pluck('user_id')->unique()->count();
                    @endphp
                    
                    <div class="summary-card">
                        <div class="summary-title">成功操作</div>
                        <div class="summary-value" style="color: #059669;">{{ number_format($successCount) }}</div>
                    </div>
                    
                    <div class="summary-card">
                        <div class="summary-title">失敗操作</div>
                        <div class="summary-value" style="color: #dc2626;">{{ number_format($failedCount) }}</div>
                    </div>
                    
                    <div class="summary-card">
                        <div class="summary-title">高風險事件</div>
                        <div class="summary-value" style="color: #d97706;">{{ number_format($highRiskCount) }}</div>
                    </div>
                    
                    <div class="summary-card">
                        <div class="summary-title">涉及使用者</div>
                        <div class="summary-value" style="color: #7c3aed;">{{ number_format($uniqueUsers) }}</div>
                    </div>
                </div>

                <!-- 活動記錄表格 -->
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>時間</th>
                                <th>類型</th>
                                <th>描述</th>
                                <th>模組</th>
                                @if($options['include_user_details'])
                                    <th>使用者</th>
                                @endif
                                <th>IP位址</th>
                                <th>結果</th>
                                <th>風險等級</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($activities as $activity)
                                <tr>
                                    <td>{{ $activity->created_at->format('Y-m-d H:i:s') }}</td>
                                    <td>
                                        @switch($activity->type)
                                            @case('login')
                                                🔐 登入
                                                @break
                                            @case('logout')
                                                🚪 登出
                                                @break
                                            @case('login_failed')
                                                ❌ 登入失敗
                                                @break
                                            @case('create_user')
                                                👤 建立使用者
                                                @break
                                            @case('update_user')
                                                ✏️ 更新使用者
                                                @break
                                            @case('delete_user')
                                                🗑️ 刪除使用者
                                                @break
                                            @case('security_event')
                                                🛡️ 安全事件
                                                @break
                                            @default
                                                📝 {{ $activity->type }}
                                        @endswitch
                                    </td>
                                    <td>{{ $activity->description }}</td>
                                    <td>
                                        @if($activity->module)
                                            <span style="background: #e0e7ff; color: #3730a3; padding: 2px 6px; border-radius: 4px; font-size: 0.8rem;">
                                                {{ $activity->module }}
                                            </span>
                                        @else
                                            <span style="color: #9ca3af;">-</span>
                                        @endif
                                    </td>
                                    @if($options['include_user_details'])
                                        <td>
                                            @if($activity->user)
                                                <div style="font-weight: 600;">{{ $activity->user->name }}</div>
                                                <div style="font-size: 0.8rem; color: #64748b;">{{ $activity->user->username }}</div>
                                            @else
                                                <span style="color: #9ca3af; font-style: italic;">系統</span>
                                            @endif
                                        </td>
                                    @endif
                                    <td>
                                        @if($activity->ip_address)
                                            <code style="background: #f1f5f9; padding: 2px 4px; border-radius: 3px; font-size: 0.8rem;">
                                                {{ $activity->ip_address }}
                                            </code>
                                        @else
                                            <span style="color: #9ca3af;">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        @switch($activity->result)
                                            @case('success')
                                                <span class="status-badge status-success">✅ 成功</span>
                                                @break
                                            @case('failed')
                                                <span class="status-badge status-failed">❌ 失敗</span>
                                                @break
                                            @case('warning')
                                                <span class="status-badge status-warning">⚠️ 警告</span>
                                                @break
                                            @case('error')
                                                <span class="status-badge status-error">🚨 錯誤</span>
                                                @break
                                            @default
                                                <span class="status-badge">{{ $activity->result }}</span>
                                        @endswitch
                                    </td>
                                    <td>
                                        @php
                                            $riskLevel = $activity->risk_level;
                                            $riskClass = match(true) {
                                                $riskLevel >= 8 => 'risk-critical',
                                                $riskLevel >= 6 => 'risk-high', 
                                                $riskLevel >= 4 => 'risk-medium',
                                                default => 'risk-low'
                                            };
                                            $riskText = match(true) {
                                                $riskLevel >= 8 => '🔴 極高',
                                                $riskLevel >= 6 => '🟠 高',
                                                $riskLevel >= 4 => '🟡 中',
                                                $riskLevel >= 2 => '🟢 低',
                                                default => '⚪ 極低'
                                            };
                                        @endphp
                                        <span class="risk-badge {{ $riskClass }}">{{ $riskText }}</span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div style="text-align: center; padding: 60px 20px; color: #64748b;">
                    <div style="font-size: 3rem; margin-bottom: 20px;">📋</div>
                    <h3 style="margin-bottom: 10px;">沒有找到活動記錄</h3>
                    <p>在指定的條件下沒有找到任何活動記錄。</p>
                </div>
            @endif
        </div>

        <!-- 頁腳 -->
        <div class="footer">
            <p>此報告由系統自動生成 | 匯出時間：{{ $export_info['exported_at'] }} | 格式：HTML (完美支援中文)</p>
        </div>
    </div>
</body>
</html>