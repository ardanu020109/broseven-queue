@extends('layouts.admin')

@section('title', 'Edit User')

@section('breadcrumb')
    <x-breadcrumb :items="[['Pengaturan', null], ['Manajemen User', route('admin.users.index')], ['Edit', null]]" />
@endsection

@section('content')
    <x-page-header :title="'Edit ' . $user->name" subtitle="Kosongkan password jika tidak ingin mengubahnya." />

    @include('admin.users._form', [
        'user' => $user,
        'action' => route('admin.users.update', $user),
        'method' => 'PUT',
        'submitLabel' => 'Simpan Perubahan',
        'isSelf' => $user->is(auth()->user()),
    ])
@endsection
