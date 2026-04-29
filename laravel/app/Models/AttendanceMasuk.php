<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AttendanceMasuk extends Model
{
    protected $table = 'attendance_masuk';
    protected $fillable = ['employee_id', 'tanggal', 'jam_masuk', 'status'];

    // Tambahkan baris ini untuk mengubah string menjadi objek tanggal (Carbon)
    protected $casts = [
        'tanggal' => 'date',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }
}