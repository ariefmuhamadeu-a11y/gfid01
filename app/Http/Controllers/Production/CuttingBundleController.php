<?php

namespace App\Http\Controllers\Production;

use App\Http\Controllers\Controller;
use App\Models\CuttingBundle;
use App\Models\Item;
use App\Models\ProductionBatch;
use Illuminate\Http\Request;

class CuttingBundleController extends Controller
{
    public function index(Request $request)
    {
        $q = CuttingBundle::with(['productionBatch', 'lot', 'item'])
            ->orderByDesc('updated_at');

        if ($batchId = $request->get('batch_id')) {
            $q->where('production_batch_id', $batchId);
        }

        $bundles = $q->paginate(20)->appends($request->only('batch_id'));

        return view('production.cutting_bundles.index', compact('bundles', 'batchId'));
    }

    public function create(ProductionBatch $batch)
    {
        if ($batch->stage !== 'cutting') {
            abort(404, 'Batch ini bukan batch cutting.');
        }

        $batch->load(['materials.lot', 'materials.item', 'bundles']);

        $lots = $batch->materials->map(fn($m) => $m->lot)->unique('id')->values();
        $items = Item::orderBy('code')->get();
        $bundles = $batch->bundles()->with(['lot', 'item'])->orderBy('bundle_no')->get();

        $formAction = route('production.cutting_bundles.store', $batch->id);
        $backRoute = route('production.vendor_cutting.batches.show', $batch->id);

        return view('production.vendor_cutting.bundles_edit', compact(
            'batch',
            'lots',
            'items',
            'bundles',
            'formAction',
            'backRoute'
        ));
    }

    public function store(Request $request, ProductionBatch $batch)
    {
        if ($batch->stage !== 'cutting') {
            abort(404, 'Batch ini bukan batch cutting.');
        }

        $data = $request->validate([
            'bundles' => ['required', 'array', 'min:1'],
            'bundles.*.lot_id' => ['required', 'integer', 'exists:lots,id'],
            'bundles.*.item_id' => ['required', 'integer', 'exists:items,id'],
            'bundles.*.bundle_code' => ['nullable', 'string', 'max:64'],
            'bundles.*.bundle_no' => ['nullable', 'integer'],
            'bundles.*.qty_cut' => ['required', 'numeric', 'min:1'],
            'bundles.*.unit' => ['required', 'string', 'max:16'],
            'bundles.*.notes' => ['nullable', 'string', 'max:255'],
        ]);

        $batch->bundles()->delete();

        $seq = 1;
        foreach ($data['bundles'] as $row) {
            $item = Item::find($row['item_id']);

            $bundleNo = $row['bundle_no'] ?? $seq;
            $bundleCode = $row['bundle_code'] ?: $this->generateBundleCode($batch, $item, $bundleNo);

            CuttingBundle::create([
                'production_batch_id' => $batch->id,
                'lot_id' => $row['lot_id'],
                'item_id' => $row['item_id'],
                'item_code' => $item->code,
                'bundle_code' => $bundleCode,
                'bundle_no' => $bundleNo,
                'qty_cut' => $row['qty_cut'],
                'unit' => $row['unit'],
                'status' => 'cut',
                'notes' => $row['notes'] ?? null,
            ]);

            $seq++;
        }

        if ($batch->status === 'received') {
            $batch->update([
                'status' => 'in_progress',
                'started_at' => now(),
            ]);
        }

        return redirect()
            ->route('production.vendor_cutting.batches.show', $batch->id)
            ->with('success', 'Hasil cutting per iket berhasil disimpan.');
    }

    protected function generateBundleCode(ProductionBatch $batch, Item $item, int $bundleNo): string
    {
        return sprintf(
            'BND-%s-%s-%03d',
            $item->code,
            $batch->id,
            $bundleNo
        );
    }
}
