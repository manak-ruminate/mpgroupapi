<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;
class Godowns extends Model
{
    use HasFactory;
    
    public function grodownsuser()
    {
         return $this->hasMany(User::class, 'godowns_id');
    
}
}
