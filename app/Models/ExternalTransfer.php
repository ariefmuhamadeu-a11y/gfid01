<?php

namespace App\Models;

use App\Models\ExternalTransferBundleLine;
use App\Models\ExternalTransferLine;
use App\Models\ProductionBatch;
use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Model;

class ExternalTransfer extends Model
{
    protected $fillable = [
        'code',
        'from_warehouse_id',
        'to_warehouse_id',
        'date',
        'process',
        'operator_code',
        'transfer_type',
        'direction',
        'status',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'date' => 'date',
    ];

    public function fromWarehouse()
    {
        return $this->belongsTo(Warehouse::class, 'from_warehouse_id');
    }

    public function toWarehouse()
    {
        return $this->belongsTo(Warehouse::class, 'to_warehouse_id');
    }

    public function lines()
    {
        return $this->hasMany(ExternalTransferLine::class);
    }

    public function bundleLines()
    {
        return $this->hasMany(ExternalTransferBundleLine::class);
    }

    public function productionBatch()
    {
        return $this->hasOne(ProductionBatch::class, 'external_transfer_id');
    }
}
