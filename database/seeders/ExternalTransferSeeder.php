<?php

namespace Database\Seeders;

use App\Models\ExternalTransfer;
use App\Models\ExternalTransferLine;
use App\Models\Lot;
use App\Models\Warehouse;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class ExternalTransferSeeder extends Seeder
{
    public function run(): void
    {
        $fromWarehouse = Warehouse::where('code', 'RUMAH')->first();
        $toWarehouse = Warehouse::where('code', 'WIP-CUT')->first();
        $lot = Lot::where('code', 'LOT-FLC-001')->first();

        if (!$fromWarehouse || !$toWarehouse || !$lot) {
            return;
        }

        $date = Carbon::now()->subDays(7);
        $transfer = ExternalTransfer::updateOrCreate(
            ['code' => 'EXT-CUT-SEED-001'],
            [
                'from_warehouse_id' => $fromWarehouse->id,
                'to_warehouse_id' => $toWarehouse->id,
                'date' => $date->toDateString(),
                'process' => 'cutting',
                'operator_code' => 'MRF',
                'transfer_type' => 'material',
                'direction' => 'out',
                'status' => 'sent',
                'notes' => 'Sample pengiriman kain ke vendor cutting.',
            ]
        );

        ExternalTransferLine::updateOrCreate(
            [
                'external_transfer_id' => $transfer->id,
                'lot_id' => $lot->id,
            ],
            [
                'item_id' => $lot->item_id,
                'item_code' => $lot->item->code,
                'qty' => 25,
                'unit' => $lot->unit,
                'notes' => 'Roll utama untuk cutting.',
            ]
        );
    }
}
