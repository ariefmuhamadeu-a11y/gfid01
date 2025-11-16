<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\CuttingBundle;
use App\Models\Employee;
use App\Models\ExternalTransfer;
use App\Models\ExternalTransferBundleLine;
use App\Models\ExternalTransferLine;
use App\Models\Lot;
use App\Models\Warehouse;
use App\Services\InventoryService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ExternalTransferController extends Controller
{
    public function index()
    {
        $transfers = ExternalTransfer::with(['fromWarehouse', 'toWarehouse'])
            ->orderBy('date', 'desc')
            ->orderBy('code', 'desc')
            ->paginate(20);

        return view('inventory.external_transfers.index', compact('transfers'));
    }

    public function create(Request $request)
    {
        $warehouses = Warehouse::orderBy('name')->get();

        $transferType = $request->get('transfer_type', $request->old('transfer_type', 'material'));
        $defaultProcess = $transferType === 'sewing_bundle'
            ? 'sewing'
            : $request->get('process', 'cutting');

        $defaultFromWarehouse = $warehouses->firstWhere('code', 'KONTRAKAN');
        $fromWarehouseId = $request->get('from_warehouse_id');

        if (!$fromWarehouseId && $defaultFromWarehouse) {
            $fromWarehouseId = $defaultFromWarehouse->id;
        }

        $defaultFromWarehouseId = $fromWarehouseId;

        $employees = Employee::orderBy('name')->get();
        $operatorCode = $request->get('operator_code', $request->old('operator_code'));

        $lots = $this->loadLotsWithStock($fromWarehouseId);

        $autoToWarehouseCode = null;
        if ($operatorCode) {
            $autoToWarehouseCode = $this->generateAutoToWarehouseCode($defaultProcess, $operatorCode);
        }

        $bundles = $this->loadAvailableBundles($fromWarehouseId);

        return view('inventory.external_transfers.create', [
            'warehouses' => $warehouses,
            'lots' => $lots,
            'employees' => $employees,
            'defaultProcess' => $defaultProcess,
            'defaultFromWarehouseId' => $defaultFromWarehouseId,
            'autoToWarehouseCode' => $autoToWarehouseCode,
            'transferType' => $transferType,
            'bundles' => $bundles,
        ]);
    }

    public function edit(ExternalTransfer $transfer)
    {
        // Hanya ubah status & catatan, tidak ubah lines / stok
        return view('inventory.external_transfers.edit', compact('transfer'));
    }
    public function show(ExternalTransfer $transfer)
    {

        $transfer->load([
            'fromWarehouse',
            'toWarehouse',
            'lines.lot.item',
            'bundleLines.cuttingBundle.item',
        ]);
        return view('inventory.external_transfers.show', compact('transfer'));
    }
    public function update(Request $request, ExternalTransfer $transfer)
    {
        $data = $request->validate([
            'status' => ['required', 'in:sent,received,completed,cancelled'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $transfer->update($data);

        return redirect()
            ->route('inventory.external_transfers.show', $transfer->id)
            ->with('success', 'Status External Transfer berhasil diperbarui.');
    }

    public function receive(ExternalTransfer $transfer)
    {
        if ($transfer->status !== 'sent') {
            return redirect()
                ->route('inventory.external_transfers.show', $transfer->id)
                ->with('error', 'Hanya dokumen dengan status sent yang dapat diterima.');
        }

        $transfer->load(['bundleLines.cuttingBundle']);

        DB::transaction(function () use ($transfer) {
            if ($transfer->transfer_type === 'sewing_bundle') {
                foreach ($transfer->bundleLines as $line) {
                    $bundle = $line->cuttingBundle;
                    if (!$bundle) {
                        continue;
                    }

                    $qty = (float) $line->qty;

                    $bundle->update([
                        'qty_in_transfer' => max(0, (float) $bundle->qty_in_transfer - $qty),
                        'qty_reserved_for_sewing' => max(0, (float) $bundle->qty_reserved_for_sewing - $qty),
                        'current_warehouse_id' => $transfer->to_warehouse_id,
                        'sewing_status' => 'in_sewing',
                    ]);

                    // TODO: sambungkan ke InventoryService::transferBundle jika sudah tersedia
                }
            }

            $transfer->update(['status' => 'received']);
        });

        return redirect()
            ->route('inventory.external_transfers.show', $transfer->id)
            ->with('success', 'Transfer berhasil diterima.');
    }

/**
 * Map process + operator_code → kode gudang vendor, misal:
 * cutting + MRF → CUT-EXT-MRF
 */
    protected function generateAutoToWarehouseCode(string $process, string $operatorCode): string
    {
        $proc = strtolower($process);

        switch ($proc) {
            case 'cutting':
                $proc3 = 'CUT';
                break;
            case 'sewing':
                $proc3 = 'SEW';
                break;
            case 'finishing':
                $proc3 = 'FIN';
                break;
            case 'other':
                $proc3 = 'OTH';
                break;
            default:
                $proc3 = strtoupper(substr($proc, 0, 3));
                break;
        }

        $op3 = strtoupper(substr(preg_replace('/[^A-Z0-9]/i', '', $operatorCode), 0, 3));

        return "{$proc3}-EXT-{$op3}";
    }

    protected function generateTransferCode(string $process, Carbon $date): string
    {
        $prefix = match ($process) {
            'cutting' => 'EXT-CUT',
            'sewing' => 'EXT-SEW',
            'finishing' => 'EXT-FIN',
            default => 'EXT-OTH',
        };

        $ymd = $date->format('ymd');

        $countToday = ExternalTransfer::where('process', $process)
            ->whereDate('date', $date->toDateString())
            ->count();

        $seq = str_pad($countToday + 1, 3, '0', STR_PAD_LEFT);

        return "{$prefix}-{$ymd}-{$seq}";
    }

    protected function resolveDestinationWarehouse(string $transferType, string $process, string $operatorCode): int
    {
        if ($transferType === 'sewing_bundle') {
            $warehouse = Warehouse::firstOrCreate(
                ['code' => 'WIP-SEW'],
                ['name' => 'WIP Sewing']
            );

            return $warehouse->id;
        }

        $toWarehouseCode = $this->generateAutoToWarehouseCode($process, $operatorCode);

        return Warehouse::firstOrCreate(
            ['code' => $toWarehouseCode],
            [
                'name' => $toWarehouseCode . ' (Vendor)',
                'type' => 'external',
            ]
        )->id;
    }

    protected function loadLotsWithStock(?int $fromWarehouseId)
    {
        $lotsQuery = Lot::query()
            ->selectRaw('
            lots.id,
            lots.item_id,
            lots.code as lot_code,
            lots.unit as uom,
            items.code as item_code,
            items.name as item_name,
            COALESCE(SUM(inventory_stocks.qty), 0) as stock_remain
        ')
            ->join('items', 'items.id', '=', 'lots.item_id')
            ->leftJoin('inventory_stocks', function ($q) use ($fromWarehouseId) {
                $q->on('inventory_stocks.lot_id', '=', 'lots.id');
                if ($fromWarehouseId) {
                    $q->where('inventory_stocks.warehouse_id', $fromWarehouseId);
                }
            })
            ->groupBy('lots.id', 'lots.item_id', 'lots.code', 'lots.unit', 'items.code', 'items.name')
            ->orderBy('lots.code');

        return $lotsQuery
            ->having('stock_remain', '>', 0)
            ->get();
    }

    protected function loadAvailableBundles(?int $warehouseId)
    {
        $query = CuttingBundle::query()
            ->with(['item'])
            ->where('status', 'qc_done')
            ->whereRaw('COALESCE(qty_ok,0) - COALESCE(qty_reserved_for_sewing,0) - COALESCE(qty_in_transfer,0) - COALESCE(qty_sewn_ok,0) - COALESCE(qty_sewn_reject,0) > 0');

        if ($warehouseId) {
            $query->where(function ($q) use ($warehouseId) {
                $q->whereNull('current_warehouse_id')
                    ->orWhere('current_warehouse_id', $warehouseId);
            });
        }

        return $query
            ->orderBy('bundle_code')
            ->limit(500)
            ->get();
    }
    public function store(Request $request)
    {
        $transferType = $request->input('transfer_type', 'material');

        $rules = [
            'date' => ['required', 'date'],
            'process' => ['required', 'in:cutting,sewing,finishing,other'],
            'operator_code' => ['required', 'string', 'max:50'],
            'from_warehouse_id' => ['required', 'integer', 'exists:warehouses,id'],
            'notes' => ['nullable', 'string', 'max:500'],
            'transfer_type' => ['required', Rule::in(['material', 'sewing_bundle'])],
        ];

        if ($transferType === 'sewing_bundle') {
            $rules = array_merge($rules, [
                'bundle_lines' => ['required', 'array', 'min:1'],
                'bundle_lines.*.cutting_bundle_id' => ['required', 'integer', 'exists:cutting_bundles,id'],
                'bundle_lines.*.qty' => ['required', 'numeric', 'gt:0'],
                'bundle_lines.*.notes' => ['nullable', 'string', 'max:500'],
            ]);
        } else {
            $rules = array_merge($rules, [
                'lines' => ['required', 'array', 'min:1'],
                'lines.*.lot_id' => ['required', 'integer', 'exists:lots,id'],
                'lines.*.item_id' => ['required', 'integer', 'exists:items,id'],
                'lines.*.qty' => ['required', 'numeric', 'gt:0'],
                'lines.*.uom' => ['nullable', 'string', 'max:16'],
                'lines.*.notes' => ['nullable', 'string', 'max:500'],
            ]);
        }

        $validated = $request->validate($rules, [
            'lines.required' => 'Minimal harus ada 1 LOT yang dipilih.',
            'bundle_lines.required' => 'Minimal harus ada 1 bundle yang dipilih.',
        ]);

        $user = Auth::user();

        DB::transaction(function () use ($validated, $user) {
            $transferType = $validated['transfer_type'];
            $date = Carbon::parse($validated['date']);
            $process = $transferType === 'sewing_bundle' ? 'sewing' : $validated['process'];
            $operatorCode = $validated['operator_code'];
            $fromWarehouse = (int) $validated['from_warehouse_id'];
            $notes = $validated['notes'] ?? null;

            $toWarehouseId = $this->resolveDestinationWarehouse($transferType, $process, $operatorCode);
            $code = $this->generateTransferCode($process, $date);

            /** @var ExternalTransfer $transfer */
            $transfer = ExternalTransfer::create([
                'code' => $code,
                'date' => $date->toDateString(),
                'process' => $process,
                'operator_code' => $operatorCode,
                'transfer_type' => $transferType,
                'direction' => 'out',
                'from_warehouse_id' => $fromWarehouse,
                'to_warehouse_id' => $toWarehouseId,
                'status' => 'sent',
                'notes' => $notes,
                'created_by' => $user?->id,
            ]);

            if ($transferType === 'sewing_bundle') {
                foreach ($validated['bundle_lines'] as $line) {
                    $qty = (float) ($line['qty'] ?? 0);
                    if ($qty <= 0) {
                        continue;
                    }

                    /** @var CuttingBundle $bundle */
                    $bundle = CuttingBundle::with('item')
                        ->lockForUpdate()
                        ->findOrFail($line['cutting_bundle_id']);

                    $available = $bundle->availableQtyForSewing();
                    if ($qty > $available) {
                        throw \Illuminate\Validation\ValidationException::withMessages([
                            'bundle_lines' => [
                                "Qty kirim untuk bundle {$bundle->bundle_code} melebihi stok tersedia ({$available}).",
                            ],
                        ]);
                    }

                    ExternalTransferBundleLine::create([
                        'external_transfer_id' => $transfer->id,
                        'cutting_bundle_id' => $bundle->id,
                        'qty' => $qty,
                        'unit' => $bundle->unit ?? 'pcs',
                        'notes' => $line['notes'] ?? null,
                    ]);

                    $bundle->update([
                        'qty_reserved_for_sewing' => (float) $bundle->qty_reserved_for_sewing + $qty,
                        'qty_in_transfer' => (float) $bundle->qty_in_transfer + $qty,
                        'sewing_status' => 'in_transfer',
                        'current_warehouse_id' => $fromWarehouse,
                    ]);
                }

                // TODO: sambungkan ke InventoryService untuk mutasi stok bundle jika sudah siap
            } else {
                foreach ($validated['lines'] as $line) {
                    $qty = (float) ($line['qty'] ?? 0);
                    if ($qty <= 0) {
                        continue;
                    }

                    /** @var Lot $lot */
                    $lot = Lot::with('item')->findOrFail($line['lot_id']);
                    $uom = $line['uom'] ?? $lot->unit ?? ($lot->item->default_unit ?? 'm');

                    ExternalTransferLine::create([
                        'external_transfer_id' => $transfer->id,
                        'lot_id' => $lot->id,
                        'item_id' => $lot->item_id,
                        'item_code' => $lot->item->code,
                        'qty' => $qty,
                        'unit' => $uom,
                        'notes' => $line['notes'] ?? null,
                    ]);

                    InventoryService::transferLot([
                        'from_warehouse_id' => $fromWarehouse,
                        'to_warehouse_id' => $toWarehouseId,
                        'lot_id' => $lot->id,
                        'item_id' => $lot->item_id,
                        'item_code' => $lot->item->code,
                        'unit' => $uom,
                        'qty' => $qty,
                        'date' => $date->toDateString(),
                        'ref_code' => $code,
                        'category' => 'rawmaterial',
                    ]);
                }
            }
        });

        return redirect()
            ->route('inventory.external_transfers.index')
            ->with('success', 'External Transfer berhasil dibuat.');
    }

    // index() dll bisa kamu isi belakangan
}
