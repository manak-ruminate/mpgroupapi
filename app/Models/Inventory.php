<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use  App\Models\Godowns;
use  App\Models\HoldQty;
use Illuminate\Database\Eloquent\SoftDeletes;
class Inventory extends Model
{
    use HasFactory;
     use SoftDeletes;
 protected $appends = ['total_hold_qty'];
    
    public function godownsName()
{
    return $this->belongsTo(Godowns::class, 'godowns_id');
}
public function stockout()
{
     return $this->hasMany(StockOutHistory::class, 'inventory_id');
}
public function holdqty()
{
     return $this->hasMany(HoldQty::class, 'inventories_id');
}
 public function getTotalHoldQtyAttribute()
    {
     return HoldQty::where('inventories_id',$this->id)->sum('hold_qty');
}
 public function product()
{
    return $this->belongsTo(Product::class, 'sku_code','sku')->with(['category','subcategory','brand']);
}


 
}
