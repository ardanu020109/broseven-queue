@extends('layouts.admin')

@section('title', 'Edit Layanan')

@section('breadcrumb')
    <x-breadcrumb :items="[['Master Antrean', null], ['Item Antrean', route('admin.queue-items.index')], ['Edit', null]]" />
@endsection

@push('scripts')
    @vite('resources/js/queue-item-form.js')
@endpush

@section('content')
    <x-page-header :title="'Edit ' . $item->name" subtitle="Perbarui pengaturan layanan antrean." />

    <x-alert type="warning" :dismissible="false">
        Perubahan prefix, format nomor, jumlah digit, atau periode reset hanya berlaku untuk antrean
        <strong>baru</strong>. Nomor yang sudah terbit tidak berubah.
    </x-alert>

    @include('admin.queue-items._form', [
        'item' => $item,
        'action' => route('admin.queue-items.update', $item),
        'method' => 'PUT',
        'submitLabel' => 'Simpan Perubahan',
    ])
@endsection
