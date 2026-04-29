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
        if (Schema::hasTable('users') && ! Schema::hasTable('admins')) {
            Schema::rename('users', 'admins');
        }

        if (! Schema::hasTable('admins')) {
            Schema::create('admins', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('email')->unique();
                $table->string('password');
                $table->string('remember_token', 100)->nullable();
                $table->timestamps();
            });

            return;
        }

        $columns = Schema::getColumnListing('admins');

        Schema::table('admins', function (Blueprint $table) use ($columns) {
            if (! in_array('name', $columns, true)) {
                $table->string('name');
            }

            if (! in_array('email', $columns, true)) {
                $table->string('email')->unique();
            }

            if (! in_array('password', $columns, true)) {
                $table->string('password');
            }

            if (! in_array('remember_token', $columns, true)) {
                $table->string('remember_token', 100)->nullable();
            }

            if (! in_array('created_at', $columns, true) && ! in_array('updated_at', $columns, true)) {
                $table->timestamps();
            } elseif (! in_array('created_at', $columns, true)) {
                $table->timestamp('created_at')->nullable();
            } elseif (! in_array('updated_at', $columns, true)) {
                $table->timestamp('updated_at')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('admins') && ! Schema::hasTable('users')) {
            Schema::rename('admins', 'users');
        }
    }
};
