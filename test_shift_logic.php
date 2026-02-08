<?php
use App\Models\Campus;
use App\Models\Department;
use App\Models\Employee;
use App\Models\BiometricRawLog;
use App\Models\AttendanceLog;
use App\Models\User;
use Illuminate\Support\Facades\Artisan;

echo "=== TESTING SHIFT LOGIC (LATE/EARLY) ===\n";

// 0. Cleanup Old Data
BiometricRawLog::where('enroll_number', '99999')->delete();
Employee::where('biometric_enroll_number', '99999')->delete();

// 1. Setup Campus & Employee
$campus = Campus::create([
    'company_id' => 1,
    'name' => 'Strict Campus',
    'location' => 'Test Loc',
    'start_time' => '08:00:00',
    'end_time' => '17:00:00'
]);

$dept = Department::create(['campus_id' => $campus->id, 'name' => 'Strict Dept']);

$user = User::factory()->create();
$emp = Employee::create([
    'user_id' => $user->id,
    'department_id' => $dept->id,
    'employee_code' => 'SHIFT-001',
    'full_name' => 'Late Guy',
    'biometric_enroll_number' => '99999',
    'basic_salary' => 500,
    'join_date' => now(),
    'date_of_birth' => '2000-01-01'
]);

// 2. Simulate Late Log (08:30)
echo " > Simulating Check-in at 08:30 (30 mins late)...\n";
BiometricRawLog::create([
    'enroll_number' => '99999',
    'log_time' => '2026-06-01 08:30:00',
    'processed' => false
]);

// 3. Process
echo " > Processing...\n";
Artisan::call('biometric:process');

// 4. Verify
$log = AttendanceLog::where('employee_id', $emp->id)->whereDate('check_in', '2026-06-01')->first();

if($log) {
    echo "--- RESULT ---\n";
    echo "Status: " . $log->status . "\n";
    echo "Late Minutes: " . $log->late_minutes . "\n";
    
    if ($log->status === 'Late' && $log->late_minutes == 30) {
        echo "SUCCESS: Late status detected correctly.\n";
    } else {
        echo "FAIL: Incorrect status or minutes.\n";
    }
} else {
    echo "FAIL: Log not created.\n";
}

// Cleanup
$emp->delete();
$dept->delete();
$campus->delete();
$user->delete();
BiometricRawLog::where('enroll_number', '99999')->delete();
AttendanceLog::where('employee_id', $emp->id)->delete();
exit;
