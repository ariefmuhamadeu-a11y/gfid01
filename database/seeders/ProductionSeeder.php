<?php

namespace Database\Seeders;

use App\Models\CuttingBundle;
use App\Models\ExternalTransfer;
use App\Models\Item;
use App\Models\Lot;
use App\Models\ProductionBatch;
use App\Models\ProductionBatchMaterial;
use App\Models\Warehouse;
use Illuminate\Database\Seeder;

class ProductionSeeder extends Seeder
{
    public function run(): void
    {
        $transfer = ExternalTransfer::where('code', 'EXT-CUT-SEED-001')->first();
        $lot = Lot::where('code', 'LOT-FLC-001')->first();
        $items = Item::whereIn('code', ['K7BLK', 'K5BLK'])->get()->keyBy('code');
        $fromWarehouse = Warehouse::where('code', 'RUMAH')->first();
        $toWarehouse = Warehouse::where('code', 'WIP-CUT')->first();

        if (!$transfer || !$lot || $items->isEmpty() || !$fromWarehouse || !$toWarehouse) {
            return;
        }

        $batch = ProductionBatch::updateOrCreate(
            ['code' => 'BATCH-CUT-SEED-001'],
            [
                'stage' => 'cutting',
                'status' => 'waiting_qc',
                'operator_code' => 'MRF',
                'from_warehouse_id' => $fromWarehouse->id,
                'to_warehouse_id' => $toWarehouse->id,
                'external_transfer_id' => $transfer->id,
                'date_received' => now()->subDays(5)->toDateString(),
                'started_at' => now()->subDays(4),
                'notes' => 'Batch contoh dari external transfer seed.',
            ]
        );

        $transfer->update(['status' => 'BATCHED']);

        ProductionBatchMaterial::updateOrCreate(
            [
                'production_batch_id' => $batch->id,
                'lot_id' => $lot->id,
            ],
            [
                'item_id' => $lot->item_id,
                'item_code' => $lot->item->code,
                'qty_planned' => 25,
                'unit' => $lot->unit,
            ]
        );

        $bundleRows = [
            ['item' => $items['K7BLK'] ?? null, 'bundle_no' => 1, 'qty' => 40],
            ['item' => $items['K7BLK'] ?? null, 'bundle_no' => 2, 'qty' => 35],
            ['item' => $items['K5BLK'] ?? null, 'bundle_no' => 3, 'qty' => 30],
        ];

        foreach ($bundleRows as $row) {
            if (!$row['item']) {
                continue;
            }

            CuttingBundle::updateOrCreate(
                [
                    'production_batch_id' => $batch->id,
                    'bundle_no' => $row['bundle_no'],
                ],
                [
                    'lot_id' => $lot->id,
                    'item_id' => $row['item']->id,
                    'item_code' => $row['item']->code,
                    'bundle_code' => sprintf('BND-%s-%s-%03d', $row['item']->code, $batch->id, $row['bundle_no']),
                    'qty_cut' => $row['qty'],
                    'qty_ok' => null,
                    'qty_reject' => null,
                    'unit' => 'pcs',
                    'status' => 'sent_qc',
                    'current_warehouse_id' => $toWarehouse->id,
                ]
            );
        }
    }
}
