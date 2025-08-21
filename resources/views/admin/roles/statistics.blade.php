@extends('admin.layouts.app')

@section('title', '角色統計')

@section('content')
<div class="container mx-auto px-4 py-6">
    {{-- 系統角色統計 --}}
    <livewire:admin.roles.role-statistics mode="system" />
</div>
@endsection