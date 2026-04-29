@extends('layouts.public')

@section('title', 'Absensi Pegawai BKM')

@section('content')
    <section class="hero reveal">
        <p class="hero-eyebrow">Absensi Pegawai</p>
        <h1 class="hero-title">Portal Absensi Harian PT BKM</h1>
        <p class="hero-subtitle">Pilih absensi masuk atau absensi keluar, lalu isi data yang diperlukan.</p>
    </section>

    <section class="public-grid">
        <article class="glass-card reveal delay-1">
            <h2 class="section-title">Form Absensi</h2>
            <p class="section-subtitle">Data otomatis tersimpan ke sistem absensi perusahaan.</p>

            <form action="{{ route('public.attendance.store') }}" method="POST" class="mt-5 space-y-4" data-jenis-form>
                @csrf

                <div>
                    <label class="field-label">Pilih Jenis Absensi</label>
                    <div class="attendance-type-switch mt-2">
                        <label class="type-chip">
                            <input type="radio" name="jenis_absensi" value="masuk" @checked(old('jenis_absensi', 'masuk') === 'masuk') data-jenis-trigger>
                            <span>Absen Masuk</span>
                        </label>
                        <label class="type-chip">
                            <input type="radio" name="jenis_absensi" value="keluar" @checked(old('jenis_absensi') === 'keluar') data-jenis-trigger>
                            <span>Absen Keluar</span>
                        </label>
                    </div>
                </div>

                <div>
                    <label class="field-label" for="employee_id">Nama Pegawai</label>
                    <select id="employee_id" name="employee_id" class="field-input" required>
                        <option value="">Pilih pegawai</option>
                        @foreach ($employees as $employee)
                            <option value="{{ $employee->id }}" @selected((int) old('employee_id') === $employee->id)>
                                {{ $employee->nama }} - {{ $employee->posisi }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="field-label" for="tanggal">Tanggal</label>
                    <input id="tanggal" type="date" name="tanggal" class="field-input" value="{{ old('tanggal', now()->toDateString()) }}" required>
                </div>

                <div class="rounded-xl border border-blue-100 bg-blue-50 px-4 py-3 text-sm text-blue-700">
                    Jam absensi tercatat otomatis sesuai waktu server saat tombol simpan ditekan.
                </div>

                <div data-jenis-target="masuk">
                    <label class="field-label" for="status">Status Masuk</label>
                    <select id="status" name="status" class="field-input">
                        @foreach ($checkInStatuses as $status)
                            <option value="{{ $status }}" @selected(old('status', 'hadir') === $status)>{{ ucfirst($status) }}</option>
                        @endforeach
                    </select>
                </div>

                <button type="submit" class="btn-primary w-full">Simpan Absensi</button>
            </form>
        </article>

        <article class="glass-card reveal delay-2">
            <h2 class="section-title">Absensi Terakhir</h2>
            <p class="section-subtitle">Memudahkan pengecekan data terbaru di lapangan.</p>

            <div class="table-shell mt-4">
                <table class="modern-table compact">
                    <thead>
                        <tr>
                            <th>Pegawai</th>
                            <th>Jenis</th>
                            <th>Waktu</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($recentAttendances as $attendance)
                            @php $isCheckIn = $attendance->jenis_absensi === 'masuk'; @endphp
                            <tr>
                                <td>{{ $attendance->employee_nama ?? '-' }}</td>
                                <td>
                                    <span class="badge {{ $isCheckIn ? 'badge-blue' : 'badge-orange' }}">
                                        {{ strtoupper($attendance->jenis_absensi) }}
                                    </span>
                                </td>
                                <td>{{ $attendance->jam ? substr((string) $attendance->jam, 0, 5) : '-' }}</td>
                                <td>{{ $isCheckIn ? ucfirst((string) $attendance->status) : '-' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center text-slate-500">Belum ada data absensi.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </article>
    </section>

    <a href="{{ route('admin.entry') }}" class="admin-fab" title="Masuk ke halaman admin">
        Admin
    </a>
@endsection
