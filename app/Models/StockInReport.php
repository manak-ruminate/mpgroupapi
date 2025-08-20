<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockInReport extends Model
{
    public function godownsName()
{
    return $this->belongsTo(Godowns::class, 'godowns_id');
}
 public function product()
{
    return $this->belongsTo(Product::class, 'sku_code','sku')->with(['category','subcategory','brand']);
}
 
}
