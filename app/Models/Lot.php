<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Lot extends Model
{
    protected $fillable = ['item_id', 'code', 'unit', 'initial_qty', 'unit_cost', 'date'];

    // LOT PUNYA RELASI KE ITEM
    public function item()
    {
        return $this->belongsTo(Item::class, 'item_id');
    }

}
