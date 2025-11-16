<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SewingQcLine extends Model
{
    protected $fillable = [
        'cutting_bundle_id',
        'external_transfer_id',
        'qc_date',
        'qty_input',
        'qty_ok',
        'qty_reject',
        'note',
    ];

    protected $casts = [
        'qc_date' => 'date',
        'qty_input' => 'decimal:2',
        'qty_ok' => 'decimal:2',
        'qty_reject' => 'decimal:2',
    ];

    public function bundle()
    {
        return $this->belongsTo(CuttingBundle::class, 'cutting_bundle_id');
    }

    public function externalTransfer()
    {
        return $this->belongsTo(ExternalTransfer::class, 'external_transfer_id');
    }
}
