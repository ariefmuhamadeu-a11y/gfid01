<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class JournalService
{
    /**
     * === PEMBELIAN (KOMPATIBILITAS VERSI LAMA) ===
     * Dr 1201 Persediaan | Cr 1101 Kas (jika $cash = true) ATAU Cr 2101 Hutang (jika $cash = false)
     *
     * Sekarang diarahkan ke skema baru:
     * - Jika $cash = true  → dianggap dibayar penuh (cashPaid = amount, payableRemain = 0)
     * - Jika $cash = false → dianggap kredit penuh (cashPaid = 0, payableRemain = amount)
     *
     * Rekomendasi baru: panggil postPurchaseSplit() langsung dari controller
     * dengan parameter grand total & jumlah bayar.
     */
    public function postPurchase(string $refCode, string $date, float $amount, bool $cash = false, ?string $memo = null): void
    {
        if ($amount <= 0) {
            return;
        }

        $cashPaid = $cash ? $amount : 0.0;
        $payableRemain = max(0.0, $amount - $cashPaid);

        $this->postPurchaseSplit(
            refCode: $refCode,
            date: $date,
            inventoryAmount: $amount,
            cashPaid: $cashPaid,
            payableRemain: $payableRemain,
            cashAccountNote: $cash ? 'CASH' : null,
            memo: $memo
        );
    }

    /**
     * === PEMBELIAN (SKEMA BARU – DISARANKAN) ===
     * Dr 1201 Persediaan (inventoryAmount)
     * Cr 1101/1102 Kas/Bank (cashPaid, jika >0)
     * Cr 2101 Hutang Dagang (payableRemain, jika >0)
     *
     * $cashAccountNote opsional hanya untuk memberi label pada nama akun kas/bank di catatan.
     */
    public function postPurchaseSplit(
        string $refCode,
        string $date,
        float $inventoryAmount,
        float $cashPaid,
        float $payableRemain,
        ?string $cashAccountNote = null,
        ?string $memo = null
    ): void {
        if ($inventoryAmount <= 0) {
            return;
        }

        $dateObj = Carbon::parse($date);
        $dateStr = $dateObj->toDateString();
        $prefix = 'JRN-' . $dateObj->format('Ymd') . '-';

        // Akun dari tabel accounts
        $accPersediaan = DB::table('accounts')->where('code', '1201')->first(); // Persediaan Bahan
        $accCash = DB::table('accounts')->where('code', '1101')->first(); // Kas
        $accBank = DB::table('accounts')->where('code', '1102')->first(); // Bank (opsional)
        $accAP = DB::table('accounts')->where('code', '2101')->first(); // Hutang Dagang

        if (!$accPersediaan || !$accAP) {
            throw new \RuntimeException('Akun 1201/2101 belum ada. Seed AccountSeeder dulu.');
        }

        // Pilih akun kas/bank untuk kredit pembayaran (prioritas Bank lalu Kas)
        $creditCashAccountId = $accBank->id ?? $accCash->id ?? null;

        $autoMemo = sprintf(
            'Pembelian %s sebesar Rp %s (bayar: Rp %s, sisa: Rp %s)',
            $refCode,
            number_format($inventoryAmount, 0, ',', '.'),
            number_format(max(0, $cashPaid), 0, ',', '.'),
            number_format(max(0, $payableRemain), 0, ',', '.')
        );
        $memo = $memo ? mb_strimwidth($memo, 0, 255, '…', 'UTF-8') : $autoMemo;

        $lines = [];

        // Dr Persediaan (full)
        $lines[] = [
            'account_id' => $accPersediaan->id,
            'debit' => $inventoryAmount,
            'credit' => 0,
            'note' => 'Persediaan bertambah dari pembelian',
        ];

        // Cr Kas/Bank jika ada pembayaran saat ini
        if ($cashPaid > 0) {
            if (!$creditCashAccountId) {
                throw new \RuntimeException('Akun kas/bank (1101/1102) belum tersedia.');
            }
            $note = 'Kas/Bank keluar untuk pembelian';
            if ($cashAccountNote) {
                $note .= " ({$cashAccountNote})";
            }
            $lines[] = [
                'account_id' => $creditCashAccountId,
                'debit' => 0,
                'credit' => $cashPaid,
                'note' => $note,
            ];
        }

        // Cr Hutang jika ada sisa
        if ($payableRemain > 0) {
            $lines[] = [
                'account_id' => $accAP->id,
                'debit' => 0,
                'credit' => $payableRemain,
                'note' => 'Hutang timbul dari pembelian (sisa)',
            ];
        }

        $this->postBalanced($prefix, $dateStr, $refCode, $memo, $lines);
    }

    /**
     * === PEMBAYARAN HUTANG PEMBELIAN (DP/Termin) ===
     * Dr 2101 Hutang Dagang | Cr (1101 Kas / 1102 Bank) sesuai method
     *
     * @param string $method cash|bank|transfer|other
     *   - cash     -> 1101 (Kas)
     *   - bank     -> 1102 (Bank)
     *   - transfer -> 1102 (Bank)
     *   - other    -> 1101 (fallback Kas)
     */
    public function postPaymentPurchase(string $refCode, string $date, float $amount, string $method = 'cash', ?string $memo = null): void
    {
        if ($amount <= 0) {
            return;
        }

        $dateObj = Carbon::parse($date);
        $dateStr = $dateObj->toDateString();
        $prefix = 'JRN-' . $dateObj->format('Ymd') . '-';

        $accAP = DB::table('accounts')->where('code', '2101')->first(); // Hutang Dagang
        $accCash = DB::table('accounts')->where('code', '1101')->first(); // Kas
        $accBank = DB::table('accounts')->where('code', '1102')->first(); // Bank (opsional)

        if (!$accAP) {
            throw new \RuntimeException('Akun 2101 (Hutang Dagang) belum ada.');
        }

        // Tentukan akun kredit (kas/bank) berdasar method
        $method = strtolower($method);
        $creditAccountId = match ($method) {
            'bank', 'transfer' => ($accBank?->id ?? $accCash?->id),
            'cash', 'other' => $accCash?->id,
            default => $accCash?->id,
        };

        if (!$creditAccountId) {
            throw new \RuntimeException('Akun kas/bank (1101/1102) belum tersedia.');
        }

        $autoMemo = sprintf(
            'Pembayaran pembelian %s sebesar Rp %s',
            $refCode,
            number_format($amount, 0, ',', '.')
        );
        $memo = $memo ? mb_strimwidth($memo, 0, 255, '…', 'UTF-8') : $autoMemo;

        // Dr Hutang (2101), Cr Kas/Bank (1101/1102)
        $lines = [
            [
                'account_id' => $accAP->id,
                'debit' => $amount,
                'credit' => 0,
                'note' => 'Pelunasan/DP hutang pembelian',
            ],
            [
                'account_id' => $creditAccountId,
                'debit' => 0,
                'credit' => $amount,
                'note' => 'Kas/Bank keluar untuk pembayaran pembelian',
            ],
        ];

        $this->postBalanced($prefix, $dateStr, $refCode, $memo, $lines);
    }

    /**
     * === REVERSAL PEMBAYARAN (saat hapus PurchasePayment) ===
     * Dr Kas/Bank (1101/1102) | Cr Hutang (2101)
     */
    public function reversePaymentPurchase(string $refCode, string $date, float $amount, string $method = 'cash', ?string $memo = null): void
    {
        if ($amount <= 0) {
            return;
        }

        $dateObj = Carbon::parse($date);
        $dateStr = $dateObj->toDateString();
        $prefix = 'JRN-' . $dateObj->format('Ymd') . '-';

        $accAP = DB::table('accounts')->where('code', '2101')->first(); // Hutang Dagang
        $accCash = DB::table('accounts')->where('code', '1101')->first(); // Kas
        $accBank = DB::table('accounts')->where('code', '1102')->first(); // Bank

        if (!$accAP) {
            throw new \RuntimeException('Akun 2101 (Hutang Dagang) belum ada.');
        }

        $method = strtolower($method);
        $debitAccountId = match ($method) {
            'bank', 'transfer' => ($accBank?->id ?? $accCash?->id),
            'cash', 'other' => $accCash?->id,
            default => $accCash?->id,
        };

        if (!$debitAccountId) {
            throw new \RuntimeException('Akun kas/bank (1101/1102) belum tersedia.');
        }

        $autoMemo = sprintf(
            'Reversal pembayaran pembelian %s sebesar Rp %s',
            $refCode,
            number_format($amount, 0, ',', '.')
        );
        $memo = $memo ? mb_strimwidth($memo, 0, 255, '…', 'UTF-8') : $autoMemo;

        // Dr Kas/Bank, Cr Hutang
        $lines = [
            [
                'account_id' => $debitAccountId,
                'debit' => $amount,
                'credit' => 0,
                'note' => 'Reversal pembayaran pembelian (kas/bank kembali)',
            ],
            [
                'account_id' => $accAP->id,
                'debit' => 0,
                'credit' => $amount,
                'note' => 'Reversal: hutang bertambah kembali',
            ],
        ];

        $this->postBalanced($prefix, $dateStr, $refCode, $memo, $lines);
    }

    /**
     * Helper: Insert journal entry + lines & guard balance.
     * Menghasilkan kode JRN-YYYYMMDD-###
     */
    protected function postBalanced(string $prefix, string $dateStr, string $refCode, ?string $memo, array $lines): void
    {
        DB::transaction(function () use ($prefix, $dateStr, $refCode, $memo, $lines) {
            $seq = $this->nextSeq($prefix);
            $jrCode = $prefix . str_pad((string) $seq, 3, '0', STR_PAD_LEFT);

            $jrId = DB::table('journal_entries')->insertGetId([
                'code' => $jrCode,
                'date' => $dateStr,
                'ref_code' => $refCode,
                'memo' => $memo ? mb_strimwidth($memo, 0, 255, '…', 'UTF-8') : null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $rows = [];
            $totDr = 0.0;
            $totCr = 0.0;

            foreach ($lines as $l) {
                $dr = (float) ($l['debit'] ?? 0);
                $cr = (float) ($l['credit'] ?? 0);
                $rows[] = [
                    'journal_entry_id' => $jrId,
                    'account_id' => $l['account_id'],
                    'debit' => $dr,
                    'credit' => $cr,
                    'note' => $l['note'] ?? null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
                $totDr += $dr;
                $totCr += $cr;
            }

            if (round($totDr - $totCr, 2) !== 0.00) {
                throw new \RuntimeException("Jurnal tidak balance: {$jrCode}");
            }

            DB::table('journal_lines')->insert($rows);
        });
    }

    /**
     * Penomoran sequence JRN-YYYYMMDD-###
     */
    protected function nextSeq(string $prefix): int
    {
        $max = DB::table('journal_entries')
            ->where('code', 'like', $prefix . '%')
            ->selectRaw("MAX(CAST(SUBSTR(code, ?) AS INTEGER)) AS maxnum", [strlen($prefix) + 1])
            ->value('maxnum');

        return ((int) $max) + 1;
    }
}
