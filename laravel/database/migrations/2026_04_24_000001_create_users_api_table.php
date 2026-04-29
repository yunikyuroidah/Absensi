<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\QueryException;
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
        $tableExists = Schema::hasTable('users_api');

        if (! $tableExists) {
            try {
                Schema::create('users_api', function (Blueprint $table) {
                    $table->id();
                    $table->string('name');
                    $table->string('email')->unique();
                    $table->timestamp('email_verified_at')->nullable();
                    $table->string('password');
                    $table->string('role')->default('admin');
                    $table->string('remember_token', 100)->nullable();
                    $table->boolean('is_admin')->default(false);
                    $table->timestamps();
                });

                return;
            } catch (QueryException $exception) {
                $sqlState = $exception->errorInfo[0] ?? null;

                if ($sqlState !== '42P07') {
                    throw $exception;
                }
            }
        }

        $columns = Schema::getColumnListing('users_api');

        Schema::table('users_api', function (Blueprint $table) use ($columns) {
            if (! in_array('name', $columns, true)) {
                $table->string('name');
            }

            if (! in_array('email', $columns, true)) {
                $table->string('email')->unique();
            }

            if (! in_array('email_verified_at', $columns, true)) {
                $table->timestamp('email_verified_at')->nullable();
            }

            if (! in_array('password', $columns, true)) {
                $table->string('password');
            }

            if (! in_array('role', $columns, true)) {
                $table->string('role')->default('admin');
            }

            if (! in_array('remember_token', $columns, true)) {
                $table->string('remember_token', 100)->nullable();
            }

            if (! in_array('is_admin', $columns, true)) {
                $table->boolean('is_admin')->default(false);
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
        Schema::dropIfExists('users_api');
    }
};
