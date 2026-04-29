<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public $withinTransaction = false;

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasTable('attendance_masuk')) {
            Schema::create('attendance_masuk', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
                $table->date('tanggal');
                $table->time('jam_masuk');
                $table->string('status');
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('attendance_keluar')) {
            Schema::create('attendance_keluar', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
                $table->date('tanggal');
                $table->time('jam_keluar');
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('attendances')) {
            return;
        }

        DB::table('attendance_masuk')->delete();
        DB::table('attendance_keluar')->delete();

        $columns = Schema::getColumnListing('attendances');
        $hasJenisAbsensi = in_array('jenis_absensi', $columns, true);

        if ($hasJenisAbsensi) {
            DB::statement(<<<'SQL'
                INSERT INTO attendance_masuk (employee_id, tanggal, jam_masuk, status, created_at, updated_at)
                SELECT
                    employee_id,
                    tanggal,
                    COALESCE(jam_masuk, '08:00:00') AS jam_masuk,
                    CASE
                        WHEN status IS NULL OR TRIM(status) = '' OR LOWER(status) = 'keluar' THEN 'hadir'
                        ELSE LOWER(status)
                    END AS status,
                        COALESCE(created_at, CURRENT_TIMESTAMP) AS created_at,
                        COALESCE(updated_at, CURRENT_TIMESTAMP) AS updated_at
                FROM attendances
                WHERE LOWER(COALESCE(jenis_absensi, '')) = 'masuk' OR jam_masuk IS NOT NULL
            SQL);

            DB::statement(<<<'SQL'
                INSERT INTO attendance_keluar (employee_id, tanggal, jam_keluar, created_at, updated_at)
                SELECT
                    employee_id,
                    tanggal,
                        COALESCE(jam_keluar, '17:00:00') AS jam_keluar,
                        COALESCE(created_at, CURRENT_TIMESTAMP) AS created_at,
                        COALESCE(updated_at, CURRENT_TIMESTAMP) AS updated_at
                FROM attendances
                WHERE LOWER(COALESCE(jenis_absensi, '')) = 'keluar' OR jam_keluar IS NOT NULL
            SQL);

            return;
        }

        DB::statement(<<<'SQL'
            INSERT INTO attendance_masuk (employee_id, tanggal, jam_masuk, status, created_at, updated_at)
            SELECT
                employee_id,
                tanggal,
                COALESCE(jam_masuk, '08:00:00') AS jam_masuk,
                CASE
                    WHEN status IS NULL OR TRIM(status) = '' OR LOWER(status) = 'keluar' THEN 'hadir'
                    ELSE LOWER(status)
                END AS status,
                    COALESCE(created_at, CURRENT_TIMESTAMP) AS created_at,
                    COALESCE(updated_at, CURRENT_TIMESTAMP) AS updated_at
            FROM attendances
            WHERE jam_masuk IS NOT NULL
        SQL);

        DB::statement(<<<'SQL'
            INSERT INTO attendance_keluar (employee_id, tanggal, jam_keluar, created_at, updated_at)
            SELECT
                employee_id,
                tanggal,
                COALESCE(jam_keluar, '17:00:00') AS jam_keluar,
                    COALESCE(created_at, CURRENT_TIMESTAMP) AS created_at,
                    COALESCE(updated_at, CURRENT_TIMESTAMP) AS updated_at
            FROM attendances
            WHERE jam_keluar IS NOT NULL
        SQL);

        // Keep legacy table to avoid long locks on managed PostgreSQL.
        // Application no longer reads/writes this table.
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasTable('attendances')) {
            Schema::create('attendances', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
                $table->date('tanggal');
                $table->string('jenis_absensi', 20)->default('masuk');
                $table->time('jam_masuk')->nullable();
                $table->time('jam_keluar')->nullable();
                $table->string('status')->nullable();
                $table->timestamps();
            });
        }

        if (Schema::hasTable('attendance_masuk')) {
            $masukRows = DB::table('attendance_masuk')->get();

            foreach ($masukRows as $row) {
                DB::table('attendances')->updateOrInsert(
                    [
                        'employee_id' => $row->employee_id,
                        'tanggal' => $row->tanggal,
                        'jenis_absensi' => 'masuk',
                    ],
                    [
                        'jam_masuk' => $row->jam_masuk,
                        'jam_keluar' => null,
                        'status' => $row->status,
                        'created_at' => $row->created_at ?? now(),
                        'updated_at' => $row->updated_at ?? now(),
                    ]
                );
            }
        }

        if (Schema::hasTable('attendance_keluar')) {
            $keluarRows = DB::table('attendance_keluar')->get();

            foreach ($keluarRows as $row) {
                DB::table('attendances')->updateOrInsert(
                    [
                        'employee_id' => $row->employee_id,
                        'tanggal' => $row->tanggal,
                        'jenis_absensi' => 'keluar',
                    ],
                    [
                        'jam_masuk' => null,
                        'jam_keluar' => $row->jam_keluar,
                        'status' => 'keluar',
                        'created_at' => $row->created_at ?? now(),
                        'updated_at' => $row->updated_at ?? now(),
                    ]
                );
            }
        }

        Schema::dropIfExists('attendance_keluar');
        Schema::dropIfExists('attendance_masuk');
    }
};
