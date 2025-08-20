<?php

namespace App\Http\Controllers\Super;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Uom;
class UomController extends Controller
{
    public function index()
    {
        $data = Uom::where('is_active',1)->get();
        return response(['data' => $data]);
    }

    public function add(Request $request)
    {
        $request->validate([
            'name' => 'required',
         ]);

        $data = new  Uom();
        $data->name = $request->get('name');
       
        $data->save();
        return response(['msg' => 'uom created succesfully']);
    }
    public function update(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'id' => 'required',
        ]);

       $data =  Uom::find($request->get('id'));
        $data->name = $request->get('name');
       
        $data->save();
        return response(['msg' => 'uom update succesfully']);
    }
    public function delete($id)
    {
       
        $data = Uom::find($id);
        if ($data->delete()) {
            return response(['msg' => 'uom delete successfully']);
        }
    }
}
