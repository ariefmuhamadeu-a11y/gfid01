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

class VendorCuttingController extends Controller
{
    /**
     * List dokumen external transfer (cutting) yang siap / sedang diproses vendor.
     * Hanya ambil yang process = cutting dan status sent / received.
     */
    public function index()
    {
        $rows = ExternalTransfer::withCount('lines')
            ->where('process', 'cutting')
            ->whereIn('status', ['sent', 'received'])
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->paginate(30);

        return view('production.vendor_cutting.index', compact('rows'));
    }

    /**
     * Form proses cutting untuk satu external transfer.
     * - tampilkan info pengiriman & lot kain
     * - input hasil cutting (barang setengah jadi / WIP)
     */
    public function create(ExternalTransfer $externalTransfer)
    {
        // safety: hanya boleh cutting & status sent/received
        if ($externalTransfer->process !== 'cutting' || !in_array($externalTransfer->status, ['sent', 'received'])) {
            return redirect()
                ->route('vendor-cutting.index')
                ->with('error', 'Dokumen ini tidak bisa diproses cutting.');
        }

        $externalTransfer->load(['fromWarehouse', 'toWarehouse', 'lines.lot', 'lines.item']);

        // Hitung total qty kain (input)
        $inputQty = $externalTransfer->lines->sum('qty');
        $inputUom = optional($externalTransfer->lines->first())->uom ?? 'kg';

        // Item barang jadi / WIP yang boleh dipilih sebagai hasil cutting.
        // Untuk sementara, ambil semua items; nanti bisa difilter by type.
        $finishedItems = Item::orderBy('code')
            ->select('id', 'code', 'name')
            ->limit(500)
            ->get();

        return view('production.vendor_cutting.create', [
            't' => $externalTransfer,
            'inputQty' => $inputQty,
            'inputUom' => $inputUom,
            'finishedItems' => $finishedItems,
        ]);
    }

    /**
     * Simpan hasil cutting:
     * - jika status awal sent → ubah ke received (konfirmasi bahan diterima)
     * - buat 1 production_batch (process = cutting)
     */
    public function store($externalTransferId, Request $request)
    {
        // Ambil header + lines External Transfer (cutting)
        $t = ExternalTransfer::with('lines')
            ->where('process', 'cutting')
            ->whereIn('status', ['sent', 'received'])
            ->findOrFail($externalTransferId);

        // =============== VALIDASI INPUT FORM ===============
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

        // Susun hasil cutting: [item_code => total_qty]
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

        // Cari gudang utama / KONTRAKAN (gudang stok kain & WIP)
        $mainWarehouseId = Warehouse::where('code', 'KONTRAKAN')->value('id');
        if (!$mainWarehouseId) {
            throw new \RuntimeException('Warehouse dengan code "KONTRAKAN" tidak ditemukan.');
        }

        DB::transaction(function () use ($t, $data, $outputItems, $itemsFound, $mainWarehouseId) {
            $today = now()->toDateString();

            // ===== 1) UPDATE STATUS EXTERNAL TRANSFER =====
            if ($t->status === 'sent') {
                $t->status = 'received';
            }
            $t->status = 'done';
            $t->save();

            // ===== 2) INFO LOT & UOM DARI BARIS PERTAMA =====
            $firstLine = $t->lines->first();
            $lotId = $firstLine?->lot_id;
            $uom = $data['input_uom'] ?: ($firstLine?->unit ?? 'kg');

            if (!$lotId) {
                throw new \RuntimeException('External Transfer cutting tidak memiliki LOT.');
            }

            $outputTotal = array_sum($outputItems);

            // ===== 3) GENERATE KODE BATCH CUTTING =====
            $prefix = 'BCH-CUT';
            $countToday = ProductionBatch::whereDate('date', $today)
                ->where('process', 'cutting')
                ->count();

            $seq = str_pad($countToday + 1, 3, '0', STR_PAD_LEFT);
            $code = $prefix . '-' . date('ymd', strtotime($today)) . '-' . $seq;

            // ===== 4) SIMPAN PRODUCTION BATCH (CUTTING) =====
            $batch = ProductionBatch::create([
                'code' => $code,
                'date' => $today,
                'process' => 'cutting',
                'status' => 'done',

                'external_transfer_id' => $t->id,
                'lot_id' => $lotId,

                // proses fisik terjadi di vendor (to_warehouse dari ET)
                'from_warehouse_id' => $t->to_warehouse_id,
                // hasil & WIP kita anggap ada di KONTRAKAN
                'to_warehouse_id' => $mainWarehouseId,

                'operator_code' => $t->operator_code,

                'input_qty' => (float) $data['input_qty'],
                'input_uom' => $uom,

                'output_total_pcs' => $outputTotal,
                'output_items_json' => $outputItems, // cast json di model kalau mau

                'waste_qty' => (float) ($data['waste_qty'] ?? 0),
                'remain_qty' => (float) ($data['remain_qty'] ?? 0),

                'notes' => $data['notes'] ?? null,
                'created_by' => auth()->id(),
                'updated_by' => auth()->id(),
            ]);

            // ===== 5) KURANGI STOK KAIN LOT DI KONTRAKAN =====
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
                    'type' => 'CUTTING_USE',
                    'ref_code' => $batch->code,
                    'note' => 'Pemakaian kain untuk cutting ' . $batch->code,
                    'date' => $today,
                    'category' => 'rawmaterial',
                ]);
            }

            // ===== 6) BUAT WIP CUTTING DI GUDANG KONTRAKAN =====
            // Ini stok BSJ / potongan siap jahit (belum dibagikan ke penjahit).
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
                    // kalau sudah ada kolom ini dari migration QC:
                    'qc_status' => 'pending',
                    'qc_notes' => null,
                    'notes' => 'WIP cutting di KONTRAKAN (stok belum jahit) - ' . $batch->code,
                ]);
            }
        });

        return redirect()
            ->route('vendor-cutting.index')
            ->with('success', "Hasil cutting untuk {$t->code} berhasil disimpan, stok kain LOT berkurang, dan WIP dibuat di KONTRAKAN.");
    }

    /**
     * Generate kode batch cutting:
     * BCH-CUT-YYMMDD-###
     */
    protected function generateBatchCode(string $date): string
    {
        $d = Carbon::parse($date);
        $prefix = 'BCH-CUT';
        $ymd = $d->format('ymd');

        $countToday = ProductionBatch::where('process', 'cutting')
            ->whereDate('date', $d->toDateString())
            ->count();

        $seq = str_pad($countToday + 1, 3, '0', STR_PAD_LEFT);

        return "{$prefix}-{$ymd}-{$seq}";
    }
}
