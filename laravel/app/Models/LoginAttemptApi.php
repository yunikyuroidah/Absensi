<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LoginAttemptApi extends Model
{
    protected $table = 'login_attempts_api';

    protected $fillable = [
        'ip_address',
        'attempts',
        'blocked_until',
    ];

    protected function casts(): array
    {
        return [
            'attempts' => 'integer',
            'blocked_until' => 'datetime',
        ];
    }
}
