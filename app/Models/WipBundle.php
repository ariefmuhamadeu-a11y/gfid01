<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WipBundle extends Model
{
    protected $table = 'wip_bundles';

    protected $fillable = [
        'wip_item_id',
        'bundle_no',
        'qty',
        'unit',
        'status',
    ];

    protected $casts = [
        'qty' => 'float',
    ];

    public function wipItem()
    {
        return $this->belongsTo(WipItem::class, 'wip_item_id');
    }
}
