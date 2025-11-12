<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class JournalController extends Controller
{
    /** Daftar jurnal (index) */
    public function index(Request $r)
    {
        $q = trim((string) $r->get('q', ''));
        $range = $r->get('range'); // "YYYY-MM-DD s/d YYYY-MM-DD"

        $rows = DB::table('journal_entries as je')
            ->when($q, fn($qq) => $qq->where(function ($w) use ($q) {
                $w->where('je.code', 'like', "%{$q}%")
                    ->orWhere('je.ref_code', 'like', "%{$q}%")
                    ->orWhere('je.memo', 'like', "%{$q}%");
            }))
            ->when($range, function ($qq) use ($range) {
                if (preg_match('~^\s*(\d{4}-\d{2}-\d{2})\s*s/d\s*(\d{4}-\d{2}-\d{2})\s*$~', $range, $m)) {
                    $qq->whereBetween('je.date', [$m[1], $m[2]]);
                }
            })
            ->select('je.id', 'je.code', 'je.date', 'je.ref_code', 'je.memo')
            ->orderByDesc('je.date')
            ->orderByDesc('je.id')
            ->paginate(20);

        return view('accounting.journals.index', compact('rows', 'q', 'range'));
    }

    /** Detail 1 jurnal (show) */
    public function show(int $id)
    {
        $jr = DB::table('journal_entries')->where('id', $id)->first();
        abort_if(!$jr, 404);

        $lines = DB::table('journal_lines as jl')
            ->join('accounts as a', 'a.id', '=', 'jl.account_id')
            ->where('jl.journal_entry_id', $id)
            ->orderBy('jl.id')
            ->get(['a.code as account_code', 'a.name as account_name', 'jl.debit', 'jl.credit', 'jl.note']);

        // total
        $totalDebit = $lines->sum('debit');
        $totalCredit = $lines->sum('credit');

        return view('accounting.journals.show', compact('jr', 'lines', 'totalDebit', 'totalCredit'));
    }

    public function ledger(Request $r)
    {
        $accountId = $r->integer('account_id') ?: null;
        $rangeRaw = trim((string) $r->get('range', ''));

        // === Resolve tanggal ===
        $start = null;
        $end = null;
        if ($rangeRaw && preg_match('~^\s*(\d{4}-\d{2}-\d{2})\s*s/d\s*(\d{4}-\d{2}-\d{2})\s*$~', $rangeRaw, $m)) {
            $start = Carbon::parse($m[1])->startOfDay()->toDateString();
            $end = Carbon::parse($m[2])->endOfDay()->toDateString();
        } else {
            // default 1 bulan berjalan
            $start = now()->startOfMonth()->toDateString();
            $end = now()->endOfMonth()->toDateString();
            $rangeRaw = $start . ' s/d ' . $end;
        }
        $startPrev = Carbon::parse($start)->subDay()->toDateString(); // H-1 untuk saldo awal

        // === Data akun untuk filter ===
        $accounts = DB::table('accounts')
            ->orderBy('code')
            ->get(['id', 'code', 'name', 'normal']); // kolom 'normal' opsional (Debit|Kredit)

        // === KPI periode (semua akun / satu akun) ===
        $kpiBase = DB::table('journal_lines as jl')
            ->join('journal_entries as je', 'je.id', '=', 'jl.journal_entry_id')
            ->when($accountId, fn($q) => $q->where('jl.account_id', $accountId))
            ->whereBetween('je.date', [$start, $end]);

        $kpi = (array) $kpiBase
            ->selectRaw('COALESCE(SUM(jl.debit),0) as total_debit, COALESCE(SUM(jl.credit),0) as total_credit')
            ->first();

        // === Ambil daftar akun yang relevan (punya transaksi di periode / perlu saldo awal) ===
        // 1) akun dengan transaksi di periode
        $acctPeriod = DB::table('journal_lines as jl')
            ->join('journal_entries as je', 'je.id', '=', 'jl.journal_entry_id')
            ->when($accountId, fn($q) => $q->where('jl.account_id', $accountId))
            ->whereBetween('je.date', [$start, $end])
            ->groupBy('jl.account_id')
            ->pluck('jl.account_id')
            ->all();

        // 2) akun dengan saldo awal (sebelum start)
        $acctOpening = DB::table('journal_lines as jl')
            ->join('journal_entries as je', 'je.id', '=', 'jl.journal_entry_id')
            ->when($accountId, fn($q) => $q->where('jl.account_id', $accountId))
            ->where('je.date', '<=', $startPrev)
            ->groupBy('jl.account_id')
            ->pluck('jl.account_id')
            ->all();

        $accountIds = array_values(array_unique(array_merge($acctPeriod, $acctOpening)));
        if ($accountId && empty($accountIds)) {
            $accountIds = [$accountId];
        }

        // Siapkan metadata akun (code,name,normal)
        $acctMeta = DB::table('accounts')
            ->whereIn('id', $accountIds ?: [0])
            ->pluck('name', 'id')
            ->map(fn($v, $k) => $v);

        $metaFull = DB::table('accounts')
            ->whereIn('id', $accountIds ?: [0])
            ->get(['id', 'code', 'name', 'normal'])
            ->keyBy('id');

        // === Hitung saldo awal per akun (s.d. H-1) ===
        $openingRows = DB::table('journal_lines as jl')
            ->join('journal_entries as je', 'je.id', '=', 'jl.journal_entry_id')
            ->whereIn('jl.account_id', $accountIds ?: [0])
            ->where('je.date', '<=', $startPrev)
            ->groupBy('jl.account_id')
            ->selectRaw('jl.account_id, COALESCE(SUM(jl.debit),0) as d, COALESCE(SUM(jl.credit),0) as c')
            ->get()
            ->keyBy('account_id');

        $openingByAccount = [];
        foreach ($accountIds as $aid) {
            $row = $openingRows->get($aid);
            $openingByAccount[$aid] = (float) ($row->d ?? 0) - (float) ($row->c ?? 0);
        }

        // === Ambil transaksi periode per akun ===
        $lines = DB::table('journal_lines as jl')
            ->join('journal_entries as je', 'je.id', '=', 'jl.journal_entry_id')
            ->whereIn('jl.account_id', $accountIds ?: [0])
            ->whereBetween('je.date', [$start, $end])
            ->orderBy('je.date')
            ->orderBy('je.id')
            ->orderBy('jl.id')
            ->get([
                'jl.account_id',
                'je.date',
                'je.code as jcode',
                'je.ref_code as ref',
                'jl.debit',
                'jl.credit',
                'jl.note',
            ]);

        // === Bangun grouped ===
        $grouped = [];
        foreach ($accountIds as $aid) {
            $meta = $metaFull->get($aid);
            $rows = [];

            // saldo berjalan diawali saldo awal
            $running = $openingByAccount[$aid] ?? 0.0;

            // jika ingin tampil baris saldo awal di tabel → kirim penanda
            $showOpening = true;

            // kumpulkan baris periode
            foreach ($lines->where('account_id', $aid) as $ln) {
                $d = (float) $ln->debit;
                $c = (float) $ln->credit;
                $running = $running + $d - $c;

                $rows[] = [
                    'date' => Carbon::parse($ln->date)->toDateString(),
                    'jcode' => $ln->jcode,
                    'ref' => $ln->ref,
                    'note' => $ln->note,
                    'debit' => $d,
                    'credit' => $c,
                    'balance' => $running,
                    'code' => $meta->code ?? '',
                    'name' => $meta->name ?? '',
                    'normal' => $meta->normal ?? (Str::startsWith($meta->code ?? '', '1') ? 'Debit' : 'Kredit'),
                ];
            }

            // Inject info akun + saldo awal di elemen pertama (untuk header & baris saldo awal)
            if (!empty($rows)) {
                $rows[0]['opening'] = (float) ($openingByAccount[$aid] ?? 0);
                $rows[0]['show_opening'] = $showOpening;
                $rows[0]['code'] = $meta->code ?? '';
                $rows[0]['name'] = $meta->name ?? '';
                $rows[0]['normal'] = $rows[0]['normal'] ?? 'Debit';
                $grouped[$aid] = $rows;
            } else {
                // Tidak ada transaksi di periode tapi ada saldo awal → tetap tampil satu “header kosong”
                if (array_key_exists($aid, $openingByAccount)) {
                    $grouped[$aid] = [[
                        'date' => $start,
                        'jcode' => '',
                        'ref' => '',
                        'note' => '',
                        'debit' => 0,
                        'credit' => 0,
                        'balance' => (float) $openingByAccount[$aid],
                        'opening' => (float) $openingByAccount[$aid],
                        'show_opening' => true,
                        'code' => $meta->code ?? '',
                        'name' => $meta->name ?? '',
                        'normal' => $meta->normal ?? (Str::startsWith($meta->code ?? '', '1') ? 'Debit' : 'Kredit'),
                    ]];
                }
            }
        }

        // === Kirim ke view (selaras dengan ledger.blade yang kamu pakai) ===
        return view('accounting.ledger.index', [
            'accounts' => $accounts,
            'accountId' => $accountId,
            'range' => $rangeRaw,
            'grouped' => $grouped,
            'kpi' => [
                'total_debit' => (float) ($kpi['total_debit'] ?? 0),
                'total_credit' => (float) ($kpi['total_credit'] ?? 0),
            ],
        ]);
    }

}
