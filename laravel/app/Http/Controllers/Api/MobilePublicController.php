<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AttendanceKeluar;
use App\Models\AttendanceMasuk;
use App\Models\Employee;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;

class MobilePublicController extends Controller
{
    private const CHECK_IN_STATUSES = [
        'hadir',
        'terlambat',
        'izin',
        'sakit',
    ];

    public function bootstrap(): JsonResponse
    {
        return response()->json([
            'employees' => Employee::query()
                ->orderBy('nama')
                ->get(['id', 'nama', 'posisi']),
            'check_in_statuses' => self::CHECK_IN_STATUSES,
            'recent_attendances' => $this->recentAttendances(),
        ]);
    }

    public function storeAttendance(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'employee_id' => ['required', 'integer', 'exists:employees,id'],
            'tanggal' => ['required', 'date'],
            'jenis_absensi' => ['required', Rule::in(['masuk', 'keluar'])],
            'status' => ['nullable', 'string', Rule::in(self::CHECK_IN_STATUSES)],
        ]);

        $jenis = $validated['jenis_absensi'];
        $currentTime = now()->format('H:i:s');

        if ($jenis === 'masuk') {
            AttendanceMasuk::query()->create([
                'employee_id' => $validated['employee_id'],
                'tanggal' => $validated['tanggal'],
                'jam_masuk' => $currentTime,
                'status' => strtolower((string) ($validated['status'] ?? 'hadir')),
            ]);
        }

        if ($jenis === 'keluar') {
            AttendanceKeluar::query()->create([
                'employee_id' => $validated['employee_id'],
                'tanggal' => $validated['tanggal'],
                'jam_keluar' => $currentTime,
            ]);
        }

        return response()->json([
            'message' => 'Absensi '.strtoupper($jenis).' berhasil direkam pada jam '.substr($currentTime, 0, 5).'.',
            'recent_attendances' => $this->recentAttendances(),
        ], 201);
    }

    private function recentAttendances(int $limit = 8): Collection
    {
        $recentMasuk = AttendanceMasuk::query()
            ->with('employee:id,nama')
            ->latest('tanggal')
            ->latest('id')
            ->limit($limit)
            ->get()
            ->map(fn (AttendanceMasuk $item) => [
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
            ->limit($limit)
            ->get()
            ->map(fn (AttendanceKeluar $item) => [
                'id' => $item->id,
                'employee_nama' => $item->employee?->nama,
                'jenis_absensi' => 'keluar',
                'tanggal' => $item->tanggal?->toDateString(),
                'jam' => $item->jam_keluar,
                'status' => null,
            ]);

        return $recentMasuk
            ->concat($recentKeluar)
            ->sortByDesc(fn (array $item) => sprintf('%s %s %010d', $item['tanggal'], $item['jam'], $item['id']))
            ->take($limit)
            ->values();
    }
}
