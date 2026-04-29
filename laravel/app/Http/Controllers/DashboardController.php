<?php

namespace App\Http\Controllers;

use App\Models\AttendanceKeluar;
use App\Models\AttendanceMasuk;
use App\Models\Employee;
use App\Models\LoginAttemptApi;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function __invoke()
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
            ->map(fn (AttendanceMasuk $item) => (object) [
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
            ->map(fn (AttendanceKeluar $item) => (object) [
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
            ->values();

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
                'label' => \Carbon\Carbon::parse($date)->translatedFormat('d M'),
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
        $notCheckedInCount = max(0, $employeeCount - $employeePresentToday);

        return view('admin.dashboard', [
            'employeeCount' => $employeeCount,
            'attendanceTodayCount' => $attendanceTodayCount,
            'checkInTodayCount' => $checkInTodayCount,
            'checkOutTodayCount' => $checkOutTodayCount,
            'notCheckedInCount' => $notCheckedInCount,
            'lateTodayCount' => $lateTodayCount,
            'activeBlocksCount' => $activeBlocksCount,
            'positionBreakdown' => $positionBreakdown,
            'recentAttendances' => $recentAttendances,
            'attendanceTrend' => $attendanceTrend,
            'statusBreakdown' => $statusBreakdown,
        ]);
    }
}
