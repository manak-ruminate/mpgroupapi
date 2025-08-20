<?php

namespace App\Http\Controllers\Super;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Role;

class RoleController extends Controller
{
    public function index()
    {
        $data = Role::where('is_active',1)->get();
        return response(['data' => $data]);
    }

    public function add(Request $request)
    {
        $request->validate([
            'name' => 'required',
        ]);

        $data = new  Role();
        $data->name = $request->get('name');
        $data->save();
        return response(['msg' => 'Roles created succesfully']);
    }
    public function update(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'id' => 'required',
        ]);

        $data = Role::find($request->get('id'));
        $data->name = $request->get('name');
        $data->save();
        return response(['msg' => 'Roles update succesfully']);
    }
    public function delete($id)
    {
       
        $data = Role::find($id);
        if ($data->delete()) {
            return response(['msg' => 'role delete successfully']);
        }
    }
}
