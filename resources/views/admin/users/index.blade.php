@extends('layouts.admin')

@section('title', 'Manajemen User')

@section('breadcrumb')
    <x-breadcrumb :items="[['Pengaturan', null], ['Manajemen User', null]]" />
@endsection

@section('content')
    <x-page-header title="Manajemen User" subtitle="Kelola akun administrator. Semua user memiliki hak akses admin.">
        <x-slot:actions>
            <a href="{{ route('admin.users.create') }}" class="btn btn-primary">
                <i class="bi bi-person-plus"></i> Tambah User
            </a>
        </x-slot:actions>
    </x-page-header>

    <section class="card">
        <div class="card-header table-head">
            <div>
                <h2>Daftar User</h2>
                <p>{{ $users->total() }} akun{{ $search !== '' ? ' ditemukan' : '' }}</p>
            </div>

            <form method="GET" action="{{ route('admin.users.index') }}" class="search-form">
                <input type="search" name="q" class="form-control" value="{{ $search }}" placeholder="Cari nama, username, email" aria-label="Cari user">
                <button type="submit" class="btn btn-outline"><i class="bi bi-search"></i></button>
            </form>
        </div>

        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>User</th>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Status</th>
                        <th>Dibuat</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($users as $row)
                        @php $isSelf = $row->is(auth()->user()); @endphp
                        <tr>
                            <td>
                                <div class="item-cell">
                                    <span class="avatar">
                                        @if ($row->profile_photo_url)
                                            <img src="{{ $row->profile_photo_url }}" alt="{{ $row->name }}">
                                        @else
                                            {{ $row->initials }}
                                        @endif
                                    </span>
                                    <div>
                                        <strong>{{ $row->name }}</strong>
                                        @if ($isSelf) <span class="badge badge-called">Anda</span> @endif
                                    </div>
                                </div>
                            </td>
                            <td>{{ $row->username ?: '-' }}</td>
                            <td>{{ $row->email }}</td>
                            <td>
                                @if ($isSelf)
                                    <span class="badge badge-called">Aktif</span>
                                @else
                                    <form method="POST" action="{{ route('admin.users.toggle', $row) }}" class="status-cell">
                                        @csrf
                                        @method('PATCH')
                                        <button
                                            type="submit"
                                            class="switch {{ $row->is_active ? 'on' : '' }}"
                                            role="switch"
                                            aria-checked="{{ $row->is_active ? 'true' : 'false' }}"
                                            aria-label="Aktifkan atau nonaktifkan {{ $row->name }}"
                                        ><span></span></button>
                                        <span>{{ $row->is_active ? 'Aktif' : 'Nonaktif' }}</span>
                                    </form>
                                @endif
                            </td>
                            <td>{{ $row->created_at->translatedFormat('d M Y') }}</td>
                            <td>
                                <div class="row-actions">
                                    <a href="{{ route('admin.users.edit', $row) }}" class="btn btn-outline btn-sm">
                                        <i class="bi bi-pencil"></i> Edit
                                    </a>

                                    <form
                                        method="POST"
                                        action="{{ route('admin.users.destroy', $row) }}"
                                        data-confirm="{{ $isSelf
                                            ? 'Hapus akun Anda sendiri? Anda akan langsung keluar dari sistem.'
                                            : 'Hapus user ' . $row->name . '? Riwayat aktivitasnya tetap tersimpan.' }}"
                                    >
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-danger-outline btn-sm">
                                            <i class="bi bi-trash"></i> Hapus
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="table-empty">Tidak ada user yang cocok.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($users->hasPages())
            <div class="card-footer">{{ $users->links('pagination.admin') }}</div>
        @endif
    </section>
@endsection
