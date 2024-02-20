<?php

namespace App\Http\Controllers\Auth;



use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\Auth\EmailverificationRequest;
use App\Models\User;
use Otp;

class EmailverificationController extends Controller
{
    private $otp;


    public function __construct(){
        $this->otp = new Otp;
    }


    public function email_verification(EmailverificationRequest $request){
        $otp2 = $this->otp->validate($request->email, $request->otp);
        if(!$otp2->status){
            return response()->json(['error'=>$otp2],401);
        }
    
        $user = user::where('email',$request->email)->first();
            $user->update(['email_verified_at' => now()]);
        $success['success'] = true;
        return response([
            'user' => $user,
            'otp' => $request->otp
        ], 200);
        return response()->json($success,200);
    }
}
