<?php

namespace App\Http\Controllers\Super;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Godowns;
use App\Models\FileUpload;
class GodownsController extends Controller
{
    public function index()
    {
        $data = Godowns::where('is_active',1)->get();
        return response(['data' => $data]);
    }

    public function add(Request $request)
    {
        $valiadator = \Validator::make($request->all(), [
            'name' => 'required',
        ]);

        $data = new Godowns();
        $data->name = $request->get('name');
        $data->latitude  = $request->get('latitude');
        $data->longitude = $request->get('longitude');
        // $data->showroomname = $request->get('showroomname');
        $data->save();
        return response(['msg' => 'Godowns created successfully']);
    }

    public function update(Request $request)
    {
        $valiadator = \Validator::make($request->all(), [
            'name' => 'required',
            'id' => 'id',
        ]);

        $data =  Godowns::find($request->get('id'));
        $data->name = $request->get('name');
        $data->latitude  = $request->get('latitude');
        $data->longitude = $request->get('longitude');
        $data->showroomname = $request->get('showroomname');
        $data->save();
        return response(['msg' => 'Godowns update successfully']);
    }

    public function delete($id)
    {
     

        $data =  Godowns::find($id);
        $data->delete();
        return response(['msg' => 'Godowns update successfully']);
    }
    
    public function file_add(Request $request)
    {
        $manuals =null;
        $brochure = null;
        $catalog = null;
        $image = null;
        if($request->hasFile('catalog')){
        $catalog = $request->file('catalog')->store('catalogs', 'public');
        }
         if($request->hasFile('image')){
        $image = $request->file('image')->store('catalogs/image', 'public');
        }
        $data = new FileUpload();
        $data->title =$request->get('title');
        $data->description = $request->get('description');
        $data->catalog = $catalog;
        $data->image = $image;
         $data->save();
         return response(['msg'=>'file upload succesfully']);
    }
    
    public function file_update(Request $request)
    {
           $data =  FileUpload::find($request->get('id'));
     
        $catalog =  $data->catalog;
         $image = $data->image;
        if($request->hasFile('catalog')){
        $catalog = $request->file('catalog')->store('catalogs', 'public');
        }
      if($request->hasFile('image')){
        $image = $request->file('image')->store('catalogs/image', 'public');
        }
        $data->title =$request->get('title');
        $data->description = $request->get('description');
        $data->catalog = $catalog;
        $data->image = $image;
         $data->save();
         return response(['msg'=>'file upload succesfully']);
    }
    public function file()
{
  
    $data = FileUpload::all();
 $data->map(function ($item) {
        $item->catalog = asset('storage/' . $item->catalog);
        return $item;
    });

    return response()->json(['data' => $data]);
}
 public function file_delete($id)
{
  
     $data = FileUpload::find($id);
 if($data->delete()){
     return response()->json(['msg' => 'data delete succesfull']);
 }

    return response()->json(['msg' => 'not found']);
}


}
