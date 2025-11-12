<?php

// app/Models/PurchasePayment.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchasePayment extends Model
{
    protected $fillable = [
        'purchase_invoice_id', 'date', 'amount', 'method', 'ref_no', 'note',
    ];
    protected $casts = [
        'date' => 'date',
        'amount' => 'decimal:0',
    ];

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(PurchaseInvoice::class, 'purchase_invoice_id');
    }
}
