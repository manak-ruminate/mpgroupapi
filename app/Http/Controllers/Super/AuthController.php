<?php

namespace App\Http\Controllers\Super;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\LoginHistory;
class AuthController extends Controller
{
public function login(Request $request)
{
    $request->validate([
        'email' => 'required|email',
        'password' => 'required',
    ]);

    \Log::info('Login attempt', ['email' => $request->email]);

    if (Auth::attempt(['email' => $request->get('email'), 'password' => $request->get('password')])) {
        // if((Auth::user()->role_id ==1) &&(Auth::user()->role_id ==2)){
        if(Auth::user()->is_active ==1){
        $user = Auth::user();
        $user['grodownsname'] = $user->getGodownName();
        $token = $user->createToken('APP_KEY')->plainTextToken;
        $data = new LoginHistory();
        $data->user_id = $user->id;
        $data->godown_id = $user->godowns_id;
        $data->ip = $request->ip();
        $data->save();
        return response()->json([
            'token' => $token,
            'user' => $user,
        ]);
    }
        
    }
    // }

    return response()->json(['error' => 'Unauthorized'], 401);
}

}
