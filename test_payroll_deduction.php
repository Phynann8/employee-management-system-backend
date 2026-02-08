<?php
echo "TESTING PAYROLL DEDUCTION...\n";
$month = '2025-05';
$emp = App\Models\Employee::first();
if(!$emp) die("No employee found");

// 1. Wipe old data for clean test
App\Models\PayrollRun::where('month', $month)->delete();
App\Models\AttendanceLog::where('employee_id', $emp->id)
    ->whereBetween('check_in', ["$month-01", "$month-31 23:59:59"])
    ->delete();

// 2. Insert 25 Days of Attendance (Working 26 days is standard, so 1 day missing)
echo "Inserting 25 days of logs for {$emp->full_name}...\n";
for($i=1; $i<=25; $i++) {
    $day = str_pad($i, 2, '0', STR_PAD_LEFT);
    App\Models\AttendanceLog::create([
        'employee_id' => $emp->id,
        'check_in' => "$month-$day 08:00:00",
        'source' => 'TEST'
    ]);
}

// 3. Run Payroll
echo "Running Payroll for $month...\n";
$service = new App\Services\PayrollService();
$run = $service->generatePayroll($month);

// 4. Verify
$item = $run->items()->where('employee_id', $emp->id)->first();
echo "--- RESULT ---\n";
echo "Basic Salary: $" . $emp->basic_salary . "\n";
echo "Days Worked: " . $item->days_worked . " (Expected: 25)\n";
echo "Deduction: $" . $item->deduction_amount . "\n";
echo "Net Salary: $" . $item->net_salary . "\n";

if($item->deduction_amount > 0 && $item->days_worked == 25) {
    echo "SUCCESS: Deduction applied correctly!\n";
} else {
    echo "FAIL: No deduction or wrong day count.\n";
}
exit();
