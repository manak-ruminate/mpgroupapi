<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Inventory;
use Illuminate\Database\Eloquent\SoftDeletes;
class Product extends Model
{
    use HasFactory;
     use SoftDeletes;
    //   protected $fillable = ['is_web'];
    protected $appends  = ['ImageUrl', 'ImageUrl2', 'ImageUrl3', 'ImageUrl4'];
    public function inventories()
    {
        return $this->hasMany(Inventory::class, 'product_id');
    }

    public function getImageUrlAttribute()
    {
        if ($this->image) {
            return asset('storage/' . $this->image);
        } else {
            return  asset('storage/img/no_image.jpeg');
        }
    }
    // 2
    public function getImageUrl2Attribute()
    {
        if ($this->image2) {
            return asset('storage/' . $this->image2);
        } else {
            return  asset('storage/img/no_image.jpeg');
        }
    }
    // 3
    public function getImageUrl3Attribute()
    {
        if ($this->image3) {
            return asset('storage/' . $this->image3);
        } else {
            return  asset('storage/img/no_image.jpeg');
        }
    }
    public function getImageUrl4Attribute()
    {
        if ($this->image4) {
            return asset('storage/' . $this->image4);
        } else {
            return  asset('storage/img/no_image.jpeg');
        }
    }

    public function stockInReports()
    {
        return $this->hasMany(StockInReport::class, 'sku_code','sku')->with('godownsName');
    }
    public function stockOutReports()
    {
        return $this->hasMany(StockOutReport::class, 'product_id');
    }
    public function inventory()
    {
        return $this->hasMany(Inventory::class, 'product_id')->with('godownsName');
    }
     public function category()
    {
        return $this->belongsTo(Category::class, 'category_id');
    }
     public function subcategory()
    {
        return $this->belongsTo(Subcategory::class, 'sub_category');
    }
       public function brand()
    {
        return $this->belongsTo(Brand::class, 'brand');
    }
   
}
