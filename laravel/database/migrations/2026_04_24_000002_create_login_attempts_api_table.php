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
        $tableExists = Schema::hasTable('login_attempts_api');

        if (! $tableExists) {
            try {
                Schema::create('login_attempts_api', function (Blueprint $table) {
                    $table->id();
                    $table->string('ip_address');
                    $table->unsignedInteger('attempts')->default(0);
                    $table->timestamp('blocked_until')->nullable();
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

        $columns = Schema::getColumnListing('login_attempts_api');

        Schema::table('login_attempts_api', function (Blueprint $table) use ($columns) {
            if (! in_array('ip_address', $columns, true)) {
                $table->string('ip_address');
            }

            if (! in_array('attempts', $columns, true)) {
                $table->unsignedInteger('attempts')->default(0);
            }

            if (! in_array('blocked_until', $columns, true)) {
                $table->timestamp('blocked_until')->nullable();
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
        Schema::dropIfExists('login_attempts_api');
    }
};
