<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Employee extends Model
{
    protected $fillable = [
        'nama',
        'posisi',
        'nomer_telepon',
    ];

    public function attendanceMasuk(): HasMany
    {
        return $this->hasMany(AttendanceMasuk::class);
    }

    public function attendanceKeluar(): HasMany
    {
        return $this->hasMany(AttendanceKeluar::class);
    }
}
