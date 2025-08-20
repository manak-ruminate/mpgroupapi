<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Subcategory;
class Category extends Model
{
    use HasFactory;
     public function subcategories()
    {
        return $this->hasMany(Subcategory::class);
         
    }
    public function products()
{
    return $this->hasMany(Product::class); // Assuming Product model has a category_id field
}
 public function sub_category()
    {
        return $this->hasMany(Subcategory::class, 'category_id');
    }
    
}
