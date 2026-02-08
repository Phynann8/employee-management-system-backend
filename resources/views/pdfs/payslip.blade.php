<!DOCTYPE html>
<html>
<head>
    <title>Payslip</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; }
        .header { text-align: center; margin-bottom: 20px; }
        .details { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        .details td { padding: 5px; }
        .salary-table { width: 100%; border-collapse: collapse; border: 1px solid #000; }
        .salary-table th, .salary-table td { border: 1px solid #000; padding: 8px; text-align: left; }
        .total { font-weight: bold; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Tech Cambodia</h1>
        <h2>Payslip for {{ $run->month }}</h2>
    </div>

    <table class="details">
        <tr>
            <td><strong>Employee:</strong> {{ $item->employee->full_name }}</td>
            <td><strong>ID:</strong> {{ $item->employee->employee_code }}</td>
        </tr>
        <tr>
            <td><strong>Department:</strong> {{ $item->employee->department->name ?? '-' }}</td>
            <td><strong>Role:</strong> {{ $item->employee->user->role->name ?? 'Employee' }}</td>
        </tr>
    </table>

    <table class="salary-table">
        <thead>
            <tr>
                <th>Description</th>
                <th>Amount (USD)</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>Basic Salary</td>
                <td>${{ number_format($item->gross_salary + $item->deduction_amount, 2) }}</td>
            </tr>
            @if($item->deduction_amount > 0)
            <tr>
                <td>Deductions (Absent/Unpaid)</td>
                <td style="color: red;">-${{ number_format($item->deduction_amount, 2) }}</td>
            </tr>
            @endif
            <tr>
                <td><strong>Gross Salary (Tax Base)</strong></td>
                <td><strong>${{ number_format($item->gross_salary, 2) }}</strong></td>
            </tr>
            <tr>
                <td>Tax on Salary</td>
                <td style="color: red;">-${{ number_format($item->tax_amount, 2) }}</td>
            </tr>
            <tr>
                <td>NSSF (Pension/Health) Valued</td>
                <td>(Included in Tax calc)</td>
            </tr>
            <tr class="total">
                <td>NET SALARY</td>
                <td>${{ number_format($item->net_salary, 2) }}</td>
            </tr>
        </tbody>
    </table>

    <div style="margin-top: 50px;">
        <p>Generated on: {{ date('Y-m-d H:i:s') }}</p>
    </div>
</body>
</html>
