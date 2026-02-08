<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\Company;
use App\Models\Campus;
use App\Models\Department;
use App\Models\Role;
use App\Models\User;
use App\Models\Employee;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Structure
        $company = Company::create(['name' => 'Tech Cambodia']);
        
        $campus = Campus::create([
            'company_id' => $company->id,
            'name' => 'Phnom Penh HQ',
            'location' => 'Monivong Blvd'
        ]);

        $deptIT = Department::create(['campus_id' => $campus->id, 'name' => 'IT Department']);
        $deptHR = Department::create(['campus_id' => $campus->id, 'name' => 'HR Department']);

        $roleAdmin = Role::create(['name' => 'Admin', 'level' => 10]);
        $roleEmp = Role::create(['name' => 'Employee', 'level' => 1]);

        // 2. Admin User
        $userAdmin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => Hash::make('password'), // default password
            'role_id' => $roleAdmin->id
        ]);

        Employee::create([
            'user_id' => $userAdmin->id,
            'department_id' => $deptIT->id,
            'employee_code' => 'EMP-001',
            'full_name' => 'System Administrator',
            'date_of_birth' => '1990-01-01',
            'join_date' => '2023-01-01',
            'basic_salary' => 1000.00
        ]);

        // 3. Normal User
        $userEmp = User::create([
            'name' => 'Sok Dara',
            'email' => 'employee@example.com',
            'password' => Hash::make('password'),
            'role_id' => $roleEmp->id
        ]);

        Employee::create([
            'user_id' => $userEmp->id,
            'department_id' => $deptIT->id,
            'employee_code' => 'EMP-002',
            'full_name' => 'Sok Dara',
            'date_of_birth' => '1995-05-15',
            'join_date' => '2023-06-01',
            'basic_salary' => 500.00
        ]);
        
        $this->command->info('Database seeded successfully!');
    }
}
