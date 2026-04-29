<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AttendanceKeluar extends Model
{
    protected $table = 'attendance_keluar';
    protected $fillable = ['employee_id', 'tanggal', 'jam_keluar'];

    // Tambahkan baris ini juga
    protected $casts = [
        'tanggal' => 'date',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }
}