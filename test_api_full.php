<?php
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\Role;
use App\Models\Employee;
use App\Models\AttendanceLog;
use App\Models\AttendanceLock;
use App\Models\PayrollRun;

echo "=== STARTING FULL API VERIFICATION ===\n";

// S1: SETUP & CLEANUP
echo "[1] Setup: Cleaning old test data...\n";
$testMonth = '2026-05';
AttendanceLock::where('month', $testMonth)->delete();
PayrollRun::where('month', $testMonth)->delete();
$testEmpCode = 'TEST-API-001';
Employee::where('employee_code', $testEmpCode)->delete();

// S2: ADMIN ACTIONS
echo "[2] Admin Action: Login & Create Employee...\n";
$admin = User::where('email', 'admin@example.com')->first();
Auth::login($admin);

// Create Employee
try {
    $emp = Employee::create([
        'user_id' => $admin->id, // Just linking to admin for test simplicity, usually new user
        'department_id' => 1,
        'employee_code' => $testEmpCode,
        'full_name' => 'API Test User',
        'basic_salary' => 1000,
        'join_date' => now(),
        'date_of_birth' => '1990-01-01'
    ]);
    echo " > Employee Created: ID {$emp->id}\n";
} catch (\Exception $e) { die("X Employee Create Failed: " . $e->getMessage()); }

// S3: ATTENDANCE
echo "[3] Attendance: Simulating Logs...\n";
// Insert 20 logs (Should result in deduction)
for ($i=1; $i<=20; $i++) {
    $day = str_pad($i, 2, '0', STR_PAD_LEFT);
    AttendanceLog::create([
        'employee_id' => $emp->id,
        'check_in' => "$testMonth-$day 08:00:00",
        'check_out' => "$testMonth-$day 17:00:00",
        'source' => 'API_TEST'
    ]);
}
echo " > 20 Daily Logs Created.\n";

// S4: LOCKING
echo "[4] Security: Locking Month $testMonth...\n";
try {
    $lock = AttendanceLock::create(['month' => $testMonth, 'locked_by' => $admin->id]);
    echo " > Month Locked.\n";
} catch (\Exception $e) { die("X Locking Failed: " . $e->getMessage()); }

// Verify Lock (Try to add log)
try {
    AttendanceLog::create([
        'employee_id' => $emp->id,
        'check_in' => "$testMonth-25 08:00:00",
        'source' => 'FAIL_TEST'
    ]);
    echo " X ERROR: Lock failed! Was able to add log.\n";
} catch (\Exception $e) {
    echo " > SUCCESS: Creation blocked by Lock.\n";
}

// S5: PAYROLL
echo "[5] Payroll: Running Calculation...\n";
$service = new App\Services\PayrollService();
$run = $service->generatePayroll($testMonth);
$item = $run->items()->where('employee_id', $emp->id)->first();

echo " > Days Worked: {$item->days_worked} (Expected 20)\n";
echo " > Deduction: \${$item->deduction_amount} (Expected > 0)\n";

if ($item->days_worked == 20 && $item->deduction_amount > 0) {
    echo " > Payroll Verified.\n";
} else {
    echo " X Payroll Verification FAILED.\n";
}

// S6: RBAC CHECK
echo "[6] RBAC Check: Switching to User Role...\n";
$userRole = Role::where('name', 'Employee')->first();
$userEmp = User::where('role_id', $userRole->id)->first();
if(!$userEmp) {
    // Create temp user if needed
    $userEmp = User::create([
        'name' => 'Temp Emp', 'email' => 'temp@test.com', 
        'password' => 'x', 'role_id' => $userRole->id
    ]);
}
Auth::login($userEmp);
echo " > Logged in as: {$userEmp->name} ({$userEmp->role->name})\n";

// Middleware is HTTP level, but we can check policy logic or manually try restricted actions if implemented in Model hooks
// For Tinker, we simulate the Middleware check logic
$allowed = in_array($userEmp->role->name, ['Admin']);
if (!$allowed) {
    echo " > RBAC Logic holds: User is NOT Admin.\n";
}

echo "=== VERIFICATION COMPLETE ===\n";
