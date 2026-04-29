<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public $withinTransaction = false;

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('attendances')) {
            Schema::drop('attendances');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Legacy table is intentionally not restored.
    }
};
