<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {

        $this->call([
            SupplierSeeder::class,
            WarehouseSeeder::class,
            WarehouseItemSeeder::class,
            ItemSeeder::class,
            LotSeeder::class,
            ExternalTransferSeeder::class,
            ProductionSeeder::class,
            AccountSeeder::class,
            // OpeningBalanceSeeder::class,
            EmployeeSeeder::class,
            EmployeePieceRateSeeder::class,
            UsersFromEmployeesSeeder::class,

            // PurchasePostedSeeder::class,
        ]);

    }
}
