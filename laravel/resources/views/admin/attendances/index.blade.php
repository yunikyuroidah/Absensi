@extends('layouts.admin')

@section('title', 'Manajemen Absensi')
@section('header_title', 'Absensi')

@section('content')
    <section class="panel reveal">
        <div class="panel-toolbar">
            <div>
                <h2 class="panel-title">Data Absensi Pegawai</h2>
                <p class="panel-subtitle">List absensi tampil lebih dulu. Form tambah/edit muncul saat Anda klik tombol aksi.</p>
            </div>
            <button type="button" class="btn-primary" data-open-modal="add-attendance-modal">+ Tambah Absensi</button>
        </div>

        <form action="{{ route('admin.attendances.index') }}" method="GET" class="search-grid-4 mt-4">
            <input type="text" name="q" value="{{ $q }}" class="field-input" placeholder="Cari nama, jenis, status">
            <select name="jenis" class="field-input">
                <option value="">Semua Jenis</option>
                <option value="masuk" @selected($jenis === 'masuk')>Masuk</option>
                <option value="keluar" @selected($jenis === 'keluar')>Keluar</option>
            </select>
            <select name="status" class="field-input">
                <option value="">Semua Status</option>
                @foreach ($statuses as $statusItem)
                    <option value="{{ $statusItem }}" @selected($status === $statusItem)>{{ ucfirst($statusItem) }}</option>
                @endforeach
            </select>
            <input type="date" name="date" value="{{ $date }}" class="field-input">
            <button type="submit" class="btn-primary">Filter</button>
            <a href="{{ route('admin.attendances.index') }}" class="btn-ghost text-center">Reset</a>
        </form>

        <div class="table-shell mt-5">
            <table class="modern-table">
                <thead>
                    <tr>
                        <th>Pegawai</th>
                        <th>Tanggal</th>
                        <th>Jenis</th>
                        <th>Jam</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($attendances as $attendance)
                        @php $isCheckIn = $attendance->jenis_absensi === 'masuk'; @endphp
                        <tr>
                            <td>{{ $attendance->employee_nama ?? '-' }}</td>
                            <td>{{ $attendance->tanggal ? \Carbon\Carbon::parse($attendance->tanggal)->translatedFormat('d M Y') : '-' }}</td>
                            <td>
                                <span class="badge {{ $isCheckIn ? 'badge-blue' : 'badge-orange' }}">{{ strtoupper($attendance->jenis_absensi) }}</span>
                            </td>
                            <td>{{ $attendance->jam ? substr((string) $attendance->jam, 0, 5) : '-' }}</td>
                            <td>{{ $isCheckIn ? ucfirst((string) $attendance->status) : '-' }}</td>
                            <td class="action-row">
                                <button type="button" class="btn-ghost" data-open-modal="edit-attendance-{{ $attendance->jenis_absensi }}-{{ $attendance->id }}">Edit</button>
                                <form
                                    action="{{ route('admin.attendances.destroy', ['jenis' => $attendance->jenis_absensi, 'record' => $attendance->id]) }}"
                                    method="POST"
                                    data-confirm-delete="Hapus data absensi {{ $attendance->employee_nama ?? 'pegawai' }} pada {{ $attendance->tanggal ? \Carbon\Carbon::parse($attendance->tanggal)->translatedFormat('d M Y') : '-' }}?"
                                >
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn-danger">Hapus</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-slate-500">Belum ada data absensi.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="pagination-wrap mt-5">{{ $attendances->links() }}</div>
    </section>

    <div class="modal-overlay" id="add-attendance-modal" data-modal>
        <div class="modal-card">
            <h3 class="modal-title">Tambah Absensi</h3>
            <form action="{{ route('admin.attendances.store') }}" method="POST" class="space-y-3 mt-4" data-jenis-form>
                @csrf
                <div>
                    <label class="field-label">Pegawai</label>
                    <select name="employee_id" class="field-input" required>
                        <option value="">Pilih Pegawai</option>
                        @foreach ($employees as $employee)
                            <option value="{{ $employee->id }}">{{ $employee->nama }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="field-label">Tanggal</label>
                    <input type="date" name="tanggal" class="field-input" value="{{ now()->toDateString() }}" required>
                </div>
                <div>
                    <label class="field-label">Jenis Absensi</label>
                    <select name="jenis_absensi" class="field-input" data-jenis-switch>
                        <option value="masuk">Masuk</option>
                        <option value="keluar">Keluar</option>
                    </select>
                </div>

                <div data-jenis-target="masuk">
                    <label class="field-label">Jam Masuk</label>
                    <input type="time" name="jam_masuk" class="field-input" value="{{ now()->format('H:i') }}">
                </div>
                <div data-jenis-target="masuk">
                    <label class="field-label">Status</label>
                    <select name="status" class="field-input">
                        <option value="">Pilih Status</option>
                        @foreach ($statuses as $statusItem)
                            <option value="{{ $statusItem }}">{{ ucfirst($statusItem) }}</option>
                        @endforeach
                    </select>
                </div>

                <div data-jenis-target="keluar">
                    <label class="field-label">Jam Keluar</label>
                    <input type="time" name="jam_keluar" class="field-input" value="{{ now()->format('H:i') }}">
                </div>

                <div class="modal-actions">
                    <button type="button" class="btn-ghost" data-close-modal>Batal</button>
                    <button type="submit" class="btn-primary">Simpan</button>
                </div>
            </form>
        </div>
    </div>

    @foreach ($attendances as $attendance)
        <div class="modal-overlay" id="edit-attendance-{{ $attendance->jenis_absensi }}-{{ $attendance->id }}" data-modal>
            <div class="modal-card">
                <h3 class="modal-title">Edit Absensi {{ strtoupper($attendance->jenis_absensi) }}</h3>
                <form
                    action="{{ route('admin.attendances.update', ['jenis' => $attendance->jenis_absensi, 'record' => $attendance->id]) }}"
                    method="POST"
                    class="space-y-3 mt-4"
                >
                    @csrf
                    @method('PUT')

                    <div>
                        <label class="field-label">Pegawai</label>
                        <select name="employee_id" class="field-input" required>
                            @foreach ($employees as $employee)
                                <option value="{{ $employee->id }}" @selected((int) $attendance->employee_id === $employee->id)>
                                    {{ $employee->nama }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="field-label">Tanggal</label>
                        <input
                            type="date"
                            name="tanggal"
                            class="field-input"
                            value="{{ $attendance->tanggal ? \Carbon\Carbon::parse($attendance->tanggal)->toDateString() : now()->toDateString() }}"
                            required
                        >
                    </div>

                    @if ($attendance->jenis_absensi === 'masuk')
                        <div>
                            <label class="field-label">Jam Masuk</label>
                            <input type="time" name="jam_masuk" class="field-input" value="{{ $attendance->jam ? substr((string) $attendance->jam, 0, 5) : '' }}">
                        </div>
                        <div>
                            <label class="field-label">Status</label>
                            <select name="status" class="field-input" required>
                                @foreach ($statuses as $statusItem)
                                    <option value="{{ $statusItem }}" @selected((string) $attendance->status === $statusItem)>
                                        {{ ucfirst($statusItem) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    @else
                        <div>
                            <label class="field-label">Jam Keluar</label>
                            <input type="time" name="jam_keluar" class="field-input" value="{{ $attendance->jam ? substr((string) $attendance->jam, 0, 5) : '' }}">
                        </div>
                    @endif

                    <div class="modal-actions">
                        <button type="button" class="btn-ghost" data-close-modal>Batal</button>
                        <button type="submit" class="btn-primary">Update</button>
                    </div>
                </form>
            </div>
        </div>
    @endforeach
@endsection
