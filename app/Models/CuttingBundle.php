<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Warehouse;
use App\Models\ExternalTransferBundleLine;
use App\Models\SewingQcLine;

class CuttingBundle extends Model
{
    use HasFactory;

    protected $table = 'cutting_bundles';

    protected $fillable = [
        'production_batch_id',
        'lot_id',
        'item_id',
        'item_code',
        'bundle_code',
        'bundle_no',
        'qty_cut',
        'qty_ok', // ⬅ TAMBAH
        'qty_reject', // ⬅ TAMBAH
        'unit',
        'status',
        'current_warehouse_id',
        'sewing_status',
        'qty_reserved_for_sewing',
        'qty_in_transfer',
        'qty_sewn_ok',
        'qty_sewn_reject',
        'notes',
    ];

    protected $casts = [
        'qty_cut' => 'decimal:2',
        'qty_ok' => 'decimal:2',
        'qty_reject' => 'decimal:2',
        'qty_reserved_for_sewing' => 'decimal:2',
        'qty_in_transfer' => 'decimal:2',
        'qty_sewn_ok' => 'decimal:2',
        'qty_sewn_reject' => 'decimal:2',
    ];

    /* =====================
     * RELASI
     * ===================== */

    public function batch()
    {
        return $this->belongsTo(ProductionBatch::class, 'production_batch_id');
    }

    public function lot()
    {
        return $this->belongsTo(Lot::class, 'lot_id');
    }

    public function item()
    {
        return $this->belongsTo(Item::class, 'item_id');
    }

    public function productionBatch()
    {
        return $this->belongsTo(ProductionBatch::class);
    }

    public function currentWarehouse()
    {
        return $this->belongsTo(Warehouse::class, 'current_warehouse_id');
    }

    public function transferBundleLines()
    {
        return $this->hasMany(ExternalTransferBundleLine::class, 'cutting_bundle_id');
    }

    public function availableQtyForSewing(): float
    {
        $qtyOk = (float) ($this->qty_ok ?? 0);
        $reserved = (float) ($this->qty_reserved_for_sewing ?? 0);
        $inTransfer = (float) ($this->qty_in_transfer ?? 0);
        $sewn = (float) ($this->qty_sewn_ok ?? 0) + (float) ($this->qty_sewn_reject ?? 0);

        return max(0, $qtyOk - $reserved - $inTransfer - $sewn);
    }

    public function sewingQcLines()
    {
        return $this->hasMany(SewingQcLine::class, 'cutting_bundle_id');
    }

}
