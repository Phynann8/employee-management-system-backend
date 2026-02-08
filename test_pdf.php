<?php
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\PayrollRun;
use App\Models\PayrollItem;

echo "=== TESTING PDF GENERATION ===\n";

// Login as Admin
$admin = User::where('email', 'admin@example.com')->first();
Auth::login($admin);

// Find a payroll item
$item = PayrollItem::first();
if (!$item) {
    die("X No payroll items found. Run payroll first.\n");
}

echo " > Testing download for Item ID: {$item->id}...\n";

// Helper to simulate Controller Call (since we can't easily curl locally in tinker without server)
// We will just instantiate the controller and call the method?
// Or better, just check if PDF View renders without error.

try {
    $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdfs.payslip', [
        'item' => $item, 
        'run' => $item->run
    ]);
    $content = $pdf->output(); // Should generate binary string
    
    if (strlen($content) > 1000 && strpos($content, '%PDF') === 0) {
        echo "SUCCESS: PDF Generated (" . strlen($content) . " bytes).\n";
        file_put_contents('test_payslip.pdf', $content);
        echo " > Saved to test_payslip.pdf\n";
    } else {
        echo "FAIL: Content seems invalid.\n";
    }
} catch (\Exception $e) {
    echo "FAIL: " . $e->getMessage() . "\n";
}
