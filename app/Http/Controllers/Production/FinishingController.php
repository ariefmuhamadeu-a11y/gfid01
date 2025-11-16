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

    public function show(FinishingBatch $finishingBatch)
    {
        $finishingBatch->load([
            'sewingBatch.productionBatch',
            'employee',
            'lines.sewingLine.cuttingBundle.item',
        ]);

        // dd($finishingBatch); // boleh dipakai debug, tapi nanti dihapus kalau sudah ok

        return view('production.finishing.show', compact('finishingBatch'));
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
        // dd($sewingBatch);
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

    public function edit(FinishingBatch $finishingBatch)
    {
        $finishingBatch->load('sewingBatch.productionBatch', 'lines.sewingLine.cuttingBundle', 'employee');
        // dd($finishingBatch);

        return view('production.finishing.edit', [
            'finishingBatch' => $finishingBatch,
        ]);
    }

    public function update(Request $request, FinishingBatch $finishingBatch)
    {
        // $finishingBatch->load('lines.bundle');
        // $finishingBatch->load('lines.cuttingBundle');
        $finishingBatch->load('lines.sewingLine.cuttingBundle');

        $validated = $request->validate([
            'lines' => ['required', 'array'],
            'lines.*.id' => ['required', 'integer', 'exists:finishing_bundle_lines,id'],
            'lines.*.qty_ok' => ['required', 'integer', 'min:0'],
            'lines.*.note' => ['nullable', 'string', 'max:255'],
        ]);

        $linesInput = $validated['lines'];

        // Validasi: qty_ok <= qty_input per line
        foreach ($linesInput as $index => $input) {
            $lineId = $input['id'];

            /** @var FinishingLine|null $line */
            $line = $finishingBatch->lines->firstWhere('id', $lineId);

            if (!$line) {
                return back()
                    ->withInput()
                    ->withErrors([
                        "lines.$index.id" => "Data line tidak valid untuk batch finishing ini.",
                    ]);
            }

            $qtyInput = (int) ($line->qty_input ?? 0);
            $qtyOk = (int) $input['qty_ok'];

            if ($qtyOk > $qtyInput) {
                $bundle = $line->sewingLine->cuttingBundle ?? null;
                $code = $bundle->code ?? $bundle->bundle_code ?? $line->id;

                return back()
                    ->withInput()
                    ->withErrors([
                        "lines.$index.qty_ok" =>
                        "Qty OK untuk bundle {$code} melebihi qty input ({$qtyInput}).",
                    ]);
            }

        }

        // Simpan & hitung total
        DB::transaction(function () use ($finishingBatch, $linesInput) {
            $totalInput = 0;
            $totalOk = 0;
            $totalReject = 0;

            foreach ($linesInput as $index => $input) {
                $lineId = $input['id'];

                /** @var FinishingLine|null $line */
                $line = $finishingBatch->lines->firstWhere('id', $lineId);
                if (!$line) {
                    continue;
                }

                $qtyInput = (int) ($line->qty_input ?? 0);
                $qtyOk = (int) $input['qty_ok'];
                $qtyReject = max($qtyInput - $qtyOk, 0);

                $line->qty_ok = $qtyOk;
                $line->qty_reject = $qtyReject;
                $line->note = $input['note'] ?? null;
                $line->save();

                $totalInput += $qtyInput;
                $totalOk += $qtyOk;
                $totalReject += $qtyReject;
            }

            $finishingBatch->total_qty_input = $totalInput;
            $finishingBatch->total_qty_ok = $totalOk;
            $finishingBatch->total_qty_reject = $totalReject;

            // kalau status masih 'in_progress' atau 'draft' biarin,
            // finished_at nanti diisi di complete()
            $finishingBatch->save();
        });

        return redirect()
            ->route('production.finishing.edit', $finishingBatch)
            ->with('success', 'Hasil finishing berhasil disimpan.');
    }

    public function complete(FinishingBatch $finishing)
    {
        $finishing->status = 'done';
        $finishing->finished_at = now();
        $finishing->save();

        return redirect()
            ->route('production.finishing.show', $finishing)
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
