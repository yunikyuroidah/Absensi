<?php

namespace Database\Seeders;

use App\Models\AttendanceKeluar;
use App\Models\AttendanceMasuk;
use App\Models\Employee;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $now = now();

        if (Schema::hasTable('admins')) {
            $adminSeeds = [
                [
                    'name' => 'Super Admin BKM',
                    'email' => 'admin@bkm.com',
                    'password' => Hash::make('admin12345'),
                ],
                [
                    'name' => 'Admin Operasional',
                    'email' => 'operator@bkm.com',
                    'password' => Hash::make('operator123'),
                ],
            ];

            foreach ($adminSeeds as $seed) {
                DB::table('admins')->updateOrInsert(
                    ['email' => $seed['email']],
                    [
                        'name' => $seed['name'],
                        'password' => $seed['password'],
                        'remember_token' => null,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]
                );
            }
        }

        $employeeSeeds = [
            ['nama' => 'Paul', 'posisi' => 'Operator Lapangan', 'nomer_telepon' => '082010640629'],
            ['nama' => 'Refita', 'posisi' => 'Admin Operasional', 'nomer_telepon' => '083229981316'],
            ['nama' => 'Eka', 'posisi' => 'Mekanik', 'nomer_telepon' => '085132831868'],
            ['nama' => 'Bayu', 'posisi' => 'Driver Dump Truck', 'nomer_telepon' => '086008764714'],
            ['nama' => 'Yunik Yuroidah', 'posisi' => 'Manager Operasional', 'nomer_telepon' => '085309420176'],
            ['nama' => 'Dayana Wolff', 'posisi' => 'Admin Logistik', 'nomer_telepon' => '084426942044'],
            ['nama' => 'Harvey Powlowski', 'posisi' => 'Operator Alat Berat', 'nomer_telepon' => '088662121762'],
            ['nama' => 'Lawson Schmeler', 'posisi' => 'Mekanik', 'nomer_telepon' => '083156267380'],
        ];

        $employees = [];

        if (Schema::hasTable('employees')) {
            foreach ($employeeSeeds as $seed) {
                $employees[] = Employee::query()->updateOrCreate(
                    ['nomer_telepon' => $seed['nomer_telepon']],
                    ['nama' => $seed['nama'], 'posisi' => $seed['posisi']]
                );
            }
        }

        if (Schema::hasTable('attendance_masuk') && Schema::hasTable('attendance_keluar') && $employees !== []) {
            $statusPatterns = ['hadir', 'hadir', 'terlambat', 'hadir', 'izin', 'sakit', 'hadir'];

            foreach ($employees as $index => $employee) {
                foreach (range(0, 6) as $dayOffset) {
                    $date = now()->subDays($dayOffset)->toDateString();
                    $status = $statusPatterns[($dayOffset + $index) % count($statusPatterns)];

                    AttendanceMasuk::query()->updateOrCreate(
                        [
                            'employee_id' => $employee->id,
                            'tanggal' => $date,
                        ],
                        [
                            'jam_masuk' => '08:00',
                            'status' => $status,
                        ]
                    );

                    if (! in_array($status, ['izin', 'sakit'], true)) {
                        AttendanceKeluar::query()->updateOrCreate(
                            [
                                'employee_id' => $employee->id,
                                'tanggal' => $date,
                            ],
                            [
                                'jam_keluar' => '17:00',
                            ]
                        );
                    }
                }
            }
        }

        if (Schema::hasTable('login_attempts_api')) {
            DB::table('login_attempts_api')->updateOrInsert(
                ['ip_address' => '127.0.0.1'],
                [
                    'attempts' => 0,
                    'blocked_until' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );
        }
    }
}
