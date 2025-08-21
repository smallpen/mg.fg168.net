@extends('admin.layouts.app')

@section('title', $role->display_name . ' - 統計資訊')

@section('content')
<div class="container mx-auto px-4 py-6">
    {{-- 特定角色統計 --}}
    <livewire:admin.roles.role-statistics :role="$role" mode="role" />
</div>
@endsection