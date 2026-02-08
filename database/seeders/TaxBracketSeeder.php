<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\TaxBracket;

class TaxBracketSeeder extends Seeder
{
    public function run(): void
    {
        // Cambodian Tax on Salary 2024 (Monthly in Riel)
        // Rate 0%
        TaxBracket::create([
            'min_salary' => 0, 
            'max_salary' => 1500000, 
            'rate' => 0, 
            'deduction' => 0
        ]);

        // Rate 5%
        TaxBracket::create([
            'min_salary' => 1500001, 
            'max_salary' => 2000000, 
            'rate' => 5, 
            'deduction' => 75000
        ]);

        // Rate 10%
        TaxBracket::create([
            'min_salary' => 2000001, 
            'max_salary' => 8500000, 
            'rate' => 10, 
            'deduction' => 175000
        ]);

        // Rate 15%
        TaxBracket::create([
            'min_salary' => 8500001, 
            'max_salary' => 12500000, 
            'rate' => 15, 
            'deduction' => 600000
        ]);

        // Rate 20%
        TaxBracket::create([
            'min_salary' => 12500001, 
            'max_salary' => 9999999999, // High cap
            'rate' => 20, 
            'deduction' => 1225000
        ]);
    }
}
