<?php
echo "STARTING TEST...";
$user = App\Models\User::first();
Auth::login($user);
App\Models\AttendanceLock::firstOrCreate(['month'=>'2025-01'],['locked_by'=>$user->id]);

try {
    App\Models\AttendanceLog::create(['employee_id'=>1, 'check_in'=>'2025-01-10', 'source'=>'TEST']);
} catch (\Exception $e) {
    echo "BLOCK SUCCESS: " . $e->getMessage();
}
exit();
