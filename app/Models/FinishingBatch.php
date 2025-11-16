<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FinishingBatch extends Model
{
    protected $fillable = [
        'code', 'sewing_batch_id', 'employee_id', 'status',
        'total_qty_input', 'total_qty_ok', 'total_qty_reject',
        'started_at', 'finished_at',
    ];

    public function sewingBatch()
    {
        return $this->belongsTo(SewingBatch::class);
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function lines()
    {
        return $this->hasMany(FinishingBundleLine::class);
    }
}
