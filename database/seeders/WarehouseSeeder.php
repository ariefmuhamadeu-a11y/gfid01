<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class WarehouseSeeder extends Seeder
{
    public function run(): void
    {
        $warehouses = [
            ['code' => 'KONTRAKAN', 'name' => 'Gudang Kontrakan'],
            ['code' => 'RUMAH', 'name' => 'Gudang Rumah'],
            ['code' => 'WIP-CUT', 'name' => 'WIP Cutting'],
        ];

        foreach ($warehouses as $wh) {
            DB::table('warehouses')->updateOrInsert(
                ['code' => $wh['code']],
                [
                    'name' => $wh['name'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }
}
