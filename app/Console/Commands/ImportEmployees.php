<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Employee;
use App\Models\Department;
use App\Models\Role;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class ImportEmployees extends Command
{
    protected $signature = 'biometric:import-employees';
    protected $description = 'Import employees from ZKTime SQL Server';

    public function handle()
    {
        $this->info('Starting Employee Import from ZKTime...');

        // 1. Fetch ZK Departments & Sync
        $deptMapping = []; // ZK_ID => Local_ID
        $defaultDeptId = 1;

        try {
            $zkDepts = DB::connection('zkteco_sqlsrv')->table('hr_department')->get();
            $this->info("Found {$zkDepts->count()} departments.");

            if ($zkDepts->isNotEmpty()) {
                // Detect Name Field
                $first = (array)$zkDepts->first();
                $nameKey = 'dept_name'; // Default
                foreach (array_keys($first) as $key) {
                    if (str_contains(strtolower($key), 'name') || str_contains(strtolower($key), 'title')) {
                        $nameKey = $key;
                        break;
                    }
                }
                $this->info("Using '$nameKey' for department name.");

                foreach ($zkDepts as $zkDept) {
                    $dName = $zkDept->$nameKey ?? "Unknown Dept {$zkDept->id}";
                    
                    // Create/Find Local Dept
                    // Assuming existing Company ID 1 and Campus ID 1
                    $localDept = Department::firstOrCreate(
                        ['name' => $dName],
                        ['campus_id' => 1] 
                    );
                    $deptMapping[$zkDept->id] = $localDept->id;
                }
            }

        } catch (\Exception $e) {
            $this->warn("Department Sync Failed: " . $e->getMessage());
        }

        // 2. Fetch ZK Employees
        try {
            $zkEmployees = DB::connection('zkteco_sqlsrv')->table('hr_employee')->get();
            $this->info("Found {$zkEmployees->count()} employees in ZKTime.");
        } catch (\Exception $e) {
            $this->error("Connection Failed: " . $e->getMessage());
            return 1;
        }

        // 3. Prepare Defaults
        $defaultDept = Department::firstOrCreate(['name' => 'Unassigned'], ['campus_id' => 1]);
        $defaultRole = Role::firstOrCreate(['name' => 'Employee'], ['level' => 1]);

        foreach ($zkEmployees as $zkEmp) {
            // Mapping
            $enrollNumber = $zkEmp->emp_pin;
            $firstName = $zkEmp->emp_firstname ?? '';
            $lastName = $zkEmp->emp_lastname ?? '';
            $fullName = trim("$firstName $lastName");
            
            if (empty($fullName)) {
                $fullName = "Employee #{$enrollNumber}"; 
            }

            $email = "{$enrollNumber}@ems.local";
            
            // Resolve Department
            $zkDeptId = $zkEmp->department_id ?? null;
            $localDeptId = $deptMapping[$zkDeptId] ?? $defaultDept->id;

            $this->line("Processing: $fullName ($enrollNumber) -> Dept ID: $localDeptId");

            // 4. Create or Update User
            $user = User::firstOrCreate(
                ['email' => $email],
                [
                    'name' => $fullName,
                    'password' => Hash::make('password'),
                    'role_id' => $defaultRole->id
                ]
            );

            // 5. Create or Update Employee
            $employee = Employee::where('biometric_enroll_number', $enrollNumber)
                ->orWhere('employee_code', (string)$enrollNumber)
                ->first();

            if ($employee) {
                // Update existing
                $employee->update([
                    'biometric_enroll_number' => $enrollNumber,
                    'department_id' => $localDeptId // Update department
                ]);
                $this->info("Mapped existing: {$employee->full_name} to Dept $localDeptId");
            } else {
                // Create New
                Employee::create([
                    'user_id' => $user->id,
                    'department_id' => $localDeptId,
                    'full_name' => $fullName,
                    'employee_code' => (string)$enrollNumber,
                    'biometric_enroll_number' => $enrollNumber,
                    'basic_salary' => 0, 
                    'date_of_birth' => '2000-01-01',
                    'join_date' => now(),
                    'gender' => 'Other',
                    'employment_status' => 'Probation'
                ]);
                $this->info("Created new: $fullName in Dept $localDeptId");
            }
        }

        $this->info('Import Complete.');
        return 0;
    }
}
