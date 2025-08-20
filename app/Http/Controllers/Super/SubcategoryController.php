<?php

namespace App\Http\Controllers\Super;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Subcategory;
use App\Models\Finish;
class SubcategoryController extends Controller
{
    public function index(){
    $data = Subcategory:: where('is_active',1)->get();
    return response(['data'=>$data]);
    }
    public function add(Request $request)
    {
        
        $valiadator = \Validator::make($request->all(),[
            'name'=>'required',
            'category_id  ' =>'required',
            ]);
            $image = null;
            if($request->hasFile('image'))
            {
            $image =$request->file('image')->store('subcategory','public');    
            }
            $data = new Subcategory();
            $data->name = $request->get('name');
            $data->image = $image ??null;
            $data->category_id  = $request->get('category_id');
            $data->save();
            return response(['msg'=>'category created successfully']);
    }
    
    
    
    public function update(Request $request)
    {
        $valiadator = \Validator::make($request->all(),[
            'name'=>'required',
            'id'=>'required',
            ]);
             $data = Subcategory::find($request->get('id'));
             $image = $data->image;
            if($request->hasFile('image'))
            {
            $image =$request->file('image')->store('subcategory','public');    
            }
           
            $data->name = $request->get('name');
             $data->image = $image ??null;
            $data->save();
            return response(['msg'=>'category update successfully']);
    }
    
    public function delete($id)
    {
          $data = Subcategory::find($id);
            
            $data->delete();
            return response(['msg'=>'category update successfully']);
    }
    public function finish()
    {
        $data = Finish::all();
         return response(['data'=>$data]);
    }
    public function finish_add(Request $request)
    {
        $data = new Finish();
        $data->name = $request->get('name');
        $data->save();
        return response(['msg'=>'finish added succesfully']);
    }
    public function finish_update(Request $request)
    {
        $data =Finish::find($request->get('id'));
        $data->name = $request->get('name');
        $data->save();
        return response(['msg'=>'finish update succesfully']);
    }
    public function finish_delete($id)
    {
        $data = Finish::find($id);
        $data->delete();
        return response(['msg'=>'finish delete succesfully']);
    }
}
