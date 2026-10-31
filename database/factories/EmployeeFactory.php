<?php

namespace Database\Factories;

use App\Models\Department;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Employee>
 */
class EmployeeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => null, // most employees have no login
            'department_id' => Department::query()->inRandomOrder()->value('id')
                ?? Department::factory(),
            'first_name' => fake()->firstName(),
            'middle_name' => fake()->optional(0.7)->lastName(),
            'last_name' => fake()->lastName(),
            'suffix' => fake()->optional(0.1)->randomElement(['Jr.', 'Sr.', 'II', 'III']),
            'position' => fake()->randomElement([
                'Cook', 'Kitchen Helper', 'Warehouse Staff', 'Stock Clerk',
                'Purchaser', 'Cashier', 'Supervisor', 'Admin Assistant',
            ]),
        ];
    }

    /** Employee that also has a login account. */
    public function withUser(): static
    {
        return $this->afterCreating(function (Employee $employee) {
            $user = User::factory()->create(['name' => $employee->full_name]);
            $employee->update(['user_id' => $user->id]);
        });
    }
}
