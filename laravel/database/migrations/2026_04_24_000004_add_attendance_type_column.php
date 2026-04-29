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
        if (! Schema::hasTable('attendances')) {
            return;
        }

        $columns = Schema::getColumnListing('attendances');

        Schema::table('attendances', function (Blueprint $table) use ($columns) {
            if (! in_array('jenis_absensi', $columns, true)) {
                $table->string('jenis_absensi', 20)->default('masuk')->after('tanggal');
            }
        });

        DB::table('attendances')
            ->whereNull('jenis_absensi')
            ->update(['jenis_absensi' => 'masuk']);

        DB::table('attendances')
            ->whereNull('jam_masuk')
            ->whereNotNull('jam_keluar')
            ->update(['jenis_absensi' => 'keluar']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasTable('attendances')) {
            return;
        }

        if (Schema::hasColumn('attendances', 'jenis_absensi')) {
            Schema::table('attendances', function (Blueprint $table) {
                $table->dropColumn('jenis_absensi');
            });
        }
    }
};
