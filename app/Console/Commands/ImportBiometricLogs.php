<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\BiometricRawLog;
use App\Models\Employee;
use Carbon\Carbon;
use PDO;

class ImportBiometricLogs extends Command
{
    protected $signature = 'biometric:sync';
    protected $description = 'Syncs logs from ZKTime.Net database to EMS';

    public function handle()
    {
        $this->info('Starting Biometric Sync...');

        // 1. Check Driver
        if (!in_array('sqlsrv', PDO::getAvailableDrivers())) {
            $this->error('Missing PHP Driver: sqlsrv. Please install Microsoft Drivers for PHP for SQL Server.');
            return 1;
        }

        try {
            // 2. Get Last Sync Time
            $lastLog = BiometricRawLog::orderBy('log_time', 'desc')->first();
            $lastTime = $lastLog ? $lastLog->log_time : '2020-01-01 00:00:00';

            $this->line("Fetching logs after: $lastTime");

            // 3. Select from External DB (New Schema: att_punches + hr_employee)
            // att_punches: punch_time, employee_id
            // hr_employee: id, emp_pin (Enroll Number)
            
            $newLogs = DB::connection('zkteco_sqlsrv')
                ->table('att_punches')
                ->join('hr_employee', 'att_punches.employee_id', '=', 'hr_employee.id')
                ->where('att_punches.punch_time', '>', $lastTime)
                ->select(
                    'hr_employee.emp_pin as enroll_number',
                    'att_punches.punch_time as timestamp',
                    'att_punches.workstate as type', // Often maps to Check-In/Out state
                    'att_punches.verifycode as verify_mode',
                    'att_punches.terminal_id as device_id'
                )
                ->orderBy('att_punches.punch_time', 'asc')
                ->limit(1000) // Batch limit
                ->get();

            if ($newLogs->isEmpty()) {
                $this->info('No new logs found.');
                return 0;
            }

            $count = 0;
            foreach ($newLogs as $log) {
                BiometricRawLog::create([
                    'device_id' => $log->device_id,
                    'enroll_number' => $log->enroll_number,
                    'log_time' => $log->timestamp,
                    'verify_mode' => $log->verify_mode ?? 0,
                    'in_out_mode' => $log->type ?? 0, // Need to verify if type maps directly
                    'processed' => false
                ]);
                $count++;
            }

            $this->info("Successfully imported $count logs.");

        } catch (\Exception $e) {
            $this->error("Sync Failed: " . $e->getMessage());
            return 1;
        }

        return 0;
    }
}
