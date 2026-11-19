@extends('layouts.admin')

@section('title', 'Tambah Layanan')

@section('breadcrumb')
    <x-breadcrumb :items="[['Master Antrean', null], ['Item Antrean', route('admin.queue-items.index')], ['Tambah', null]]" />
@endsection

@push('scripts')
    @vite('resources/js/queue-item-form.js')
@endpush

@section('content')
    <x-page-header title="Tambah Layanan" subtitle="Buat jenis layanan antrean baru." />

    @include('admin.queue-items._form', [
        'item' => $item,
        'action' => route('admin.queue-items.store'),
        'method' => 'POST',
        'submitLabel' => 'Simpan Layanan',
    ])
@endsection
