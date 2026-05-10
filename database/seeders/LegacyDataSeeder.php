<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use App\Models\User;

class LegacyDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // $sqlPath = 'd:/1-Computer Science/1-Personal Project/CRM_System_Legacy/db/db_psischv_09_10_24.sql'; // Partial dump
        $sqlPath = 'd:/1-Computer Science/1-Personal Project/CRM_System_Legacy/db/db_psischv_09_10_24.sql';
        
        if (!File::exists($sqlPath)) {
            $this->command->error("SQL file not found at: $sqlPath");
            return;
        }

        $this->command->info('Parsing SQL file...');
        $content = File::get($sqlPath);
        
        // Import Users
        $this->importUsers($content);
        
        // Import Students
        $this->importStudents($content);
    }

    protected function importUsers($content)
    {
        $this->command->info('Importing Users...');
        
        // Match INSERT INTO `rms_users` (...) VALUES ...
        // We look for the pattern with optional columns. Handle flexible whitespace. Ungreedy match for values.
        preg_match('/INSERT\s+INTO\s+[`"]?rms_users[`"]?\s*(\(([^)]+)\))?\s*VALUES\s*(.*?);/si', $content, $matches);
        
        if (!empty($matches)) {
            $this->command->info('Matched INSERT statements for rms_users.');
            // $this->command->info('Columns: ' . ($matches[2] ?? 'None'));
             $this->command->info('Values Preview: ' . substr($matches[3] ?? '', 0, 100));
        }

        if (empty($matches[3])) {
            $this->command->warn('No INSERT statements found for rms_users.');
            return;
        }

        $columns = [];
        if (!empty($matches[2])) {
            // Columns are specified
            $columns = array_map(function($col) {
                return trim($col, '`" ');
            }, explode(',', $matches[2]));
        } else {
            // Fallback: assume standard columns based on legacy code analysis
            // Schema: id, first_name, last_name, user_name, password, user_type, status, photo...
            // This is a GUESS. If this fails, we need the CREATE TABLE definition.
            $this->command->warn('Column names not found in INSERT. Using default mapping schema.');
             $columns = ['id', 'first_name', 'last_name', 'user_name', 'password', 'user_type', 'status', 'photo', 'remember_token', 'created_at', 'updated_at']; 
        }

        $valuesBlock = $matches[3];
        // Parse values: (1, 'John', ...), (2, 'Jane', ...)
        preg_match_all('/\((.*?)\)/s', $valuesBlock, $rows);

        foreach ($rows[1] as $row) {
            $data = str_getcsv($row, ',', "'");
            
            $userData = [];
            foreach ($columns as $index => $col) {
                if (isset($data[$index])) {
                    $val = $data[$index];
                    if ($val === 'NULL') $val = null;
                    
                    // Map legacy columns to new User model
                    if ($col === 'stu_id') continue; // Skip if irrelevant (though this is users table)
                    
                    $userData[$col] = $val;
                }
            }

            // Ensure critical fields
            if (empty($userData['password'])) $userData['password'] = 'legacy_no_pass'; 
            
            DB::table('rms_users')->updateOrInsert(
                ['id' => $userData['id'] ?? null],
                $userData
            );
        }
        $this->command->info('Users imported successfully.');
    }

    protected function importStudents($content)
    {
        // Increase limits for large SQL processing
        ini_set('memory_limit', '512M');
        ini_set('pcre.backtrack_limit', '10000000');

        $this->command->info('Importing Students...');
        
        // Find start of INSERT
        $startPos = stripos($content, 'INSERT INTO `rms_student`');
        if ($startPos === false) $startPos = stripos($content, 'INSERT  INTO `rms_student`');
        if ($startPos === false) $startPos = stripos($content, 'insert into rms_student');

        if ($startPos === false) {
             $this->command->error("Could not find 'INSERT INTO rms_student' in content!");
             return;
        }

        // Find end of statement (semicolon)
        $endPos = strpos($content, ";\n", $startPos);
        if ($endPos === false) $endPos = strpos($content, ";\r", $startPos);
        if ($endPos === false) $endPos = strpos($content, ");", $startPos) + 1; // Fallback

        $length = $endPos - $startPos + 1;
        $sqlParam = substr($content, $startPos, $length);
        
        $this->command->info("Extracted SQL length: " . strlen($sqlParam));
        // $this->command->info("Snippet: " . substr($sqlParam, 0, 200) . " ... " . substr($sqlParam, -100));

        // Match columns and values on the extracted string
        preg_match('/INSERT\s+INTO\s+[`"]?rms_student[`"]?\s*\((.*?)\)\s*VALUES\s*(.*?);/si', $sqlParam, $matches);

        if (empty($matches[2])) {
            $this->command->warn('No VALUES found for rms_student in extracted SQL.');
             // Log regex error if any
            if (preg_last_error() !== PREG_NO_ERROR) {
                $this->command->error('PCRE Error: ' . preg_last_error());
            }
            return;
        }
        
        $columns = [];
        if (!empty($matches[1])) {
            $columns = array_map(function($col) { return trim($col, '`" '); }, explode(',', $matches[1]));
        }

        $valuesBlock = $matches[2];
        // Parse values
        preg_match_all('/\((.*?)\)/s', $valuesBlock, $rows);
        
        $count = 0;
        foreach ($rows[1] as $row) {
            $data = str_getcsv($row, ',', "'");
            $stuData = [];
           
            foreach ($columns as $index => $col) {
                 if (isset($data[$index])) {
                    $val = $data[$index];
                    if ($val === 'NULL') $val = null;
                    $stuData[$col] = $val;
                }
            }
            
            if (empty($stuData)) continue;

            // Filter data to match existing columns in the table
            $validColumns = Schema::getColumnListing('rms_student');
            $filteredData = array_intersect_key($stuData, array_flip($validColumns));

             // Ensure stu_id is present for updateOrInsert
            $id = $filteredData['stu_id'] ?? ($stuData['stu_id'] ?? null);
            if (!$id && isset($stuData['id'])) $id = $stuData['id'];

            if (!$id) continue;

            DB::table('rms_student')->updateOrInsert(
                ['stu_id' => $id],
                $filteredData
            );
            $count++;
        }
        $this->command->info("Students imported successfully: $count records.");
    }
}
