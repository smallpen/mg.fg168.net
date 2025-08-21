{{-- 
權限管理多語言支援示例
Permission Management Multilingual Support Example
--}}

<div class="permission-multilingual-demo">
    {{-- 使用 Blade 指令的範例 / Examples using Blade directives --}}
    
    <h1>@permission('titles.permission_management')</h1>
    
    <div class="actions">
        <button class="btn btn-primary">
            @permissionUI('buttons.create_new')
        </button>
        
        <button class="btn btn-secondary">
            @permissionUI('buttons.export_permissions')
        </button>
    </div>

    {{-- 表單範例 / Form example --}}
    <form class="permission-form">
        <div class="form-group">
            <label for="name">@permission('form.name')</label>
            <input type="text" 
                   id="name" 
                   name="name" 
                   placeholder="@permission('form.name_placeholder')"
                   title="@permissionUI('tooltips.permission_name_help')">
        </div>

        <div class="form-group">
            <label for="display_name">@permission('form.display_name')</label>
            <input type="text" 
                   id="display_name" 
                   name="display_name" 
                   placeholder="@permission('form.display_name_placeholder')"
                   title="@permissionUI('tooltips.display_name_help')">
        </div>

        <div class="form-group">
            <label for="module">@permission('form.module')</label>
            <select id="module" name="module">
                <option value="">@permission('form.module_placeholder')</option>
                @foreach($permissionModules as $key => $value)
                    <option value="{{ $key }}">{{ $value }}</option>
                @endforeach
            </select>
        </div>

        <div class="form-group">
            <label for="type">@permission('form.type')</label>
            <select id="type" name="type">
                <option value="">@permission('form.type_placeholder')</option>
                @foreach($permissionTypes as $key => $value)
                    <option value="{{ $key }}">{{ $value }}</option>
                @endforeach
            </select>
        </div>
    </form>

    {{-- 表格範例 / Table example --}}
    <table class="permission-table">
        <thead>
            <tr>
                <th>@permission('table.name')</th>
                <th>@permission('table.display_name')</th>
                <th>@permission('table.module')</th>
                <th>@permission('table.type')</th>
                <th>@permission('table.status')</th>
                <th>@permission('table.created_at')</th>
                <th>@permission('table.actions')</th>
            </tr>
        </thead>
        <tbody>
            {{-- 示例資料行 / Example data row --}}
            <tr>
                <td>users.create</td>
                <td>@permission('types.create') @permission('modules.users')</td>
                <td>@permissionModule('users')</td>
                <td>@permissionType('create')</td>
                <td>
                    <span class="status-badge status-active">
                        @permissionStatus('active')
                    </span>
                </td>
                <td>@permissionDateTime('2024-01-15 10:30:00')</td>
                <td>
                    <button class="btn btn-sm btn-primary">
                        @permissionUI('buttons.edit_permission')
                    </button>
                    <button class="btn btn-sm btn-danger">
                        @permissionUI('buttons.delete_permission')
                    </button>
                </td>
            </tr>
        </tbody>
    </table>

    {{-- 狀態指示器範例 / Status indicators example --}}
    <div class="status-indicators">
        <h3>@permission('titles.usage_analysis')</h3>
        
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-value">@permissionNumber(125)</div>
                <div class="stat-label">@permission('usage.total_permissions')</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-value">@permissionNumber(98)</div>
                <div class="stat-label">@permission('usage.used_permissions')</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-value">@permissionNumber(27)</div>
                <div class="stat-label">@permission('usage.unused_permissions')</div>
            </div>
        </div>
    </div>

    {{-- 訊息範例 / Messages example --}}
    <div class="messages">
        {{-- 成功訊息 / Success message --}}
        <div class="alert alert-success">
            @permissionMessage('crud.created', ['name' => 'users.create'])
        </div>

        {{-- 錯誤訊息 / Error message --}}
        <div class="alert alert-danger">
            @permissionError('validation.name_required')
        </div>

        {{-- 警告訊息 / Warning message --}}
        <div class="alert alert-warning">
            @permissionError('deletion.permission_has_roles', ['count' => 3])
        </div>
    </div>

    {{-- 搜尋和篩選範例 / Search and filter example --}}
    <div class="search-filters">
        <div class="search-box">
            <input type="text" 
                   placeholder="@permission('search.search_placeholder')"
                   class="form-control">
        </div>

        <div class="filters">
            <select class="form-control">
                <option value="">@permission('search.all_modules')</option>
                @foreach($permissionModules as $key => $value)
                    <option value="{{ $key }}">{{ $value }}</option>
                @endforeach
            </select>

            <select class="form-control">
                <option value="">@permission('search.all_types')</option>
                @foreach($permissionTypes as $key => $value)
                    <option value="{{ $key }}">{{ $value }}</option>
                @endforeach
            </select>

            <select class="form-control">
                <option value="">@permission('search.all_statuses')</option>
                @foreach($permissionStatuses as $key => $value)
                    <option value="{{ $key }}">{{ $value }}</option>
                @endforeach
            </select>
        </div>
    </div>

    {{-- 批量操作範例 / Bulk operations example --}}
    <div class="bulk-operations">
        <div class="bulk-selector">
            <input type="checkbox" id="select-all">
            <label for="select-all">@permission('bulk.select_all')</label>
        </div>

        <div class="bulk-actions">
            <select class="form-control">
                <option value="">@permission('bulk.bulk_actions')</option>
                <option value="delete">@permission('bulk.bulk_delete')</option>
                <option value="activate">@permission('bulk.bulk_activate')</option>
                <option value="deactivate">@permission('bulk.bulk_deactivate')</option>
                <option value="export">@permission('bulk.bulk_export')</option>
            </select>
        </div>
    </div>

    {{-- 分頁範例 / Pagination example --}}
    <div class="pagination-info">
        @permission('pagination.showing', [
            'from' => 1,
            'to' => 25,
            'total' => 125
        ])
    </div>

    {{-- 空狀態範例 / Empty state example --}}
    <div class="empty-state">
        <div class="empty-icon">📋</div>
        <div class="empty-title">@permission('empty.no_permissions')</div>
        <div class="empty-description">@permission('empty.create_first_permission')</div>
        <button class="btn btn-primary">
            @permissionUI('buttons.create_new')
        </button>
    </div>
</div>

{{-- CSS 樣式 / CSS Styles --}}
<style>
.permission-multilingual-demo {
    padding: 20px;
    max-width: 1200px;
    margin: 0 auto;
}

.actions {
    margin: 20px 0;
}

.actions .btn {
    margin-right: 10px;
}

.permission-form .form-group {
    margin-bottom: 15px;
}

.permission-form label {
    display: block;
    margin-bottom: 5px;
    font-weight: bold;
}

.permission-form input,
.permission-form select {
    width: 100%;
    padding: 8px 12px;
    border: 1px solid #ddd;
    border-radius: 4px;
}

.permission-table {
    width: 100%;
    border-collapse: collapse;
    margin: 20px 0;
}

.permission-table th,
.permission-table td {
    padding: 12px;
    text-align: left;
    border-bottom: 1px solid #ddd;
}

.permission-table th {
    background-color: #f8f9fa;
    font-weight: bold;
}

.status-badge {
    padding: 4px 8px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: bold;
}

.status-active {
    background-color: #d4edda;
    color: #155724;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin: 20px 0;
}

.stat-card {
    background: #f8f9fa;
    padding: 20px;
    border-radius: 8px;
    text-align: center;
}

.stat-value {
    font-size: 2em;
    font-weight: bold;
    color: #007bff;
}

.stat-label {
    margin-top: 8px;
    color: #6c757d;
}

.messages {
    margin: 20px 0;
}

.alert {
    padding: 12px 16px;
    margin-bottom: 10px;
    border-radius: 4px;
}

.alert-success {
    background-color: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
}

.alert-danger {
    background-color: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
}

.alert-warning {
    background-color: #fff3cd;
    color: #856404;
    border: 1px solid #ffeaa7;
}

.search-filters {
    display: flex;
    gap: 15px;
    margin: 20px 0;
    align-items: center;
}

.search-box {
    flex: 1;
}

.filters {
    display: flex;
    gap: 10px;
}

.filters select {
    min-width: 150px;
}

.bulk-operations {
    display: flex;
    align-items: center;
    gap: 15px;
    margin: 20px 0;
    padding: 15px;
    background-color: #f8f9fa;
    border-radius: 4px;
}

.pagination-info {
    text-align: center;
    margin: 20px 0;
    color: #6c757d;
}

.empty-state {
    text-align: center;
    padding: 60px 20px;
    color: #6c757d;
}

.empty-icon {
    font-size: 4em;
    margin-bottom: 20px;
}

.empty-title {
    font-size: 1.5em;
    font-weight: bold;
    margin-bottom: 10px;
}

.empty-description {
    margin-bottom: 20px;
}

.btn {
    padding: 8px 16px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    text-decoration: none;
    display: inline-block;
}

.btn-primary {
    background-color: #007bff;
    color: white;
}

.btn-secondary {
    background-color: #6c757d;
    color: white;
}

.btn-danger {
    background-color: #dc3545;
    color: white;
}

.btn-sm {
    padding: 4px 8px;
    font-size: 0.875em;
}

.form-control {
    padding: 8px 12px;
    border: 1px solid #ddd;
    border-radius: 4px;
}
</style>