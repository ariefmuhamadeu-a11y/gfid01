<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SewingBundleLine extends Model
{
    protected $fillable = [
        'sewing_batch_id',
        'cutting_bundle_id',
        'qty_input',
        'qty_ok',
        'qty_reject',
        'note',
    ];

    public function sewingBatch()
    {
        return $this->belongsTo(SewingBatch::class);
    }

    public function cuttingBundle()
    {
        return $this->belongsTo(CuttingBundle::class);
    }
}
