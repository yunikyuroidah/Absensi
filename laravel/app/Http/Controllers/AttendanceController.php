<?php

namespace App\Http\Controllers\Api; // Sesuaikan dengan namespace kamu

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\AttendanceMasuk;
use App\Models\AttendanceKeluar;
use Carbon\Carbon;

class AttendanceController extends Controller
{
    public function store(Request $request)
    {
        // Validasi request
        $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'jenis_absen' => 'required|in:masuk,keluar',
            'status'      => 'nullable|in:izin,sakit',
        ]);

        $waktuSekarang = Carbon::now('Asia/Jakarta');
        $tanggal = $waktuSekarang->format('Y-m-d');
        $jam = $waktuSekarang->format('H:i:s');

        try {
            if ($request->jenis_absen === 'masuk') {
                $status = $request->status;

                // Jika status kosong (tidak pilih izin/sakit), tentukan hadir atau terlambat
                if (empty($status)) {
                    $batasWaktu = Carbon::createFromTime(8, 0, 0, 'Asia/Jakarta');
                    if ($waktuSekarang->greaterThan($batasWaktu)) {
                        $status = 'terlambat';
                    } else {
                        $status = 'hadir';
                    }
                }

                // Simpan ke tabel attendance_masuk
                $data = AttendanceMasuk::create([
                    'employee_id' => $request->employee_id,
                    'tanggal'     => $tanggal,
                    'jam_masuk'   => $jam,
                    'status'      => $status,
                ]);

            } else {
                // Simpan ke tabel attendance_keluar
                $data = AttendanceKeluar::create([
                    'employee_id' => $request->employee_id,
                    'tanggal'     => $tanggal,
                    'jam_keluar'  => $jam,
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Absensi ' . $request->jenis_absen . ' berhasil dicatat',
                'data'    => $data
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal menyimpan absensi: ' . $e->getMessage()
            ], 500);
        }
    }
}