<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AttendanceLock extends Model
{
    use HasFactory;
    
    protected $guarded = [];

    public function lockedBy()
    {
        return $this->belongsTo(User::class, 'locked_by');
    }
}
