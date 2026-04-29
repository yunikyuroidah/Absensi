<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AttendanceKeluar;
use App\Models\AttendanceMasuk;
use App\Models\Employee;
use App\Models\LoginAttemptApi;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class MobileAdminController extends Controller
{
    private const CHECK_IN_STATUSES = [
        'hadir',
        'terlambat',
        'izin',
        'sakit',
    ];

    public function dashboard(): JsonResponse
    {
        $today = now()->toDateString();

        $checkInTodayCount = AttendanceMasuk::query()
            ->whereDate('tanggal', $today)
            ->count();

        $checkOutTodayCount = AttendanceKeluar::query()
            ->whereDate('tanggal', $today)
            ->count();

        $attendanceTodayCount = $checkInTodayCount + $checkOutTodayCount;

        $lateTodayCount = AttendanceMasuk::query()
            ->whereDate('tanggal', $today)
            ->where('status', 'terlambat')
            ->count();

        $activeBlocksCount = LoginAttemptApi::query()
            ->whereNotNull('blocked_until')
            ->where('blocked_until', '>', now())
            ->count();

        $positionBreakdown = Employee::query()
            ->select('posisi', DB::raw('COUNT(*) as total'))
            ->groupBy('posisi')
            ->orderByDesc('total')
            ->limit(6)
            ->get();

        $recentMasuk = AttendanceMasuk::query()
            ->with('employee:id,nama')
            ->latest('tanggal')
            ->latest('id')
            ->limit(10)
            ->get()
            ->map(fn (AttendanceMasuk $item) => [
                'id' => $item->id,
                'employee_nama' => $item->employee?->nama,
                'tanggal' => $item->tanggal?->toDateString(),
                'jenis_absensi' => 'masuk',
                'jam' => $item->jam_masuk,
                'status' => $item->status,
                'sort_at' => sprintf('%s %s %010d', $item->tanggal?->toDateString(), $item->jam_masuk, $item->id),
            ]);

        $recentKeluar = AttendanceKeluar::query()
            ->with('employee:id,nama')
            ->latest('tanggal')
            ->latest('id')
            ->limit(10)
            ->get()
            ->map(fn (AttendanceKeluar $item) => [
                'id' => $item->id,
                'employee_nama' => $item->employee?->nama,
                'tanggal' => $item->tanggal?->toDateString(),
                'jenis_absensi' => 'keluar',
                'jam' => $item->jam_keluar,
                'status' => null,
                'sort_at' => sprintf('%s %s %010d', $item->tanggal?->toDateString(), $item->jam_keluar, $item->id),
            ]);

        $recentAttendances = $recentMasuk
            ->concat($recentKeluar)
            ->sortByDesc('sort_at')
            ->take(10)
            ->values()
            ->map(fn (array $item) => [
                'id' => $item['id'],
                'employee_nama' => $item['employee_nama'],
                'tanggal' => $item['tanggal'],
                'jenis_absensi' => $item['jenis_absensi'],
                'jam' => $item['jam'],
                'status' => $item['status'],
            ]);

        $dateBuckets = collect(range(6, 0))
            ->mapWithKeys(fn (int $offset) => [now()->subDays($offset)->toDateString() => ['masuk' => 0, 'keluar' => 0]]);

        $normalizeDateKey = static function (mixed $date): string {
            return $date instanceof CarbonInterface
                ? $date->toDateString()
                : (string) $date;
        };

        $masukTrendRaw = AttendanceMasuk::query()
            ->selectRaw('tanggal, COUNT(*) as total')
            ->whereDate('tanggal', '>=', now()->subDays(6)->toDateString())
            ->groupBy('tanggal')
            ->orderBy('tanggal')
            ->get();

        $masukTrendRaw->each(function ($row) use ($dateBuckets, $normalizeDateKey): void {
            $dateKey = $normalizeDateKey($row->tanggal);
            $bucket = $dateBuckets->get($dateKey);

            if (! is_array($bucket)) {
                return;
            }

            $bucket['masuk'] = (int) $row->total;
            $dateBuckets->put($dateKey, $bucket);
        });

        $keluarTrendRaw = AttendanceKeluar::query()
            ->selectRaw('tanggal, COUNT(*) as total')
            ->whereDate('tanggal', '>=', now()->subDays(6)->toDateString())
            ->groupBy('tanggal')
            ->orderBy('tanggal')
            ->get();

        $keluarTrendRaw->each(function ($row) use ($dateBuckets, $normalizeDateKey): void {
            $dateKey = $normalizeDateKey($row->tanggal);
            $bucket = $dateBuckets->get($dateKey);

            if (! is_array($bucket)) {
                return;
            }

            $bucket['keluar'] = (int) $row->total;
            $dateBuckets->put($dateKey, $bucket);
        });

        $attendanceTrend = $dateBuckets
            ->map(fn (array $totals, string $date) => [
                'label' => Carbon::parse($date)->translatedFormat('d M'),
                'masuk' => $totals['masuk'],
                'keluar' => $totals['keluar'],
            ])
            ->values();

        $statusBreakdown = AttendanceMasuk::query()
            ->select('status', DB::raw('COUNT(*) as total'))
            ->groupBy('status')
            ->orderByDesc('total')
            ->get();

        $employeePresentToday = AttendanceMasuk::query()
            ->whereDate('tanggal', $today)
            ->distinct('employee_id')
            ->count('employee_id');

        $employeeCount = Employee::count();

        return response()->json([
            'metrics' => [
                'employee_count' => $employeeCount,
                'attendance_today_count' => $attendanceTodayCount,
                'check_in_today_count' => $checkInTodayCount,
                'check_out_today_count' => $checkOutTodayCount,
                'not_checked_in_count' => max(0, $employeeCount - $employeePresentToday),
                'late_today_count' => $lateTodayCount,
                'active_blocks_count' => $activeBlocksCount,
            ],
            'position_breakdown' => $positionBreakdown,
            'recent_attendances' => $recentAttendances,
            'attendance_trend' => $attendanceTrend,
            'status_breakdown' => $statusBreakdown,
        ]);
    }

    public function indexEmployees(Request $request): JsonResponse
    {
        $q = trim((string) $request->query('q', ''));
        $perPage = min(max((int) $request->query('per_page', 20), 1), 100);

        $employees = Employee::query()
            ->when($q !== '', function ($query) use ($q): void {
                $query->where(function ($filter) use ($q): void {
                    $filter->where('nama', 'like', "%{$q}%")
                        ->orWhere('posisi', 'like', "%{$q}%")
                        ->orWhere('nomer_telepon', 'like', "%{$q}%");
                });
            })
            ->latest()
            ->paginate($perPage)
            ->withQueryString();

        return response()->json($this->paginateResponse($employees));
    }

    public function storeEmployee(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'nama' => ['required', 'string', 'max:120'],
            'posisi' => ['required', 'string', 'max:120'],
            'nomer_telepon' => ['nullable', 'string', 'max:30'],
        ]);

        $employee = Employee::query()->create($validated);

        return response()->json([
            'message' => 'Data pegawai berhasil ditambahkan.',
            'data' => $employee,
        ], 201);
    }

    public function updateEmployee(Request $request, Employee $employee): JsonResponse
    {
        $validated = $request->validate([
            'nama' => ['required', 'string', 'max:120'],
            'posisi' => ['required', 'string', 'max:120'],
            'nomer_telepon' => ['nullable', 'string', 'max:30'],
        ]);

        $employee->update($validated);

        return response()->json([
            'message' => 'Data pegawai berhasil diperbarui.',
            'data' => $employee->fresh(),
        ]);
    }

    public function destroyEmployee(Employee $employee): JsonResponse
    {
        $employee->delete();

        return response()->json([
            'message' => 'Data pegawai berhasil dihapus.',
        ]);
    }

    public function indexAttendances(Request $request): JsonResponse
    {
        $q = trim((string) $request->query('q', ''));
        $status = trim((string) $request->query('status', ''));
        $date = trim((string) $request->query('date', ''));
        $jenis = trim((string) $request->query('jenis', ''));
        $perPage = min(max((int) $request->query('per_page', 20), 1), 100);

        $includeMasuk = $jenis === '' || $jenis === 'masuk';
        $includeKeluar = ($jenis === '' || $jenis === 'keluar') && $status === '';

        $masukQuery = DB::table('attendance_masuk as am')
            ->join('employees as e', 'e.id', '=', 'am.employee_id')
            ->selectRaw("am.id, am.employee_id, e.nama as employee_nama, am.tanggal, 'masuk' as jenis_absensi, am.jam_masuk as jam, am.status, am.created_at")
            ->when($q !== '', function ($query) use ($q): void {
                $query->where(function ($filter) use ($q): void {
                    $filter->where('e.nama', 'like', "%{$q}%")
                        ->orWhere('am.status', 'like', "%{$q}%");
                });
            })
            ->when($status !== '', fn ($query) => $query->where('am.status', $status))
            ->when($date !== '', fn ($query) => $query->whereDate('am.tanggal', $date));

        $keluarQuery = DB::table('attendance_keluar as ak')
            ->join('employees as e', 'e.id', '=', 'ak.employee_id')
            ->selectRaw("ak.id, ak.employee_id, e.nama as employee_nama, ak.tanggal, 'keluar' as jenis_absensi, ak.jam_keluar as jam, NULL as status, ak.created_at")
            ->when($q !== '', fn ($query) => $query->where('e.nama', 'like', "%{$q}%"))
            ->when($date !== '', fn ($query) => $query->whereDate('ak.tanggal', $date));

        $attendances = $this->paginateAttendanceRows(
            request: $request,
            includeMasuk: $includeMasuk,
            includeKeluar: $includeKeluar,
            masukQuery: $masukQuery,
            keluarQuery: $keluarQuery,
            perPage: $perPage,
        );

        $attendances->setCollection(
            $attendances->getCollection()->map(fn ($item) => [
                'id' => (int) $item->id,
                'employee_id' => (int) $item->employee_id,
                'employee_nama' => $item->employee_nama,
                'tanggal' => (string) $item->tanggal,
                'jenis_absensi' => (string) $item->jenis_absensi,
                'jam' => $item->jam,
                'status' => $item->status,
                'created_at' => $item->created_at,
            ])
        );

        return response()->json($this->paginateResponse($attendances));
    }

    public function storeAttendance(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'employee_id' => ['required', 'integer', 'exists:employees,id'],
            'tanggal' => ['required', 'date'],
            'jenis_absensi' => ['required', Rule::in(['masuk', 'keluar'])],
            'jam_masuk' => ['nullable', 'date_format:H:i'],
            'jam_keluar' => ['nullable', 'date_format:H:i'],
            'status' => ['nullable', 'string', Rule::in(self::CHECK_IN_STATUSES)],
        ]);

        if ($validated['jenis_absensi'] === 'masuk') {
            $attendance = AttendanceMasuk::query()->create([
                'employee_id' => $validated['employee_id'],
                'tanggal' => $validated['tanggal'],
                'jam_masuk' => $validated['jam_masuk'] ?? now()->format('H:i:s'),
                'status' => strtolower((string) ($validated['status'] ?? 'hadir')),
            ]);

            return response()->json([
                'message' => 'Data absensi masuk berhasil ditambahkan.',
                'data' => [
                    'jenis_absensi' => 'masuk',
                    'record_id' => $attendance->id,
                ],
            ], 201);
        }

        $attendance = AttendanceKeluar::query()->create([
            'employee_id' => $validated['employee_id'],
            'tanggal' => $validated['tanggal'],
            'jam_keluar' => $validated['jam_keluar'] ?? now()->format('H:i:s'),
        ]);

        return response()->json([
            'message' => 'Data absensi keluar berhasil ditambahkan.',
            'data' => [
                'jenis_absensi' => 'keluar',
                'record_id' => $attendance->id,
            ],
        ], 201);
    }

    public function updateAttendance(Request $request, string $jenis, int $record): JsonResponse
    {
        if ($jenis === 'masuk') {
            $attendance = AttendanceMasuk::query()->findOrFail($record);

            $validated = $request->validate([
                'employee_id' => ['required', 'integer', 'exists:employees,id'],
                'tanggal' => ['required', 'date'],
                'jam_masuk' => ['nullable', 'date_format:H:i'],
                'status' => ['required', 'string', Rule::in(self::CHECK_IN_STATUSES)],
            ]);

            $attendance->update([
                'employee_id' => $validated['employee_id'],
                'tanggal' => $validated['tanggal'],
                'jam_masuk' => $validated['jam_masuk'] ?? $attendance->jam_masuk ?? now()->format('H:i:s'),
                'status' => strtolower((string) $validated['status']),
            ]);

            return response()->json([
                'message' => 'Data absensi masuk berhasil diperbarui.',
            ]);
        }

        if ($jenis === 'keluar') {
            $attendance = AttendanceKeluar::query()->findOrFail($record);

            $validated = $request->validate([
                'employee_id' => ['required', 'integer', 'exists:employees,id'],
                'tanggal' => ['required', 'date'],
                'jam_keluar' => ['nullable', 'date_format:H:i'],
            ]);

            $attendance->update([
                'employee_id' => $validated['employee_id'],
                'tanggal' => $validated['tanggal'],
                'jam_keluar' => $validated['jam_keluar'] ?? $attendance->jam_keluar ?? now()->format('H:i:s'),
            ]);

            return response()->json([
                'message' => 'Data absensi keluar berhasil diperbarui.',
            ]);
        }

        return response()->json([
            'message' => 'Jenis absensi tidak valid.',
        ], 422);
    }

    public function destroyAttendance(string $jenis, int $record): JsonResponse
    {
        if ($jenis === 'masuk') {
            AttendanceMasuk::query()->findOrFail($record)->delete();

            return response()->json([
                'message' => 'Data absensi masuk berhasil dihapus.',
            ]);
        }

        if ($jenis === 'keluar') {
            AttendanceKeluar::query()->findOrFail($record)->delete();

            return response()->json([
                'message' => 'Data absensi keluar berhasil dihapus.',
            ]);
        }

        return response()->json([
            'message' => 'Jenis absensi tidak valid.',
        ], 422);
    }

    public function indexLoginAttempts(Request $request): JsonResponse
    {
        $q = trim((string) $request->query('q', ''));
        $perPage = min(max((int) $request->query('per_page', 20), 1), 100);

        $attempts = LoginAttemptApi::query()
            ->when($q !== '', fn ($query) => $query->where('ip_address', 'like', "%{$q}%"))
            ->latest()
            ->paginate($perPage)
            ->withQueryString();

        $attempts->setCollection(
            $attempts->getCollection()->map(fn (LoginAttemptApi $item) => [
                'id' => $item->id,
                'ip_address' => $item->ip_address,
                'attempts' => $item->attempts,
                'blocked_until' => $item->blocked_until?->toIso8601String(),
                'is_blocked' => (bool) ($item->blocked_until && $item->blocked_until->isFuture()),
                'updated_at' => $item->updated_at?->toIso8601String(),
            ])
        );

        return response()->json($this->paginateResponse($attempts));
    }

    private function paginateAttendanceRows(
        Request $request,
        bool $includeMasuk,
        bool $includeKeluar,
        mixed $masukQuery,
        mixed $keluarQuery,
        int $perPage,
    ): LengthAwarePaginator {
        if (! $includeMasuk && ! $includeKeluar) {
            return new LengthAwarePaginator(
                items: [],
                total: 0,
                perPage: $perPage,
                currentPage: $request->integer('page', 1),
                options: [
                    'path' => $request->url(),
                    'query' => $request->query(),
                ],
            );
        }

        if ($includeMasuk && ! $includeKeluar) {
            return DB::query()
                ->fromSub($masukQuery, 'attendance_rows')
                ->orderByDesc('tanggal')
                ->orderByDesc('created_at')
                ->paginate($perPage)
                ->withQueryString();
        }

        if (! $includeMasuk && $includeKeluar) {
            return DB::query()
                ->fromSub($keluarQuery, 'attendance_rows')
                ->orderByDesc('tanggal')
                ->orderByDesc('created_at')
                ->paginate($perPage)
                ->withQueryString();
        }

        return DB::query()
            ->fromSub($masukQuery->unionAll($keluarQuery), 'attendance_rows')
            ->orderByDesc('tanggal')
            ->orderByDesc('created_at')
            ->paginate($perPage)
            ->withQueryString();
    }

    private function paginateResponse(LengthAwarePaginator $paginator): array
    {
        return [
            'data' => $paginator->items(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ];
    }
}
