<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\WebClient;
use App\Models\FeedBack;
use App\Models\Enquiry;
use App\Models\Request as ClientRequest;
use App\Models\Godowns;
use App\Models\FileUpload;
class UserController extends Controller
{
    public function add(Request $request)
    {
        $data = new WebClient();
        $data->name = $request->get('name');
        $data->last_name = $request->get('lastName');
        $data->email = $request->get('email');
        $data->phone = $request->get('phone');
        $data->save();
        return response(['data'=>$data,'status'=>200]);
    }
    
    public function index()
    {
        $data = WebClient::all();
        return response(['data'=>$data,'status'=>200]);
    }
    
    
    public function add_feedback(Request $request)
    {
        $data = new FeedBack();
        $data->name = $request->get('name');
        $data->location = $request->get('location');
        $data->review = $request->get('review');
        $data->save();
         return response(['msg' =>'feedback added succesfully','data'=>$data,'status'=>200]);
    }
    
     public function add_enquiry(Request $request)
    {
        $data = new Enquiry();
        $data->name = $request->get('name');
        $data->phone = $request->get('phone');
        $data->email = $request->get('email');
        $data->remarks = $request->get('remarks');
        $data->save();
         return response(['msg' =>'enquiry added succesfully','data'=>$data,'status'=>200]);
    }
    
     public function enquiry(Request $request)
    {
        $data =  Enquiry::all();
        return response(['data' =>$data]);
    }
     public function add_request(Request $request)
    {
        $data = new ClientRequest();
        $data->name = $request->get('name');
        $data->phone = $request->get('phone');
        $data->email = $request->get('email');
        
        $data->qty = $request->get('qty'); 
        $data->sku = $request->get('sku');
        $data->save();
         return response(['msg' =>'request added succesfully','data'=>$data,'status'=>200]);
    }
    
      public function request(){
            $data =  ClientRequest::all();
          return response(['data' =>$data]);
      }
    public function godowns()
    {
         $data = Godowns::where('is_active',1)->get();
        return response(['data' => $data]);
    }
   public function file()
{
  
    $data = FileUpload::all();
 $data->map(function ($item) {
        $item->catalog = asset('storage/' . $item->catalog);
        $item->image = asset('storage/' . $item->image);
        return $item;
    });

    return response()->json(['data' => $data]);
}

}
