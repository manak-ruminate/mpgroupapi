<?php

namespace App\Http\Controllers\Super;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\LoginHistory;
use App\Models\Device;;
class UserController extends Controller
{
    public function index()
    {
        $data = User::select('users.*','godowns.name as godownsname','roles.name as rolename')
        ->leftjoin('godowns','users.godowns_id','godowns.id')
        ->leftjoin('roles','users.role_id','roles.id')
            // ->where('users.is_active',1)
            ->get();
        return response(['data' => $data]);
    }

    public function add(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'email' =>'required|email|unique:users,email',
            'role' =>'required',
            'empCode' =>'required',
        ]);

        $data = new  User();
        $data->name = $request->get('name') ? $request->get('name') :  $data->name;
        $data->empCode = $request->get('empCode');
        $data->email = $request->get('email') ? $request->get('email') : $data->email; 
        $data->password =\Hash::make($request->get('password'));
        $data->phone = $request->get('phone') ? $request->get('phone') :$data->phone;
        $data->last_name = $request->get('last_name') ? $request->get('last_name') : $data->last_name;
        $data->role_id = $request->get('role') ? $request->get('role') : $data->role_id;
        $data->godowns_id = $request->get('godowns_id') ?  $request->get('godowns_id') : '';
        // $data->empCode = $request->get('empCode');
        $data->use_password = $request->get('password') ? $request->get('password') : $data->use_password ;
        $data->save();
        return response(['msg' => 'user created succesfully']);
    }
    public function update(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'id' => 'required',
        ]);

       $data =  User::find($request->get('id'));
        $data->name = $request->get('name');
        // $data->email = $request->get('email');
        $data->password =$request->get('password') ? \Hash::make($request->get('password')) :$data->password;
        $data->phone = $request->get('phone');
        $data->last_name = $request->get('last_name');
        $data->role_id = $request->get('role');
        $data->is_active = $request->get('is_active');
        $data->use_password = $request->get('password') ? $request->get('password') : '' ;
        $data->save();
        return response(['msg' => 'User update succesfully']);
    }
    public function delete($id)
    {
       
        $data = User::find($id);
        if ($data->delete()) {
            return response(['msg' => 'User delete successfully']);
        }
    }
    public function log_info()
    {
      $data = LoginHistory::select(
        'login_histories.*',
        'users.name as username',
        'users.role_id as userRole',
        'users.empCode as empCode',
        'godowns.name as godownsName',
        'roles.name as roleName' // Assuming you want the role name
    )
    ->join('users', 'login_histories.user_id', '=', 'users.id')
    ->join('godowns', 'login_histories.godown_id', '=', 'godowns.id')
    ->join('roles', 'users.role_id', '=', 'roles.id') // Fixed join condition
    ->get();

return response()->json(['data' => $data]);

    }
    
    public function device()
    {
        $data = Device::select('devices.*','users.name as userName','users.empCode as empCode','users.email as userEmail')
        ->join('users','devices.user_id','users.id')
        ->get();
         return response(['data' => $data]);
    }
    
    public function device_edit(Request $request)
    {
         $data = Device::find($request->get('id'));
        $data->device_active = $request->get('device_active');
        $data->save();
         return response(['msg' => 'device info update succesfully']);
    }
}
