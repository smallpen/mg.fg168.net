@extends('layouts.admin')

@section('title', '自訂統計報告')

@section('content')
<div class="container mx-auto px-4 py-6">
    <livewire:admin.activities.custom-report-builder />
</div>
@endsection