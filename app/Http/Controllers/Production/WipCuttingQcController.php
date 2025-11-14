<?php

namespace App\Http\Controllers\Production;

use App\Http\Controllers\Controller;
use App\Models\Lot;
use App\Models\WipComponent;
use App\Models\WipItem;
use App\Services\InventoryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class WipCuttingQcController extends Controller
{
    /**
     * Daftar WIP Cutting yang belum di-QC (pending).
     */
    public function index(Request $request)
    {
        $q = WipItem::with(['item', 'warehouse', 'productionBatch'])
            ->stageCutting()
            ->qcPending()
            ->orderBy('created_at', 'desc');

        if ($code = $request->get('item_code')) {
            $q->where('item_code', 'like', '%' . $code . '%');
        }

        $wips = $q->paginate(50)->withQueryString();

        return view('production.wip_cutting_qc.index', compact('wips'));
    }

    /**
     * Form QC untuk satu WIP Cutting.
     */
    public function edit(WipItem $wipItem)
    {
        $wipItem->load(['item', 'warehouse', 'productionBatch', 'components.lot']);

        if ($wipItem->stage !== 'cutting') {
            abort(404, 'WIP ini bukan stage cutting.');
        }

        return view('production.wip_cutting_qc.edit', [
            'wip' => $wipItem,
        ]);
    }

    /**
     * Proses QC:
     * - update qc_status, qc_notes
     * - opsional adjust qty WIP (qty_ok)
     * - reduce stok rib/karet (komponen) via InventoryService
     * - simpan wip_components
     */
    public function update(WipItem $wipItem, Request $request)
    {
        if ($wipItem->stage !== 'cutting') {
            abort(404, 'WIP ini bukan stage cutting.');
        }

        $data = $request->validate([
            'qc_status' => ['required', 'in:approved,rejected'],
            'qc_notes' => ['nullable', 'string', 'max:255'],
            'qty_ok' => ['nullable', 'numeric', 'min:0'],
            'qty_reject' => ['nullable', 'numeric', 'min:0'],

            'components' => ['nullable', 'array'],
            'components.*.lot_id' => ['required_with:components', 'integer', 'min:1'],
            'components.*.qty' => ['required_with:components', 'numeric', 'min:0.0001'],
            'components.*.unit' => ['required_with:components', 'string', 'max:16'],
            'components.*.type' => ['nullable', 'string', 'max:30'],
        ]);

        // Pastikan qty_ok (kalau diisi) tidak lebih besar dari stok WIP sekarang
        $currentQty = (float) $wipItem->qty;
        $qtyOk = $data['qty_ok'] !== null ? (float) $data['qty_ok'] : $currentQty;

        if ($qtyOk > $currentQty) {
            throw ValidationException::withMessages([
                'qty_ok' => 'Qty OK tidak boleh melebihi stok WIP saat ini (' . $currentQty . ').',
            ]);
        }

        DB::transaction(function () use ($wipItem, $data, $qtyOk, $currentQty) {
            $wipItem->refresh();

            // 1) Update qty WIP (kalau qty_ok diisi & berbeda)
            if ($qtyOk !== $currentQty) {
                $wipItem->qty = $qtyOk;
            }

            // 2) Update status QC
            $wipItem->qc_status = $data['qc_status'];
            $wipItem->qc_notes = $data['qc_notes'] ?? null;
            $wipItem->save();

            // 3) Kalau ada komponen (rib/karet), kurangi stok dan simpan
            $components = $data['components'] ?? [];

            if (!empty($components)) {
                $warehouseId = $wipItem->warehouse_id;
                $date = now()->toDateString();
                $batchCode = $wipItem->productionBatch?->code ?? null;

                foreach ($components as $row) {
                    $lotId = (int) $row['lot_id'];
                    $qty = (float) $row['qty'];
                    $unit = $row['unit'];
                    $type = $row['type'] ?? null;

                    if ($qty <= 0) {
                        continue;
                    }

                    // Ambil info lot + item
                    $lot = Lot::with('item')->find($lotId);
                    if (!$lot || !$lot->item) {
                        throw new \RuntimeException("LOT {$lotId} tidak ditemukan atau tidak punya item.");
                    }

                    // 3a) Kurangi stok komponen dari inventory (rib/karet/karet pinggang, dll.)
                    InventoryService::reduceStockLot([
                        'warehouse_id' => $warehouseId,
                        'lot_id' => $lotId,
                        'unit' => $unit,
                        'qty' => $qty,
                        'type' => 'WIP_COMPONENT_USE',
                        'ref_code' => $batchCode ?: ('WIP-' . $wipItem->id),
                        'note' => 'Komponen ' . ($type ?: $lot->item->code) . ' untuk WIP ' . $wipItem->item_code,
                        'date' => $date,
                        'category' => 'support', // boleh diganti rib/elastic kalau mau
                    ]);

                    // 3b) Simpan ke wip_components
                    WipComponent::create([
                        'wip_item_id' => $wipItem->id,
                        'lot_id' => $lotId,
                        'item_id' => $lot->item_id,
                        'item_code' => $lot->item->code,
                        'qty' => $qty,
                        'unit' => $unit,
                        'type' => $type,
                    ]);
                }
            }
        });

        return redirect()
            ->route('wip_cutting_qc.index')
            ->with('success', 'QC WIP Cutting berhasil disimpan.');
    }
}
