@extends('layouts.admin')

@section('title', 'Manajemen Pegawai')
@section('header_title', 'Pegawai')

@section('content')
    <section class="panel reveal">
        <div class="panel-toolbar">
            <div>
                <h2 class="panel-title">Data Pegawai</h2>
                <p class="panel-subtitle">Kelola data pegawai dengan pencarian cepat dan aksi CRUD.</p>
            </div>
            <button type="button" class="btn-primary" data-open-modal="add-employee-modal">+ Tambah Pegawai</button>
        </div>

        <form action="{{ route('admin.employees.index') }}" method="GET" class="search-grid mt-4">
            <input type="text" name="q" value="{{ $q }}" class="field-input" placeholder="Cari nama, posisi, atau nomor telepon">
            <button type="submit" class="btn-primary">Cari</button>
            <a href="{{ route('admin.employees.index') }}" class="btn-ghost text-center">Reset</a>
        </form>

        <div class="table-shell mt-5">
            <table class="modern-table">
                <thead>
                    <tr>
                        <th>Nama</th>
                        <th>Posisi</th>
                        <th>No. Telepon</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($employees as $employee)
                        <tr>
                            <td>{{ $employee->nama }}</td>
                            <td>{{ $employee->posisi }}</td>
                            <td>{{ $employee->nomer_telepon ?: '-' }}</td>
                            <td class="action-row">
                                <button type="button" class="btn-ghost" data-open-modal="edit-employee-{{ $employee->id }}">Edit</button>
                                <form
                                    action="{{ route('admin.employees.destroy', $employee) }}"
                                    method="POST"
                                    data-confirm-delete="Hapus data pegawai {{ $employee->nama }}?"
                                >
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn-danger">Hapus</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center text-slate-500">Belum ada data pegawai.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="pagination-wrap mt-5">{{ $employees->links() }}</div>
    </section>

    <div class="modal-overlay" id="add-employee-modal" data-modal>
        <div class="modal-card">
            <h3 class="modal-title">Tambah Pegawai</h3>
            <form action="{{ route('admin.employees.store') }}" method="POST" class="space-y-3 mt-4">
                @csrf
                <div>
                    <label class="field-label" for="add-nama">Nama</label>
                    <input id="add-nama" type="text" name="nama" class="field-input" required>
                </div>
                <div>
                    <label class="field-label" for="add-posisi">Posisi</label>
                    <input id="add-posisi" type="text" name="posisi" class="field-input" required>
                </div>
                <div>
                    <label class="field-label" for="add-phone">No. Telepon</label>
                    <input id="add-phone" type="text" name="nomer_telepon" class="field-input">
                </div>
                <div class="modal-actions">
                    <button type="button" class="btn-ghost" data-close-modal>Batal</button>
                    <button type="submit" class="btn-primary">Simpan</button>
                </div>
            </form>
        </div>
    </div>

    @foreach ($employees as $employee)
        <div class="modal-overlay" id="edit-employee-{{ $employee->id }}" data-modal>
            <div class="modal-card">
                <h3 class="modal-title">Edit Pegawai</h3>
                <form action="{{ route('admin.employees.update', $employee) }}" method="POST" class="space-y-3 mt-4">
                    @csrf
                    @method('PUT')
                    <div>
                        <label class="field-label" for="edit-name-{{ $employee->id }}">Nama</label>
                        <input id="edit-name-{{ $employee->id }}" type="text" name="nama" class="field-input" value="{{ $employee->nama }}" required>
                    </div>
                    <div>
                        <label class="field-label" for="edit-position-{{ $employee->id }}">Posisi</label>
                        <input id="edit-position-{{ $employee->id }}" type="text" name="posisi" class="field-input" value="{{ $employee->posisi }}" required>
                    </div>
                    <div>
                        <label class="field-label" for="edit-phone-{{ $employee->id }}">No. Telepon</label>
                        <input id="edit-phone-{{ $employee->id }}" type="text" name="nomer_telepon" class="field-input" value="{{ $employee->nomer_telepon }}">
                    </div>
                    <div class="modal-actions">
                        <button type="button" class="btn-ghost" data-close-modal>Batal</button>
                        <button type="submit" class="btn-primary">Update</button>
                    </div>
                </form>
            </div>
        </div>
    @endforeach
@endsection
