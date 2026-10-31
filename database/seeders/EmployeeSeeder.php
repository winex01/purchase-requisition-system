<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\Employee;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class EmployeeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach (['Kitchen', 'Warehouse', 'Admin', 'Purchasing', 'Sales'] as $name) {
            Department::firstOrCreate(['name' => $name]);
        }

        Employee::factory()->count(30)->create();

        // A few employees that also have user accounts
        Employee::factory()->count(3)->withUser()->create();
    }
}
