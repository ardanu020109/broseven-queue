@extends('layouts.admin')

@section('title', 'Pengaturan Website')

@section('breadcrumb')
    <x-breadcrumb :items="[['Pengaturan', null], ['Pengaturan Website', null]]" />
@endsection

@section('content')
    @php
        $bool = fn (string $key, string $default = '1') => filter_var(old($key, $values[$key] ?? $default), FILTER_VALIDATE_BOOLEAN);
        $text = fn (string $key, string $default = '') => old($key, $values[$key] ?? $default);
        $fileUrl = fn (string $key) => ! empty($values[$key]) ? asset('storage/' . $values[$key]) : null;
    @endphp

    <x-page-header title="Pengaturan Website" subtitle="Identitas, kontak, media sosial, display antrean, dan tiket. Semua tersimpan di database." />

    <section class="card settings-card">
        <div class="tabs" role="tablist">
            @foreach ($tabs as $key => $label)
                <a href="{{ route('admin.settings.website.edit', ['tab' => $key]) }}" class="tab {{ $tab === $key ? 'active' : '' }}">{{ $label }}</a>
            @endforeach
        </div>

        <div class="card-body">
            <form
                method="POST"
                action="{{ route('admin.settings.website.update', $tab) }}"
                enctype="multipart/form-data"
                data-loading
                novalidate
            >
                @csrf
                @method('PUT')

                @include('admin.settings.website._' . $tab)

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary"><i class="bi bi-check2-circle"></i> Simpan Pengaturan {{ $tabs[$tab] }}</button>
                </div>
            </form>
        </div>
    </section>
@endsection
