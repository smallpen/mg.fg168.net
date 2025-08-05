@extends('layouts.admin')

@section('title', __('admin.users.edit'))

@section('content')
    <livewire:admin.users.user-form :user-id="$user" />
@endsection