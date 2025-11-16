<?php

namespace Database\Seeders;

use App\Models\InventoryStock;
use App\Models\Item;
use App\Models\Lot;
use App\Models\Warehouse;
use Illuminate\Database\Seeder;

class LotSeeder extends Seeder
{
    public function run(): void
    {
        $items = Item::whereIn('code', ['FLC280BLK', 'RIBBLK'])->get()->keyBy('code');
        $warehouses = Warehouse::whereIn('code', ['RUMAH', 'KONTRAKAN'])->get()->keyBy('code');

        if ($items->isEmpty() || $warehouses->isEmpty()) {
            return;
        }

        $lotsData = [
            [
                'code' => 'LOT-FLC-001',
                'item' => $items['FLC280BLK'] ?? null,
                'unit' => 'kg',
                'initial_qty' => 50,
                'date' => now()->subDays(14)->toDateString(),
                'warehouse' => $warehouses['RUMAH'] ?? null,
            ],
            [
                'code' => 'LOT-RIB-001',
                'item' => $items['RIBBLK'] ?? null,
                'unit' => 'kg',
                'initial_qty' => 10,
                'date' => now()->subDays(10)->toDateString(),
                'warehouse' => $warehouses['KONTRAKAN'] ?? null,
            ],
        ];

        foreach ($lotsData as $lotRow) {
            if (!$lotRow['item'] || !$lotRow['warehouse']) {
                continue;
            }

            $lot = Lot::updateOrCreate(
                ['code' => $lotRow['code']],
                [
                    'item_id' => $lotRow['item']->id,
                    'unit' => $lotRow['unit'],
                    'initial_qty' => $lotRow['initial_qty'],
                    'date' => $lotRow['date'],
                ]
            );

            InventoryStock::updateOrCreate(
                [
                    'warehouse_id' => $lotRow['warehouse']->id,
                    'lot_id' => $lot->id,
                    'unit' => $lotRow['unit'],
                ],
                [
                    'item_id' => $lotRow['item']->id,
                    'item_code' => $lotRow['item']->code,
                    'qty' => $lotRow['initial_qty'],
                ]
            );
        }
    }
}
