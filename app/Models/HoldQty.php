<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HoldQty extends Model
{
    use HasFactory;

 public function user()
    {
        return $this->belongsTo(User::class, 'user_id')->select('id', 'name');
    }
     public function godowns()
    {
        return $this->belongsTo(Godowns::class, 'godowns_id')->select('id','name');
    }
     public function inventory()
    {
        return $this->belongsTo(Inventory::class, 'inventories_id')->with(['product']);
    }
}
