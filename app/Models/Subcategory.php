<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Subcategory extends Model
{
    use HasFactory;
    
      protected $appends = ['subcateimages'];
    public function getsubcateimagesAttribute()
    {
        return asset('storage/' . $this->image);
    }
}
