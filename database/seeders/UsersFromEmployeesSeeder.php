<?php

// database/seeders/UsersFromEmployeesSeeder.php
namespace Database\Seeders;

use App\Models\Employee;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UsersFromEmployeesSeeder extends Seeder
{
    public function run(): void
    {
        $employees = Employee::all();

        foreach ($employees as $emp) {
            // bikin email dummy kalau employees tidak punya email
            $email = $emp->email ?? ($emp->code . '@local.test');

            User::updateOrCreate(
                ['employee_code' => $emp->code],
                [
                    'name' => $emp->name,
                    'email' => $email,
                    'password' => Hash::make('123'),
                    'is_admin' => $emp->role === 'owner',
                    // pengen nambahin ini:
                    'is_owner' => $emp->role === 'owner',
                ]
            );

        }
    }
}
