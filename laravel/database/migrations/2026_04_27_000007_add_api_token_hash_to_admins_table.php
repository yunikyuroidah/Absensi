<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public $withinTransaction = false;

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasTable('admins')) {
            return;
        }

        if (Schema::hasColumn('admins', 'api_token_hash')) {
            return;
        }

        Schema::table('admins', function (Blueprint $table) {
            $table->string('api_token_hash', 64)->nullable()->index()->after('password');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasTable('admins') || ! Schema::hasColumn('admins', 'api_token_hash')) {
            return;
        }

        Schema::table('admins', function (Blueprint $table) {
            $table->dropColumn('api_token_hash');
        });
    }
};
