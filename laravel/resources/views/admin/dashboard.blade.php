@extends('layouts.admin')

@section('title', 'Dashboard Admin BKM')
@section('header_title', 'Dashboard')

@section('content')
    @php
        $chartLabels = $attendanceTrend->pluck('label')->values();
        $chartMasuk = $attendanceTrend->pluck('masuk')->values();
        $chartKeluar = $attendanceTrend->pluck('keluar')->values();
        $statusLabels = $statusBreakdown->pluck('status')->map(fn ($item) => ucfirst((string) $item))->values();
        $statusValues = $statusBreakdown->pluck('total')->values();
    @endphp

    <section class="kpi-grid reveal">
        <article class="kpi-card">
            <p class="kpi-label">Total Pegawai</p>
            <p class="kpi-value" data-counter="{{ $employeeCount }}">0</p>
        </article>
        <article class="kpi-card">
            <p class="kpi-label">Absensi Hari Ini</p>
            <p class="kpi-value" data-counter="{{ $attendanceTodayCount }}">0</p>
        </article>
        <article class="kpi-card">
            <p class="kpi-label">Check-In Hari Ini</p>
            <p class="kpi-value" data-counter="{{ $checkInTodayCount }}">0</p>
        </article>
        <article class="kpi-card">
            <p class="kpi-label">Check-Out Hari Ini</p>
            <p class="kpi-value" data-counter="{{ $checkOutTodayCount }}">0</p>
        </article>
        <article class="kpi-card">
            <p class="kpi-label">Belum Check-In</p>
            <p class="kpi-value" data-counter="{{ $notCheckedInCount }}">0</p>
        </article>
        <article class="kpi-card">
            <p class="kpi-label">Terlambat Hari Ini</p>
            <p class="kpi-value" data-counter="{{ $lateTodayCount }}">0</p>
        </article>
        <article class="kpi-card">
            <p class="kpi-label">IP Terblokir Aktif</p>
            <p class="kpi-value" data-counter="{{ $activeBlocksCount }}">0</p>
        </article>
    </section>

    <section class="admin-two-grid mt-6">
        <article class="panel reveal delay-1">
            <div class="panel-header">
                <h2 class="panel-title">Tren Absensi 7 Hari</h2>
                <p class="panel-subtitle">Diagram masuk vs keluar untuk membaca ritme kerja tim.</p>
            </div>
            <div class="chart-box">
                <canvas
                    data-attendance-chart
                    data-labels='@json($chartLabels)'
                    data-checkin='@json($chartMasuk)'
                    data-checkout='@json($chartKeluar)'
                ></canvas>
            </div>
        </article>

        <article class="panel reveal delay-2">
            <div class="panel-header">
                <h2 class="panel-title">Distribusi Status Masuk</h2>
                <p class="panel-subtitle">Komposisi status absensi masuk pegawai.</p>
            </div>
            <div class="chart-box small">
                <canvas
                    data-status-chart
                    data-labels='@json($statusLabels)'
                    data-values='@json($statusValues)'
                ></canvas>
            </div>
        </article>
    </section>

    <section class="admin-two-grid mt-6">
        <article class="panel reveal delay-1">
            <div class="panel-header">
                <h2 class="panel-title">Komposisi Posisi Pegawai</h2>
                <p class="panel-subtitle">Distribusi jabatan untuk memetakan kapasitas tim.</p>
            </div>

            @php
                $maxPosition = max(1, (int) $positionBreakdown->max('total'));
            @endphp

            <div class="stack-list">
                @forelse ($positionBreakdown as $item)
                    @php
                        $width = (int) (($item->total / $maxPosition) * 100);
                    @endphp
                    <div>
                        <div class="flex items-center justify-between text-sm">
                            <p class="font-semibold text-slate-700">{{ $item->posisi }}</p>
                            <p class="text-slate-500">{{ $item->total }} orang</p>
                        </div>
                        <div class="progress-track">
                            <div class="progress-fill" style="width: {{ $width }}%"></div>
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-slate-500">Belum ada data posisi pegawai.</p>
                @endforelse
            </div>
        </article>

        <article class="panel reveal delay-2">
            <div class="panel-header">
                <h2 class="panel-title">Aktivitas Absensi Terbaru</h2>
                <p class="panel-subtitle">Ringkasan aktivitas real-time yang mudah dipantau.</p>
            </div>
            <div class="table-shell mt-4">
                <table class="modern-table">
                    <thead>
                        <tr>
                            <th>Pegawai</th>
                            <th>Tanggal</th>
                            <th>Jenis</th>
                            <th>Jam</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($recentAttendances as $attendance)
                            @php $isCheckIn = $attendance->jenis_absensi === 'masuk'; @endphp
                            <tr>
                                <td>{{ $attendance->employee_nama ?? '-' }}</td>
                                <td>{{ $attendance->tanggal ? \Carbon\Carbon::parse($attendance->tanggal)->translatedFormat('d M Y') : '-' }}</td>
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
                                <td colspan="5" class="text-center text-slate-500">Belum ada data absensi terbaru.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </article>
    </section>
@endsection
