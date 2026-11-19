@extends('layouts.admin')

@section('title', 'Tambah User')

@section('breadcrumb')
    <x-breadcrumb :items="[['Pengaturan', null], ['Manajemen User', route('admin.users.index')], ['Tambah', null]]" />
@endsection

@section('content')
    <x-page-header title="Tambah User" subtitle="Buat akun administrator baru." />

    @include('admin.users._form', [
        'user' => $user,
        'action' => route('admin.users.store'),
        'method' => 'POST',
        'submitLabel' => 'Simpan User',
        'isSelf' => false,
    ])
@endsection
