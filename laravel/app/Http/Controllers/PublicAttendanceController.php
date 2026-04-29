<?php

namespace App\Http\Controllers;

use App\Models\AttendanceKeluar;
use App\Models\AttendanceMasuk;
use App\Models\Employee;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PublicAttendanceController extends Controller
{
    private const CHECK_IN_STATUSES = [
        'hadir',
        'terlambat',
        'izin',
        'sakit',
    ];

    public function index(): View
    {
        $recentMasuk = AttendanceMasuk::query()
            ->with('employee:id,nama')
            ->latest('tanggal')
            ->latest('id')
            ->limit(8)
            ->get()
            ->map(fn (AttendanceMasuk $item) => (object) [
                'id' => $item->id,
                'employee_nama' => $item->employee?->nama,
                'jenis_absensi' => 'masuk',
                'tanggal' => $item->tanggal?->toDateString(),
                'jam' => $item->jam_masuk,
                'status' => $item->status,
            ]);

        $recentKeluar = AttendanceKeluar::query()
            ->with('employee:id,nama')
            ->latest('tanggal')
            ->latest('id')
            ->limit(8)
            ->get()
            ->map(fn (AttendanceKeluar $item) => (object) [
                'id' => $item->id,
                'employee_nama' => $item->employee?->nama,
                'jenis_absensi' => 'keluar',
                'tanggal' => $item->tanggal?->toDateString(),
                'jam' => $item->jam_keluar,
                'status' => null,
            ]);

        $recentAttendances = $recentMasuk
            ->concat($recentKeluar)
            ->sortByDesc(fn (object $item) => sprintf('%s %s %010d', $item->tanggal, $item->jam, $item->id))
            ->take(8)
            ->values();

        return view('public.attendance', [
            'employees' => Employee::query()->orderBy('nama')->get(['id', 'nama', 'posisi']),
            'checkInStatuses' => self::CHECK_IN_STATUSES,
            'recentAttendances' => $recentAttendances,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'employee_id' => ['required', 'integer', 'exists:employees,id'],
            'tanggal' => ['required', 'date'],
            'jenis_absensi' => ['required', Rule::in(['masuk', 'keluar'])],
            'status' => ['nullable', 'string', Rule::in(self::CHECK_IN_STATUSES)],
        ]);

        $jenis = $validated['jenis_absensi'];
        $currentTime = now()->format('H:i:s');

        $resolvedStatus = strtolower((string) ($validated['status'] ?? 'hadir'));

        if ($jenis === 'masuk') {
            AttendanceMasuk::query()->create([
                'employee_id' => $validated['employee_id'],
                'tanggal' => $validated['tanggal'],
                'jam_masuk' => $currentTime,
                'status' => $resolvedStatus,
            ]);
        }

        if ($jenis === 'keluar') {
            AttendanceKeluar::query()->create([
                'employee_id' => $validated['employee_id'],
                'tanggal' => $validated['tanggal'],
                'jam_keluar' => $currentTime,
            ]);
        }

        return redirect()
            ->route('public.attendance.index')
            ->with('success', 'Absensi '.strtoupper($jenis).' berhasil direkam otomatis pada jam '.substr($currentTime, 0, 5).'.');
    }
}
