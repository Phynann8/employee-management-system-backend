<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class EmployeeController extends Controller
{
    public function index()
    {
        return response()->json(Employee::with(['user', 'department', 'user.role'])->paginate(15));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email|unique:users',
            'full_name' => 'required|string',
            'employee_code' => 'required|unique:employees',
            'department_id' => 'required|exists:departments,id',
            'role_id' => 'required|exists:roles,id',
            'basic_salary' => 'required|numeric',
            'date_of_birth' => 'required|date',

            'join_date' => 'required|date',
            'biometric_enroll_number' => 'nullable|integer|unique:employees',
        ]);

        return DB::transaction(function () use ($validated) {
            // Create User
            $user = User::create([
                'name' => $validated['full_name'],
                'email' => $validated['email'],
                'password' => Hash::make('password'), // Default password
                'role_id' => $validated['role_id']
            ]);

            // Create Employee Profile
            $employee = Employee::create([
                'user_id' => $user->id,
                'full_name' => $validated['full_name'],
                'employee_code' => $validated['employee_code'],
                'department_id' => $validated['department_id'],
                'basic_salary' => $validated['basic_salary'],
                'date_of_birth' => $validated['date_of_birth'],
                'join_date' => $validated['join_date'],
                'gender' => $request->gender,

                'biometric_enroll_number' => $request->biometric_enroll_number,
                'employment_status' => 'Probation'
            ]);

            return response()->json($employee->load('user'), 201);
        });
    }

    public function show($id)
    {
        return response()->json(Employee::with(['user', 'department'])->findOrFail($id));
    }

    public function update(Request $request, $id)
    {
        $employee = Employee::findOrFail($id);
        $employee->update($request->only([
            'full_name', 'department_id', 'basic_salary', 'employment_status', 'biometric_enroll_number'
        ]));
        
        return response()->json($employee);
    }
    
    public function destroy($id)
    {
        $employee = Employee::findOrFail($id);
        $employee->user->delete(); // Delete user account too
        // Configured cascade delete should handle employee record if DB set up right, 
        // but safely:
        $employee->delete();
        return response()->json(['message' => 'Employee deleted']);
    }
}
