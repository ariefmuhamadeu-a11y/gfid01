<?php

namespace App\Http\Controllers\Production;

use App\Http\Controllers\Controller;
use App\Models\FinishingBatch;
use App\Models\FinishingBundleLine;
use App\Models\SewingBatch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class FinishingController extends Controller
{
    public function index()
    {
        $sewingDone = SewingBatch::where('status', 'done')
            ->with('productionBatch')
            ->get();

        return view('production.finishing.index', compact('sewingDone'));
    }

    public function createFromSewing(SewingBatch $sewingBatch)
    {
        if ($sewingBatch->status !== 'done') {
            return back()->with('error', 'Sewing belum selesai.');
        }

        $sewingBatch->load('lines.sewingBatch', 'lines.cuttingBundle');

        $employee = Auth::user()->employee;
        $code = $this->generateCode($employee?->code);

        return view('production.finishing.create_from_sewing', compact('sewingBatch', 'code', 'employee'));
    }

    public function storeFromSewing(Request $request, SewingBatch $sewingBatch)
    {
        if ($sewingBatch->status !== 'done') {
            return back()->with('error', 'Sewing belum selesai.');
        }

        DB::beginTransaction();

        try {
            $employee = Auth::user()->employee;
            $code = $this->generateCode($employee?->code);

            $totalInput = $sewingBatch->lines->sum('qty_ok');

            $finishing = FinishingBatch::create([
                'code' => $code,
                'sewing_batch_id' => $sewingBatch->id,
                'employee_id' => $employee?->id,
                'status' => 'draft',
                'total_qty_input' => $totalInput,
                'total_qty_ok' => 0,
                'total_qty_reject' => 0,
                'started_at' => now(),
            ]);

            foreach ($sewingBatch->lines as $line) {
                if ($line->qty_ok > 0) {
                    FinishingBundleLine::create([
                        'finishing_batch_id' => $finishing->id,
                        'sewing_bundle_line_id' => $line->id,
                        'qty_input' => $line->qty_ok,
                    ]);
                }
            }

            DB::commit();

            return redirect()->route('production.finishing.edit', $finishing)
                ->with('success', 'Finishing Batch berhasil dibuat.');
        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->with('error', $e->getMessage());
        }
    }

    public function edit(FinishingBatch $finishing)
    {
        $finishing->load('lines.sewingLine.cuttingBundle');
        return view('production.finishing.edit', compact('finishing'));
    }

    public function update(Request $request, FinishingBatch $finishing)
    {
        $request->validate([
            'lines' => 'required|array',
        ]);

        DB::transaction(function () use ($request, $finishing) {
            $totalOK = 0;
            $totalReject = 0;

            foreach ($finishing->lines as $line) {
                $data = $request->lines[$line->id];

                if ($data['qty_ok'] + $data['qty_reject'] > $line->qty_input) {
                    throw new \Exception("Qty melebihi input pada bundle {$line->sewingLine->cuttingBundle->code}");
                }

                $line->update([
                    'qty_ok' => $data['qty_ok'],
                    'qty_reject' => $data['qty_reject'],
                    'note' => $data['note'] ?? null,
                ]);

                $totalOK += $data['qty_ok'];
                $totalReject += $data['qty_reject'];
            }

            $finishing->update([
                'total_qty_ok' => $totalOK,
                'total_qty_reject' => $totalReject,
            ]);
        });

        return back()->with('success', 'Data finishing berhasil disimpan.');
    }

    public function complete(FinishingBatch $finishing)
    {
        $finishing->status = 'done';
        $finishing->finished_at = now();
        $finishing->save();

        return redirect()->route('production.finishing.show', $finishing)
            ->with('success', 'Finishing selesai.');
    }

    protected function generateCode($emp)
    {
        $date = now()->format('ymd');
        $emp = $emp ?: 'EMP';
        $prefix = "FIN-{$date}-{$emp}-";

        $last = FinishingBatch::where('code', 'like', "$prefix%")
            ->orderBy('code', 'desc')
            ->first();

        $next = $last ? intval(substr($last->code, -3)) + 1 : 1;
        return $prefix . str_pad($next, 3, '0', STR_PAD_LEFT);
    }
}
