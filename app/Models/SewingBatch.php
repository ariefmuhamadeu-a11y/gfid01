<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SewingBatch extends Model
{
    protected $fillable = [
        'code',
        'production_batch_id',
        'employee_id',
        'status',
        'total_qty_input',
        'total_qty_ok',
        'total_qty_reject',
        'started_at',
        'finished_at',
    ];

    public function productionBatch()
    {
        return $this->belongsTo(ProductionBatch::class);
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function lines()
    {
        return $this->hasMany(SewingBundleLine::class);
    }

    public function finishingBatch()
    {
        return $this->hasOne(FinishingBatch::class);
    }

}
