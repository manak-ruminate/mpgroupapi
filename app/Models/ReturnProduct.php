<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReturnProduct extends Model
{
    use HasFactory;
    public function inventory()
{
    return $this->belongsTo(Inventory::class, 'inventories_id')->with(['product']);
}
}
