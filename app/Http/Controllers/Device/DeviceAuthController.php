<?php

namespace App\Http\Controllers\Device;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use App\Models\User;
use App\Mail\OtpMail;
use Carbon\Carbon;
use App\Models\Device;
class DeviceAuthController extends Controller
{
    public function send_otp(Request $request)
{
    
    $request->validate([
        'email' => 'required|email',
    ]);

    $user = User::where('email', $request->input('email'))->where('role_id',3)->where('is_active',1)->first();
    if(!$user) {
        return response()->json(['error' => 'User not found.'], 404);
    }
    if($user)
    {
        if($request->get('email') == 'testapp.ris@gmail.com'){
             $expiration = Carbon::now()->addMinutes(15);
                $user->otp_expires_at = $expiration;
                $user->save();
            return response()->json([
                            'status' => true,
                            'message' => 'Email send',
                        ], 200);
        }
        else{
    $otp = rand(100000, 999999); 
    $expiration = Carbon::now()->addMinutes(15);
    $user->otp = $otp;
    $user->otp_expires_at = $expiration;
    $user->save();
    Mail::to($user->email)->send(new OtpMail($otp));
    return response()->json(['message' => 'OTP sent to your email.'], 200);
        }
    }
}
    public function verifyOtp(Request $request)
{
    $request->validate([
        'email' => 'required|email',
        'otp' => 'required|integer',
        'imei' => 'required',
        'version' => 'required',
        'device_name' =>'required',
    ]);
         $user = User::where('email', $request->input('email'))->where('is_active',1)->where('role_id',3)->first();
  if ($user && $user->otp && $user->otp_expires_at && Carbon::now()->lessThan($user->otp_expires_at)) {
        if ($request->otp == $user->otp)
        {
            
             if($request->get('email')== 'testapp.ris@gmail.com')
                {
                    $device_data =  Device::where('user_id',$user->id)->where('imei',$request->get('imei'))->where('version',$request->get('version'))->first();
                    if($device_data){
                          $user['token'] =  $user->createToken(env('APP_KEY'))->plainTextToken;
                          $user['device'] = $device_data;
                         return response()->json([
                            'status' => true,
                            'message' => 'Device info save successuflly',
                            'data' => $user,
                        ], 200);
                    }
                    else{
                    $device = new Device();
                        $device->imei = $request->get('imei');
                        $device->version = $request->get('version');
                        $device->devicename = $request->get('device_name');
                        $device->user_id = $user->id;
                        // $device->app_version = $request->get('app_version') ? $request->get('app_version') : "";
                        $device->device_active = 1;
                        $device->app_version = $request->get('app_version');
                        $device->save();
                        $user['token'] =  $user->createToken(env('APP_KEY'))->plainTextToken;

                        $user['device'] = Device::where('version', $request->get('version'))
                            // ->where('device_name', $request->get('device_name'))
                            ->where('imei', $request->get('imei'))
                            ->where('version', $request->get('version'))
                            ->where('user_id', $user->id)
                            ->latest()
                            ->first();
                        return response()->json([
                            'status' => true,
                            'message' => 'Device info save successuflly',
                            'data' => $user,
                        ], 200);
                    }
                }
                else{
                    
              $device_data =  Device::where('user_id',$user->id)->where('imei',$request->get('imei'))->where('version',$request->get('version'))
              ->where('devicename',$request->get('device_name'))
              ->latest()->first();
            if($device_data)
            {
             $data = $user->toArray(); 
            $data['token'] = $user->createToken('APP_KEY')->plainTextToken; 
            $data['device'] = $device_data;
              return response()->json(['message' => 'OTP verified successfully.', 'data' => $data], 200);
            }
            else{
            $device = new Device();
            $device->user_id =$user->id;
            $device->imei = $request->get('imei'); // Fixed the typo from 'imel' to 'imei'
            $device->version = $request->get('version');
              $device->devicename = $request->get('device_name');
            $device->device_active = 0;
            $device->app_version = $request->get('app_version');
            $device->save();
            $data = $user->toArray(); 
            $data['token'] = $user->createToken('APP_KEY')->plainTextToken; 
            $data['device'] = $device;
              return response()->json(['message' => 'OTP verified successfully.', 'data' => $data], 200);
            }
                    
                }
        }
        return response()->json(['error' => 'Invalid OTP.'], 400);
    }
     return response()->json(['error' => 'OTP has expired or is invalid.'], 400);
}
}
