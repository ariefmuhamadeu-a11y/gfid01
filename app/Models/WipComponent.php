<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WipComponent extends Model
{
    protected $table = 'wip_components';

    protected $fillable = [
        'wip_item_id',
        'lot_id',
        'item_id',
        'item_code',
        'qty',
        'unit',
        'type',
    ];

    protected $casts = [
        'qty' => 'float',
    ];

    public function wipItem()
    {
        return $this->belongsTo(WipItem::class, 'wip_item_id');
    }

    public function lot()
    {
        return $this->belongsTo(Lot::class, 'lot_id');
    }

    public function item()
    {
        return $this->belongsTo(Item::class, 'item_id');
    }
}
