<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockOutHistory extends Model
{
    use HasFactory;
    
   public function product()
{
    return $this->belongsTo(Product::class, 'sku_code','sku')->with(['category','subcategory','brand']);
}
}
