<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>系統設定變更通知</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f4f4f4;
        }
        .container {
            background-color: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #e9ecef;
        }
        .header h1 {
            color: #2c3e50;
            margin: 0;
            font-size: 24px;
        }
        .content {
            margin-bottom: 30px;
        }
        .change-details {
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 6px;
            margin: 20px 0;
            border-left: 4px solid #007bff;
        }
        .change-item {
            margin-bottom: 15px;
        }
        .change-item strong {
            color: #495057;
            display: inline-block;
            width: 120px;
        }
        .value-change {
            background-color: #fff;
            padding: 15px;
            border-radius: 4px;
            margin: 10px 0;
            border: 1px solid #dee2e6;
        }
        .old-value {
            color: #dc3545;
            background-color: #f8d7da;
            padding: 8px 12px;
            border-radius: 4px;
            margin: 5px 0;
            font-family: monospace;
        }
        .new-value {
            color: #155724;
            background-color: #d4edda;
            padding: 8px 12px;
            border-radius: 4px;
            margin: 5px 0;
            font-family: monospace;
        }
        .arrow {
            text-align: center;
            color: #6c757d;
            margin: 5px 0;
        }
        .footer {
            text-align: center;
            padding-top: 20px;
            border-top: 1px solid #e9ecef;
            color: #6c757d;
            font-size: 14px;
        }
        .important-badge {
            background-color: #dc3545;
            color: white;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: bold;
        }
        .action-badge {
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
        }
        .action-changed {
            background-color: #ffc107;
            color: #212529;
        }
        .action-restored {
            background-color: #17a2b8;
            color: white;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>
                @if($action === 'restored')
                    系統設定回復通知
                @else
                    系統設定變更通知
                @endif
            </h1>
            <div style="margin-top: 10px;">
                <span class="action-badge action-{{ $action }}">
                    @if($action === 'restored')
                        已回復
                    @else
                        已變更
                    @endif
                </span>
                @if($settingChange->is_important_change)
                    <span class="important-badge">重要變更</span>
                @endif
            </div>
        </div>

        <div class="content">
            <p>您好，</p>
            <p>
                @if($action === 'restored')
                    系統設定已被回復到先前的版本，詳細資訊如下：
                @else
                    系統設定已發生變更，詳細資訊如下：
                @endif
            </p>

            <div class="change-details">
                <div class="change-item">
                    <strong>設定項目：</strong>
                    {{ $settingChange->setting_key }}
                </div>
                
                @if($settingChange->setting && $settingChange->setting->description)
                <div class="change-item">
                    <strong>設定描述：</strong>
                    {{ $settingChange->setting->description }}
                </div>
                @endif

                @if($settingChange->setting && $settingChange->setting->category)
                <div class="change-item">
                    <strong>設定分類：</strong>
                    {{ $settingChange->setting->category }}
                </div>
                @endif

                <div class="change-item">
                    <strong>操作人員：</strong>
                    {{ $settingChange->user->name ?? '未知使用者' }}
                </div>

                <div class="change-item">
                    <strong>變更時間：</strong>
                    {{ $settingChange->created_at->format('Y-m-d H:i:s') }}
                </div>

                @if($settingChange->ip_address)
                <div class="change-item">
                    <strong>IP 位址：</strong>
                    {{ $settingChange->ip_address }}
                </div>
                @endif

                @if($settingChange->reason)
                <div class="change-item">
                    <strong>變更原因：</strong>
                    {{ $settingChange->reason }}
                </div>
                @endif
            </div>

            <div class="value-change">
                <h4 style="margin-top: 0; color: #495057;">設定值變更：</h4>
                
                <div class="old-value">
                    <strong>變更前：</strong>
                    @if($settingChange->old_value === null)
                        (空值)
                    @elseif(is_bool($settingChange->old_value))
                        {{ $settingChange->old_value ? '是' : '否' }}
                    @elseif(is_array($settingChange->old_value))
                        {{ json_encode($settingChange->old_value, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) }}
                    @else
                        {{ $settingChange->old_value }}
                    @endif
                </div>

                <div class="arrow">↓</div>

                <div class="new-value">
                    <strong>變更後：</strong>
                    @if($settingChange->new_value === null)
                        (空值)
                    @elseif(is_bool($settingChange->new_value))
                        {{ $settingChange->new_value ? '是' : '否' }}
                    @elseif(is_array($settingChange->new_value))
                        {{ json_encode($settingChange->new_value, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) }}
                    @else
                        {{ $settingChange->new_value }}
                    @endif
                </div>
            </div>

            @if($settingChange->is_important_change)
            <div style="background-color: #fff3cd; border: 1px solid #ffeaa7; padding: 15px; border-radius: 6px; margin: 20px 0;">
                <strong style="color: #856404;">⚠️ 重要提醒：</strong>
                <p style="margin: 5px 0 0 0; color: #856404;">
                    此變更可能會影響系統的安全性或核心功能，請確認變更是否符合預期。
                </p>
            </div>
            @endif

            <p>
                如果您對此變更有任何疑問，請聯繫系統管理員。
            </p>
        </div>

        <div class="footer">
            <p>此郵件由系統自動發送，請勿直接回覆。</p>
            <p>{{ config('app.name') }} 系統管理團隊</p>
        </div>
    </div>
</body>
</html>