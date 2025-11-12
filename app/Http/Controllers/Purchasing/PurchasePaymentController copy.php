<?php

// app/Http/Controllers/Purchasing/PurchasePaymentController.php
namespace App\Http\Controllers\Purchasing;

use App\Http\Controllers\Controller;
use App\Models\PurchaseInvoice;
use App\Models\PurchasePayment;
use App\Services\JournalService;
use App\Services\PurchasePaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PurchasePaymentController extends Controller
{
    /** Tambah pembayaran (DP/termin) */
    public function store(Request $r, PurchasePaymentService $pps, JournalService $journal, $invoiceId)
    {
        $data = $r->validate([
            'date' => ['required', 'date'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'method' => ['required', 'in:cash,bank,transfer,other'],
            'ref_no' => ['nullable', 'string', 'max:64'],
            'note' => ['nullable', 'string', 'max:255'],
        ]);

        $invoice = PurchaseInvoice::with('payments')->findOrFail($invoiceId);

        // guard: tidak boleh overpay
        $already = (float) $invoice->payments()->sum('amount');
        $grand = (float) $invoice->grand_total;
        if ($already + (float) $data['amount'] - $grand > 0.00001) {
            return back()->withErrors(['amount' => 'Pembayaran melebihi sisa tagihan.']);
        }

        DB::transaction(function () use ($data, $invoice, $pps, $journal) {
            $payment = PurchasePayment::create([
                'purchase_invoice_id' => $invoice->id,
                'date' => $data['date'],
                'amount' => $data['amount'],
                'method' => $data['method'],
                'ref_no' => $data['ref_no'] ?? null,
                'note' => $data['note'] ?? null,
            ]);

            // Jurnal pembayaran: 2101 Hutang Dagang (Debit) vs 1101 Kas/Bank (Kredit)
            // Jika invoice awal dicatat sebagai hutang (non-cash), ini akan mengurangi 2101.
            $journal->postPaymentPurchase(
                refCode: $invoice->code . '/PAY-' . $payment->id,
                date: $payment->date->toDateString(),
                amount: (float) $payment->amount,
                method: $payment->method, // bisa mapping ke akun kas/bank
                memo: $payment->note
            );

            // Recalc status & paid_amount
            $pps->recalc($invoice->fresh('payments'));
        });

        return redirect()->route('purchasing.invoices.show', $invoice->id)
            ->with('ok', 'Pembayaran tersimpan.');
    }

    /** Hapus pembayaran (reversal) */
    public function destroy(PurchasePaymentService $pps, JournalService $journal, $invoiceId, $paymentId)
    {
        $invoice = PurchaseInvoice::findOrFail($invoiceId);
        $payment = \App\Models\PurchasePayment::where('purchase_invoice_id', $invoiceId)->findOrFail($paymentId);

        DB::transaction(function () use ($invoice, $payment, $pps, $journal) {
            // Jurnal reversal pembayaran (kembalikan ke posisi hutang)
            $journal->reversePaymentPurchase(
                refCode: $invoice->code . '/PAY-' . $payment->id,
                date: now()->toDateString(),
                amount: (float) $payment->amount,
                method: $payment->method,
                memo: 'Reversal payment #' . $payment->id,
            );

            $payment->delete();
            $pps->recalc($invoice->fresh('payments'));
        });

        return back()->with('ok', 'Pembayaran dihapus & jurnal reversal dibuat.');
    }
}
