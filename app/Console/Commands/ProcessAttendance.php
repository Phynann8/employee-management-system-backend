<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\BiometricRawLog;
use App\Models\AttendanceLog;
use App\Models\Employee;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ProcessAttendance extends Command
{
    protected $signature = 'biometric:process';
    protected $description = 'Process raw biometric logs into attendance records';

    public function handle()
    {
        $this->info('Processing Biometric Logs...');

        // 1. Get Unprocessed Logs
        $rawLogs = BiometricRawLog::where('processed', false)
            ->orderBy('log_time', 'asc')
            ->get();

        if ($rawLogs->isEmpty()) {
            $this->info('No new logs to process.');
            return 0;
        }

        $this->info("Found {$rawLogs->count()} logs. Grouping by employee and date...");

        // 2. Group by Employee Enroll Number
        $logsByEmployee = $rawLogs->groupBy('enroll_number');
        
        // Optimize: Fetch all employees involved in this batch
        $enrollNumbers = $logsByEmployee->keys()->map(fn($id) => trim($id))->toArray();
        $employees = Employee::whereIn('biometric_enroll_number', $enrollNumbers)
            ->with('department.campus')
            ->get()
            ->keyBy('biometric_enroll_number');

        foreach ($logsByEmployee as $enrollNumber => $employeeLogs) {
            // Find Employee from Collection
            $cleanEnrollNumber = trim($enrollNumber);
            $employee = $employees->get($cleanEnrollNumber);

            if (!$employee) {
                // $this->warn("Skipping: No employee found with Enrollment #{$cleanEnrollNumber}");
                continue;
            }

            // Group by Date (Y-m-d)
            $logsByDate = $employeeLogs->groupBy(function ($log) {
                return Carbon::parse($log->log_time)->format('Y-m-d');
            });

            foreach ($logsByDate as $date => $dayLogs) {
                // 2. Check if Month is Locked
            $month = Carbon::parse($date)->format('Y-m');
            if (\App\Models\AttendanceLock::where('month', $month)->exists()) {
                 $this->warn("Skipping processed date $date (LOCKED)");
                 continue;
            }

                // 3. Find First and Last LogOut
                $firstLog = $dayLogs->first();
                $lastLog = $dayLogs->last();

                $checkInTime = Carbon::parse($firstLog->log_time);
                $checkOutTime = $dayLogs->count() > 1 ? Carbon::parse($lastLog->log_time) : null;

                // --- SHIFT LOGIC START ---
                $status = 'Present';
                $lateMins = 0;
                $earlyMins = 0;

                if ($employee->department && $employee->department->campus) {
                    $campus = $employee->department->campus;
                    
                    // Parse Shift Times for THIS Day
                    $shiftStart = Carbon::parse($date . ' ' . $campus->start_time);
                    $shiftEnd = Carbon::parse($date . ' ' . $campus->end_time);

                    // 1. Check Late
                    // Add 5 min grace period? Let's be strict for now or 5 min
                    if ($checkInTime->gt($shiftStart->copy()->addMinutes(5))) {
                        $lateMins = $checkInTime->diffInMinutes($shiftStart);
                        $status = 'Late';
                    }

                    // 2. Check Early Leave
                    if ($checkOutTime && $checkOutTime->lt($shiftEnd)) {
                        $earlyMins = $shiftEnd->diffInMinutes($checkOutTime);
                        $status = ($status === 'Late') ? 'Late & Early Leave' : 'Early Leave';
                    }
                }
                // --- SHIFT LOGIC END ---

                // Sync to AttendanceLog Table
                $attendance = AttendanceLog::where('employee_id', $employee->id)
                    ->whereDate('check_in', $date)
                    ->first();

                if ($attendance) {
                    // Update existing
                    $updated = false;
                    if ($checkInTime->lt($attendance->check_in)) {
                        $attendance->check_in = $checkInTime;
                        $updated = true;
                    }
                    if ($checkOutTime) {
                        if (!$attendance->check_out || $checkOutTime->gt($attendance->check_out)) {
                            $attendance->check_out = $checkOutTime;
                            $updated = true;
                        }
                    }
                    
                    // Always update status on re-process
                    $attendance->status = $status;
                    $attendance->late_minutes = $lateMins;
                    $attendance->early_minutes = $earlyMins;
                    $attendance->save();

                    $this->line("Updated: {$employee->full_name} on {$date} ($status)");
                } else {
                    AttendanceLog::create([
                        'employee_id' => $employee->id,
                        'check_in' => $checkInTime,
                        'check_out' => $checkOutTime,
                        'source' => 'Biometric',
                        'status' => $status,
                        'late_minutes' => $lateMins,
                        'early_minutes' => $earlyMins
                    ]);
                    $this->line("Created: {$employee->full_name} on {$date} ($status)");
                }
            }
        }

        // 3. Mark Logs as Processed
        BiometricRawLog::whereIn('id', $rawLogs->pluck('id'))->update(['processed' => true]);

        $this->info('Processing Complete.');
        return 0;
    }
}
