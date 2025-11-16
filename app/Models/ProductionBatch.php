<?php

namespace App\Models;

use App\Models\CuttingBundle;
use App\Models\ExternalTransfer;
use App\Models\ProductionBatchMaterial;
use App\Models\Warehouse;
use App\Models\WipItem;
use Illuminate\Database\Eloquent\Factories\HasFactory; // nanti kalau sudah ada
use Illuminate\Database\Eloquent\Model;

// nanti kalau sudah ada

class ProductionBatch extends Model
{
    use HasFactory;

    protected $table = 'production_batches';

    protected $fillable = [
        'code',
        'stage',
        'status',
        'operator_code',
        'from_warehouse_id',
        'to_warehouse_id',
        'external_transfer_id',
        'date_received',
        'started_at',
        'finished_at',
        'total_output_qty',
        'total_reject_qty',
        'notes',
    ];

    protected $casts = [
        'date_received' => 'date',
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
        'total_output_qty' => 'decimal:2',
        'total_reject_qty' => 'decimal:2',

    ];

    /* =========================
     * RELATIONS
     * ========================= */

    /**
     * Gudang asal (misal: KONTRAKAN)
     */
    public function fromWarehouse()
    {
        return $this->belongsTo(Warehouse::class, 'from_warehouse_id');
    }

    /**
     * Gudang tujuan (misal: CUT-EXT-MRF)
     */
    public function toWarehouse()
    {
        return $this->belongsTo(Warehouse::class, 'to_warehouse_id');
    }

    /**
     * Dokumen external transfer sumber batch ini
     */
    public function externalTransfer()
    {
        return $this->belongsTo(ExternalTransfer::class, 'external_transfer_id');
    }

    /**
     * (Optional) Relasi ke cutting_bundles nanti
     * 1 batch memiliki banyak iket/bundle hasil cutting
     */
    public function cuttingBundles()
    {
        return $this->hasMany(CuttingBundle::class, 'production_batch_id');
    }

    /**
     * (Optional) Relasi ke WIP Items (hasil akhir cutting yang siap ke QC/sewing)
     */
    public function wipItems()
    {
        return $this->hasMany(WipItem::class, 'production_batch_id');
    }

    /* =========================
     * SCOPES BANTUAN
     * ========================= */

    /**
     * Scope hanya batch cutting
     */
    public function scopeCutting($query)
    {
        return $query->where('stage', 'cutting');
    }

    /**
     * Scope filter status
     */
    public function scopeStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    // app/Models/ProductionBatch.php

    public function bundles()
    {
        return $this->hasMany(CuttingBundle::class, 'production_batch_id');
    }

    public function materials()
    {
        return $this->hasMany(ProductionBatchMaterial::class);
    }

    public function sewingBatches()
    {
        return $this->hasMany(SewingBatch::class);
    }

}
