<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AttendanceLog extends Model
{
    use HasFactory;
    
    protected $guarded = [];

    protected static function boot()
    {
        parent::boot();

        static::saving(function ($log) {
            $month = \Carbon\Carbon::parse($log->check_in)->format('Y-m');
            if (\App\Models\AttendanceLock::where('month', $month)->exists()) {
                throw new \Exception("Attendance for $month is LOCKED and cannot be modified.");
            }
        });

        static::deleting(function ($log) {
            $month = \Carbon\Carbon::parse($log->check_in)->format('Y-m');
            if (\App\Models\AttendanceLock::where('month', $month)->exists()) {
                throw new \Exception("Attendance for $month is LOCKED and cannot be deleted.");
            }
        });
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
}
