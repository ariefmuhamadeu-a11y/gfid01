<?php

namespace App\Http\Controllers\Production;

use App\Http\Controllers\Controller;
use App\Models\CuttingBundle;
use App\Models\ProductionBatch;
use Illuminate\Http\Request;

class WipCuttingQcController extends Controller
{
    /**
     * Daftar batch cutting yang menunggu QC.
     */
    public function index()
    {
        $batches = ProductionBatch::withCount(['bundles'])
            ->where('stage', 'cutting')
            ->where('status', 'waiting_qc')
            ->orderByDesc('date_received')
            ->paginate(20);

        return view('production.wip_cutting_qc.index', compact('batches'));
    }

    public function show(ProductionBatch $batch)
    {
        // Pastikan ini batch cutting
        if ($batch->stage !== 'cutting') {
            abort(404, 'Batch ini bukan batch cutting.');
        }

        // Kalau mau batasi hanya yang sudah di-QC:
        // if (! in_array($batch->status, ['waiting_qc', 'qc_done'])) {
        //     abort(403, 'Batch ini belum dikirim ke QC.');
        // }

        $batch->load([
            'materials.lot',
            'materials.item',
            'bundles.lot',
            'bundles.item',
            'fromWarehouse',
            'toWarehouse',
            'externalTransfer',
        ]);

        return view('production.wip_cutting_qc.show', compact('batch'));
    }

    /**
     * Form QC untuk satu batch.
     */
    public function edit(ProductionBatch $batch)
    {
        if ($batch->stage !== 'cutting') {
            abort(404, 'Batch ini bukan batch cutting.');
        }

        if ($batch->status !== 'waiting_qc' && $batch->status !== 'qc_in_progress') {
            abort(403, 'Batch ini tidak dalam status waiting_qc.');
        }

        $batch->load([
            'materials.lot',
            'materials.item',
            'bundles.lot',
            'bundles.item',
        ]);

        return view('production.wip_cutting_qc.edit', compact('batch'));
    }

    /**
     * Proses QC: mark tiap iket OK/Reject, buat/ubah WIP.
     */
    public function update(Request $request, ProductionBatch $batch)
    {
        $batch->load('bundles'); // sudah eager load semua bundles di batch ini

        $data = $request->validate([
            'bundles' => ['required', 'array'],
            'bundles.*.id' => ['required', 'integer'],
            'bundles.*.qty_reject' => ['required', 'numeric', 'min:0'],
            'bundles.*.qc_notes' => ['nullable', 'string', 'max:255'],
        ]);

        $totalOk = 0;
        $totalReject = 0;

        foreach ($data['bundles'] as $row) {
            /** @var \App\Models\CuttingBundle|null $bundle */
            $bundle = $batch->bundles->firstWhere('id', $row['id']);

            if (!$bundle) {
                continue; // jaga-jaga kalau id bundlenya ga ketemu
            }

            $qtyCut = (float) $bundle->qty_cut;
            $qtyReject = (float) $row['qty_reject'];

            // VALIDASI: reject tidak boleh lebih besar dari qty_cut
            if ($qtyReject > $qtyCut) {
                return back()
                    ->withErrors("Total reject untuk bundle {$bundle->bundle_code} melebihi qty cut ({$qtyCut}).")
                    ->withInput();
            }

            $qtyOk = max($qtyCut - $qtyReject, 0);

            // 🔥 INI YANG BENAR-BENAR MENGUPDATE TABEL cutting_bundles
            $bundle->update([
                'qty_ok' => $qtyOk,
                'qty_reject' => $qtyReject,
                'status' => 'qc_done',
                'notes' => $row['qc_notes'] ?? $bundle->notes,
            ]);

            $totalOk += $qtyOk;
            $totalReject += $qtyReject;
        }

        // optional: simpan ringkasan di header batch
        $batch->update([
            'status' => 'qc_done',
            'total_output_qty' => $totalOk,
            'total_reject_qty' => $totalReject,
        ]);

        return redirect()
            ->route('production.wip_cutting_qc.show', $batch->id)
            ->with('success', "Hasil QC disimpan. OK: {$totalOk} pcs, Reject: {$totalReject} pcs.");
    }

}
