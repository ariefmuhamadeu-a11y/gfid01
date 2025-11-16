<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExternalTransferBundleLine extends Model
{
    protected $fillable = [
        'external_transfer_id',
        'cutting_bundle_id',
        'qty',
        'received_qty',
        'defect_qty',
        'unit',
        'notes',
    ];

    protected $casts = [
        'qty' => 'decimal:2',
        'received_qty' => 'decimal:2',
        'defect_qty' => 'decimal:2',
    ];

    public function transfer()
    {
        return $this->belongsTo(ExternalTransfer::class, 'external_transfer_id');
    }

    public function cuttingBundle()
    {
        return $this->belongsTo(CuttingBundle::class);
    }
}
