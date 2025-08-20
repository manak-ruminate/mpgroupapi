<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockOutReport extends Model
{
    public function product()
{
    return $this->belongsTo(Product::class, 'sku_code','sku')->with(['category','subcategory','brand']);
}

   public function stockInReports()
    {
        return $this->belongsTo(StockInReport::class, 'stock_report_id')->with('godownsName');
    }
  public function godownsName()
{
    return $this->belongsTo(Godowns::class, 'godowns_id');
}

}
