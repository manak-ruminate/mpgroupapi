<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Godowns;
class AdminController extends Controller
{
   public function godown()
   {
       $data = Godowns::where('is_active',1)->get();
       return response(['data'=>$data]);
   }
}
