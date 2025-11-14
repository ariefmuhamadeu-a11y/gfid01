<?php

namespace App\Http\Controllers\Production;

use App\Http\Controllers\Controller;
use App\Models\ExternalTransfer;
use App\Models\Item;
use App\Models\ProductionBatch;
use App\Models\Warehouse;
use App\Models\WipItem;
use App\Services\InventoryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SewingController extends Controller
{
    /**
     * Daftar WIP hasil cutting yang siap dijahit.
     */
    public function index()
    {
        $wips = WipItem::with(['item', 'warehouse', 'productionBatch'])
            ->stage('cutting') // hanya WIP stage cutting
            ->available() // qty > 0
            ->orderBy('warehouse_id')
            ->orderBy('item_code')
            ->paginate(50);

        return view('production.sewing.index', compact('wips'));
    }

    /**
     * Form buat 1 batch sewing dari 1 WIP item (hasil cutting).
     */
    public function create($wipItemId)
    {
        $wip = WipItem::with(['item', 'warehouse', 'productionBatch', 'sourceLot'])
            ->stage('cutting')
            ->available()
            ->findOrFail($wipItemId);

        return view('production.sewing.create', [
            'wip' => $wip,
        ]);
    }

    /**
     * Simpan batch sewing:
     * - kurangi qty WIP cutting
     * - buat production_batch (sewing)
     * - buat WIP baru di stage 'sewing' (stok hasil jahit)
     */
    public function store($externalTransferId, Request $request)
    {
        // Ambil header + lines External Transfer (cutting)
        $t = ExternalTransfer::with('lines')
            ->where('process', 'cutting')
            ->whereIn('status', ['sent', 'received'])
            ->findOrFail($externalTransferId);

        // ================== VALIDASI INPUT ==================
        $data = $request->validate([
            'input_qty' => ['required', 'numeric', 'min:0'],
            'input_uom' => ['nullable', 'string', 'max:10'],
            'waste_qty' => ['nullable', 'numeric', 'min:0'],
            'remain_qty' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:1000'],

            'results' => ['required', 'array', 'min:1'],
            'results.*.item_code' => ['required', 'string', 'max:50'],
            'results.*.item_name' => ['nullable', 'string', 'max:255'],
            'results.*.qty' => ['required', 'numeric', 'min:1'],
        ], [
            'results.required' => 'Minimal satu baris hasil cutting harus diisi.',
        ]);

        // SUSUN hasil cutting: [item_code => total_qty]
        $outputItems = [];
        foreach ($data['results'] as $row) {
            $code = trim($row['item_code']);
            $qty = (float) $row['qty'];

            if ($code === '' || $qty <= 0) {
                continue;
            }

            if (!isset($outputItems[$code])) {
                $outputItems[$code] = 0;
            }
            $outputItems[$code] += $qty;
        }

        if (empty($outputItems)) {
            throw ValidationException::withMessages([
                'results' => 'Tidak ada data hasil cutting yang valid.',
            ]);
        }

        // Pastikan semua item hasil cutting sudah ada di master items
        $codes = array_keys($outputItems);
        $itemsFound = Item::whereIn('code', $codes)->pluck('id', 'code');

        $missing = array_diff($codes, $itemsFound->keys()->all());
        if (count($missing) > 0) {
            throw ValidationException::withMessages([
                'results' => 'Kode item berikut belum terdaftar di master items: ' . implode(', ', $missing),
            ]);
        }

        // Cari gudang utama / KONTRAKAN (gudang WIP & gudang stok kain)
        $mainWarehouseId = Warehouse::where('code', 'KONTRAKAN')->value('id');
        if (!$mainWarehouseId) {
            throw new \RuntimeException('Warehouse dengan code "KONTRAKAN" tidak ditemukan.');
        }

        DB::transaction(function () use ($t, $data, $outputItems, $itemsFound, $mainWarehouseId) {
            $today = now()->toDateString();

            // ========== 1) UPDATE STATUS EXTERNAL TRANSFER ==========
            if ($t->status === 'sent') {
                $t->status = 'received';
            }
            $t->status = 'done';
            $t->save();

            // ========== 2) INFO LOT & UOM DARI BARIS PERTAMA ==========
            $firstLine = $t->lines->first();
            $lotId = $firstLine?->lot_id;
            $uom = $data['input_uom'] ?: ($firstLine?->unit ?? 'kg');

            if (!$lotId) {
                throw new \RuntimeException('External Transfer cutting tidak memiliki LOT.');
            }

            $outputTotal = array_sum($outputItems);

            // ========== 3) GENERATE KODE BATCH CUTTING ==========
            $prefix = 'BCH-CUT';
            $countToday = ProductionBatch::whereDate('date', $today)
                ->where('process', 'cutting')
                ->count();

            $seq = str_pad($countToday + 1, 3, '0', STR_PAD_LEFT);
            $code = $prefix . '-' . date('ymd', strtotime($today)) . '-' . $seq;

            // ========== 4) SIMPAN PRODUCTION BATCH (CUTTING) ==========
            $batch = ProductionBatch::create([
                'code' => $code,
                'date' => $today,
                'process' => 'cutting',
                'status' => 'done',

                'external_transfer_id' => $t->id,
                'lot_id' => $lotId,

                // proses fisik di vendor, tapi hasil dianggap balik ke KONTRAKAN
                'from_warehouse_id' => $t->to_warehouse_id, // gudang vendor (CUT-EXT-XXX)
                'to_warehouse_id' => $mainWarehouseId, // KONTRAKAN

                'operator_code' => $t->operator_code,

                'input_qty' => (float) $data['input_qty'],
                'input_uom' => $uom,

                'output_total_pcs' => $outputTotal,
                'output_items_json' => $outputItems,

                'waste_qty' => (float) ($data['waste_qty'] ?? 0),
                'remain_qty' => (float) ($data['remain_qty'] ?? 0),

                'notes' => $data['notes'] ?? null,
                'created_by' => auth()->id(),
                'updated_by' => auth()->id(),
            ]);

            // ========== 5) REDUCE STOK LOT KAIN DI KONTRAKAN ==========
            // Logika pemakaian kain:
            // dipakai = input_qty - remain_qty
            $inputQty = (float) $data['input_qty'];
            $remainQty = (float) ($data['remain_qty'] ?? 0);
            $usedQty = max($inputQty - $remainQty, 0);

            if ($usedQty > 0) {
                InventoryService::reduceStockLot([
                    'warehouse_id' => $mainWarehouseId, // stok kain dianggap milik KONTRAKAN
                    'lot_id' => $lotId,
                    'unit' => $uom,
                    'qty' => $usedQty,
                    'type' => 'CUTTING_USE', // bebas, nanti tinggal filter di report
                    'ref_code' => $batch->code,
                    'note' => 'Pemakaian kain untuk cutting ' . $batch->code,
                    'date' => $today,
                    'category' => 'rawmaterial', // kalau mau pakai kategori
                ]);
            }

            // ========== 6) BUAT WIP CUTTING DI GUDANG KONTRAKAN ==========
            // Ini stok yang nanti kamu IKET & distribusi ke penjahit rumah.
            $warehouseId = $mainWarehouseId;
            $sourceLotId = $lotId;

            foreach ($outputItems as $code => $qty) {
                $itemId = $itemsFound[$code] ?? null;
                if (!$itemId) {
                    continue;
                }

                WipItem::create([
                    'production_batch_id' => $batch->id,
                    'item_id' => $itemId,
                    'item_code' => $code,
                    'warehouse_id' => $warehouseId, // KONTRAKAN
                    'source_lot_id' => $sourceLotId,
                    'stage' => 'cutting',
                    'qty' => $qty,
                    'notes' => 'WIP cutting di KONTRAKAN (siap diikat & dibagi ke penjahit) - ' . $batch->code,
                ]);
            }
        });

        return redirect()
            ->route('vendor_cutting.index')
            ->with('success', "Hasil cutting untuk {$t->code} berhasil disimpan, stok kain LOT berkurang, dan WIP dibuat di KONTRAKAN.");
    }
}
