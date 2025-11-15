<?php

namespace App\Http\Controllers\Production;

use App\Http\Controllers\Controller;
use App\Models\ProductionBatch;
use App\Models\SewingBatch;
use App\Models\SewingBundleLine;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class WipSewingController extends Controller
{
    /**
     * Daftar batch yang sudah QC_DONE (waiting sewing) + status sewing batch.
     */
    public function index()
    {
        // asumsi: kolom qc_status di production_batches: waiting_qc, in_progress, qc_done
        $batches = ProductionBatch::where('qc_status', 'qc_done')
            ->withCount('sewingBatches')
            ->orderByDesc('created_at')
            ->get();

        // nanti kita buat view: resources/views/production/wip_sewing/index.blade.php
        return view('production.wip_sewing.index', compact('batches'));
    }

    /**
     * Halaman konfirmasi pembuatan SewingBatch dari sebuah ProductionBatch.
     * - load bundle hasil cutting (qty_ok > 0)
     * - generate kode sewing batch awal
     */
    public function createFromBatch(ProductionBatch $batch)
    {
        // Pastikan batch sudah QC done
        if ($batch->qc_status !== 'qc_done') {
            return redirect()
                ->route('production.wip_sewing.index')
                ->with('error', 'Batch ini belum selesai QC.');
        }

        // Ambil hanya bundle yang punya qty_ok > 0
        $batch->load(['cuttingBundles' => function ($q) {
            $q->where('qty_ok', '>', 0);
        }]);

        if ($batch->cuttingBundles->isEmpty()) {
            return redirect()
                ->route('production.wip_sewing.index')
                ->with('error', 'Tidak ada bundle OK yang bisa dijahit dari batch ini.');
        }

        // Ambil employee yang login (kalau ada)
        $user = Auth::user();
        $employee = $user->employee ?? null;
        $employeeCode = $employee?->code;

        // Generate kode sewing otomatis
        $code = $this->generateCode($employeeCode);

        return view('production.wip_sewing.create_from_batch', [
            'batch' => $batch,
            'code' => $code,
            'employee' => $employee,
        ]);
    }

    /**
     * Proses simpan sewing_batch + sewing_bundle_lines (AUTO GENERATE dari hasil QC).
     */
    public function storeFromBatch(Request $request, ProductionBatch $batch)
    {
        // Validasi sederhana, bisa ditambah kalau mau pilih operator / catatan
        $validated = $request->validate([
            'employee_id' => ['nullable', 'exists:employees,id'],
        ]);

        // Pastikan batch sudah QC done
        if ($batch->qc_status !== 'qc_done') {
            return redirect()
                ->route('production.wip_sewing.index')
                ->with('error', 'Batch ini belum selesai QC.');
        }

        // Jangan double-generate sewing batch kalau kita maunya 1:1
        if ($batch->sewingBatches()->exists()) {
            return redirect()
                ->route('production.wip_sewing.index')
                ->with('error', 'Batch ini sudah memiliki Sewing Batch.');
        }

        // Ambil bundle yang punya qty_ok > 0
        $batch->load(['cuttingBundles' => function ($q) {
            $q->where('qty_ok', '>', 0);
        }]);

        if ($batch->cuttingBundles->isEmpty()) {
            return redirect()
                ->route('production.wip_sewing.index')
                ->with('error', 'Tidak ada bundle OK yang bisa dijahit dari batch ini.');
        }

        $user = Auth::user();
        $employee = $user->employee ?? null;

        $employeeId = $validated['employee_id'] ?? ($employee?->id);
        $employeeCode = $employee?->code;

        $code = $this->generateCode($employeeCode);

        DB::beginTransaction();

        try {
            // Hitung total input dari semua bundle (qty_ok)
            $totalInput = $batch->cuttingBundles->sum('qty_ok');

            // Buat SewingBatch
            /** @var SewingBatch $sewingBatch */
            $sewingBatch = SewingBatch::create([
                'code' => $code,
                'production_batch_id' => $batch->id,
                'employee_id' => $employeeId,
                'status' => 'draft',
                'total_qty_input' => $totalInput,
                'total_qty_ok' => 0,
                'total_qty_reject' => 0,
                'started_at' => now(),
            ]);

            // Buat SewingBundleLine untuk tiap CuttingBundle
            foreach ($batch->cuttingBundles as $bundle) {
                SewingBundleLine::create([
                    'sewing_batch_id' => $sewingBatch->id,
                    'cutting_bundle_id' => $bundle->id,
                    'qty_input' => $bundle->qty_ok, // sumber dari hasil QC
                    'qty_ok' => 0,
                    'qty_reject' => 0,
                    'note' => null,
                ]);
            }

            DB::commit();

            return redirect()
                ->route('production.wip_sewing.edit', $sewingBatch)
                ->with('success', 'Sewing batch berhasil dibuat dari hasil QC.');
        } catch (\Throwable $e) {
            DB::rollBack();

            return redirect()
                ->route('production.wip_sewing.index')
                ->with('error', 'Gagal membuat Sewing Batch: ' . $e->getMessage());
        }
    }

    /**
     * Lihat detail sewing batch.
     * (Nanti kita isi view-nya di step berikut)
     */
    public function show(SewingBatch $sewingBatch)
    {
        $sewingBatch->load(['productionBatch', 'employee', 'lines.cuttingBundle']);

        return view('production.wip_sewing.show', compact('sewingBatch'));
    }

    /**
     * Form input hasil sewing per bundle.
     */
    public function edit(SewingBatch $sewingBatch)
    {
        $sewingBatch->load(['productionBatch', 'employee', 'lines.cuttingBundle']);

        return view('production.wip_sewing.edit', compact('sewingBatch'));
    }

    /**
     * Simpan update qty_ok / qty_reject per bundle.
     * (Logic detail akan kita buat di STEP 3)
     */
    public function update(Request $request, SewingBatch $sewingBatch)
    {
        $sewingBatch->load('lines');

        $data = $request->validate([
            'lines' => ['required', 'array'],
            'lines.*.id' => ['required', 'integer', 'exists:sewing_bundle_lines,id'],
            'lines.*.qty_ok' => ['required', 'integer', 'min:0'],
            'lines.*.qty_reject' => ['required', 'integer', 'min:0'],
            'lines.*.note' => ['nullable', 'string', 'max:255'],
        ]);

        $linesInput = $data['lines'];

        // Validasi custom: qty_ok + qty_reject <= qty_input per baris
        foreach ($sewingBatch->lines as $line) {
            if (!isset($linesInput[$line->id])) {
                continue;
            }

            $input = $linesInput[$line->id];
            $ok = (int) $input['qty_ok'];
            $reject = (int) $input['qty_reject'];

            if ($ok + $reject > $line->qty_input) {
                return back()
                    ->withInput()
                    ->withErrors([
                        "lines.{$line->id}.qty_ok" =>
                        "Total OK + Reject untuk bundle {$line->cuttingBundle?->code} melebihi qty input ({$line->qty_input}).",
                    ]);
            }
        }

        // Kalau semua valid, simpan & hitung ulang total di sewing_batches
        DB::transaction(function () use ($sewingBatch, $linesInput) {
            $totalOk = 0;
            $totalReject = 0;

            foreach ($sewingBatch->lines as $line) {
                if (!isset($linesInput[$line->id])) {
                    continue;
                }

                $input = $linesInput[$line->id];

                $line->qty_ok = (int) $input['qty_ok'];
                $line->qty_reject = (int) $input['qty_reject'];
                $line->note = $input['note'] ?? null;
                $line->save();

                $totalOk += $line->qty_ok;
                $totalReject += $line->qty_reject;
            }

            $sewingBatch->total_qty_ok = $totalOk;
            $sewingBatch->total_qty_reject = $totalReject;
            $sewingBatch->save();
        });

        return redirect()
            ->route('production.wip_sewing.edit', $sewingBatch)
            ->with('success', 'Hasil sewing berhasil disimpan.');
    }

    /**
     * Menandai sewing batch selesai (done).
     * (Nanti akan ada validasi total qty dll)
     */
    public function complete(Request $request, SewingBatch $sewingBatch)
    {
        // nanti kita lengkapi di STEP 3
        return back()->with('info', 'Complete sewing belum diimplementasikan.');
    }

    /**
     * Generate kode sewing: SEW-YYMMDD-EMP-###
     */
    protected function generateCode(?string $employeeCode): string
    {
        $datePart = now()->format('ymd');
        $empPart = $employeeCode ?: 'XXX';

        $prefix = "SEW-{$datePart}-{$empPart}-";

        $last = SewingBatch::where('code', 'like', $prefix . '%')
            ->orderBy('code', 'desc')
            ->first();

        $next = 1;
        if ($last) {
            $lastSeq = (int) substr($last->code, -3);
            $next = $lastSeq + 1;
        }

        $seqPart = str_pad($next, 3, '0', STR_PAD_LEFT);

        return $prefix . $seqPart;
    }
}
