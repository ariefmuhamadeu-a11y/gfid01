<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FinishingBundleLine extends Model
{
    protected $table = 'finishing_bundle_lines';

    protected $fillable = [
        'finishing_batch_id',
        'sewing_bundle_line_id',
        'qty_input',
        'qty_ok',
        'qty_reject',
        'note',
    ];

    public function finishingBatch()
    {
        return $this->belongsTo(FinishingBatch::class);
    }

    public function sewingLine()
    {
        return $this->belongsTo(SewingBundleLine::class, 'sewing_bundle_line_id');
    }
}
