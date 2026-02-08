<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\EmployeeController;
use App\Http\Controllers\Api\AttendanceController;
use App\Http\Controllers\Api\LeaveController;
use App\Http\Controllers\Api\PayrollController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// Public Routes
Route::post('/login', [AuthController::class, 'login']);

// Protected Routes
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);

    // Employee Self-Service (ESS)
    Route::get('/me', [AuthController::class, 'me']);
    Route::get('/me/attendance', [AttendanceController::class, 'myAttendance']);
    Route::get('/me/leaves', [LeaveController::class, 'myLeaves']);
    Route::post('/me/leave-request', [LeaveController::class, 'requestLeave']);
    Route::get('/me/payslips', [PayrollController::class, 'myPayslips']);

    // Attendance
    Route::post('/attendance/check-in', [AttendanceController::class, 'checkIn']);
    Route::post('/attendance/check-out', [AttendanceController::class, 'checkOut']);

    // HR Management (Employees)
    // HR Management (Employees)
    Route::get('/employees', [EmployeeController::class, 'index']); // Visible to all authenticated

    // --- ADMIN ONLY ---
    Route::middleware('role:Admin')->group(function() {
        Route::post('/employees', [EmployeeController::class, 'store']);
        Route::put('/employees/{id}', [EmployeeController::class, 'update']);
        Route::delete('/employees/{id}', [EmployeeController::class, 'destroy']);

        // Payroll
        Route::post('/payroll/run', [PayrollController::class, 'generatePayroll']);
        Route::get('/payroll/{month}', [PayrollController::class, 'viewPayroll']);
        Route::get('/payroll/item/{id}/download', [PayrollController::class, 'downloadPayslip']); // New Route
        
        // Attendance Lock
        Route::post('/attendance/lock', [AttendanceController::class, 'lockMonth']);
        Route::get('/attendance/lock/{month}', [AttendanceController::class, 'checkLock']);
    });
});
