@extends('layouts.admin')

@section('title', '權限模板管理')

@section('content')
<x-admin.layout.admin-layout>
    <livewire:admin.permissions.permission-template-manager />
</x-admin.layout.admin-layout>
@endsection

@push('scripts')
<script>
    // 權限模板管理相關的 JavaScript
    document.addEventListener('DOMContentLoaded', function() {
        // 監聽 Livewire 事件
        Livewire.on('template-applied', function(data) {
            // 顯示成功訊息
            if (data.created > 0) {
                showNotification('success', `成功建立 ${data.created} 個權限`);
            }
            if (data.skipped > 0) {
                showNotification('warning', `跳過 ${data.skipped} 個已存在的權限`);
            }
        });

        Livewire.on('template-created', function() {
            showNotification('success', '模板建立成功');
        });

        Livewire.on('template-updated', function() {
            showNotification('success', '模板更新成功');
        });

        Livewire.on('template-deleted', function() {
            showNotification('success', '模板刪除成功');
        });
    });

    function showNotification(type, message) {
        // 這裡可以整合現有的通知系統
        console.log(`${type}: ${message}`);
    }
</script>
@endpush