<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Notifications\Notifiable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Notifications\EmailVerificationNotification;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Otp;

class AuthenticationController extends Controller
{
    public function register(RegisterRequest $request)
    {
        $request->validated();

        $userData = [
            'name'=> $request->name,
            'email'=> $request->email,
            'password' => Hash::make($request->password),
        ];

        $user = User::create($userData);
        $token = $user->createToken('EagonApp')->plainTextToken;

        return response([
            'user' => $user,
            'token' => $token
        ],201);
    }

            public function login(LoginRequest $request)
        {
            $request->validated();
            $user = User::whereEmail($request->email)->first();

            if (!$user || !Hash::check($request->password, $user->password)) {
                return response(['message' => 'invalid credentials'], 422);
            }

            // Create an instance of the notification
            $notification = new EmailVerificationNotification();

            // Send the notification
            $user->notify($notification);

            // Access the generated OTP from the notification instance
            $generatedOtp = $notification->generatedOtp;

            // Continue with your logic
            $token = $user->createToken('EagonApp')->plainTextToken;

            return response([
                'user' => $user,
                'token' => $token
            ], 200);
        }
}