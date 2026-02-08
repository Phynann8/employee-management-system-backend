<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\PayrollRun;
use App\Models\PayrollItem;
use App\Models\TaxBracket;
use App\Models\AttendanceLog;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class PayrollService
{
    // Exchange Rate: 1 USD = 4100 KHR (Approx)
    // Tax calculation is usually done in Riel in Cambodia
    const EXCHANGE_RATE = 4100; 

    public function generatePayroll($month)
    {
        return DB::transaction(function () use ($month) {
            // 1. Create Run Header
            $run = PayrollRun::create([
                'month' => $month,
                'status' => 'Draft'
            ]);

            // 2. Get All Active Employees
            $employees = Employee::where('employment_status', '!=', 'Resigned')->get();

            foreach ($employees as $emp) {
                $this->calculatePayslip($run->id, $emp, $month);
            }

            return $run->load('items');
        });
    }

    private function calculatePayslip($runId, $employee, $monthStr)
    {
        $basicUsd = $employee->basic_salary;
        
        // --- 1. Calculate Attendance Deductions ---
        $startOfMonth = Carbon::parse($monthStr)->startOfMonth();
        $endOfMonth = Carbon::parse($monthStr)->endOfMonth();

        // Count unique working days (Presence)
        $daysWorked = AttendanceLog::where('employee_id', $employee->id)
            ->whereBetween('check_in', [$startOfMonth, $endOfMonth])
            ->selectRaw('DATE(check_in) as date')
            ->distinct()
            ->get()
            ->count();

        // Standard Working Days = 26
        $standardDays = 26;
        $payableDays = $daysWorked; // + Add Paid Leave days here later
        
        $deductionUsd = 0;
        if ($payableDays < $standardDays) {
            $missingDays = $standardDays - $payableDays;
            $dailyRate = $basicUsd / $standardDays;
            $deductionUsd = $missingDays * $dailyRate;
        }

        // Adjusted Gross for Tax
        $actualGrossUsd = max(0, $basicUsd - $deductionUsd);

        // Convert to Riel for Tax Calculation
        $grossRiel = $actualGrossUsd * self::EXCHANGE_RATE;
        
        // 2. NSSF Deduction (2%)
        $nssfRiel = $grossRiel * 0.02; 

        // 3. Tax Base
        $taxBaseRiel = $grossRiel - $nssfRiel;

        // 4. Calculate Tax
        $taxRiel = 0;
        $bracket = TaxBracket::where('min_salary', '<=', $taxBaseRiel)
                             ->orderBy('min_salary', 'desc')
                             ->first();

        if ($bracket) {
            $taxRiel = ($taxBaseRiel * ($bracket->rate / 100)) - $bracket->deduction;
        }
        if ($taxRiel < 0) $taxRiel = 0;

        // 5. Convert back to USD
        $taxUsd = $taxRiel / self::EXCHANGE_RATE;
        $nssfUsd = $nssfRiel / self::EXCHANGE_RATE;
        $netUsd = $actualGrossUsd - $taxUsd - $nssfUsd;

        // 6. Save Item
        PayrollItem::create([
            'payroll_run_id' => $runId,
            'employee_id' => $employee->id,
            'gross_salary' => round($actualGrossUsd, 2),
            'deduction_amount' => round($deductionUsd, 2),
            'days_worked' => $daysWorked,
            'tax_amount' => round($taxUsd, 2),
            'net_salary' => round($netUsd, 2)
        ]);
    }
}
