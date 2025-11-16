<?php

namespace App\Http\Controllers\Production;

use App\Http\Controllers\Controller;
use App\Models\CuttingBundle;
use App\Models\SewingQcLine;
use Illuminate\Http\Request;

class SewingQcController extends Controller
{
    public function index()
    {
        $bundles = CuttingBundle::with('item')
            ->where('sewing_status', 'in_sewing')
            ->orderByDesc('updated_at')
            ->paginate(20);

        return view('production.sewing_qc.index', compact('bundles'));
    }

    public function show(CuttingBundle $bundle)
    {
        $bundle->load(['item', 'sewingQcLines' => fn($q) => $q->latest()]);

        return view('production.sewing_qc.show', compact('bundle'));
    }

    public function update(Request $request, CuttingBundle $bundle)
    {
        $totalForQc = (float) ($bundle->qty_ok ?? $bundle->qty_cut ?? 0);
        $alreadyQc = (float) ($bundle->qty_sewn_ok ?? 0) + (float) ($bundle->qty_sewn_reject ?? 0);
        $remaining = max(0, $totalForQc - $alreadyQc);

        $data = $request->validate([
            'qty_ok' => ['required', 'numeric', 'min:0'],
            'qty_reject' => ['required', 'numeric', 'min:0'],
            'note' => ['nullable', 'string', 'max:255'],
            'external_transfer_id' => ['nullable', 'integer'],
        ]);

        $qtyOk = (float) $data['qty_ok'];
        $qtyReject = (float) $data['qty_reject'];

        if ($qtyOk + $qtyReject > $remaining) {
            return back()
                ->withInput()
                ->withErrors(['qty_ok' => 'Total QC melebihi qty tersisa (' . $remaining . ').']);
        }

        SewingQcLine::create([
            'cutting_bundle_id' => $bundle->id,
            'external_transfer_id' => $data['external_transfer_id'] ?? null,
            'qc_date' => now()->toDateString(),
            'qty_input' => $qtyOk + $qtyReject,
            'qty_ok' => $qtyOk,
            'qty_reject' => $qtyReject,
            'note' => $data['note'] ?? null,
        ]);

        $remainingAfterQc = max(0, $totalForQc - ($alreadyQc + $qtyOk + $qtyReject));

        $bundle->update([
            'qty_sewn_ok' => (float) $bundle->qty_sewn_ok + $qtyOk,
            'qty_sewn_reject' => (float) $bundle->qty_sewn_reject + $qtyReject,
            'sewing_status' => $remainingAfterQc > 0 ? 'in_sewing' : 'sewing_qc_done',
        ]);

        // TODO: hubungkan ke InventoryService / Finishing jika sudah siap

        return redirect()
            ->route('production.wip_sewing_qc.index')
            ->with('success', 'Hasil QC sewing tersimpan.');
    }
}
