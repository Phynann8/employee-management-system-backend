<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\AttendanceLog; // Correct Model Name
use App\Models\AttendanceLock;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class AttendanceController extends Controller
{
    // Employee: PUNCH IN/OUT Logic (Manual Web)
    public function checkIn(Request $request) {
        $user = Auth::user();
        if (!$user->employee) return response()->json(['error' => 'Not an employee'], 403);
        
        // Use ProcessAttendance logic or simple insert?
        // Let's simple insert a "Web Punch"
        $log = AttendanceLog::create([
            'employee_id' => $user->employee->id,
            'check_in' => now(),
            'source' => 'WEB'
        ]);
        
        return response()->json(['message' => 'Checked In', 'data' => $log]);
    }

    public function checkOut(Request $request) {
        $user = Auth::user();
        if (!$user->employee) return response()->json(['error' => 'Not an employee'], 403);
        
        // Find last open log
        $log = AttendanceLog::where('employee_id', $user->employee->id)
            ->whereNull('check_out')
            ->orderByDesc('check_in')
            ->first();

        if ($log) {
            $log->update(['check_out' => now()]);
            return response()->json(['message' => 'Checked Out', 'data' => $log]);
        }
        return response()->json(['error' => 'No active check-in found'], 404);
    }

    public function myAttendance(Request $request) {
        $user = Auth::user();
        $logs = AttendanceLog::where('employee_id', $user->employee->id)
            ->orderByDesc('check_in')
            ->limit(30)
            ->get();
        return response()->json($logs);
    }

    // ADMIN: Lock Attendance Month
    public function lockMonth(Request $request)
    {
        $request->validate(['month' => 'required|date_format:Y-m']);
        
        $lock = AttendanceLock::firstOrCreate(
            ['month' => $request->month],
            ['locked_by' => Auth::id()]
        );

        return response()->json(['message' => "Attendance for {$request->month} is LOCKED.", 'data' => $lock]);
    }

    // ADMIN: Check if Locked
    public function checkLock($month)
    {
        $isLocked = AttendanceLock::where('month', $month)->exists();
        return response()->json(['locked' => $isLocked]);
    }
}
