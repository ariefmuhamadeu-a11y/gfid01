<?php

namespace App\Models;

use App\Models\Item;
use App\Models\Lot;
use App\Models\ProductionBatch;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductionBatchMaterial extends Model
{
    use HasFactory;

    protected $table = 'production_batch_materials';

    protected $fillable = [
        'production_batch_id',
        'lot_id',
        'item_id',
        'item_code',
        'qty_planned',
        'qty_used',
        'unit',
        'notes',
    ];

    protected $casts = [
        'qty_planned' => 'decimal:4',
        'qty_used' => 'decimal:4',
    ];

    /* =========================
     * RELATIONS
     * ========================= */

    /**
     * Header batch (cutting batch)
     */
    public function batch()
    {
        return $this->belongsTo(ProductionBatch::class, 'production_batch_id');
    }

    /**
     * LOT kain yang dipakai
     */
    public function lot()
    {
        return $this->belongsTo(Lot::class, 'lot_id');
    }

    /**
     * Item kain (FLC280BLK, dll)
     */
    public function item()
    {
        return $this->belongsTo(Item::class, 'item_id');
    }

}
