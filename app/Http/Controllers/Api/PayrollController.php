<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\PayrollService;
use App\Models\PayrollRun;
use Illuminate\Support\Facades\Auth;

class PayrollController extends Controller
{
    protected $payrollService;

    public function __construct(PayrollService $payrollService)
    {
        $this->payrollService = $payrollService;
    }

    // Admin: Run Payroll
    public function generatePayroll(Request $request)
    {
        $request->validate(['month' => 'required|date_format:Y-m']);
        
        // Check if run exists
        if (PayrollRun::where('month', $request->month)->exists()) {
            return response()->json(['message' => 'Payroll already exists for this month'], 400);
        }

        $run = $this->payrollService->generatePayroll($request->month);
        return response()->json(['message' => 'Payroll generated successfully', 'data' => $run]);
    }

    // Admin: View Payroll
    public function viewPayroll($month)
    {
        $run = PayrollRun::with(['items.employee'])->where('month', $month)->firstOrFail();
        return response()->json($run);
    }

    // Employee: My Payslips
    public function myPayslips(Request $request)
    {
        $user = Auth::user();
        if (!$user->employee) {
            return response()->json(['data' => []]);
        }
        
        $items = \App\Models\PayrollItem::with('run')
            ->where('employee_id', $user->employee->id)
            ->orderByDesc('id') 
            ->get();
            
        return response()->json(['data' => $items]);
    }

    // PDF Download
    public function downloadPayslip($id) {
        $user = Auth::user();
        $item = \App\Models\PayrollItem::with(['run', 'employee.department', 'employee.user.role'])
            ->findOrFail($id);
        
        // Security Check
        if ($user->role->name !== 'Admin' && $item->employee_id !== $user->employee->id) {
            abort(403, 'Unauthorized');
        }

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdfs.payslip', [
            'item' => $item, 
            'run' => $item->run
        ]);
        
        return $pdf->download("payslip-{$item->run->month}.pdf");
    }
}
