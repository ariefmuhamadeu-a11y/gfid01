<?php

namespace App\Services;

use App\Models\PurchaseInvoice;

class PurchasePaymentService
{
    public function recalc(PurchaseInvoice $invoice): void
    {
        $paid = (float) $invoice->payments()->sum('amount');
        $grand = (float) ($invoice->grand_total ?? 0.0);

        $status = 'unpaid';
        if ($grand <= 0.0) {
            // kalau grand_total nol, anggap paid (tidak ada kewajiban)
            $status = 'paid';
        } else {
            if ($paid > 0 && $paid + 0.00001 < $grand) {
                $status = 'partial';
            }

            if ($paid + 0.00001 >= $grand) {
                $status = 'paid';
            }

        }

        $invoice->forceFill([
            'paid_amount' => round($paid, 2),
            'payment_status' => $status,
        ])->save();
    }
}
